<?php
/**
 * Review archive template.
 */

get_header();

$review_sort = function_exists( 'lunara_get_review_archive_sort' )
    ? lunara_get_review_archive_sort()
    : 'release_desc';

$archive_kicker = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_kicker', 'Review Archive' )
    : 'Review Archive';
$archive_title = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_title', 'The Review Archive' )
    : 'The Review Archive';
$archive_copy = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_copy', '' )
    : '';
$archive_copy = '';

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
                'add_args' => 'release_desc' === $review_sort ? false : array( 'sort' => $review_sort ),
            )
        ),
    )
);

get_footer();
