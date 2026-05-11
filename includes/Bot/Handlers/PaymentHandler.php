<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{KeyboardBuilder, MessageTemplateEngine};
use RobotForooshande\WooCommerce\{CartSync, PaymentGatewayBridge};
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class PaymentHandler {

    public function payOnline( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;
        $gatewayId = $ctx['parts'][1] ?? '';

        $cart  = new CartSync();
        $items = $cart->getItems( $botUserId );

        if ( empty( $items ) ) {
            $bot->sendMessage( $chatId, '🛒 سبد خرید خالی است.' );
            return;
        }

        $bot->sendMessage( $chatId, '⏳ در حال ایجاد سفارش...' );

        $botUser  = $ctx['bot_user'];
        $addrCity = (string) $cart->getMeta( (int) $botUser->id, 'addr_city', '' );
        $address  = [
            'first_name' => $botUser->first_name ?? '',
            'last_name'  => $botUser->last_name ?? '',
            'phone'      => $botUser->phone ?? '',
            'address'    => $botUser->address ?? '',
            'city'       => $addrCity,
            'province'   => '',
            'postcode'   => '',
        ];
        $order = $cart->createWcOrder( $botUser->id, $address, (int) ( $botUser->user_id ?? 0 ) );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ خطا در ایجاد سفارش. لطفاً دوباره تلاش کنید.' );
            return;
        }

        $order->set_payment_method( $gatewayId );
        $order->calculate_totals();
        $order->save();

        // Generate payment link
        $payUrl = PaymentGatewayBridge::createPaymentLink( $order->get_id(), $gatewayId );

        if ( ! $payUrl ) {
            $bot->sendMessage( $chatId, '❌ خطا در ایجاد لینک پرداخت.' );
            return;
        }

        $vars = MessageTemplateEngine::orderVars( $order );
        $text = MessageTemplateEngine::render( 'rf_msg_order_created', $vars );
        if ( empty( $text ) ) {
            $text = "✅ سفارش شما با شماره #" . PersianDate::toPersianDigits( (string) $order->get_id() ) . " ثبت شد.\n";
            $text .= "💰 مبلغ: " . PriceFormatter::format( $order->get_total() ) . "\n";
        }
        $text .= "\n\n💳 برای پرداخت روی دکمه زیر کلیک کنید:";

        $rows = [
            [
                KeyboardBuilder::urlButton( '💳 پرداخت آنلاین', $payUrl ),
            ],
            [
                KeyboardBuilder::inlineButton( '❌ انصراف', "cancel_order:{$order->get_id()}" ),
            ],
        ];

        // Clear cart
        $cart->clearCart( $botUserId );

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function payCOD( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $cart  = new CartSync();
        $items = $cart->getItems( $botUserId );

        if ( empty( $items ) ) {
            $bot->sendMessage( $chatId, '🛒 سبد خرید خالی است.' );
            return;
        }

        $bot->sendMessage( $chatId, '⏳ در حال ایجاد سفارش...' );

        $botUser = $ctx['bot_user'];
        $addrCity = (string) $cart->getMeta( (int) $botUser->id, 'addr_city', '' );
        $address  = [
            'first_name' => $botUser->first_name ?? '',
            'last_name'  => $botUser->last_name ?? '',
            'phone'      => $botUser->phone ?? '',
            'address'    => $botUser->address ?? '',
            'city'       => $addrCity,
            'province'   => '',
            'postcode'   => '',
        ];
        $order = $cart->createWcOrder( $botUser->id, $address, (int) ( $botUser->user_id ?? 0 ) );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ خطا در ایجاد سفارش. لطفاً دوباره تلاش کنید.' );
            return;
        }

        $order->set_payment_method( 'cod' );
        $order->set_payment_method_title( 'پرداخت در محل' );
        $order->calculate_totals();
        $order->save();

        $cart->clearCart( $botUserId );

        $vars = MessageTemplateEngine::orderVars( $order );
        $text = MessageTemplateEngine::render( 'rf_msg_order_created', $vars );
        if ( empty( $text ) ) {
            $text = "✅ سفارش شما با شماره #" . PersianDate::toPersianDigits( (string) $order->get_id() ) . " ثبت شد.\n";
            $text .= "💰 مبلغ: " . PriceFormatter::format( $order->get_total() ) . "\n";
            $text .= "📦 روش پرداخت: پرداخت در محل\n";
        }

        $rows = [
            [ KeyboardBuilder::inlineButton( '📦 پیگیری سفارش', "order:{$order->get_id()}" ) ],
        ];

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );

        // Fire new order action (after order is fully built)
        do_action( 'rf_new_order', $order->get_id() );
    }

    public function handlePaymentCallback( array $ctx ): void {
        $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        $status  = $ctx['parts'][2] ?? 'failed';
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            return;
        }

        if ( $status === 'success' ) {
            $vars = MessageTemplateEngine::orderVars( $order );
            $text = MessageTemplateEngine::render( 'rf_msg_payment_success', $vars );
            $rows = [
                [ KeyboardBuilder::inlineButton( '📦 جزئیات سفارش', "order:{$orderId}" ) ],
            ];
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );

            do_action( 'rf_payment_complete', $order );
        } else {
            $text = "❌ پرداخت ناموفق بود.\n\nشماره سفارش: " . PersianDate::toPersianDigits( (string) $orderId );
            $payUrl = PaymentGatewayBridge::createPaymentLink( $order->get_id(), $order->get_payment_method() );
            $rows = [];
            if ( $payUrl ) {
                $rows[] = [ KeyboardBuilder::urlButton( '🔄 تلاش مجدد', $payUrl ) ];
            }
            $rows[] = [ KeyboardBuilder::inlineButton( '❌ انصراف', "cancel_order:{$orderId}" ) ];
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function cancelOrder( array $ctx ): void {
        $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            return;
        }

        // Only cancel if pending or on-hold
        $status = $order->get_status();
        if ( ! in_array( $status, [ 'pending', 'on-hold' ], true ) ) {
            $bot->sendMessage( $chatId, '❌ امکان لغو این سفارش وجود ندارد.' );
            return;
        }

        $order->update_status( 'cancelled', 'لغو توسط کاربر از طریق ربات' );

        $bot->sendMessage(
            $chatId,
            "✅ سفارش شماره " . PersianDate::toPersianDigits( (string) $orderId ) . " لغو شد."
        );
    }
}
