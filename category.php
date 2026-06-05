<?php
/**
 * Category archive template for Lunara editorial archives.
 */

get_header();

$term           = get_queried_object();
$archive_title  = $term instanceof WP_Term ? single_cat_title( '', false ) : __( 'Category Archive', 'lunara-film' );
$archive_copy   = '';
$archive_kicker = __( 'Category Archive', 'lunara-film' );
$source_label   = __( 'Category file', 'lunara-film' );
$run_title      = __( 'More From This Category', 'lunara-film' );
$run_copy       = '';
$rail_title     = __( 'What This Category Is Holding Beside The Lead', 'lunara-film' );
$rail_copy      = '';

if ( $term instanceof WP_Term ) {
    $archive_copy = trim( wp_strip_all_tags( term_description( $term, 'category' ) ) );
    $source_label = $term->name;

    if ( lunara_is_editorial_category_term( $term ) ) {
        $archive_kicker = __( 'Lunara Journal', 'lunara-film' );
        $run_title      = sprintf(
            /* translators: %s: Category name. */
            __( 'More %s Signal', 'lunara-film' ),
            $term->name
        );
        $run_copy       = '';
        $rail_title     = __( 'What This Editorial Lane Is Holding Beside The Lead', 'lunara-film' );
        $rail_copy      = '';
    }
}

$archive_copy = '';

echo lunara_render_editorial_archive_shell(
    array(
        'classes'     => 'lunara-editorial-archive-page lunara-category-archive-page',
        'kicker'      => $archive_kicker,
        'title'       => $archive_title,
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts(),
        'source_label'=> $source_label,
        'lead_rail_title' => $rail_title,
        'lead_rail_copy'  => $rail_copy,
        'run_title'       => $run_title,
        'run_copy'        => $run_copy,
        'empty_title' => __( 'Nothing has been filed in this archive yet.', 'lunara-film' ),
    )
);

get_footer();
