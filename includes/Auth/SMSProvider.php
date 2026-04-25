<?php
namespace RobotForooshande\Auth;

if ( ! defined( 'ABSPATH' ) ) exit;

interface SMSProvider {
    public function send( string $phone, string $message ): bool;
    public function sendOTP( string $phone, string $code ): bool;
    public function getBalance(): ?float;
}
