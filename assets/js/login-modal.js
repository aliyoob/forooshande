/**
 * Robot Forooshande - Login Modal JS
 */
(function ($) {
    'use strict';

    // rfLogin is set by wp_localize_script (ajaxUrl, nonce, otpMethod, autoShow)
    var cfg = window.rfLogin || {};

    var app = {
        timerInterval: null,

        init: function () {
            this.bindPhoneStep();
            this.bindOtpStep();
            this.bindPasswordStep();
            this.bindSwitchers();
            this.bindClose();
        },

        bindPhoneStep: function () {
            var self = this;
            $(document).on('click', '#rf-send-otp', function () {
                var phone = $('#rf-login-phone').val().trim();
                if (!/^09\d{9}$/.test(phone)) {
                    $('#rf-phone-error').text('شماره موبایل نامعتبر است.');
                    return;
                }

                var method = cfg.otpMethod || 'sms';
                var $radio = $('input[name="rf_otp_method"]:checked');
                if ($radio.length) {
                    method = $radio.val();
                }

                self.showLoading();
                $.post(cfg.ajaxUrl, {
                    action: 'rf_login_send_otp',
                    phone: phone,
                    method: method,
                    nonce: cfg.nonce
                }, function (res) {
                    self.hideLoading();
                    if (res.success) {
                        $('#rf-login-step-phone').hide();
                        $('#rf-login-step-otp').show();
                        self.startTimer(res.data.expiry || 120);
                    } else {
                        $('#rf-login-step-phone').show();
                        $('#rf-phone-error').text(res.data && res.data.message ? res.data.message : 'خطا در ارسال کد.');
                    }
                }).fail(function () {
                    self.hideLoading();
                    $('#rf-login-step-phone').show();
                    $('#rf-phone-error').text('خطای شبکه');
                });
            });
        },

        bindOtpStep: function () {
            var self = this;
            $(document).on('click', '#rf-verify-otp', function () {
                var phone = $('#rf-login-phone').val().trim();
                var otp = $('#rf-login-otp').val().trim();

                if (!otp) {
                    $('#rf-otp-error').text('کد تأیید را وارد کنید.');
                    return;
                }

                self.showLoading();
                $.post(cfg.ajaxUrl, {
                    action: 'rf_login_verify_otp',
                    phone: phone,
                    code: otp,
                    nonce: cfg.nonce
                }, function (res) {
                    self.hideLoading();
                    if (res.success) {
                        if (res.data && res.data.redirect) {
                            window.location.href = res.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        $('#rf-login-step-otp').show();
                        $('#rf-otp-error').text(res.data && res.data.message ? res.data.message : 'کد نامعتبر است.');
                    }
                }).fail(function () {
                    self.hideLoading();
                    $('#rf-login-step-otp').show();
                    $('#rf-otp-error').text('خطای شبکه');
                });
            });

            $(document).on('click', '#rf-resend-otp', function (e) {
                e.preventDefault();
                $('#rf-login-step-otp').hide();
                $('#rf-login-step-phone').show();
                clearInterval(self.timerInterval);
            });
        },

        bindPasswordStep: function () {
            var self = this;
            $(document).on('click', '#rf-login-pw-btn', function () {
                var phone = $('#rf-login-phone-pw').val().trim();
                var password = $('#rf-login-password').val();

                if (!/^09\d{9}$/.test(phone)) {
                    $('#rf-pw-error').text('شماره موبایل نامعتبر است.');
                    return;
                }
                if (!password) {
                    $('#rf-pw-error').text('رمز عبور را وارد کنید.');
                    return;
                }

                self.showLoading();
                $.post(cfg.ajaxUrl, {
                    action: 'rf_login_password',
                    phone: phone,
                    password: password,
                    nonce: cfg.nonce
                }, function (res) {
                    self.hideLoading();
                    if (res.success) {
                        if (res.data && res.data.redirect) {
                            window.location.href = res.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        $('#rf-login-step-password').show();
                        $('#rf-pw-error').text(res.data && res.data.message ? res.data.message : 'اطلاعات نادرست است.');
                    }
                }).fail(function () {
                    self.hideLoading();
                    $('#rf-login-step-password').show();
                    $('#rf-pw-error').text('خطای شبکه');
                });
            });
        },

        bindSwitchers: function () {
            $(document).on('click', '#rf-switch-password', function (e) {
                e.preventDefault();
                $('.rf-login-step').hide();
                $('#rf-login-step-password').show();
            });
            $(document).on('click', '#rf-switch-otp', function (e) {
                e.preventDefault();
                $('.rf-login-step').hide();
                $('#rf-login-step-phone').show();
            });
        },

        bindClose: function () {
            $(document).on('click', '.rf-login-close', function () {
                $('#rf-login-overlay').hide();
            });
            $(document).on('click', '#rf-login-overlay', function (e) {
                if (e.target === e.currentTarget) {
                    $(this).hide();
                }
            });
        },

        startTimer: function (seconds) {
            var self = this;
            var $timer = $('#rf-otp-timer');
            var $resend = $('#rf-resend-otp');
            var remaining = seconds;

            $resend.hide();
            $timer.show();

            var format = function (s) {
                var m = Math.floor(s / 60);
                var sec = s % 60;
                return (m > 0 ? m + ':' : '') + (sec < 10 ? '0' : '') + sec;
            };

            $timer.text(format(remaining));

            this.timerInterval = setInterval(function () {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(self.timerInterval);
                    $timer.hide();
                    $resend.show();
                } else {
                    $timer.text(format(remaining));
                }
            }, 1000);
        },

        showLoading: function () {
            $('.rf-login-step').hide();
            $('#rf-login-loading').show();
        },

        hideLoading: function () {
            $('#rf-login-loading').hide();
        }
    };

    $(document).ready(function () {
        app.init();

        if ($('#rf-login-overlay').length && cfg.autoShow) {
            $('#rf-login-overlay').show();
        }
    });

})(jQuery);
