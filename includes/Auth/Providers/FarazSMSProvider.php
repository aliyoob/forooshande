<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class FarazSMSProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_farazsms_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        // FarazSMS pattern-only; fallback to sendOTP
        return false;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $api_key      = $this->settings['api_key'] ?? '';
        $line_number  = $this->settings['line_number'] ?? '';
        $pattern_code = $this->settings['pattern_code'] ?? '';
        if ( empty( $api_key ) || empty( $line_number ) || empty( $pattern_code ) ) return false;

        $otp_variable = ! empty( $this->settings['otp_variable'] ) ? $this->settings['otp_variable'] : 'var';

        $response = wp_remote_post( 'https://api.iranpayamak.com/ws/v1/sms/pattern', [
            'timeout' => 30,
            'headers' => [
                'Accept'       => 'application/json',
                'Api-Key'      => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( [
                'code'          => $pattern_code,
                'attributes'    => [ $otp_variable => (string) $code ],
                'recipient'     => $phone,
                'line_number'   => $line_number,
                'number_format' => 'english',
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return false;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['status'] ?? '' ) === 'success' || ( $body['success'] ?? false ) === true;
    }

    public function getBalance(): ?float {
        return null;
    }
}
