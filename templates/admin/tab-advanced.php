<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="advanced">

        <h3>تنظیمات پیشرفته</h3>
        <table class="form-table">
            <tr>
                <th><label for="rf_log_level">سطح لاگ</label></th>
                <td>
                    <?php $level = get_option( 'rf_log_level', 'error' ); ?>
                    <select id="rf_log_level" name="rf_log_level">
                        <option value="debug" <?php selected( $level, 'debug' ); ?>>Debug (همه)</option>
                        <option value="info" <?php selected( $level, 'info' ); ?>>Info</option>
                        <option value="warning" <?php selected( $level, 'warning' ); ?>>Warning</option>
                        <option value="error" <?php selected( $level, 'error' ); ?>>Error</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="rf_log_retention">مدت نگهداری لاگ (روز)</label></th>
                <td>
                    <input type="number" id="rf_log_retention" name="rf_log_retention"
                           value="<?php echo esc_attr( get_option( 'rf_log_retention', 30 ) ); ?>"
                           min="1" max="365" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_cache_ttl">مدت کش (ثانیه)</label></th>
                <td>
                    <input type="number" id="rf_cache_ttl" name="rf_cache_ttl"
                           value="<?php echo esc_attr( get_option( 'rf_cache_ttl', 3600 ) ); ?>"
                           min="0" class="small-text">
                    <p class="description">۰ = غیرفعال</p>
                </td>
            </tr>
        </table>

        <h3>عملیات</h3>
        <table class="form-table">
            <tr>
                <th>پاکسازی لاگ‌ها</th>
                <td>
                    <button type="button" class="button" id="rf-clear-logs">پاکسازی همه لاگ‌ها</button>
                    <span id="rf-clear-logs-result"></span>
                </td>
            </tr>
            <tr>
                <th>خروجی تنظیمات</th>
                <td>
                    <button type="button" class="button" id="rf-export-settings">دانلود تنظیمات (JSON)</button>
                </td>
            </tr>
            <tr>
                <th>ورودی تنظیمات</th>
                <td>
                    <input type="file" id="rf-import-file" accept=".json">
                    <button type="button" class="button" id="rf-import-settings">بارگذاری</button>
                    <span id="rf-import-result"></span>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
