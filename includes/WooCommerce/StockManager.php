<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class StockManager {

    public static function checkStock( int $productId, int $variationId = 0 ): array {
        $product = wc_get_product( $variationId ?: $productId );
        if ( ! $product ) {
            return [ 'exists' => false, 'in_stock' => false, 'quantity' => 0 ];
        }

        return [
            'exists'    => true,
            'in_stock'  => $product->is_in_stock(),
            'quantity'  => $product->get_stock_quantity(),
            'managing'  => $product->managing_stock(),
            'status'    => $product->get_stock_status(),
        ];
    }

    public static function getAvailableQuantity( int $productId, int $variationId = 0 ): int {
        $product = wc_get_product( $variationId ?: $productId );
        if ( ! $product || ! $product->is_in_stock() ) return 0;

        if ( $product->managing_stock() ) {
            return max( 0, $product->get_stock_quantity() );
        }

        return PHP_INT_MAX; // unlimited
    }

    public static function formatStockStatus( int $productId, int $variationId = 0 ): string {
        $stock = self::checkStock( $productId, $variationId );

        if ( ! $stock['exists'] ) return '❌ محصول یافت نشد';
        if ( ! $stock['in_stock'] ) return '❌ ناموجود';

        if ( $stock['managing'] && $stock['quantity'] !== null ) {
            $qty = \RobotForooshande\Helpers\PersianDate::toPersianDigits( (string) $stock['quantity'] );
            return "✅ موجود ({$qty} عدد)";
        }

        return '✅ موجود';
    }
}
