<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Helpers\Logger;

class WebhookHandler {

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $hash       = $request->get_param( 'hash' );
        $saved_hash = get_option( 'rf_webhook_hash', '' );

        // Verify webhook hash
        if ( empty( $saved_hash ) || $saved_hash !== $hash ) {
            Logger::warning( 'Webhook: invalid token hash', [ 'hash' => $hash ] );
            return new \WP_REST_Response( [ 'ok' => false ], 403 );
        }

        $update = $request->get_json_params();
        if ( empty( $update ) ) {
            return new \WP_REST_Response( [ 'ok' => true ] );
        }

        Logger::debug( 'Webhook received', $update );

        // Process the update AFTER the HTTP response is sent to Telegram/Bale.
        // WordPress registers shutdown_action_hook via register_shutdown_function,
        // so our callback runs even after wp_die() flushes the REST response.
        // Under PHP-FPM, fastcgi_finish_request() releases the connection immediately
        // so the platform receives 200 OK before any heavy processing begins.
        add_action( 'shutdown', static function () use ( $update ) {
            if ( function_exists( 'fastcgi_finish_request' ) ) {
                fastcgi_finish_request();
            }

            try {
                $router = new Router();
                $router->dispatch( $update );
            } catch ( \Throwable $e ) {
                Logger::error( 'Webhook processing error', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ] );
            }
        }, 5 );

        return new \WP_REST_Response( [ 'ok' => true ] );
    }
}
