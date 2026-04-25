<?php
namespace RobotForooshande\User;

if ( ! defined( 'ABSPATH' ) ) exit;

use RobotForooshande\Helpers\Logger;

class UserSync {

    /**
     * Sync bot user with WordPress/WooCommerce user by phone number
     *
     * @return int WordPress user ID (existing or newly created)
     */
    public static function sync( string $phone, array $botUserData = [] ): int {
        $normalized = PhoneNormalizer::normalize( $phone );

        // Try to find existing WP user by phone
        $wpUserId = self::findWpUserByPhone( $normalized );

        if ( $wpUserId ) {
            // Update chat_id in user meta
            update_user_meta( $wpUserId, 'rf_chat_id', $botUserData['chat_id'] ?? '' );
            update_user_meta( $wpUserId, 'rf_platform', $botUserData['platform'] ?? 'telegram' );
            update_user_meta( $wpUserId, 'rf_bot_registered_at', current_time( 'mysql' ) );

            Logger::info( 'User synced: existing WP user', [
                'wp_user_id' => $wpUserId,
                'phone'      => $normalized,
            ] );

            return $wpUserId;
        }

        // Create new WordPress user
        $username = self::generateUsername( $normalized );
        $email    = $username . '@bot.local'; // placeholder email
        $password = wp_generate_password( 16 );

        $wpUserId = wp_insert_user( [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'first_name' => $botUserData['first_name'] ?? '',
            'last_name'  => $botUserData['last_name'] ?? '',
            'role'       => 'customer',
        ] );

        if ( is_wp_error( $wpUserId ) ) {
            Logger::error( 'Failed to create WP user', [
                'phone' => $normalized,
                'error' => $wpUserId->get_error_message(),
            ] );
            return 0;
        }

        // Save phone in the configured meta field
        $phone_meta_key = get_option( 'rf_phone_meta_key', 'billing_phone' );
        update_user_meta( $wpUserId, $phone_meta_key, $normalized );

        // Also save in billing_phone for WooCommerce
        if ( $phone_meta_key !== 'billing_phone' ) {
            update_user_meta( $wpUserId, 'billing_phone', $normalized );
        }

        // Save bot-specific meta
        update_user_meta( $wpUserId, 'rf_chat_id', $botUserData['chat_id'] ?? '' );
        update_user_meta( $wpUserId, 'rf_platform', $botUserData['platform'] ?? 'telegram' );
        update_user_meta( $wpUserId, 'rf_bot_registered_at', current_time( 'mysql' ) );

        // Save first/last name for billing
        if ( ! empty( $botUserData['first_name'] ) ) {
            update_user_meta( $wpUserId, 'billing_first_name', $botUserData['first_name'] );
        }
        if ( ! empty( $botUserData['last_name'] ) ) {
            update_user_meta( $wpUserId, 'billing_last_name', $botUserData['last_name'] );
        }

        Logger::info( 'User synced: new WP user created', [
            'wp_user_id' => $wpUserId,
            'phone'      => $normalized,
        ] );

        // Notify admin about new user
        if ( get_option( 'rf_notify_new_user', true ) ) {
            do_action( 'rf_new_user_registered', $wpUserId, $botUserData );
        }

        return $wpUserId;
    }

    /**
     * Find WordPress user by phone number
     * Checks multiple meta fields and username based on settings
     */
    public static function findWpUserByPhone( string $phone ): int {
        $normalized  = PhoneNormalizer::normalize( $phone );
        $display     = PhoneNormalizer::formatDisplay( $phone );
        $phone_meta  = get_option( 'rf_phone_meta_key', 'billing_phone' );

        // Variations to try
        $phoneVariants = array_unique( [ $normalized, $display, ltrim( $normalized, '+' ), substr( $normalized, 1 ) ] );

        // Method 0: If configured key is user_login, check username first
        if ( $phone_meta === 'user_login' ) {
            foreach ( $phoneVariants as $variant ) {
                $user = get_user_by( 'login', $variant );
                if ( $user ) {
                    return $user->ID;
                }
            }
        }

        // Method 1: Check configured meta key (skip if user_login)
        if ( $phone_meta !== 'user_login' ) {
            foreach ( $phoneVariants as $variant ) {
                $users = get_users( [
                    'meta_key'   => $phone_meta,
                    'meta_value' => $variant,
                    'number'     => 1,
                    'fields'     => 'ID',
                ] );
                if ( ! empty( $users ) ) {
                    return (int) $users[0];
                }
            }
        }

        // Method 2: Check billing_phone (if different from configured)
        if ( $phone_meta !== 'billing_phone' ) {
            foreach ( $phoneVariants as $variant ) {
                $users = get_users( [
                    'meta_key'   => 'billing_phone',
                    'meta_value' => $variant,
                    'number'     => 1,
                    'fields'     => 'ID',
                ] );
                if ( ! empty( $users ) ) {
                    return (int) $users[0];
                }
            }
        }

        // Method 3: Check by username (some themes use phone as username)
        foreach ( $phoneVariants as $variant ) {
            $user = get_user_by( 'login', $variant );
            if ( $user ) {
                return $user->ID;
            }
        }

        // Method 4: Check rf_chat_id if we already have it synced
        // (already covered by the meta checks above)

        return 0;
    }

    /**
     * Get chat_id for a WordPress user
     */
    public static function getChatId( int $wpUserId ): ?int {
        $chat_id = get_user_meta( $wpUserId, 'rf_chat_id', true );
        return $chat_id ? (int) $chat_id : null;
    }

    /**
     * Get bot user by WordPress user ID
     */
    public static function getBotUser( int $wpUserId ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rf_bot_users WHERE user_id = %d LIMIT 1",
            $wpUserId
        ) );
    }

    private static function generateUsername( string $phone ): string {
        $base = 'user_' . substr( preg_replace( '/[^0-9]/', '', $phone ), -10 );
        if ( ! username_exists( $base ) ) return $base;

        $i = 1;
        while ( username_exists( $base . '_' . $i ) ) {
            $i++;
        }
        return $base . '_' . $i;
    }
}
