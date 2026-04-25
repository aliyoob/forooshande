<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class IppanelProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_ippanel_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        if ( empty( $username ) || empty( $password ) ) return false;

        $response = wp_remote_post( 'https://ippanel.com/api/select', [
            'timeout' => 30,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'op'      => 'send',
                'user'    => $username,
                'pass'    => $password,
                'fromNum' => $this->settings['sender'] ?? '3000505',
                'toNum'   => $phone,
                'message' => $message,
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return false;
        return wp_remote_retrieve_response_code( $response ) === 200;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        $template = $this->settings['template'] ?? '';
        if ( empty( $username ) || empty( $password ) || empty( $template ) ) return false;

        $response = wp_remote_post( 'https://ippanel.com/api/select', [
            'timeout' => 30,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'op'          => 'pattern',
                'user'        => trim( $username ),
                'pass'        => trim( $password ),
                'fromNum'     => $this->settings['sender'] ?? '3000505',
                'toNum'       => $phone,
                'patternCode' => $template,
                'inputData'   => [ [ 'otp' => $code ] ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return false;

        $raw  = wp_remote_retrieve_body( $response );
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) return false;

        // Response may be a numeric message ID (success) or JSON
        if ( is_numeric( trim( $raw ) ) ) return true;

        $body = json_decode( $raw, true );
        return ( $body['status'] ?? '' ) === 'OK' || ( $body['success'] ?? false ) === true;
    }

    public function getBalance(): ?float {
        return null;
    }
}
