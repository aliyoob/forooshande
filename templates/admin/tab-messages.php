<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="messages">

        <p class="description">از متغیرهای زیر در پیام‌ها استفاده کنید:
            <code>{order_id}</code>, <code>{order_total}</code>, <code>{order_status}</code>,
            <code>{product_name}</code>, <code>{product_price}</code>,
            <code>{first_name}</code>, <code>{last_name}</code>, <code>{phone}</code>,
            <code>{customer_name}</code>, <code>{customer_phone}</code>,
            <code>{tracking_code}</code>, <code>{shipping_company}</code>,
            <code>{shop_name}</code>, <code>{date}</code>,
            <code>{stock_status}</code>, <code>{product_short_description}</code>,
            <code>{product_url}</code>, <code>{otp_code}</code>, <code>{otp_expire}</code>
        </p>

        <table class="form-table">
            <?php
            $messages = [
                'rf_msg_welcome'          => [ 'پیام خوش‌آمدگویی', 5 ],
                'rf_msg_request_phone'    => [ 'درخواست شماره تلفن', 3 ],
                'rf_msg_product_display'  => [ 'نمایش محصول', 8 ],
                'rf_msg_order_created'    => [ 'ثبت سفارش', 5 ],
                'rf_msg_order_detail'     => [ 'جزئیات سفارش', 6 ],
                'rf_msg_order_status'     => [ 'تغییر وضعیت سفارش', 4 ],
                'rf_msg_payment_success'  => [ 'پرداخت موفق', 4 ],
                'rf_msg_payment_failed'   => [ 'پرداخت ناموفق', 4 ],
                'rf_msg_tracking'         => [ 'کد رهگیری', 4 ],
                'rf_msg_stock_alert'      => [ 'هشدار موجودی', 4 ],
                'rf_msg_new_order_admin'  => [ 'سفارش جدید (ادمین)', 5 ],
                'rf_msg_product_update'   => [ 'بروزرسانی محصول', 4 ],
                'rf_msg_otp'              => [ 'کد تأیید OTP', 3 ],
                'rf_msg_abandoned_cart'   => [ 'سبد خرید رها شده', 4 ],
            ];

            foreach ( $messages as $key => [ $label, $rows ] ) : ?>
                <tr>
                    <th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                    <td>
                        <textarea id="<?php echo esc_attr( $key ); ?>"
                                  name="<?php echo esc_attr( $key ); ?>"
                                  rows="<?php echo esc_attr( $rows ); ?>"
                                  class="large-text" dir="rtl"><?php echo esc_textarea( get_option( $key, '' ) ); ?></textarea>
                        <button type="button" class="button rf-preview-msg" data-key="<?php echo esc_attr( $key ); ?>">
                            پیش‌نمایش
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>

    <div id="rf-preview-modal" style="display:none;">
        <div class="rf-preview-overlay"></div>
        <div class="rf-preview-content">
            <h3>پیش‌نمایش پیام</h3>
            <div class="rf-preview-body"></div>
            <button type="button" class="button rf-preview-close">بستن</button>
        </div>
    </div>
</div>
