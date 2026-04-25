<?php
namespace RobotForooshande\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) exit;

class OrderMetaBox {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'saveMetaBoxes' ] );
        add_action( 'wp_ajax_rf_send_tracking_notify', [ $this, 'ajaxSendTracking' ] );
    }

    public function addMetaBoxes(): void {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'rf_tracking_info',
            __( '🚚 اطلاعات ارسال - ربات فروشنده', 'robot-forooshande' ),
            [ $this, 'renderTrackingBox' ],
            $screen,
            'side',
            'high'
        );

        add_meta_box(
            'rf_bot_info',
            __( '🤖 اطلاعات ربات', 'robot-forooshande' ),
            [ $this, 'renderBotInfoBox' ],
            $screen,
            'side',
            'default'
        );
    }

    public function renderTrackingBox( $post_or_order ): void {
        $order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
        if ( ! $order ) return;

        $tracking_code    = $order->get_meta( '_rf_tracking_code' );
        $shipping_company = $order->get_meta( '_rf_shipping_company' );
        $order_id         = $order->get_id();

        wp_nonce_field( 'rf_tracking_nonce', 'rf_tracking_nonce_field' );
        ?>
        <div style="direction:rtl;">
            <p>
                <label><strong><?php esc_html_e( 'شرکت پستی:', 'robot-forooshande' ); ?></strong></label><br>
                <select name="rf_shipping_company" style="width:100%;">
                    <option value=""><?php esc_html_e( 'انتخاب کنید', 'robot-forooshande' ); ?></option>
                    <?php
                    $companies = [ 'پست پیشتاز', 'پست سفارشی', 'تیپاکس', 'اسنپ باکس', 'الو پیک', 'پیک موتوری', 'باربری', 'سایر' ];
                    foreach ( $companies as $c ) {
                        printf( '<option value="%s" %s>%s</option>',
                            esc_attr( $c ),
                            selected( $shipping_company, $c, false ),
                            esc_html( $c )
                        );
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><strong><?php esc_html_e( 'کد رهگیری:', 'robot-forooshande' ); ?></strong></label><br>
                <input type="text" name="rf_tracking_code" value="<?php echo esc_attr( $tracking_code ); ?>"
                       style="width:100%;direction:ltr;" placeholder="<?php esc_attr_e( 'کد رهگیری پستی', 'robot-forooshande' ); ?>">
            </p>
            <p>
                <button type="button" class="button button-primary" id="rf-send-tracking"
                        data-order="<?php echo esc_attr( $order_id ); ?>"
                        style="width:100%;">
                    📤 <?php esc_html_e( 'ارسال اطلاع‌رسانی به مشتری', 'robot-forooshande' ); ?>
                </button>
            </p>
            <div id="rf-tracking-result" style="margin-top:5px;"></div>
        </div>
        <script>
        jQuery(function($){
            $('#rf-send-tracking').on('click', function(){
                var btn = $(this);
                btn.prop('disabled', true).text('در حال ارسال...');
                $.post(ajaxurl, {
                    action: 'rf_send_tracking_notify',
                    nonce: '<?php echo wp_create_nonce( 'rf_tracking_notify' ); ?>',
                    order_id: btn.data('order'),
                    tracking_code: $('input[name=rf_tracking_code]').val(),
                    shipping_company: $('select[name=rf_shipping_company]').val()
                }, function(res){
                    btn.prop('disabled', false).html('📤 ارسال اطلاع‌رسانی به مشتری');
                    $('#rf-tracking-result').html(
                        '<div class="notice notice-' + (res.success ? 'success' : 'error') + ' inline"><p>' + res.data.message + '</p></div>'
                    );
                });
            });
        });
        </script>
        <?php
    }

    public function renderBotInfoBox( $post_or_order ): void {
        $order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
        if ( ! $order ) return;

        $from_bot   = $order->get_meta( '_rf_from_bot' );
        $bot_user_id = $order->get_meta( '_rf_bot_user_id' );

        echo '<div style="direction:rtl;">';
        if ( $from_bot ) {
            echo '<p>✅ ' . esc_html__( 'سفارش از ربات', 'robot-forooshande' ) . '</p>';
            if ( $bot_user_id ) {
                global $wpdb;
                $bot_user = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rf_bot_users WHERE id = %d", $bot_user_id
                ) );
                if ( $bot_user ) {
                    printf( '<p>👤 %s %s</p>', esc_html( $bot_user->first_name ?? '' ), esc_html( $bot_user->last_name ?? '' ) );
                    printf( '<p>📱 %s</p>', esc_html( $bot_user->phone ?? '-' ) );
                    printf( '<p>💬 Chat ID: %s</p>', esc_html( $bot_user->chat_id ) );
                    printf( '<p>📡 %s</p>', esc_html( ucfirst( $bot_user->platform ) ) );
                }
            }
        } else {
            echo '<p>' . esc_html__( 'سفارش از سایت', 'robot-forooshande' ) . '</p>';
        }
        echo '</div>';
    }

    public function saveMetaBoxes( int $orderId ): void {
        if ( ! isset( $_POST['rf_tracking_nonce_field'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rf_tracking_nonce_field'] ) ), 'rf_tracking_nonce' ) ) {
            return;
        }

        $order = wc_get_order( $orderId );
        if ( ! $order ) return;

        if ( isset( $_POST['rf_tracking_code'] ) ) {
            $order->update_meta_data( '_rf_tracking_code', sanitize_text_field( wp_unslash( $_POST['rf_tracking_code'] ) ) );
        }
        if ( isset( $_POST['rf_shipping_company'] ) ) {
            $order->update_meta_data( '_rf_shipping_company', sanitize_text_field( wp_unslash( $_POST['rf_shipping_company'] ) ) );
        }

        $order->save();
    }

    public static function ajaxSendTracking(): void {
        check_ajax_referer( 'rf_tracking_notify', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'دسترسی غیرمجاز' ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $order    = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => 'سفارش یافت نشد.' ] );
        }

        $tracking_code    = sanitize_text_field( wp_unslash( $_POST['tracking_code'] ?? '' ) );
        $shipping_company = sanitize_text_field( wp_unslash( $_POST['shipping_company'] ?? '' ) );

        if ( empty( $tracking_code ) ) {
            wp_send_json_error( [ 'message' => 'لطفاً کد رهگیری را وارد کنید.' ] );
        }

        // Save meta
        $order->update_meta_data( '_rf_tracking_code', $tracking_code );
        $order->update_meta_data( '_rf_shipping_company', $shipping_company );
        $order->save();

        do_action( 'rf_tracking_code_added', $order_id, $tracking_code, $shipping_company );

        wp_send_json_success( [ 'message' => 'اطلاع‌رسانی ارسال شد.' ] );
    }
}
