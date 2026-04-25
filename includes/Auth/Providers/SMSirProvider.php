<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class SMSirProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_smsir_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        return false;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $api_key     = $this->settings['api_key'] ?? '';
        $template_id = $this->settings['template_id'] ?? '';
        if ( empty( $api_key ) || empty( $template_id ) ) return false;

        $param_name = ! empty( $this->settings['parameter_name'] ) ? $this->settings['parameter_name'] : 'Code';

        $response = wp_remote_post( 'https://api.sms.ir/v1/send/verify', [
            'timeout' => 30,
            'headers' => [
                'Accept'       => 'application/json',
                'X-API-KEY'    => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( [
                'mobile'     => $phone,
                'templateId' => (int) $template_id,
                'parameters' => [
                    [
                        'name'  => $param_name,
                        'value' => (string) $code,
                    ],
                ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return false;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['status'] ?? 0 ) === 1;
    }

    public function getBalance(): ?float {
        return null;
    }
}
