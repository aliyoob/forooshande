<?php
namespace RobotForooshande\User;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Helpers\Logger;

class OTPManager {

    public static function generate( string $phone, string $method = 'sms' ): ?string {
        $normalized = PhoneNormalizer::normalize( $phone );

        // Rate limiting
        if ( self::isRateLimited( $normalized ) ) {
            return null;
        }

        $length  = (int) get_option( 'rf_otp_length', 5 );
        $length  = max( 4, min( 8, $length ) );
        $min     = (int) str_pad( '1', $length, '0' );
        $max     = (int) str_pad( '', $length, '9' );
        $code    = (string) random_int( $min, $max );
        $expiry  = (int) get_option( 'rf_otp_expiry', 120 );

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rf_otps',
            [
                'phone'      => $normalized,
                'code'       => $code,
                'method'     => $method,
                'expires_at' => gmdate( 'Y-m-d H:i:s', time() + $expiry ),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );

        Logger::info( 'OTP generated', [ 'phone' => $normalized, 'method' => $method ] );

        return $code;
    }

    public static function verify( string $phone, string $code ): bool {
        $normalized = PhoneNormalizer::normalize( $phone );
        global $wpdb;

        $otp = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rf_otps
             WHERE phone = %s AND code = %s AND verified = 0 AND expires_at > %s
             ORDER BY id DESC LIMIT 1",
            $normalized, $code, current_time( 'mysql', true )
        ) );

        if ( ! $otp ) {
            // Increment attempts
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}rf_otps
                 SET attempts = attempts + 1
                 WHERE phone = %s AND verified = 0 AND expires_at > %s
                 ORDER BY id DESC LIMIT 1",
                $normalized, current_time( 'mysql', true )
            ) );
            return false;
        }

        // Max attempts check
        if ( (int) $otp->attempts >= 5 ) {
            return false;
        }

        // Mark as verified
        $wpdb->update(
            $wpdb->prefix . 'rf_otps',
            [ 'verified' => 1 ],
            [ 'id' => $otp->id ],
            [ '%d' ],
            [ '%d' ]
        );

        Logger::info( 'OTP verified', [ 'phone' => $normalized ] );

        return true;
    }

    public static function isRateLimited( string $phone ): bool {
        $normalized = PhoneNormalizer::normalize( $phone );
        $limit      = (int) get_option( 'rf_otp_rate_limit', 3 );

        global $wpdb;
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rf_otps
             WHERE phone = %s AND created_at > %s",
            $normalized,
            gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) )
        ) );

        return $count >= $limit;
    }

    public static function cleanup(): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}rf_otps WHERE expires_at < %s",
            current_time( 'mysql', true )
        ) );
    }
}
