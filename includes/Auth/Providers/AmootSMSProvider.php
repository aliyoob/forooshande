<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class AmootSMSProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_amootsms_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        return false;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $token           = $this->settings['token'] ?? '';
        $pattern_code_id = $this->settings['pattern_code_id'] ?? '';
        if ( empty( $token ) || empty( $pattern_code_id ) ) return false;

        $params_count = ! empty( $this->settings['pattern_params_count'] ) ? (int) $this->settings['pattern_params_count'] : 1;
        $otp_position = ! empty( $this->settings['otp_param_position'] ) ? (int) $this->settings['otp_param_position'] : 1;

        $pattern_values = array_fill( 0, $params_count, '' );
        $pattern_values[ $otp_position - 1 ] = (string) $code;

        $response = wp_remote_post( 'https://portal.amootsms.com/rest/SendWithPattern', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => $token,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'Mobile'        => $phone,
                'PatternCodeID' => (int) $pattern_code_id,
                'PatternValues' => implode( ',', $pattern_values ),
            ],
        ] );

        if ( is_wp_error( $response ) ) return false;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['status'] ) && in_array( $body['status'], [ 'success', 1, '1' ], true ) ) return true;
        if ( isset( $body['success'] ) && $body['success'] === true ) return true;

        return false;
    }

    public function getBalance(): ?float {
        return null;
    }
}
