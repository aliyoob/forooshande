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
            'rf_msg_new_order_admin' => "🔔 سفارش جدید #{order_id}\n👤 {first_name} {last_name}\n📱 {phone}\n📍 آدرس: {address}\n📮 کدپستی: {postcode}\n💳 پرداخت: {payment_method}\n🚚 ارسال: {shipping_method}\n💰 مبلغ: {order_total}\n📝 توضیحات: {order_note}\n\n📋 محصولات:\n{products_list}",
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
            $line = '📦 ' . $item->get_name()
                  . ' × ' . PersianDate::toPersianDigits( (string) $item->get_quantity() )
                  . ' - ' . PriceFormatter::format( $item->get_total() );

            // Append variation attributes (size/color/…) on a new indented line
            $attrs = self::formatItemAttributes( $item );
            if ( $attrs !== '' ) {
                $line .= "\n   🏷 " . $attrs;
            }
            $items_list .= $line . "\n";
        }

        $statuses     = wc_get_order_statuses();
        $status_label = $statuses[ 'wc-' . $order->get_status() ] ?? $order->get_status();

        // Customer / billing fields — fall back to shipping if billing is empty
        $first_name  = $order->get_billing_first_name() ?: $order->get_shipping_first_name();
        $last_name   = $order->get_billing_last_name()  ?: $order->get_shipping_last_name();
        $full_name   = trim( $first_name . ' ' . $last_name );
        if ( $full_name === '' ) {
            $full_name = $order->get_formatted_billing_full_name();
        }
        $phone       = $order->get_billing_phone();
        $email       = $order->get_billing_email();

        // Address — prefer shipping address when set, otherwise billing
        $hasShipping = (bool) $order->get_shipping_address_1();
        $address_1   = $hasShipping ? $order->get_shipping_address_1() : $order->get_billing_address_1();
        $address_2   = $hasShipping ? $order->get_shipping_address_2() : $order->get_billing_address_2();
        $city        = $hasShipping ? $order->get_shipping_city()      : $order->get_billing_city();
        $state_code  = $hasShipping ? $order->get_shipping_state()     : $order->get_billing_state();
        $country     = $hasShipping ? $order->get_shipping_country()   : $order->get_billing_country();
        $postcode    = $hasShipping ? $order->get_shipping_postcode()  : $order->get_billing_postcode();
        $state_name  = ( $state_code && $country )
            ? ( WC()->countries->get_states( $country )[ $state_code ] ?? $state_code )
            : $state_code;

        $address_full = trim(
            ( $state_name ? $state_name . ' - ' : '' )
            . ( $city ? $city . ' - ' : '' )
            . trim( $address_1 . ' ' . $address_2 )
        );

        // Payment / shipping method labels
        $payment_method  = $order->get_payment_method_title() ?: $order->get_payment_method();
        $shipping_method = '';
        foreach ( $order->get_shipping_methods() as $ship ) {
            $shipping_method = $ship->get_method_title();
            break;
        }

        // Totals
        $shipping_total = (float) $order->get_shipping_total();
        $discount_total = (float) $order->get_discount_total();
        $subtotal       = (float) $order->get_subtotal();

        return [
            '{order_id}'          => $order->get_id(),
            '{order_status}'      => $status_label,
            '{order_total}'       => PriceFormatter::format( $order->get_total() ),
            '{order_subtotal}'    => PriceFormatter::format( $subtotal ),
            '{order_shipping}'    => $shipping_total > 0 ? PriceFormatter::format( $shipping_total ) : 'رایگان',
            '{order_discount}'    => $discount_total > 0 ? PriceFormatter::format( $discount_total ) : '۰',
            '{order_note}'        => $order->get_customer_note() ?: '-',
            '{customer_name}'     => $full_name ?: '-',
            '{customer_phone}'    => $phone ?: '-',
            '{customer_email}'    => $email ?: '-',
            '{first_name}'        => $first_name ?: '-',
            '{last_name}'         => $last_name ?: '-',
            '{phone}'             => $phone ?: '-',
            '{address}'           => $address_full ?: '-',
            '{billing_address}'   => trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() ) ?: '-',
            '{shipping_address}'  => trim( $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() ) ?: '-',
            '{city}'              => $city ?: '-',
            '{state}'             => $state_name ?: '-',
            '{postcode}'          => $postcode ?: '-',
            '{postal_code}'       => $postcode ?: '-',
            '{payment_method}'    => $payment_method ?: '-',
            '{shipping_method}'   => $shipping_method ?: '-',
            '{products_list}'     => $items_list,
            '{date}'              => PersianDate::format( $order->get_date_created()->date( 'Y-m-d H:i:s' ) ),
            '{tracking_code}'     => $order->get_meta( '_rf_tracking_code' ) ?: '-',
            '{shipping_company}'  => $order->get_meta( '_rf_shipping_company' ) ?: '-',
        ];
    }

    /**
     * Format a Woo order item's variation attributes / meta into a readable string.
     * e.g. "رنگ: قرمز، سایز: XL"
     */
    private static function formatItemAttributes( \WC_Order_Item $item ): string {
        $parts = [];

        if ( $item instanceof \WC_Order_Item_Product ) {
            $product = $item->get_product();
            // Variation attributes (preferred — gives clean taxonomy term labels)
            if ( $product && $product->is_type( 'variation' ) ) {
                foreach ( $product->get_variation_attributes() as $attr => $value ) {
                    if ( $value === '' ) continue;
                    $taxonomy = str_replace( 'attribute_', '', $attr );
                    $label    = wc_attribute_label( $taxonomy, $product );
                    $term     = get_term_by( 'slug', $value, $taxonomy );
                    $parts[]  = $label . ': ' . ( $term ? $term->name : $value );
                }
            }
        }

        // Fall back to hidden/custom item meta (skips _underscored internal keys)
        if ( empty( $parts ) ) {
            foreach ( $item->get_meta_data() as $meta ) {
                $key = (string) $meta->key;
                if ( $key === '' || $key[0] === '_' ) continue;
                $value = is_scalar( $meta->value ) ? (string) $meta->value : '';
                if ( $value === '' ) continue;
                $label   = wc_attribute_label( $key );
                $parts[] = $label . ': ' . $value;
            }
        }

        return implode( '، ', $parts );
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
            '{order_subtotal}'  => '۱,۲۰۰,۰۰۰ تومان',
            '{order_shipping}'  => '۵۰,۰۰۰ تومان',
            '{order_discount}'  => '۰',
            '{order_note}'      => 'لطفاً قبل از ارسال تماس بگیرید',
            '{customer_name}'   => 'علی رضایی',
            '{customer_email}'  => 'ali@example.com',
            '{customer_phone}'  => '۰۹۱۲۳۴۵۶۷۸۹',
            '{address}'         => 'تهران - خیابان آزادی - پلاک ۱۲',
            '{billing_address}' => 'خیابان آزادی، پلاک ۱۲',
            '{shipping_address}'=> 'خیابان آزادی، پلاک ۱۲',
            '{city}'            => 'تهران',
            '{state}'           => 'تهران',
            '{postcode}'        => '۱۲۳۴۵۶۷۸۹۰',
            '{postal_code}'     => '۱۲۳۴۵۶۷۸۹۰',
            '{payment_method}'  => 'پرداخت در محل',
            '{shipping_method}' => 'پست پیشتاز',
            '{products_list}'   => "📦 شال دورگان × ۲ - ۲۵۰,۰۰۰ تومان\n   🏷 رنگ: قرمز، سایز: متوسط\n📦 ایرپاد پرو × ۱ - ۱,۰۰۰,۰۰۰ تومان",
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
