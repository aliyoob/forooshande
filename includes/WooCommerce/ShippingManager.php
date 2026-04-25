<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class ShippingManager {

    public static function getAvailableMethods( array $address ): array {
        if ( ! function_exists( 'WC' ) ) return [];

        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package( [
            'destination' => [
                'country'  => 'IR',
                'state'    => $address['province'] ?? '',
                'postcode' => $address['postcode'] ?? '',
                'city'     => $address['city'] ?? '',
            ],
        ] );

        $methods = [];
        foreach ( $shipping_zone->get_shipping_methods( true ) as $method ) {
            $methods[] = [
                'id'    => $method->get_rate_id(),
                'title' => $method->get_title(),
                'cost'  => $method->get_option( 'cost', 0 ),
            ];
        }

        return $methods;
    }

    public static function calculateShipping( \WC_Order $order ): float {
        $order->calculate_shipping();
        return (float) $order->get_shipping_total();
    }
}
