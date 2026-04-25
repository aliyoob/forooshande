<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class AdminBotHandler {

    public function handleCallback( array $ctx ): void {
        if ( ! $this->checkAdmin( $ctx ) ) return;

        $action = $ctx['parts'][0] ?? '';

        match ( $action ) {
            'admin_panel', 'admin' => $this->showPanel( $ctx ),
            'admin_stats'          => $this->showStats( $ctx ),
            'admin_orders'         => $this->showRecentOrders( $ctx ),
            'admin_order'          => $this->showAdminOrder( $ctx ),
            'admin_status'         => $this->changeOrderStatus( $ctx ),
            'admin_tracking'       => $this->promptTracking( $ctx ),
            'admin_tracking_co'    => $this->selectTrackingCompany( $ctx ),
            'admin_users'          => $this->showUsers( $ctx ),
            'admin_bot_stats'      => $this->showBotStats( $ctx ),
            'admin_top_products'   => $this->showTopProducts( $ctx ),
            'admin_broadcast'      => ( new BroadcastHandler() )->promptBroadcast( $ctx ),
            default                => $this->showPanel( $ctx ),
        };
    }

    private function checkAdmin( array $ctx ): bool {
        if ( (bool) ( $ctx['bot_user']->is_admin ?? false ) ) {
            return true;
        }
        $ctx['bot']->sendMessage( $ctx['chat_id'], '❌ شما دسترسی ادمین ندارید.' );
        return false;
    }

    public function showPanel( array $ctx ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        if ( ! $this->checkAdmin( $ctx ) ) return;

        $text = "⚙️ پنل مدیریت:\n━━━━━━━━━━━━━━\n";
        $text .= $this->getQuickStats();

        $rows = [
            [
                KeyboardBuilder::inlineButton( '📊 آمار فروش', 'admin_stats' ),
                KeyboardBuilder::inlineButton( '📦 سفارشات اخیر', 'admin_orders' ),
            ],
            [
                KeyboardBuilder::inlineButton( '👥 کاربران', 'admin_users' ),
                KeyboardBuilder::inlineButton( '📢 ارسال همگانی', 'admin_broadcast' ),
            ],
            [
                KeyboardBuilder::inlineButton( '📊 آمار ربات', 'admin_bot_stats' ),
                KeyboardBuilder::inlineButton( '📦 محصولات پرفروش', 'admin_top_products' ),
            ],
            KeyboardBuilder::backButton(),
        ];

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function showStats( array $ctx ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        // Today stats (use local timezone)
        $todayLocal = current_time( 'Y-m-d' ) . ' 00:00:00';
        $todayStart = get_gmt_from_date( $todayLocal );
        $todayArgs  = [
            'status'       => [ 'processing', 'completed', 'on-hold' ],
            'date_created' => '>=' . $todayStart,
            'return'       => 'ids',
            'limit'        => -1,
        ];
        $todayOrders = wc_get_orders( $todayArgs );
        $todayTotal  = 0;
        foreach ( $todayOrders as $oid ) {
            $o = wc_get_order( $oid );
            if ( $o ) $todayTotal += (float) $o->get_total();
        }

        // This week
        $weekLocal  = current_time( 'Y-m-d', false );
        $weekDay    = (int) gmdate( 'N', strtotime( $weekLocal ) );
        $weekStartLocal = gmdate( 'Y-m-d', strtotime( $weekLocal . ' -' . ( $weekDay - 1 ) . ' days' ) ) . ' 00:00:00';
        $weekStart  = get_gmt_from_date( $weekStartLocal );
        $weekArgs   = $todayArgs;
        $weekArgs['date_created'] = '>=' . $weekStart;
        $weekOrders = wc_get_orders( $weekArgs );
        $weekTotal  = 0;
        foreach ( $weekOrders as $oid ) {
            $o = wc_get_order( $oid );
            if ( $o ) $weekTotal += (float) $o->get_total();
        }

        // This month
        $monthLocal = current_time( 'Y-m' ) . '-01 00:00:00';
        $monthStart = get_gmt_from_date( $monthLocal );
        $monthArgs  = $todayArgs;
        $monthArgs['date_created'] = '>=' . $monthStart;
        $monthOrders = wc_get_orders( $monthArgs );
        $monthTotal  = 0;
        foreach ( $monthOrders as $oid ) {
            $o = wc_get_order( $oid );
            if ( $o ) $monthTotal += (float) $o->get_total();
        }

        // Pending orders
        $pendingCount = count( wc_get_orders( [ 'status' => [ 'pending', 'on-hold' ], 'return' => 'ids', 'limit' => -1 ] ) );

        $text  = "📊 آمار فروش:\n━━━━━━━━━━━━━━\n\n";
        $text .= "📅 امروز:\n";
        $text .= "   سفارش: " . PersianDate::toPersianDigits( (string) count( $todayOrders ) ) . "\n";
        $text .= "   فروش: " . PriceFormatter::format( (string) $todayTotal ) . "\n\n";
        $text .= "📅 این هفته:\n";
        $text .= "   سفارش: " . PersianDate::toPersianDigits( (string) count( $weekOrders ) ) . "\n";
        $text .= "   فروش: " . PriceFormatter::format( (string) $weekTotal ) . "\n\n";
        $text .= "📅 این ماه:\n";
        $text .= "   سفارش: " . PersianDate::toPersianDigits( (string) count( $monthOrders ) ) . "\n";
        $text .= "   فروش: " . PriceFormatter::format( (string) $monthTotal ) . "\n\n";
        $text .= "⏳ در انتظار پرداخت: " . PersianDate::toPersianDigits( (string) $pendingCount ) . "\n";

        $rows = [
            [ KeyboardBuilder::inlineButton( '🔄 به‌روزرسانی', 'admin_stats' ) ],
            KeyboardBuilder::backButton( 'admin_panel' ),
        ];

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function showRecentOrders( array $ctx ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $orders = wc_get_orders( [
            'limit'   => 10,
            'orderby' => 'date',
            'order'   => 'DESC',
        ] );

        if ( empty( $orders ) ) {
            $bot->sendMessage( $chatId, '📦 سفارشی وجود ندارد.' );
            return;
        }

        $text = "📦 سفارشات اخیر:\n━━━━━━━━━━━━━━\n\n";
        $rows = [];

        foreach ( $orders as $order ) {
            $num    = PersianDate::toPersianDigits( (string) $order->get_id() );
            $total  = PriceFormatter::format( $order->get_total() );
            $status = $this->getStatusEmoji( $order->get_status() );
            $name   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

            $text .= "{$status} #{$num} - {$name}\n";
            $text .= "   💰 {$total}\n\n";

            $rows[] = [
                KeyboardBuilder::inlineButton(
                    "{$status} #{$num}",
                    "admin_order:{$order->get_id()}"
                ),
            ];
        }

        $rows[] = KeyboardBuilder::backButton( 'admin_panel' );

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function showAdminOrder( array $ctx ): void {
        $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            return;
        }

        $num  = PersianDate::toPersianDigits( (string) $orderId );
        $text = "📦 سفارش #{$num}\n━━━━━━━━━━━━━━\n\n";
        $text .= "👤 " . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "\n";
        $text .= "📱 " . ( $order->get_billing_phone() ?: '-' ) . "\n";
        $text .= "📍 " . $order->get_formatted_billing_address() . "\n\n";

        $text .= "📦 اقلام:\n";
        foreach ( $order->get_items() as $item ) {
            $qty   = PersianDate::toPersianDigits( (string) $item->get_quantity() );
            $total = PriceFormatter::format( $item->get_total() );
            $text .= "• {$item->get_name()} × {$qty} = {$total}\n";
        }

        $text .= "\n💰 جمع: " . PriceFormatter::format( $order->get_total() ) . "\n";
        $text .= "📊 وضعیت: " . $this->getStatusLabel( $order->get_status() ) . "\n";

        $note = $order->get_customer_note();
        if ( $note ) {
            $text .= "📝 یادداشت: {$note}\n";
        }

        $rows = [
            [
                KeyboardBuilder::inlineButton( '✅ تکمیل', "admin_status:{$orderId}:completed" ),
                KeyboardBuilder::inlineButton( '🔄 پردازش', "admin_status:{$orderId}:processing" ),
            ],
            [
                KeyboardBuilder::inlineButton( '❌ لغو', "admin_status:{$orderId}:cancelled" ),
                KeyboardBuilder::inlineButton( '📮 کد رهگیری', "admin_tracking:{$orderId}" ),
            ],
            KeyboardBuilder::backButton( 'admin_orders' ),
        ];

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function changeOrderStatus( array $ctx ): void {
        $orderId  = (int) ( $ctx['parts'][1] ?? 0 );
        $newStatus = $ctx['parts'][2] ?? '';
        $bot       = $ctx['bot'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $ctx['chat_id'], '❌ سفارش یافت نشد.' );
            return;
        }

        $allowed = [ 'processing', 'completed', 'cancelled', 'on-hold' ];
        if ( ! in_array( $newStatus, $allowed, true ) ) {
            $bot->sendMessage( $ctx['chat_id'], '❌ وضعیت نامعتبر.' );
            return;
        }

        $order->update_status( $newStatus, 'تغییر وضعیت توسط ادمین از طریق ربات' );

        $label = $this->getStatusLabel( $newStatus );
        $bot->sendMessage( $ctx['chat_id'], "✅ وضعیت سفارش تغییر کرد: {$label}" );

        // WC's update_status already fires woocommerce_order_status_changed → rf_order_status_changed
        $this->showAdminOrder( $ctx );
    }

    public function promptTracking( array $ctx ): void {
        $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $companies = [ 'پست پیشتاز', 'پست سفارشی', 'تیپاکس', 'اسنپ باکس', 'الو پیک', 'پیک موتوری', 'باربری', 'سایر' ];
        $rows = [];
        $row  = [];
        foreach ( $companies as $c ) {
            $row[] = KeyboardBuilder::inlineButton( $c, "admin_tracking_co:{$orderId}:" . urlencode( $c ) );
            if ( count( $row ) >= 2 ) {
                $rows[] = $row;
                $row    = [];
            }
        }
        if ( ! empty( $row ) ) $rows[] = $row;
        $rows[] = KeyboardBuilder::backButton( "admin_order:{$orderId}" );

        $bot->sendMessage( $chatId, "🚚 شرکت ارسال کننده را انتخاب کنید:", KeyboardBuilder::inline( $rows ) );
    }

    public function selectTrackingCompany( array $ctx ): void {
        $orderId = (int) ( $ctx['parts'][1] ?? 0 );
        $company = urldecode( $ctx['parts'][2] ?? '' );
        $bot     = $ctx['bot'];
        $chatId  = $ctx['chat_id'];

        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_tracking', [
            'order_id' => $orderId,
            'company'  => $company,
        ] );
        $bot->sendMessage( $chatId, "📮 شرکت: {$company}\n\nلطفاً کد رهگیری را وارد کنید:" );
    }

    public function receiveTracking( array $ctx, string $text ): void {
        $stateData = $ctx['state']->getStateData( $ctx['bot_user'] );
        $orderId   = $stateData['order_id'] ?? 0;
        $company   = $stateData['company'] ?? '';
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];

        $order = wc_get_order( $orderId );
        if ( ! $order ) {
            $bot->sendMessage( $chatId, '❌ سفارش یافت نشد.' );
            $ctx['state']->clearState( $ctx['bot_user']->id );
            return;
        }

        $code = trim( $text );

        $order->update_meta_data( '_rf_tracking_code', sanitize_text_field( $code ) );
        $order->update_meta_data( '_rf_shipping_company', sanitize_text_field( $company ) );
        $order->save();

        $ctx['state']->clearState( $ctx['bot_user']->id );
        $bot->sendMessage( $chatId, "✅ کد رهگیری ثبت شد.\n🚚 {$company}\n📮 {$code}" );

        do_action( 'rf_tracking_code_added', $orderId, $code, $company );
    }

    public function showUsers( array $ctx ): void {
        global $wpdb;
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rf_bot_users" );
        $active = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rf_bot_users WHERE last_activity > %s",
            gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
        ) );
        $withPhone = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rf_bot_users WHERE phone IS NOT NULL AND phone != ''"
        );

        $text  = "👥 آمار کاربران:\n━━━━━━━━━━━━━━\n\n";
        $text .= "👤 کل: " . PersianDate::toPersianDigits( (string) $total ) . "\n";
        $text .= "🟢 فعال (۷ روز): " . PersianDate::toPersianDigits( (string) $active ) . "\n";
        $text .= "📱 با شماره تلفن: " . PersianDate::toPersianDigits( (string) $withPhone ) . "\n";

        $rows = [
            [ KeyboardBuilder::inlineButton( '🔄 به‌روزرسانی', 'admin_users' ) ],
            KeyboardBuilder::backButton( 'admin_panel' ),
        ];

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function showBotStats( array $ctx ): void {
        global $wpdb;
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $totalProducts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" );
        $totalWishlists = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rf_wishlists" );
        $totalAlerts    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rf_stock_alerts WHERE notified = 0" );

        $text  = "📊 آمار ربات:\n━━━━━━━━━━━━━━\n\n";
        $text .= "📦 محصولات فعال: " . PersianDate::toPersianDigits( (string) $totalProducts ) . "\n";
        $text .= "❤️ علاقه‌مندی‌ها: " . PersianDate::toPersianDigits( (string) $totalWishlists ) . "\n";
        $text .= "🔔 هشدار موجودی: " . PersianDate::toPersianDigits( (string) $totalAlerts ) . "\n";

        $rows = [
            [ KeyboardBuilder::inlineButton( '🔄 به‌روزرسانی', 'admin_bot_stats' ) ],
            KeyboardBuilder::backButton( 'admin_panel' ),
        ];

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function showTopProducts( array $ctx ): void {
        global $wpdb;
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $results = $wpdb->get_results( "
            SELECT oi.order_item_name, SUM(oim.meta_value) as qty
            FROM {$wpdb->prefix}woocommerce_order_items oi
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE oi.order_item_type = 'line_item' AND oim.meta_key = '_qty'
            GROUP BY oi.order_item_name
            ORDER BY qty DESC
            LIMIT 10
        " );

        if ( empty( $results ) ) {
            $bot->sendMessage( $chatId, '📦 هنوز فروشی ثبت نشده.' );
            return;
        }

        $text = "📦 محصولات پرفروش:\n━━━━━━━━━━━━━━\n\n";
        $i    = 1;
        foreach ( $results as $row ) {
            $num = PersianDate::toPersianDigits( (string) $i );
            $qty = PersianDate::toPersianDigits( (string) $row->qty );
            $text .= "{$num}. {$row->order_item_name} - {$qty} عدد\n";
            $i++;
        }

        $rows = [
            KeyboardBuilder::backButton( 'admin_panel' ),
        ];

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    private function getQuickStats(): string {
        $todayLocal = current_time( 'Y-m-d' ) . ' 00:00:00';
        $todayStart = get_gmt_from_date( $todayLocal );
        $todayOrders = count( wc_get_orders( [
            'status'       => [ 'processing', 'completed', 'on-hold' ],
            'date_created' => '>=' . $todayStart,
            'return'       => 'ids',
            'limit'        => -1,
        ] ) );

        $pendingOrders = count( wc_get_orders( [
            'status' => [ 'pending', 'on-hold' ],
            'return' => 'ids',
            'limit'  => -1,
        ] ) );

        return "📊 سفارش امروز: " . PersianDate::toPersianDigits( (string) $todayOrders )
             . " | ⏳ در انتظار: " . PersianDate::toPersianDigits( (string) $pendingOrders ) . "\n\n";
    }

    private function getStatusEmoji( string $status ): string {
        return match ( $status ) {
            'pending'    => '⏳',
            'processing' => '🔄',
            'on-hold'    => '⏸',
            'completed'  => '✅',
            'cancelled'  => '❌',
            'refunded'   => '↩️',
            'failed'     => '⛔',
            default      => '📦',
        };
    }

    private function getStatusLabel( string $status ): string {
        return match ( $status ) {
            'pending'    => 'در انتظار پرداخت',
            'processing' => 'در حال پردازش',
            'on-hold'    => 'در انتظار',
            'completed'  => 'تکمیل شده',
            'cancelled'  => 'لغو شده',
            'refunded'   => 'مسترد شده',
            'failed'     => 'ناموفق',
            default      => $status,
        };
    }
}
