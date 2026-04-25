<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;
use RobotForooshande\Helpers\PersianDate;

class CategoryHandler {

    public function showCategories( array $ctx, int $parentId = 0 ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => $parentId,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ];

        $categories = get_terms( $args );

        if ( empty( $categories ) || is_wp_error( $categories ) ) {
            if ( $parentId > 0 ) {
                $productHandler = new ProductHandler();
                $productHandler->showProductsByCategory( $ctx, $parentId );
                return;
            }
            $bot->sendMessage( $chatId, '📂 دسته‌بندی فعالی وجود ندارد.' );
            return;
        }

        $text = $parentId ? "📂 زیردسته‌ها:\n━━━━━━━━━━━━━━\n" : "📂 دسته‌بندی‌ها:\n━━━━━━━━━━━━━━\n";
        $rows = [];
        $row  = [];
        $i    = 1;

        foreach ( $categories as $cat ) {
            $count = $cat->count;
            $label = $cat->name;

            $children = get_terms( [
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'parent'     => $cat->term_id,
                'fields'     => 'ids',
            ] );
            $hasChildren = ! empty( $children ) && ! is_wp_error( $children );

            $emoji = $hasChildren ? '📁' : '📦';
            $text .= "{$emoji} {$label}";
            if ( $count > 0 ) {
                $text .= ' (' . PersianDate::toPersianDigits( (string) $count ) . ')';
            }
            $text .= "\n";

            $row[] = KeyboardBuilder::inlineButton(
                "{$emoji} {$label}",
                "cat:{$cat->term_id}"
            );

            if ( count( $row ) >= 2 ) {
                $rows[] = $row;
                $row    = [];
            }
            $i++;
        }

        if ( ! empty( $row ) ) $rows[] = $row;

        if ( $parentId ) {
            $parent = get_term( $parentId );
            if ( $parent && ! is_wp_error( $parent ) && $parent->parent ) {
                $rows[] = KeyboardBuilder::backButton( "cat:{$parent->parent}" );
            } else {
                $rows[] = KeyboardBuilder::backButton( 'back_shop' );
            }
        } else {
            $rows[] = KeyboardBuilder::backButton();
        }

        $msgId = $ctx['message_id'] ?? 0;
        if ( $msgId ) {
            $bot->editMessageText( $chatId, $msgId, $text, KeyboardBuilder::inline( $rows ) );
        } else {
            $bot->sendMessage( $chatId, $text, KeyboardBuilder::inline( $rows ) );
        }
    }

    public function handleCallback( array $ctx ): void {
        $catId = (int) ( $ctx['parts'][1] ?? 0 );
        if ( ! $catId ) return;

        $children = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => $catId,
            'fields'     => 'ids',
        ] );

        if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
            $this->showCategories( $ctx, $catId );
        } else {
            $productHandler = new ProductHandler();
            $productHandler->showProductsByCategory( $ctx, $catId );
        }
    }
}
