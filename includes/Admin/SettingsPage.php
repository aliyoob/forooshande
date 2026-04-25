<?php
namespace RobotForooshande\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class SettingsPage {

    private array $tabs;
    private array $tabIcons;

    public function __construct() {
        $this->tabs = [
            'general'       => 'عمومی',
            'bot'           => 'ربات',
            'notifications' => 'اعلان‌ها',
            'messages'      => 'پیام‌ها',
            'payment'       => 'پرداخت',
            'sms'           => 'پیامک',
            'loginpage'     => 'صفحه لاگین',
            'advanced'      => 'پیشرفته',
        ];
        $this->tabIcons = [
            'general'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'bot'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><!-- Icon from Mage Icons by MageIcons - https://github.com/Mage-Icons/mage-icons/blob/main/License.txt --><g fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.706 4.313H9.294a4.98 4.98 0 0 0-4.982 4.981v5.412a4.98 4.98 0 0 0 4.982 4.982h5.412a4.98 4.98 0 0 0 4.982-4.982V9.294a4.98 4.98 0 0 0-4.982-4.982Z"/><path d="M19.606 15.588h1.619a1.025 1.025 0 0 0 1.025-1.025V9.438a1.025 1.025 0 0 0-1.025-1.025h-1.62m-15.21 7.175h-1.62a1.025 1.025 0 0 1-1.025-1.025V9.438a1.025 1.025 0 0 1 1.025-1.025h1.62"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.765 8.413v-4.1m18.46 4.1l-.01-4.1M9.95 15.237a2.91 2.91 0 0 0 4.1 0m-6.17-4.262L8.903 9.95l1.025 1.025m4.102 0l1.025-1.025l1.024 1.025"/></g></svg>',
            'notifications' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
            'messages'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            'payment'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
            'sms'           => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
            'loginpage'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
            'advanced'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        ];
    }

    public function render(): void {
        $activeTab = sanitize_key( $_GET['tab'] ?? 'general' );
        if ( ! isset( $this->tabs[ $activeTab ] ) ) {
            $activeTab = 'general';
        }
        ?>
        <div class="rf-admin-wrap" dir="rtl">
            <!-- Header -->
            <div class="rf-admin-header">
                <div class="rf-admin-header-inner">
                    <div class="rf-admin-brand">
                        <div class="rf-admin-logo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="currentColor" d="M22.078 8.347a1.4 1.4 0 0 0-.488-.325V4.647a.717.717 0 1 0-1.434 0V7.85h-.21a5.48 5.48 0 0 0-5.25-3.92H9.427a5.48 5.48 0 0 0-5.25 3.92H3.9V4.647a.717.717 0 1 0-1.434 0v3.385a1.5 1.5 0 0 0-.469.315A1.72 1.72 0 0 0 1.5 9.552v4.896a1.7 1.7 0 0 0 1.702 1.702h.956a5.48 5.48 0 0 0 5.25 3.92h5.183a5.48 5.48 0 0 0 5.25-3.92h.955a1.7 1.7 0 0 0 1.702-1.702V9.552c.02-.44-.131-.872-.42-1.205M3.996 14.716H3.24a.27.27 0 0 1-.191-.077a.3.3 0 0 1-.076-.191V9.552a.26.26 0 0 1 .248-.268h.775a.6.6 0 0 0 0 .125v5.182a.6.6 0 0 0 0 .125m4.695-3.118a.813.813 0 0 1-1.386-.578c0-.217.086-.425.238-.579l.956-.956a.813.813 0 0 1 1.148 0l.956.956a.812.812 0 0 1-.574 1.387a.8.8 0 0 1-.573-.23l-.412-.41zm5.9 4.074a3.605 3.605 0 0 1-5.068 0a.813.813 0 0 1 .885-1.326a.8.8 0 0 1 .262.178a2.017 2.017 0 0 0 2.773 0a.804.804 0 0 1 1.148 0a.813.813 0 0 1 0 1.148m1.912-4.074a.813.813 0 0 1-1.148 0l-.41-.41l-.402.41a.82.82 0 0 1-.574.23a.8.8 0 0 1-.574-.23a.82.82 0 0 1 0-1.157l.957-.956a.813.813 0 0 1 1.147 0l.956.956a.82.82 0 0 1 .077 1.157zm4.609 2.869a.3.3 0 0 1-.077.191a.27.27 0 0 1-.191.077h-.755a.6.6 0 0 0 0-.125V9.37a.6.6 0 0 0 0-.124h.765a.25.25 0 0 1 .181.077c.049.052.076.12.077.19z"/></svg>
                        </div>
                        <div>
                            <h1>ربات فروشنده</h1>
                            <span class="rf-admin-version">نسخه <?php echo esc_html( RF_VERSION ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rf-admin-body">
                <!-- Sidebar -->
                <nav class="rf-admin-sidebar">
                    <?php foreach ( $this->tabs as $slug => $label ) : ?>
                        <a href="<?php echo esc_url( admin_url( "admin.php?page=robot-forooshande&tab={$slug}" ) ); ?>"
                           class="rf-admin-nav-item <?php echo $activeTab === $slug ? 'rf-active' : ''; ?>">
                            <span class="rf-nav-icon"><?php echo $this->tabIcons[ $slug ] ?? ''; ?></span>
                            <span class="rf-nav-label"><?php echo esc_html( $label ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Content -->
                <main class="rf-admin-content">
                    <?php
                    $template = RF_PLUGIN_DIR . "templates/admin/tab-{$activeTab}.php";
                    if ( file_exists( $template ) ) {
                        include $template;
                    }
                    ?>
                </main>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Save settings
     */
    public static function ajax_save(): void {
        check_ajax_referer( 'rf_save_settings', 'rf_nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'دسترسی ندارید.' ] );
        }

        $tab      = sanitize_key( $_POST['tab'] ?? 'general' );
        $exclude  = [ 'action', 'rf_nonce', '_wp_http_referer', 'tab' ];

        // Booleans grouped by tab — only reset unchecked booleans for the current tab
        $booleans_by_tab = [
            'general'       => [ 'rf_support_enabled' ],
            'loginpage'     => [ 'rf_login_modal_enabled' ],
            'payment'       => [ 'rf_cod_enabled' ],
            'notifications' => [
                'rf_notify_order_pending', 'rf_notify_order_processing', 'rf_notify_order_completed',
                'rf_notify_order_cancelled', 'rf_notify_order_refunded', 'rf_notify_order_onhold',
                'rf_notify_order_failed', 'rf_notify_tracking_code', 'rf_notify_stock_alert',
                'rf_admin_notify_new_order', 'rf_admin_notify_payment', 'rf_admin_notify_cancel',
                'rf_admin_notify_low_stock', 'rf_admin_notify_new_user',
            ],
        ];

        // Save unchecked booleans as '0' — only for the current tab
        $tab_booleans = $booleans_by_tab[ $tab ] ?? [];
        foreach ( $tab_booleans as $key ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                update_option( $key, '0' );
            }
        }

        foreach ( $_POST as $key => $value ) {
            if ( in_array( $key, $exclude, true ) ) continue;
            if ( strpos( $key, 'rf_' ) !== 0 ) continue;

            if ( is_array( $value ) ) {
                $value = array_map( 'sanitize_text_field', $value );
            } elseif ( $key === 'rf_bot_token' ) {
                $value = \RobotForooshande\Helpers\Sanitizer::botToken( $value );
            } elseif ( str_contains( $key, 'msg_' ) ) {
                $value = sanitize_textarea_field( $value );
            } else {
                $value = sanitize_text_field( $value );
            }

            update_option( $key, $value );
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Test bot connection
     */
    public static function ajax_test_bot(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $bot    = new \RobotForooshande\Bot\BotCore();
        $result = $bot->getMe();

        if ( $result && isset( $result['ok'] ) && $result['ok'] ) {
            wp_send_json_success( [ 'bot_name' => $result['result']['first_name'] ?? 'OK' ] );
        } else {
            wp_send_json_error( [ 'message' => 'اتصال برقرار نشد.' ] );
        }
    }

    /**
     * AJAX: Set webhook
     */
    public static function ajax_set_webhook(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $hash = md5( get_option( 'rf_bot_token', '' ) . wp_salt() );
        update_option( 'rf_webhook_hash', $hash );

        $url = rest_url( "robot-forooshande/v1/webhook/{$hash}" );
        $bot = new \RobotForooshande\Bot\BotCore();
        $res = $bot->setWebhook( $url );

        if ( $res && ! empty( $res['ok'] ) ) {
            update_option( 'rf_webhook_active', true );
            wp_send_json_success();
        } else {
            wp_send_json_error( [ 'message' => $res['description'] ?? 'خطا' ] );
        }
    }

    /**
     * AJAX: Delete webhook
     */
    public static function ajax_delete_webhook(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $bot = new \RobotForooshande\Bot\BotCore();
        $bot->deleteWebhook();
        update_option( 'rf_webhook_active', false );
        wp_send_json_success();
    }

    /**
     * AJAX: Test SMS
     */
    public static function ajax_test_sms(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        if ( empty( $phone ) ) wp_send_json_error( [ 'message' => 'شماره وارد کنید.' ] );

        $result = \RobotForooshande\Auth\SMSFactory::sendOTP( $phone, '12345' );
        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( [ 'message' => 'ارسال ناموفق.' ] );
        }
    }

    /**
     * AJAX: Clear logs
     */
    public static function ajax_clear_logs(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rf_logs" );
        wp_send_json_success();
    }

    /**
     * AJAX: Export settings
     */
    public static function ajax_export_settings(): void {
        check_admin_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Unauthorized' );

        global $wpdb;
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'rf_%'",
            OBJECT_K
        );

        $data = [];
        foreach ( $options as $opt ) {
            $data[ $opt->option_name ] = $opt->option_value;
        }

        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename=robot-forooshande-settings.json' );
        echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit;
    }

    /**
     * AJAX: Import settings
     */
    public static function ajax_import_settings(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        $raw = sanitize_text_field( wp_unslash( $_POST['settings'] ?? '' ) );
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => 'فایل نامعتبر.' ] );
        }

        foreach ( $data as $key => $value ) {
            if ( strpos( $key, 'rf_' ) === 0 ) {
                update_option( sanitize_key( $key ), sanitize_text_field( $value ) );
            }
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Get user meta keys for phone field mapping
     */
    public static function ajax_get_user_meta_keys(): void {
        check_ajax_referer( 'rf_admin_nonce', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        global $wpdb;
        $keys = $wpdb->get_col(
            "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE '%phone%' OR meta_key LIKE '%mobile%' OR meta_key = 'billing_phone' ORDER BY meta_key"
        );

        // Add user_login as option (some sites use phone number as username)
        array_unshift( $keys, 'user_login' );
        $keys = array_unique( $keys );

        wp_send_json_success( [ 'keys' => $keys ] );
    }
}
