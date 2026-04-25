<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{KeyboardBuilder, MessageTemplateEngine};
use RobotForooshande\User\{PhoneNormalizer, UserSync};
use RobotForooshande\Helpers\Logger;

class ContactHandler {

    public function handle( array $ctx, array $contact ): void {
        $bot     = $ctx['bot'];
        $state   = $ctx['state'];
        $botUser = $ctx['bot_user'];
        $chatId  = $ctx['chat_id'];

        $phone = $contact['phone_number'] ?? '';
        if ( empty( $phone ) ) {
            $bot->sendMessage( $chatId, '❌ شماره تلفن دریافت نشد. لطفاً دوباره تلاش کنید.' );
            return;
        }

        // Normalize
        $normalized = PhoneNormalizer::normalize( $phone );

        if ( ! PhoneNormalizer::isValid( $normalized ) ) {
            $bot->sendMessage( $chatId, '❌ شماره تلفن نامعتبر است. لطفاً با شماره ایرانی تلاش کنید.' );
            return;
        }

        // Sync with WordPress
        $wpUserId = UserSync::sync( $normalized, [
            'chat_id'    => $chatId,
            'first_name' => $botUser->first_name,
            'last_name'  => $botUser->last_name,
            'platform'   => get_option( 'rf_platform', 'telegram' ),
            'phone'      => $normalized,
        ] );

        // Save phone in bot users table
        $state->setPhone( $botUser->id, $normalized, $wpUserId ?: null );

        Logger::info( 'Contact received', [
            'chat_id' => $chatId,
            'phone'   => $normalized,
            'wp_user' => $wpUserId,
        ] );

        // Check for pending deeplink
        $stateData = $state->getStateData( $botUser );
        $state->clearState( $botUser->id );

        if ( ! empty( $stateData['pending_deeplink'] ) ) {
            $refreshed = $state->refreshUser( $botUser->id );
            $ctx['bot_user'] = $refreshed;

            $parts = explode( '_', $stateData['pending_deeplink'], 2 );
            $type  = $parts[0] ?? '';
            $id    = $parts[1] ?? '';

            match ( $type ) {
                'product'  => ( new ProductHandler() )->showProduct( $ctx, (int) $id ),
                'category' => ( new CategoryHandler() )->showCategories( $ctx, (int) $id ),
                default    => null,
            };
            return;
        }

        // Welcome message
        $refreshed       = $state->refreshUser( $botUser->id );
        $ctx['bot_user'] = $refreshed;
        $vars            = MessageTemplateEngine::userVars( $refreshed );
        $message         = MessageTemplateEngine::render( 'rf_msg_welcome', $vars );

        $keyboard = KeyboardBuilder::mainMenu( (bool) $refreshed->is_admin );
        $bot->sendMessage( $chatId, $message, $keyboard );
    }
}
