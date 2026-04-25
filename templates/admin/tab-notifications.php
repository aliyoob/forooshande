<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="notifications">

        <h3>اعلان‌های مشتری</h3>
        <table class="form-table">
            <?php
            $customerNotifs = [
                'rf_notify_order_pending'    => 'در انتظار پرداخت',
                'rf_notify_order_processing' => 'در حال پردازش',
                'rf_notify_order_completed'  => 'تکمیل شده',
                'rf_notify_order_cancelled'  => 'لغو شده',
                'rf_notify_order_refunded'   => 'مسترد شده',
                'rf_notify_order_onhold'     => 'در انتظار',
                'rf_notify_order_failed'     => 'پرداخت ناموفق',
                'rf_notify_tracking_code'    => 'ثبت کد رهگیری',
                'rf_notify_stock_alert'      => 'هشدار موجود شدن محصول',
            ];
            foreach ( $customerNotifs as $key => $label ) : ?>
                <tr>
                    <th><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
                                <?php checked( get_option( $key, '1' ), '1' ); ?>>
                            ارسال به مشتری
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>اعلان‌های ادمین</h3>
        <table class="form-table">
            <?php
            $adminNotifs = [
                'rf_admin_notify_new_order' => 'سفارش جدید',
                'rf_admin_notify_payment'   => 'پرداخت موفق',
                'rf_admin_notify_cancel'    => 'لغو سفارش',
                'rf_admin_notify_low_stock' => 'موجودی کم',
                'rf_admin_notify_new_user'  => 'ثبت‌نام کاربر جدید',
            ];
            foreach ( $adminNotifs as $key => $label ) : ?>
                <tr>
                    <th><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
                                <?php checked( get_option( $key, '1' ), '1' ); ?>>
                            ارسال به ادمین
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
