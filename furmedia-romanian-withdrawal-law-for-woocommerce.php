<?php
/**
 * Plugin Name: Furmedia Romanian Withdrawal Law for WooCommerce
 * Plugin URI: https://github.com/furmedia/contract-withdrawal-wordpress-woocomerce-free
 * Description: A free online contract-withdrawal form for Romanian WooCommerce stores, with evidence records, email acknowledgements, widgets, blocks and shortcodes.
 * Version: 1.1.2
 * Author: Furmedia
 * Author URI: https://furmedia.ro/
 * Text Domain: furmedia-romanian-withdrawal-law-for-woocommerce
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

define( 'FURMROWI_VERSION', '1.1.2' );
define( 'FURMROWI_SCHEMA_VERSION', '1.0.0' );
define( 'FURMROWI_FILE', __FILE__ );
define( 'FURMROWI_PATH', plugin_dir_path( __FILE__ ) );
define( 'FURMROWI_URL', plugin_dir_url( __FILE__ ) );
define( 'FURMROWI_BASENAME', plugin_basename( __FILE__ ) );

require_once FURMROWI_PATH . 'includes/class-furmrowi-settings.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-installer.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-repository.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-security.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-mailer.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-frontend.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-admin.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-widgets.php';
require_once FURMROWI_PATH . 'includes/class-furmrowi-plugin.php';

register_activation_hook( __FILE__, array( 'Furmedia\\Furmrowi\\Installer', 'activate' ) );

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
		\Furmedia\Furmrowi\Plugin::instance()->boot();
	},
	20
);

/**
 * Public accessor used by integrations and templates.
 *
 * @return \Furmedia\Furmrowi\Plugin
 */
function furmrowi() {
	return \Furmedia\Furmrowi\Plugin::instance();
}
