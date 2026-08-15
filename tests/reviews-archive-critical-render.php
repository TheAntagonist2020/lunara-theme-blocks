<?php
/**
 * CLI adapter for the pure Reviews archive critical-CSS composer.
 */

define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = array() ) {
        return array_merge( (array) $defaults, (array) $args );
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $value ) {
        return abs( (int) $value );
    }
}

require dirname( __DIR__ ) . '/inc/review-archive-critical.php';

$payload = isset( $argv[1] ) ? json_decode( base64_decode( (string) $argv[1] ), true ) : array();
$orders = isset( $payload['orders'] ) && is_array( $payload['orders'] ) ? $payload['orders'] : array();
$visibility = isset( $payload['visibility'] ) && is_array( $payload['visibility'] ) ? $payload['visibility'] : array();

echo lunara_reviews_archive_critical_css( $orders, $visibility );
