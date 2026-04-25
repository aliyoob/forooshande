<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="loginpage">

        <h3>تنظیمات عمومی صفحه لاگین</h3>
        <table class="form-table">
            <tr>
                <th><label for="rf_login_modal_enabled">جایگزینی فرم ورود ووکامرس</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="rf_login_modal_enabled" name="rf_login_modal_enabled" value="1"
                            <?php checked( get_option( 'rf_login_modal_enabled', '0' ), '1' ); ?>>
                        فرم ورود ووکامرس با فرم ربات فروشنده جایگزین شود
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_methods">روش‌های ورود</label></th>
                <td>
                    <?php $methods = get_option( 'rf_login_methods', 'otp' ); ?>
                    <select id="rf_login_methods" name="rf_login_methods">
                        <option value="otp" <?php selected( $methods, 'otp' ); ?>>فقط OTP</option>
                        <option value="password" <?php selected( $methods, 'password' ); ?>>فقط رمز عبور</option>
                        <option value="both" <?php selected( $methods, 'both' ); ?>>OTP + رمز عبور</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="rf_otp_method">روش ارسال OTP</label></th>
                <td>
                    <?php $otpMethod = get_option( 'rf_otp_method', 'sms' ); ?>
                    <select id="rf_otp_method" name="rf_otp_method">
                        <option value="sms" <?php selected( $otpMethod, 'sms' ); ?>>فقط پیامک (SMS)</option>
                        <option value="bot" <?php selected( $otpMethod, 'bot' ); ?>>فقط ربات (بله/تلگرام)</option>
                        <option value="both" <?php selected( $otpMethod, 'both' ); ?>>هر دو (کاربر انتخاب کند)</option>
                    </select>
                    <p class="description">در حالت «هر دو»، کاربر در صفحه لاگین می‌تواند روش دریافت کد را انتخاب کند.</p>
                </td>
            </tr>
            <tr>
                <th><label for="rf_otp_length">طول کد OTP</label></th>
                <td>
                    <input type="number" id="rf_otp_length" name="rf_otp_length"
                           value="<?php echo esc_attr( get_option( 'rf_otp_length', 5 ) ); ?>"
                           min="4" max="8" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_otp_expiry">مدت اعتبار OTP (ثانیه)</label></th>
                <td>
                    <input type="number" id="rf_otp_expiry" name="rf_otp_expiry"
                           value="<?php echo esc_attr( get_option( 'rf_otp_expiry', 120 ) ); ?>"
                           min="60" max="600" class="small-text">
                </td>
            </tr>
            <tr>
                <th>شورت‌کد</th>
                <td>
                    <code>[rf_login]</code>
                    <p class="description">از این شورت‌کد در هر صفحه‌ای می‌توانید استفاده کنید.</p>
                </td>
            </tr>
        </table>

        <h3>آیکون فرم لاگین</h3>
        <table class="form-table">
            <tr>
                <th><label for="rf_login_icon">آیکون سربرگ فرم</label></th>
                <td>
                    <?php $loginIcon = get_option( 'rf_login_icon', 'user' ); ?>
                    <select id="rf_login_icon" name="rf_login_icon" style="min-width:200px;">
                        <option value="user" <?php selected( $loginIcon, 'user' ); ?>>👤 کاربر</option>
                        <option value="lock" <?php selected( $loginIcon, 'lock' ); ?>>🔒 قفل</option>
                        <option value="shield" <?php selected( $loginIcon, 'shield' ); ?>>🛡️ سپر</option>
                        <option value="login" <?php selected( $loginIcon, 'login' ); ?>>🚪 ورود</option>
                        <option value="key" <?php selected( $loginIcon, 'key' ); ?>>🔑 کلید</option>
                        <option value="store" <?php selected( $loginIcon, 'store' ); ?>>🛍️ فروشگاه</option>
                        <option value="fingerprint" <?php selected( $loginIcon, 'fingerprint' ); ?>>👆 اثر انگشت</option>
                    </select>
                    <p class="description">آیکونی که در بالای فرم لاگین نمایش داده می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th><label>آیکون روش ربات (بله/تلگرام)</label></th>
                <td>
                    <?php $botIconUrl = get_option( 'rf_login_bot_method_icon', '' ); ?>
                    <div class="rf-media-upload-field">
                        <input type="hidden" id="rf_login_bot_method_icon" name="rf_login_bot_method_icon" value="<?php echo esc_attr( $botIconUrl ); ?>">
                        <div id="rf-bot-icon-preview" style="margin-bottom:10px;<?php echo empty( $botIconUrl ) ? 'display:none;' : ''; ?>">
                            <img src="<?php echo esc_url( $botIconUrl ); ?>" style="max-width:48px;max-height:48px;border-radius:8px;border:1px solid #e5e7eb;">
                        </div>
                        <button type="button" class="button" id="rf-upload-bot-icon">انتخاب تصویر</button>
                        <button type="button" class="button" id="rf-remove-bot-icon" style="color:#ef4444;<?php echo empty( $botIconUrl ) ? 'display:none;' : ''; ?>">حذف</button>
                    </div>
                    <p class="description">تصویر آیکون برای گزینه «ارسال با ربات» در فرم لاگین. اگر خالی باشد، آیکون پیش‌فرض SVG نمایش داده می‌شود.</p>
                </td>
            </tr>
        </table>

        <h3>رنگ‌بندی صفحه لاگین</h3>
        <table class="form-table">
            <tr>
                <th><label for="rf_login_color_primary">رنگ اصلی</label></th>
                <td>
                    <input type="text" id="rf_login_color_primary" name="rf_login_color_primary"
                           value="<?php echo esc_attr( get_option( 'rf_login_color_primary', '#6366f1' ) ); ?>"
                           class="rf-color-picker" data-default-color="#6366f1">
                    <p class="description">رنگ اصلی دکمه‌ها و عناصر تعاملی</p>
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_color_secondary">رنگ ثانویه</label></th>
                <td>
                    <input type="text" id="rf_login_color_secondary" name="rf_login_color_secondary"
                           value="<?php echo esc_attr( get_option( 'rf_login_color_secondary', '#8b5cf6' ) ); ?>"
                           class="rf-color-picker" data-default-color="#8b5cf6">
                    <p class="description">رنگ ثانویه برای گرادینت‌ها و هایلایت‌ها</p>
                </td>
            </tr>
        </table>

        <h3>متن‌های قابل شخصی‌سازی</h3>
        <table class="form-table">
            <tr>
                <th><label for="rf_login_text_title">عنوان فرم</label></th>
                <td>
                    <input type="text" id="rf_login_text_title" name="rf_login_text_title"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_title', 'ورود / ثبت‌نام' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_subtitle">زیرعنوان فرم</label></th>
                <td>
                    <input type="text" id="rf_login_text_subtitle" name="rf_login_text_subtitle"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_subtitle', 'به حساب کاربری خود وارد شوید' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_phone_label">متن فیلد شماره موبایل</label></th>
                <td>
                    <input type="text" id="rf_login_text_phone_label" name="rf_login_text_phone_label"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_phone_label', 'شماره موبایل' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_phone_placeholder">پلیس‌هولدر شماره موبایل</label></th>
                <td>
                    <input type="text" id="rf_login_text_phone_placeholder" name="rf_login_text_phone_placeholder"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_phone_placeholder', '09123456789' ) ); ?>"
                           class="regular-text" dir="ltr">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_send_otp">متن دکمه ارسال کد</label></th>
                <td>
                    <input type="text" id="rf_login_text_send_otp" name="rf_login_text_send_otp"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_send_otp', 'دریافت کد تأیید' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_otp_label">متن فیلد کد تأیید</label></th>
                <td>
                    <input type="text" id="rf_login_text_otp_label" name="rf_login_text_otp_label"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_otp_label', 'کد تأیید ارسال شده را وارد کنید' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_verify_btn">متن دکمه تأیید</label></th>
                <td>
                    <input type="text" id="rf_login_text_verify_btn" name="rf_login_text_verify_btn"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_verify_btn', 'تأیید و ورود' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_resend">متن ارسال مجدد</label></th>
                <td>
                    <input type="text" id="rf_login_text_resend" name="rf_login_text_resend"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_resend', 'ارسال مجدد کد' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_switch_password">متن لینک ورود با رمز</label></th>
                <td>
                    <input type="text" id="rf_login_text_switch_password" name="rf_login_text_switch_password"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_switch_password', 'ورود با رمز عبور' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_switch_otp">متن لینک ورود با OTP</label></th>
                <td>
                    <input type="text" id="rf_login_text_switch_otp" name="rf_login_text_switch_otp"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_switch_otp', 'ورود با کد تأیید' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_password_label">متن فیلد رمز عبور</label></th>
                <td>
                    <input type="text" id="rf_login_text_password_label" name="rf_login_text_password_label"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_password_label', 'رمز عبور' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_login_btn">متن دکمه ورود با رمز</label></th>
                <td>
                    <input type="text" id="rf_login_text_login_btn" name="rf_login_text_login_btn"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_login_btn', 'ورود' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_method_sms">متن گزینه SMS</label></th>
                <td>
                    <input type="text" id="rf_login_text_method_sms" name="rf_login_text_method_sms"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_method_sms', 'ارسال با پیامک' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_method_bot">متن گزینه ربات</label></th>
                <td>
                    <input type="text" id="rf_login_text_method_bot" name="rf_login_text_method_bot"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_method_bot', 'ارسال با ربات (بله/تلگرام)' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_logged_in">متن کاربر وارد شده</label></th>
                <td>
                    <input type="text" id="rf_login_text_logged_in" name="rf_login_text_logged_in"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_logged_in', 'شما وارد شده‌اید.' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
        </table>

        <h3>باکس اطلاع‌رسانی ربات</h3>
        <p class="description" style="margin-bottom:15px;">این باکس زمانی نمایش داده می‌شود که روش OTP روی «ربات» یا «هر دو» باشد.</p>
        <table class="form-table">
            <tr>
                <th><label for="rf_login_text_bot_info">متن باکس اطلاع‌رسانی</label></th>
                <td>
                    <textarea id="rf_login_text_bot_info" name="rf_login_text_bot_info" rows="3" class="large-text"
                    ><?php echo esc_textarea( get_option( 'rf_login_text_bot_info', 'برای دریافت کد تأیید از طریق ربات، لازم است ابتدا ربات ما را در بله/تلگرام استارت کرده باشید.' ) ); ?></textarea>
                    <p class="description">در صفحه لاگین به صورت یک باکس اطلاعاتی نمایش داده می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_bot_link">لینک ربات (اختیاری)</label></th>
                <td>
                    <input type="url" id="rf_login_text_bot_link" name="rf_login_text_bot_link"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_bot_link', '' ) ); ?>"
                           class="regular-text" dir="ltr" placeholder="https://t.me/YourBot">
                    <p class="description">اگر پر شود، یک دکمه «استارت ربات» در باکس نمایش داده می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th><label for="rf_login_text_bot_link_text">متن دکمه لینک ربات</label></th>
                <td>
                    <input type="text" id="rf_login_text_bot_link_text" name="rf_login_text_bot_link_text"
                           value="<?php echo esc_attr( get_option( 'rf_login_text_bot_link_text', 'استارت ربات' ) ); ?>"
                           class="regular-text">
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>
