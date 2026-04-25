<?php
namespace RobotForooshande\User;

if ( ! defined( 'ABSPATH' ) ) exit;

class PhoneNormalizer {

    public static function normalize( string $phone ): string {
        // Convert Persian/Arabic digits to English
        $persian = [ '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ];
        $arabic  = [ '٠','١','٢','٣','٤','٥','٦','٧','٨','٩' ];
        $english = [ '0','1','2','3','4','5','6','7','8','9' ];

        $phone = str_replace( $persian, $english, $phone );
        $phone = str_replace( $arabic, $english, $phone );
        $phone = preg_replace( '/[^0-9+]/', '', $phone );

        if ( str_starts_with( $phone, '09' ) ) {
            return '+98' . substr( $phone, 1 );
        }
        if ( str_starts_with( $phone, '9' ) && strlen( $phone ) === 10 ) {
            return '+98' . $phone;
        }
        if ( str_starts_with( $phone, '0098' ) ) {
            return '+98' . substr( $phone, 4 );
        }
        if ( str_starts_with( $phone, '98' ) && strlen( $phone ) === 12 ) {
            return '+' . $phone;
        }
        if ( str_starts_with( $phone, '+98' ) ) {
            return $phone;
        }

        return $phone;
    }

    public static function isValid( string $phone ): bool {
        $normalized = self::normalize( $phone );
        return (bool) preg_match( '/^\+989[0-9]{9}$/', $normalized );
    }

    /**
     * Format phone for display (09xxxxxxxxx)
     */
    public static function formatDisplay( string $phone ): string {
        $normalized = self::normalize( $phone );
        if ( str_starts_with( $normalized, '+98' ) ) {
            return '0' . substr( $normalized, 3 );
        }
        return $phone;
    }
}
