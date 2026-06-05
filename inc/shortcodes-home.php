<?php
/**
 * Legacy homepage shortcodes.
 *
 * These remain available for older page content, but the canonical live
 * homepage path is now the section-based front-page template.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shortcode: Homepage Content
 */
function lunara_home_shortcode() {
    ob_start();
    ?>
    <?php echo do_shortcode('[lunara_carousel set="homepage"]'); ?>

    <div class="lunara-tagline">
        <p class="lunara-tagline-text">Film criticism and the living record of the Oscars.</p>
    </div>

    <section class="lunara-section">
        <div class="lunara-section-header">
            <h2 class="lunara-section-title">Latest Reviews</h2>
        </div>
        <?php echo do_shortcode('[lunara_reviews count="3"]'); ?>
        <div class="text-center" style="margin-top: 30px;">
            <a href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" class="lunara-btn">View All Reviews</a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lunara_home', 'lunara_home_shortcode' );

/**
 * Shortcode: Display Reviews
 */
function lunara_reviews_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'count' => 6 ), $atts );
    $count = intval( $atts['count'] );
    if ( $count === 0 ) { $count = 6; }

    $query = new WP_Query( array(
        'post_type'      => 'review',
        'posts_per_page' => $count < 0 ? -1 : $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'ignore_sticky_posts' => true,
    ) );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No reviews yet.</p>';
    }

    ob_start();
    echo '<div class="lunara-review-grid lunara-review-archive-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $review_id = get_the_ID();
        $score     = get_post_meta( $review_id, '_lunara_score', true );
        $quote     = function_exists( 'lunara_get_review_card_pull_quote' )
            ? lunara_get_review_card_pull_quote( $review_id, 22 )
            : wp_trim_words( wp_strip_all_tags( get_the_excerpt( $review_id ) ), 22, '...' );
        $thumb_attrs = array(
            'class'    => 'lunara-review-grid-poster',
            'loading'  => 'lazy',
            'decoding' => 'async',
            'sizes'    => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
        );
        $image_data = function_exists( 'lunara_get_review_card_image_data' )
            ? lunara_get_review_card_image_data( $review_id, 'lunara-review-card', $thumb_attrs )
            : array(
                'url'  => has_post_thumbnail() ? get_the_post_thumbnail_url( $review_id, 'medium_large' ) : '',
                'html' => has_post_thumbnail() ? get_the_post_thumbnail( $review_id, 'medium_large', $thumb_attrs ) : '',
            );
        $thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
        $has_thumb_html  = ! empty( $image_data['html'] );
        $use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
        ?>
        <article class="lunara-review-grid-card lunara-review-archive-card">
            <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                <div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                    <?php if ( $has_thumb_html ) : ?>
                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <?php if ( $score ) : ?><span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span><?php endif; ?>
                </div>
                <div class="lunara-review-grid-copy">
                    <h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
                    <?php if ( '' !== trim( $quote ) ) : ?>
                        <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'lunara_reviews', 'lunara_reviews_shortcode' );

/**
 * Shortcode: Display Posts by Category
 */
function lunara_posts_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'category' => '',
        'count'    => 6
    ), $atts );

    $query = new WP_Query( array(
        'post_type'      => 'post',
        'category_name'  => sanitize_text_field( $atts['category'] ),
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No posts found.</p>';
    }

    ob_start();
    echo '<div class="lunara-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        ?>
        <article class="lunara-card">
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'medium', array( 'class' => 'lunara-card-thumb' ) ); ?>
                </a>
            <?php endif; ?>
            <h3 class="lunara-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <div class="lunara-card-meta"><?php echo get_the_date( 'F j, Y' ); ?></div>
            <div class="lunara-card-excerpt"><?php the_excerpt(); ?></div>
            <a href="<?php the_permalink(); ?>" class="lunara-btn">Read More</a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'lunara_posts', 'lunara_posts_shortcode' );
