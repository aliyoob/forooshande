<?php
namespace RobotForooshande\Auth\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Auth\SMSProvider;

class MeliPayamakProvider implements SMSProvider {

    private array $settings;

    public function __construct() {
        $this->settings = get_option( 'rf_melipayamak_settings', [] );
    }

    public function send( string $phone, string $message ): bool {
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        $sender   = $this->settings['sender'] ?? '';
        if ( empty( $username ) || empty( $password ) ) return false;

        $response = wp_remote_post( 'https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'username' => $username,
                'password' => $password,
                'from'     => $sender,
                'to'       => $phone,
                'text'     => $message,
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ( $body['RetStatus'] ?? 0 ) === 1;
    }

    public function sendOTP( string $phone, string $code ): bool {
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        $template = $this->settings['template'] ?? '';
        if ( empty( $username ) || empty( $password ) || empty( $template ) ) return false;

        try {
            $sms_client = new \SoapClient( 'http://api.payamak-panel.com/post/Send.asmx?wsdl', [
                'encoding'   => 'UTF-8',
                'trace'      => 1,
                'exceptions' => true,
            ] );

            $result = $sms_client->SendByBaseNumber( [
                'username' => $username,
                'password' => $password,
                'text'     => [ $code ],
                'to'       => $phone,
                'bodyId'   => (int) $template,
            ] );

            $send_result = $result->SendByBaseNumberResult;
            return $send_result > 1000;

        } catch ( \SoapFault $e ) {
            return false;
        }
    }

    public function getBalance(): ?float {
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        if ( empty( $username ) || empty( $password ) ) return null;

        $response = wp_remote_post( 'https://rest.payamak-panel.com/api/SendSMS/GetCredit', [
            'timeout' => 10,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'username' => $username,
                'password' => $password,
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return null;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['Value'] ?? null;
    }
}
