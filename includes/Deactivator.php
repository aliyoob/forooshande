<?php
namespace RobotForooshande;

if ( ! defined( 'ABSPATH' ) ) exit;

class Deactivator {

    public static function deactivate(): void {
        // Remove cron events
        wp_clear_scheduled_hook( 'rf_abandoned_cart_check' );
        wp_clear_scheduled_hook( 'rf_broadcast_queue' );

        // Remove webhook
        $token = get_option( 'rf_bot_token', '' );
        if ( ! empty( $token ) ) {
            $bot = new Bot\BotCore();
            $bot->deleteWebhook();
        }

        flush_rewrite_rules();
    }
}
