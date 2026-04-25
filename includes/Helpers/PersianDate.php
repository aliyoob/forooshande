<?php
namespace RobotForooshande\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class PersianDate {

    private static array $jalali_months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    public static function toJalali( int $gy, int $gm, int $gd ): array {
        $g_d_m = [ 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 ];
        $gy2   = ( $gm > 2 ) ? $gy + 1 : $gy;
        $days  = 355666 + ( 365 * $gy ) + (int)( ( $gy2 + 3 ) / 4 ) - (int)( ( $gy2 + 99 ) / 100 )
                 + (int)( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
        $jy    = -1595 + ( 33 * (int)( $days / 12053 ) );
        $days %= 12053;
        $jy   += 4 * (int)( $days / 1461 );
        $days %= 1461;

        if ( $days > 365 ) {
            $jy   += (int)( ( $days - 1 ) / 365 );
            $days  = ( $days - 1 ) % 365;
        }

        if ( $days < 186 ) {
            $jm = 1 + (int)( $days / 31 );
            $jd = 1 + ( $days % 31 );
        } else {
            $jm = 7 + (int)( ( $days - 186 ) / 30 );
            $jd = 1 + ( ( $days - 186 ) % 30 );
        }

        return [ $jy, $jm, $jd ];
    }

    public static function format( string $datetime, string $format = 'Y/m/d H:i' ): string {
        $timestamp = strtotime( $datetime );
        if ( ! $timestamp ) return $datetime;

        $gy = (int) gmdate( 'Y', $timestamp );
        $gm = (int) gmdate( 'm', $timestamp );
        $gd = (int) gmdate( 'd', $timestamp );

        [ $jy, $jm, $jd ] = self::toJalali( $gy, $gm, $gd );

        $result = $format;
        $result = str_replace( 'Y', (string) $jy, $result );
        $result = str_replace( 'm', str_pad( (string) $jm, 2, '0', STR_PAD_LEFT ), $result );
        $result = str_replace( 'd', str_pad( (string) $jd, 2, '0', STR_PAD_LEFT ), $result );
        $result = str_replace( 'F', self::$jalali_months[ $jm ] ?? '', $result );
        $result = str_replace( 'H', gmdate( 'H', $timestamp ), $result );
        $result = str_replace( 'i', gmdate( 'i', $timestamp ), $result );

        return self::toPersianDigits( $result );
    }

    public static function now( string $format = 'Y/m/d H:i' ): string {
        return self::format( current_time( 'mysql' ), $format );
    }

    public static function convert( string $datetime, string $format = 'Y/m/d' ): string {
        return self::format( $datetime, $format );
    }

    public static function toPersianDigits( string $str ): string {
        $persian = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
        $english = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
        return str_replace( $english, $persian, $str );
    }
}
