<?php
/**
 * Robot Forooshande - Core Loader
 * This file bootstraps the plugin after license validation.
 *
 * @package RobotForooshande
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------------------------------------------------------------------------------- Start RTL License
$rtlLicenseClassName  = 'RTL_License_cbf548c1f1b54787';
$rtlLicenseFilePath   = __DIR__ . DIRECTORY_SEPARATOR . $rtlLicenseClassName . '.php';
$rtlLicenseFileHash   = @sha1_file($rtlLicenseFilePath);
$robot_license_active = false;

if ( $rtlLicenseFileHash === '558bdfaf793d3ad7b6659f22c76d5782c397b468' && file_exists($rtlLicenseFilePath) ) {
	require_once $rtlLicenseFilePath;

	if ( class_exists($rtlLicenseClassName) && method_exists($rtlLicenseClassName, 'isActive') ) {
		$rtlLicenseClass = new $rtlLicenseClassName();

		if ( $rtlLicenseClass->{'isActive'}() === true ) {
			// Product is Active Now, Enable Pro Features
			$robot_license_active = true;
		}
	}
}
// اگر لایسنس فعال نیست، افزونه لود نشود
if ( !$robot_license_active ) {
	return;
}
// ----------------------------------------------------------------------------------------------------- End RTL License

// Composer Autoloader
if ( file_exists( RF_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once RF_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Manual PSR-4 autoloader
    spl_autoload_register( function ( $class ) {
        $prefix    = 'RobotForooshande\\';
        $base_dir  = RF_PLUGIN_DIR . 'includes/';
        $len       = strlen( $prefix );

        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

/**
 * Check WooCommerce dependency
 */
function rf_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'افزونه «ربات فروشنده» نیاز به نصب و فعال‌سازی ووکامرس دارد.', 'robot-forooshande' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Activation hook
 */
register_activation_hook( RF_PLUGIN_FILE, function () {
    require_once RF_PLUGIN_DIR . 'includes/Activator.php';
    \RobotForooshande\Activator::activate();
} );

/**
 * Deactivation hook
 */
register_deactivation_hook( RF_PLUGIN_FILE, function () {
    require_once RF_PLUGIN_DIR . 'includes/Deactivator.php';
    \RobotForooshande\Deactivator::deactivate();
} );

/**
 * Initialize plugin
 */
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'robot-forooshande', false, dirname( RF_PLUGIN_BASENAME ) . '/languages' );

    if ( ! rf_check_woocommerce() ) {
        return;
    }

    \RobotForooshande\Plugin::instance();
}, 20 );
