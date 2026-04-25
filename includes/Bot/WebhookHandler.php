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

        try {
            $router = new Router();
            $router->dispatch( $update );
        } catch ( \Throwable $e ) {
            Logger::error( 'Webhook processing error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ] );
        }

        return new \WP_REST_Response( [ 'ok' => true ] );
    }
}
