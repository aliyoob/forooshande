<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{KeyboardBuilder, MessageTemplateEngine};
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class OrderHandler {

    public function showOrders( array $ctx, int $page = 1 ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUser   = $ctx['bot_user'];
        $perPage   = 5;

        $wpUserId = $botUser->user_id ?? 0;
        if ( ! $wpUserId ) {
            $bot->sendMessage( $chatId, '❌ هنوز حسابی ندارید. ابتدا شماره تلفن خود را ثبت کنید.' );
            return;
        }

        $orders = wc_get_orders( [
            'customer_id' => $wpUserId,
            'limit'       => $perPage,
            'page'        => $page,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ] );

        $totalOrders = wc_get_orders( [
            'customer_id' => $wpUserId,
            'return'      => 'ids',
            'limit'       => -1,
        ] );
        $total = count( $totalOrders );
        $pages = max( 1, (int) ceil( $total / $perPage ) );

        if ( empty( $orders ) ) {
            $bot->sendMessage( $chatId, '📦 هنوز سفارشی ثبت نکرده‌اید.' );
            return;
        }

        $text = "📦 سفارش‌های شما:\n━━━━━━━━━━━━━━\n";
        $rows = [];

        foreach ( $orders as $order ) {
            $statusLabel = $this->getStatusLabel( $order->get_status() );
            $date        = PersianDate::convert( $order->get_date_created()->format( 'Y-m-d' ) );
            $total       = PriceFormatter::format( $order->get_total() );
            $num         = PersianDate::toPersianDigits( (string) $order->get_id() );

            $text .= "🔸 سفارش #{$num}\n";
            $text .= "   📅 {$date}\n";
            $text .= "   💰 {$total}\n";
            $text .= "   📊 {$statusLabel}\n\n";

            $rows[] = [
                KeyboardBuilder::inlineButton(
                    "📦 #{$num} - {$statusLabel}",
                    "order:{$order->get_id()}"
                ),
            ];
        }

        if ( $pages > 1 ) {
            $rows[] = KeyboardBuilder::pagination( $page, $pages, "orders_page:{$page}" );
        }

        $rows[] = KeyboardBuilder::backButton();

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function showOrderDetailById( array $ctx, int $orderId ): void {
        $this->showOrderDetail( $ctx, $orderId );
    }

    public function showOrderDetail( array $ctx, int $orderId = 0 ): void {
        if ( ! $orderId ) {
            $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        }
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            return;
        }

        // Verify ownership
        $wpUserId = $ctx['bot_user']->user_id ?? 0;
        if ( (int) $order->get_customer_id() !== $wpUserId && ! ( $ctx['is_admin'] ?? false ) ) {
            $bot->sendMessage( $chatId, '❌ دسترسی به این سفارش ندارید.' );
            return;
        }

        $vars = MessageTemplateEngine::orderVars( $order );
        $text = MessageTemplateEngine::render( 'rf_msg_order_detail', $vars );

        // Add items
        $text .= "\n\n📦 اقلام سفارش:\n";
        foreach ( $order->get_items() as $item ) {
            $qty     = PersianDate::toPersianDigits( (string) $item->get_quantity() );
            $total   = PriceFormatter::format( $item->get_total() );
            $text   .= "• {$item->get_name()} × {$qty} = {$total}\n";
        }

        // Tracking info
        $trackingCode    = $order->get_meta( '_rf_tracking_code' );
        $shippingCompany = $order->get_meta( '_rf_shipping_company' );
        if ( $trackingCode ) {
            $text .= "\n🚚 شرکت حمل: {$shippingCompany}\n";
            $text .= "📮 کد رهگیری: {$trackingCode}\n";
        }

        $rows   = [];
        $status = $order->get_status();

        if ( in_array( $status, [ 'pending', 'on-hold' ], true ) ) {
            $payUrl = \RobotForooshande\WooCommerce\PaymentGatewayBridge::createPaymentLink( $order->get_id(), $order->get_payment_method() );
            if ( $payUrl ) {
                $rows[] = [ KeyboardBuilder::urlButton( '💳 پرداخت', $payUrl ) ];
            }
            $rows[] = [ KeyboardBuilder::inlineButton( '❌ لغو سفارش', "cancel_order:{$orderId}" ) ];
        }

        if ( $status === 'completed' ) {
            $rows[] = [ KeyboardBuilder::inlineButton( '🔄 سفارش مجدد', "reorder:{$orderId}" ) ];
        }

        $rows[] = [ KeyboardBuilder::inlineButton( '📦 بازگشت به سفارش‌ها', 'my_orders' ) ];
        $rows[] = KeyboardBuilder::backButton();

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function handleOrdersPage( array $ctx ): void {
        $page = (int) ( $ctx['parts'][1] ?? 1 );
        $this->showOrders( $ctx, $page );
    }

    public function reorder( array $ctx ): void {
        $orderId   = (int) ( $ctx['parts'][1] ?? 0 );
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            return;
        }

        $cart  = new \RobotForooshande\WooCommerce\CartSync();
        $cart->clearCart( $botUserId );

        $added   = 0;
        $skipped = 0;
        foreach ( $order->get_items() as $item ) {
            $productId   = $item->get_product_id();
            $variationId = $item->get_variation_id();
            $product     = wc_get_product( $variationId ?: $productId );

            if ( $product && $product->is_in_stock() ) {
                $cart->addItem( $botUserId, $productId, $variationId, $item->get_quantity() );
                $added++;
            } else {
                $skipped++;
            }
        }

        if ( $added === 0 ) {
            $bot->sendMessage( $chatId, '❌ هیچ‌کدام از محصولات این سفارش موجود نیست.' );
            return;
        }

        $msg = "✅ {$added} محصول به سبد خرید اضافه شد.";
        if ( $skipped > 0 ) {
            $msg .= "\n⚠️ {$skipped} محصول ناموجود بود و اضافه نشد.";
        }
        $bot->sendMessage( $chatId, $msg );

        $cartHandler = new CartHandler();
        $cartHandler->showCart( $ctx );
    }

    private function getStatusLabel( string $status ): string {
        $map = [
            'pending'    => '⏳ در انتظار پرداخت',
            'processing' => '🔄 در حال پردازش',
            'on-hold'    => '⏸ در انتظار',
            'completed'  => '✅ تکمیل شده',
            'cancelled'  => '❌ لغو شده',
            'refunded'   => '↩️ مسترد شده',
            'failed'     => '⛔ ناموفق',
        ];
        return $map[ $status ] ?? $status;
    }
}
