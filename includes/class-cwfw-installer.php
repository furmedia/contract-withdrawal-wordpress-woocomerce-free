<?php
namespace Furmedia\CWFW;

defined( 'ABSPATH' ) || exit;

class Installer {
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				switch_to_blog( $site_id );
				self::install_site();
				restore_current_blog();
			}
			return;
		}
		self::install_site();
	}

	public static function install_site() {
		self::create_tables();
		if ( ! is_array( get_option( Settings::OPTION, null ) ) ) {
			update_option( Settings::OPTION, Settings::defaults(), false );
		}

		$settings   = new Settings();
		$current    = $settings->all();
		$form_page  = self::ensure_page( 'retragere-din-contract', __( 'Withdrawal from contract', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), '[cwfw_form]' );
		$legal_page = self::ensure_page( 'dreptul-de-retragere', __( 'Right of withdrawal', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), '[cwfw_legal_notice full="yes"]' );
		$settings->update(
			array(
				'form_page_id'  => $current['form_page_id'] ? $current['form_page_id'] : $form_page,
				'legal_page_id' => $current['legal_page_id'] ? $current['legal_page_id'] : $legal_page,
			)
		);
		update_option( 'cwfw_schema_version', CWFW_SCHEMA_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( CWFW_SCHEMA_VERSION !== get_option( 'cwfw_schema_version' ) ) {
			self::create_tables();
			update_option( 'cwfw_schema_version', CWFW_SCHEMA_VERSION, false );
		}
	}

	private static function ensure_page( $slug, $title, $content ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page instanceof \WP_Post && false !== strpos( (string) $page->post_content, $content ) ) {
			return (int) $page->ID;
		}
		if ( $page instanceof \WP_Post ) {
			$slug .= '-free';
			$page  = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $page instanceof \WP_Post ) {
				return (int) $page->ID;
			}
		}
		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_name'      => $slug,
				'post_title'     => $title,
				'post_content'   => $content,
				'comment_status' => 'closed',
			),
			true
		);
		return is_wp_error( $page_id ) ? 0 : (int) $page_id;
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$main    = $wpdb->prefix . 'cwfw_withdrawals';
		$limits  = $wpdb->prefix . 'cwfw_rate_limits';

		$sql_main = "CREATE TABLE {$main} (
			withdrawal_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			reference varchar(32) NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			language_code varchar(16) NOT NULL DEFAULT '',
			contract_reference varchar(128) NOT NULL DEFAULT '',
			firstname varchar(64) NOT NULL DEFAULT '',
			lastname varchar(64) NOT NULL DEFAULT '',
			email varchar(254) NOT NULL DEFAULT '',
			scope varchar(32) NOT NULL DEFAULT 'full',
			items longtext NOT NULL,
			note text NOT NULL,
			declaration longtext NOT NULL,
			module_version varchar(16) NOT NULL DEFAULT '',
			legal_template_version varchar(64) NOT NULL DEFAULT '',
			settings_snapshot longtext NULL,
			confirmation_subject varchar(255) NOT NULL DEFAULT '',
			confirmation_html longtext NOT NULL,
			confirmation_text longtext NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'received',
			email_status varchar(16) NOT NULL DEFAULT 'pending',
			admin_email_status varchar(16) NOT NULL DEFAULT 'pending',
			idempotency_hash char(64) NULL,
			date_submitted_utc datetime NOT NULL,
			date_confirmed_utc datetime NULL,
			date_modified_utc datetime NOT NULL,
			PRIMARY KEY  (withdrawal_id),
			UNIQUE KEY reference (reference),
			UNIQUE KEY idempotency_hash (idempotency_hash),
			KEY order_id (order_id),
			KEY customer_id (customer_id),
			KEY submitted (date_submitted_utc)
		) {$charset};";

		$sql_limits = "CREATE TABLE {$limits} (
			bucket_hash char(64) NOT NULL,
			window_started_utc datetime NOT NULL,
			submission_count int(11) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (bucket_hash),
			KEY window_started_utc (window_started_utc)
		) {$charset};";

		dbDelta( $sql_main );
		dbDelta( $sql_limits );
	}
}
