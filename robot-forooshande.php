<?php
/**
 * Plugin Name: ربات فروشنده
 * Plugin URI: https://example.com/robot-forooshande
 * Description: ربات فروشگاهی تلگرام و بله برای ووکامرس - خرید و مدیریت کامل فروشگاه از طریق ربات
 * Version: 1.0.0
 * Author: Robot Forooshande
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: robot-forooshande
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

define( 'RF_VERSION', '1.0.0' );
define( 'RF_PLUGIN_FILE', __FILE__ );
define( 'RF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once RF_PLUGIN_DIR . 'includes/Loader.php';
