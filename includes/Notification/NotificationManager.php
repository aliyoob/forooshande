<?php
namespace RobotForooshande\Notification;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{BotCore, MessageTemplateEngine};
use RobotForooshande\Auth\SMSFactory;
use RobotForooshande\User\UserSync;
use RobotForooshande\Helpers\Logger;

class NotificationManager {

    private BotCore $bot;

    public function __construct() {
        $this->bot = new BotCore();

        add_action( 'rf_order_status_changed', [ $this, 'onOrderStatusChanged' ], 10, 3 );
        add_action( 'rf_new_order', [ $this, 'onNewOrder' ] );
        add_action( 'rf_payment_complete', [ $this, 'onPaymentComplete' ] );
        add_action( 'rf_tracking_code_added', [ $this, 'onTrackingAdded' ], 10, 3 );
        add_action( 'rf_product_updated', [ $this, 'onProductUpdated' ] );
        add_action( 'rf_new_product', [ $this, 'onNewProduct' ] );
        add_action( 'rf_product_back_in_stock', [ $this, 'onProductBackInStock' ], 10, 2 );
        add_action( 'rf_product_low_stock', [ $this, 'onProductLowStock' ] );
        add_action( 'rf_post_updated', [ $this, 'onPostUpdated' ], 10, 3 );
        add_action( 'rf_new_user_registered', [ $this, 'onNewUserRegistered' ], 10, 2 );
    }

    /**
     * Notify customer on order status change
     */
    public function onOrderStatusChanged( int $orderId, string $oldStatus, string $newStatus ): void {
        $order = wc_get_order( $orderId );
        if ( ! $order ) return;

        // Notify admins when order moves to processing (payment complete)
        if ( $newStatus === 'processing' ) {
            $this->notifyAdminsNewOrder( $order );
        }

        // Notify admins on order cancellation
        if ( $newStatus === 'cancelled' && get_option( 'rf_admin_notify_cancel', '1' ) === '1' ) {
            $admin_ids = $this->parseAdminChatIds();
            if ( ! empty( $admin_ids ) ) {
                $cancelMsg = sprintf(
                    "❌ سفارش لغو شد\n📦 سفارش #%s\n👤 %s %s\n💰 %s",
                    $order->get_id(),
                    $order->get_billing_first_name(),
                    $order->get_billing_last_name(),
                    strip_tags( wc_price( $order->get_total() ) )
                );
                foreach ( $admin_ids as $adminChatId ) {
                    $this->bot->sendAsync( 'sendMessage', [
                        'chat_id'    => (int) $adminChatId,
                        'text'       => $cancelMsg,
                        'parse_mode' => 'HTML',
                    ] );
                }
            }
        }

        // Check per-status toggle from notifications settings
        $statusToggleMap = [
            'pending'    => 'rf_notify_order_pending',
            'processing' => 'rf_notify_order_processing',
            'completed'  => 'rf_notify_order_completed',
            'cancelled'  => 'rf_notify_order_cancelled',
            'refunded'   => 'rf_notify_order_refunded',
            'on-hold'    => 'rf_notify_order_onhold',
            'failed'     => 'rf_notify_order_failed',
        ];

        $toggleKey = $statusToggleMap[ $newStatus ] ?? '';
        if ( ! $toggleKey || get_option( $toggleKey, '1' ) !== '1' ) return;

        $vars = MessageTemplateEngine::orderVars( $order );
        $vars['{order_status_old}'] = wc_get_order_statuses()[ 'wc-' . $oldStatus ] ?? $oldStatus;

        // Use payment failed template for failed status, otherwise generic order status template
        $templateKey = ( $newStatus === 'failed' ) ? 'rf_msg_payment_failed' : 'rf_msg_order_status';
        $message = MessageTemplateEngine::render( $templateKey, $vars );
        if ( empty( $message ) ) return;

        // Send via bot
        $chatId = $this->getCustomerChatId( $order );
        if ( $chatId ) {
            $this->bot->sendMessage( $chatId, $message );
        }

        // Send via SMS
        $notify_method = get_option( 'rf_notify_method', 'bot' );
        if ( in_array( $notify_method, [ 'sms', 'both' ], true ) ) {
            $phone = $order->get_billing_phone();
            if ( $phone ) {
                $sms = SMSFactory::make();
                if ( $sms ) {
                    $sms->send( $phone, wp_strip_all_tags( $message ) );
                }
            }
        }

        Logger::info( 'Order status notification sent', [
            'order_id'   => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ] );
    }

    /**
     * Notify admins on new order (COD) - called from PaymentHandler after order is fully built
     */
    public function onNewOrder( int $orderId ): void {
        $order = wc_get_order( $orderId );
        if ( ! $order ) return;

        $this->notifyAdminsNewOrder( $order );
    }

    /**
     * Send admin notification for a new order
     */
    private function notifyAdminsNewOrder( \WC_Order $order ): void {
        if ( get_option( 'rf_admin_notify_new_order', '1' ) !== '1' ) return;

        $vars    = MessageTemplateEngine::orderVars( $order );
        $message = MessageTemplateEngine::render( 'rf_msg_new_order_admin', $vars );
        if ( empty( $message ) ) {
            $message  = "📦 سفارش جدید #" . $order->get_id() . "\n";
            $message .= "👤 " . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "\n";
            $message .= "📱 " . ( $order->get_billing_phone() ?: '-' ) . "\n";
            $message .= "💰 " . wc_price( $order->get_total() ) . "\n";
            foreach ( $order->get_items() as $item ) {
                $message .= "📦 " . $item->get_name() . " × " . $item->get_quantity() . "\n";
            }
        }

        // Send to admin chat IDs
        $admin_ids = $this->parseAdminChatIds();
        foreach ( $admin_ids as $adminChatId ) {
            $this->bot->sendAsync( 'sendMessage', [
                'chat_id'    => (int) $adminChatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ] );
        }

        // Send to channel
        $channel_id = get_option( 'rf_order_channel_id', '' );
        if ( ! empty( $channel_id ) ) {
            $this->bot->sendAsync( 'sendMessage', [
                'chat_id'    => $channel_id,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ] );
        }
    }

    /**
     * Notify customer on payment complete
     */
    public function onPaymentComplete( int $orderId ): void {
        $order = wc_get_order( $orderId );
        if ( ! $order ) return;

        $vars    = MessageTemplateEngine::orderVars( $order );
        $message = MessageTemplateEngine::render( 'rf_msg_payment_success', $vars );

        $chatId = $this->getCustomerChatId( $order );
        if ( $chatId && ! empty( $message ) ) {
            $this->bot->sendMessage( $chatId, $message );
        }

        // Notify admins about payment
        if ( get_option( 'rf_admin_notify_payment', '1' ) === '1' ) {
            $admin_ids = $this->parseAdminChatIds();
            if ( ! empty( $admin_ids ) ) {
                $adminMsg = sprintf(
                    "💳 پرداخت موفق\n📦 سفارش #%s\n👤 %s %s\n💰 %s",
                    $order->get_id(),
                    $order->get_billing_first_name(),
                    $order->get_billing_last_name(),
                    strip_tags( wc_price( $order->get_total() ) )
                );
                foreach ( $admin_ids as $adminChatId ) {
                    $this->bot->sendAsync( 'sendMessage', [
                        'chat_id'    => (int) $adminChatId,
                        'text'       => $adminMsg,
                        'parse_mode' => 'HTML',
                    ] );
                }
            }
        }
    }

    /**
     * Notify customer on tracking code added
     */
    public function onTrackingAdded( int $orderId, string $trackingCode, string $shippingCompany ): void {
        if ( get_option( 'rf_notify_tracking_code', '1' ) !== '1' ) return;

        $order = wc_get_order( $orderId );
        if ( ! $order ) return;

        $vars = MessageTemplateEngine::orderVars( $order );
        $vars['{tracking_code}']    = $trackingCode;
        $vars['{shipping_company}'] = $shippingCompany;

        $message = MessageTemplateEngine::render( 'rf_msg_tracking', $vars );
        if ( empty( $message ) ) return;

        $chatId = $this->getCustomerChatId( $order );
        if ( $chatId ) {
            $this->bot->sendMessage( $chatId, $message );
        }

        // SMS
        $notify_method = get_option( 'rf_tracking_notify_method', 'bot' );
        if ( in_array( $notify_method, [ 'sms', 'both' ], true ) ) {
            $phone = $order->get_billing_phone();
            if ( $phone ) {
                $sms = SMSFactory::make();
                if ( $sms ) {
                    $sms->send( $phone, wp_strip_all_tags( $message ) );
                }
            }
        }
    }

    /**
     * Notify channel on product update
     */
    public function onProductUpdated( int $productId ): void {
        if ( ! get_option( 'rf_notify_product_update', false ) ) return;

        $channel_id = get_option( 'rf_product_channel_id', '' );
        if ( empty( $channel_id ) ) return;

        $product = wc_get_product( $productId );
        if ( ! $product ) return;

        $vars    = MessageTemplateEngine::productVars( $product );
        $message = MessageTemplateEngine::render( 'rf_msg_product_update', $vars );

        $imageId = (int) $product->get_image_id();
        $this->sendAttachmentPhoto( $channel_id, $imageId, $message );
    }

    /**
     * Notify channel on new product
     */
    public function onNewProduct( int $productId ): void {
        $this->onProductUpdated( $productId );
    }

    /**
     * Notify users who subscribed to stock alerts
     */
    public function onProductBackInStock( int $productId, \WC_Product $product ): void {
        if ( get_option( 'rf_notify_stock_alert', '1' ) !== '1' ) return;

        global $wpdb;

        $alerts = $wpdb->get_results( $wpdb->prepare(
            "SELECT sa.*, bu.chat_id, bu.first_name
             FROM {$wpdb->prefix}rf_stock_alerts sa
             INNER JOIN {$wpdb->prefix}rf_bot_users bu ON sa.bot_user_id = bu.id
             WHERE sa.product_id = %d AND sa.notified = 0",
            $productId
        ) );

        if ( empty( $alerts ) ) return;

        $vars    = MessageTemplateEngine::productVars( $product );
        $message = MessageTemplateEngine::render( 'rf_msg_stock_alert', $vars );

        foreach ( $alerts as $alert ) {
            $personalMsg = str_replace( '{first_name}', $alert->first_name ?? '', $message );
            $this->bot->sendAsync( 'sendMessage', [
                'chat_id'    => (int) $alert->chat_id,
                'text'       => $personalMsg,
                'parse_mode' => 'HTML',
            ] );

            $wpdb->update(
                $wpdb->prefix . 'rf_stock_alerts',
                [ 'notified' => 1 ],
                [ 'id' => $alert->id ],
                [ '%d' ],
                [ '%d' ]
            );
        }
    }

    /**
     * Notify admins on low stock
     */
    public function onProductLowStock( \WC_Product $product ): void {
        if ( get_option( 'rf_admin_notify_low_stock', '1' ) !== '1' ) return;

        $admin_ids = $this->parseAdminChatIds();
        if ( empty( $admin_ids ) ) return;

        $message = sprintf(
            "⚠️ موجودی کم\n📦 %s\n📊 موجودی: %s",
            $product->get_name(),
            $product->get_stock_quantity()
        );

        foreach ( $admin_ids as $adminChatId ) {
            $this->bot->sendAsync( 'sendMessage', [
                'chat_id'    => (int) $adminChatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ] );
        }
    }

    /**
     * Notify channel on post update
     */
    public function onPostUpdated( int $postId, \WP_Post $post, bool $update ): void {
        $channel_id = get_option( 'rf_product_channel_id', '' );
        if ( empty( $channel_id ) ) return;

        $message = sprintf(
            "📝 %s\n\n%s\n\n🔗 %s",
            $post->post_title,
            wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ),
            get_permalink( $postId )
        );

        $thumbId = (int) get_post_thumbnail_id( $postId );
        $this->sendAttachmentPhoto( $channel_id, $thumbId, $message );
    }

    /**
     * Notify admin about new user registration
     */
    public function onNewUserRegistered( int $wpUserId, array $botUserData ): void {
        if ( get_option( 'rf_admin_notify_new_user', '1' ) !== '1' ) return;

        $admin_ids = $this->parseAdminChatIds();
        if ( empty( $admin_ids ) ) return;

        $message = sprintf(
            "👤 کاربر جدید در ربات\n📱 %s\n👤 %s %s\n📡 %s",
            $botUserData['phone'] ?? '-',
            $botUserData['first_name'] ?? '',
            $botUserData['last_name'] ?? '',
            ucfirst( $botUserData['platform'] ?? 'telegram' )
        );

        foreach ( $admin_ids as $adminChatId ) {
            $this->bot->sendAsync( 'sendMessage', [
                'chat_id'    => (int) $adminChatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ] );
        }
    }

    private function getCustomerChatId( \WC_Order $order ): ?int {
        $customer_id = $order->get_customer_id();
        if ( $customer_id ) {
            return UserSync::getChatId( $customer_id );
        }

        // Try by phone
        $phone = $order->get_billing_phone();
        if ( $phone ) {
            global $wpdb;
            $normalized = \RobotForooshande\User\PhoneNormalizer::normalize( $phone );
            $chatId     = $wpdb->get_var( $wpdb->prepare(
                "SELECT chat_id FROM {$wpdb->prefix}rf_bot_users WHERE phone = %s LIMIT 1",
                $normalized
            ) );
            return $chatId ? (int) $chatId : null;
        }

        return null;
    }

    /**
     * Parse admin chat IDs supporting both comma and newline separators
     */
    private function parseAdminChatIds(): array {
        return array_filter( array_map( 'trim', preg_split( '/[\s,]+/', get_option( 'rf_admin_chat_ids', '' ) ) ) );
    }

    /**
     * Send a WordPress attachment as a photo.
     * Tries cached file_id → public URL → local file upload → text-only fallback.
     */
    private function sendAttachmentPhoto( int|string $chatId, int $imageId, string $caption ): void {
        if ( ! $imageId ) {
            $this->bot->sendMessage( $chatId, $caption );
            return;
        }

        $platform = $this->bot->getPlatform();
        $metaKey  = "rf_file_id_{$platform}";

        // Tier 1 — cached file_id
        $fileId = get_post_meta( $imageId, $metaKey, true );
        if ( $fileId ) {
            $result = $this->bot->sendPhoto( $chatId, $fileId, $caption );
            if ( ! empty( $result['ok'] ) ) return;
            delete_post_meta( $imageId, $metaKey );
        }

        // Tier 2 — public URL (original approach, supports WebP on Bale)
        $url = wp_get_attachment_url( $imageId );
        if ( $url ) {
            $result = $this->bot->sendPhoto( $chatId, $url, $caption );
            if ( ! empty( $result['ok'] ) ) {
                $photos = $result['result']['photo'] ?? [];
                if ( ! empty( $photos ) ) {
                    $best = end( $photos );
                    update_post_meta( $imageId, $metaKey, $best['file_id'] );
                }
                return;
            }
        }

        // Tier 3 — local file upload (converts WebP → JPEG automatically)
        $localPath = get_attached_file( $imageId );
        if ( $localPath && file_exists( $localPath ) ) {
            $result = $this->bot->sendPhotoFile( $chatId, $localPath, $caption );
            if ( ! empty( $result['ok'] ) ) {
                $photos = $result['result']['photo'] ?? [];
                if ( ! empty( $photos ) ) {
                    $best = end( $photos );
                    update_post_meta( $imageId, $metaKey, $best['file_id'] );
                }
                return;
            }
        }

        // Tier 4 — text-only fallback
        $this->bot->sendMessage( $chatId, $caption );
    }
}
