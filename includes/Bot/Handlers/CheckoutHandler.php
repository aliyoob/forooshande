<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\WooCommerce\{CartSync, ShippingManager, PaymentGatewayBridge};
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};
use RobotForooshande\User\UserSync;

class CheckoutHandler {

    public function startCheckout( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $cart  = new CartSync();
        $items = $cart->getItems( $botUserId );

        if ( empty( $items ) ) {
            $bot->sendMessage( $chatId, '🛒 سبد خرید شما خالی است.' );
            return;
        }

        // Validate stock
        $invalid = $cart->validateCart( $botUserId );
        if ( ! empty( $invalid ) ) {
            $names = implode( ', ', $invalid );
            $bot->sendMessage( $chatId, "❌ محصولات زیر ناموجود هستند و حذف شدند:\n{$names}" );
            $items = $cart->getItems( $botUserId );
            if ( empty( $items ) ) {
                $bot->sendMessage( $chatId, '🛒 سبد خرید خالی شد.' );
                return;
            }
        }

        // Sync WP user
        $botUser = $ctx['bot_user'];
        if ( $botUser->phone && ! $botUser->user_id ) {
            $wpUserId = UserSync::sync( $botUser->phone, [
                'chat_id'    => $botUser->chat_id,
                'platform'   => $botUser->platform ?? 'telegram',
                'first_name' => $botUser->first_name ?? '',
                'last_name'  => $botUser->last_name ?? '',
            ] );
            if ( $wpUserId ) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'rf_bot_users',
                    [ 'user_id' => $wpUserId ],
                    [ 'id' => $botUser->id ],
                    [ '%d' ],
                    [ '%d' ]
                );
                $botUser->user_id = $wpUserId;
            }
        }

        // Check if we have address
        $address = $ctx['bot_user']->address ?? '';

        if ( empty( $address ) ) {
            $ctx['state']->setState( $botUserId, 'awaiting_checkout_address' );
            $bot->sendMessage( $chatId, "📍 لطفاً آدرس پستی خود را وارد کنید:\n\n(شامل استان، شهر، آدرس کامل و کد پستی)" );
            return;
        }

        $this->showCheckoutSummary( $ctx );
    }

    public function receiveAddress( array $ctx, string $address ): void {
        global $wpdb;
        $botUserId = $ctx['bot_user']->id;

        // Save address
        $wpdb->update(
            $wpdb->prefix . 'rf_bot_users',
            [ 'address' => sanitize_textarea_field( $address ) ],
            [ 'id' => $botUserId ],
            [ '%s' ],
            [ '%d' ]
        );

        $ctx['bot_user']->address = $address;
        $ctx['state']->clearState( $botUserId );

        $this->showCheckoutSummary( $ctx );
    }

    public function showCheckoutSummary( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $cart  = new CartSync();
        $items = $cart->getItems( $botUserId );
        $name  = $ctx['bot_user']->first_name ?? '';

        $text = "📋 خلاصه سفارش:\n━━━━━━━━━━━━━━\n";
        $text .= "👤 {$name}\n";
        $text .= "📍 " . ( $ctx['bot_user']->address ?? 'ثبت نشده' ) . "\n";
        $text .= "━━━━━━━━━━━━━━\n";

        $subtotal = 0;
        foreach ( $items as $item ) {
            $product = wc_get_product( $item->variation_id ?: $item->product_id );
            if ( ! $product ) continue;
            $lineTotal = (float) $product->get_price() * $item->quantity;
            $subtotal += $lineTotal;
            $text .= "• " . $product->get_name()
                   . " × " . PersianDate::toPersianDigits( (string) $item->quantity )
                   . " = " . PriceFormatter::format( (string) $lineTotal ) . "\n";
        }

        $coupon   = $cart->getCoupon( $botUserId );
        $discount = 0;
        if ( $coupon ) {
            $text .= "\n🏷 کد تخفیف: {$coupon}\n";
        }

        $text .= "\n━━━━━━━━━━━━━━\n";
        $text .= "💰 جمع: " . PriceFormatter::format( (string) $subtotal ) . "\n";

        // Shipping info
        $userAddress = [
            'province' => '',
            'city'     => '',
            'postcode' => '',
        ];
        $methods = ShippingManager::getAvailableMethods( $userAddress );
        if ( ! empty( $methods ) ) {
            $text .= "\n📦 روش ارسال:\n";
            $shippingRows = [];
            foreach ( $methods as $idx => $method ) {
                $cost = $method['cost'] > 0 ? PriceFormatter::format( (string) $method['cost'] ) : 'رایگان';
                $text .= "  {$method['title']} - {$cost}\n";
                $shippingRows[] = KeyboardBuilder::inlineButton(
                    "📦 {$method['title']}",
                    "shipping:{$idx}"
                );
            }
        }

        // Payment methods
        $gateways = PaymentGatewayBridge::getActiveGateways();
        $rows     = [];

        if ( ! empty( $shippingRows ?? [] ) ) {
            foreach ( array_chunk( $shippingRows, 2 ) as $chunk ) {
                $rows[] = $chunk;
            }
        }

        $payRow = [];
        foreach ( $gateways as $id => $gateway ) {
            $title = is_object( $gateway ) ? $gateway->get_title() : (string) $gateway;
            if ( $id === 'cod' ) {
                $payRow[] = KeyboardBuilder::inlineButton( "💵 {$title}", 'pay_cod' );
            } else {
                $payRow[] = KeyboardBuilder::inlineButton( "💳 {$title}", "pay_online:{$id}" );
            }
        }
        if ( ! empty( $payRow ) ) {
            foreach ( array_chunk( $payRow, 2 ) as $chunk ) {
                $rows[] = $chunk;
            }
        }

        $rows[] = [
            KeyboardBuilder::inlineButton( '📝 ویرایش آدرس', 'edit_checkout_address' ),
            KeyboardBuilder::inlineButton( '📝 یادداشت', 'order_note' ),
        ];
        $rows[] = KeyboardBuilder::backButton( 'cart_refresh' );

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function editAddress( array $ctx ): void {
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_checkout_address' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], "📍 آدرس جدید را وارد کنید:\n\n(شامل استان، شهر، آدرس کامل و کد پستی)" );
    }

    public function promptOrderNote( array $ctx ): void {
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_order_note' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '📝 یادداشت سفارش خود را وارد کنید:' );
    }

    public function receiveOrderNote( array $ctx, string $note ): void {
        $cart = new CartSync();
        $cart->setMeta( $ctx['bot_user']->id, 'order_note', sanitize_textarea_field( $note ) );
        $ctx['state']->clearState( $ctx['bot_user']->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✅ یادداشت ذخیره شد.' );
        $this->showCheckoutSummary( $ctx );
    }

    public function selectShipping( array $ctx ): void {
        $index = (int) ( $ctx['parts'][1] ?? 0 );
        $cart  = new CartSync();
        $cart->setMeta( $ctx['bot_user']->id, 'shipping_method', $index );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✅ روش ارسال انتخاب شد.' );
        $this->showCheckoutSummary( $ctx );
    }
}
