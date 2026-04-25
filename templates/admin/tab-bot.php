<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="bot">

        <table class="form-table">
            <tr>
                <th><label for="rf_bot_token">توکن ربات</label></th>
                <td>
                    <input type="text" id="rf_bot_token" name="rf_bot_token"
                           value="<?php echo esc_attr( get_option( 'rf_bot_token', '' ) ); ?>"
                           class="regular-text" dir="ltr" placeholder="123456:ABC-DEF...">
                    <button type="button" class="button" id="rf-test-bot">تست اتصال</button>
                    <span id="rf-test-bot-result"></span>
                </td>
            </tr>
            <tr>
                <th><label for="rf_bot_username">یوزرنیم ربات</label></th>
                <td>
                    <input type="text" id="rf_bot_username" name="rf_bot_username"
                           value="<?php echo esc_attr( get_option( 'rf_bot_username', '' ) ); ?>"
                           class="regular-text" dir="ltr" placeholder="myshop_bot">
                    <p class="description">بدون @</p>
                </td>
            </tr>
            <tr>
                <th>وبهوک</th>
                <td>
                    <?php $webhookActive = get_option( 'rf_webhook_active', false ); ?>
                    <div style="margin-bottom:10px;">
                        وضعیت: <strong style="color:<?php echo $webhookActive ? 'green' : 'red'; ?>">
                            <?php echo $webhookActive ? '✅ فعال' : '❌ غیرفعال'; ?>
                        </strong>
                    </div>
                    <button type="button" class="button button-primary" id="rf-set-webhook">فعال‌سازی وبهوک</button>
                    <button type="button" class="button" id="rf-delete-webhook">غیرفعال‌سازی</button>
                    <span id="rf-webhook-result"></span>
                </td>
            </tr>
            <tr>
                <th><label for="rf_admin_chat_ids">چت آیدی ادمین‌ها</label></th>
                <td>
                    <input type="text" id="rf_admin_chat_ids" name="rf_admin_chat_ids"
                           value="<?php echo esc_attr( get_option( 'rf_admin_chat_ids', '' ) ); ?>"
                           class="regular-text" dir="ltr" placeholder="123456,789012">
                    <p class="description">چت آیدی ادمین‌ها با کاما جدا شوند. ادمین‌ها به پنل مدیریت ربات دسترسی دارند.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
