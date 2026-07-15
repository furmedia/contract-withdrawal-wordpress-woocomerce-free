<?php
namespace Furmedia\Furmrowi;

defined( 'ABSPATH' ) || exit;

class Settings {
	const OPTION = 'furmrowi_settings';

	private $cache;

	public static function defaults() {
		$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : '';
		if ( ! $timezone || '+00:00' === $timezone ) {
			$timezone = 'Europe/Bucharest';
		}

		return array(
			'enabled'                     => 0,
			'business_name'               => (string) get_bloginfo( 'name' ),
			'business_address'            => '',
			'return_address'              => '',
			'contact_email'               => (string) get_option( 'admin_email' ),
			'contact_phone'               => '',
			'notification_email'          => (string) get_option( 'admin_email' ),
			'period_days'                 => 14,
			'return_cost_payer'           => 'consumer',
			'additional_return_cost_info' => '',
			'recent_orders_limit'         => 15,
			'session_rate_limit_count'    => 3,
			'session_rate_limit_window'   => 30,
			'server_rate_limit_count'     => 20,
			'server_rate_limit_window'    => 30,
			'footer_link_enabled'         => 1,
			'privacy_page_id'             => (int) get_option( 'wp_page_for_privacy_policy' ),
			'form_page_id'                => 0,
			'legal_page_id'               => 0,
			'timezone'                    => $timezone,
			'rate_limit_secret'           => self::generate_secret(),
			'legal_template_version'      => 'ro-art-11-1-2026-v1',
		);
	}

	public function all() {
		if ( null === $this->cache ) {
			$value       = get_option( self::OPTION, array() );
			$this->cache = wp_parse_args( is_array( $value ) ? $value : array(), self::defaults() );
		}
		return $this->cache;
	}

	public function get( $key, $fallback = null ) {
		$settings = $this->all();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	public function update( array $input ) {
		$clean = $this->sanitize( array_merge( $this->all(), $input ) );
		update_option( self::OPTION, $clean, false );
		$this->cache = $clean;
		return $clean;
	}

	public function sanitize( array $value ) {
		$defaults = self::defaults();
		$clean    = $defaults;
		$clean['enabled']             = empty( $value['enabled'] ) ? 0 : 1;
		$clean['footer_link_enabled'] = empty( $value['footer_link_enabled'] ) ? 0 : 1;

		foreach ( array( 'business_name', 'contact_phone', 'legal_template_version' ) as $key ) {
			$clean[ $key ] = isset( $value[ $key ] ) ? sanitize_text_field( (string) $value[ $key ] ) : $defaults[ $key ];
		}
		$clean['business_address']            = isset( $value['business_address'] ) ? sanitize_textarea_field( (string) $value['business_address'] ) : '';
		$clean['return_address']              = isset( $value['return_address'] ) ? sanitize_textarea_field( (string) $value['return_address'] ) : '';
		$clean['additional_return_cost_info'] = isset( $value['additional_return_cost_info'] ) ? sanitize_textarea_field( (string) $value['additional_return_cost_info'] ) : '';
		$clean['contact_email']               = sanitize_email( isset( $value['contact_email'] ) ? (string) $value['contact_email'] : '' );
		$clean['notification_email']          = sanitize_email( isset( $value['notification_email'] ) ? (string) $value['notification_email'] : '' );
		$clean['return_cost_payer']           = isset( $value['return_cost_payer'] ) && 'professional' === $value['return_cost_payer'] ? 'professional' : 'consumer';
		$clean['period_days']                 = self::clamp( isset( $value['period_days'] ) ? $value['period_days'] : 14, 14, 365 );
		$clean['recent_orders_limit']         = self::clamp( isset( $value['recent_orders_limit'] ) ? $value['recent_orders_limit'] : 15, 5, 50 );
		$clean['session_rate_limit_count']    = self::clamp( isset( $value['session_rate_limit_count'] ) ? $value['session_rate_limit_count'] : 3, 1, 20 );
		$clean['session_rate_limit_window']   = self::clamp( isset( $value['session_rate_limit_window'] ) ? $value['session_rate_limit_window'] : 30, 5, 1440 );
		$clean['server_rate_limit_count']     = self::clamp( isset( $value['server_rate_limit_count'] ) ? $value['server_rate_limit_count'] : 20, 1, 200 );
		$clean['server_rate_limit_window']    = self::clamp( isset( $value['server_rate_limit_window'] ) ? $value['server_rate_limit_window'] : 30, 5, 1440 );

		foreach ( array( 'privacy_page_id', 'form_page_id', 'legal_page_id' ) as $key ) {
			$clean[ $key ] = isset( $value[ $key ] ) ? absint( $value[ $key ] ) : 0;
		}

		$timezone = isset( $value['timezone'] ) ? sanitize_text_field( (string) $value['timezone'] ) : 'Europe/Bucharest';
		try {
			new \DateTimeZone( $timezone );
			$clean['timezone'] = $timezone;
		} catch ( \Exception $exception ) {
			$clean['timezone'] = 'Europe/Bucharest';
		}

		$secret = isset( $value['rate_limit_secret'] ) ? trim( (string) $value['rate_limit_secret'] ) : '';
		$clean['rate_limit_secret'] = preg_match( '/^[A-Za-z0-9_-]{32,128}$/D', $secret ) ? $secret : self::generate_secret();
		return $clean;
	}

	public function notification_email() {
		$email = sanitize_email( (string) $this->get( 'notification_email' ) );
		return $email ? $email : sanitize_email( (string) get_option( 'admin_email' ) );
	}

	public function form_url() {
		$page_id = absint( $this->get( 'form_page_id' ) );
		$url     = $page_id ? get_permalink( $page_id ) : '';
		return $url ? $url : home_url( '/retragere-din-contract/' );
	}

	public function legal_url() {
		$page_id = absint( $this->get( 'legal_page_id' ) );
		$url     = $page_id ? get_permalink( $page_id ) : '';
		return $url ? $url : $this->form_url();
	}

	public function is_ready() {
		return (bool) ( $this->get( 'business_name' ) && $this->get( 'business_address' ) && is_email( $this->get( 'contact_email' ) ) );
	}

	public function snapshot() {
		return array(
			'business' => array(
				'name'           => (string) $this->get( 'business_name' ),
				'address'        => (string) $this->get( 'business_address' ),
				'return_address' => (string) $this->get( 'return_address' ),
				'contact_email'  => (string) $this->get( 'contact_email' ),
				'contact_phone'  => (string) $this->get( 'contact_phone' ),
			),
			'withdrawal_period_days' => (int) $this->get( 'period_days' ),
			'return_cost' => array(
				'payer'                  => (string) $this->get( 'return_cost_payer' ),
				'additional_information' => (string) $this->get( 'additional_return_cost_info' ),
			),
			'timezone' => (string) $this->get( 'timezone' ),
			'site'     => array(
				'blog_id' => get_current_blog_id(),
				'name'    => (string) get_bloginfo( 'name' ),
				'url'     => (string) home_url( '/' ),
			),
		);
	}

	private static function clamp( $value, $minimum, $maximum ) {
		return min( $maximum, max( $minimum, (int) $value ) );
	}

	private static function generate_secret() {
		try {
			return rtrim( strtr( base64_encode( random_bytes( 48 ) ), '+/', '-_' ), '=' );
		} catch ( \Exception $exception ) {
			return wp_generate_password( 64, false, false );
		}
	}
}
