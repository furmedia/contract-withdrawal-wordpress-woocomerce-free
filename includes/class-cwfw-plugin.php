<?php
namespace Foxly\CWFW;

defined( 'ABSPATH' ) || exit;

class Plugin {
	private static $instance;
	private $booted = false;
	private $settings;
	private $repository;
	private $security;
	private $mailer;
	private $frontend;
	private $admin;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		$this->settings   = new Settings();
		$this->repository = new Repository();
		$this->security   = new Security( $this->settings, $this->repository );
		$this->mailer     = new Mailer( $this->settings, $this->repository );
		$this->frontend   = new Frontend( $this->settings, $this->repository, $this->security, $this->mailer );
		$this->admin      = new Admin( $this->settings, $this->repository, $this->frontend );

		add_action( 'init', array( 'Foxly\\CWFW\\Installer', 'maybe_upgrade' ), 1 );
		add_action( 'widgets_init', array( 'Foxly\\CWFW\\Widgets', 'register' ) );
		$this->frontend->register_hooks();
		if ( is_admin() ) {
			$this->admin->register_hooks();
		}
	}

	public function settings() {
		return $this->settings;
	}

	public function repository() {
		return $this->repository;
	}

	public function frontend() {
		return $this->frontend;
	}

	public function woocommerce_missing_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Contract Withdrawal Free for WooCommerce requires WooCommerce to be installed and active.', 'contract-withdrawal-free-for-woocommerce' ) . '</p></div>';
		}
	}
}
