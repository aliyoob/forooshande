<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="general">

        <table class="form-table">
            <tr>
                <th><label for="rf_shop_name">نام فروشگاه</label></th>
                <td>
                    <input type="text" id="rf_shop_name" name="rf_shop_name"
                           value="<?php echo esc_attr( get_option( 'rf_shop_name', get_bloginfo( 'name' ) ) ); ?>"
                           class="regular-text" dir="rtl">
                </td>
            </tr>
            <tr>
                <th><label for="rf_shop_description">توضیحات فروشگاه</label></th>
                <td>
                    <textarea id="rf_shop_description" name="rf_shop_description"
                              rows="3" class="large-text" dir="rtl"><?php echo esc_textarea( get_option( 'rf_shop_description', '' ) ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="rf_platform">پلتفرم</label></th>
                <td>
                    <?php $platform = get_option( 'rf_platform', 'telegram' ); ?>
                    <select id="rf_platform" name="rf_platform">
                        <option value="telegram" <?php selected( $platform, 'telegram' ); ?>>تلگرام</option>
                        <option value="bale" <?php selected( $platform, 'bale' ); ?>>بله</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="rf_products_per_page">تعداد محصول در هر صفحه</label></th>
                <td>
                    <input type="number" id="rf_products_per_page" name="rf_products_per_page"
                           value="<?php echo esc_attr( get_option( 'rf_products_per_page', 6 ) ); ?>"
                           min="1" max="20" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_currency_label">واحد پولی</label></th>
                <td>
                    <?php $currency = get_option( 'rf_currency_label', 'toman' ); ?>
                    <select id="rf_currency_label" name="rf_currency_label">
                        <option value="toman" <?php selected( $currency, 'toman' ); ?>>تومان</option>
                        <option value="rial" <?php selected( $currency, 'rial' ); ?>>ریال</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="rf_support_enabled">پشتیبانی در ربات</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="rf_support_enabled" name="rf_support_enabled" value="1"
                            <?php checked( get_option( 'rf_support_enabled', '1' ), '1' ); ?>>
                        فعال
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="rf_phone_meta_key">کلید متای شماره تلفن</label></th>
                <td>
                    <select id="rf_phone_meta_key" name="rf_phone_meta_key" class="regular-text">
                        <option value="">پیش‌فرض (billing_phone)</option>
                    </select>
                    <p class="description">متا کلیدی که شماره تلفن کاربران در آن ذخیره شده. گزینه <code>user_login</code> برای سایت‌هایی است که شماره موبایل به عنوان نام کاربری استفاده می‌شود.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
