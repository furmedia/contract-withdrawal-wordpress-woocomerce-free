<?php
namespace Foxly\CWFW;

defined( 'ABSPATH' ) || exit;

class Repository {
	const REFERENCE_PLACEHOLDER = '[[WITHDRAWAL_REFERENCE]]';

	private $db;
	private $main;
	private $limits;
	private $columns = 'withdrawal_id,reference,order_id,customer_id,language_code,contract_reference,firstname,lastname,email,scope,items,note,declaration,module_version,legal_template_version,settings_snapshot,confirmation_subject,confirmation_html,confirmation_text,status,email_status,admin_email_status,idempotency_hash,date_submitted_utc,date_confirmed_utc,date_modified_utc';

	public function __construct( $database = null ) {
		global $wpdb;
		$this->db     = $database ? $database : $wpdb;
		$this->main   = $this->db->prefix . 'cwfw_withdrawals';
		$this->limits = $this->db->prefix . 'cwfw_rate_limits';
	}

	public function create( array $data ) {
		$idempotency_hash = (string) $data['idempotency_hash'];
		$temporary        = 'TMP-' . substr( $idempotency_hash, 0, 28 );
		$now              = (string) $data['date_submitted_utc'];
		$row              = array(
			'reference'              => $temporary,
			'order_id'               => absint( $data['order_id'] ),
			'customer_id'            => absint( $data['customer_id'] ),
			'language_code'          => (string) $data['language_code'],
			'contract_reference'     => (string) $data['contract_reference'],
			'firstname'              => (string) $data['firstname'],
			'lastname'               => (string) $data['lastname'],
			'email'                  => (string) $data['email'],
			'scope'                  => (string) $data['scope'],
			'items'                  => (string) $data['items'],
			'note'                   => (string) $data['note'],
			'declaration'            => (string) $data['declaration'],
			'module_version'         => (string) $data['module_version'],
			'legal_template_version' => (string) $data['legal_template_version'],
			'settings_snapshot'      => (string) $data['settings_snapshot'],
			'confirmation_subject'   => (string) $data['confirmation_subject'],
			'confirmation_html'      => (string) $data['confirmation_html'],
			'confirmation_text'      => (string) $data['confirmation_text'],
			'status'                 => 'received',
			'email_status'           => 'pending',
			'admin_email_status'     => 'pending',
			'idempotency_hash'       => $idempotency_hash,
			'date_submitted_utc'     => $now,
			'date_confirmed_utc'     => null,
			'date_modified_utc'      => $now,
		);

		$this->db->query( 'START TRANSACTION' );
		try {
			if ( false === $this->db->insert( $this->main, $row ) ) {
				throw new \RuntimeException( 'The withdrawal could not be stored.' );
			}
			$id        = (int) $this->db->insert_id;
			$timestamp = strtotime( $now . ' UTC' );
			$reference = 'WDR-' . gmdate( 'Ymd', $timestamp ? $timestamp : time() ) . '-' . str_pad( (string) $id, 6, '0', STR_PAD_LEFT );
			$replace   = array(
				'reference'            => $reference,
				'declaration'          => str_replace( self::REFERENCE_PLACEHOLDER, $reference, $row['declaration'] ),
				'confirmation_subject' => str_replace( self::REFERENCE_PLACEHOLDER, $reference, $row['confirmation_subject'] ),
				'confirmation_html'    => str_replace( self::REFERENCE_PLACEHOLDER, $reference, $row['confirmation_html'] ),
				'confirmation_text'    => str_replace( self::REFERENCE_PLACEHOLDER, $reference, $row['confirmation_text'] ),
			);
			if ( false === $this->db->update( $this->main, $replace, array( 'withdrawal_id' => $id ) ) ) {
				throw new \RuntimeException( 'The evidence reference could not be finalized.' );
			}
			$this->db->query( 'COMMIT' );
			return $this->get( $id );
		} catch ( \Throwable $exception ) {
			$this->db->query( 'ROLLBACK' );
			throw $exception;
		}
	}

	public function get( $withdrawal_id ) {
		return $this->db->get_row(
			$this->db->prepare( "SELECT {$this->columns} FROM {$this->main} WHERE withdrawal_id = %d LIMIT 1", absint( $withdrawal_id ) ),
			ARRAY_A
		);
	}

	public function get_by_idempotency( $hash ) {
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', (string) $hash ) ) {
			return null;
		}
		return $this->db->get_row(
			$this->db->prepare( "SELECT {$this->columns} FROM {$this->main} WHERE idempotency_hash = %s LIMIT 1", $hash ),
			ARRAY_A
		);
	}

	public function admin_rows( $page = 1, $per_page = 20 ) {
		$per_page = min( 100, max( 1, (int) $per_page ) );
		$offset   = max( 0, ( (int) $page - 1 ) * $per_page );
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT withdrawal_id,reference,contract_reference,firstname,lastname,email,scope,email_status,admin_email_status,date_submitted_utc FROM {$this->main} ORDER BY date_submitted_utc DESC,withdrawal_id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	public function admin_total() {
		return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->main}" );
	}

	public function mark_customer_email_status( $withdrawal_id, $status ) {
		if ( ! in_array( $status, array( 'sent', 'failed' ), true ) ) {
			return false;
		}
		$data = array(
			'email_status'      => $status,
			'date_modified_utc' => gmdate( 'Y-m-d H:i:s' ),
		);
		if ( 'sent' === $status ) {
			$data['date_confirmed_utc'] = gmdate( 'Y-m-d H:i:s' );
		}
		return false !== $this->db->update( $this->main, $data, array( 'withdrawal_id' => absint( $withdrawal_id ) ) );
	}

	public function set_admin_email_status( $withdrawal_id, $status ) {
		if ( ! in_array( $status, array( 'sent', 'failed', 'not_configured' ), true ) ) {
			return false;
		}
		return false !== $this->db->update(
			$this->main,
			array(
				'admin_email_status' => $status,
				'date_modified_utc'  => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'withdrawal_id' => absint( $withdrawal_id ) )
		);
	}

	public function reserve_submission_slot( $bucket_hash, $window_started_utc, $limit ) {
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', (string) $bucket_hash ) ) {
			return false;
		}
		$limit = min( 200, max( 1, (int) $limit ) );
		$this->db->query( "DELETE FROM {$this->limits} WHERE window_started_utc < DATE_SUB(UTC_TIMESTAMP(),INTERVAL 2 DAY)" );
		$sql = "INSERT INTO {$this->limits} (bucket_hash,window_started_utc,submission_count) VALUES (%s,%s,LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE submission_count=IF(submission_count<%d,LAST_INSERT_ID(submission_count+1),submission_count+(LAST_INSERT_ID(0)*0))";
		if ( false === $this->db->query( $this->db->prepare( $sql, $bucket_hash, $window_started_utc, $limit ) ) ) {
			return false;
		}
		return (int) $this->db->get_var( 'SELECT LAST_INSERT_ID()' ) > 0;
	}
}
