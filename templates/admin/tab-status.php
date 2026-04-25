<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <h2>🔍 وضعیت سیستم</h2>

    <table class="widefat striped">
        <tbody>
            <tr>
                <th>نسخه PHP</th>
                <td><?php echo esc_html( PHP_VERSION ); ?>
                    <?php echo version_compare( PHP_VERSION, '8.0', '>=' ) ? '✅' : '❌ حداقل 8.0 نیاز است'; ?>
                </td>
            </tr>
            <tr>
                <th>نسخه وردپرس</th>
                <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?> ✅</td>
            </tr>
            <tr>
                <th>نسخه ووکامرس</th>
                <td>
                    <?php
                    if ( defined( 'WC_VERSION' ) ) {
                        echo esc_html( WC_VERSION );
                        echo version_compare( WC_VERSION, '7.0', '>=' ) ? ' ✅' : ' ❌ حداقل 7.0 نیاز است';
                    } else {
                        echo '❌ نصب نشده';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>نسخه پلاگین</th>
                <td><?php echo esc_html( RF_VERSION ); ?></td>
            </tr>
            <tr>
                <th>پلتفرم ربات</th>
                <td><?php echo get_option( 'rf_platform', 'telegram' ) === 'bale' ? 'بله' : 'تلگرام'; ?></td>
            </tr>
            <tr>
                <th>توکن ربات</th>
                <td>
                    <?php
                    $token = get_option( 'rf_bot_token', '' );
                    echo $token ? '✅ تنظیم شده' : '❌ تنظیم نشده';
                    ?>
                </td>
            </tr>
            <tr>
                <th>وبهوک</th>
                <td>
                    <?php
                    $active = get_option( 'rf_webhook_active', false );
                    echo $active ? '✅ فعال' : '❌ غیرفعال';
                    ?>
                </td>
            </tr>
            <tr>
                <th>SSL</th>
                <td><?php echo is_ssl() ? '✅ فعال' : '❌ غیرفعال (وبهوک تلگرام نیاز به SSL دارد)'; ?></td>
            </tr>
            <tr>
                <th>cURL</th>
                <td><?php echo function_exists( 'curl_version' ) ? '✅ فعال' : '❌ غیرفعال'; ?></td>
            </tr>
            <tr>
                <th>سرویس پیامکی</th>
                <td>
                    <?php
                    $sms = get_option( 'rf_sms_provider', '' );
                    echo $sms ? '✅ ' . esc_html( $sms ) : '❌ تنظیم نشده';
                    ?>
                </td>
            </tr>
            <tr>
                <th>کاربران ربات</th>
                <td>
                    <?php
                    global $wpdb;
                    echo esc_html( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rf_bot_users" ) );
                    ?>
                </td>
            </tr>
            <tr>
                <th>آدرس وبهوک</th>
                <td>
                    <code dir="ltr" style="word-break:break-all;">
                        <?php
                        $hash = get_option( 'rf_webhook_hash', '' );
                        echo esc_html( rest_url( "robot-forooshande/v1/webhook/{$hash}" ) );
                        ?>
                    </code>
                </td>
            </tr>
        </tbody>
    </table>
</div>
