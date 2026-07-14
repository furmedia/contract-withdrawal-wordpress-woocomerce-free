<?php
namespace Furmedia\CWFW;

defined( 'ABSPATH' ) || exit;

class Security {
	private $settings;
	private $repository;

	public function __construct( Settings $settings, Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;
	}

	public function ensure_idempotency_token() {
		$session = $this->session();
		$token   = $session ? (string) $session->get( 'cwfw_idempotency', '' ) : '';
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) {
			$token = $this->random_token();
			if ( $session ) {
				$session->set( 'cwfw_idempotency', $token );
			}
		}
		return $token;
	}

	public function rotate_idempotency_token() {
		$token   = $this->random_token();
		$session = $this->session();
		if ( $session ) {
			$session->set( 'cwfw_idempotency', $token );
		}
		return $token;
	}

	public function validate_submission_tokens( $nonce, $idempotency ) {
		$known = $this->ensure_idempotency_token();
		return wp_verify_nonce( (string) $nonce, 'cwfw_submit' )
			&& preg_match( '/^[a-f0-9]{64}$/D', (string) $idempotency )
			&& hash_equals( $known, (string) $idempotency );
	}

	public function idempotency_hash( $token ) {
		return hash( 'sha256', (string) $token );
	}

	public function is_session_rate_limited() {
		$session = $this->session();
		if ( ! $session ) {
			return false;
		}
		$all    = $session->get( 'cwfw_recent_submissions', array() );
		$key    = $this->identity_key();
		$cutoff = time() - ( (int) $this->settings->get( 'session_rate_limit_window', 30 ) * MINUTE_IN_SECONDS );
		$recent = isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? $all[ $key ] : array();
		$recent = array_values(
			array_filter(
				$recent,
				static function ( $timestamp ) use ( $cutoff ) {
					return (int) $timestamp >= $cutoff;
				}
			)
		);
		$all[ $key ] = $recent;
		$session->set( 'cwfw_recent_submissions', $all );
		return count( $recent ) >= (int) $this->settings->get( 'session_rate_limit_count', 3 );
	}

	public function remember_submission() {
		$session = $this->session();
		if ( ! $session ) {
			return;
		}
		$this->is_session_rate_limited();
		$all           = $session->get( 'cwfw_recent_submissions', array() );
		$key           = $this->identity_key();
		$all[ $key ]   = isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? $all[ $key ] : array();
		$all[ $key ][] = time();
		$session->set( 'cwfw_recent_submissions', $all );
	}

	public function reserve_persistent_slot() {
		$window_seconds = (int) $this->settings->get( 'server_rate_limit_window', 30 ) * MINUTE_IN_SECONDS;
		$window_start   = (int) floor( time() / $window_seconds ) * $window_seconds;
		$remote_address = isset( $_SERVER['REMOTE_ADDR'] ) && is_scalar( $_SERVER['REMOTE_ADDR'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) ) : 'unknown';
		if ( '' === $remote_address ) {
			$remote_address = 'unknown';
		}
		$secret = (string) $this->settings->get( 'rate_limit_secret' );
		if ( '' === $secret ) {
			return false;
		}
		$bucket = hash_hmac( 'sha256', get_current_blog_id() . '|' . $window_start . '|' . $remote_address, $secret );
		return $this->repository->reserve_submission_slot(
			$bucket,
			gmdate( 'Y-m-d H:i:s', $window_start ),
			(int) $this->settings->get( 'server_rate_limit_count', 20 )
		);
	}

	public function remember_success( array $record ) {
		$session = $this->session();
		if ( ! $session ) {
			return;
		}
		$session->set(
			'cwfw_success',
			array(
				'withdrawal_id' => (int) $record['withdrawal_id'],
				'customer_id'   => (int) $record['customer_id'],
				'reference'     => (string) $record['reference'],
				'email_status'  => (string) $record['email_status'],
				'issued_at'     => time(),
			)
		);
	}

	public function success() {
		$session = $this->session();
		$value   = $session ? $session->get( 'cwfw_success', array() ) : array();
		if ( ! is_array( $value ) || empty( $value['withdrawal_id'] ) || ! isset( $value['customer_id'], $value['issued_at'] ) ) {
			return array();
		}
		if ( (int) $value['issued_at'] < time() - ( 30 * MINUTE_IN_SECONDS ) || (int) $value['customer_id'] !== get_current_user_id() ) {
			if ( $session ) {
				$session->set( 'cwfw_success', null );
			}
			return array();
		}
		return $value;
	}

	public function can_access_evidence( array $record ) {
		$user_id = get_current_user_id();
		if ( $user_id > 0 && (int) $record['customer_id'] === $user_id ) {
			return true;
		}
		$success = $this->success();
		return $success && (int) $success['withdrawal_id'] === (int) $record['withdrawal_id'] && (int) $record['customer_id'] === 0;
	}

	public function constant_time_equals( $known, $provided ) {
		return is_string( $known ) && is_string( $provided ) && '' !== $known && hash_equals( $known, $provided );
	}

	private function identity_key() {
		return get_current_blog_id() . ':' . get_current_user_id();
	}

	private function session() {
		return function_exists( 'WC' ) && WC() && WC()->session ? WC()->session : null;
	}

	private function random_token() {
		try {
			return bin2hex( random_bytes( 32 ) );
		} catch ( \Exception $exception ) {
			return hash( 'sha256', wp_generate_password( 64, true, true ) . microtime( true ) );
		}
	}
}
