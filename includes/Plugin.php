<?php
namespace RobotForooshande;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->maybe_upgrade_db();
        $this->init_hooks();
    }

    private function maybe_upgrade_db(): void {
        $db_version = get_option( 'rf_db_version', '0' );
        if ( version_compare( $db_version, RF_VERSION, '<' ) ) {
            Database\Migrator::run();
        }
    }

    private function init_hooks(): void {
        // Admin
        if ( is_admin() ) {
            ( new Admin\AdminMenu() )->register();
            ( new Admin\DashboardWidget() )->register();
            ( new Admin\Notices() )->register();
        }

        // Bot webhook (REST API)
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // WooCommerce hooks
        new WooCommerce\OrderHooks();
        new WooCommerce\ProductHooks();
        new WooCommerce\OrderMetaBox();

        // Notifications
        new Notification\NotificationManager();

        // Login modal — always register shortcode; conditionally replace WC form
        new Auth\LoginModal();

        // Cron events
        add_action( 'rf_abandoned_cart_check', [ $this, 'process_abandoned_carts' ] );
        add_action( 'rf_broadcast_queue', [ $this, 'process_broadcast_queue' ] );

        // Auto-login for bot order payment pages
        add_action( 'template_redirect', [ $this, 'auto_login_bot_order_pay' ] );

        // Cart sync for bot order payment pages: populate WC session cart + suppress
        // empty-cart redirects that some themes/plugins add on the checkout page.
        add_action( 'wp', [ $this, 'restore_bot_order_cart' ] );
        add_filter( 'woocommerce_checkout_redirect_empty_cart', [ $this, 'no_redirect_for_bot_order_pay' ] );

        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );

        // WooCommerce product meta box for deep link
        add_action( 'woocommerce_product_data_panels', [ $this, 'product_deeplink_panel' ] );
        add_action( 'woocommerce_product_data_tabs', [ $this, 'product_deeplink_tab' ] );

        // AJAX handlers
        $this->register_ajax_handlers();
    }

    public function register_rest_routes(): void {
        $token = get_option( 'rf_bot_token', '' );
        if ( empty( $token ) ) return;

        register_rest_route( 'robot-forooshande/v1', '/webhook/(?P<hash>[a-f0-9]{32})', [
            [
                'methods'             => 'POST',
                'callback'            => [ new Bot\WebhookHandler(), 'handle' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'GET',
                'callback'            => static function () {
                    return new \WP_REST_Response( [ 'ok' => true, 'status' => 'webhook active' ], 200 );
                },
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    public function admin_assets( string $hook ): void {
        if ( strpos( $hook, 'robot-forooshande' ) === false && $hook !== 'index.php' ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_style(
            'rf-admin',
            RF_PLUGIN_URL . 'assets/css/admin.css',
            [ 'wp-color-picker' ],
            RF_VERSION
        );

        wp_enqueue_script(
            'rf-admin',
            RF_PLUGIN_URL . 'assets/js/admin-settings.js',
            [ 'jquery', 'wp-color-picker' ],
            RF_VERSION,
            true
        );

        wp_localize_script( 'rf-admin', 'rfSettings', [
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'rf_admin_nonce' ),
            'phoneMetaKey' => get_option( 'rf_phone_meta_key', '' ),
        ] );

        if ( $hook === 'index.php' ) {
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', [], '4.0', true );
        }
    }

    private function register_ajax_handlers(): void {
        $ajax_actions = [
            'rf_save_settings'      => [ Admin\SettingsPage::class, 'ajax_save' ],
            'rf_test_bot'           => [ Admin\SettingsPage::class, 'ajax_test_bot' ],
            'rf_set_webhook'        => [ Admin\SettingsPage::class, 'ajax_set_webhook' ],
            'rf_delete_webhook'     => [ Admin\SettingsPage::class, 'ajax_delete_webhook' ],
            'rf_test_sms'           => [ Admin\SettingsPage::class, 'ajax_test_sms' ],
            'rf_clear_logs'         => [ Admin\SettingsPage::class, 'ajax_clear_logs' ],
            'rf_export_settings'    => [ Admin\SettingsPage::class, 'ajax_export_settings' ],
            'rf_import_settings'    => [ Admin\SettingsPage::class, 'ajax_import_settings' ],
            'rf_get_user_meta_keys'    => [ Admin\SettingsPage::class, 'ajax_get_user_meta_keys' ],
            'rf_save_shipping_methods' => [ Admin\SettingsPage::class, 'ajax_save_shipping' ],
        ];

        foreach ( $ajax_actions as $action => $callback ) {
            add_action( "wp_ajax_{$action}", $callback );
        }
    }

    public function process_abandoned_carts(): void {
        $enabled = get_option( 'rf_abandoned_cart_enabled', false );
        if ( ! $enabled ) return;

        $delay_hours = (int) get_option( 'rf_abandoned_cart_delay', 1 );
        $cutoff      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$delay_hours} hours" ) );

        global $wpdb;
        $table_carts = $wpdb->prefix . 'rf_bot_carts';
        $table_users = $wpdb->prefix . 'rf_bot_users';

        $abandoned_users = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT bu.chat_id, bu.first_name, bu.platform
             FROM {$table_carts} bc
             INNER JOIN {$table_users} bu ON bc.bot_user_id = bu.id
             WHERE bc.added_at < %s
             AND bu.chat_id NOT IN (
                SELECT DISTINCT chat_id FROM {$table_users}
                WHERE state = 'idle'
             )
             GROUP BY bu.id
             HAVING MAX(bc.added_at) < %s",
            $cutoff, $cutoff
        ) );

        if ( empty( $abandoned_users ) ) return;

        $template = get_option( 'rf_msg_abandoned_cart', 'سبد خرید شما منتظر شماست! 🛒' );
        $bot      = new Bot\BotCore();

        foreach ( $abandoned_users as $user ) {
            $msg = str_replace( '{first_name}', $user->first_name ?? '', $template );
            $bot->sendMessage( $user->chat_id, $msg );
        }
    }

    public function process_broadcast_queue(): void {
        $queue = get_option( 'rf_broadcast_queue', [] );
        if ( empty( $queue ) ) return;

        $batch_size = 25;
        $bot        = new Bot\BotCore();
        $processed  = 0;

        foreach ( $queue as $key => $item ) {
            if ( $processed >= $batch_size ) break;

            if ( ! empty( $item['photo'] ) ) {
                $bot->sendPhoto( $item['chat_id'], $item['photo'], $item['text'] ?? '', $item['keyboard'] ?? null );
            } else {
                $bot->sendMessage( $item['chat_id'], $item['text'], $item['keyboard'] ?? null );
            }

            unset( $queue[ $key ] );
            $processed++;
            usleep( 50000 ); // 50ms delay
        }

        $queue = array_values( $queue );
        update_option( 'rf_broadcast_queue', $queue, false );

        if ( ! empty( $queue ) ) {
            wp_schedule_single_event( time() + 5, 'rf_broadcast_queue' );
        }
    }

    public function product_deeplink_tab( array $tabs ): array {
        $tabs['rf_deeplink'] = [
            'label'    => __( 'لینک ربات', 'robot-forooshande' ),
            'target'   => 'rf_deeplink_panel',
            'class'    => [],
            'priority' => 90,
        ];
        return $tabs;
    }

    /**
     * Auto-login WP user for bot order payment pages.
     * When a bot order payment link is opened, log in the customer automatically
     * so they can pay without manually logging in.
     */
    public function auto_login_bot_order_pay(): void {
        if ( is_user_logged_in() ) return;
        if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) return;

        $order_id  = absint( get_query_var( 'order-pay' ) );
        $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

        if ( ! $order_id || ! $order_key ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Verify order key
        if ( $order->get_order_key() !== $order_key ) return;

        // Only for bot orders
        if ( ! $order->get_meta( '_rf_from_bot' ) ) return;

        $customer_id = $order->get_customer_id();
        if ( ! $customer_id ) return;

        // Auto-login the customer
        wp_set_current_user( $customer_id );
        wp_set_auth_cookie( $customer_id, false, is_ssl() );

        // Redirect to reload the page as the logged-in user
        wp_safe_redirect( esc_url_raw( add_query_arg( [] ) ) );
        exit;
    }

    /**
     * Populate the WC session cart from a bot order when the order-pay page is loaded.
     * This prevents themes or plugins that check WC()->cart->is_empty() from
     * redirecting the user away from the order payment page.
     */
    public function restore_bot_order_cart(): void {
        if ( ! function_exists( 'is_wc_endpoint_url' ) ) return;
        if ( ! is_wc_endpoint_url( 'order-pay' ) ) return;
        if ( ! WC()->cart || ! WC()->cart->is_empty() ) return;

        $order_id  = absint( get_query_var( 'order-pay' ) );
        $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

        if ( ! $order_id || ! $order_key ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        if ( $order->get_order_key() !== $order_key ) return;
        if ( ! $order->get_meta( '_rf_from_bot' ) ) return;

        foreach ( $order->get_items() as $item ) {
            /** @var \WC_Order_Item_Product $item */
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $quantity     = $item->get_quantity();
            if ( $product_id ) {
                WC()->cart->add_to_cart( $product_id, $quantity, $variation_id ?: 0 );
            }
        }

        // Store the order id in session so WooCommerce recognises payment is in progress.
        if ( WC()->session ) {
            WC()->session->set( 'order_awaiting_payment', $order_id );
        }
    }

    /**
     * Prevent WooCommerce from redirecting to the cart when the cart is empty
     * on the order-pay page for a bot-created order.
     */
    public function no_redirect_for_bot_order_pay( bool $should_redirect ): bool {
        if ( ! function_exists( 'is_wc_endpoint_url' ) ) return $should_redirect;
        if ( ! is_wc_endpoint_url( 'order-pay' ) ) return $should_redirect;

        $order_id = absint( get_query_var( 'order-pay' ) );
        if ( ! $order_id ) return $should_redirect;

        $order = wc_get_order( $order_id );
        if ( $order && $order->get_meta( '_rf_from_bot' ) ) {
            return false;
        }
        return $should_redirect;
    }

    public function product_deeplink_panel(): void {
        global $post;
        $bot_username = get_option( 'rf_bot_username', '' );
        $platform     = get_option( 'rf_platform', 'telegram' );

        if ( $platform === 'bale' ) {
            $link = "https://ble.ir/{$bot_username}?start=product_{$post->ID}";
        } else {
            $link = "https://t.me/{$bot_username}?start=product_{$post->ID}";
        }
        ?>
        <div id="rf_deeplink_panel" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label><?php esc_html_e( 'لینک دیپ‌لینک ربات', 'robot-forooshande' ); ?></label>
                    <input type="text" readonly value="<?php echo esc_attr( $link ); ?>" class="large-text"
                           id="rf-deeplink-url" style="direction:ltr;" />
                    <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('rf-deeplink-url').value)">
                        <?php esc_html_e( 'کپی', 'robot-forooshande' ); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }
}
