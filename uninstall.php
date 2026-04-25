<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$delete_data = get_option( 'rf_delete_data_on_uninstall', false );

if ( $delete_data ) {
    global $wpdb;

    // Delete custom tables
    $tables = [
        $wpdb->prefix . 'rf_bot_users',
        $wpdb->prefix . 'rf_bot_carts',
        $wpdb->prefix . 'rf_logs',
        $wpdb->prefix . 'rf_otps',
        $wpdb->prefix . 'rf_wishlists',
        $wpdb->prefix . 'rf_stock_alerts',
    ];

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
    }

    // Delete all plugin options
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rf\_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL

    // Delete user meta
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'rf\_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL

    // Delete transients
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rf_%' OR option_name LIKE '_transient_timeout_rf_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL

    // Delete scheduled events
    wp_clear_scheduled_hook( 'rf_abandoned_cart_check' );
    wp_clear_scheduled_hook( 'rf_broadcast_queue' );
}
