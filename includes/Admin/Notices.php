<?php
namespace RobotForooshande\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Notices {

    public function register(): void {
        add_action( 'admin_notices', [ $this, 'show' ] );
    }

    public function show(): void {
        // WooCommerce check
        if ( ! class_exists( 'WooCommerce' ) ) {
            $this->renderNotice(
                'ربات فروشنده نیاز به افزونه ووکامرس دارد. لطفاً ووکامرس را نصب و فعال کنید.',
                'error'
            );
            return;
        }

        // Bot token check
        $token = get_option( 'rf_bot_token', '' );
        if ( empty( $token ) ) {
            $url = admin_url( 'admin.php?page=robot-forooshande&tab=bot' );
            $this->renderNotice(
                "ربات فروشنده: توکن ربات تنظیم نشده است. <a href=\"{$url}\">تنظیم کنید</a>",
                'warning'
            );
        }

        // Webhook check
        if ( ! empty( $token ) && ! get_option( 'rf_webhook_active', false ) ) {
            $url = admin_url( 'admin.php?page=robot-forooshande&tab=bot' );
            $this->renderNotice(
                "ربات فروشنده: وبهوک فعال نیست. <a href=\"{$url}\">فعال‌سازی</a>",
                'warning'
            );
        }

        // HTTPS check
        if ( ! is_ssl() ) {
            $this->renderNotice(
                'ربات فروشنده: سایت شما HTTPS نیست. وبهوک تلگرام به SSL نیاز دارد.',
                'warning'
            );
        }
    }

    private function renderNotice( string $message, string $type = 'info' ): void {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $type ),
            wp_kses_post( $message )
        );
    }
}
