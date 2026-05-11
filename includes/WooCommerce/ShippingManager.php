<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom shipping manager — reads methods defined in the plugin settings.
 * No longer relies on WooCommerce shipping zones so results are consistent
 * regardless of zone configuration.
 *
 * Each method stored in rf_custom_shipping_methods has the shape:
 *   [ 'id' => string, 'title' => string, 'cost' => float ]
 */
class ShippingManager {

    /**
     * Return all active custom shipping methods.
     * The $address and $cartItems params are kept for API compatibility but are
     * not used (custom methods are global — no zone filtering).
     *
     * @param array $address    (unused, kept for interface compatibility)
     * @param array $cartItems  (unused, kept for interface compatibility)
     * @return array<array{id:string,title:string,cost:float}>
     */
    public static function getAvailableMethods( array $address = [], array $cartItems = [] ): array {
        $raw = get_option( 'rf_custom_shipping_methods', [] );
        if ( ! is_array( $raw ) ) {
            return [];
        }

        $methods = [];
        foreach ( $raw as $i => $entry ) {
            if ( empty( $entry['title'] ) ) {
                continue;
            }
            $methods[] = [
                'id'    => 'custom_' . $i,
                'title' => sanitize_text_field( $entry['title'] ),
                'cost'  => (float) ( $entry['cost'] ?? 0 ),
            ];
        }

        return $methods;
    }

    /**
     * Persist custom shipping methods array to options.
     * Called from the admin AJAX handler.
     *
     * @param list<array{title:string,cost:float|int}> $methods
     */
    public static function saveMethods( array $methods ): void {
        $clean = [];
        foreach ( $methods as $m ) {
            $title = sanitize_text_field( $m['title'] ?? '' );
            if ( $title === '' ) {
                continue;
            }
            $clean[] = [
                'title' => $title,
                'cost'  => (float) ( $m['cost'] ?? 0 ),
            ];
        }
        update_option( 'rf_custom_shipping_methods', $clean, false );
    }
}
