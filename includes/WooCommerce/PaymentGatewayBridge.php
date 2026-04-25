<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class PaymentGatewayBridge {

    public static function getActiveGateways(): array {
        if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) return [];

        $wc_gateways    = WC()->payment_gateways->get_available_payment_gateways();
        $enabled_in_bot = get_option( 'rf_bot_gateways', [] );

        if ( empty( $enabled_in_bot ) ) return $wc_gateways;

        return array_intersect_key( $wc_gateways, array_flip( $enabled_in_bot ) );
    }

    public static function getAllGateways(): array {
        if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) return [];
        return WC()->payment_gateways->get_available_payment_gateways();
    }

    public static function createPaymentLink( int $orderId, string $gatewayId ): string {
        $order = wc_get_order( $orderId );
        if ( ! $order ) return '';

        $order->set_payment_method( $gatewayId );
        $order->save();

        $url = $order->get_checkout_payment_url();

        $callback = get_option( 'rf_payment_callback_url', '' );
        if ( ! empty( $callback ) ) {
            $url = add_query_arg( 'rf_return', rawurlencode( $callback ), $url );
        }

        return $url;
    }

    public static function isCODEnabled(): bool {
        $gateways = self::getActiveGateways();
        return isset( $gateways['cod'] ) && get_option( 'rf_cod_enabled', true );
    }
}
