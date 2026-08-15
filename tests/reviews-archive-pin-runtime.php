<?php
/**
 * Runtime regression for the canonical Reviews archive pin mutation contract.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'LUNARA_CORE_VERSION', 'runtime-test' );

$lunara_runtime_posts = array(
    11 => array( 'type' => 'review', 'status' => 'publish' ),
    22 => array( 'type' => 'review', 'status' => 'draft' ),
    33 => array( 'type' => 'review', 'status' => 'publish' ),
    44 => array( 'type' => 'post', 'status' => 'publish' ),
);
$lunara_runtime_pins = array( 11 => 100 );

function add_action() {}
function add_filter() {}
function absint( $value ) { return abs( (int) $value ); }
function get_post_stati() { return array( 'publish' => 'publish', 'draft' => 'draft', 'trash' => 'trash' ); }
function get_post_type( $post_id ) {
    global $lunara_runtime_posts;
    return isset( $lunara_runtime_posts[ $post_id ] ) ? $lunara_runtime_posts[ $post_id ]['type'] : false;
}
function get_post_status( $post_id ) {
    global $lunara_runtime_posts;
    return isset( $lunara_runtime_posts[ $post_id ] ) ? $lunara_runtime_posts[ $post_id ]['status'] : false;
}
function get_posts() {
    global $lunara_runtime_pins;
    return array_keys( $lunara_runtime_pins );
}
function delete_post_meta( $post_id ) {
    global $lunara_runtime_pins;
    unset( $lunara_runtime_pins[ (int) $post_id ] );
}
function update_post_meta( $post_id, $key, $value ) {
    global $lunara_runtime_pins;
    $lunara_runtime_pins[ (int) $post_id ] = $value;
}

function lunara_pin_test_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

require dirname( __DIR__ ) . '/inc/reviews-cpt.php';

$result = lunara_set_pinned_review_id( 22 );
lunara_pin_test_assert( 0 === $result, 'A draft Review must be rejected.' );
lunara_pin_test_assert( array( 11 ) === array_keys( $lunara_runtime_pins ), 'Rejecting a draft must preserve the current published pin.' );

$result = lunara_set_pinned_review_id( 44 );
lunara_pin_test_assert( 0 === $result, 'A published non-Review must be rejected.' );
lunara_pin_test_assert( array( 11 ) === array_keys( $lunara_runtime_pins ), 'Rejecting another post type must preserve the current published pin.' );

$result = lunara_set_pinned_review_id( 33 );
lunara_pin_test_assert( 33 === $result, 'A published Review must become the canonical pin.' );
lunara_pin_test_assert( array( 33 ) === array_keys( $lunara_runtime_pins ), 'A valid replacement must clear the older pin exactly once.' );

$result = lunara_set_pinned_review_id( 0 );
lunara_pin_test_assert( 0 === $result, 'Automatic mode must return zero.' );
lunara_pin_test_assert( array() === $lunara_runtime_pins, 'Explicit Automatic mode must clear every pin.' );

echo "Reviews archive pin runtime: all assertions passed.\n";
