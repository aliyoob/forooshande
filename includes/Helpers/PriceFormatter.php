<?php
namespace RobotForooshande\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class PriceFormatter {


    private static function needsRialToTomanConversion(): bool {
        $unit      = get_option( 'rf_currency_label', 'toman' );
        $wc_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'IRR';

        return $unit === 'toman' && $wc_currency === 'IRR';
    }

    public static function format( $price ): string {
        $unit = get_option( 'rf_currency_label', 'toman' );

        if ( self::needsRialToTomanConversion() ) {
            $price = (float) $price / 10;
        }

        $formatted  = number_format( (float) $price, 0, '', ',' );
        $unit_label = $unit === 'toman' ? 'تومان' : 'ریال';

        return PersianDate::toPersianDigits( $formatted ) . ' ' . $unit_label;
    }

    public static function rawFormat( $price ): string {
        if ( self::needsRialToTomanConversion() ) {
            $price = (float) $price / 10;
        }
        return number_format( (float) $price, 0, '', ',' );
    }
}
