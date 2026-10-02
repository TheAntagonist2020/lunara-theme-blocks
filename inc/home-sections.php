<?php
/**
 * Homepage section helpers: the card where-to-watch hook and the Latest
 * Reviews renderer.
 *
 * The Oscars data builders that used to live here (snapshot, spotlight, deep
 * cuts, winner cards, rotating showcase) moved to inc/oscars-data.php in
 * Theme 3.2.90, which the loader requires just before this file.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lunara_card_where_to_watch' ) ) {
    /**
     * "Where to watch" markup for a review/journal card — JustWatch slot.
     *
     * Returns '' by default so the card never shows a half-built streaming row.
     * When JustWatch's API lands, hook the lunara_card_where_to_watch_html filter
     * to return provider chips for the post's IMDb title ID; the card already
     * reserves the slot and carries the data it needs.
     *
     * @param int $post_id Review/journal post ID.
     * @return string
     */
    function lunara_card_where_to_watch( $post_id ) {
        $post_id = (int) $post_id;
        $tt      = function_exists( 'lunara_get_review_imdb_title_id' ) ? (string) lunara_get_review_imdb_title_id( $post_id ) : '';

        /**
         * Filter the card "Where to watch" markup (JustWatch stub).
         *
         * @param string $html    Default markup (empty).
         * @param int    $post_id Card post ID.
         * @param string $tt      IMDb title ID (lowercased) or ''.
         */
        return (string) apply_filters( 'lunara_card_where_to_watch_html', '', $post_id, $tt );
    }
}

/**
 * Render the homepage latest/current-release review block.
 *
 * This definition loads before the monolithic fallback in functions.php, so it
 * becomes the active renderer for the lunara/latest-reviews block.
 */
if ( ! function_exists( 'lunara_render_homepage_latest_reviews' ) ) {
    function lunara_render_homepage_latest_reviews( $attrs = array() ) {
        if ( function_exists( 'lunara_home_carousel_is_adopted' ) && lunara_home_carousel_is_adopted( 'reviews' ) ) {
            return lunara_render_home_reviews_carousel();
        }
        $attrs = is_array( $attrs ) ? $attrs : array();

        $count     = isset( $attrs['count'] ) ? max( 1, min( 24, (int) $attrs['count'] ) ) : 8;
        $source    = isset( $attrs['source'] ) ? sanitize_key( (string) $attrs['source'] ) : 'curated';
        $heading   = isset( $attrs['heading'] ) ? trim( (string) $attrs['heading'] ) : '';
        $kicker    = isset( $attrs['kicker'] ) ? trim( (string) $attrs['kicker'] ) : '';
        $cta_label = isset( $attrs['ctaLabel'] ) ? trim( (string) $attrs['ctaLabel'] ) : '';
        $cta_url   = isset( $attrs['ctaUrl'] ) ? trim( (string) $attrs['ctaUrl'] ) : '';
        $source    = in_array( $source, array( 'curated', 'latest', 'hero' ), true ) ? $source : 'curated';

        $current_release_ids = function_exists( 'lunara_home_latest_review_ids' ) ? lunara_home_latest_review_ids() : array();
        $has_current_release = ! empty( $current_release_ids );

        if ( '' === $heading ) {
            $heading = __( 'Latest Reviews', 'lunara-film' );
        }
        if ( '' === $kicker ) {
            $kicker = $has_current_release ? __( 'Current Release Spotlight', 'lunara-film' ) : __( 'Lunara Reviews', 'lunara-film' );
        }
        if ( '' === $cta_label ) {
            $cta_label = __( 'All Reviews', 'lunara-film' );
        }
        if ( '' === $cta_url ) {
            $cta_url = home_url( '/reviews/' );
        }

        if ( function_exists( 'lunara_repair_mojibake_text' ) ) {
            $heading   = lunara_repair_mojibake_text( $heading );
            $kicker    = lunara_repair_mojibake_text( $kicker );
            $cta_label = lunara_repair_mojibake_text( $cta_label );
        }

        if ( $has_current_release && function_exists( 'lunara_home_latest_reviews_query' ) ) {
            $latest = lunara_home_latest_reviews_query( $count );
            $source = 'current-release';
        } elseif ( 'latest' === $source && function_exists( 'lunara_home_latest_reviews_query' ) ) {
            $latest = lunara_home_latest_reviews_query( $count );
        } elseif ( 'latest' === $source && function_exists( 'lunara_latest_reviews_query' ) ) {
            $latest = lunara_latest_reviews_query( $count );
        } elseif ( 'hero' === $source && function_exists( 'lunara_home_hero_reviews_query' ) ) {
            $latest = lunara_home_hero_reviews_query( $count );
        } elseif ( function_exists( 'lunara_home_featured_reviews_query' ) ) {
            $latest = lunara_home_featured_reviews_query( $count );
        } elseif ( function_exists( 'lunara_latest_reviews_query' ) ) {
            $latest = lunara_latest_reviews_query( $count );
        } else {
            $latest = new WP_Query(
                array(
                    'post_type'      => 'review',
                    'posts_per_page' => $count,
                    'post_status'    => 'publish',
                    'no_found_rows'  => true,
                )
            );
        }

        if ( ! ( $latest instanceof WP_Query ) || ! $latest->have_posts() ) {
            return '';
        }

        ob_start();
        ?>
        <section class="lunara-home-section lunara-home-slot-latest-reviews lunara-latest-reviews-section" data-lunara-site-studio-section="latest-reviews" data-review-source="<?php echo esc_attr( $source ); ?>" data-lunara-carousel aria-label="<?php esc_attr_e( 'Latest Reviews', 'lunara-film' ); ?>">
            <div class="lunara-home-section-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
                    <h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
                </div>
                <div class="lunara-home-section-head-actions">
                    <div class="lunara-poster-carousel-controls" data-lunara-carousel-controls>
                        <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-prev" data-lunara-carousel-prev aria-label="<?php esc_attr_e( 'Previous reviews', 'lunara-film' ); ?>">&#8592;</button>
                        <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-next" data-lunara-carousel-next aria-label="<?php esc_attr_e( 'Next reviews', 'lunara-film' ); ?>">&#8594;</button>
                    </div>
                    <a class="lunara-section-link" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
                </div>
            </div>
            <div class="lunara-review-grid lunara-review-archive-uniform lunara-review-rail-track" data-lunara-carousel-track role="list" tabindex="0" aria-label="<?php esc_attr_e( 'Latest reviews carousel', 'lunara-film' ); ?>">
                <?php
                while ( $latest->have_posts() ) :
                    $latest->the_post();
                    $rid                = get_the_ID();
                    $is_current_release = in_array( $rid, $current_release_ids, true );
                    $score              = get_post_meta( $rid, '_lunara_score', true );
                    // Always give a card a blurb: hand-set pull-quote first, otherwise
                    // fall back to the review's excerpt / opening lines so no card is empty.
                    $quote              = function_exists( 'lunara_get_review_card_pull_quote' )
                        ? lunara_get_review_card_pull_quote( $rid, 22, true )
                        : wp_trim_words( wp_strip_all_tags( get_the_excerpt( $rid ) ), 22, '...' );
                    $thumb_attrs        = array(
                        'class'         => 'lunara-review-grid-poster',
                        'loading'       => 'lazy',
                        'decoding'      => 'async',
                        'fetchpriority' => 'low',
                        'sizes'         => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
                    );
                    $image_data = function_exists( 'lunara_get_review_card_image_data' )
                        ? lunara_get_review_card_image_data( $rid, 'lunara-review-card', $thumb_attrs )
                        : array(
                            'url'  => has_post_thumbnail( $rid ) ? get_the_post_thumbnail_url( $rid, 'medium_large' ) : '',
                            'html' => has_post_thumbnail( $rid ) ? get_the_post_thumbnail( $rid, 'medium_large', $thumb_attrs ) : '',
                        );
                    $thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
                    $has_thumb_html  = ! empty( $image_data['html'] );
                    $use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
                    $has_card_media  = $has_thumb_html || $use_fallback_bg;
                    ?>
                    <article class="lunara-review-grid-card<?php echo $is_current_release ? ' is-current-release-spotlight' : ''; ?> <?php echo $has_card_media ? 'has-visual' : 'has-no-visual'; ?>">
                        <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                            <?php if ( $has_card_media ) : ?>
                                <div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                                    <?php if ( $has_thumb_html ) : ?>
                                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php endif; ?>
                                    <?php if ( $score && function_exists( 'lunara_render_stars' ) ) : ?>
                                        <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="lunara-review-grid-copy">
                                <p class="lunara-review-grid-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                                <?php if ( ! $has_card_media && $score && function_exists( 'lunara_render_stars' ) ) : ?>
                                    <span class="lunara-score-badge is-inline-score"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                                <?php endif; ?>
                                <?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
                                    <?php echo lunara_render_trailer_card_badge( $rid, 'review-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php endif; ?>
                                <h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
                                <?php if ( '' !== trim( $quote ) ) : ?>
                                    <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                                <?php endif; ?>
                                <?php if ( $is_current_release ) : ?>
                                    <p class="lunara-review-grid-updated"><?php esc_html_e( 'Current Release Spotlight', 'lunara-film' ); ?></p>
                                <?php endif; ?>
                                <?php
                                // Where-to-watch slot (JustWatch). Stub: returns '' until wired,
                                // so it stays invisible; lights up across every card when ready.
                                $card_watch = function_exists( 'lunara_card_where_to_watch' ) ? lunara_card_where_to_watch( $rid ) : '';
                                ?>
                                <?php if ( '' !== trim( (string) $card_watch ) ) : ?>
                                    <div class="lunara-review-grid-watch"><?php echo wp_kses_post( $card_watch ); ?></div>
                                <?php endif; ?>
                                <div class="lunara-review-grid-cta">
                                    <span class="lunara-review-grid-cta-score">
                                        <?php if ( $score && function_exists( 'lunara_render_stars' ) ) : ?>
                                            <?php echo wp_kses_post( lunara_render_stars( $score ) ); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="lunara-review-grid-cta-read"><?php esc_html_e( 'Read the review', 'lunara-film' ); ?> <span class="lunara-review-grid-cta-arrow" aria-hidden="true">&rarr;</span></span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }
}
