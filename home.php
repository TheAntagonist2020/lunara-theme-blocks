<?php
/**
 * Posts index template.
 */

get_header();

$posts_page_id   = absint( get_option( 'page_for_posts' ) );
$posts_page      = $posts_page_id > 0 ? get_post( $posts_page_id ) : null;
$archive_kicker  = lunara_theme_mod_text( 'lunara_journal_archive_kicker', 'The Journal' );
$archive_title   = $posts_page instanceof WP_Post
    ? get_the_title( $posts_page )
    : lunara_theme_mod_text( 'lunara_journal_archive_title', 'News, Reactions, Essays, and Audio' );
$archive_copy    = '';
$editorial_sort  = function_exists( 'lunara_get_editorial_archive_sort' ) ? lunara_get_editorial_archive_sort() : 'date_desc';

if ( $posts_page instanceof WP_Post ) {
    $archive_copy = function_exists( 'lunara_get_archive_intro_from_post' )
        ? lunara_get_archive_intro_from_post( $posts_page )
        : trim( wp_strip_all_tags( has_excerpt( $posts_page ) ? $posts_page->post_excerpt : $posts_page->post_content ) );
}

if ( '' === $archive_copy ) {
    $archive_copy = lunara_theme_mod_text(
        'lunara_journal_archive_copy',
        'This is the live editorial lane for news, quick reactions, longer think pieces, interviews, and podcast writing that should stand beside the reviews without being mistaken for them.'
    );
}

$pagination = paginate_links(
    array(
        'add_args' => 'date_desc' === $editorial_sort ? false : array( 'sort' => $editorial_sort ),
    )
);

echo lunara_render_editorial_archive_shell(
    array(
        'classes'      => 'lunara-editorial-archive-page lunara-journal-archive-page',
        'kicker'       => $archive_kicker,
        'title'        => $archive_title,
        'copy'         => $archive_copy,
        'posts'        => lunara_get_loop_posts(),
        'current_sort' => $editorial_sort,
        'pagination'   => $pagination,
        'empty_title'  => __( 'No journal entries yet.', 'lunara-film' ),
        'empty_copy'   => __( 'News, reactions, essays, and podcast posts will appear here automatically as they are published.', 'lunara-film' ),
    )
);

get_footer();
