<?php
namespace RobotForooshande\Auth;

if ( ! defined( 'ABSPATH' ) ) exit;

class SMSFactory {

    public static function make( ?string $provider = null ): ?SMSProvider {
        $provider = $provider ?? get_option( 'rf_sms_provider', '' );

        return match ( $provider ) {
            'kavenegar'    => new Providers\KavenegarProvider(),
            'ippanel'      => new Providers\IppanelProvider(),
            'melipayamak'  => new Providers\MeliPayamakProvider(),
            'farazsms'     => new Providers\FarazSMSProvider(),
            'freesms'      => new Providers\FreeSMSProvider(),
            'qasedaksms'   => new Providers\QasedakSMSProvider(),
            'smsir'        => new Providers\SMSirProvider(),
            'amootsms'     => new Providers\AmootSMSProvider(),
            default        => null,
        };
    }

    public static function sendOTP( string $phone, string $code ): bool {
        $sms = self::make();
        if ( ! $sms ) return false;

        return $sms->sendOTP( $phone, $code );
    }
}
