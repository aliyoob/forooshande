<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\WooCommerce\{CartSync, StockManager, CouponManager};
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class CartHandler {

    private CartSync $cart;

    public function __construct() {
        $this->cart = new CartSync();
    }

    public function addToCart( array $ctx ): void {
        $productId   = (int) ( $ctx['parts'][1] ?? 0 );
        $variationId = (int) ( $ctx['parts'][2] ?? 0 );
        $qty         = (int) ( $ctx['parts'][3] ?? 1 );
        $bot         = $ctx['bot'];
        $chatId      = $ctx['chat_id'];
        $botUserId   = $ctx['bot_user']->id;

        $product = wc_get_product( $variationId ?: $productId );
        if ( ! $product ) {
            $bot->sendMessage( $chatId, '❌ محصول یافت نشد.' );
            return;
        }

        if ( ! $product->is_in_stock() ) {
            $bot->sendMessage( $chatId, '❌ محصول ناموجود است.' );
            return;
        }

        // For variable products without variation, show variation selector
        $mainProduct = wc_get_product( $productId );
        if ( $mainProduct && $mainProduct->is_type( 'variable' ) && ! $variationId ) {
            $handler = new ProductHandler();
            $handler->handleVariation( $ctx );
            return;
        }

        // Check if needs quantity input
        if ( $qty <= 0 ) {
            $ctx['state']->setState( $botUserId, 'awaiting_quantity', [
                'product_id'   => $productId,
                'variation_id' => $variationId,
            ] );
            $bot->sendMessage( $chatId, '🔢 لطفاً تعداد مورد نظر را وارد کنید:' );
            return;
        }

        $this->cart->addItem( $botUserId, $productId, $variationId, $qty );
        $bot->sendMessage( $chatId, '✅ به سبد خرید اضافه شد!' );
    }

    public function showCart( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $items = $this->cart->getItems( $botUserId );

        if ( empty( $items ) ) {
            $bot->sendMessage( $chatId, '🛒 سبد خرید شما خالی است.' );
            return;
        }

        $text       = "🛒 سبد خرید شما:\n━━━━━━━━━━━━━━\n";
        $rows       = [];
        $total      = 0;
        $i          = 1;
        $hasInvalid = false;

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->variation_id ?: $item->product_id );
            if ( ! $product ) {
                $hasInvalid = true;
                continue;
            }

            $price    = (float) $product->get_price() * $item->quantity;
            $total   += $price;
            $inStock  = $product->is_in_stock();

            $text .= PersianDate::toPersianDigits( (string) $i ) . "️⃣ "
                   . $product->get_name() . "\n"
                   . "   💰 " . PriceFormatter::format( $product->get_price() )
                   . " × " . PersianDate::toPersianDigits( (string) $item->quantity )
                   . " = " . PriceFormatter::format( (string) $price );

            if ( ! $inStock ) {
                $text .= " ❌ ناموجود";
                $hasInvalid = true;
            }
            $text .= "\n";

            $rows[] = [
                KeyboardBuilder::inlineButton( "➖", "cart_minus:{$item->id}" ),
                KeyboardBuilder::inlineButton(
                    PersianDate::toPersianDigits( (string) $item->quantity ),
                    "cart_qty:{$item->id}"
                ),
                KeyboardBuilder::inlineButton( "➕", "cart_plus:{$item->id}" ),
                KeyboardBuilder::inlineButton( "🗑", "cart_rm:{$item->id}" ),
            ];
            $i++;
        }

        $text .= "\n━━━━━━━━━━━━━━\n";
        $text .= "💰 جمع کل: " . PriceFormatter::format( (string) $total ) . "\n";

        if ( $hasInvalid ) {
            $text .= "\n⚠️ برخی محصولات ناموجود هستند و در ادامه خرید حذف خواهند شد.";
        }

        $actionRow = [];
        $actionRow[] = KeyboardBuilder::inlineButton( '🗑 خالی کردن سبد', 'cart_clear' );
        if ( ! $hasInvalid || count( $items ) > 0 ) {
            $actionRow[] = KeyboardBuilder::inlineButton( '💳 تسویه حساب', 'checkout' );
        }
        $rows[] = $actionRow;

        $rows[] = [
            KeyboardBuilder::inlineButton( '🏷 کد تخفیف', 'coupon_input' ),
            KeyboardBuilder::inlineButton( '🔄 به‌روزرسانی', 'cart_refresh' ),
        ];
        $rows[] = KeyboardBuilder::backButton();

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function increaseQuantity( array $ctx ): void {
        $itemId  = (int) ( $ctx['parts'][1] ?? 0 );
        $current = $this->cart->getItemQuantity( $itemId );
        $this->cart->updateQuantity( $itemId, $current + 1 );
        $this->showCart( $ctx );
    }

    public function decreaseQuantity( array $ctx ): void {
        $itemId  = (int) ( $ctx['parts'][1] ?? 0 );
        $current = $this->cart->getItemQuantity( $itemId );
        if ( $current <= 1 ) {
            $this->removeItem( $ctx );
            return;
        }
        $this->cart->updateQuantity( $itemId, $current - 1 );
        $this->showCart( $ctx );
    }

    public function removeItem( array $ctx ): void {
        $itemId = (int) ( $ctx['parts'][1] ?? 0 );
        $this->cart->removeItem( $itemId );
        $this->showCart( $ctx );
    }

    public function clearCart( array $ctx ): void {
        $this->cart->clearCart( $ctx['bot_user']->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '🗑 سبد خرید خالی شد.' );
    }

    public function refreshCart( array $ctx ): void {
        $this->cart->validateCart( $ctx['bot_user']->id );
        $this->showCart( $ctx );
    }

    public function promptCoupon( array $ctx ): void {
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_coupon' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '🏷 لطفاً کد تخفیف خود را وارد کنید:' );
    }

    public function receiveCoupon( array $ctx, string $code ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $botUserId = $ctx['bot_user']->id;

        $code   = trim( $code );
        $coupon = new \WC_Coupon( $code );

        if ( ! $coupon->get_id() ) {
            $ctx['state']->clearState( $botUserId );
            $bot->sendMessage( $chatId, '❌ کد تخفیف نامعتبر است.' );
            return;
        }

        if ( ! $coupon->is_valid() ) {
            $ctx['state']->clearState( $botUserId );
            $bot->sendMessage( $chatId, '❌ کد تخفیف منقضی شده یا شرایط استفاده را ندارید.' );
            return;
        }

        $ctx['state']->clearState( $botUserId );
        $this->cart->setCoupon( $botUserId, $code );
        $bot->sendMessage( $chatId, "✅ کد تخفیف «{$code}» ذخیره شد و هنگام تسویه حساب اعمال خواهد شد." );
    }

    public function buyDirect( array $ctx ): void {
        $productId   = (int) ( $ctx['parts'][1] ?? 0 );
        $variationId = (int) ( $ctx['parts'][2] ?? 0 );
        $botUserId   = $ctx['bot_user']->id;

        // Clear cart and add this item only
        $this->cart->clearCart( $botUserId );
        $this->cart->addItem( $botUserId, $productId, $variationId, 1 );

        // Redirect to checkout
        $checkout = new CheckoutHandler();
        $checkout->startCheckout( $ctx );
    }

    public function setQuantity( array $ctx ): void {
        $itemId = (int) ( $ctx['parts'][1] ?? 0 );
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_cart_qty', [ 'item_id' => $itemId ] );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '🔢 تعداد جدید را وارد کنید:' );
    }

    public function receiveCartQuantity( array $ctx, string $text ): void {
        $qty = (int) $text;
        if ( $qty < 1 ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '❌ تعداد باید حداقل ۱ باشد.' );
            return;
        }

        $stateData = $ctx['state']->getStateData( $ctx['bot_user'] );
        $itemId    = $stateData['item_id'] ?? 0;

        if ( $itemId ) {
            $this->cart->setItemQuantity( $itemId, $qty );
        }

        $ctx['state']->clearState( $ctx['bot_user']->id );
        $this->showCart( $ctx );
    }
}
