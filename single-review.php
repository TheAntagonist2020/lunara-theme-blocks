<?php
/**
 * Single review template for Lunara criticism.
 */

if ( 'review' !== get_post_type() ) {
    $fallback = locate_template(
        array(
            'single-' . get_post_type() . '.php',
            'singular.php',
            'index.php',
        ),
        false,
        false
    );

    if ( $fallback ) {
        include $fallback;
        return;
    }
}

if ( function_exists( 'lunara_prepend_review_metadata' ) ) {
    remove_filter( 'the_content', 'lunara_prepend_review_metadata', 5 );
}

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

        $post_id          = get_the_ID();
        $score            = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
        $year             = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $director         = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        $runtime          = trim( (string) get_post_meta( $post_id, '_lunara_runtime', true ) );
        $studio           = trim( (string) get_post_meta( $post_id, '_lunara_studio', true ) );
        $where            = trim( (string) get_post_meta( $post_id, '_lunara_where', true ) );
        $review_meta_line = lunara_get_review_card_meta( $post_id );
        $review_label     = trim( (string) get_post_meta( $post_id, '_lunara_review_lane_label_override', true ) );
        $standfirst       = trim( (string) get_post_meta( $post_id, '_lunara_review_standfirst', true ) );
        $hide_standfirst  = '1' === get_post_meta( $post_id, '_lunara_review_hide_standfirst', true );
        $archive_label    = trim( (string) get_post_meta( $post_id, '_lunara_review_archive_cta_label', true ) );
        $archive_url_meta = trim( (string) get_post_meta( $post_id, '_lunara_review_archive_url_override', true ) );
        $hide_where_card  = '1' === get_post_meta( $post_id, '_lunara_review_hide_where_card', true );
        $hide_detail_card = '1' === get_post_meta( $post_id, '_lunara_review_hide_details_card', true );
        $default_label    = trim( (string) get_theme_mod( 'lunara_review_default_label', 'Lunara Review' ) );
        $show_standfirst_default = (bool) get_theme_mod( 'lunara_review_show_standfirst_default', true );
        $show_where_default      = (bool) get_theme_mod( 'lunara_review_show_where_card_default', true );
        $show_details_default    = (bool) get_theme_mod( 'lunara_review_show_details_card_default', true );
        $excerpt          = '';
        if ( ! $hide_standfirst ) {
            if ( '' !== $standfirst ) {
                $excerpt = $standfirst;
            } elseif ( $show_standfirst_default ) {
                $excerpt = has_excerpt( $post_id ) ? get_the_excerpt() : lunara_card_excerpt( $post_id, 42 );
            }
        }
        $review_tt        = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
        $ledger_counts    = '' !== $review_tt ? lunara_get_oscar_ledger_counts( $review_tt ) : array();
        $ledger_pill      = '' !== $review_tt ? lunara_render_oscar_ledger_pill( $review_tt, $ledger_counts ) : '';
        $debrief_block    = do_shortcode( '[lunara_debrief]' );
        $debrief_parts    = function_exists( 'lunara_split_review_debrief_block' )
            ? lunara_split_review_debrief_block( $debrief_block )
            : array(
                'signature' => $debrief_block,
                'pairings'  => '',
            );
        $hero_visual      = function_exists( 'lunara_render_review_visual_slot' )
            ? lunara_render_review_visual_slot(
                $post_id,
                'hero_banner',
                array(
                    'context' => 'hero',
                    'loading' => 'eager',
                )
            )
            : '';
        if ( '' === trim( $hero_visual ) && has_post_thumbnail( $post_id ) ) {
            $hero_attachment_id = get_post_thumbnail_id( $post_id );
            $hero_full_image    = $hero_attachment_id ? wp_get_attachment_image_src( $hero_attachment_id, 'full' ) : false;
            $is_poster_hero     = (
                is_array( $hero_full_image ) &&
                ! empty( $hero_full_image[1] ) &&
                ! empty( $hero_full_image[2] ) &&
                (int) $hero_full_image[2] > (int) $hero_full_image[1] * 1.2
            );
            $hero_profile = function_exists( 'lunara_get_review_image_profile' )
                ? lunara_get_review_image_profile( $is_poster_hero ? 'lunara-review-visual-image lunara-review-visual--poster-hero' : 'lunara-review-visual-image' )
                : ( $is_poster_hero ? array(
                    'size'   => 'lunara-poster-library',
                    'width'  => 1000,
                    'height' => 1500,
                    'sizes'  => '(max-width: 900px) min(78vw, 420px), 460px',
                ) : array(
                    'size'   => 'lunara-hero-spotlight',
                    'width'  => 960,
                    'height' => 540,
                    'sizes'  => '(max-width: 900px) 100vw, 960px',
                ) );
            $hero_source  = (string) get_the_post_thumbnail_url( $post_id, 'full' );
            $hero_image   = get_the_post_thumbnail(
                $post_id,
                'full',
                array(
                    'class'    => 'lunara-review-visual-image',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                    'width'    => isset( $hero_profile['width'] ) ? absint( $hero_profile['width'] ) : 960,
                    'height'   => isset( $hero_profile['height'] ) ? absint( $hero_profile['height'] ) : 540,
                    'sizes'    => isset( $hero_profile['sizes'] ) ? (string) $hero_profile['sizes'] : '(max-width: 900px) 100vw, 960px',
                    'data-no-lazy'   => '1',
                    'data-skip-lazy' => '1',
                )
            );

            if ( function_exists( 'lunara_lock_review_image_markup' ) ) {
                $hero_image = lunara_lock_review_image_markup( $hero_image, $hero_source, $hero_profile );
            }

            $hero_visual = sprintf(
                '<figure class="lunara-review-visual lunara-review-visual--featured lunara-review-visual--hero%2$s"><div class="lunara-review-visual-frame">%1$s</div></figure>',
                $hero_image,
                $is_poster_hero ? ' lunara-review-visual--poster-hero' : ''
            );
        }
        $debrief_media    = function_exists( 'lunara_get_review_debrief_signature_media_html' )
            ? lunara_get_review_debrief_signature_media_html( $post_id )
            : '';
        $related_query    = function_exists( 'lunara_get_related_review_posts' ) ? lunara_get_related_review_posts( $post_id, absint( get_theme_mod( 'lunara_review_related_count', 4 ) ) ) : null;
        $archive_url      = '' !== $archive_url_meta ? $archive_url_meta : get_post_type_archive_link( 'review' );
        $archive_url      = is_string( $archive_url ) && '' !== $archive_url ? $archive_url : home_url( '/reviews/' );
        $director_url     = '';
        $tmdb_providers     = '';
        $tmdb_provider_text = wp_strip_all_tags( $tmdb_providers );
        $watch_url          = '';

        // PRESERVED FROM REGRESSED LIVE 20260510 — defensive filter so TMDB
        // outages don't render their error page inside the Where to Watch card.
        if (
            false !== stripos( $tmdb_provider_text, 'This site is temporarily unavailable' ) ||
            false !== stripos( $tmdb_provider_text, 'critical error' ) ||
            false !== stripos( $tmdb_provider_text, 'site owner has been notified' )
        ) {
            $tmdb_providers = '';
        }

        if ( '' !== trim( $tmdb_providers ) && preg_match( '~https://www\.themoviedb\.org/[^"\']+/watch\?locale=[A-Z]{2}~', $tmdb_providers, $watch_match ) ) {
            $watch_url = $watch_match[0];
        }

        $has_where = ( '' !== $where );

        if ( '' !== $director ) {
            $director_term = get_term_by( 'name', $director, 'lunara_director' );
            if ( $director_term instanceof WP_Term ) {
                $term_link = get_term_link( $director_term );
                if ( ! is_wp_error( $term_link ) && is_string( $term_link ) && '' !== $term_link ) {
                    $director_url = $term_link;
                }
            }
        }

        $detail_items = array_filter(
            array(
                'Year'      => $year,
                'Director'  => $director,
                'Runtime'   => $runtime,
                'Studio'    => $studio,
                'Published' => get_the_date( 'F j, Y', $post_id ),
            ),
            static function( $value ) {
                return '' !== trim( (string) $value );
            }
        );
        $show_where_card = $has_where && ! $hide_where_card && $show_where_default;
        $show_detail_card = ! empty( $detail_items ) && ! $hide_detail_card && $show_details_default;
        $show_ledger_card = '' !== $ledger_pill;
        ?>
        <main id="primary" class="site-main lunara-archive-page lunara-review-single-page">
            <article <?php post_class( 'lunara-journal-single lunara-review-single' ); ?>>
                <section class="lunara-review-single-hero">
                    <div class="lunara-review-single-hero-inner">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( '' !== $review_label ? $review_label : $default_label ); ?></p>
                        <h1 class="lunara-review-single-title"><?php the_title(); ?></h1>

                        <?php if ( '' !== trim( $excerpt ) ) : ?>
                            <p class="lunara-review-single-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        <?php endif; ?>

                        <div class="lunara-review-single-meta">
                            <?php if ( '' !== $review_meta_line ) : ?>
                                <span><?php echo esc_html( $review_meta_line ); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                            <?php if ( '' !== $score ) : ?>
                                <span><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="lunara-review-single-body">
                    <div class="lunara-review-single-body-grid">
                        <div class="lunara-review-single-content">
                            <?php if ( '' !== trim( $hero_visual ) ) : ?>
                                <div class="lunara-review-single-cinematic-hero">
                                    <?php echo $hero_visual; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            <?php endif; ?>
                            <?php
                            $review_raw_content = get_the_content();

                            if (
                                function_exists( 'lunara_render_trailer_module' ) &&
                                function_exists( 'lunara_get_trailer_placement' ) &&
                                'after_hero' === lunara_get_trailer_placement( $post_id ) &&
                                ! has_shortcode( $review_raw_content, 'lunara_trailer' )
                            ) {
                                echo lunara_render_trailer_module( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }

                            $review_content_html = apply_filters( 'the_content', $review_raw_content );
                            if ( function_exists( 'lunara_insert_trailer_into_content_html' ) && ! has_shortcode( $review_raw_content, 'lunara_trailer' ) ) {
                                $review_content_html = lunara_insert_trailer_into_content_html( $review_content_html, $post_id );
                            }
                            echo $review_content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                        </div>

                        <aside class="lunara-review-single-rail" aria-label="<?php esc_attr_e( 'Review details', 'lunara-film' ); ?>">
                            <div class="lunara-review-single-rail-sticky">
                                <?php if ( $show_ledger_card ) : ?>
                                    <div class="lunara-journal-rail-card lunara-review-single-ledger-card">
                                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Oscar Ledger', 'lunara-film' ); ?></p>
                                        <div class="lunara-review-single-ledger-pill-wrap">
                                            <?php echo wp_kses_post( $ledger_pill ); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $show_where_card ) : ?>
                                    <div class="lunara-journal-rail-card lunara-review-single-where-card">
                                        <p class="lunara-home-section-kicker"><?php echo esc_html( get_theme_mod( 'lunara_review_where_kicker', __( 'Where to Watch', 'lunara-film' ) ) ); ?></p>
                                        <?php if ( '' !== $where ) : ?>
                                            <div class="lunara-review-single-where-value">
                                                <?php echo wp_kses_post( lunara_render_review_where_links( $where, get_the_title( $post_id ), $watch_url ) ); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $show_detail_card ) : ?>
                                    <div class="lunara-journal-rail-card lunara-review-single-details">
                                        <p class="lunara-home-section-kicker"><?php echo esc_html( get_theme_mod( 'lunara_review_details_kicker', __( 'Review Details', 'lunara-film' ) ) ); ?></p>
                                        <ul class="lunara-review-single-detail-list">
                                            <?php foreach ( $detail_items as $label => $value ) : ?>
                                                <li>
                                                    <strong><?php echo esc_html( $label ); ?></strong>
                                                    <span><?php echo esc_html( $value ); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <div class="lunara-review-single-rail-actions">
                                    <a class="lunara-btn lunara-btn-primary" href="<?php echo esc_url( $archive_url ); ?>">
                                        <?php echo esc_html( '' !== $archive_label ? $archive_label : get_theme_mod( 'lunara_review_archive_button', __( 'Browse Reviews', 'lunara-film' ) ) ); ?>
                                    </a>
                                    <?php if ( '' !== $director_url ) : ?>
                                        <a class="lunara-btn lunara-btn-secondary" href="<?php echo esc_url( $director_url ); ?>">
                                            <?php echo esc_html( get_theme_mod( 'lunara_review_director_button', __( 'Director Archive', 'lunara-film' ) ) ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <?php if ( '' !== trim( wp_strip_all_tags( $debrief_block ) ) ) : ?>
                <section class="lunara-review-single-debrief-section lunara-review-single-debrief-shell">
                    <div class="lunara-review-single-debrief-wrap<?php echo '' !== $debrief_media ? ' has-signature-media' : ''; ?>">
                        <?php if ( '' !== $debrief_media ) : ?>
                            <?php echo $debrief_media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endif; ?>
                        <div class="lunara-review-single-debrief">
                            <?php echo $debrief_parts['signature']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                    <?php if ( '' !== trim( (string) $debrief_parts['pairings'] ) ) : ?>
                        <div class="lunara-review-single-debrief lunara-review-single-debrief--pairings">
                            <?php echo $debrief_parts['pairings']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

                <?php if ( $related_query instanceof WP_Query && $related_query->have_posts() ) : ?>
                    <section class="lunara-home-section lunara-review-related">
                        <div class="lunara-home-section-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php echo esc_html( get_theme_mod( 'lunara_review_related_kicker', __( 'Continue Watching', 'lunara-film' ) ) ); ?></p>
                                <h2 class="lunara-section-title"><?php echo esc_html( get_theme_mod( 'lunara_review_related_title', __( 'More Lunara Criticism', 'lunara-film' ) ) ); ?></h2>
                            </div>
                            <a class="lunara-section-link" href="<?php echo esc_url( $archive_url ); ?>">
                                <?php esc_html_e( 'Open Reviews', 'lunara-film' ); ?>
                            </a>
                        </div>

                        <div class="lunara-review-grid lunara-review-related-grid">
                            <?php
                            while ( $related_query->have_posts() ) :
                                $related_query->the_post();
                                echo lunara_render_review_grid_card( get_the_ID() );
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </section>
                <?php endif; ?>
            </article>
        </main>
        <?php
    endwhile;
endif;

get_footer();
