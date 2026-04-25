<?php
namespace RobotForooshande\Bot;

if ( ! defined( 'ABSPATH' ) ) exit;

class StateManager {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'rf_bot_users';
    }

    public function getOrCreateUser( int $chatId, array $from = [] ): object {
        global $wpdb;
        $platform = get_option( 'rf_platform', 'telegram' );

        // Parse admin IDs (support both comma and newline separated)
        $raw_admin_ids = get_option( 'rf_admin_chat_ids', '' );
        $admin_ids     = array_map( 'intval', array_filter( preg_split( '/[\s,]+/', $raw_admin_ids ) ) );
        $is_admin      = in_array( $chatId, $admin_ids, true ) ? 1 : 0;

        $user = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE chat_id = %d AND platform = %s",
            $chatId, $platform
        ) );

        if ( ! $user ) {
            $wpdb->insert( $this->table, [
                'chat_id'    => $chatId,
                'platform'   => $platform,
                'first_name' => $from['first_name'] ?? null,
                'last_name'  => $from['last_name'] ?? null,
                'username'   => $from['username'] ?? null,
                'is_admin'   => $is_admin,
                'state'      => 'idle',
                'created_at' => current_time( 'mysql' ),
            ], [ '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ] );

            $user = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d", $wpdb->insert_id
            ) );
        } else {
            // Update name/username if changed
            $updates = [];
            $formats = [];
            if ( ! empty( $from['first_name'] ) && $from['first_name'] !== $user->first_name ) {
                $updates['first_name'] = $from['first_name'];
                $formats[] = '%s';
            }
            if ( ! empty( $from['last_name'] ) && ( $from['last_name'] ?? '' ) !== ( $user->last_name ?? '' ) ) {
                $updates['last_name'] = $from['last_name'];
                $formats[] = '%s';
            }
            if ( ! empty( $from['username'] ) && ( $from['username'] ?? '' ) !== ( $user->username ?? '' ) ) {
                $updates['username'] = $from['username'];
                $formats[] = '%s';
            }
            // Always sync is_admin flag from settings
            if ( (int) $user->is_admin !== $is_admin ) {
                $updates['is_admin'] = $is_admin;
                $formats[] = '%d';
            }
            if ( ! empty( $updates ) ) {
                $wpdb->update( $this->table, $updates, [ 'id' => $user->id ], $formats, [ '%d' ] );
                $user = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE id = %d", $user->id
                ) );
            }
        }

        return $user;
    }

    public function setState( int $botUserId, string $state, ?array $data = null ): void {
        global $wpdb;
        $wpdb->update(
            $this->table,
            [
                'state'      => $state,
                'state_data' => $data ? wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) : null,
            ],
            [ 'id' => $botUserId ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    public function getStateData( object $botUser ): ?array {
        if ( empty( $botUser->state_data ) ) return null;
        return json_decode( $botUser->state_data, true );
    }

    public function clearState( int $botUserId ): void {
        $this->setState( $botUserId, 'idle', null );
    }

    public function setPhone( int $botUserId, string $phone, ?int $wpUserId = null ): void {
        global $wpdb;
        $data = [ 'phone' => $phone ];
        $formats = [ '%s' ];

        if ( $wpUserId ) {
            $data['user_id'] = $wpUserId;
            $formats[] = '%d';
        }

        $wpdb->update( $this->table, $data, [ 'id' => $botUserId ], $formats, [ '%d' ] );
    }

    public function getUserByChatId( int $chatId ): ?object {
        global $wpdb;
        $platform = get_option( 'rf_platform', 'telegram' );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE chat_id = %d AND platform = %s",
            $chatId, $platform
        ) );
    }

    public function getUserByPhone( string $phone ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE phone = %s LIMIT 1", $phone
        ) );
    }

    public function getAllUsers( int $limit = 0 ): array {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table}";
        if ( $limit > 0 ) {
            $sql = $wpdb->prepare( "{$sql} LIMIT %d", $limit );
        }
        return $wpdb->get_results( $sql );
    }

    public function getUserCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }

    public function refreshUser( int $botUserId ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d", $botUserId
        ) );
    }
}
