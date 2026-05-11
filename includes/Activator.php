<?php
namespace RobotForooshande;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activator {

    public static function activate(): void {
        // Create database tables
        Database\Migrator::run();

        // Set default options
        self::set_defaults();

        // Schedule cron events
        if ( ! wp_next_scheduled( 'rf_abandoned_cart_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'rf_abandoned_cart_check' );
        }

        flush_rewrite_rules();
    }

    private static function set_defaults(): void {
        $defaults = [
            'rf_enabled'            => true,
            'rf_platform'           => 'telegram',
            'rf_shop_name'          => get_bloginfo( 'name' ),
            'rf_currency_unit'      => 'toman',
            'rf_phone_meta_key'     => 'billing_phone',
            'rf_products_per_page'  => 6,
            'rf_max_cart_items'     => 20,
            'rf_cache_duration'     => 300,
            'rf_otp_rate_limit'     => 3,
            'rf_otp_expire_minutes' => 5,
            'rf_log_enabled'        => true,
            'rf_log_level'          => 'info',
            'rf_delete_data_on_uninstall' => false,

            // Notification defaults
            'rf_notify_new_order'         => true,
            'rf_notify_status_change'     => true,
            'rf_notify_tracking'          => true,
            'rf_notify_new_user'          => true,
            'rf_notify_product_update'    => false,

            // Abandoned cart
            'rf_abandoned_cart_enabled'   => false,
            'rf_abandoned_cart_delay'     => 1,

            // Message templates – sourced from MessageTemplateEngine to avoid duplication
            ...Bot\MessageTemplateEngine::defaults(),

            // Menu button labels
            'rf_menu_shop'          => '🛍 فروشگاه',
            'rf_menu_search'        => '🔍 جستجوی محصول',
            'rf_menu_categories'    => '📂 دسته‌بندی‌ها',
            'rf_menu_cart'          => '🛒 سبد خرید',
            'rf_menu_orders'        => '📦 سفارشات من',
            'rf_menu_account'       => '👤 حساب من',
            'rf_menu_support'       => '📞 پشتیبانی',
            'rf_menu_about'         => 'ℹ️ درباره ما',
            'rf_menu_wishlist'      => '❤️ علاقه‌مندی‌ها',
            'rf_menu_offers'        => '🔥 حراجی‌ها',
            'rf_menu_featured'      => '⭐ محصولات ویژه',
            'rf_menu_recent'        => '🕐 اخیراً دیده‌شده',
        ];

        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                update_option( $key, $value );
            }
        }
    }
}
