<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="payment">

        <table class="form-table">
            <tr>
                <th>درگاه‌های پرداخت فعال</th>
                <td>
                    <p class="description">درگاه‌های پرداخت از تنظیمات ووکامرس خوانده می‌شوند.</p>
                    <?php
                    if ( function_exists( 'WC' ) ) {
                        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
                        if ( ! empty( $gateways ) ) {
                            echo '<ul>';
                            foreach ( $gateways as $id => $gw ) {
                                echo '<li>✅ ' . esc_html( $gw->get_title() ) . ' (<code>' . esc_html( $id ) . '</code>)</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>❌ هیچ درگاه پرداختی فعال نیست.</p>';
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="rf_payment_callback_url">آدرس بازگشت پس از پرداخت</label></th>
                <td>
                    <input type="url" id="rf_payment_callback_url" name="rf_payment_callback_url"
                           value="<?php echo esc_attr( get_option( 'rf_payment_callback_url', '' ) ); ?>"
                           class="regular-text" dir="ltr"
                           placeholder="<?php echo esc_attr( home_url( '/rf-payment-callback/' ) ); ?>">
                    <p class="description">اگر خالی باشد، آدرس پیش‌فرض استفاده می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th><label for="rf_cod_enabled">پرداخت در محل</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="rf_cod_enabled" name="rf_cod_enabled" value="1"
                            <?php checked( get_option( 'rf_cod_enabled', '1' ), '1' ); ?>>
                        فعال در ربات
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
