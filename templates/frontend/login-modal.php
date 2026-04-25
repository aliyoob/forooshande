<?php if ( ! defined( 'ABSPATH' ) ) exit;
$loginMethods   = get_option( 'rf_login_methods', 'otp' );
$otpMethod      = get_option( 'rf_otp_method', 'sms' );
$colorPrimary   = esc_attr( get_option( 'rf_login_color_primary', '#6366f1' ) );
$colorSecondary = esc_attr( get_option( 'rf_login_color_secondary', '#8b5cf6' ) );
$loginIcon      = get_option( 'rf_login_icon', 'user' );
$iconSvgs = [
    'user'        => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'lock'        => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'shield'      => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'login'       => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
    'key'         => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
    'store'       => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 7v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-3-5z"/><line x1="3" y1="7" x2="21" y2="7"/><path d="M16 11a4 4 0 0 1-8 0"/></svg>',
    'fingerprint' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12C2 6.5 6.5 2 12 2a10 10 0 0 1 8 4"/><path d="M5 19.5C5.5 18 6 15 6 12c0-3.5 2.5-6 6-6 3 0 5.5 2 6 5"/><path d="M12 12v4s0 2-1 3.5"/><path d="M8.5 16.5c0 1-.5 3-1.5 4.5"/><path d="M17 14c.5 1 .5 3 0 5"/></svg>',
];
$selectedIcon = $iconSvgs[ $loginIcon ] ?? $iconSvgs['user'];
$botMethodIcon = get_option( 'rf_login_bot_method_icon', '' );

$t = [
    'title'           => get_option( 'rf_login_text_title', 'ورود / ثبت‌نام' ),
    'subtitle'        => get_option( 'rf_login_text_subtitle', 'به حساب کاربری خود وارد شوید' ),
    'phone_label'     => get_option( 'rf_login_text_phone_label', 'شماره موبایل' ),
    'phone_ph'        => get_option( 'rf_login_text_phone_placeholder', '09123456789' ),
    'send_otp'        => get_option( 'rf_login_text_send_otp', 'دریافت کد تأیید' ),
    'otp_label'       => get_option( 'rf_login_text_otp_label', 'کد تأیید ارسال شده را وارد کنید' ),
    'verify_btn'      => get_option( 'rf_login_text_verify_btn', 'تأیید و ورود' ),
    'resend'          => get_option( 'rf_login_text_resend', 'ارسال مجدد کد' ),
    'switch_password' => get_option( 'rf_login_text_switch_password', 'ورود با رمز عبور' ),
    'switch_otp'      => get_option( 'rf_login_text_switch_otp', 'ورود با کد تأیید' ),
    'password_label'  => get_option( 'rf_login_text_password_label', 'رمز عبور' ),
    'login_btn'       => get_option( 'rf_login_text_login_btn', 'ورود' ),
    'method_sms'      => get_option( 'rf_login_text_method_sms', 'ارسال با پیامک' ),
    'method_bot'      => get_option( 'rf_login_text_method_bot', 'ارسال با ربات (بله/تلگرام)' ),
    'bot_info'        => get_option( 'rf_login_text_bot_info', 'برای دریافت کد تأیید از طریق ربات، لازم است ابتدا ربات ما را در بله/تلگرام استارت کرده باشید.' ),
    'bot_link'        => get_option( 'rf_login_text_bot_link', '' ),
    'bot_link_text'   => get_option( 'rf_login_text_bot_link_text', 'استارت ربات' ),
];
?>
<div class="rf-login-modal-overlay" id="rf-login-overlay" style="display:none;"
     dir="rtl">
    <div class="rf-login-card"
         style="--rf-primary: <?php echo $colorPrimary; ?>; --rf-secondary: <?php echo $colorSecondary; ?>;">
        <button type="button" class="rf-login-close">&times;</button>

        <div class="rf-login-header">
            <div class="rf-login-icon">
                <?php echo $selectedIcon; ?>
            </div>
            <h2 class="rf-login-title"><?php echo esc_html( $t['title'] ); ?></h2>
            <p class="rf-login-subtitle"><?php echo esc_html( $t['subtitle'] ); ?></p>
        </div>

        <?php if ( in_array( $otpMethod, [ 'bot', 'both' ], true ) && ! empty( $t['bot_info'] ) ) : ?>
        <div class="rf-login-bot-info">
            <div class="rf-bot-info-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <div class="rf-bot-info-content">
                <p><?php echo esc_html( $t['bot_info'] ); ?></p>
                <?php if ( ! empty( $t['bot_link'] ) ) : ?>
                    <a href="<?php echo esc_url( $t['bot_link'] ); ?>" target="_blank" rel="noopener" class="rf-bot-info-link">
                        <?php echo esc_html( $t['bot_link_text'] ); ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $loginMethods !== 'password' ) : ?>
        <div id="rf-login-step-phone" class="rf-login-step">
            <div class="rf-input-group">
                <label for="rf-login-phone" class="rf-input-label"><?php echo esc_html( $t['phone_label'] ); ?></label>
                <div class="rf-input-wrapper">
                    <span class="rf-input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    </span>
                    <input type="tel" id="rf-login-phone" maxlength="11" placeholder="<?php echo esc_attr( $t['phone_ph'] ); ?>" dir="ltr" autocomplete="tel">
                </div>
            </div>

            <?php if ( $otpMethod === 'both' ) : ?>
            <div class="rf-otp-method-select">
                <label class="rf-method-option">
                    <input type="radio" name="rf_otp_method" value="sms" checked>
                    <span class="rf-method-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span><?php echo esc_html( $t['method_sms'] ); ?></span>
                    </span>
                </label>
                <label class="rf-method-option">
                    <input type="radio" name="rf_otp_method" value="bot">
                    <span class="rf-method-box">
                        <?php if ( ! empty( $botMethodIcon ) ) : ?>
                            <img src="<?php echo esc_url( $botMethodIcon ); ?>" alt="" class="rf-method-icon-img">
                        <?php else : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        <?php endif; ?>
                        <span><?php echo esc_html( $t['method_bot'] ); ?></span>
                    </span>
                </label>
            </div>
            <?php endif; ?>

            <button type="button" id="rf-send-otp" class="rf-btn rf-btn-primary">
                <span><?php echo esc_html( $t['send_otp'] ); ?></span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>

            <?php if ( $loginMethods === 'both' ) : ?>
            <div class="rf-login-divider"><span>یا</span></div>
            <a href="#" id="rf-switch-password" class="rf-btn rf-btn-outline"><?php echo esc_html( $t['switch_password'] ); ?></a>
            <?php endif; ?>
            <div class="rf-login-error" id="rf-phone-error"></div>
        </div>
        <?php endif; ?>

        <div id="rf-login-step-otp" class="rf-login-step" style="display:none;">
            <div class="rf-otp-sent-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span>کد ارسال شد</span>
            </div>
            <div class="rf-input-group">
                <label for="rf-login-otp" class="rf-input-label"><?php echo esc_html( $t['otp_label'] ); ?></label>
                <div class="rf-input-wrapper">
                    <span class="rf-input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="text" id="rf-login-otp" maxlength="8" placeholder="•••••" dir="ltr" autocomplete="one-time-code" class="rf-otp-input">
                </div>
            </div>
            <button type="button" id="rf-verify-otp" class="rf-btn rf-btn-primary">
                <span><?php echo esc_html( $t['verify_btn'] ); ?></span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            <div class="rf-login-timer-row">
                <span id="rf-otp-timer" class="rf-timer-badge"></span>
                <a href="#" id="rf-resend-otp" class="rf-resend-link" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    <?php echo esc_html( $t['resend'] ); ?>
                </a>
            </div>
            <div class="rf-login-error" id="rf-otp-error"></div>
        </div>

        <div id="rf-login-step-password" class="rf-login-step" style="display:none;">
            <div class="rf-input-group">
                <label for="rf-login-phone-pw" class="rf-input-label"><?php echo esc_html( $t['phone_label'] ); ?></label>
                <div class="rf-input-wrapper">
                    <span class="rf-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
                    <input type="tel" id="rf-login-phone-pw" maxlength="11" placeholder="<?php echo esc_attr( $t['phone_ph'] ); ?>" dir="ltr">
                </div>
            </div>
            <div class="rf-input-group">
                <label for="rf-login-password" class="rf-input-label"><?php echo esc_html( $t['password_label'] ); ?></label>
                <div class="rf-input-wrapper">
                    <span class="rf-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <input type="password" id="rf-login-password" placeholder="••••••••" dir="ltr">
                </div>
            </div>
            <button type="button" id="rf-login-pw-btn" class="rf-btn rf-btn-primary">
                <span><?php echo esc_html( $t['login_btn'] ); ?></span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            </button>
            <?php if ( $loginMethods === 'both' ) : ?>
            <div class="rf-login-divider"><span>یا</span></div>
            <a href="#" id="rf-switch-otp" class="rf-btn rf-btn-outline"><?php echo esc_html( $t['switch_otp'] ); ?></a>
            <?php endif; ?>
            <div class="rf-login-error" id="rf-pw-error"></div>
        </div>

        <div id="rf-login-loading" class="rf-login-step" style="display:none;">
            <div class="rf-loading-spinner">
                <div class="rf-spinner-ring"></div>
                <p>لطفاً صبر کنید...</p>
            </div>
        </div>
    </div>
</div>
