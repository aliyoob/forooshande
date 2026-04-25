<?php
namespace RobotForooshande\Bot\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Bot\KeyboardBuilder;

class SearchHandler {

    public function promptSearch( array $ctx ): void {
        $ctx['state']->setState( $ctx['bot_user']->id, 'awaiting_search' );
        $ctx['bot']->sendMessage( $ctx['chat_id'], '🔍 نام یا بخشی از نام محصول مورد نظر را وارد کنید:' );
    }

    public function receiveSearch( array $ctx, string $query ): void {
        $bot    = $ctx['bot'];
        $chatId = $ctx['chat_id'];

        $query = trim( $query );
        if ( mb_strlen( $query ) < 2 ) {
            $bot->sendMessage( $chatId, '❌ عبارت جستجو باید حداقل ۲ کاراکتر باشد.' );
            return;
        }

        $ctx['state']->clearState( $ctx['bot_user']->id );

        $productHandler = new ProductHandler();
        $productHandler->showSearchResults( $ctx, $query );
    }
}
