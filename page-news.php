<?php
/**
 * Dedicated Journal page template on the /news/ route.
 */

get_header();

$paged = max(
    1,
    intval( get_query_var( 'paged' ) ),
    intval( get_query_var( 'page' ) )
);

$news_query = new WP_Query(
    array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => 9,
        'paged'                  => $paged,
        'ignore_sticky_posts'    => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'tax_query'              => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => array( 'news' ),
                'operator' => 'IN',
            ),
        ),
    )
);

$news_page      = get_queried_object();
$archive_kicker = __( 'Lunara Journal', 'lunara-film' );
$archive_title  = $news_page instanceof WP_Post ? get_the_title( $news_page ) : __( 'Journal', 'lunara-film' );

if ( 'News' === trim( (string) $archive_title ) ) {
    $archive_title = __( 'Journal', 'lunara-film' );
}
$archive_copy   = $news_page instanceof WP_Post && function_exists( 'lunara_get_archive_intro_from_post' )
    ? lunara_get_archive_intro_from_post( $news_page )
    : '';

if ( '' === $archive_copy ) {
    $archive_copy = lunara_theme_mod_text(
        'lunara_journal_archive_copy',
        'This is the live editorial lane for news, quick reactions, longer think pieces, interviews, and podcast writing that should stand beside the reviews without being mistaken for them.'
    );
}
$archive_copy = '';

$pagination = paginate_links(
    array(
        'total'   => max( 1, intval( $news_query->max_num_pages ) ),
        'current' => $paged,
    )
);

echo lunara_render_news_archive_shell(
    array(
        'classes'     => 'lunara-editorial-archive-page lunara-news-archive-page',
        'kicker'      => $archive_kicker,
        'title'       => $archive_title,
        'copy'        => $archive_copy,
        'posts'       => lunara_get_loop_posts( $news_query ),
        'empty_title' => lunara_theme_mod_text(
            'lunara_journal_archive_empty_title',
            __( 'The desk is on standby, not off.', 'lunara-film' )
        ),
        'empty_copy'  => '',
        'pagination'  => $pagination,
        'source_label'=> lunara_theme_mod_text(
            'lunara_journal_archive_source_label',
            __( 'Breaking / Industry / Festival', 'lunara-film' )
        ),
        'lead_rail_kicker' => lunara_theme_mod_text(
            'lunara_journal_archive_lead_rail_kicker',
            __( 'In Rotation', 'lunara-film' )
        ),
        'lead_rail_title' => lunara_theme_mod_text(
            'lunara_journal_archive_lead_rail_title',
            __( 'What The Signal Is Holding Beside The Lead', 'lunara-film' )
        ),
        'lead_rail_copy' => '',
        'run_kicker' => lunara_theme_mod_text(
            'lunara_journal_archive_run_kicker',
            __( 'Archive Run', 'lunara-film' )
        ),
        'run_title' => lunara_theme_mod_text(
            'lunara_journal_archive_run_title',
            __( 'More From The Journal', 'lunara-film' )
        ),
        'run_copy' => '',
        'empty_note_title' => lunara_theme_mod_text(
            'lunara_journal_archive_empty_note_title',
            __( 'Breaking items, industry shifts, and the stories worth moving on quickly.', 'lunara-film' )
        ),
        'empty_note_copy' => '',
        'standby_kicker' => lunara_theme_mod_text(
            'lunara_journal_archive_standby_kicker',
            __( 'Stay On Signal', 'lunara-film' )
        ),
        'standby_title' => lunara_theme_mod_text(
            'lunara_journal_archive_standby_title',
            __( 'The publication is still alive around the dispatch desk.', 'lunara-film' )
        ),
        'standby_copy' => '',
        'live_section_order' => lunara_theme_mod_text(
            'lunara_journal_archive_live_section_order',
            'spotlight,run,pagination'
        ),
        'empty_section_order' => lunara_theme_mod_text(
            'lunara_journal_archive_empty_section_order',
            'intro,standby'
        ),
        'standby_card_order' => lunara_theme_mod_text(
            'lunara_journal_archive_standby_card_order',
            'reviews,ledger,home'
        ),
        'standby_cards' => array(
            'reviews' => array(
                'kicker' => lunara_theme_mod_text( 'lunara_journal_standby_reviews_kicker', __( 'Criticism', 'lunara-film' ) ),
                'title'  => lunara_theme_mod_text( 'lunara_journal_standby_reviews_title', __( 'Browse The Review Archive', 'lunara-film' ) ),
                'copy'   => '',
                'button' => lunara_theme_mod_text( 'lunara_journal_standby_reviews_button', __( 'Enter The Reviews', 'lunara-film' ) ),
                'url'    => lunara_theme_mod_url( 'lunara_journal_standby_reviews_url', get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ),
            ),
            'ledger' => array(
                'kicker' => lunara_theme_mod_text( 'lunara_journal_standby_ledger_kicker', __( 'Ledger', 'lunara-film' ) ),
                'title'  => lunara_theme_mod_text( 'lunara_journal_standby_ledger_title', __( 'Step Into The Oscar Ledger', 'lunara-film' ) ),
                'copy'   => '',
                'button' => lunara_theme_mod_text( 'lunara_journal_standby_ledger_button', __( 'Open The Ledger', 'lunara-film' ) ),
                'url'    => lunara_theme_mod_url( 'lunara_journal_standby_ledger_url', home_url( '/oscars/' ) ),
            ),
            'home' => array(
                'kicker' => lunara_theme_mod_text( 'lunara_journal_standby_home_kicker', __( 'Front Door', 'lunara-film' ) ),
                'title'  => lunara_theme_mod_text( 'lunara_journal_standby_home_title', __( 'Return To The Live Homepage', 'lunara-film' ) ),
                'copy'   => '',
                'button' => lunara_theme_mod_text( 'lunara_journal_standby_home_button', __( 'Go To Lunara', 'lunara-film' ) ),
                'url'    => lunara_theme_mod_url( 'lunara_journal_standby_home_url', home_url( '/' ) ),
            ),
        ),
    )
);

wp_reset_postdata();
get_footer();
