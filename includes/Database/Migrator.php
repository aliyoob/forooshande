<?php
namespace RobotForooshande\Database;

if ( ! defined( 'ABSPATH' ) ) exit;

class Migrator {

    public static function run(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Bot Users
        dbDelta( "CREATE TABLE {$prefix}rf_bot_users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            chat_id BIGINT NOT NULL,
            platform VARCHAR(10) NOT NULL DEFAULT 'telegram',
            phone VARCHAR(20) NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            username VARCHAR(100) NULL,
            address TEXT NULL,
            coupon_code VARCHAR(100) NULL,
            is_admin TINYINT(1) DEFAULT 0,
            state VARCHAR(50) DEFAULT 'idle',
            state_data LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_chat (chat_id, platform),
            KEY idx_phone (phone),
            KEY idx_user (user_id)
        ) {$charset};" );

        // Bot Carts
        dbDelta( "CREATE TABLE {$prefix}rf_bot_carts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED DEFAULT 0,
            quantity INT UNSIGNED DEFAULT 1,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_cart (bot_user_id)
        ) {$charset};" );

        // Logs
        dbDelta( "CREATE TABLE {$prefix}rf_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            level VARCHAR(10) DEFAULT 'info',
            message TEXT NOT NULL,
            context LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_level (level),
            KEY idx_date (created_at)
        ) {$charset};" );

        // OTP
        dbDelta( "CREATE TABLE {$prefix}rf_otps (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            code VARCHAR(10) NOT NULL,
            method VARCHAR(10) NOT NULL DEFAULT 'sms',
            attempts INT UNSIGNED DEFAULT 0,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_phone_code (phone, code)
        ) {$charset};" );

        // Wishlists
        dbDelta( "CREATE TABLE {$prefix}rf_wishlists (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wish (bot_user_id, product_id)
        ) {$charset};" );

        // Stock Alerts
        dbDelta( "CREATE TABLE {$prefix}rf_stock_alerts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            notified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_alert (bot_user_id, product_id)
        ) {$charset};" );

        update_option( 'rf_db_version', RF_VERSION );
    }
}
