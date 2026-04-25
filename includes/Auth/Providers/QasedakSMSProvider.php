<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class QasedakSMSProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_qasedaksms_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        return false;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $apikey   = $this->settings['apikey'] ?? '';
        $template = $this->settings['template'] ?? '';
        if ( empty( $apikey ) || empty( $template ) ) return false;

        $param1_name = ! empty( $this->settings['param1_name'] ) ? $this->settings['param1_name'] : 'param1';

        $response = wp_remote_post( 'http://api.ghasedaksms.com/v2/send/verify', [
            'timeout' => 30,
            'headers' => [
                'apikey'        => $apikey,
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'cache-control' => 'no-cache',
            ],
            'body' => [
                'receptor'   => $phone,
                'type'       => 1,
                'template'   => $template,
                $param1_name => (string) $code,
            ],
        ] );

        if ( is_wp_error( $response ) ) return false;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['result'] ?? '' ) === 'success';
    }

    public function getBalance(): ?float {
        return null;
    }
}
