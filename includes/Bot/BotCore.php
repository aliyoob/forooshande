<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Helpers\Logger;

class BotCore {

    private string $token;
    private string $baseUrl;
    private string $platform;

    public function __construct( ?string $token = null, ?string $platform = null ) {
        $this->token    = $token ?? get_option( 'rf_bot_token', '' );
        $this->platform = $platform ?? get_option( 'rf_platform', 'telegram' );
        $this->baseUrl  = match ( $this->platform ) {
            'bale'  => "https://tapi.bale.ai/bot{$this->token}",
            default => "https://api.telegram.org/bot{$this->token}",
        };
    }

    public function getToken(): string {
        return $this->token;
    }

    public function getPlatform(): string {
        return $this->platform;
    }

    public function sendMessage( int|string $chatId, string $text, ?array $replyMarkup = null, string $parseMode = 'HTML' ): array {
        $params = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => $parseMode,
        ];
        if ( $replyMarkup ) {
            $params['reply_markup'] = wp_json_encode( $replyMarkup );
        }
        return $this->request( 'sendMessage', $params );
    }

    public function sendPhoto( int|string $chatId, string $photo, string $caption = '', ?array $replyMarkup = null, string $parseMode = 'HTML' ): array {
        $params = [
            'chat_id'    => $chatId,
            'photo'      => $photo,
            'caption'    => $caption,
            'parse_mode' => $parseMode,
        ];
        if ( $replyMarkup ) {
            $params['reply_markup'] = wp_json_encode( $replyMarkup );
        }
        return $this->request( 'sendPhoto', $params );
    }

    public function editMessageText( int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, string $parseMode = 'HTML' ): array {
        $params = [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $text,
            'parse_mode'   => $parseMode,
        ];
        if ( $replyMarkup ) {
            $params['reply_markup'] = wp_json_encode( $replyMarkup );
        }
        return $this->request( 'editMessageText', $params );
    }

    public function editMessageCaption( int|string $chatId, int $messageId, string $caption, ?array $replyMarkup = null, string $parseMode = 'HTML' ): array {
        $params = [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'caption'      => $caption,
            'parse_mode'   => $parseMode,
        ];
        if ( $replyMarkup ) {
            $params['reply_markup'] = wp_json_encode( $replyMarkup );
        }
        return $this->request( 'editMessageCaption', $params );
    }

    public function deleteMessage( int|string $chatId, int $messageId ): array {
        return $this->request( 'deleteMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ] );
    }

    public function answerCallbackQuery( string $callbackQueryId, string $text = '', bool $showAlert = false ): array {
        return $this->request( 'answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
            'show_alert'        => $showAlert,
        ] );
    }

    public function getMe(): array {
        return $this->request( 'getMe' );
    }

    public function setWebhook( string $url ): array {
        return $this->request( 'setWebhook', [ 'url' => $url ] );
    }

    public function deleteWebhook(): array {
        return $this->request( 'deleteWebhook' );
    }

    public function getWebhookInfo(): array {
        return $this->request( 'getWebhookInfo' );
    }

    public function sendDocument( int|string $chatId, string $document, string $caption = '' ): array {
        return $this->request( 'sendDocument', [
            'chat_id'  => $chatId,
            'document' => $document,
            'caption'  => $caption,
        ] );
    }

    public function sendLocation( int|string $chatId, float $lat, float $lon ): array {
        return $this->request( 'sendLocation', [
            'chat_id'   => $chatId,
            'latitude'  => $lat,
            'longitude' => $lon,
        ] );
    }

    public function getChatMember( int|string $chatId, int $userId ): array {
        return $this->request( 'getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ] );
    }

    private function request( string $method, array $params = [] ): array {
        $url      = $this->baseUrl . '/' . $method;
        $response = wp_remote_post( $url, [
            'timeout' => 30,
            'body'    => $params,
        ] );

        if ( is_wp_error( $response ) ) {
            Logger::error( "Bot API error: {$method}", [
                'error'  => $response->get_error_message(),
                'params' => $params,
            ] );
            return [ 'ok' => false, 'error' => $response->get_error_message() ];
        }

        $body   = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        if ( ! is_array( $result ) ) {
            Logger::error( "Bot API invalid response: {$method}", [ 'body' => $body ] );
            return [ 'ok' => false, 'error' => 'Invalid JSON response' ];
        }

        if ( empty( $result['ok'] ) ) {
            Logger::warning( "Bot API failed: {$method}", [
                'description' => $result['description'] ?? 'Unknown error',
            ] );
        }

        return $result;
    }

    /**
     * Non-blocking request for notifications
     */
    public function sendAsync( string $method, array $params ): void {
        $url = $this->baseUrl . '/' . $method;
        wp_remote_post( $url, [
            'timeout'  => 0.01,
            'blocking' => false,
            'body'     => $params,
        ] );
    }
}
