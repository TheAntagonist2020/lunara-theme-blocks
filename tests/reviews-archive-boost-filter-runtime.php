<?php
/**
 * Isolated runtime check for the Reviews archive Jetpack Boost filters.
 */

define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );

$GLOBALS['lunara_test_filters'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['lunara_test_filters'][] = array(
        'hook'          => (string) $hook,
        'callback'      => (string) $callback,
        'priority'      => (int) $priority,
        'accepted_args' => (int) $accepted_args,
    );
}

function add_action() {}

function apply_filters( $hook, $value = null ) {
    return 'lunara_use_custom_footer' === $hook ? false : $value;
}

require dirname( __DIR__ ) . '/inc/frontend.php';

$wanted_hooks = array( 'css_do_concat', 'jetpack_boost_async_style' );
$registrations = array_values(
    array_filter(
        $GLOBALS['lunara_test_filters'],
        static function ( $registration ) use ( $wanted_hooks ) {
            return in_array( $registration['hook'], $wanted_hooks, true );
        }
    )
);

echo json_encode(
    array(
        'concat_archive_true'  => lunara_keep_review_archive_css_unaggregated( true, 'lunara-review-archive' ),
        'concat_archive_false' => lunara_keep_review_archive_css_unaggregated( false, 'lunara-review-archive' ),
        'concat_other_true'    => lunara_keep_review_archive_css_unaggregated( true, 'lunara-shell' ),
        'concat_other_false'   => lunara_keep_review_archive_css_unaggregated( false, 'lunara-shell' ),
        'async_archive_true'   => lunara_keep_review_archive_css_synchronous( true, 'lunara-review-archive' ),
        'async_archive_false'  => lunara_keep_review_archive_css_synchronous( false, 'lunara-review-archive' ),
        'async_other_true'     => lunara_keep_review_archive_css_synchronous( true, 'lunara-shell' ),
        'async_other_false'    => lunara_keep_review_archive_css_synchronous( false, 'lunara-shell' ),
        'registrations'        => $registrations,
    )
);
