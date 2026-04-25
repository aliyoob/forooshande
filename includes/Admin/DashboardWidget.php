<?php
namespace RobotForooshande\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class DashboardWidget {

    public function register(): void {
        add_action( 'wp_dashboard_setup', [ $this, 'addWidget' ] );
    }

    public function addWidget(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;

        wp_add_dashboard_widget(
            'rf_dashboard_widget',
            '🤖 ربات فروشنده',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        global $wpdb;

        $totalUsers = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rf_bot_users" );
        $todayOrders = count( wc_get_orders( [
            'status'       => [ 'processing', 'completed' ],
            'date_created' => '>=' . gmdate( 'Y-m-d 00:00:00' ),
            'return'       => 'ids',
            'limit'        => -1,
        ] ) );
        $pendingOrders = count( wc_get_orders( [
            'status' => 'pending',
            'return' => 'ids',
            'limit'  => -1,
        ] ) );

        $platform = get_option( 'rf_platform', 'telegram' );
        $isActive = get_option( 'rf_webhook_active', false );
        ?>
        <div class="rf-widget" dir="rtl">
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;">
                <div>
                    <strong>👥 کاربران ربات:</strong> <?php echo esc_html( $totalUsers ); ?>
                </div>
                <div>
                    <strong>📦 سفارش امروز:</strong> <?php echo esc_html( $todayOrders ); ?>
                </div>
                <div>
                    <strong>⏳ در انتظار:</strong> <?php echo esc_html( $pendingOrders ); ?>
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <strong>پلتفرم:</strong> <?php echo $platform === 'bale' ? 'بله' : 'تلگرام'; ?>
                &nbsp;|&nbsp;
                <strong>وبهوک:</strong>
                <span style="color:<?php echo $isActive ? 'green' : 'red'; ?>">
                    <?php echo $isActive ? '✅ فعال' : '❌ غیرفعال'; ?>
                </span>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=robot-forooshande' ) ); ?>" class="button button-primary">
                تنظیمات ربات
            </a>
        </div>
        <?php
    }
}
