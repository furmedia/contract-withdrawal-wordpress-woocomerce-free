<?php
/**
 * Plugin Name: Contract Withdrawal Free for WooCommerce
 * Plugin URI: https://foxly.ro/
 * Description: A free online contract-withdrawal form for Romanian WooCommerce stores, with evidence records, email acknowledgements, widgets, blocks and shortcodes.
 * Version: 1.0.0
 * Author: Foxly Software
 * Author URI: https://foxly.ro/
 * Text Domain: contract-withdrawal-free-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.9
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'CWFW_VERSION', '1.0.0' );
define( 'CWFW_SCHEMA_VERSION', '1.0.0' );
define( 'CWFW_FILE', __FILE__ );
define( 'CWFW_PATH', plugin_dir_path( __FILE__ ) );
define( 'CWFW_URL', plugin_dir_url( __FILE__ ) );
define( 'CWFW_BASENAME', plugin_basename( __FILE__ ) );

require_once CWFW_PATH . 'includes/class-cwfw-settings.php';
require_once CWFW_PATH . 'includes/class-cwfw-installer.php';
require_once CWFW_PATH . 'includes/class-cwfw-repository.php';
require_once CWFW_PATH . 'includes/class-cwfw-security.php';
require_once CWFW_PATH . 'includes/class-cwfw-mailer.php';
require_once CWFW_PATH . 'includes/class-cwfw-frontend.php';
require_once CWFW_PATH . 'includes/class-cwfw-admin.php';
require_once CWFW_PATH . 'includes/class-cwfw-widgets.php';
require_once CWFW_PATH . 'includes/class-cwfw-plugin.php';

register_activation_hook( __FILE__, array( 'Foxly\\CWFW\\Installer', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\Foxly\CWFW\Plugin::instance()->boot();
	},
	20
);

/**
 * Public accessor used by integrations and templates.
 *
 * @return \Foxly\CWFW\Plugin
 */
function cwfw() {
	return \Foxly\CWFW\Plugin::instance();
}
