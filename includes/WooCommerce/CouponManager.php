<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class CouponManager {

    public static function applyCoupon( int $orderId, string $couponCode ): array {
        $order  = wc_get_order( $orderId );
        $coupon = new \WC_Coupon( $couponCode );

        if ( ! $coupon->get_id() ) {
            return [ 'success' => false, 'message' => 'کد تخفیف نامعتبر است.' ];
        }

        $valid = $coupon->is_valid();
        if ( ! $valid ) {
            return [ 'success' => false, 'message' => 'کد تخفیف منقضی شده یا شرایط استفاده را ندارید.' ];
        }

        $result = $order->apply_coupon( $couponCode );
        if ( is_wp_error( $result ) ) {
            return [ 'success' => false, 'message' => $result->get_error_message() ];
        }

        $order->calculate_totals();
        $order->save();

        return [
            'success'  => true,
            'message'  => 'کد تخفیف اعمال شد!',
            'discount' => $order->get_total_discount(),
            'total'    => $order->get_total(),
        ];
    }
}
