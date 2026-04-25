<?php if ( ! defined( 'ABSPATH' ) ) exit;
$provider = get_option( 'rf_sms_provider', '' );
?>
<div class="rf-settings-page" dir="rtl">
    <form method="post" action="" id="rf-settings-form">
        <?php wp_nonce_field( 'rf_save_settings', 'rf_nonce' ); ?>
        <input type="hidden" name="tab" value="sms">

        <table class="form-table">
            <tr>
                <th><label for="rf_sms_provider">سرویس پیامکی</label></th>
                <td>
                    <select id="rf_sms_provider" name="rf_sms_provider" onchange="rfShowProviderSettings(this.value)">
                        <option value="">غیرفعال</option>
                        <option value="kavenegar" <?php selected( $provider, 'kavenegar' ); ?>>کاوه‌نگار</option>
                        <option value="ippanel" <?php selected( $provider, 'ippanel' ); ?>>آی‌پی پنل (IPPanel)</option>
                        <option value="melipayamak" <?php selected( $provider, 'melipayamak' ); ?>>ملی پیامک</option>
                        <option value="farazsms" <?php selected( $provider, 'farazsms' ); ?>>فراز پیامک (ایران پیامک)</option>
                        <option value="freesms" <?php selected( $provider, 'freesms' ); ?>>رایگان SMS</option>
                        <option value="qasedaksms" <?php selected( $provider, 'qasedaksms' ); ?>>قاصدک پیامک</option>
                        <option value="smsir" <?php selected( $provider, 'smsir' ); ?>>SMS.ir</option>
                        <option value="amootsms" <?php selected( $provider, 'amootsms' ); ?>>آموت پیامک</option>
                    </select>
                </td>
            </tr>
        </table>

        <!-- کاوه‌نگار -->
        <?php $s = get_option( 'rf_kavenegar_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-kavenegar" style="display:<?php echo $provider === 'kavenegar' ? 'block' : 'none'; ?>">
            <h3>تنظیمات کاوه‌نگار</h3>
            <table class="form-table">
                <tr>
                    <th>توکن API</th>
                    <td><input type="text" name="rf_kavenegar_settings[token]" value="<?php echo esc_attr( $s['token'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>شماره فرستنده</th>
                    <td><input type="text" name="rf_kavenegar_settings[sender]" value="<?php echo esc_attr( $s['sender'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>نام قالب (Template)</th>
                    <td>
                        <input type="text" name="rf_kavenegar_settings[template]" value="<?php echo esc_attr( $s['template'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">نام قالب ساخته شده در پنل کاوه‌نگار</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- آی‌پی پنل -->
        <?php $s = get_option( 'rf_ippanel_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-ippanel" style="display:<?php echo $provider === 'ippanel' ? 'block' : 'none'; ?>">
            <h3>تنظیمات آی‌پی پنل (IPPanel)</h3>
            <table class="form-table">
                <tr>
                    <th>نام کاربری</th>
                    <td><input type="text" name="rf_ippanel_settings[username]" value="<?php echo esc_attr( $s['username'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>رمز عبور</th>
                    <td><input type="password" name="rf_ippanel_settings[password]" value="<?php echo esc_attr( $s['password'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>شماره فرستنده</th>
                    <td><input type="text" name="rf_ippanel_settings[sender]" value="<?php echo esc_attr( $s['sender'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>کد قالب (Pattern Code)</th>
                    <td><input type="text" name="rf_ippanel_settings[template]" value="<?php echo esc_attr( $s['template'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
            </table>
        </div>

        <!-- ملی پیامک -->
        <?php $s = get_option( 'rf_melipayamak_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-melipayamak" style="display:<?php echo $provider === 'melipayamak' ? 'block' : 'none'; ?>">
            <h3>تنظیمات ملی پیامک</h3>
            <table class="form-table">
                <tr>
                    <th>نام کاربری</th>
                    <td><input type="text" name="rf_melipayamak_settings[username]" value="<?php echo esc_attr( $s['username'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>رمز عبور</th>
                    <td><input type="password" name="rf_melipayamak_settings[password]" value="<?php echo esc_attr( $s['password'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>شماره فرستنده</th>
                    <td><input type="text" name="rf_melipayamak_settings[sender]" value="<?php echo esc_attr( $s['sender'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>شناسه قالب (Body ID)</th>
                    <td>
                        <input type="text" name="rf_melipayamak_settings[template]" value="<?php echo esc_attr( $s['template'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">شناسه عددی قالب ساخته شده در پنل</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- فراز پیامک -->
        <?php $s = get_option( 'rf_farazsms_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-farazsms" style="display:<?php echo $provider === 'farazsms' ? 'block' : 'none'; ?>">
            <h3>تنظیمات فراز پیامک (ایران پیامک)</h3>
            <table class="form-table">
                <tr>
                    <th>API Key</th>
                    <td><input type="text" name="rf_farazsms_settings[api_key]" value="<?php echo esc_attr( $s['api_key'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>شماره خط (Line Number)</th>
                    <td>
                        <input type="text" name="rf_farazsms_settings[line_number]" value="<?php echo esc_attr( $s['line_number'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">مثال: 50002191307530</p>
                    </td>
                </tr>
                <tr>
                    <th>کد پترن (Pattern Code)</th>
                    <td>
                        <input type="text" name="rf_farazsms_settings[pattern_code]" value="<?php echo esc_attr( $s['pattern_code'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">مثال: 7690GmBuab</p>
                    </td>
                </tr>
                <tr>
                    <th>نام متغیر OTP</th>
                    <td>
                        <input type="text" name="rf_farazsms_settings[otp_variable]" value="<?php echo esc_attr( $s['otp_variable'] ?? 'var' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">نام متغیر در پترن که کد OTP در آن قرار می‌گیرد (پیش‌فرض: var)</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- رایگان SMS -->
        <?php $s = get_option( 'rf_freesms_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-freesms" style="display:<?php echo $provider === 'freesms' ? 'block' : 'none'; ?>">
            <h3>تنظیمات رایگان SMS</h3>
            <table class="form-table">
                <tr>
                    <th>کد دسترسی (AccessHash)</th>
                    <td>
                        <input type="text" name="rf_freesms_settings[access_hash]" value="<?php echo esc_attr( $s['access_hash'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">کد دسترسی از پنل رایگان SMS</p>
                    </td>
                </tr>
                <tr>
                    <th>شناسه پترن (PatternId)</th>
                    <td>
                        <input type="text" name="rf_freesms_settings[pattern_id]" value="<?php echo esc_attr( $s['pattern_id'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">شناسه پترن ساخته شده در پنل</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- قاصدک پیامک -->
        <?php $s = get_option( 'rf_qasedaksms_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-qasedaksms" style="display:<?php echo $provider === 'qasedaksms' ? 'block' : 'none'; ?>">
            <h3>تنظیمات قاصدک پیامک</h3>
            <table class="form-table">
                <tr>
                    <th>API Key</th>
                    <td><input type="text" name="rf_qasedaksms_settings[apikey]" value="<?php echo esc_attr( $s['apikey'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>نام قالب (Template)</th>
                    <td>
                        <input type="text" name="rf_qasedaksms_settings[template]" value="<?php echo esc_attr( $s['template'] ?? '' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">عنوان قالبی که در پنل ایجاد کرده‌اید (مثال: verifycode)</p>
                    </td>
                </tr>
                <tr>
                    <th>نام پارامتر OTP</th>
                    <td>
                        <input type="text" name="rf_qasedaksms_settings[param1_name]" value="<?php echo esc_attr( $s['param1_name'] ?? 'param1' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">نام پارامتری که کد OTP در آن قرار می‌گیرد (پیش‌فرض: param1)</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- SMS.ir -->
        <?php $s = get_option( 'rf_smsir_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-smsir" style="display:<?php echo $provider === 'smsir' ? 'block' : 'none'; ?>">
            <h3>تنظیمات SMS.ir</h3>
            <table class="form-table">
                <tr>
                    <th>API Key</th>
                    <td><input type="text" name="rf_smsir_settings[api_key]" value="<?php echo esc_attr( $s['api_key'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>Template ID</th>
                    <td><input type="text" name="rf_smsir_settings[template_id]" value="<?php echo esc_attr( $s['template_id'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>نام پارامتر کد</th>
                    <td>
                        <input type="text" name="rf_smsir_settings[parameter_name]" value="<?php echo esc_attr( $s['parameter_name'] ?? 'Code' ); ?>" class="regular-text" dir="ltr">
                        <p class="description">نام پارامتری که کد OTP در آن قرار می‌گیرد (پیش‌فرض: Code)</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- آموت پیامک -->
        <?php $s = get_option( 'rf_amootsms_settings', [] ); ?>
        <div class="rf-provider-settings" id="rf-provider-amootsms" style="display:<?php echo $provider === 'amootsms' ? 'block' : 'none'; ?>">
            <h3>تنظیمات آموت پیامک</h3>
            <table class="form-table">
                <tr>
                    <th>Token</th>
                    <td><input type="text" name="rf_amootsms_settings[token]" value="<?php echo esc_attr( $s['token'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>کد الگو (Pattern Code ID)</th>
                    <td><input type="text" name="rf_amootsms_settings[pattern_code_id]" value="<?php echo esc_attr( $s['pattern_code_id'] ?? '' ); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>تعداد پارامترها</th>
                    <td>
                        <input type="number" name="rf_amootsms_settings[pattern_params_count]" value="<?php echo esc_attr( $s['pattern_params_count'] ?? '1' ); ?>" class="small-text" min="1" max="10">
                        <p class="description">تعداد پارامترهای الگوی شما (معمولاً 1 برای کد OTP)</p>
                    </td>
                </tr>
                <tr>
                    <th>ترتیب پارامتر کد OTP</th>
                    <td>
                        <input type="number" name="rf_amootsms_settings[otp_param_position]" value="<?php echo esc_attr( $s['otp_param_position'] ?? '1' ); ?>" class="small-text" min="1" max="10">
                        <p class="description">کد OTP در کدام پارامتر الگو قرار می‌گیرد؟ (پیش‌فرض: 1)</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="form-table">
            <tr>
                <th>تست پیامک</th>
                <td>
                    <input type="text" id="rf-test-sms-phone" placeholder="09123456789" class="regular-text" dir="ltr">
                    <button type="button" class="button" id="rf-test-sms">ارسال تست</button>
                    <span id="rf-test-sms-result"></span>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary rf-save-btn">ذخیره تنظیمات</button>
            <span class="rf-save-status"></span>
        </p>
    </form>
</div>

<script>
function rfShowProviderSettings(provider) {
    document.querySelectorAll('.rf-provider-settings').forEach(function(el) { el.style.display = 'none'; });
    var el = document.getElementById('rf-provider-' + provider);
    if (el) el.style.display = 'block';
}
</script>
