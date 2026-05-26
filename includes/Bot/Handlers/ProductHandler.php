<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\{KeyboardBuilder, MessageTemplateEngine};
use RobotForooshande\WooCommerce\{CartSync, StockManager};
use RobotForooshande\Helpers\{PersianDate, PriceFormatter, Cache};

class ProductHandler {

    public function showProduct( array $ctx, int $productId ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $product = wc_get_product( $productId );
        if ( ! $product || $product->get_status() !== 'publish' || $product->get_catalog_visibility() === 'hidden' ) {
            $bot->sendMessage( $chatId, '❌ محصول یافت نشد.' );
            return;
        }

        $this->saveRecentlyViewed( $ctx['bot_user']->id, $productId );

        $vars    = MessageTemplateEngine::productVars( $product );
        $message = MessageTemplateEngine::render( 'rf_msg_product_display', $vars );

        $isVariable = $product->is_type( 'variable' );
        $inStock    = $product->is_in_stock();
        $keyboard   = KeyboardBuilder::productActions( $productId, $inStock, $isVariable );

        $imageId = (int) $product->get_image_id();
        if ( $imageId ) {
            $this->sendProductPhoto( $bot, $chatId, $imageId, $message, $keyboard );
        } else {
            $bot->sendMessage( $chatId, $message, $keyboard );
        }
    }

    /**
     * Send a product image reliably with four tiers:
     *  1. Cached file_id  — instant, no upload
     *  2. Public URL      — original working approach; Bale/Telegram downloads it
     *  3. Local file path — direct binary upload as fallback (JPEG converted if needed)
     *  4. Text-only       — always gives the user something
     */
    private function sendProductPhoto( \RobotForooshande\Bot\BotCore $bot, int|string $chatId, int $imageId, string $caption, ?array $keyboard ): void {
        $platform = $bot->getPlatform();
        $metaKey  = "rf_file_id_{$platform}";

        // Tier 1 — try cached file_id (zero-upload, fastest path)
        $fileId = get_post_meta( $imageId, $metaKey, true );
        if ( $fileId ) {
            $result = $bot->sendPhoto( $chatId, $fileId, $caption, $keyboard );
            if ( ! empty( $result['ok'] ) ) {
                return;
            }
            // Stale file_id — clear and fall through.
            delete_post_meta( $imageId, $metaKey );
        }

        // Tier 2 — send via public URL (original behaviour, works for WebP on Bale)
        $url = wp_get_attachment_url( $imageId );
        if ( $url ) {
            $result = $bot->sendPhoto( $chatId, $url, $caption, $keyboard );
            if ( ! empty( $result['ok'] ) ) {
                // Cache the returned file_id so the next send skips the upload.
                $photos = $result['result']['photo'] ?? [];
                if ( ! empty( $photos ) ) {
                    $best = end( $photos );
                    update_post_meta( $imageId, $metaKey, $best['file_id'] );
                }
                return;
            }
        }

        // Tier 3 — upload from local disk (converts WebP → JPEG automatically)
        $localPath = get_attached_file( $imageId );
        if ( $localPath && file_exists( $localPath ) ) {
            $result = $bot->sendPhotoFile( $chatId, $localPath, $caption, $keyboard );
            if ( ! empty( $result['ok'] ) ) {
                $photos = $result['result']['photo'] ?? [];
                if ( ! empty( $photos ) ) {
                    $best = end( $photos );
                    update_post_meta( $imageId, $metaKey, $best['file_id'] );
                }
                return;
            }
        }

        // Tier 4 — text-only fallback so the user always gets a reply
        $bot->sendMessage( $chatId, $caption, $keyboard );
    }

    public function handleCallback( array $ctx ): void {
        $productId = (int) ( $ctx['parts'][1] ?? 0 );
        if ( $productId ) {
            $this->showProduct( $ctx, $productId );
        }
    }

    public function handleVariation( array $ctx ): void {
        $bot       = $ctx['bot'];
        $chatId    = $ctx['chat_id'];
        $productId = (int) ( $ctx['parts'][1] ?? 0 );
        $attrIndex = $ctx['parts'][2] ?? null;
        $attrValue = $ctx['parts'][3] ?? null;

        $product = wc_get_product( $productId );
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            $bot->sendMessage( $chatId, '❌ محصول یافت نشد.' );
            return;
        }

        // $ctx['bot_user'] is always loaded fresh from DB at the start of each webhook request,
        // so state_data is already up to date — no extra SELECT needed.
        $stateData = ! empty( $ctx['bot_user']->state_data ) ? json_decode( $ctx['bot_user']->state_data, true ) : [];
        $selected  = $stateData['selected_attrs'] ?? [];

        if ( $attrIndex !== null && $attrValue !== null ) {
            $attributes = $product->get_variation_attributes();
            $attrNames  = array_keys( $attributes );
            if ( isset( $attrNames[ (int) $attrIndex ] ) ) {
                $selected[ $attrNames[ (int) $attrIndex ] ] = urldecode( $attrValue );
            }
        }

        $attributes = $product->get_variation_attributes();
        $attrNames  = array_keys( $attributes );

        $nextAttr = null;
        $nextIdx  = null;
        foreach ( $attrNames as $idx => $name ) {
            if ( ! isset( $selected[ $name ] ) ) {
                $nextAttr = $name;
                $nextIdx  = $idx;
                break;
            }
        }

        if ( $nextAttr !== null ) {
            $values = $attributes[ $nextAttr ];
            $label  = wc_attribute_label( $nextAttr );
            $rows   = [];
            $row    = [];

            foreach ( $values as $value ) {
                $term_name = $value;
                $term = get_term_by( 'slug', $value, $nextAttr );
                if ( $term ) $term_name = $term->name;

                $row[] = KeyboardBuilder::inlineButton(
                    $term_name,
                    "variation:{$productId}:{$nextIdx}:" . urlencode( $value )
                );
                if ( count( $row ) >= 3 ) {
                    $rows[] = $row;
                    $row    = [];
                }
            }
            if ( ! empty( $row ) ) $rows[] = $row;
            $rows[] = KeyboardBuilder::backButton( "product:{$productId}" );

            $ctx['state']->setState( $ctx['bot_user']->id, 'idle', [ 'selected_attrs' => $selected ] );

            $text = "🎨 لطفاً {$label} را انتخاب کنید:";
            if ( ! empty( $selected ) ) {
                $text .= "\n\nانتخاب‌های شما:";
                foreach ( $selected as $k => $v ) {
                    $lbl = wc_attribute_label( $k );
                    $term = get_term_by( 'slug', $v, $k );
                    $text .= "\n• {$lbl}: " . ( $term ? $term->name : $v );
                }
            }

            // Always sendMessage for variations (original product page is a photo, editMessageText won't work on photos)
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
            return;
        }

        $data_store = \WC_Data_Store::load( 'product' );

        // Build attribute array with 'attribute_' prefix as WC expects
        $matchAttrs = [];
        foreach ( $selected as $k => $v ) {
            $matchAttrs[ 'attribute_' . sanitize_title( $k ) ] = sanitize_title( $v );
        }
        $variationId = $data_store->find_matching_product_variation( $product, $matchAttrs );

        // Fallback: try with raw (non-sanitized) values
        if ( ! $variationId ) {
            $matchRaw = [];
            foreach ( $selected as $k => $v ) {
                $matchRaw[ 'attribute_' . $k ] = $v;
            }
            $variationId = $data_store->find_matching_product_variation( $product, $matchRaw );
        }

        // Fallback: manually iterate available variations
        if ( ! $variationId ) {
            foreach ( $product->get_available_variations() as $v ) {
                $vAttrs = $v['attributes'];
                $match  = true;
                foreach ( $selected as $k => $val ) {
                    $key = 'attribute_' . $k;
                    if ( isset( $vAttrs[ $key ] ) && $vAttrs[ $key ] !== '' && strtolower( $vAttrs[ $key ] ) !== strtolower( $val ) ) {
                        $match = false;
                        break;
                    }
                }
                if ( $match && $v['is_in_stock'] ) {
                    $variationId = $v['variation_id'];
                    break;
                }
            }
        }

        if ( ! $variationId ) {
            $bot->sendMessage( $chatId, '❌ این ترکیب موجود نیست. لطفاً دوباره انتخاب کنید.' );
            $ctx['state']->clearState( $ctx['bot_user']->id );
            return;
        }

        $variation = wc_get_product( $variationId );
        if ( ! $variation || ! $variation->is_in_stock() ) {
            $bot->sendMessage( $chatId, '❌ این تنوع در حال حاضر ناموجود است.' );
            $ctx['state']->clearState( $ctx['bot_user']->id );
            return;
        }

        $attrText = '';
        foreach ( $selected as $k => $v ) {
            $lbl  = wc_attribute_label( $k );
            $term = get_term_by( 'slug', $v, $k );
            $attrText .= "• {$lbl}: " . ( $term ? $term->name : $v ) . "\n";
        }

        $text = "📦 {$product->get_name()}\n";
        $text .= $attrText;
        $text .= "💰 قیمت: " . PriceFormatter::format( $variation->get_price() ) . "\n";
        $text .= "📊 " . StockManager::formatStockStatus( $productId, $variationId );

        $rows = [
            [
                KeyboardBuilder::inlineButton( '🛒 افزودن به سبد', "add_cart:{$productId}:{$variationId}" ),
                KeyboardBuilder::inlineButton( '💳 خرید مستقیم', "buy_direct:{$productId}:{$variationId}" ),
            ],
            KeyboardBuilder::backButton( "product:{$productId}" ),
        ];

        $ctx['state']->clearState( $ctx['bot_user']->id );

        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function handlePagination( array $ctx ): void {
        $page     = (int) ( $ctx['parts'][1] ?? 1 );
        $type     = $ctx['parts'][2] ?? 'all';
        $catId    = (int) ( $ctx['parts'][3] ?? 0 );
        $searchQ  = $ctx['parts'][3] ?? '';

        match ( $type ) {
            'cat'      => $this->showProductsByCategory( $ctx, $catId, $page ),
            'search'   => $this->showSearchResults( $ctx, urldecode( $searchQ ), $page ),
            'sale'     => $this->showOnSale( $ctx, $page ),
            'featured' => $this->showFeatured( $ctx, $page ),
            default    => null,
        };
    }

    public function showProductsByCategory( array $ctx, int $catId, int $page = 1 ): void {
        $perPage = (int) get_option( 'rf_products_per_page', 6 );

        // Use WP_Query directly with an explicit visibility tax_query so hidden products
        // (tagged with 'exclude-from-catalog') are reliably excluded regardless of
        // WooCommerce version or caching layer.
        $tax_query = [
            'relation' => 'AND',
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [ $catId ],
            ],
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => [ 'exclude-from-catalog' ],
                'operator' => 'NOT IN',
            ],
        ];

        $query = new \WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $perPage,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => $tax_query,
        ] );

        $products = array_values( array_filter( array_map( 'wc_get_product', $query->posts ) ) );
        $total    = (int) $query->found_posts;
        $pages    = max( 1, (int) ceil( $total / $perPage ) );

        $this->renderProductList( $ctx, $products, $page, $pages, "page:%d:cat:{$catId}", 'back_shop' );
    }

    public function showSearchResults( array $ctx, string $query, int $page = 1 ): void {
        $perPage = (int) get_option( 'rf_products_per_page', 6 );

        $tax_query = [
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => [ 'exclude-from-search' ],
                'operator' => 'NOT IN',
            ],
        ];

        $wp_query = new \WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $perPage,
            'paged'          => $page,
            's'              => $query,
            'tax_query'      => $tax_query,
        ] );

        $products = array_values( array_filter( array_map( 'wc_get_product', $wp_query->posts ) ) );
        $total    = (int) $wp_query->found_posts;
        $pages    = max( 1, (int) ceil( $total / $perPage ) );

        $encodedQ = urlencode( $query );
        $this->renderProductList( $ctx, $products, $page, $pages, "page:%d:search:{$encodedQ}", 'back_menu' );
    }

    public function showOnSale( array $ctx, int $page = 1 ): void {
        $perPage  = (int) get_option( 'rf_products_per_page', 6 );
        $sale_ids = wc_get_product_ids_on_sale();

        if ( ! empty( $sale_ids ) ) {
            // Filter out products that are hidden from the catalog
            $sale_ids = wc_get_products( [
                'include'            => $sale_ids,
                'status'             => 'publish',
                'catalog_visibility' => 'catalog',
                'limit'              => -1,
                'return'             => 'ids',
            ] );
        }

        if ( empty( $sale_ids ) ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '🔥 در حال حاضر حراجی فعالی وجود ندارد.' );
            return;
        }

        $offset   = ( $page - 1 ) * $perPage;
        $page_ids = array_slice( $sale_ids, $offset, $perPage );
        $products = array_filter( array_map( 'wc_get_product', $page_ids ) );
        $pages    = max( 1, (int) ceil( count( $sale_ids ) / $perPage ) );

        $this->renderProductList( $ctx, $products, $page, $pages, "page:%d:sale", 'back_shop' );
    }

    public function showFeatured( array $ctx, int $page = 1 ): void {
        $perPage = (int) get_option( 'rf_products_per_page', 6 );

        $args = [
            'status'             => 'publish',
            'catalog_visibility' => 'catalog',
            'limit'              => $perPage,
            'page'               => $page,
            'featured'           => true,
        ];

        $products = wc_get_products( $args );

        $count_args           = $args;
        $count_args['limit']  = -1;
        $count_args['return'] = 'ids';
        $total = count( wc_get_products( $count_args ) );
        $pages = max( 1, (int) ceil( $total / $perPage ) );

        if ( empty( $products ) ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '⭐ محصول ویژه‌ای یافت نشد.' );
            return;
        }

        $this->renderProductList( $ctx, $products, $page, $pages, "page:%d:featured", 'back_shop' );
    }

    public function showWishlist( array $ctx ): void {
        global $wpdb;
        $botUser = $ctx['bot_user'];
        $chatId  = $ctx['chat_id'];
        $bot     = $ctx['bot'];

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}rf_wishlists WHERE bot_user_id = %d ORDER BY added_at DESC",
            $botUser->id
        ) );

        if ( empty( $items ) ) {
            $bot->sendMessage( $chatId, '❤️ لیست علاقه‌مندی‌های شما خالی است.' );
            return;
        }

        $text = "❤️ علاقه‌مندی‌های شما:\n━━━━━━━━━━━━━━\n";
        $rows = [];
        $i    = 1;

        foreach ( $items as $item ) {
            $product = wc_get_product( $item->product_id );
            if ( ! $product ) continue;

            $emoji = $product->is_in_stock() ? '✅' : '❌';
            $text .= PersianDate::toPersianDigits( (string) $i ) . "️⃣ "
                   . $product->get_name() . " - "
                   . PriceFormatter::format( $product->get_price() )
                   . " {$emoji}\n";

            $rows[] = [
                KeyboardBuilder::inlineButton( "📦 {$i}", "product:{$item->product_id}" ),
                KeyboardBuilder::inlineButton( '🛒', "add_cart:{$item->product_id}" ),
                KeyboardBuilder::inlineButton( '🗑', "wishlist_rm:{$item->product_id}" ),
            ];
            $i++;
        }

        $rows[] = KeyboardBuilder::backButton();
        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function showRecent( array $ctx ): void {
        $botUser = $ctx['bot_user'];
        $chatId  = $ctx['chat_id'];
        $bot     = $ctx['bot'];

        $recent = Cache::get( "recent_{$botUser->id}", [] );
        if ( empty( $recent ) ) {
            $bot->sendMessage( $chatId, '🕐 هنوز محصولی مشاهده نکرده‌اید.' );
            return;
        }

        $text = "🕐 اخیراً مشاهده شده:\n━━━━━━━━━━━━━━\n";
        $rows = [];
        $i    = 1;

        foreach ( array_reverse( $recent ) as $pid ) {
            $product = wc_get_product( $pid );
            if ( ! $product ) continue;

            $text .= PersianDate::toPersianDigits( (string) $i ) . "️⃣ "
                   . $product->get_name() . " - "
                   . PriceFormatter::format( $product->get_price() ) . "\n";

            $rows[] = [
                KeyboardBuilder::inlineButton( "📦 {$i}", "product:{$pid}" ),
            ];
            $i++;
            if ( $i > 10 ) break;
        }

        $rows[] = KeyboardBuilder::backButton();
        $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
    }

    public function addToWishlist( array $ctx ): void {
        global $wpdb;
        $productId = (int) ( $ctx['parts'][1] ?? 0 );
        $botUser   = $ctx['bot_user'];

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rf_wishlists WHERE bot_user_id = %d AND product_id = %d",
            $botUser->id, $productId
        ) );

        if ( $exists ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '❤️ قبلاً در علاقه‌مندی‌ها وجود دارد.' );
            return;
        }

        $wpdb->insert( $wpdb->prefix . 'rf_wishlists', [
            'bot_user_id' => $botUser->id,
            'product_id'  => $productId,
            'added_at'    => current_time( 'mysql' ),
        ], [ '%d', '%d', '%s' ] );

        $ctx['bot']->sendMessage( $ctx['chat_id'], '❤️ به علاقه‌مندی‌ها اضافه شد!' );
    }

    public function removeFromWishlist( array $ctx ): void {
        global $wpdb;
        $productId = (int) ( $ctx['parts'][1] ?? 0 );
        $botUser   = $ctx['bot_user'];

        $wpdb->delete( $wpdb->prefix . 'rf_wishlists', [
            'bot_user_id' => $botUser->id,
            'product_id'  => $productId,
        ], [ '%d', '%d' ] );

        $this->showWishlist( $ctx );
    }

    public function addStockAlert( array $ctx ): void {
        global $wpdb;
        $productId = (int) ( $ctx['parts'][1] ?? 0 );
        $botUser   = $ctx['bot_user'];

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rf_stock_alerts WHERE bot_user_id = %d AND product_id = %d AND notified = 0",
            $botUser->id, $productId
        ) );

        if ( $exists ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '🔔 قبلاً ثبت شده است.' );
            return;
        }

        $wpdb->insert( $wpdb->prefix . 'rf_stock_alerts', [
            'bot_user_id' => $botUser->id,
            'product_id'  => $productId,
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d', '%d', '%s' ] );

        $ctx['bot']->sendMessage( $ctx['chat_id'], '🔔 هر وقت موجود شد بهتون خبر میدیم!' );
    }

    public function shareProduct( array $ctx ): void {
        $productId    = (int) ( $ctx['parts'][1] ?? 0 );
        $platform     = get_option( 'rf_platform', 'telegram' );
        $bot_username = get_option( 'rf_bot_username', '' );

        if ( $platform === 'bale' ) {
            $link = "https://ble.ir/{$bot_username}?start=product_{$productId}";
        } else {
            $link = "https://t.me/{$bot_username}?start=product_{$productId}";
        }

        $product = wc_get_product( $productId );
        $name    = $product ? $product->get_name() : 'محصول';

        $text = "📤 لینک اشتراک‌گذاری:\n\n{$name}\n{$link}";
        $ctx['bot']->sendMessage( $ctx['chat_id'], $text );
    }

    public function receiveQuantity( array $ctx, string $text ): void {
        $qty = (int) $text;
        if ( $qty < 1 ) {
            $ctx['bot']->sendMessage( $ctx['chat_id'], '❌ تعداد باید حداقل ۱ باشد.' );
            return;
        }

        $stateData   = $ctx['state']->getStateData( $ctx['bot_user'] );
        $productId   = $stateData['product_id'] ?? 0;
        $variationId = $stateData['variation_id'] ?? 0;

        $available = StockManager::getAvailableQuantity( $productId, $variationId );
        if ( $qty > $available && $available !== PHP_INT_MAX ) {
            $max = PersianDate::toPersianDigits( (string) $available );
            $ctx['bot']->sendMessage( $ctx['chat_id'], "❌ حداکثر {$max} عدد از این محصول موجود است." );
            return;
        }

        $cart = new CartSync();
        $cart->addItem( $ctx['bot_user']->id, $productId, $variationId, $qty );

        $ctx['state']->clearState( $ctx['bot_user']->id );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '✅ محصول به سبد خرید اضافه شد.' );
    }

    private function renderProductList( array $ctx, array $products, int $page, int $pages, string $pageFormat, string $backCallback ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        if ( empty( $products ) ) {
            $bot->sendMessage( $chatId, '📦 محصولی یافت نشد.' );
            return;
        }

        $text = "📦 محصولات:\n━━━━━━━━━━━━━━\n";
        $rows = [];
        $i    = 1;

        foreach ( $products as $product ) {
            if ( ! $product instanceof \WC_Product ) continue;

            $emoji = $product->is_in_stock() ? '✅' : '❌';
            $price = PriceFormatter::format( $product->get_price() );

            $text .= $product->get_name() . "\n"
                   . "   💰 {$price} {$emoji}\n";

            $rows[] = [
                KeyboardBuilder::inlineButton(
                    mb_substr( $product->get_name(), 0, 25 ),
                    "product:{$product->get_id()}"
                ),
            ];
            $i++;
        }

        if ( $pages > 1 ) {
            $rows[] = KeyboardBuilder::pagination( $page, $pages, $pageFormat );
        }

        $rows[] = KeyboardBuilder::backButton( $backCallback );

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    private function saveRecentlyViewed( int $botUserId, int $productId ): void {
        $recent = Cache::get( "recent_{$botUserId}", [] );
        $recent = array_filter( $recent, fn( $id ) => $id !== $productId );
        $recent[] = $productId;
        $recent = array_slice( $recent, -10 );
        Cache::set( "recent_{$botUserId}", $recent, 86400 * 30 );
    }
}
