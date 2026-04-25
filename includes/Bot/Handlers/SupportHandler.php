<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\Helpers\PersianDate;

class SupportHandler {

    public function startSupport( array $ctx ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $supportEnabled = get_option( 'rf_support_enabled', '1' );
        if ( $supportEnabled !== '1' ) {
            $bot->sendMessage( $chatId, '❌ بخش پشتیبانی غیرفعال است.' );
            return;
        }

        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_support_message' );

        $text = "💬 پشتیبانی:\n━━━━━━━━━━━━━━\n\n";
        $text .= "پیام خود را بنویسید تا به تیم پشتیبانی ارسال شود.\n";
        $text .= "می‌توانید متن، عکس یا فایل بفرستید.\n\n";
        $text .= "برای انصراف /cancel بزنید.";

        $bot->sendMessage( $chatId, $text );
    }

    public function receiveMessage( array $ctx, ?string $text = null, ?string $photo = null ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUser   = $ctx['bot_user'];

        if ( $text === '/cancel' ) {
            $ctx['state']->clearState( $botUser->id );
            $bot->sendMessage( $chatId, '❌ انصراف از پشتیبانی.' );
            return;
        }

        $ctx['state']->clearState( $botUser->id );

        // Forward to admin chat IDs
        $adminChatIds = get_option( 'rf_admin_chat_ids', '' );
        $admins       = array_filter( array_map( 'trim', preg_split( '/[\s,]+/', $adminChatIds ) ) );

        if ( empty( $admins ) ) {
            $bot->sendMessage( $chatId, '❌ امکان ارسال پیام وجود ندارد. لطفاً بعداً تلاش کنید.' );
            return;
        }

        $name    = $botUser->first_name ?? '';
        $phone   = $botUser->phone ?? '';
        $header  = "💬 پیام پشتیبانی جدید\n━━━━━━━━━━━━━━\n";
        $header .= "👤 {$name}\n";
        if ( $phone ) {
            $header .= "📱 {$phone}\n";
        }
        $header .= "🆔 Chat ID: {$chatId}\n";
        $header .= "━━━━━━━━━━━━━━\n\n";

        foreach ( $admins as $adminId ) {
            if ( $photo ) {
                $bot->sendPhoto( $adminId, $photo, $header . ( $text ?? '' ) );
            } else {
                $bot->sendMessage( $adminId, $header . ( $text ?? '' ) );
            }

            // Reply button
            $rows = [
                [ KeyboardBuilder::inlineButton( '💬 پاسخ', "support_reply:{$chatId}" ) ],
            ];
            $bot->sendMessage( $adminId, '↑ برای پاسخ:', KeyboardBuilder::inline( $rows ) );
        }

        $bot->sendMessage( $chatId, '✅ پیام شما ارسال شد. به زودی پاسخ داده می‌شود.' );
    }

    public function promptReply( array $ctx ): void {
        if ( ! ( $ctx['is_admin'] ?? false ) ) return;

        $targetChatId = $ctx['parts'][1] ?? '';
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_support_reply', [ 'target_chat_id' => $targetChatId ] );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '💬 پاسخ خود را بنویسید:' );
    }

    public function sendReply( array $ctx, ?string $text = null, ?string $photo = null ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $stateData = $ctx['state']->getStateData( $ctx['bot_user'] );
        $targetId  = $stateData['target_chat_id'] ?? '';

        if ( empty( $targetId ) ) {
            $bot->sendMessage( $chatId, '❌ خطا: مقصد یافت نشد.' );
            $ctx['state']->clearState( $ctx['bot_user']->id );
            return;
        }

        $ctx['state']->clearState( $ctx['bot_user']->id );

        $shopName = get_option( 'rf_shop_name', get_bloginfo( 'name' ) );
        $header   = "💬 پاسخ {$shopName}:\n━━━━━━━━━━━━━━\n\n";

        if ( $photo ) {
            $result = $bot->sendPhoto( $targetId, $photo, $header . ( $text ?? '' ) );
        } else {
            $result = $bot->sendMessage( $targetId, $header . ( $text ?? '' ) );
        }

        if ( $result ) {
            $bot->sendMessage( $chatId, '✅ پاسخ ارسال شد.' );
        } else {
            $bot->sendMessage( $chatId, '❌ خطا در ارسال پاسخ. ممکن است کاربر ربات را بلاک کرده باشد.' );
        }
    }
}
