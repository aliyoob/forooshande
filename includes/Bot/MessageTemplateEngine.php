<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Helpers\{PersianDate, PriceFormatter};

class MessageTemplateEngine {

    /**
     * Default values for all message templates.
     * Used as fallback when an option is missing or empty.
     */
    public static function defaults(): array {
        return [
            'rf_msg_welcome'         => "سلام {first_name} عزیز! 👋\nبه فروشگاه {shop_name} خوش آمدید.\nاز منوی زیر استفاده کنید:",
            'rf_msg_request_phone'   => "📱 لطفاً شماره تلفن خود را با دکمه زیر ارسال کنید:",
            'rf_msg_order_status'    => "📦 سفارش شماره #{order_id}\nوضعیت: {order_status}\nتاریخ: {date}",
            'rf_msg_tracking'        => "📮 کد رهگیری سفارش #{order_id}:\nشرکت پستی: {shipping_company}\nکد رهگیری: {tracking_code}",
            'rf_msg_new_order_admin' => "🔔 سفارش جدید #{order_id}\n👤 {customer_name}\n📱 {customer_phone}\n💰 مبلغ: {order_total}\n📋 محصولات:\n{products_list}",
            'rf_msg_product_update'  => "🔄 محصول بروزرسانی شد:\n📦 {product_name}\n💰 قیمت: {product_price}\n🔗 {product_url}",
            'rf_msg_product_display' => "📦 {product_name}\n💰 قیمت: {product_price}\n📊 موجودی: {stock_status}\n📝 {product_short_description}",
            'rf_msg_order_created'   => "✅ سفارش شما ثبت شد!\nشماره سفارش: #{order_id}\nمبلغ: {order_total}\n🙏 با تشکر از خرید شما",
            'rf_msg_otp'             => "🔐 کد تأیید شما: {otp_code}\nاین کد تا {otp_expire} دقیقه معتبر است.",
            'rf_msg_abandoned_cart'  => "🛒 سبد خرید شما منتظر شماست!\n{first_name} عزیز، محصولاتی در سبد خرید شما باقی مانده.",
            'rf_msg_stock_alert'     => "🔔 محصول «{product_name}» که منتظرش بودید، موجود شد!\n💰 قیمت: {product_price}",
            'rf_msg_payment_success' => "✅ پرداخت سفارش #{order_id} با موفقیت انجام شد.\nمبلغ: {order_total}",
            'rf_msg_payment_failed'  => "❌ پرداخت سفارش #{order_id} ناموفق بود.\nلطفاً دوباره تلاش کنید.",
        ];
    }

    /**
     * Render a message template with variables
     */
    public static function render( string $optionKey, array $vars = [] ): string {
        $templateDefaults = self::defaults();
        $template = get_option( $optionKey, $templateDefaults[ $optionKey ] ?? '' );
        if ( empty( $template ) ) {
            $template = $templateDefaults[ $optionKey ] ?? '';
        }
        if ( empty( $template ) ) return '';

        // Default variables
        $defaults = [
            '{shop_name}' => get_option( 'rf_shop_name', get_bloginfo( 'name' ) ),
            '{bot_name}'  => get_option( 'rf_bot_username', '' ),
            '{date}'      => PersianDate::now(),
        ];

        $vars = array_merge( $defaults, $vars );

        return strtr( $template, $vars );
    }

    /**
     * Build order variables
     */
    public static function orderVars( \WC_Order $order ): array {
        $items_list = '';
        foreach ( $order->get_items() as $item ) {
            $items_list .= sprintf(
                "📦 %s × %d - %s\n",
                $item->get_name(),
                $item->get_quantity(),
                PriceFormatter::format( $item->get_total() )
            );
        }

        $statuses = wc_get_order_statuses();
        $status_label = $statuses[ 'wc-' . $order->get_status() ] ?? $order->get_status();

        return [
            '{order_id}'         => $order->get_id(),
            '{order_status}'     => $status_label,
            '{order_total}'      => PriceFormatter::format( $order->get_total() ),
            '{customer_name}'    => $order->get_formatted_billing_full_name(),
            '{customer_phone}'   => $order->get_billing_phone(),
            '{products_list}'    => $items_list,
            '{date}'             => PersianDate::format( $order->get_date_created()->date( 'Y-m-d H:i:s' ) ),
            '{tracking_code}'    => $order->get_meta( '_rf_tracking_code' ) ?: '-',
            '{shipping_company}' => $order->get_meta( '_rf_shipping_company' ) ?: '-',
        ];
    }

    /**
     * Build product variables
     */
    public static function productVars( \WC_Product $product ): array {
        $stock_status = $product->is_in_stock() ? '✅ موجود' : '❌ ناموجود';
        if ( $product->is_in_stock() && $product->get_stock_quantity() !== null ) {
            $stock_status .= ' (' . PersianDate::toPersianDigits( (string) $product->get_stock_quantity() ) . ' عدد)';
        }

        // Handle variable product price range
        if ( $product->is_type( 'variable' ) ) {
            $min_price = $product->get_variation_price( 'min' );
            $max_price = $product->get_variation_price( 'max' );
            if ( $min_price === $max_price ) {
                $price_text = PriceFormatter::format( $min_price );
            } else {
                $price_text = PriceFormatter::format( $min_price ) . ' ~ ' . PriceFormatter::format( $max_price );
            }
        } elseif ( $product->is_on_sale() && $product->get_regular_price() ) {
            $price_text = '<s>' . PriceFormatter::format( $product->get_regular_price() ) . '</s> '
                        . PriceFormatter::format( $product->get_sale_price() );

            $regular = (float) $product->get_regular_price();
            $sale    = (float) $product->get_sale_price();
            if ( $regular > 0 ) {
                $discount = round( ( ( $regular - $sale ) / $regular ) * 100 );
                $price_text .= ' (' . PersianDate::toPersianDigits( (string) $discount ) . '% تخفیف)';
            }
        } else {
            $price_text = PriceFormatter::format( $product->get_price() );
        }

        $platform     = get_option( 'rf_platform', 'telegram' );
        $bot_username = get_option( 'rf_bot_username', '' );
        if ( $platform === 'bale' ) {
            $product_url = "https://ble.ir/{$bot_username}?start=product_{$product->get_id()}";
        } else {
            $product_url = "https://t.me/{$bot_username}?start=product_{$product->get_id()}";
        }

        return [
            '{product_name}'              => htmlspecialchars( $product->get_name(), ENT_QUOTES, 'UTF-8' ),
            '{product_price}'             => $price_text,
            '{product_regular_price}'     => PriceFormatter::format( $product->get_regular_price() ),
            '{product_sale_price}'        => $product->get_sale_price() ? PriceFormatter::format( $product->get_sale_price() ) : '',
            '{stock_status}'              => $stock_status,
            '{product_short_description}' => htmlspecialchars( wp_strip_all_tags( $product->get_short_description() ), ENT_QUOTES, 'UTF-8' ),
            '{product_url}'               => $product_url,
            '{product_id}'                => $product->get_id(),
            '{product_sku}'               => $product->get_sku(),
        ];
    }

    /**
     * Build user variables
     */
    public static function userVars( object $botUser ): array {
        return [
            '{first_name}' => $botUser->first_name ?? '',
            '{last_name}'  => $botUser->last_name ?? '',
            '{phone}'      => $botUser->phone ?? '',
            '{chat_id}'    => $botUser->chat_id ?? '',
        ];
    }

    /**
     * Get preview data for settings page
     */
    public static function previewData(): array {
        return [
            '{first_name}'      => 'علی',
            '{last_name}'       => 'رضایی',
            '{phone}'           => '۰۹۱۲۳۴۵۶۷۸۹',
            '{shop_name}'       => get_option( 'rf_shop_name', 'فروشگاه من' ),
            '{bot_name}'        => get_option( 'rf_bot_username', 'mybot' ),
            '{order_id}'        => '۱۲۳۴',
            '{order_status}'    => 'تکمیل شده',
            '{order_total}'     => '۱,۲۵۰,۰۰۰ تومان',
            '{customer_name}'   => 'علی رضایی',
            '{customer_phone}'  => '۰۹۱۲۳۴۵۶۷۸۹',
            '{products_list}'   => "📦 شال دورگان × ۲\n📦 ایرپاد پرو × ۱",
            '{date}'            => PersianDate::now(),
            '{tracking_code}'   => '۱۲۳۴۵۶۷۸۹۰',
            '{shipping_company}'=> 'پست پیشتاز',
            '{product_name}'    => 'شال دورگان طرح‌دار',
            '{product_price}'   => '۱۲۵,۰۰۰ تومان',
            '{stock_status}'    => '✅ موجود (۵ عدد)',
            '{product_short_description}' => 'شال نخی درجه یک با طرح‌های متنوع',
            '{product_url}'     => 'https://t.me/mybot?start=product_123',
            '{otp_code}'        => '۱۲۳۴۵',
            '{otp_expire}'      => '۵',
        ];
    }
}
