<?php
/**
 * Payment callback handler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$orderId = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
$status  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';

if ( ! $orderId ) {
    wp_die( 'سفارش نامعتبر.' );
}

$order = wc_get_order( $orderId );
if ( ! $order ) {
    wp_die( 'سفارش یافت نشد.' );
}

// Notify bot user
$botCore = new \RobotForooshande\Bot\BotCore();
$phone   = $order->get_billing_phone();

if ( $phone ) {
    global $wpdb;
    $normalized = \RobotForooshande\User\PhoneNormalizer::normalize( $phone );
    $botUser    = $wpdb->get_row( $wpdb->prepare(
        "SELECT chat_id FROM {$wpdb->prefix}rf_bot_users WHERE phone = %s",
        $normalized
    ) );

    if ( $botUser && $botUser->chat_id ) {
        $ctx = [
            'bot'     => $botCore,
            'parts'   => [ '', $orderId, $status === 'success' ? 'success' : 'failed' ],
            'chat_id' => $botUser->chat_id,
        ];
        $handler = new \RobotForooshande\Bot\Handlers\PaymentHandler();
        $handler->handlePaymentCallback( $ctx );
    }
}

// Redirect to order page or thank you page
if ( $status === 'success' ) {
    $redirect = $order->get_checkout_order_received_url();
} else {
    $redirect = $order->get_checkout_payment_url();
}

wp_safe_redirect( $redirect );
exit;
