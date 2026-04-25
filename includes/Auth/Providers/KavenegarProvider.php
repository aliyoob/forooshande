<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class KavenegarProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_kavenegar_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        $token = $this->settings['token'] ?? '';
        if ( empty( $token ) ) return false;

        $response = wp_remote_get( add_query_arg( [
            'receptor' => $phone,
            'message'  => $message,
            'sender'   => $this->settings['sender'] ?? '',
        ], "https://api.kavenegar.com/v1/{$token}/sms/send.json" ), [
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['return']['status'] ?? 0 ) === 200;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $token    = $this->settings['token'] ?? '';
        $template = $this->settings['template'] ?? '';
        if ( empty( $token ) || empty( $template ) ) return false;

        $response = wp_remote_get( add_query_arg( [
            'receptor' => $phone,
            'token'    => $code,
            'template' => $template,
        ], "https://api.kavenegar.com/v1/{$token}/verify/lookup.json" ), [
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['return']['status'] ?? 0 ) === 200;
    }

    public function getBalance(): ?float {
        $token = $this->settings['token'] ?? '';
        if ( empty( $token ) ) return null;

        $response = wp_remote_get(
            "https://api.kavenegar.com/v1/{$token}/account/info.json",
            [ 'timeout' => 10 ]
        );

        if ( is_wp_error( $response ) ) return null;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['entries']['remaincredit'] ?? null;
    }
}
