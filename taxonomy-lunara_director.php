<?php
/**
 * Director archive template.
 */

get_header();

$term         = get_queried_object();
$archive_copy = '';

if ( $term instanceof WP_Term ) {
    $archive_copy = trim( wp_strip_all_tags( term_description( $term, $term->taxonomy ) ) );

    if ( '' === $archive_copy ) {
        $archive_copy = sprintf(
            /* translators: %s: Director name. */
            __( 'Every Lunara review currently filed under %s lives here, so the archive reads like a focused filmography rather than a scattershot tag page.', 'lunara-film' ),
            $term->name
        );
    }
}

echo lunara_render_review_archive_shell(
    array(
        'classes'     => 'lunara-review-archive-page lunara-director-archive-page',
        'kicker'      => __( 'Director Archive', 'lunara-film' ),
        'title'       => $term instanceof WP_Term ? $term->name : __( 'Director Archive', 'lunara-film' ),
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts(),
        'empty_title' => __( 'No reviews found for this director.', 'lunara-film' ),
        'empty_copy'  => '',
    )
);

get_footer();
