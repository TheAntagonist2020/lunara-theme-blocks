<?php
/**
 * Review archive template.
 */

get_header();

$review_sort = function_exists( 'lunara_get_review_archive_sort' )
    ? lunara_get_review_archive_sort()
    : 'release_desc';
$review_year = function_exists( 'lunara_get_review_archive_year' )
    ? lunara_get_review_archive_year()
    : '';
$pagination_args = array();
if ( 'release_desc' !== $review_sort ) {
    $pagination_args['sort'] = $review_sort;
}
if ( '' !== $review_year ) {
    $pagination_args['review_year'] = $review_year;
}

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

echo lunara_render_review_archive_shell(
    array(
        'classes'     => 'lunara-review-archive-page',
        'kicker'      => $archive_kicker,
        'title'       => $archive_title,
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts(),
        'current_sort' => $review_sort,
        'empty_title' => get_theme_mod( 'lunara_archive_review_empty_text', __( 'No reviews have been filed yet...', 'lunara-film' ) ),
        'empty_copy'  => '',
        'pagination'  => paginate_links(
            array(
                'add_args'  => empty( $pagination_args ) ? false : $pagination_args,
                'prev_text' => $pagination_prev,
                'next_text' => $pagination_next,
            )
        ),
    )
);

get_footer();
