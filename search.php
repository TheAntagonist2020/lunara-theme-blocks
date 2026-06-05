<?php
/**
 * Search results template.
 *
 * @package Lunara_Film
 */

get_header();

$query_text    = trim( get_search_query() );
$result_posts  = lunara_get_loop_posts();
$oscar_matches = function_exists( 'lunara_get_oscars_search_matches' ) ? lunara_get_oscars_search_matches( $query_text, 6 ) : array();
$recovery_hits = function_exists( 'lunara_get_search_recovery_routes' ) ? lunara_get_search_recovery_routes( $query_text, 6 ) : array();
$result_count  = 0;

global $wp_query;

if ( isset( $wp_query ) && $wp_query instanceof WP_Query ) {
    $result_count = max( 0, intval( $wp_query->found_posts ) );
}

if ( $result_count <= 0 ) {
    $result_count = count( $result_posts );
}

$archive_title = '' !== $query_text
    ? sprintf(
        /* translators: %s: Search query. */
        __( 'Searching Lunara for "%s"', 'lunara-film' ),
        $query_text
    )
    : __( 'Search Lunara Film', 'lunara-film' );

$archive_copy = '';

$overview_lines = array(
    array(
        'label' => __( 'Matches', 'lunara-film' ),
        'value' => number_format_i18n( $result_count ),
    ),
    array(
        'label' => __( 'Query', 'lunara-film' ),
        'value' => '' !== $query_text ? $query_text : __( 'None entered', 'lunara-film' ),
    ),
    array(
        'label' => __( 'Search Scope', 'lunara-film' ),
        'value' => __( 'Reviews / Editorial / Direct Oscar matches', 'lunara-film' ),
    ),
);

if ( ! empty( $oscar_matches ) ) {
    $overview_lines[] = array(
        'label' => __( 'Ledger Hits', 'lunara-film' ),
        'value' => number_format_i18n( count( $oscar_matches ) ),
    );
}

if ( empty( $result_posts ) && empty( $oscar_matches ) && ! empty( $recovery_hits ) ) {
    $overview_lines[] = array(
        'label' => __( 'Closest Routes', 'lunara-film' ),
        'value' => number_format_i18n( count( $recovery_hits ) ),
    );
}
?>
<main id="primary" class="site-main lunara-archive-page lunara-search-page">
    <section class="lunara-home-section lunara-archive-hero">
        <div class="lunara-editorial-archive-hero-shell">
            <div class="lunara-editorial-archive-hero-copy-wrap">
                <p class="lunara-archive-hero-kicker"><?php echo esc_html( get_theme_mod( 'lunara_search_kicker', __( 'Search Desk', 'lunara-film' ) ) ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php echo esc_html( $archive_title ); ?></h1>
                <?php if ( '' !== $archive_copy ) : ?>
                    <p class="lunara-archive-hero-copy"><?php echo esc_html( $archive_copy ); ?></p>
                <?php endif; ?>
            </div>
            <aside class="lunara-editorial-archive-debrief" aria-label="<?php esc_attr_e( 'Search summary', 'lunara-film' ); ?>">
                <p class="lunara-editorial-archive-debrief-kicker"><?php esc_html_e( 'At A Glance', 'lunara-film' ); ?></p>
                <ul class="lunara-editorial-archive-debrief-list">
                    <?php foreach ( $overview_lines as $line ) : ?>
                        <li>
                            <strong><?php echo esc_html( $line['label'] ); ?></strong>
                            <span><?php echo esc_html( $line['value'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </section>

    <?php if ( ! empty( $oscar_matches ) ) : ?>
        <section class="lunara-home-section lunara-search-oscar-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Oscar Signal', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Direct Ledger Matches', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-oscar-grid">
                <?php foreach ( $oscar_matches as $match ) : ?>
                    <article class="lunara-search-oscar-card">
                        <a class="lunara-search-oscar-link" href="<?php echo esc_url( $match['url'] ); ?>">
                            <p class="lunara-search-oscar-kicker"><?php echo esc_html( $match['kicker'] ); ?></p>
                            <h3 class="lunara-search-oscar-title"><?php echo esc_html( $match['title'] ); ?></h3>
                            <?php if ( ! empty( $match['meta'] ) ) : ?>
                                <p class="lunara-search-oscar-meta"><?php echo esc_html( $match['meta'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $result_posts ) ) : ?>
        <section class="lunara-home-section lunara-search-results-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Search Run', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Results On The Record', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-results-grid">
                <?php foreach ( $result_posts as $post_item ) : ?>
                    <?php
                    if ( ! ( $post_item instanceof WP_Post ) ) {
                        continue;
                    }

                    if ( 'review' === $post_item->post_type && function_exists( 'lunara_render_review_grid_card' ) ) {
                        echo lunara_render_review_grid_card( $post_item->ID );
                        continue;
                    }

                    if ( 'post' === $post_item->post_type && function_exists( 'lunara_render_dispatch_archive_card' ) ) {
                        echo lunara_render_dispatch_archive_card( $post_item->ID );
                        continue;
                    }
                    ?>
                    <article class="lunara-search-result-card">
                        <a class="lunara-search-result-link" href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">
                            <p class="lunara-search-result-kicker"><?php echo esc_html( get_post_type_object( $post_item->post_type )->labels->singular_name ?? __( 'Entry', 'lunara-film' ) ); ?></p>
                            <h3 class="lunara-search-result-title"><?php echo esc_html( get_the_title( $post_item ) ); ?></h3>
                            <p class="lunara-search-result-copy"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_item ) ), absint( get_theme_mod( 'lunara_search_excerpt_words', 22 ) ) ) ); ?></p>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php the_posts_pagination(); ?>
        </section>
    <?php elseif ( ! empty( $recovery_hits ) ) : ?>
        <section class="lunara-home-section lunara-search-recovery-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Closest Routes', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'You were close. These are the strongest nearby routes.', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-oscar-grid lunara-search-recovery-grid">
                <?php foreach ( $recovery_hits as $match ) : ?>
                    <article class="lunara-search-oscar-card lunara-search-recovery-card">
                        <a class="lunara-search-oscar-link" href="<?php echo esc_url( $match['url'] ); ?>">
                            <p class="lunara-search-oscar-kicker"><?php echo esc_html( $match['kicker'] ); ?></p>
                            <h3 class="lunara-search-oscar-title"><?php echo esc_html( $match['title'] ); ?></h3>
                            <?php if ( ! empty( $match['meta'] ) ) : ?>
                                <p class="lunara-search-oscar-meta"><?php echo esc_html( $match['meta'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ( empty( $oscar_matches ) ) : ?>
        <section class="lunara-home-section lunara-search-empty-shell">
            <div class="lunara-editorial-archive-empty-shell">
                <div class="lunara-archive-empty lunara-editorial-archive-empty">
                    <h2><?php esc_html_e( 'Nothing matched that search yet.', 'lunara-film' ); ?></h2>
                    <p><?php esc_html_e( 'Try a film title, a filmmaker, an Oscar category, or a broader keyword.', 'lunara-film' ); ?></p>
                </div>
                <div class="lunara-editorial-archive-empty-note">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Try Again', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Search for a title, person, or argument worth reopening.', 'lunara-film' ); ?></h2>
                    <form role="search" method="get" class="lunara-search-form lunara-search-form-shell" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <label class="screen-reader-text" for="lunara-search-input"><?php esc_html_e( 'Search for:', 'lunara-film' ); ?></label>
                        <input id="lunara-search-input" type="search" class="lunara-search-input" placeholder="<?php esc_attr_e( 'Search Lunara Film', 'lunara-film' ); ?>" value="<?php echo esc_attr( $query_text ); ?>" name="s" />
                        <button type="submit" class="lunara-btn lunara-btn-primary"><?php esc_html_e( 'Search', 'lunara-film' ); ?></button>
                    </form>
                </div>
            </div>
            <div class="lunara-search-empty-routes-shell">
                <div class="lunara-home-section-head lunara-search-results-head">
                    <div>
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Stay In The Record', 'lunara-film' ); ?></p>
                        <h2 class="lunara-section-title"><?php esc_html_e( 'Elsewhere in Lunara', 'lunara-film' ); ?></h2>
                    </div>
                </div>
                <div class="lunara-search-empty-routes-grid">
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Criticism', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Browse The Review Archive', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Enter The Reviews', 'lunara-film' ); ?></span>
                    </a>
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Ledger', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Open The Oscar Ledger', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Open The Ledger', 'lunara-film' ); ?></span>
                    </a>
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Editorial', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Return To The Journal', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Open The Journal', 'lunara-film' ); ?></span>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
get_footer();
