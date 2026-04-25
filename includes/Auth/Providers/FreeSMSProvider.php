<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class FreeSMSProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_freesms_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        return false;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $access_hash = $this->settings['access_hash'] ?? '';
        $pattern_id  = $this->settings['pattern_id'] ?? '';
        if ( empty( $access_hash ) || empty( $pattern_id ) ) return false;

        $response = wp_remote_post( 'https://smspanel.trez.ir/SendPatternCodeWithUrl.ashx', [
            'timeout' => 30,
            'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
            'body'    => [
                'AccessHash' => $access_hash,
                'Mobile'     => $phone,
                'PatternId'  => $pattern_id,
                'token1'     => (string) $code,
            ],
        ] );

        if ( is_wp_error( $response ) ) return false;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

        $raw = trim( wp_remote_retrieve_body( $response ) );

        // Numeric positive response = success
        if ( is_numeric( $raw ) && (int) $raw > 0 ) return true;

        $body = json_decode( $raw, true );
        if ( is_array( $body ) ) {
            return ( $body['status'] ?? '' ) === 'success' || ( $body['result'] ?? '' ) === 'success';
        }

        return false;
    }

    public function getBalance(): ?float {
        return null;
    }
}
