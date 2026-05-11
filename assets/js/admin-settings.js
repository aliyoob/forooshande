/**
 * Robot Forooshande - Admin Settings JS
 */
(function ($) {
    'use strict';

    const rfAdmin = {
        init() {
            this.bindSaveForm();
            this.bindTestBot();
            this.bindWebhook();
            this.bindTestSMS();
            this.bindClearLogs();
            this.bindExportImport();
            this.bindPreviewMsg();
            this.loadPhoneMetaKeys();
            this.initColorPickers();
            this.initMediaUploaders();
        },

        initColorPickers() {
            if ($.fn.wpColorPicker) {
                $('.rf-color-picker').wpColorPicker();
            }
        },

        initMediaUploaders() {
            var botFrame;
            $('#rf-upload-bot-icon').on('click', function (e) {
                e.preventDefault();
                if (botFrame) { botFrame.open(); return; }
                botFrame = wp.media({
                    title: 'انتخاب آیکون ربات',
                    button: { text: 'انتخاب' },
                    multiple: false,
                    library: { type: 'image' }
                });
                botFrame.on('select', function () {
                    var url = botFrame.state().get('selection').first().toJSON().url;
                    $('#rf_login_bot_method_icon').val(url);
                    $('#rf-bot-icon-preview').show().find('img').attr('src', url);
                    $('#rf-remove-bot-icon').show();
                });
                botFrame.open();
            });
            $('#rf-remove-bot-icon').on('click', function () {
                $('#rf_login_bot_method_icon').val('');
                $('#rf-bot-icon-preview').hide();
                $(this).hide();
            });
        },

        bindSaveForm() {
            $(document).on('submit', '#rf-settings-form', function (e) {
                e.preventDefault();
                const $btn = $(this).find('.rf-save-btn');
                const $status = $(this).find('.rf-save-status');
                $btn.prop('disabled', true);

                $.ajax({
                    url: rfSettings.ajaxUrl,
                    method: 'POST',
                    data: $(this).serialize() + '&action=rf_save_settings',
                    success(res) {
                        $status.text(res.success ? '✅ ذخیره شد' : '❌ خطا').attr('class', 'rf-save-status ' + (res.success ? 'success' : 'error'));
                        $btn.prop('disabled', false);
                        setTimeout(() => $status.text(''), 3000);
                    },
                    error() {
                        $status.text('❌ خطای شبکه').attr('class', 'rf-save-status error');
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        bindTestBot() {
            $('#rf-test-bot').on('click', function () {
                const $result = $('#rf-test-bot-result');
                $result.text('⏳ در حال تست...');
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_test_bot',
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    $result.text(res.success ? '✅ ' + res.data.bot_name : '❌ ' + (res.data?.message || 'خطا'));
                });
            });
        },

        bindWebhook() {
            $('#rf-set-webhook').on('click', function () {
                const $result = $('#rf-webhook-result');
                $result.text('⏳ ...');
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_set_webhook',
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    $result.text(res.success ? '✅ فعال شد' : '❌ ' + (res.data?.message || 'خطا'));
                    if (res.success) location.reload();
                });
            });

            $('#rf-delete-webhook').on('click', function () {
                const $result = $('#rf-webhook-result');
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_delete_webhook',
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    $result.text(res.success ? '✅ غیرفعال شد' : '❌ خطا');
                    if (res.success) location.reload();
                });
            });
        },

        bindTestSMS() {
            $('#rf-test-sms').on('click', function () {
                const phone = $('#rf-test-sms-phone').val();
                const $result = $('#rf-test-sms-result');
                if (!phone) { $result.text('شماره وارد کنید'); return; }

                $result.text('⏳ ...');
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_test_sms',
                    phone: phone,
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    $result.text(res.success ? '✅ ارسال شد' : '❌ ' + (res.data?.message || 'خطا'));
                });
            });
        },

        bindClearLogs() {
            $(document).on('click', '#rf-clear-logs', function () {
                if (!confirm('آیا مطمئن هستید؟')) return;
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_clear_logs',
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    if (res.success) location.reload();
                });
            });
        },

        bindExportImport() {
            $('#rf-export-settings').on('click', function () {
                window.location = rfSettings.ajaxUrl + '?action=rf_export_settings&_wpnonce=' + rfSettings.nonce;
            });

            $('#rf-import-settings').on('click', function () {
                const file = $('#rf-import-file')[0].files[0];
                const $result = $('#rf-import-result');
                if (!file) { $result.text('فایلی انتخاب نشده'); return; }

                const reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        const data = JSON.parse(e.target.result);
                        $.post(rfSettings.ajaxUrl, {
                            action: 'rf_import_settings',
                            settings: JSON.stringify(data),
                            _wpnonce: rfSettings.nonce
                        }, function (res) {
                            $result.text(res.success ? '✅ بارگذاری شد' : '❌ خطا');
                            if (res.success) setTimeout(() => location.reload(), 1000);
                        });
                    } catch {
                        $result.text('❌ فایل نامعتبر');
                    }
                };
                reader.readAsText(file);
            });
        },

        bindPreviewMsg() {
            $(document).on('click', '.rf-preview-msg', function () {
                const key = $(this).data('key');
                $.post(rfSettings.ajaxUrl, {
                    action: 'rf_preview_message',
                    key: key,
                    _wpnonce: rfSettings.nonce
                }, function (res) {
                    if (res.success) {
                        $('.rf-preview-body').text(res.data.preview);
                        $('#rf-preview-modal').show();
                    }
                });
            });

            $(document).on('click', '.rf-preview-close, .rf-preview-overlay', function () {
                $('#rf-preview-modal').hide();
            });
        },

        loadPhoneMetaKeys() {
            const $select = $('#rf_phone_meta_key');
            if (!$select.length) return;

            const current = rfSettings.phoneMetaKey || '';

            $.post(rfSettings.ajaxUrl, {
                action: 'rf_get_user_meta_keys',
                _wpnonce: rfSettings.nonce
            }, function (res) {
                if (!res.success) return;
                res.data.keys.forEach(function (key) {
                    const selected = key === current ? ' selected' : '';
                    $select.append('<option value="' + key + '"' + selected + '>' + key + '</option>');
                });
            });
        }
    };

    $(document).ready(function () {
        rfAdmin.init();
        rfAdmin.initShipping();
    });

    // Shipping methods manager (tab-shipping.php)
    rfAdmin.initShipping = function () {
        if ( ! $('#rf-shipping-table').length ) return;

        // Add new row
        $(document).on('click', '#rf-add-shipping-row', function () {
            $('.rf-shipping-empty-row').remove();
            const idx = $('#rf-shipping-rows tr').length;
            const row = `<tr class="rf-shipping-row" data-index="${idx}">
                <td class="rf-row-handle" style="cursor:grab;color:#94a3b8;text-align:center;">☰</td>
                <td><input type="text" class="rf-shipping-title regular-text" placeholder="مثال: پیک موتوری" dir="rtl"></td>
                <td>
                    <input type="number" class="rf-shipping-cost small-text" value="0" min="0" step="1000" placeholder="0" style="width:140px;">
                    <span style="font-size:11px;color:#64748b;margin-right:4px;">تومان</span>
                </td>
                <td>
                    <button type="button" class="button rf-remove-shipping-row" title="حذف">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>`;
            $('#rf-shipping-rows').append(row);
            $('#rf-shipping-rows .rf-shipping-title').last().focus();
        });

        // Remove row
        $(document).on('click', '.rf-remove-shipping-row', function () {
            $(this).closest('tr').remove();
            if ( ! $('#rf-shipping-rows tr').length ) {
                $('#rf-shipping-rows').append(
                    '<tr class="rf-shipping-empty-row"><td colspan="4" style="text-align:center;color:#94a3b8;padding:28px;">هیچ روش ارسالی تعریف نشده. از دکمه زیر استفاده کنید.</td></tr>'
                );
            }
        });

        // Save shipping methods
        $(document).on('click', '#rf-save-shipping', function () {
            const $btn    = $(this);
            const $status = $('#rf-shipping-save-status');
            const methods = [];

            $('#rf-shipping-rows tr.rf-shipping-row').each(function () {
                const title = $(this).find('.rf-shipping-title').val().trim();
                const cost  = parseFloat( $(this).find('.rf-shipping-cost').val() ) || 0;
                if ( title ) {
                    methods.push({ title: title, cost: cost });
                }
            });

            $btn.prop('disabled', true);
            $status.text('⏳ در حال ذخیره...').css('color', '#64748b');

            $.post(rfSettings.ajaxUrl, {
                action:    'rf_save_shipping_methods',
                methods:   JSON.stringify(methods),
                _wpnonce:  rfSettings.nonce
            }, function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    $status.text('✅ ذخیره شد').css('color', '#10b981');
                } else {
                    $status.text('❌ خطا در ذخیره').css('color', '#ef4444');
                }
                setTimeout(() => $status.text(''), 3000);
            }).fail(function () {
                $btn.prop('disabled', false);
                $status.text('❌ خطای شبکه').css('color', '#ef4444');
                setTimeout(() => $status.text(''), 3000);
            });
        });
    };

})(jQuery);
