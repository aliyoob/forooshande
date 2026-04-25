<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class MenuHandler {

    public function showMainMenu( array $ctx ): void {
        $botUser = $ctx['bot_user'];
        $msg     = get_option( 'rf_shop_name', 'فروشگاه' ) . "\n" . 'از منوی زیر استفاده کنید:';

        $ctx['bot']->sendMessage(
            $ctx['chat_id'],
            $msg,
            KeyboardBuilder::mainMenu( (bool) $botUser->is_admin )
        );
    }

    public function showAccount( array $ctx ): void {
        $botUser = $ctx['bot_user'];
        $chatId  = $ctx['chat_id'];
        $bot     = $ctx['bot'];

        // Get order stats
        $orderCount  = 0;
        $totalSpent  = 0;

        if ( $botUser->user_id ) {
            $orders = wc_get_orders( [
                'customer_id' => $botUser->user_id,
                'limit'       => -1,
                'return'      => 'ids',
            ] );
            $orderCount = count( $orders );
            foreach ( $orders as $oid ) {
                $o = wc_get_order( $oid );
                if ( $o ) $totalSpent += (float) $o->get_total();
            }
        }

        $displayPhone = $botUser->phone ? \RobotForooshande\User\PhoneNormalizer::formatDisplay( $botUser->phone ) : '-';

        $text = "👤 حساب کاربری:\n━━━━━━━━━━━━━━\n";
        $text .= "📱 شماره تلفن: " . PersianDate::toPersianDigits( $displayPhone ) . "\n";
        $text .= "👤 نام: " . ( $botUser->first_name ?? '' ) . ' ' . ( $botUser->last_name ?? '' ) . "\n";

        if ( $botUser->user_id ) {
            $address = get_user_meta( $botUser->user_id, 'billing_address_1', true );
            if ( $address ) {
                $text .= "📍 آدرس: " . $address . "\n";
            }
        }

        $text .= "📦 تعداد سفارشات: " . PersianDate::toPersianDigits( (string) $orderCount ) . "\n";
        $text .= "💰 مجموع خرید: " . PriceFormatter::format( $totalSpent ) . "\n";

        $keyboard = KeyboardBuilder::inline( [
            [
                KeyboardBuilder::inlineButton( '📝 ویرایش نام', 'edit_name' ),
                KeyboardBuilder::inlineButton( '📍 ویرایش آدرس', 'edit_address' ),
            ],
            KeyboardBuilder::backButton(),
        ] );

        $bot->sendMessage( $chatId, $text, $keyboard );
    }

    public function showAbout( array $ctx ): void {
        $shopName = get_option( 'rf_shop_name', get_bloginfo( 'name' ) );
        $siteUrl  = home_url();
        $support  = get_option( 'rf_support_info', '' );

        $text = "ℹ️ درباره {$shopName}\n━━━━━━━━━━━━━━\n";
        $text .= "🌐 وب‌سایت: {$siteUrl}\n";
        if ( $support ) {
            $text .= "\n{$support}";
        }

        $keyboard = KeyboardBuilder::inline( [
            [ KeyboardBuilder::urlButton( '🌐 مشاهده سایت', $siteUrl ) ],
            KeyboardBuilder::backButton(),
        ] );

        $ctx['bot']->sendMessage( $ctx['chat_id'], $text, $keyboard );
    }

    public function saveNewName( array $ctx, string $text ): void {
        $state   = $ctx['state'];
        $botUser = $ctx['bot_user'];

        $parts = preg_split( '/\s+/', trim( $text ), 2 );
        $first = $parts[0] ?? '';
        $last  = $parts[1] ?? '';

        if ( $botUser->user_id ) {
            wp_update_user( [
                'ID'         => $botUser->user_id,
                'first_name' => sanitize_text_field( $first ),
                'last_name'  => sanitize_text_field( $last ),
            ] );
            update_user_meta( $botUser->user_id, 'billing_first_name', $first );
            update_user_meta( $botUser->user_id, 'billing_last_name', $last );
        }

        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'rf_bot_users', [
            'first_name' => $first,
            'last_name'  => $last,
        ], [ 'id' => $botUser->id ], [ '%s', '%s' ], [ '%d' ] );

        $state->clearState( $botUser->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✅ نام شما با موفقیت تغییر کرد.' );
        $this->showMainMenu( $ctx );
    }

    public function saveNewAddress( array $ctx, string $text ): void {
        $state   = $ctx['state'];
        $botUser = $ctx['bot_user'];

        if ( $botUser->user_id ) {
            update_user_meta( $botUser->user_id, 'billing_address_1', sanitize_textarea_field( $text ) );
            update_user_meta( $botUser->user_id, 'shipping_address_1', sanitize_textarea_field( $text ) );
        }

        $state->clearState( $botUser->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✅ آدرس شما با موفقیت تغییر کرد.' );
        $this->showMainMenu( $ctx );
    }
}
