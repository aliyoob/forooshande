<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{KeyboardBuilder, MessageTemplateEngine};
use RobotForooshande\Helpers\Logger;

class StartHandler {

    public function handle( array $ctx, string $text ): void {
        $bot     = $ctx['bot'];
        $state   = $ctx['state'];
        $botUser = $ctx['bot_user'];
        $chatId  = $ctx['chat_id'];

        // Parse deep link
        $deeplink = '';
        if ( preg_match( '/^\/start\s+(.+)$/', $text, $m ) ) {
            $deeplink = trim( $m[1] );
        }

        // If user has no phone, request it first (store deeplink for later)
        if ( empty( $botUser->phone ) ) {
            if ( $deeplink ) {
                $state->setState( $botUser->id, 'idle', [ 'pending_deeplink' => $deeplink ] );
            }
            $this->requestPhone( $ctx );
            return;
        }

        // Handle deep links
        if ( $deeplink ) {
            $this->handleDeeplink( $ctx, $deeplink );
            return;
        }

        // Normal start - show welcome + main menu
        $vars    = MessageTemplateEngine::userVars( $botUser );
        $message = MessageTemplateEngine::render( 'rf_msg_welcome', $vars );

        $keyboard = KeyboardBuilder::mainMenu( (bool) $botUser->is_admin );
        $bot->sendMessage( $chatId, $message, $keyboard );

        Logger::info( 'User started bot', [ 'chat_id' => $chatId ] );
    }

    public function requestPhone( array $ctx ): void {
        $msg = get_option( 'rf_msg_request_phone', '📱 لطفاً شماره تلفن خود را با دکمه زیر ارسال کنید:' );
        $ctx['bot']->sendMessage(
            $ctx['chat_id'],
            $msg,
            KeyboardBuilder::contactRequest()
        );
    }

    private function handleDeeplink( array $ctx, string $deeplink ): void {
        $parts = explode( '_', $deeplink, 2 );
        $type  = $parts[0] ?? '';
        $id    = $parts[1] ?? '';

        match ( $type ) {
            'product'  => ( new ProductHandler() )->showProduct( $ctx, (int) $id ),
            'category' => ( new CategoryHandler() )->showCategories( $ctx, (int) $id ),
            'order'    => ( new OrderHandler() )->showOrderDetailById( $ctx, (int) $id ),
            default    => ( new MenuHandler() )->showMainMenu( $ctx ),
        };
    }
}
