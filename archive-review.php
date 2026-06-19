<?php
/**
 * Review archive template.
 */

get_header();

$review_sort = function_exists( 'lunara_get_review_archive_sort' )
    ? lunara_get_review_archive_sort()
    : 'release_desc';

$archive_kicker = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_kicker', 'Criticism Desk' )
    : 'Criticism Desk';
$archive_title = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_title', 'Lunara Reviews' )
    : 'Lunara Reviews';
$archive_copy = function_exists( 'lunara_theme_mod_text' )
    ? lunara_theme_mod_text( 'lunara_reviews_archive_copy', 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.' )
    : 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.';

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
