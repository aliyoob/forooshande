<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\Helpers\PersianDate;

class BroadcastHandler {

    public function handleCallback( array $ctx ): void {
        $action = $ctx['parts'][0] ?? '';

        match ( $action ) {
            'broadcast_type'    => $this->selectType( $ctx ),
            'broadcast_confirm' => $this->confirm( $ctx ),
            'broadcast_cancel'  => $this->cancel( $ctx ),
            default             => $this->promptBroadcast( $ctx ),
        };
    }

    public function promptBroadcast( array $ctx ): void {
        if ( ! ( $ctx['is_admin'] ?? false ) ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '❌ دسترسی ندارید.' );
            return;
        }

        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_broadcast_type' );

        $rows = [
            [
                KeyboardBuilder::inlineButton( '📝 متن', 'broadcast_type:text' ),
                KeyboardBuilder::inlineButton( '🖼 عکس + متن', 'broadcast_type:photo' ),
            ],
            KeyboardBuilder::backButton( 'admin_panel' ),
        ];

        $ctx['bot']->sendMessage( $ctx['chat_id'], '📢 نوع پیام همگانی را انتخاب کنید:', KeyboardBuilder::inline( $rows ) );
    }

    public function selectType( array $ctx ): void {
        $type = $ctx['parts'][1] ?? 'text';
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_broadcast_content', [ 'type' => $type ] );

        if ( $type === 'photo' ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '🖼 عکس مورد نظر را بفرستید (همراه با کپشن):' );
        } else {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '📝 متن پیام همگانی را وارد کنید:' );
        }
    }

    public function receiveContent( array $ctx, ?string $text = null, ?string $photo = null ): void {
        $stateData = $ctx['state']->getStateData( $ctx['bot_user'] );
        $type      = $stateData['type'] ?? 'text';
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];

        $content = [
            'type'    => $type,
            'text'    => $text,
            'photo'   => $photo,
        ];

        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_broadcast_confirm', [ 'content' => $content ] );

        $preview = "📢 پیش‌نمایش پیام:\n━━━━━━━━━━━━━━\n\n";
        if ( $type === 'photo' && $photo ) {
            $bot->sendPhoto( $chatId, $photo, $text ?? '' );
        } else {
            $preview .= $text ?? '';
            $bot->sendMessage( $chatId, $preview );
        }

        $rows = [
            [
                KeyboardBuilder::inlineButton( '✅ ارسال', 'broadcast_confirm' ),
                KeyboardBuilder::inlineButton( '❌ انصراف', 'broadcast_cancel' ),
            ],
        ];
        $bot->sendMessage( $chatId, '↑ آیا پیام بالا ارسال شود؟', KeyboardBuilder::inline( $rows ) );
    }

    public function confirm( array $ctx ): void {
        global $wpdb;
        $stateData = $ctx['state']->getStateData( $ctx['bot_user'] );
        $content   = $stateData['content'] ?? null;
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];

        if ( ! $content ) {
            $bot->sendMessage( $chatId, '❌ محتوایی یافت نشد.' );
            $ctx['state']->clearState( $ctx['bot_user']->id );
            return;
        }

        $ctx['state']->clearState( $ctx['bot_user']->id );

        // Get all bot users
        $users = $wpdb->get_results( "SELECT chat_id FROM {$wpdb->prefix}rf_bot_users WHERE chat_id IS NOT NULL" );
        $total = count( $users );

        $bot->sendMessage( $chatId, "📢 شروع ارسال به " . PersianDate::toPersianDigits( (string) $total ) . " کاربر..." );

        $sent   = 0;
        $failed = 0;

        foreach ( $users as $user ) {
            $targetChatId = $user->chat_id;
            if ( $targetChatId == $chatId ) continue; // Skip admin

            $result = false;
            if ( $content['type'] === 'photo' && $content['photo'] ) {
                $result = $bot->sendPhoto( $targetChatId, $content['photo'], $content['text'] ?? '' );
            } else {
                $result = $bot->sendMessage( $targetChatId, $content['text'] ?? '' );
            }

            if ( $result ) {
                $sent++;
            } else {
                $failed++;
            }

            // Rate limiting
            if ( ( $sent + $failed ) % 30 === 0 ) {
                sleep( 1 );
            }
        }

        $sentP   = PersianDate::toPersianDigits( (string) $sent );
        $failedP = PersianDate::toPersianDigits( (string) $failed );

        $bot->sendMessage(
            $chatId,
            "✅ ارسال همگانی تمام شد.\n\n📤 ارسال موفق: {$sentP}\n❌ ناموفق: {$failedP}"
        );
    }

    public function cancel( array $ctx ): void {
        $ctx['state']->clearState( $ctx['bot_user']->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '❌ ارسال همگانی لغو شد.' );
    }
}
