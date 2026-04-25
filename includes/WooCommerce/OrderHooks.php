<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class OrderHooks {

    public function __construct() {
        add_action( 'woocommerce_order_status_changed', [ $this, 'onStatusChanged' ], 10, 3 );
        add_action( 'woocommerce_new_order', [ $this, 'onNewOrder' ], 10, 1 );
        add_action( 'woocommerce_payment_complete', [ $this, 'onPaymentComplete' ], 10, 1 );
    }

    public function onStatusChanged( int $orderId, string $oldStatus, string $newStatus ): void {
        do_action( 'rf_order_status_changed', $orderId, $oldStatus, $newStatus );
    }

    public function onNewOrder( int $orderId ): void {
        // Note: at this point, bot orders may not have items/billing set yet
        // Admin notification is handled by rf_order_status_changed to processing
    }

    public function onPaymentComplete( int $orderId ): void {
        do_action( 'rf_payment_complete', $orderId );
    }
}
