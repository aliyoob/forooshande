<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class CartSync {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'rf_bot_carts';
    }

    public function getItems( int $botUserId ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE bot_user_id = %d ORDER BY added_at ASC",
            $botUserId
        ) );
    }

    public function addItem( int $botUserId, int $productId, int $variationId = 0, int $quantity = 1 ): bool {
        global $wpdb;

        // Check if item already in cart
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE bot_user_id = %d AND product_id = %d AND variation_id = %d",
            $botUserId, $productId, $variationId
        ) );

        if ( $existing ) {
            return $this->updateQuantity( (int) $existing->id, (int) $existing->quantity + $quantity );
        }

        // Check max cart items
        $max = (int) get_option( 'rf_max_cart_items', 20 );
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE bot_user_id = %d", $botUserId
        ) );

        if ( $count >= $max ) return false;

        $wpdb->insert( $this->table, [
            'bot_user_id'  => $botUserId,
            'product_id'   => $productId,
            'variation_id' => $variationId,
            'quantity'     => $quantity,
            'added_at'     => current_time( 'mysql' ),
        ], [ '%d', '%d', '%d', '%d', '%s' ] );

        return $wpdb->insert_id > 0;
    }

    public function updateQuantity( int $cartItemId, int $quantity ): bool {
        global $wpdb;
        if ( $quantity <= 0 ) {
            return $this->removeItem( $cartItemId );
        }

        $item = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d", $cartItemId
        ) );

        if ( ! $item ) return false;

        // Check stock
        $product = wc_get_product( $item->variation_id ?: $item->product_id );
        if ( ! $product ) return false;

        if ( $product->managing_stock() && $quantity > $product->get_stock_quantity() ) {
            return false;
        }

        $wpdb->update( $this->table, [ 'quantity' => $quantity ], [ 'id' => $cartItemId ], [ '%d' ], [ '%d' ] );
        return true;
    }

    public function removeItem( int $cartItemId ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, [ 'id' => $cartItemId ], [ '%d' ] );
    }

    public function clearCart( int $botUserId ): void {
        global $wpdb;
        $wpdb->delete( $this->table, [ 'bot_user_id' => $botUserId ], [ '%d' ] );
    }

    public function getCartTotal( int $botUserId ): float {
        $items = $this->getItems( $botUserId );
        $total = 0;

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->variation_id ?: $item->product_id );
            if ( $product ) {
                $total += (float) $product->get_price() * (int) $item->quantity;
            }
        }

        return $total;
    }

    public function getItemCount( int $botUserId ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(quantity), 0) FROM {$this->table} WHERE bot_user_id = %d",
            $botUserId
        ) );
    }

    public function getItemQuantity( int $cartItemId ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT quantity FROM {$this->table} WHERE id = %d",
            $cartItemId
        ) );
    }

    public function setItemQuantity( int $cartItemId, int $quantity ): bool {
        return $this->updateQuantity( $cartItemId, $quantity );
    }

    public function setCoupon( int $botUserId, string $couponCode ): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rf_bot_users',
            [ 'coupon_code' => sanitize_text_field( $couponCode ) ],
            [ 'id' => $botUserId ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    public function getCoupon( int $botUserId ): ?string {
        global $wpdb;
        $coupon = $wpdb->get_var( $wpdb->prepare(
            "SELECT coupon_code FROM {$wpdb->prefix}rf_bot_users WHERE id = %d",
            $botUserId
        ) );
        return $coupon ?: null;
    }

    public function validateCart( int $botUserId ): array {
        return $this->validateStock( $botUserId );
    }

    /**
     * Validate all cart items stock before checkout
     */
    public function validateStock( int $botUserId ): array {
        $items  = $this->getItems( $botUserId );
        $errors = [];

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->variation_id ?: $item->product_id );
            if ( ! $product ) {
                $errors[] = "محصول حذف شده در سبد خرید شما وجود دارد.";
                $this->removeItem( (int) $item->id );
                continue;
            }

            if ( ! $product->is_in_stock() ) {
                $errors[] = sprintf( 'محصول «%s» ناموجود شده است.', $product->get_name() );
                $this->removeItem( (int) $item->id );
                continue;
            }

            if ( $product->managing_stock() && (int) $item->quantity > $product->get_stock_quantity() ) {
                $available = $product->get_stock_quantity();
                $errors[]  = sprintf(
                    'موجودی «%s» به %d عدد کاهش یافته است.',
                    $product->get_name(), $available
                );
                if ( $available > 0 ) {
                    $this->updateQuantity( (int) $item->id, $available );
                } else {
                    $this->removeItem( (int) $item->id );
                }
            }
        }

        return $errors;
    }

    /**
     * Create WooCommerce order from bot cart
     */
    public function createWcOrder( int $botUserId, array $address, int $wpUserId ): ?\WC_Order {
        $items = $this->getItems( $botUserId );
        if ( empty( $items ) ) return null;

        $order = wc_create_order( [ 'customer_id' => $wpUserId ] );

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->variation_id ?: $item->product_id );
            if ( ! $product ) continue;
            $order->add_product( $product, (int) $item->quantity );
        }

        // Apply coupon if set
        $couponCode = $this->getCoupon( $botUserId );
        if ( $couponCode ) {
            $coupon = new \WC_Coupon( $couponCode );
            if ( $coupon->get_id() && $coupon->is_valid() ) {
                $order->apply_coupon( $couponCode );
            }
            // Clear coupon after use
            $this->setCoupon( $botUserId, '' );
        }

        // Set addresses
        $order->set_billing_first_name( $address['first_name'] ?? '' );
        $order->set_billing_last_name( $address['last_name'] ?? '' );
        $order->set_billing_phone( $address['phone'] ?? '' );
        $order->set_billing_address_1( $address['address'] ?? '' );
        $order->set_billing_city( $address['city'] ?? '' );
        $order->set_billing_state( $address['province'] ?? '' );
        $order->set_billing_postcode( $address['postcode'] ?? '' );
        $order->set_billing_country( 'IR' );

        $order->set_shipping_first_name( $address['first_name'] ?? '' );
        $order->set_shipping_last_name( $address['last_name'] ?? '' );
        $order->set_shipping_address_1( $address['address'] ?? '' );
        $order->set_shipping_city( $address['city'] ?? '' );
        $order->set_shipping_state( $address['province'] ?? '' );
        $order->set_shipping_postcode( $address['postcode'] ?? '' );
        $order->set_shipping_country( 'IR' );

        // Calculate shipping
        $order->calculate_shipping();
        $order->calculate_totals();

        // Mark as bot order
        $order->update_meta_data( '_rf_from_bot', true );
        $order->update_meta_data( '_rf_bot_user_id', $botUserId );
        $order->add_order_note( __( 'سفارش از طریق ربات فروشنده ثبت شد.', 'robot-forooshande' ) );

        $order->save();

        return $order;
    }
}
