<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rf-settings-page" dir="rtl">

    <div class="rf-shipping-header">
        <h3>روش‌های ارسال اختصاصی</h3>
        <p class="description">روش‌های حمل و نقلی که می‌خواهید در ربات نمایش داده شوند را در اینجا تعریف کنید.<br>هزینه ارسال به صورت خودکار به مبلغ سفارش اضافه می‌شود.</p>
    </div>

    <div id="rf-shipping-methods-wrap">
        <?php
        $methods = get_option( 'rf_custom_shipping_methods', [] );
        if ( ! is_array( $methods ) ) $methods = [];
        ?>

        <table class="rf-shipping-table widefat" id="rf-shipping-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>عنوان روش ارسال</th>
                    <th style="width:160px;">هزینه (تومان)</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody id="rf-shipping-rows">
                <?php if ( empty( $methods ) ) : ?>
                    <tr class="rf-shipping-empty-row">
                        <td colspan="4" style="text-align:center; color:#94a3b8; padding:28px;">
                            هیچ روش ارسالی تعریف نشده. از دکمه زیر استفاده کنید.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $methods as $i => $method ) : ?>
                        <tr class="rf-shipping-row" data-index="<?php echo esc_attr( $i ); ?>">
                            <td class="rf-row-handle" style="cursor:grab; color:#94a3b8; text-align:center;">☰</td>
                            <td>
                                <input type="text"
                                       class="rf-shipping-title regular-text"
                                       value="<?php echo esc_attr( $method['title'] ?? '' ); ?>"
                                       placeholder="مثال: پیک موتوری" dir="rtl">
                            </td>
                            <td>
                                <input type="number"
                                       class="rf-shipping-cost small-text"
                                       value="<?php echo esc_attr( (int) ( $method['cost'] ?? 0 ) ); ?>"
                                       min="0" step="1000" placeholder="0"
                                       style="width:140px;">
                                <span style="font-size:11px; color:#64748b; margin-right:4px;">تومان</span>
                            </td>
                            <td>
                                <button type="button" class="button rf-remove-shipping-row" title="حذف">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="rf-shipping-actions" style="margin-top:16px; display:flex; gap:12px; align-items:center;">
        <button type="button" class="button button-secondary" id="rf-add-shipping-row">
            ➕ افزودن روش جدید
        </button>
        <button type="button" class="button button-primary" id="rf-save-shipping">
            💾 ذخیره روش‌های ارسال
        </button>
        <span id="rf-shipping-save-status" style="font-size:13px;"></span>
    </div>

    <div style="margin-top:28px; padding:16px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
        <strong>📌 راهنما:</strong>
        <ul style="margin:8px 0 0 0; padding-right:18px; color:#475569; font-size:13px; line-height:2;">
            <li>هزینه <strong>۰</strong> را برای ارسال رایگان وارد کنید.</li>
            <li>این روش‌ها در ربات به کاربر نمایش داده می‌شوند و کاربر یکی را انتخاب می‌کند.</li>
            <li>هزینه انتخاب‌شده به مبلغ کل سفارش اضافه می‌شود.</li>
        </ul>
    </div>

</div>
