<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

class KeyboardBuilder {

    /**
     * Reply keyboard with contact button
     */
    public static function contactRequest( string $buttonText = '📱 ارسال شماره تلفن' ): array {
        return [
            'keyboard' => [
                [
                    [ 'text' => $buttonText, 'request_contact' => true ],
                ],
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => true,
        ];
    }

    /**
     * Main menu reply keyboard
     */
    public static function mainMenu( bool $isAdmin = false ): array {
        $buttons = [
            [ get_option( 'rf_menu_shop', '🛍 فروشگاه' ), get_option( 'rf_menu_search', '🔍 جستجوی محصول' ) ],
            [ get_option( 'rf_menu_categories', '📂 دسته‌بندی‌ها' ), get_option( 'rf_menu_cart', '🛒 سبد خرید' ) ],
            [ get_option( 'rf_menu_orders', '📦 سفارشات من' ), get_option( 'rf_menu_account', '👤 حساب من' ) ],
            [ get_option( 'rf_menu_wishlist', '❤️ علاقه‌مندی‌ها' ), get_option( 'rf_menu_offers', '🔥 حراجی‌ها' ) ],
            [ get_option( 'rf_menu_support', '📞 پشتیبانی' ), get_option( 'rf_menu_about', 'ℹ️ درباره ما' ) ],
        ];

        if ( $isAdmin ) {
            $buttons[] = [ '🔧 پنل مدیریت' ];
        }

        $keyboard = array_map( function ( $row ) {
            return array_map( fn( $text ) => [ 'text' => $text ], $row );
        }, $buttons );

        return [
            'keyboard'        => $keyboard,
            'resize_keyboard' => true,
        ];
    }

    /**
     * Inline keyboard builder
     */
    public static function inline( array $rows ): array {
        return [ 'inline_keyboard' => $rows ];
    }

    /**
     * Single inline button row
     */
    public static function inlineButton( string $text, string $callbackData ): array {
        return [ 'text' => $text, 'callback_data' => $callbackData ];
    }

    /**
     * URL button
     */
    public static function urlButton( string $text, string $url ): array {
        return [ 'text' => $text, 'url' => $url ];
    }

    /**
     * Pagination buttons
     * @param string $format sprintf-style format string with %d as page number placeholder (e.g. 'page:%d:cat:5')
     */
    public static function pagination( int $current, int $total, string $format ): array {
        $buttons = [];
        if ( $current > 1 ) {
            $buttons[] = self::inlineButton( '◀️ قبلی', sprintf( $format, $current - 1 ) );
        }
        $buttons[] = self::inlineButton( "📄 {$current}/{$total}", 'noop' );
        if ( $current < $total ) {
            $buttons[] = self::inlineButton( '▶️ بعدی', sprintf( $format, $current + 1 ) );
        }
        return $buttons;
    }

    /**
     * Back button
     */
    public static function backButton( string $callback = 'back_menu' ): array {
        return [ self::inlineButton( '🔙 بازگشت', $callback ) ];
    }

    /**
     * Product action buttons
     */
    public static function productActions( int $productId, bool $inStock = true, bool $isVariable = false ): array {
        $rows = [];

        if ( $inStock ) {
            if ( $isVariable ) {
                $rows[] = [ self::inlineButton( '🎨 انتخاب ویژگی', "variation:{$productId}" ) ];
            } else {
                $rows[] = [
                    self::inlineButton( '🛒 افزودن به سبد', "add_cart:{$productId}" ),
                    self::inlineButton( '💳 خرید مستقیم', "buy_direct:{$productId}" ),
                ];
            }
        }

        $platform     = get_option( 'rf_platform', 'telegram' );
        $bot_username = get_option( 'rf_bot_username', '' );
        $site_url     = get_permalink( $productId );

        $rows[] = [
            self::urlButton( '🌐 مشاهده در سایت', $site_url ),
        ];

        $rows[] = [
            self::inlineButton( '❤️ علاقه‌مندی', "wishlist_add:{$productId}" ),
            self::inlineButton( '📤 اشتراک‌گذاری', "share:{$productId}" ),
        ];

        if ( ! $inStock ) {
            $rows[] = [ self::inlineButton( '🔔 اطلاع بده وقتی موجود شد', "stock_alert:{$productId}" ) ];
        }

        $rows[] = self::backButton( 'back_shop' );

        return self::inline( $rows );
    }

    /**
     * Cart item quantity controls
     */
    public static function cartItemControls( int $cartItemId, int $quantity ): array {
        return [
            self::inlineButton( '➖', "cart_minus:{$cartItemId}" ),
            self::inlineButton( (string) $quantity, 'noop' ),
            self::inlineButton( '➕', "cart_plus:{$cartItemId}" ),
            self::inlineButton( '🗑', "cart_remove:{$cartItemId}" ),
        ];
    }

    /**
     * Remove reply keyboard
     */
    public static function removeKeyboard(): array {
        return [ 'remove_keyboard' => true ];
    }
}
