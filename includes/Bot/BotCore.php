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
            'timeout' => 15,
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
     * Upload a local image file directly via multipart/form-data (cURL).
     * Avoids the platform's servers needing to fetch the image from our URL.
     * WebP files are automatically converted to JPEG because Bale (and some
     * Telegram clients) reject WebP uploads.
     * Returns the same shape as sendPhoto() so callers can treat them identically.
     */
    public function sendPhotoFile( int|string $chatId, string $filePath, string $caption = '', ?array $replyMarkup = null, string $parseMode = 'HTML' ): array {
        if ( ! function_exists( 'curl_init' ) ) {
            Logger::warning( 'sendPhotoFile: cURL unavailable', [ 'file' => $filePath ] );
            return [ 'ok' => false, 'error' => 'cURL not available' ];
        }

        if ( ! file_exists( $filePath ) || ! is_readable( $filePath ) ) {
            Logger::warning( 'sendPhotoFile: file not found', [ 'file' => $filePath ] );
            return [ 'ok' => false, 'error' => 'File not found' ];
        }

        // Convert WebP → JPEG so Bale and older clients accept the upload.
        $uploadPath = $filePath;
        $tempFile   = null;
        if ( strtolower( pathinfo( $filePath, PATHINFO_EXTENSION ) ) === 'webp' ) {
            $converted = $this->convertWebpToJpeg( $filePath );
            if ( $converted ) {
                $uploadPath = $converted;
                $tempFile   = $converted;
            }
        }

        $params = [
            'chat_id'    => $chatId,
            'photo'      => new \CURLFile( $uploadPath, 'image/jpeg', basename( $uploadPath ) ),
            'caption'    => $caption,
            'parse_mode' => $parseMode,
        ];
        if ( $replyMarkup ) {
            $params['reply_markup'] = wp_json_encode( $replyMarkup );
        }

        $ch = curl_init( $this->baseUrl . '/sendPhoto' );
        curl_setopt_array( $ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ] );

        $body  = curl_exec( $ch );
        $errno = curl_errno( $ch );
        curl_close( $ch );

        // Remove temp conversion file regardless of outcome.
        if ( $tempFile && file_exists( $tempFile ) ) {
            @unlink( $tempFile );
        }

        if ( $errno || $body === false ) {
            Logger::error( 'sendPhotoFile cURL error', [ 'errno' => $errno, 'file' => $filePath ] );
            return [ 'ok' => false, 'error' => "cURL error {$errno}" ];
        }

        $result = json_decode( $body, true );
        if ( ! is_array( $result ) ) {
            Logger::error( 'sendPhotoFile: invalid JSON response', [ 'body' => $body ] );
            return [ 'ok' => false, 'error' => 'Invalid JSON response' ];
        }

        if ( empty( $result['ok'] ) ) {
            Logger::warning( 'Bot API failed: sendPhotoFile', [
                'description' => $result['description'] ?? 'Unknown error',
                'file'        => $filePath,
            ] );
        }

        return $result;
    }

    /**
     * Convert a WebP image to a temporary JPEG file.
     * Uses WordPress's WP_Image_Editor which tries both GD and Imagick automatically.
     * Returns the temp file path on success, or null if conversion fails.
     */
    private function convertWebpToJpeg( string $webpPath ): ?string {
        $editor = wp_get_image_editor( $webpPath );
        if ( is_wp_error( $editor ) ) {
            Logger::warning( 'convertWebpToJpeg: no image editor available', [
                'file'  => $webpPath,
                'error' => $editor->get_error_message(),
            ] );
            return null;
        }

        $tmpPath = sys_get_temp_dir() . '/rf_' . uniqid( '', true ) . '.jpg';
        $saved   = $editor->save( $tmpPath, 'image/jpeg' );

        if ( is_wp_error( $saved ) ) {
            Logger::warning( 'convertWebpToJpeg: save failed', [
                'file'  => $webpPath,
                'error' => $saved->get_error_message(),
            ] );
            return null;
        }

        // WP_Image_Editor may adjust the path (e.g. add suffix), use the reported path.
        $resultPath = $saved['path'] ?? $tmpPath;
        if ( ! file_exists( $resultPath ) ) {
            Logger::warning( 'convertWebpToJpeg: output file missing', [ 'expected' => $resultPath ] );
            return null;
        }

        return $resultPath;
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
