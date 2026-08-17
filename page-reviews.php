<?php
/**
 * Dedicated Reviews hub page.
 */

get_header();

$paged = max(
    1,
    intval( get_query_var( 'paged' ) ),
    intval( get_query_var( 'page' ) )
);

$review_sort = function_exists( 'lunara_get_review_archive_sort' )
    ? lunara_get_review_archive_sort()
    : 'release_desc';
$review_year = function_exists( 'lunara_get_review_archive_year' )
    ? lunara_get_review_archive_year()
    : '';

$query_args = array(
    'post_type'              => 'review',
    'post_status'            => 'publish',
    'posts_per_page'         => 9,
    'paged'                  => $paged,
    'ignore_sticky_posts'    => true,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => true,
);

if ( function_exists( 'lunara_get_review_archive_query_args' ) ) {
    $query_args = lunara_get_review_archive_query_args( $query_args, $review_sort, $review_year );
}

// The composer injects posts_per_page only when the Studio option carries an
// explicitly saved item_count. This hub page never followed the Reading
// setting: its exact pre-release behavior is the literal nine files per page,
// so restore that literal whenever no explicit Studio count applied.
if ( ! isset( $query_args['posts_per_page'] ) ) {
    $query_args['posts_per_page'] = 9;
}

$reviews_query = new WP_Query(
    $query_args
);

// The Reviews Archive Studio resolves the same Editorial Archives theme mods
// with the same non-empty-or-default reads, so identical stored values render
// byte-identically; the legacy reads remain the module-absent fallback.
$reviews_studio_config = function_exists( 'lunara_reviews_archive_studio_get_public_config' )
    ? lunara_reviews_archive_studio_get_public_config()
    : null;
$reviews_studio_labels = is_array( $reviews_studio_config ) && isset( $reviews_studio_config['labels'] ) && is_array( $reviews_studio_config['labels'] )
    ? $reviews_studio_config['labels']
    : array();

$archive_kicker = is_array( $reviews_studio_config ) && isset( $reviews_studio_config['kicker'] ) && '' !== trim( (string) $reviews_studio_config['kicker'] )
    ? (string) $reviews_studio_config['kicker']
    : ( function_exists( 'lunara_theme_mod_text' )
        ? lunara_theme_mod_text( 'lunara_reviews_archive_kicker', 'Criticism Desk' )
        : 'Criticism Desk' );
$archive_title = is_array( $reviews_studio_config ) && isset( $reviews_studio_config['title'] ) && '' !== trim( (string) $reviews_studio_config['title'] )
    ? (string) $reviews_studio_config['title']
    : ( function_exists( 'lunara_theme_mod_text' )
        ? lunara_theme_mod_text( 'lunara_reviews_archive_title', 'Lunara Reviews' )
        : 'Lunara Reviews' );
$archive_copy = is_array( $reviews_studio_config ) && isset( $reviews_studio_config['deck'] ) && '' !== trim( (string) $reviews_studio_config['deck'] )
    ? (string) $reviews_studio_config['deck']
    : ( function_exists( 'lunara_theme_mod_text' )
        ? lunara_theme_mod_text( 'lunara_reviews_archive_copy', 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.' )
        : 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.' );

$pagination_prev = isset( $reviews_studio_labels['pagination_prev'] ) && '' !== trim( (string) $reviews_studio_labels['pagination_prev'] )
    ? (string) $reviews_studio_labels['pagination_prev']
    : __( '&laquo; Previous', 'lunara-film' );
$pagination_next = isset( $reviews_studio_labels['pagination_next'] ) && '' !== trim( (string) $reviews_studio_labels['pagination_next'] )
    ? (string) $reviews_studio_labels['pagination_next']
    : __( 'Next &raquo;', 'lunara-film' );

$pagination_args = array();
if ( 'release_desc' !== $review_sort ) {
    $pagination_args['sort'] = $review_sort;
}
if ( '' !== $review_year ) {
    $pagination_args['review_year'] = $review_year;
}

$pagination = paginate_links(
    array(
        'total'   => max( 1, intval( $reviews_query->max_num_pages ) ),
        'current' => $paged,
        'add_args'  => empty( $pagination_args ) ? false : $pagination_args,
        'prev_text' => $pagination_prev,
        'next_text' => $pagination_next,
    )
);

echo lunara_render_review_archive_shell(
    array(
        'classes'     => 'lunara-review-archive-page',
        'kicker'      => $archive_kicker,
        'title'       => $archive_title,
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts( $reviews_query ),
        'current_sort' => $review_sort,
        'empty_title' => __( 'No reviews yet.', 'lunara-film' ),
        'empty_copy'  => '',
        'pagination'  => $pagination,
    )
);

wp_reset_postdata();
get_footer();
