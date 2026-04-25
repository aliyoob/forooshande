<?php
namespace RobotForooshande\Auth;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\User\{OTPManager, PhoneNormalizer, UserSync};

class LoginModal {

    public function __construct() {
        add_shortcode( 'rf_login', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_nopriv_rf_login_send_otp', [ $this, 'ajax_send_otp' ] );
        add_action( 'wp_ajax_nopriv_rf_login_verify_otp', [ $this, 'ajax_verify_otp' ] );
        add_action( 'wp_ajax_nopriv_rf_login_password', [ $this, 'ajax_login_password' ] );

        // Replace WooCommerce login form if enabled
        if ( get_option( 'rf_login_modal_enabled', '0' ) === '1' ) {
            add_filter( 'woocommerce_locate_template', [ $this, 'override_wc_login_template' ], 99, 3 );
        }
    }

    public function enqueue_assets(): void {
        if ( is_user_logged_in() ) return;

        wp_enqueue_style( 'rf-login-modal', RF_PLUGIN_URL . 'assets/css/login-modal.css', [], RF_VERSION );
        wp_enqueue_script( 'rf-login-modal', RF_PLUGIN_URL . 'assets/js/login-modal.js', [ 'jquery' ], RF_VERSION, true );
        wp_localize_script( 'rf-login-modal', 'rfLogin', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'rf_login_nonce' ),
            'otpMethod' => get_option( 'rf_otp_method', 'sms' ),
            'autoShow'  => false,
        ] );
    }

    public function render_shortcode( $atts = [] ): string {
        if ( is_user_logged_in() ) {
            $text = get_option( 'rf_login_text_logged_in', 'شما وارد شده‌اید.' );
            return '<p>' . esc_html( $text ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ?: home_url() ) . '">حساب کاربری</a></p>';
        }

        // Ensure assets are loaded
        $this->enqueue_assets();

        ob_start();
        include RF_PLUGIN_DIR . 'templates/frontend/login-form.php';
        return ob_get_clean();
    }

    /**
     * Override WooCommerce form-login.php template
     */
    public function override_wc_login_template( string $template, string $template_name, string $template_path ): string {
        if ( $template_name === 'myaccount/form-login.php' ) {
            $custom = RF_PLUGIN_DIR . 'templates/woocommerce/form-login.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public static function ajax_send_otp(): void {
        check_ajax_referer( 'rf_login_nonce', 'nonce' );

        $phone  = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $method = sanitize_text_field( wp_unslash( $_POST['method'] ?? 'sms' ) );

        if ( ! PhoneNormalizer::isValid( $phone ) ) {
            wp_send_json_error( [ 'message' => 'شماره تلفن نامعتبر است.' ] );
        }

        $normalized = PhoneNormalizer::normalize( $phone );

        $code = OTPManager::generate( $normalized, $method );
        if ( ! $code ) {
            wp_send_json_error( [ 'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.' ] );
        }

        if ( $method === 'bot' ) {
            $wpUserId = UserSync::findWpUserByPhone( $normalized );
            $sent     = false;
            if ( $wpUserId ) {
                $chatId = UserSync::getChatId( $wpUserId );
                if ( $chatId ) {
                    $bot = new \RobotForooshande\Bot\BotCore();
                    $msg = str_replace(
                        [ '{otp_code}', '{otp_expire}' ],
                        [ $code, (int) ceil( get_option( 'rf_otp_expiry', 120 ) / 60 ) ],
                        get_option( 'rf_msg_otp', "کد تأیید: {otp_code}" )
                    );
                    $bot->sendMessage( $chatId, $msg );
                    $sent = true;
                }
            }
            if ( ! $sent ) {
                wp_send_json_error( [ 'message' => 'حساب شما در ربات یافت نشد. لطفاً ابتدا ربات را استارت کنید یا از روش پیامکی استفاده کنید.' ] );
            }
        } elseif ( $method === 'sms' ) {
            $sent = SMSFactory::sendOTP( $normalized, $code );
            if ( ! $sent ) {
                wp_send_json_error( [ 'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید.' ] );
            }
        }

        wp_send_json_success( [ 'message' => 'کد تأیید ارسال شد.' ] );
    }

    public static function ajax_verify_otp(): void {
        check_ajax_referer( 'rf_login_nonce', 'nonce' );

        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );

        $normalized = PhoneNormalizer::normalize( $phone );

        if ( ! OTPManager::verify( $normalized, $code ) ) {
            wp_send_json_error( [ 'message' => 'کد تأیید نامعتبر یا منقضی شده است.' ] );
        }

        // Find or create user
        $wpUserId = UserSync::findWpUserByPhone( $normalized );
        if ( ! $wpUserId ) {
            $wpUserId = UserSync::sync( $normalized );
        }

        if ( ! $wpUserId ) {
            wp_send_json_error( [ 'message' => 'خطا در ورود. لطفاً دوباره تلاش کنید.' ] );
        }

        wp_set_auth_cookie( $wpUserId, true );
        wp_set_current_user( $wpUserId );

        wp_send_json_success( [
            'message'  => 'ورود موفقیت‌آمیز!',
            'redirect' => wc_get_page_permalink( 'myaccount' ) ?: home_url(),
        ] );
    }

    public static function ajax_login_password(): void {
        check_ajax_referer( 'rf_login_nonce', 'nonce' );

        $username = sanitize_text_field( wp_unslash( $_POST['username'] ?? $_POST['phone'] ?? '' ) );
        $password = $_POST['password'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

        $user = wp_authenticate( $username, $password );
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( [ 'message' => 'نام کاربری یا رمز عبور اشتباه است.' ] );
        }

        wp_set_auth_cookie( $user->ID, true );
        wp_set_current_user( $user->ID );

        wp_send_json_success( [
            'message'  => 'ورود موفقیت‌آمیز!',
            'redirect' => wc_get_page_permalink( 'myaccount' ) ?: home_url(),
        ] );
    }
}
