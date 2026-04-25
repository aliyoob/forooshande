<?php
namespace RobotForooshande\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class Cache {

    public static function get( string $key, $default = null ) {
        $value = get_transient( 'rf_' . $key );
        return $value !== false ? $value : $default;
    }

    public static function set( string $key, $value, ?int $expiration = null ): bool {
        if ( null === $expiration ) {
            $expiration = (int) get_option( 'rf_cache_duration', 300 );
        }
        return set_transient( 'rf_' . $key, $value, $expiration );
    }

    public static function delete( string $key ): bool {
        return delete_transient( 'rf_' . $key );
    }

    public static function flush(): void {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rf_%' OR option_name LIKE '_transient_timeout_rf_%'"
        );
    }
}
