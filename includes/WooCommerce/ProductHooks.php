<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class ProductHooks {

    public function __construct() {
        add_action( 'woocommerce_update_product', [ $this, 'onProductUpdate' ], 10, 1 );
        add_action( 'woocommerce_new_product', [ $this, 'onNewProduct' ], 10, 1 );
        add_action( 'woocommerce_product_set_stock_status', [ $this, 'onStockStatusChange' ], 10, 3 );
        add_action( 'woocommerce_low_stock', [ $this, 'onLowStock' ] );
        add_action( 'save_post', [ $this, 'onSavePost' ], 20, 3 );
    }

    public function onProductUpdate( int $productId ): void {
        do_action( 'rf_product_updated', $productId );
    }

    public function onNewProduct( int $productId ): void {
        do_action( 'rf_new_product', $productId );
    }

    public function onStockStatusChange( int $productId, string $stockStatus, \WC_Product $product ): void {
        if ( $stockStatus === 'instock' ) {
            do_action( 'rf_product_back_in_stock', $productId, $product );
        }
    }

    public function onLowStock( \WC_Product $product ): void {
        do_action( 'rf_product_low_stock', $product );
    }

    public function onSavePost( int $postId, \WP_Post $post, bool $update ): void {
        if ( wp_is_post_revision( $postId ) || wp_is_post_autosave( $postId ) ) return;
        if ( $post->post_type === 'product' ) return; // handled by woocommerce_update_product

        $enabled_types = get_option( 'rf_notify_post_types', [] );
        if ( empty( $enabled_types ) || ! in_array( $post->post_type, $enabled_types, true ) ) return;
        if ( $post->post_status !== 'publish' ) return;

        do_action( 'rf_post_updated', $postId, $post, $update );
    }
}
