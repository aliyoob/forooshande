<?php
namespace RobotForooshande\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class Logger {

    public static function log( string $level, string $message, array $context = [] ): void {
        if ( ! get_option( 'rf_log_enabled', true ) ) return;

        $levels    = [ 'debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3 ];
        $min_level = get_option( 'rf_log_level', 'info' );

        if ( ( $levels[ $level ] ?? 0 ) < ( $levels[ $min_level ] ?? 1 ) ) return;

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rf_logs',
            [
                'level'      => $level,
                'message'    => $message,
                'context'    => ! empty( $context ) ? wp_json_encode( $context, JSON_UNESCAPED_UNICODE ) : null,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    }

    public static function error( string $message, array $context = [] ): void {
        self::log( 'error', $message, $context );
    }

    public static function warning( string $message, array $context = [] ): void {
        self::log( 'warning', $message, $context );
    }

    public static function info( string $message, array $context = [] ): void {
        self::log( 'info', $message, $context );
    }

    public static function debug( string $message, array $context = [] ): void {
        self::log( 'debug', $message, $context );
    }

    public static function getLogs( int $page = 1, int $per_page = 50, string $level = '', string $date_from = '', string $date_to = '' ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rf_logs';
        $where = '1=1';
        $args  = [];

        if ( $level ) {
            $where .= ' AND level = %s';
            $args[] = $level;
        }
        if ( $date_from ) {
            $where .= ' AND created_at >= %s';
            $args[] = $date_from . ' 00:00:00';
        }
        if ( $date_to ) {
            $where .= ' AND created_at <= %s';
            $args[] = $date_to . ' 23:59:59';
        }

        $offset = ( $page - 1 ) * $per_page;

        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        if ( ! empty( $args ) ) {
            $count_query = $wpdb->prepare( $count_query, ...$args );
        }
        $total = (int) $wpdb->get_var( $count_query );

        $query = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = $offset;
        $items = $wpdb->get_results( $wpdb->prepare( $query, ...$args ) );

        return [
            'items'      => $items,
            'total'      => $total,
            'pages'      => ceil( $total / $per_page ),
            'current'    => $page,
        ];
    }

    public static function clear(): void {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rf_logs" );
    }
}
