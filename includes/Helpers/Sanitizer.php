<?php
namespace RobotForooshande\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class Sanitizer {

    public static function phone( string $phone ): string {
        $persian = [ '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ];
        $english = [ '0','1','2','3','4','5','6','7','8','9' ];
        $phone   = str_replace( $persian, $english, $phone );
        $phone   = preg_replace( '/[^0-9+]/', '', $phone );

        if ( str_starts_with( $phone, '09' ) ) return '+98' . substr( $phone, 1 );
        if ( str_starts_with( $phone, '9' ) && strlen( $phone ) === 10 ) return '+98' . $phone;
        if ( str_starts_with( $phone, '0098' ) ) return '+98' . substr( $phone, 4 );
        if ( str_starts_with( $phone, '+98' ) ) return $phone;
        if ( str_starts_with( $phone, '98' ) && strlen( $phone ) === 12 ) return '+' . $phone;

        return $phone;
    }

    public static function text( string $text ): string {
        return sanitize_text_field( $text );
    }

    public static function textarea( string $text ): string {
        return sanitize_textarea_field( $text );
    }

    public static function int( $value ): int {
        return absint( $value );
    }

    public static function postalCode( string $code ): string {
        $code = preg_replace( '/[^0-9]/', '', $code );
        return strlen( $code ) === 10 ? $code : '';
    }

    public static function chatId( $value ): int {
        return (int) $value;
    }

    public static function isValidIranianPhone( string $phone ): bool {
        $normalized = self::phone( $phone );
        return (bool) preg_match( '/^\+989[0-9]{9}$/', $normalized );
    }

    public static function botToken( string $token ): string {
        return preg_replace( '/[^0-9:a-zA-Z\-_]/', '', $token );
    }
}
