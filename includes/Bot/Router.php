<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\Handlers\{
    StartHandler, ContactHandler, MenuHandler, ProductHandler,
    CategoryHandler, CartHandler, CheckoutHandler, PaymentHandler,
    OrderHandler, SearchHandler, AdminBotHandler, BroadcastHandler,
    SupportHandler
};

class Router {

    private BotCore $bot;
    private StateManager $state;

    public function __construct() {
        $this->bot   = new BotCore();
        $this->state = new StateManager();
    }

    public function dispatch( array $update ): void {
        // Callback query
        if ( ! empty( $update['callback_query'] ) ) {
            $this->handleCallback( $update['callback_query'] );
            return;
        }

        // Message
        if ( ! empty( $update['message'] ) ) {
            $this->handleMessage( $update['message'] );
            return;
        }
    }

    private function handleCallback( array $callback ): void {
        $chatId = $callback['message']['chat']['id'] ?? 0;
        $data   = $callback['data'] ?? '';

        $botUser = $this->state->getOrCreateUser( $chatId, $callback['from'] ?? [] );

        // Parse callback data
        $parts  = explode( ':', $data );
        $action = $parts[0] ?? '';

        $ctx = [
            'bot'       => $this->bot,
            'state'     => $this->state,
            'bot_user'  => $botUser,
            'chat_id'   => $chatId,
            'callback'  => $callback,
            'message_id'=> $callback['message']['message_id'] ?? 0,
            'data'      => $data,
            'parts'     => $parts,
            'is_admin'  => $this->isAdmin( $botUser ),
        ];

        // Dismiss the loading spinner immediately without blocking on the API response.
        $this->bot->sendAsync( 'answerCallbackQuery', [
            'callback_query_id' => $callback['id'] ?? '',
            'text'              => '',
        ] );

        match ( $action ) {
            'cat'           => ( new CategoryHandler() )->handleCallback( $ctx ),
            'product'       => ( new ProductHandler() )->handleCallback( $ctx ),
            'variation'     => ( new ProductHandler() )->handleVariation( $ctx ),
            'add_cart'      => ( new CartHandler() )->addToCart( $ctx ),
            'cart_plus'     => ( new CartHandler() )->increaseQuantity( $ctx ),
            'cart_minus'    => ( new CartHandler() )->decreaseQuantity( $ctx ),
            'cart_remove'   => ( new CartHandler() )->removeItem( $ctx ),
            'cart_rm'       => ( new CartHandler() )->removeItem( $ctx ),
            'cart_clear'    => ( new CartHandler() )->clearCart( $ctx ),
            'cart_refresh'  => ( new CartHandler() )->refreshCart( $ctx ),
            'cart_qty'      => ( new CartHandler() )->setQuantity( $ctx ),
            'coupon_input'  => ( new CartHandler() )->promptCoupon( $ctx ),
            'checkout'      => ( new CheckoutHandler() )->startCheckout( $ctx ),
            'edit_checkout_address' => ( new CheckoutHandler() )->editAddress( $ctx ),
            'order_note'    => ( new CheckoutHandler() )->promptOrderNote( $ctx ),
            'shipping'      => ( new CheckoutHandler() )->selectShipping( $ctx ),
            'pay_online'    => ( new PaymentHandler() )->payOnline( $ctx ),
            'pay_cod'       => ( new PaymentHandler() )->payCOD( $ctx ),
            'cancel_order'  => ( new PaymentHandler() )->cancelOrder( $ctx ),

            'order'         => ( new OrderHandler() )->showOrderDetail( $ctx ),
            'orders_page'   => ( new OrderHandler() )->handleOrdersPage( $ctx ),
            'reorder'       => ( new OrderHandler() )->reorder( $ctx ),
            'wishlist_add'  => ( new ProductHandler() )->addToWishlist( $ctx ),
            'wishlist_rm'   => ( new ProductHandler() )->removeFromWishlist( $ctx ),
            'stock_alert'   => ( new ProductHandler() )->addStockAlert( $ctx ),
            'share'         => ( new ProductHandler() )->shareProduct( $ctx ),
            'buy_direct'    => ( new CartHandler() )->buyDirect( $ctx ),
            'page'          => ( new ProductHandler() )->handlePagination( $ctx ),
            'support_reply' => ( new SupportHandler() )->promptReply( $ctx ),
            'edit_name'     => $this->promptEditName( $ctx ),
            'edit_address'  => $this->promptEditAddress( $ctx ),
            'back_menu'     => ( new MenuHandler() )->showMainMenu( $ctx ),
            'back_shop'     => ( new CategoryHandler() )->showCategories( $ctx ),
            default         => str_starts_with( $action, 'admin' )
                ? ( new AdminBotHandler() )->handleCallback( $ctx )
                : ( str_starts_with( $action, 'broadcast' )
                    ? ( new BroadcastHandler() )->handleCallback( $ctx )
                    : null ),
        };
    }

    private function handleMessage( array $message ): void {
        $chatId = $message['chat']['id'] ?? 0;
        $text   = $message['text'] ?? '';

        // Contact shared
        if ( ! empty( $message['contact'] ) ) {
            $botUser = $this->state->getOrCreateUser( $chatId, $message['from'] ?? [] );
            $ctx     = $this->makeCtx( $chatId, $message, $botUser );
            ( new ContactHandler() )->handle( $ctx, $message['contact'] );
            return;
        }

        $botUser = $this->state->getOrCreateUser( $chatId, $message['from'] ?? [] );
        $ctx     = $this->makeCtx( $chatId, $message, $botUser );

        // Check if user needs to share phone first
        if ( empty( $botUser->phone ) && $text !== '/start' && ! str_starts_with( $text, '/start ' ) ) {
            ( new StartHandler() )->requestPhone( $ctx );
            return;
        }

        // Check state-based routing first
        $state = $botUser->state ?? 'idle';
        if ( $state !== 'idle' ) {
            $this->handleState( $ctx, $state, $text );
            return;
        }

        // Menu buttons (reply keyboard)
        $menuMap = [
            get_option( 'rf_menu_shop', '🛍 فروشگاه' )       => [ CategoryHandler::class, 'showCategories' ],
            get_option( 'rf_menu_search', '🔍 جستجوی محصول' ) => [ SearchHandler::class, 'promptSearch' ],
            get_option( 'rf_menu_categories', '📂 دسته‌بندی‌ها' ) => [ CategoryHandler::class, 'showCategories' ],
            get_option( 'rf_menu_cart', '🛒 سبد خرید' )       => [ CartHandler::class, 'showCart' ],
            get_option( 'rf_menu_orders', '📦 سفارشات من' )    => [ OrderHandler::class, 'showOrders' ],
            get_option( 'rf_menu_account', '👤 حساب من' )      => [ MenuHandler::class, 'showAccount' ],
            get_option( 'rf_menu_support', '📞 پشتیبانی' )     => [ SupportHandler::class, 'startSupport' ],
            get_option( 'rf_menu_about', 'ℹ️ درباره ما' )      => [ MenuHandler::class, 'showAbout' ],
            get_option( 'rf_menu_wishlist', '❤️ علاقه‌مندی‌ها' ) => [ ProductHandler::class, 'showWishlist' ],
            get_option( 'rf_menu_offers', '🔥 حراجی‌ها' )      => [ ProductHandler::class, 'showOnSale' ],
            get_option( 'rf_menu_featured', '⭐ محصولات ویژه' ) => [ ProductHandler::class, 'showFeatured' ],
            get_option( 'rf_menu_recent', '🕐 اخیراً دیده‌شده' )=> [ ProductHandler::class, 'showRecent' ],
        ];

        // Command handling
        if ( $text === '/start' || str_starts_with( $text, '/start ' ) ) {
            ( new StartHandler() )->handle( $ctx, $text );
            return;
        }

        // Admin commands
        if ( $this->isAdmin( $botUser ) ) {
            if ( $text === '🔧 پنل مدیریت' ) {
                ( new AdminBotHandler() )->showPanel( $ctx );
                return;
            }
        }

        // Menu button matching
        foreach ( $menuMap as $label => $handler ) {
            if ( $text === $label ) {
                ( new $handler[0]() )->{$handler[1]}( $ctx );
                return;
            }
        }

        // Default: show main menu
        ( new MenuHandler() )->showMainMenu( $ctx );
    }

    private function handleState( array $ctx, string $state, string $text ): void {
        match ( $state ) {
            'awaiting_search'           => ( new SearchHandler() )->receiveSearch( $ctx, $text ),
            'awaiting_checkout_address' => ( new CheckoutHandler() )->receiveAddress( $ctx, $text ),
            'awaiting_order_note'       => ( new CheckoutHandler() )->receiveOrderNote( $ctx, $text ),
            'awaiting_coupon'           => ( new CartHandler() )->receiveCoupon( $ctx, $text ),
            'awaiting_cart_qty'         => ( new CartHandler() )->receiveCartQuantity( $ctx, $text ),
            'awaiting_quantity'         => ( new CartHandler() )->receiveCartQuantity( $ctx, $text ),
            'waiting_edit_name'         => ( new MenuHandler() )->saveNewName( $ctx, $text ),
            'waiting_edit_addr'         => ( new MenuHandler() )->saveNewAddress( $ctx, $text ),
            'awaiting_support_message'  => ( new SupportHandler() )->receiveMessage( $ctx, $text ),
            'awaiting_support_reply'    => ( new SupportHandler() )->sendReply( $ctx, $text ),
            'awaiting_broadcast_type'   => ( new BroadcastHandler() )->receiveContent( $ctx, $text ),
            'awaiting_broadcast_content'=> ( new BroadcastHandler() )->receiveContent( $ctx, $text ),
            'admin_search_order'     => ( new MenuHandler() )->showMainMenu( $ctx ),
            'admin_product_search'   => ( new MenuHandler() )->showMainMenu( $ctx ),
            'waiting_review'         => ( new MenuHandler() )->showMainMenu( $ctx ),
            'waiting_order_search'   => ( new MenuHandler() )->showMainMenu( $ctx ),
            'waiting_quantity'       => ( new ProductHandler() )->receiveQuantity( $ctx, $text ),
            'awaiting_tracking'      => ( new AdminBotHandler() )->receiveTracking( $ctx, $text ),
            default                  => ( new MenuHandler() )->showMainMenu( $ctx ),
        };
    }

    private function makeCtx( int $chatId, array $message, object $botUser ): array {
        return [
            'bot'       => $this->bot,
            'state'     => $this->state,
            'bot_user'  => $botUser,
            'chat_id'   => $chatId,
            'message'   => $message,
            'is_admin'  => $this->isAdmin( $botUser ),
        ];
    }

    private function isAdmin( object $botUser ): bool {
        return (bool) $botUser->is_admin;
    }

    private function promptEditName( array $ctx ): void {
        $this->state->setState( $ctx['bot_user']->id, 'waiting_edit_name' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✏️ نام و نام‌خانوادگی جدید خود را وارد کنید:' );
    }

    private function promptEditAddress( array $ctx ): void {
        $this->state->setState( $ctx['bot_user']->id, 'waiting_edit_addr' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '📍 آدرس جدید خود را وارد کنید:' );
    }
}
