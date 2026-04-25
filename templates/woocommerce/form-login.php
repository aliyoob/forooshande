<?php
/**
 * WooCommerce Login Form Override
 * Replaces the default WooCommerce my-account login form with RF login form.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Let WooCommerce know we're handling the login form
do_action( 'woocommerce_before_customer_login_form' );

echo do_shortcode( '[rf_login]' );

do_action( 'woocommerce_after_customer_login_form' );
