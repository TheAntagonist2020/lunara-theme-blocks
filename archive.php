<?php
/**
 * Generic archive template for Lunara editorial archives.
 */

get_header();

$archive_title  = trim( wp_strip_all_tags( get_the_archive_title() ) );
$archive_copy   = '';
$archive_kicker = __( 'Archive', 'lunara-film' );
$source_label   = __( 'Editorial file', 'lunara-film' );
$run_title      = __( 'More From The Archive', 'lunara-film' );
$run_copy       = '';
$rail_title     = __( 'What The Archive Is Holding Beside The Lead', 'lunara-film' );
$rail_copy      = '';
$empty_note_title = __( 'Dispatches, reactions, essays, and signal worth following.', 'lunara-film' );
$empty_note_copy  = '';
$editorial_sort   = function_exists( 'lunara_get_editorial_archive_sort' ) ? lunara_get_editorial_archive_sort() : 'date_desc';

if ( is_category() ) {
    $archive_kicker = get_theme_mod( 'lunara_archive_category_kicker', __( 'Category Archive', 'lunara-film' ) );
    $source_label   = __( 'Category file', 'lunara-film' );
} elseif ( is_tag() ) {
    $archive_kicker = get_theme_mod( 'lunara_archive_tag_kicker', __( 'Tagged Signal', 'lunara-film' ) );
    $source_label   = __( 'Tag focus', 'lunara-film' );
    $run_title      = __( 'More Tagged Signal', 'lunara-film' );
    $run_copy       = '';
    $rail_title     = __( 'What This Tag Is Holding Beside The Lead', 'lunara-film' );
    $empty_note_title = __( 'Tagged routes become useful once the archive starts echoing itself.', 'lunara-film' );
    $empty_note_copy  = '';
} elseif ( is_author() ) {
    $archive_kicker = get_theme_mod( 'lunara_archive_author_kicker', __( 'Byline Archive', 'lunara-film' ) );
    $source_label   = __( 'Byline focus', 'lunara-film' );
    $run_title      = __( 'More From This Byline', 'lunara-film' );
    $run_copy       = '';
    $rail_title     = __( 'What This Byline Is Holding Beside The Lead', 'lunara-film' );
    $empty_note_title = __( 'Byline archives become richer as the publication deepens.', 'lunara-film' );
    $empty_note_copy  = '';
} elseif ( is_date() ) {
    $archive_kicker = get_theme_mod( 'lunara_archive_date_kicker', __( 'Calendar File', 'lunara-film' ) );
    $source_label   = __( 'Calendar focus', 'lunara-film' );
    $run_title      = __( 'More From This Filing Window', 'lunara-film' );
    $run_copy       = '';
    $rail_title     = __( 'What This Filing Window Is Holding Beside The Lead', 'lunara-film' );
    $empty_note_title = __( 'Calendar files matter once they start recording motion.', 'lunara-film' );
    $empty_note_copy  = '';
}

if ( '' === $archive_title ) {
    $archive_title = __( 'Archive', 'lunara-film' );
}

$pagination = paginate_links(
    array(
        'add_args' => 'date_desc' === $editorial_sort ? false : array( 'sort' => $editorial_sort ),
    )
);

echo lunara_render_editorial_archive_shell(
    array(
        'classes'     => 'lunara-editorial-archive-page',
        'kicker'      => $archive_kicker,
        'title'       => $archive_title,
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts(),
        'current_sort'=> $editorial_sort,
        'pagination'  => $pagination,
        'source_label'=> $source_label,
        'lead_rail_title' => $rail_title,
        'lead_rail_copy'  => $rail_copy,
        'run_title'       => $run_title,
        'run_copy'        => $run_copy,
        'empty_note_title'=> $empty_note_title,
        'empty_note_copy' => $empty_note_copy,
        'empty_title' => __( 'Nothing has been filed in this archive yet.', 'lunara-film' ),
    )
);

get_footer();
