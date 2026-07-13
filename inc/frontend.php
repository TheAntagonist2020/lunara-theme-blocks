<?php
/**
 * Frontend — footer, navigation, content filters, search, and animations.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_front_page_document_title( $title ) {
    if ( is_front_page() ) {
        return __( 'Lunara Film - Film Criticism and a Living Oscar Ledger', 'lunara-film' );
    }

    return $title;
}
add_filter( 'pre_get_document_title', 'lunara_front_page_document_title', 99 );

function lunara_get_home_identity_logo_id() {
    $home_logo_id = absint( get_option( 'lunara_home_identity_logo_id', 0 ) );

    if ( $home_logo_id ) {
        return $home_logo_id;
    }

    return absint( get_theme_mod( 'custom_logo' ) );
}

function lunara_home_brand_number_setting( $key, $default, $min, $max ) {
    $value = absint( get_theme_mod( $key, $default ) );

    if ( $value < $min ) {
        return absint( $min );
    }

    if ( $value > $max ) {
        return absint( $max );
    }

    return $value;
}

function lunara_home_select_setting( $key, $default, $allowed ) {
    $allowed = array_map( 'sanitize_key', (array) $allowed );
    $value   = sanitize_key( (string) get_theme_mod( $key, $default ) );

    if ( ! in_array( $value, $allowed, true ) ) {
        return sanitize_key( (string) $default );
    }

    return $value;
}

function lunara_home_front_door_excerpt( $text, $words = 24 ) {
    $text = trim( wp_strip_all_tags( (string) $text ) );

    if ( '' === $text ) {
        return '';
    }

    return wp_trim_words( $text, max( 8, absint( $words ) ), '...' );
}

function lunara_get_home_front_door_lead() {
    if ( function_exists( 'lunara_get_cinematic_hero_slides' ) ) {
        $slides = lunara_get_cinematic_hero_slides( 1 );
        if ( ! empty( $slides[0] ) && is_array( $slides[0] ) ) {
            $slide = $slides[0];

            return array(
                'kicker'  => trim( (string) ( $slide['kicker'] ?? __( 'Lead Editorial', 'lunara-film' ) ) ),
                'title'   => trim( (string) ( $slide['title'] ?? get_bloginfo( 'name' ) ) ),
                'excerpt' => lunara_home_front_door_excerpt( $slide['excerpt'] ?? '', 30 ),
                'url'     => trim( (string) ( $slide['url'] ?? home_url( '/' ) ) ),
                'cta'     => trim( (string) ( $slide['cta'] ?? __( 'Read the file', 'lunara-film' ) ) ),
                'image'   => trim( (string) ( $slide['image'] ?? '' ) ),
            );
        }
    }

    $latest_review = function_exists( 'lunara_get_latest_review_post' ) ? lunara_get_latest_review_post() : null;
    if ( $latest_review instanceof WP_Post ) {
        $image = function_exists( 'lunara_get_review_hero_image_url' )
            ? trim( (string) lunara_get_review_hero_image_url( $latest_review->ID ) )
            : '';

        if ( '' !== $image && function_exists( 'lunara_rightsize_backdrop_url' ) ) {
            $image = lunara_rightsize_backdrop_url( $image );
        }

        return array(
            'kicker'  => __( 'Latest Review', 'lunara-film' ),
            'title'   => get_the_title( $latest_review ),
            'excerpt' => lunara_home_front_door_excerpt( get_the_excerpt( $latest_review ), 30 ),
            'url'     => get_permalink( $latest_review ),
            'cta'     => __( 'Read the review', 'lunara-film' ),
            'image'   => $image,
        );
    }

    return array(
        'kicker'  => __( 'Lunara Film', 'lunara-film' ),
        'title'   => __( 'Film criticism, Journal files, and a living Oscar Ledger.', 'lunara-film' ),
        'excerpt' => __( 'The front door follows the work moving now: reviews, desk entries, and the archive record behind them.', 'lunara-film' ),
        'url'     => home_url( '/reviews/' ),
        'cta'     => __( 'Start with reviews', 'lunara-film' ),
        'image'   => '',
    );
}

function lunara_get_home_front_door_journal_signal() {
    $query = function_exists( 'lunara_home_dispatches_query' )
        ? lunara_home_dispatches_query( 1 )
        : new WP_Query(
            array(
                'post_type'      => array( 'journal', 'post' ),
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'no_found_rows'  => true,
            )
        );

    if ( $query instanceof WP_Query && ! empty( $query->posts[0] ) && $query->posts[0] instanceof WP_Post ) {
        $post_id = (int) $query->posts[0]->ID;
        $type    = function_exists( 'lunara_get_dispatch_type_label' )
            ? trim( (string) lunara_get_dispatch_type_label( $post_id ) )
            : __( 'Journal', 'lunara-film' );
        $updated = get_the_modified_date( 'M j, g:i A', $post_id );

        return array(
            'label' => __( 'Journal Pulse', 'lunara-film' ),
            'title' => get_the_title( $post_id ),
            'meta'  => trim( (string) $type . ( $updated ? ' / ' . $updated : '' ) ),
            'url'   => get_permalink( $post_id ),
        );
    }

    return array(
        'label' => __( 'Journal Pulse', 'lunara-film' ),
        'title' => __( 'Open the Journal desk', 'lunara-film' ),
        'meta'  => __( 'Latest files from the desk', 'lunara-film' ),
        'url'   => home_url( '/journal/' ),
    );
}

function lunara_get_home_front_door_oscar_signal() {
    $snapshot = function_exists( 'lunara_get_home_oscars_snapshot' ) ? lunara_get_home_oscars_snapshot() : array();
    if ( empty( $snapshot ) || ! is_array( $snapshot ) ) {
        return array(
            'label' => __( 'Oscar Ledger', 'lunara-film' ),
            'title' => __( 'Explore the Oscar record', 'lunara-film' ),
            'meta'  => __( 'Films, people, categories, and ceremonies', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        );
    }

    $best_picture = ! empty( $snapshot['best_picture'] ) && is_array( $snapshot['best_picture'] ) ? $snapshot['best_picture'] : array();
    $title        = ! empty( $best_picture['film'] )
        ? sprintf(
            /* translators: %s: Best Picture film title. */
            __( '%s leads the latest ceremony record', 'lunara-film' ),
            $best_picture['film']
        )
        : ( ! empty( $snapshot['ceremony_label'] ) ? $snapshot['ceremony_label'] : __( 'Oscar Ledger', 'lunara-film' ) );
    $summary      = ! empty( $snapshot['summary'] ) ? $snapshot['summary'] : ( $snapshot['winner_record'] ?? '' );
    $meta_bits    = array_filter(
        array(
            trim( (string) ( $snapshot['year_label'] ?? '' ) ),
            trim( (string) ( $snapshot['winner_record'] ?? '' ) ),
        )
    );

    return array(
        'label' => __( 'Oscar Ledger', 'lunara-film' ),
        'title' => $title,
        'meta'  => ! empty( $meta_bits ) ? implode( ' / ', array_slice( $meta_bits, 0, 2 ) ) : $summary,
        'url'   => ! empty( $snapshot['ceremony_url'] ) ? $snapshot['ceremony_url'] : home_url( '/oscars/' ),
    );
}

/**
 * Render the first-viewport editorial image as a discoverable LCP candidate.
 *
 * Local media uses WordPress image markup so srcset remains available. Remote
 * backdrops retain the already right-sized URL and explicit 16:9 dimensions.
 *
 * @param array $lead Front-door lead data.
 * @return string
 */
function lunara_home_front_door_lead_image( $lead ) {
    $image_url = trim( (string) ( $lead['image'] ?? '' ) );
    if ( '' === $image_url ) {
        return '';
    }

    $attrs = array(
        'class'         => 'lunara-home-front-desk-lead-media skip-lazy no-lazy',
        'alt'           => '',
        'loading'       => 'eager',
        'decoding'      => 'async',
        'fetchpriority' => 'high',
        'sizes'         => '(max-width: 1100px) calc(100vw - 32px), 900px',
    );

    $attachment_id = function_exists( 'attachment_url_to_postid' )
        ? absint( attachment_url_to_postid( (string) preg_replace( '/\?.*$/', '', $image_url ) ) )
        : 0;

    if ( $attachment_id ) {
        $image_html = wp_get_attachment_image( $attachment_id, 'lunara-hero-spotlight', false, $attrs );
        if ( '' !== trim( (string) $image_html ) ) {
            return (string) $image_html;
        }
    }

    return sprintf(
        '<img class="%1$s" src="%2$s" alt="" width="1920" height="1080" loading="eager" decoding="async" fetchpriority="high" sizes="%3$s">',
        esc_attr( $attrs['class'] ),
        esc_url( $image_url ),
        esc_attr( $attrs['sizes'] )
    );
}

function lunara_render_home_front_door() {
    if ( function_exists( 'lunara_render_plugin_backed_home_hero' ) && function_exists( 'lunara_home_cinematic_front_door_is_enabled' ) && lunara_home_cinematic_front_door_is_enabled() ) {
        $cinematic_front_door = trim( (string) lunara_render_plugin_backed_home_hero() );

        if ( '' !== $cinematic_front_door ) {
            return $cinematic_front_door;
        }
    }

    $reviews_url = get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' );
    $journal_url = get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' );
    $logo_id     = lunara_get_home_identity_logo_id();
    $logo_html   = '';
    $density     = lunara_home_select_setting( 'lunara_home_front_door_density', 'editorial', array( 'compact', 'editorial', 'showcase' ) );
    $prominence  = lunara_home_select_setting( 'lunara_home_route_card_prominence', 'strong', array( 'quiet', 'standard', 'strong' ) );
    $lead        = lunara_get_home_front_door_lead();
    $lead_image  = lunara_home_front_door_lead_image( $lead );
    $journal     = lunara_get_home_front_door_journal_signal();
    $oscar       = lunara_get_home_front_door_oscar_signal();
    $search_url  = function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/' );
    $search_name = function_exists( 'lunara_search_command_url' ) ? 'q' : 's';

    if ( $logo_id ) {
        // Timing-immune sizing: the settled dimensions ride ON the element as
        // an inline style, so the wordmark can never paint giant while an
        // optimizer (WP Rocket async/used CSS) is still delivering the
        // stylesheet — the exact big-then-small flash readers reported. The
        // var() indirection lets the breakpoint rules in
        // lunara_home_front_door_css() keep overriding tablet/mobile sizes.
        $logo_desktop_w = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_width', 1180, 520, 1600 );
        $logo_desktop_h = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_height', 312, 140, 420 );
        $logo_html      = wp_get_attachment_image(
            $logo_id,
            'full',
            false,
            array(
                'class'         => 'lunara-home-masthead-logo skip-lazy no-lazy',
                'loading'       => 'eager',
                'decoding'      => 'async',
                'fetchpriority' => 'auto',
                'alt'           => '',
                'style'         => sprintf(
                    'display:block;width:var(--lunara-masthead-logo-w,min(100%%,%dpx));height:auto;max-height:var(--lunara-masthead-logo-cap,clamp(118px,22vw,%dpx));object-fit:contain;',
                    (int) $logo_desktop_w,
                    (int) $logo_desktop_h
                ),
            )
        );

        // Core and performance plugins may promote an early attachment image
        // after its requested fetch priority has been evaluated. Finalize this
        // one decorative wordmark after those filters while preserving the
        // responsive srcset, dimensions, and all editor-selected media data.
        if ( is_string( $logo_html ) && '' !== $logo_html && class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $logo_processor = new WP_HTML_Tag_Processor( $logo_html );
            if ( $logo_processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
                $logo_processor->set_attribute( 'loading', 'eager' );
                $logo_processor->set_attribute( 'fetchpriority', 'auto' );
                $logo_html = $logo_processor->get_updated_html();
            }
        }
    }

    $routes = array(
        array(
            'label' => __( 'Criticism', 'lunara-film' ),
            'title' => __( 'Read Reviews', 'lunara-film' ),
            'copy'  => __( 'Arguments, scores, trailers, Debrief notes, and spoiler files when the film needs a second door.', 'lunara-film' ),
            'url'   => $reviews_url,
        ),
        array(
            'label' => __( 'Journal', 'lunara-film' ),
            'title' => __( 'Open Journal', 'lunara-film' ),
            'copy'  => __( 'Industry movement, trailer files, quick reactions, and larger work from the desk.', 'lunara-film' ),
            'url'   => $journal_url,
        ),
        array(
            'label' => __( 'Oscar Ledger', 'lunara-film' ),
            'title' => __( 'Explore Ledger', 'lunara-film' ),
            'copy'  => __( 'Films, people, categories, and ceremonies connected across the record.', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        ),
    );

    ob_start();
    ?>
    <section class="lunara-home-masthead lunara-home-front-desk is-density-<?php echo esc_attr( $density ); ?> is-route-<?php echo esc_attr( $prominence ); ?>" aria-labelledby="lunara-home-masthead-title">
        <div class="lunara-home-masthead-panel">
            <div class="lunara-home-masthead-identity">
                <p class="lunara-home-masthead-kicker"><?php esc_html_e( 'Live editorial front desk', 'lunara-film' ); ?></p>
                <h1 id="lunara-home-masthead-title" class="screen-reader-text lunara-screen-reader-text"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></h1>
                <div class="lunara-home-masthead-logo-frame" aria-hidden="true">
                    <?php
                    if ( $logo_html ) {
                        echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        ?>
                        <span class="lunara-home-masthead-logo-fallback"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></span>
                        <?php
                    }
                    ?>
                </div>
                <p class="lunara-home-masthead-dek"><?php esc_html_e( 'Reviews, Journal files, and the Oscar Ledger, edited as one publication in motion.', 'lunara-film' ); ?></p>
            </div>

            <nav class="lunara-home-signal-bar" aria-label="<?php esc_attr_e( 'Live desk signals', 'lunara-film' ); ?>">
                <span class="lunara-home-signal-bar-live"><span class="lunara-home-signal-bar-dot" aria-hidden="true"></span><?php esc_html_e( 'Live', 'lunara-film' ); ?></span>
                <a class="lunara-home-signal-bar-item" href="<?php echo esc_url( $lead['url'] ); ?>"><em><?php esc_html_e( 'Review', 'lunara-film' ); ?></em><span><?php echo esc_html( $lead['title'] ); ?></span></a>
                <a class="lunara-home-signal-bar-item" href="<?php echo esc_url( $journal['url'] ); ?>"><em><?php esc_html_e( 'Journal', 'lunara-film' ); ?></em><span><?php echo esc_html( $journal['title'] ); ?></span></a>
                <a class="lunara-home-signal-bar-item" href="<?php echo esc_url( $oscar['url'] ); ?>"><em><?php esc_html_e( 'Ledger', 'lunara-film' ); ?></em><span><?php echo esc_html( $oscar['title'] ); ?></span></a>
                <button type="button" class="lunara-home-signal-bar-item lunara-home-signal-bar-search" data-lunara-search-open><span><?php esc_html_e( 'Search the desk', 'lunara-film' ); ?></span></button>
            </nav>

            <div class="lunara-home-front-desk-grid">
                <article class="lunara-home-front-desk-lead<?php echo $lead_image ? ' has-image' : ' has-no-image'; ?>">
                    <?php echo $lead_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <a class="lunara-home-front-desk-lead-link" href="<?php echo esc_url( $lead['url'] ); ?>">
                        <span class="lunara-home-front-desk-label"><?php echo esc_html( $lead['kicker'] ); ?></span>
                        <h2 class="lunara-home-front-desk-title"><?php echo esc_html( $lead['title'] ); ?></h2>
                        <?php if ( ! empty( $lead['excerpt'] ) ) : ?>
                            <p class="lunara-home-front-desk-copy"><?php echo esc_html( $lead['excerpt'] ); ?></p>
                        <?php endif; ?>
                        <span class="lunara-home-front-desk-cta"><?php echo esc_html( $lead['cta'] ); ?> <span aria-hidden="true">&rarr;</span></span>
                    </a>
                </article>

                <div class="lunara-home-front-desk-stack" aria-label="<?php esc_attr_e( 'Live Lunara signals', 'lunara-film' ); ?>">
                    <a class="lunara-home-front-desk-signal is-journal" href="<?php echo esc_url( $journal['url'] ); ?>">
                        <span class="lunara-home-front-desk-label"><?php echo esc_html( $journal['label'] ); ?></span>
                        <strong><?php echo esc_html( $journal['title'] ); ?></strong>
                        <span><?php echo esc_html( $journal['meta'] ); ?></span>
                    </a>

                    <a class="lunara-home-front-desk-signal is-ledger" href="<?php echo esc_url( $oscar['url'] ); ?>">
                        <span class="lunara-home-front-desk-label"><?php echo esc_html( $oscar['label'] ); ?></span>
                        <strong><?php echo esc_html( $oscar['title'] ); ?></strong>
                        <span><?php echo esc_html( $oscar['meta'] ); ?></span>
                    </a>

                    <form role="search" method="get" class="lunara-home-front-desk-search" action="<?php echo esc_url( $search_url ); ?>">
                        <label class="lunara-home-front-desk-label" for="lunara-home-front-desk-search-input"><?php esc_html_e( 'Search Command', 'lunara-film' ); ?></label>
                        <div class="lunara-home-front-desk-search-row">
                            <input id="lunara-home-front-desk-search-input" type="search" name="<?php echo esc_attr( $search_name ); ?>" placeholder="<?php esc_attr_e( 'Search a film, review, Oscar category, or person', 'lunara-film' ); ?>" />
                            <button type="submit"><?php esc_html_e( 'Run', 'lunara-film' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <nav class="lunara-home-masthead-routes" aria-label="<?php esc_attr_e( 'Lunara section doors', 'lunara-film' ); ?>">
                <?php foreach ( $routes as $route ) : ?>
                    <a class="lunara-home-masthead-route" href="<?php echo esc_url( $route['url'] ); ?>">
                        <span class="lunara-home-masthead-route-label"><?php echo esc_html( $route['label'] ); ?></span>
                        <strong><?php echo esc_html( $route['title'] ); ?></strong>
                        <span><?php echo esc_html( $route['copy'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>
    <?php

    return (string) ob_get_clean();
}

function lunara_home_front_door_css() {
    if ( ! is_front_page() ) {
        return;
    }
    $desktop_logo_width   = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_width', 1180, 520, 1600 );
    $desktop_logo_height  = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_height', 312, 140, 420 );
    $tablet_logo_width    = min( $desktop_logo_width, 1040 );
    $mobile_logo_width    = lunara_home_brand_number_setting( 'lunara_home_logo_mobile_max_width', 720, 280, 920 );
    $mobile_logo_height   = lunara_home_brand_number_setting( 'lunara_home_logo_mobile_max_height', 148, 88, 240 );
    $identity_logo_gap    = lunara_home_brand_number_setting( 'lunara_home_logo_vertical_gap', 20, 8, 48 );
    $masthead_top_pad     = lunara_home_brand_number_setting( 'lunara_home_masthead_top_padding', 36, 16, 72 );
    $masthead_bottom_pad  = lunara_home_brand_number_setting( 'lunara_home_masthead_bottom_padding', 32, 12, 70 );
    $masthead_bottom_gap  = lunara_home_brand_number_setting( 'lunara_home_masthead_bottom_gap', 26, 10, 72 );
    $route_card_min       = lunara_home_brand_number_setting( 'lunara_home_route_card_min_height', 126, 88, 190 );
    $mobile_top_pad       = max( 18, min( 30, $masthead_top_pad ) );
    $mobile_bottom_pad    = max( 16, min( 28, $masthead_bottom_pad ) );
    ?>
    <style id="lunara-home-front-door-vars">
    body.home .lunara-home-masthead {
        --lunara-home-masthead-top-pad-setting: <?php echo esc_html( $masthead_top_pad ); ?>px;
        --lunara-home-masthead-bottom-pad-setting: <?php echo esc_html( $masthead_bottom_pad ); ?>px;
        --lunara-home-masthead-gap-setting: <?php echo esc_html( $masthead_bottom_gap ); ?>px;
        --lunara-home-route-card-min-setting: <?php echo esc_html( $route_card_min ); ?>px;
        --lunara-home-logo-gap-setting: <?php echo esc_html( $identity_logo_gap ); ?>px;
        --lunara-home-logo-desktop-width-setting: <?php echo esc_html( $desktop_logo_width ); ?>px;
        --lunara-home-logo-desktop-height-setting: <?php echo esc_html( $desktop_logo_height ); ?>px;
        --lunara-home-logo-tablet-width-setting: <?php echo esc_html( $tablet_logo_width ); ?>px;
        --lunara-home-logo-mobile-width-setting: <?php echo esc_html( $mobile_logo_width ); ?>px;
        --lunara-home-logo-mobile-height-setting: <?php echo esc_html( $mobile_logo_height ); ?>px;
        --lunara-home-masthead-mobile-gap-setting: <?php echo esc_html( max( 16, min( 34, $masthead_bottom_gap ) ) ); ?>px;
        --lunara-home-masthead-mobile-top-setting: <?php echo esc_html( $mobile_top_pad ); ?>px;
        --lunara-home-masthead-mobile-bottom-setting: <?php echo esc_html( $mobile_bottom_pad ); ?>px;
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_home_front_door_css', 45 );

/**
 * Keep the masthead's layout CSS out of WP Rocket's used-CSS pipeline.
 *
 * Rocket's Remove Unused CSS collects inline styles into its async-applied
 * used-CSS blob; for the homepage masthead that opens a first-paint window
 * where the wordmark has no width constraint and flashes giant before
 * snapping small — the exact complaint readers reported. These exclusions
 * Preserve the cacheable Home bundle and its tiny settings block so the
 * first viewport never flashes an unconstrained wordmark.
 */
function lunara_rocket_preserve_front_door_css( $exclusions ) {
    $exclusions[] = 'lunara-home-front-door-vars';
    $exclusions[] = 'lunara-home-modules.css';
    return $exclusions;
}
add_filter( 'rocket_rucss_inline_content_exclusions', 'lunara_rocket_preserve_front_door_css' );
add_filter( 'rocket_rucss_inline_atts_exclusions', 'lunara_rocket_preserve_front_door_css' );
add_filter( 'rocket_rucss_external_exclusions', 'lunara_rocket_preserve_front_door_css' );

/**
 * Determine whether review-card and Pair It With component CSS is needed.
 */
function lunara_phase1c_review_components_needed() {
    if (
        is_front_page()
        || is_post_type_archive( 'review' )
        || is_page_template( 'page-reviews.php' )
        || is_page( 'reviews' )
        || is_singular( 'review' )
    ) {
        return true;
    }

    if ( is_singular() ) {
        $post = get_post();
        if ( $post instanceof WP_Post && (
            has_shortcode( (string) $post->post_content, 'lunara_pair_it_with' )
            || ( function_exists( 'has_block' ) && has_block( 'lunara/pairing', $post ) )
        ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Cacheable, route-aware replacements for the legacy inline runtime.
 */
function lunara_enqueue_phase1c_delivery_assets() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $public_runtime = lunara_resolve_theme_asset( 'assets/js/lunara-public-runtime.js' );
    if ( ! empty( $public_runtime['uri'] ) ) {
        wp_enqueue_script(
            'lunara-public-runtime',
            $public_runtime['uri'],
            array(),
            lunara_theme_asset_version( $public_runtime['path'] ),
            true
        );
        wp_script_add_data( 'lunara-public-runtime', 'strategy', 'defer' );
    }

    if ( lunara_phase1c_review_components_needed() ) {
        $review_components = lunara_resolve_theme_asset( 'assets/css/lunara-review-components.css' );
        if ( ! empty( $review_components['uri'] ) ) {
            wp_enqueue_style(
                'lunara-review-components',
                $review_components['uri'],
                array( 'lunara-style' ),
                lunara_theme_asset_version( $review_components['path'] )
            );
        }
    }

    if ( is_front_page() || is_page( 'oscars' ) || is_page_template( 'page-oscars.php' ) ) {
        $carousel_runtime = lunara_resolve_theme_asset( 'assets/js/lunara-scroll-carousel.js' );
        if ( ! empty( $carousel_runtime['uri'] ) ) {
            wp_enqueue_script(
                'lunara-scroll-carousel',
                $carousel_runtime['uri'],
                array(),
                lunara_theme_asset_version( $carousel_runtime['path'] ),
                true
            );
            wp_script_add_data( 'lunara-scroll-carousel', 'strategy', 'defer' );
        }
    }

    if ( is_front_page() ) {
        $home_runtime = lunara_resolve_theme_asset( 'assets/js/lunara-home-runtime.js' );
        if ( ! empty( $home_runtime['uri'] ) ) {
            wp_enqueue_script(
                'lunara-home-runtime',
                $home_runtime['uri'],
                array(),
                lunara_theme_asset_version( $home_runtime['path'] ),
                true
            );
            wp_script_add_data( 'lunara-home-runtime', 'strategy', 'defer' );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_phase1c_delivery_assets', 30 );

/**
 * The canonical Home renderer does not consume block or theme.json markup.
 *
 * WordPress core prints the full global stylesheet through actions in both
 * the header and footer, so dequeueing the handle alone cannot prevent the
 * 23 KB inline block. Remove those core callbacks on Home only; desktop and
 * mobile pixel comparisons confirmed this stylesheet has no visual effect.
 */
function lunara_disable_unused_home_global_styles() {
    if ( ! is_front_page() ) {
        return;
    }

    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'wp', 'lunara_disable_unused_home_global_styles', 0 );

/**
 * The canonical Home renderer contains no WordPress or Stackable blocks.
 * Pixel comparisons at 1440x900 and 390x844 showed zero changed pixels when
 * these unused styles were removed, so keep them off this route only.
 */
function lunara_trim_unused_home_styles() {
    if ( ! is_front_page() ) {
        return;
    }

    $unused_handles = array(
        'global-styles',
        'wp-block-library',
        'wp-block-library-theme',
        'lunara-stackable',
        'ct-stackable-styles',
        'jetpack_likes',
        'jetpack-global-styles-frontend-style',
        'ai_summarization',
    );

    foreach ( $unused_handles as $handle ) {
        wp_dequeue_style( $handle );
    }
}
add_action( 'wp_enqueue_scripts', 'lunara_trim_unused_home_styles', 999 );

function lunara_homepage_studio_signature_css() {
    if ( ! is_front_page() ) {
        return;
    }

    $section_gap = lunara_home_brand_number_setting( 'lunara_home_section_gap', 38, 20, 90 );
    $rhythm      = lunara_home_select_setting( 'lunara_home_first_section_rhythm', 'tight', array( 'tight', 'balanced', 'spacious' ) );
    $density_options        = array( 'compact', 'editorial', 'showcase' );
    $latest_reviews_density = lunara_home_select_setting( 'lunara_home_latest_reviews_density', 'editorial', $density_options );
    $journal_lane_density   = lunara_home_select_setting( 'lunara_home_journal_lane_density', 'editorial', $density_options );
    $oscar_facts_density    = lunara_home_select_setting( 'lunara_home_oscar_facts_density', 'editorial', $density_options );
    $oscar_picks_density    = lunara_home_select_setting( 'lunara_home_oscar_picks_density', 'editorial', $density_options );

    $latest_reviews_card_min = lunara_home_brand_number_setting( 'lunara_home_latest_reviews_card_min_height', 430, 340, 560 );
    $journal_card_min        = lunara_home_brand_number_setting( 'lunara_home_journal_card_min_height', 330, 250, 480 );
    $oscar_facts_card_min    = lunara_home_brand_number_setting( 'lunara_home_oscar_facts_card_min_height', 390, 300, 540 );
    $oscar_picks_card_min    = lunara_home_brand_number_setting( 'lunara_home_oscar_picks_card_min_height', 520, 380, 720 );

    $latest_reviews_gap_map         = array( 'compact' => 18, 'editorial' => 24, 'showcase' => 30 );
    $latest_reviews_quote_clamp_map = array( 'compact' => 2, 'editorial' => 3, 'showcase' => 4 );
    $journal_gap_map                = array( 'compact' => 16, 'editorial' => 22, 'showcase' => 28 );
    $journal_excerpt_clamp_map      = array( 'compact' => 2, 'editorial' => 3, 'showcase' => 4 );
    $oscar_facts_body_clamp_map     = array( 'compact' => 3, 'editorial' => 4, 'showcase' => 5 );
    $oscar_facts_text_pad_map       = array( 'compact' => 28, 'editorial' => 40, 'showcase' => 54 );
    $oscar_picks_gap_map            = array( 'compact' => 18, 'editorial' => 24, 'showcase' => 30 );
    $oscar_picks_column_min_map     = array( 'compact' => 320, 'editorial' => 380, 'showcase' => 420 );
    $oscar_picks_column_max_map     = array( 'compact' => 390, 'editorial' => 460, 'showcase' => 520 );
    $oscar_picks_mobile_column_map  = array( 'compact' => 82, 'editorial' => 86, 'showcase' => 90 );
    $oscar_picks_title_clamp_map    = array( 'compact' => 3, 'editorial' => 4, 'showcase' => 4 );
    $oscar_picks_copy_pad_map       = array( 'compact' => 18, 'editorial' => 22, 'showcase' => 26 );

    $latest_reviews_gap         = $latest_reviews_gap_map[ $latest_reviews_density ];
    $latest_reviews_quote_clamp = $latest_reviews_quote_clamp_map[ $latest_reviews_density ];
    $journal_gap                = $journal_gap_map[ $journal_lane_density ];
    $journal_excerpt_clamp      = $journal_excerpt_clamp_map[ $journal_lane_density ];
    $oscar_facts_body_clamp     = $oscar_facts_body_clamp_map[ $oscar_facts_density ];
    $oscar_facts_text_pad       = $oscar_facts_text_pad_map[ $oscar_facts_density ];
    $oscar_facts_media_min      = min( 420, max( 220, $oscar_facts_card_min - 70 ) );
    $oscar_facts_archival_min   = min( 330, max( 230, $oscar_facts_card_min - 110 ) );
    $oscar_picks_gap            = $oscar_picks_gap_map[ $oscar_picks_density ];
    $oscar_picks_column_min     = $oscar_picks_column_min_map[ $oscar_picks_density ];
    $oscar_picks_column_max     = $oscar_picks_column_max_map[ $oscar_picks_density ];
    $oscar_picks_mobile_column  = $oscar_picks_mobile_column_map[ $oscar_picks_density ];
    $oscar_picks_title_clamp    = $oscar_picks_title_clamp_map[ $oscar_picks_density ];
    $oscar_picks_copy_pad       = $oscar_picks_copy_pad_map[ $oscar_picks_density ];

    if ( 'balanced' === $rhythm ) {
        $section_gap = (int) round( $section_gap * 1.08 );
    } elseif ( 'spacious' === $rhythm ) {
        $section_gap = (int) round( $section_gap * 1.22 );
    } else {
        $section_gap = (int) round( $section_gap * 0.86 );
    }

    $section_gap = max( 18, min( 96, $section_gap ) );
    ?>
    <style id="lunara-homepage-studio-signature-css">
    body.home .lunara-front-page{--lunara-home-section-gap:<?php echo esc_html( $section_gap ); ?>px;--lunara-home-latest-reviews-card-min:<?php echo esc_html( $latest_reviews_card_min ); ?>px;--lunara-home-latest-reviews-gap:<?php echo esc_html( $latest_reviews_gap ); ?>px;--lunara-home-latest-reviews-quote-clamp:<?php echo esc_html( $latest_reviews_quote_clamp ); ?>;--lunara-home-journal-card-min:<?php echo esc_html( $journal_card_min ); ?>px;--lunara-home-journal-gap:<?php echo esc_html( $journal_gap ); ?>px;--lunara-home-journal-excerpt-clamp:<?php echo esc_html( $journal_excerpt_clamp ); ?>;--lunara-home-oscar-facts-card-min:<?php echo esc_html( $oscar_facts_card_min ); ?>px;--lunara-home-oscar-facts-media-min:<?php echo esc_html( $oscar_facts_media_min ); ?>px;--lunara-home-oscar-facts-archival-min:<?php echo esc_html( $oscar_facts_archival_min ); ?>px;--lunara-home-oscar-facts-body-clamp:<?php echo esc_html( $oscar_facts_body_clamp ); ?>;--lunara-home-oscar-facts-text-pad:<?php echo esc_html( $oscar_facts_text_pad ); ?>px;--lunara-home-oscar-picks-card-min:<?php echo esc_html( $oscar_picks_card_min ); ?>px;--lunara-home-oscar-picks-gap:<?php echo esc_html( $oscar_picks_gap ); ?>px;--lunara-home-oscar-picks-column-min:<?php echo esc_html( $oscar_picks_column_min ); ?>px;--lunara-home-oscar-picks-column-max:<?php echo esc_html( $oscar_picks_column_max ); ?>px;--lunara-home-oscar-picks-mobile-column:<?php echo esc_html( $oscar_picks_mobile_column ); ?>%;--lunara-home-oscar-picks-title-clamp:<?php echo esc_html( $oscar_picks_title_clamp ); ?>;--lunara-home-oscar-picks-copy-pad:<?php echo esc_html( $oscar_picks_copy_pad ); ?>px;}
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_homepage_studio_signature_css', 130 );

/**
 * Tighten the homepage opening viewport after the brand and density layers.
 */
function lunara_home_first_viewport_polish_css() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style id="lunara-home-first-viewport-polish-css">
    /*lunara-home-first-viewport-polish-css*/
    body.home .lunara-front-page,
    body.home main.lunara-front-page{gap:clamp(26px,4vw,58px)!important;}
    body.home .lunara-home-masthead{margin-bottom:clamp(12px,2vw,24px)!important;}
    body.home .lunara-front-page > .lunara-home-masthead + .lunara-home-section,
    body.home main.lunara-front-page > .lunara-home-masthead + .lunara-home-section{margin-top:clamp(10px,1.4vw,20px)!important;}
    body.home .lunara-home-masthead-panel{isolation:isolate;}
    body.home .lunara-home-masthead-logo{image-rendering:auto;}
    @media(min-width:1120px){
        body.home .lunara-home-masthead-panel{grid-template-columns:minmax(0,1.14fr) minmax(340px,.86fr);min-height:clamp(386px,46vh,520px);gap:clamp(28px,5vw,82px);padding-top:clamp(24px,3vw,42px);padding-bottom:clamp(24px,3vw,42px);}
        body.home .lunara-home-masthead-panel::after{left:50.8%;right:auto;top:clamp(38px,5vw,78px);bottom:clamp(38px,5vw,78px);width:1px;height:auto;background:linear-gradient(180deg,transparent,rgba(224,196,129,.38),transparent);}
        body.home .lunara-home-masthead-identity{justify-items:start;text-align:left;align-content:center;}
        body.home .lunara-home-masthead-logo-frame{justify-content:start;width:100%;}
        body.home .lunara-home-masthead-logo{width:min(100%,980px);max-height:clamp(148px,18vw,254px);}
        body.home .lunara-home-masthead-dek{max-width:46ch;margin:0;font-size:clamp(.98rem,1vw,1.08rem);line-height:1.5;}
        body.home .lunara-home-masthead-standard{justify-self:start;}
        body.home .lunara-home-masthead-routes{grid-template-columns:minmax(0,1fr);width:min(100%,430px);justify-self:end;align-self:center;margin:0;gap:10px;}
        body.home .lunara-home-masthead-route{min-height:clamp(92px,9vh,112px);padding:13px 15px;}
        body.home .lunara-home-masthead.is-density-showcase .lunara-home-masthead-route{min-height:clamp(100px,10vh,122px);}
        body.home .lunara-home-masthead-route span:last-child{font-size:.85rem;line-height:1.36;}
        body.home .lunara-home-front-desk .lunara-home-masthead-panel{grid-template-columns:minmax(0,1fr)!important;min-height:0!important;gap:24px!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-panel::after{display:none!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-identity{justify-items:center!important;text-align:center!important;align-content:start!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-logo-frame{justify-content:center!important;width:100%!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-logo{width:min(100%,<?php echo esc_html( $desktop_logo_width ); ?>px)!important;max-height:clamp(118px,22vw,<?php echo esc_html( $desktop_logo_height ); ?>px)!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-dek{max-width:58ch!important;margin:0 auto!important;text-align:center!important;font-size:1.08rem!important;line-height:1.58!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-routes{grid-template-columns:repeat(3,minmax(0,1fr))!important;width:min(100%,1120px)!important;justify-self:center!important;align-self:auto!important;margin:0 auto!important;}
        body.home .lunara-home-front-desk .lunara-home-masthead-route{min-height:calc(var(--lunara-home-route-card-min) - 18px)!important;}
    }
    @media(min-width:1120px) and (max-height:820px){
        body.home .lunara-home-masthead-panel{min-height:clamp(350px,42vh,440px);padding-top:clamp(20px,2.5vw,34px);padding-bottom:clamp(20px,2.5vw,34px);}
        body.home .lunara-home-masthead-logo{max-height:clamp(132px,16vw,222px);}
        body.home .lunara-home-masthead-route{min-height:86px;}
        body.home .lunara-home-masthead-dek{line-height:1.45;}
    }
    @media(max-width:820px){
        body.home .lunara-front-page,
        body.home main.lunara-front-page{gap:clamp(22px,7vw,38px)!important;}
        body.home .lunara-front-page > .lunara-home-masthead + .lunara-home-section,
        body.home main.lunara-front-page > .lunara-home-masthead + .lunara-home-section{margin-top:clamp(6px,2vw,12px)!important;}
        body.home .lunara-home-masthead-panel{gap:14px;}
        body.home .lunara-home-masthead-kicker{font-size:.68rem;}
        body.home .lunara-home-masthead-logo{max-height:clamp(92px,26vw,138px);}
        body.home .lunara-home-masthead-dek{max-width:34ch;}
        body.home .lunara-home-masthead-route span:last-child{font-size:.84rem;line-height:1.36;}
        body.home .lunara-dispatches-section{padding-top:clamp(12px,4vw,18px)!important;}
        body.home .lunara-dispatches-section .lunara-home-section-head{min-height:0!important;margin-bottom:8px!important;gap:10px!important;}
        body.home .lunara-dispatches-section .lunara-home-section-kicker,
        body.home .lunara-dispatches-section .lunara-home-section-title{opacity:1!important;transform:none!important;}
        body.home .lunara-dispatches-section .lunara-home-section-title{font-size:clamp(1.72rem,7vw,2.2rem)!important;line-height:1.05!important;}
        body.home .lunara-dispatches-section .lunara-home-section-summary{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:.95rem!important;line-height:1.42!important;}
        body.home .lunara-dispatches-section .lunara-journal-home-grid{margin-top:8px!important;}
    }
    @media(prefers-reduced-motion:reduce){
        body.home .lunara-home-masthead-panel,
        body.home .lunara-home-masthead-logo{transition:none!important;}
    }
    </style>
    <?php
}
// HEAD, not footer (3.1.53): this block carries the masthead's two-column
// grid. Printed in the footer, the first viewport painted single-column
// (giant centered wordmark) and snapped into columns only when the parser
// reached the end of the document — the logo flash readers reported,
// measured at 1180px-centered → 554px-left ~475ms apart on a fast
// connection. Settings-only CSS; nothing here needs footer context.
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Footer fallback.
 */
function lunara_footer_menu_fallback() {
    echo '<ul class="lunara-footer-fallback">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Oscar Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/editorial-policy/' ) ) . '">Editorial Policy</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
    echo '</ul>';
}

if ( ! function_exists( 'lunara_render_footer_link_list' ) ) {
    function lunara_render_footer_link_list( $items ) {
        $items = array_filter( (array) $items, static function ( $item ) {
            return ! empty( $item['label'] ) && ! empty( $item['url'] );
        } );

        if ( empty( $items ) ) {
            return;
        }

        echo '<ul class="lunara-footer-curated-list">';
        foreach ( $items as $item ) {
            printf(
                '<li><a href="%1$s">%2$s</a></li>',
                esc_url( $item['url'] ),
                esc_html( $item['label'] )
            );
        }
        echo '</ul>';
    }
}

/**
 * Optional legacy Lunara footer output.
 *
 * Blocksy should own the live footer shell by default. This renderer remains as
 * a fallback path that can be re-enabled through a filter if needed during the
 * transition.
 */
function lunara_render_custom_footer() {
    $show_logo  = get_theme_mod( 'lunara_footer_show_logo', true );
    $tagline    = get_theme_mod( 'lunara_footer_tagline', 'Film criticism and a living Oscar ledger.' );
    $col1_head  = get_theme_mod( 'lunara_footer_col1_heading', 'Editorial' );
    $col2_head  = get_theme_mod( 'lunara_footer_col2_heading', 'Oscar Ledger' );
    $col3_head  = get_theme_mod( 'lunara_footer_col3_heading', 'Utility' );
    $copyright  = get_theme_mod( 'lunara_footer_copyright', 'Lunara Film' );
    ?>
    <footer class="lunara-site-footer" role="contentinfo">
        <div class="lunara-footer-inner">
            <!-- Zone 1: Branded close -->
            <div class="lunara-footer-brand">
                <?php if ( $show_logo ) :
                    $custom_logo_id = get_theme_mod( 'custom_logo' );
                    if ( $custom_logo_id ) :
                        echo wp_get_attachment_image( $custom_logo_id, 'full', false, array(
                            'class'    => 'lunara-footer-logo skip-lazy no-lazy',
                            'loading'  => 'eager',
                            'decoding' => 'async',
                            'alt'      => get_bloginfo( 'name' ) . ' logo',
                        ) );
                    else : ?>
                        <span class="lunara-footer-wordmark"><?php bloginfo( 'name' ); ?></span>
                    <?php endif;
                endif; ?>
                <?php if ( $tagline ) : ?>
                    <p class="lunara-footer-tagline"><?php echo esc_html( $tagline ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Zone 2: Navigation columns -->
            <nav class="lunara-footer-nav-grid" aria-label="<?php esc_attr_e( 'Footer navigation', 'lunara-film' ); ?>">
                <div class="lunara-footer-nav-col">
                    <?php if ( $col1_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col1_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    lunara_render_footer_link_list( array(
                        array( 'label' => __( 'Home', 'lunara-film' ), 'url' => home_url( '/' ) ),
                        array( 'label' => __( 'Reviews', 'lunara-film' ), 'url' => get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ),
                        array( 'label' => __( 'Journal', 'lunara-film' ), 'url' => get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' ) ),
                        array( 'label' => __( 'About', 'lunara-film' ), 'url' => home_url( '/about/' ) ),
                        array( 'label' => __( 'Editorial Policy', 'lunara-film' ), 'url' => home_url( '/editorial-policy/' ) ),
                    ) );
                    ?>
                </div>
                <div class="lunara-footer-nav-col">
                    <?php if ( $col2_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col2_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    lunara_render_footer_link_list( array(
                        array( 'label' => __( 'Oscars', 'lunara-film' ), 'url' => home_url( '/oscars/' ) ),
                        array( 'label' => __( 'Categories', 'lunara-film' ), 'url' => home_url( '/oscars/categories/' ) ),
                        array( 'label' => __( 'Ceremonies', 'lunara-film' ), 'url' => home_url( '/oscars/ceremonies/' ) ),
                        array( 'label' => __( 'Full Ledger', 'lunara-film' ), 'url' => home_url( '/oscars/?view=table#oscars-research' ) ),
                    ) );
                    ?>
                </div>
                <div class="lunara-footer-nav-col">
                    <?php if ( $col3_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col3_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    $utility_links = array(
                        array( 'label' => __( 'Search', 'lunara-film' ), 'url' => function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/?s=' ) ),
                        array( 'label' => __( 'Contact', 'lunara-film' ), 'url' => home_url( '/contact/' ) ),
                        array( 'label' => __( 'RSS Feed', 'lunara-film' ), 'url' => get_bloginfo( 'rss2_url' ) ),
                    );
                    $privacy_url = get_privacy_policy_url();
                    if ( $privacy_url ) {
                        $utility_links[] = array( 'label' => __( 'Privacy', 'lunara-film' ), 'url' => $privacy_url );
                    }
                    lunara_render_footer_link_list( $utility_links );
                    ?>
                </div>
            </nav>

            <!-- Zone 3: Utility row -->
            <div class="lunara-footer-utility">
                <span class="lunara-footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $copyright ); ?></span>
                <?php $privacy_url = get_privacy_policy_url(); ?>
                <?php if ( $privacy_url ) : ?>
                    <span class="lunara-footer-legal">
                        <a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy', 'lunara-film' ); ?></a>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    <?php
}

if ( apply_filters( 'lunara_use_custom_footer', true ) ) {
    add_action( 'wp_footer', 'lunara_render_custom_footer', 1 );
    add_filter( 'blocksy:footer:has-widgets', '__return_false' );
    add_filter( 'blocksy:builder:footer:enabled', '__return_false' );
}

/* Footer menu fallbacks */
function lunara_footer_editorial_fallback() {
    $journal_url   = lunara_home_dispatch_archive_url();
    $journal_label = 'Journal';
    $posts_page_id = absint( get_option( 'page_for_posts' ) );
    $news_url      = home_url( '/news/' );

    if ( $posts_page_id > 0 ) {
        $posts_page_title = trim( wp_strip_all_tags( get_the_title( $posts_page_id ) ) );
        if ( '' !== $posts_page_title ) {
            $journal_label = $posts_page_title;
        }
    }

    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( $journal_url ) . '">' . esc_html( $journal_label ) . '</a></li>';
    if ( untrailingslashit( $journal_url ) !== untrailingslashit( $news_url ) && 'Journal' !== $journal_label ) {
        echo '<li><a href="' . esc_url( $news_url ) . '">Journal</a></li>';
    }
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '</ul>';
}

function lunara_footer_oscars_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/categories/' ) ) . '">Categories</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/ceremonies/' ) ) . '">Ceremonies</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/about/' ) ) . '">About the Ledger</a></li>';
    echo '</ul>';
}

function lunara_footer_utility_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/?s=' ) ) . '">Search</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
    echo '<li><a href="' . esc_url( get_feed_link() ) . '">RSS</a></li>';
    echo '</ul>';
}

/**
 * Map primary-nav utility paths to reliable fallback labels.
 */
function lunara_primary_menu_fallback_label_for_path( $path ) {
    $label_map = array(
        '/oscars/categories-page'         => 'Categories',
        '/oscars/categories'              => 'Categories',
        '/oscars/about-this-database-page' => 'About the Ledger',
        '/oscars/about'                   => 'About the Ledger',
        '/awards-tracker'                 => 'Awards Tracker',
        '/search'                         => 'Search',
    );

    if ( isset( $label_map[ $path ] ) ) {
        return $label_map[ $path ];
    }

    return '';
}

/**
 * Supply readable labels when a primary-nav item is configured as icon-only.
 */
function lunara_primary_menu_item_title_fallback( $title, $item, $args, $depth ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $title;
    }

    $plain_title = trim( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) ) );
    if ( '' !== $plain_title ) {
        return $title;
    }

    $item_url = isset( $item->url ) ? (string) $item->url : '';
    if ( '' === $item_url ) {
        return $title;
    }

    $path  = wp_parse_url( $item_url, PHP_URL_PATH );
    $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
    $label = lunara_primary_menu_fallback_label_for_path( $path );

    if ( '' !== $label ) {
        return esc_html( $label );
    }

    return $title;
}
add_filter( 'nav_menu_item_title', 'lunara_primary_menu_item_title_fallback', 10, 4 );

/**
 * Normalize icon-only primary-menu items before the walker renders them.
 */
function lunara_primary_menu_object_title_fallback( $sorted_menu_items, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location || ! is_array( $sorted_menu_items ) ) {
        return $sorted_menu_items;
    }

    foreach ( $sorted_menu_items as $item ) {
        if ( ! is_object( $item ) ) {
            continue;
        }

        $current_title = isset( $item->title ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $item->title, ENT_QUOTES, 'UTF-8' ) ) ) : '';
        if ( '' !== $current_title ) {
            continue;
        }

        $item_url = isset( $item->url ) ? (string) $item->url : '';
        if ( '' === $item_url ) {
            continue;
        }

        $path  = wp_parse_url( $item_url, PHP_URL_PATH );
        $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
        $label = lunara_primary_menu_fallback_label_for_path( $path );

        if ( '' === $label ) {
            continue;
        }

        $item->title = $label;

        if ( isset( $item->post_title ) && '' === trim( (string) $item->post_title ) ) {
            $item->post_title = $label;
        }
    }

    return $sorted_menu_items;
}
add_filter( 'wp_nav_menu_objects', 'lunara_primary_menu_object_title_fallback', 10, 2 );

/**
 * Ensure icon-only primary menu items still output a visible text label.
 */
function lunara_primary_menu_start_el_fallback( $item_output, $item, $depth, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $item_output;
    }

    $item_url = isset( $item->url ) ? (string) $item->url : '';
    if ( '' === $item_url ) {
        return $item_output;
    }

    $path  = wp_parse_url( $item_url, PHP_URL_PATH );
    $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
    $label = lunara_primary_menu_fallback_label_for_path( $path );
    if ( '' === $label || false !== strpos( $item_output, $label ) ) {
        return $item_output;
    }

    if ( ! preg_match( '/(<a\b[^>]*>)(.*?)(<\/a>)/is', $item_output, $matches ) ) {
        return $item_output;
    }

    $inner_html = preg_replace( '/<!--.*?-->/s', '', $matches[2] );
    $inner_html = preg_replace( '/<svg\b.*?<\/svg>/is', '', $inner_html );
    $plain_html = trim( wp_strip_all_tags( $inner_html ) );
    if ( '' !== $plain_html ) {
        return $item_output;
    }

    $fallback_markup = '<span class="lunara-menu-fallback-label">' . esc_html( $label ) . '</span>';
    return $matches[1] . $matches[2] . $fallback_markup . $matches[3];
}
add_filter( 'walker_nav_menu_start_el', 'lunara_primary_menu_start_el_fallback', 10, 4 );

/**
 * Review metadata prepended above single review content.
 */
function lunara_prepend_review_metadata( $content ) {
    if ( ! is_singular( 'review' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $director = get_post_meta( get_the_ID(), '_lunara_director', true );
    $year     = get_post_meta( get_the_ID(), '_lunara_year', true );
    $runtime  = get_post_meta( get_the_ID(), '_lunara_runtime', true );
    $studio   = get_post_meta( get_the_ID(), '_lunara_studio', true );

    $items = array();
    if ( $director ) $items[] = '<span><strong>Director:</strong> ' . esc_html( $director ) . '</span>';
    if ( $year )     $items[] = '<span><strong>Year:</strong> ' . esc_html( $year ) . '</span>';
    if ( $runtime )  $items[] = '<span><strong>Runtime:</strong> ' . esc_html( $runtime ) . '</span>';
    if ( $studio )   $items[] = '<span><strong>Studio:</strong> ' . esc_html( $studio ) . '</span>';

    if ( empty( $items ) ) {
        return $content;
    }

    $bar = '<div class="lunara-review-metadata">' . implode( '', $items ) . '</div>';
    return $bar . $content;
}
add_filter( 'the_content', 'lunara_prepend_review_metadata', 5 );

/**
 * Drop malformed srcset candidates injected by CDN/image optimizers.
 *
 * Some homepage poster images receive an extra candidate like:
 *   "...&_jb=custom 1440.00"
 * which is missing a valid width or density descriptor. Browsers then emit
 * warnings and may ignore the whole srcset. We keep only candidates with a
 * standard trailing descriptor.
 */
if ( ! function_exists( 'lunara_sanitize_srcset_value' ) ) {
    function lunara_sanitize_srcset_value( $srcset ) {
        $srcset = is_string( $srcset ) ? trim( html_entity_decode( $srcset, ENT_QUOTES, 'UTF-8' ) ) : '';
        if ( '' === $srcset ) {
            return '';
        }

        $candidates = preg_split( '/,\s*(?=(?:https?:)?\/\/|\/)/', $srcset );
        if ( ! is_array( $candidates ) || empty( $candidates ) ) {
            $candidates = array( $srcset );
        }

        $valid = array();
        foreach ( $candidates as $candidate ) {
            $candidate = trim( (string) $candidate );
            if ( '' === $candidate ) {
                continue;
            }

            $decoded_candidate = html_entity_decode( $candidate, ENT_QUOTES, 'UTF-8' );
            if ( preg_match( '/(?:[?&;](?:resize|fit)=0(?:%2c|,)nan)/i', $decoded_candidate ) || preg_match( '/(?:[?&;](?:w|h)=0(?:&|$))/i', $decoded_candidate ) ) {
                continue;
            }

            if ( preg_match( '/\s+\d+w$/', $candidate ) || preg_match( '/\s+\d+(?:\.\d+)?x$/', $candidate ) ) {
                $valid[] = $candidate;
            }
        }

        return implode( ', ', $valid );
    }
}

/**
 * Sanitize attachment image attributes after WordPress/CDN filters run.
 */
if ( ! function_exists( 'lunara_sanitize_attachment_image_attributes' ) ) {
    function lunara_sanitize_attachment_image_attributes( $attr ) {
        if ( empty( $attr['srcset'] ) ) {
            return $attr;
        }

        $sanitized = lunara_sanitize_srcset_value( (string) $attr['srcset'] );
        if ( '' === $sanitized ) {
            unset( $attr['srcset'], $attr['sizes'] );
            return $attr;
        }

        $attr['srcset'] = $sanitized;
        if ( false === strpos( $sanitized, ',' ) ) {
            unset( $attr['sizes'] );
        }

        return $attr;
    }
}
add_filter( 'wp_get_attachment_image_attributes', 'lunara_sanitize_attachment_image_attributes', 999 );

/**
 * Sanitize content image tags that may bypass wp_get_attachment_image().
 */
if ( ! function_exists( 'lunara_sanitize_content_image_tag' ) ) {
    function lunara_sanitize_content_image_tag( $filtered_image ) {
        $filtered_image = is_string( $filtered_image ) ? $filtered_image : '';
        if ( '' === $filtered_image || false === strpos( $filtered_image, 'srcset=' ) ) {
            return $filtered_image;
        }

        return preg_replace_callback(
            '/\s(srcset)=("|\')(.*?)\2/i',
            static function ( $matches ) {
                $sanitized = lunara_sanitize_srcset_value( html_entity_decode( (string) $matches[3], ENT_QUOTES, 'UTF-8' ) );
                if ( '' === $sanitized ) {
                    return '';
                }

                return ' ' . $matches[1] . '=' . $matches[2] . esc_attr( $sanitized ) . $matches[2];
            },
            $filtered_image
        );
    }
}
add_filter( 'wp_content_img_tag', 'lunara_sanitize_content_image_tag', 999 );

/**
 * Make search reflect the real Lunara content universe.
 */
if ( ! function_exists( 'lunara_configure_main_search_query' ) ) {
    function lunara_configure_main_search_query( $query ) {
        if ( ! ( $query instanceof WP_Query ) || is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
            return;
        }

        $query->set( 'post_type', array( 'review', 'post', 'page' ) );
        $query->set( 'post_status', 'publish' );
        $query->set( 'ignore_sticky_posts', true );
        $query->set( 'posts_per_page', 12 );
    }
}
add_action( 'pre_get_posts', 'lunara_configure_main_search_query' );

/**
 * Push exact and title-based matches higher in Lunara search results.
 */
if ( ! function_exists( 'lunara_boost_search_orderby' ) ) {
    function lunara_boost_search_orderby( $orderby, $query ) {
        if ( ! ( $query instanceof WP_Query ) || is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
            return $orderby;
        }

        global $wpdb;

        $search = trim( (string) $query->get( 's' ) );
        if ( '' === $search || ! ( $wpdb instanceof wpdb ) ) {
            return $orderby;
        }

        $like_any   = '%' . $wpdb->esc_like( $search ) . '%';
        $like_start = $wpdb->esc_like( $search ) . '%';
        $quoted_any = "'" . esc_sql( $like_any ) . "'";
        $quoted_start = "'" . esc_sql( $like_start ) . "'";
        $quoted_exact = "'" . esc_sql( $search ) . "'";

        $posts_table = $wpdb->posts;

        return "
            CASE
                WHEN {$posts_table}.post_title = {$quoted_exact} THEN 0
                WHEN {$posts_table}.post_title LIKE {$quoted_start} THEN 1
                WHEN {$posts_table}.post_title LIKE {$quoted_any} THEN 2
                WHEN {$posts_table}.post_excerpt LIKE {$quoted_any} THEN 3
                WHEN {$posts_table}.post_content LIKE {$quoted_any} THEN 4
                ELSE 5
            END ASC,
            CASE
                WHEN {$posts_table}.post_type = 'review' THEN 0
                WHEN {$posts_table}.post_type = 'post' THEN 1
                WHEN {$posts_table}.post_type = 'page' THEN 2
                ELSE 3
            END ASC,
            {$posts_table}.post_date DESC
        ";
    }
}
add_filter( 'posts_orderby', 'lunara_boost_search_orderby', 20, 2 );

/**
 * Build fast front-end search suggestions from posts/pages/reviews.
 */
if ( ! function_exists( 'lunara_get_post_search_suggestions' ) ) {
    function lunara_get_post_search_suggestions( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $posts_table  = $wpdb->posts;
        $like_any     = '%' . $wpdb->esc_like( $query_text ) . '%';
        $like_start   = $wpdb->esc_like( $query_text ) . '%';
        $quoted_any   = "'" . esc_sql( $like_any ) . "'";
        $quoted_start = "'" . esc_sql( $like_start ) . "'";
        $quoted_exact = "'" . esc_sql( $query_text ) . "'";

        $sql = $wpdb->prepare(
              "SELECT ID, post_title, post_type, post_date
               FROM {$posts_table}
               WHERE post_status = 'publish'
                 AND post_type IN ('review','post','page')
                 AND post_title LIKE %s
               ORDER BY
                  CASE
                      WHEN post_title = {$quoted_exact} THEN 0
                      WHEN post_title LIKE {$quoted_start} THEN 1
                      WHEN post_title LIKE {$quoted_any} THEN 2
                      ELSE 3
                  END ASC,
                  CASE
                      WHEN post_type = 'review' THEN 0
                      WHEN post_type = 'post' THEN 1
                      WHEN post_type = 'page' THEN 2
                    ELSE 3
                END ASC,
                post_date DESC
             LIMIT %d",
            $like_any,
            $limit
        );

        $rows = $wpdb->get_results( $sql, ARRAY_A );
        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return array();
        }

        $results = array();
        foreach ( $rows as $row ) {
            $post_id   = intval( $row['ID'] ?? 0 );
            $post_type = (string) ( $row['post_type'] ?? '' );
            $title     = trim( (string) ( $row['post_title'] ?? '' ) );
            if ( $post_id <= 0 ) {
                continue;
            }

            $score = function_exists( 'lunara_search_text_match_score' )
                ? lunara_search_text_match_score( $title, $query_text )
                : 0;

            if ( $score <= 0 ) {
                continue;
            }

            if ( 'review' === $post_type ) {
                $kicker = __( 'Review', 'lunara-film' );
            } elseif ( 'page' === $post_type ) {
                $kicker = __( 'Page', 'lunara-film' );
            } else {
                $kicker = function_exists( 'lunara_get_dispatch_type_label' ) ? lunara_get_dispatch_type_label( $post_id ) : __( 'Dispatch', 'lunara-film' );
            }

            $results[] = array(
                'kicker' => $kicker,
                'title'  => $title,
                'url'    => get_permalink( $post_id ),
                'score'  => $score,
            );
        }

        usort(
            $results,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return $results;
    }
}

/**
 * Normalize a label for typo-tolerant search recovery checks.
 */
if ( ! function_exists( 'lunara_normalize_search_recovery_label' ) ) {
    function lunara_normalize_search_recovery_label( $label ) {
        $label = strtolower( trim( (string) $label ) );
        $label = preg_replace( '/\(\d{4}\)/', '', $label );
        $label = preg_replace( '/[^a-z0-9]+/i', ' ', $label );
        $label = trim( preg_replace( '/\s+/', ' ', $label ) );

        return is_string( $label ) ? $label : '';
    }
}

/**
 * Pull typo-tolerant fallback routes when a search is weak or empty.
 */
if ( ! function_exists( 'lunara_get_search_recovery_routes' ) ) {
    function lunara_get_search_recovery_routes( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $normalized_query = lunara_normalize_search_recovery_label( $query_text );
        if ( '' === $normalized_query ) {
            return array();
        }

        $seed = substr( str_replace( ' ', '', $normalized_query ), 0, 3 );
        if ( '' === $seed ) {
            return array();
        }

        $seed_like = '%' . $wpdb->esc_like( $seed ) . '%';
        $matches   = array();

        $push_match = static function ( $key, $match ) use ( &$matches ) {
            if ( empty( $match['score'] ) ) {
                return;
            }

            if ( ! isset( $matches[ $key ] ) || intval( $match['score'] ) > intval( $matches[ $key ]['score'] ) ) {
                $matches[ $key ] = $match;
            }
        };

        $score_label = static function ( $label ) use ( $normalized_query ) {
            $normalized_label = lunara_normalize_search_recovery_label( $label );
            if ( '' === $normalized_label ) {
                return 0;
            }

            if ( $normalized_label === $normalized_query ) {
                return 100;
            }

            if ( str_starts_with( $normalized_label, $normalized_query ) ) {
                return 94;
            }

            if ( str_contains( $normalized_label, $normalized_query ) ) {
                return 88;
            }

            $distance = levenshtein( $normalized_query, $normalized_label );
            $length   = max( strlen( $normalized_query ), strlen( $normalized_label ) );

            if ( $length <= 0 ) {
                return 0;
            }

            if ( $distance <= 2 ) {
                return 82 - ( $distance * 6 );
            }

            similar_text( $normalized_query, $normalized_label, $percent );
            if ( $percent >= 72 ) {
                return intval( round( $percent ) );
            }

            $query_tokens = array_values( array_filter( explode( ' ', $normalized_query ) ) );
            if ( count( $query_tokens ) > 1 ) {
                $all_tokens_near = true;
                foreach ( $query_tokens as $token ) {
                    if ( ! str_contains( $normalized_label, $token ) ) {
                        $all_tokens_near = false;
                        break;
                    }
                }
                if ( $all_tokens_near ) {
                    return 74;
                }
            }

            return 0;
        };

        $posts_table = $wpdb->posts;
        $post_sql    = $wpdb->prepare(
            "SELECT ID, post_title, post_type
             FROM {$posts_table}
             WHERE post_status = 'publish'
               AND post_type IN ('review','post','page')
               AND post_title LIKE %s
             ORDER BY post_date DESC
             LIMIT 30",
            $seed_like
        );
        $post_rows   = $wpdb->get_results( $post_sql, ARRAY_A );

        if ( is_array( $post_rows ) ) {
            foreach ( $post_rows as $row ) {
                $post_id   = intval( $row['ID'] ?? 0 );
                $post_type = (string) ( $row['post_type'] ?? '' );
                $title     = trim( (string) ( $row['post_title'] ?? '' ) );
                $score     = $score_label( $title );

                if ( $post_id <= 0 || $score < 72 ) {
                    continue;
                }

                if ( 'review' === $post_type ) {
                    $kicker = __( 'Review Route', 'lunara-film' );
                    $score += 6;
                } elseif ( 'page' === $post_type ) {
                    $kicker = __( 'Page Route', 'lunara-film' );
                } else {
                    $kicker = __( 'Dispatch Route', 'lunara-film' );
                    $score += 2;
                }

                $push_match(
                    'post:' . $post_id,
                    array(
                        'kicker' => $kicker,
                        'title'  => $title,
                        'meta'   => __( 'Closest Lunara route', 'lunara-film' ),
                        'url'    => get_permalink( $post_id ),
                        'score'  => $score,
                    )
                );
            }
        }

        $table_name   = $wpdb->prefix . 'academy_awards';
        $table_like   = $wpdb->esc_like( $table_name );
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_like ) );

        if ( $table_exists === $table_name ) {
            $oscars_sql = $wpdb->prepare(
                "SELECT film, film_id, nominees, nominee_ids, category, canonical_category, ceremony, year, winner
                 FROM {$table_name}
                 WHERE film LIKE %s
                    OR nominees LIKE %s
                 ORDER BY winner DESC, ceremony DESC, id DESC
                 LIMIT 60",
                $seed_like,
                $seed_like
            );
            $rows = $wpdb->get_results( $oscars_sql, ARRAY_A );

            if ( is_array( $rows ) ) {
                $base_url = home_url( '/oscars/' );
                if ( class_exists( 'Academy_Awards_Table' ) ) {
                    $aat = Academy_Awards_Table::get_instance();
                    if ( $aat && method_exists( $aat, 'get_entity_base_url' ) ) {
                        $base_url = $aat->get_entity_base_url();
                    }
                }
                $base_url = trailingslashit( $base_url );

                foreach ( $rows as $row ) {
                    $film    = trim( (string) ( $row['film'] ?? '' ) );
                    $film_id = strtolower( trim( (string) ( $row['film_id'] ?? '' ) ) );
                    $score   = $score_label( $film );

                    if ( '' !== $film && preg_match( '/^tt\d+$/', $film_id ) && $score >= 72 ) {
                        if ( intval( $row['winner'] ?? 0 ) > 0 ) {
                            $score += 2;
                        }

                        $push_match(
                            'title:' . $film_id,
                            array(
                                'kicker' => __( 'Closest Ledger Title', 'lunara-film' ),
                                'title'  => $film,
                                'meta'   => sprintf(
                                    /* translators: 1: ceremony number, 2: year */
                                    __( '%1$s Ceremony / %2$s', 'lunara-film' ),
                                    intval( $row['ceremony'] ?? 0 ),
                                    trim( (string) ( $row['year'] ?? '' ) )
                                ),
                                'url'    => $base_url . 'title/' . rawurlencode( $film_id ) . '/',
                                'score'  => $score,
                            )
                        );
                    }
                }
            }
        }

        uasort(
            $matches,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return array_slice( array_values( $matches ), 0, $limit );
    }
}

/**
 * AJAX suggestions endpoint for front-end search boxes.
 */
if ( ! function_exists( 'lunara_ajax_search_suggestions' ) ) {
    function lunara_ajax_search_suggestions() {
        $query_text = isset( $_REQUEST['q'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) ) : '';
        $query_text = trim( $query_text );

        if ( '' === $query_text || strlen( $query_text ) < 2 ) {
            wp_send_json_success(
                array(
                    'items' => array(),
                )
            );
        }

        $items = array();
        $seen  = array();

        foreach ( lunara_get_post_search_suggestions( $query_text, 6 ) as $item ) {
            $url = isset( $item['url'] ) ? (string) $item['url'] : '';
            if ( '' === $url || isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;
            $score        = intval( $item['score'] ?? 0 );
            $kicker       = isset( $item['kicker'] ) ? (string) $item['kicker'] : '';
            if ( 'Review' === $kicker ) {
                $score += 8;
            } elseif ( 'Page' === $kicker ) {
                $score += 1;
            } else {
                $score += 4;
            }
            $items[]      = array(
                'kicker' => $kicker,
                'title'  => $item['title'] ?? '',
                'meta'   => $item['meta'] ?? '',
                'url'    => $url,
                'score'  => $score,
            );
        }

        foreach ( lunara_get_oscars_search_matches( $query_text, 4 ) as $item ) {
            $url = isset( $item['url'] ) ? (string) $item['url'] : '';
            if ( '' === $url || isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;
            $items[]      = array(
                'kicker' => $item['kicker'] ?? __( 'Oscar Match', 'lunara-film' ),
                'title'  => $item['title'] ?? '',
                'meta'   => $item['meta'] ?? '',
                'url'    => $url,
                'score'  => intval( $item['score'] ?? 0 ),
            );
        }

        usort(
            $items,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        if ( empty( $items ) ) {
            foreach ( lunara_get_search_recovery_routes( $query_text, 6 ) as $item ) {
                $items[] = array(
                    'kicker' => $item['kicker'] ?? __( 'Closest Route', 'lunara-film' ),
                    'title'  => $item['title'] ?? '',
                    'meta'   => $item['meta'] ?? '',
                    'url'    => $item['url'] ?? '',
                    'score'  => intval( $item['score'] ?? 0 ),
                );
            }
        }

        wp_send_json_success(
            array(
                'items' => array_slice( $items, 0, 8 ),
            )
        );
    }
}
add_action( 'wp_ajax_lunara_search_suggestions', 'lunara_ajax_search_suggestions' );
add_action( 'wp_ajax_nopriv_lunara_search_suggestions', 'lunara_ajax_search_suggestions' );

/**
 * Lightweight live-search suggestions for front-end search inputs.
 */
if ( ! function_exists( 'lunara_render_live_search_script' ) ) {
    function lunara_render_live_search_script() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <script id="lunara-live-search-script">
        document.addEventListener('DOMContentLoaded', function () {
            const forms = Array.from(document.querySelectorAll('form[role="search"], .search-form')).filter(function (form) {
                return form.querySelector('input[name="s"], input[name="q"]');
            });
            if (!forms.length) return;

            const endpoint = <?php echo wp_json_encode( admin_url( 'admin-ajax.php?action=lunara_search_suggestions' ) ); ?>;

            forms.forEach(function (form) {
                const input = form.querySelector('input[name="s"], input[name="q"]');
                if (!input || input.dataset.lunaraSuggestionsReady === '1') return;
                input.dataset.lunaraSuggestionsReady = '1';

                form.classList.add('lunara-live-search-form');
                let panel = form.querySelector('.lunara-live-search-panel');
                if (!panel) {
                    panel = document.createElement('div');
                    panel.className = 'lunara-live-search-panel';
                    panel.hidden = true;
                    form.appendChild(panel);
                }

                let controller = null;
                let activeIndex = -1;
                let currentItems = [];

                const closePanel = function () {
                    panel.hidden = true;
                    panel.innerHTML = '';
                    activeIndex = -1;
                    currentItems = [];
                };

                const renderPanel = function (items) {
                    currentItems = items.slice();
                    activeIndex = -1;

                    if (!items.length) {
                        closePanel();
                        return;
                    }

                    const allResultsUrl = function () {
                        const value = input.value.trim();
                        const queryName = input.name || 's';
                        try {
                            const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
                            url.searchParams.set(queryName, value);
                            return url.toString();
                        } catch (error) {
                            const action = form.action || window.location.href;
                            const separator = action.indexOf('?') === -1 ? '?' : '&';
                            return action + separator + encodeURIComponent(queryName) + '=' + encodeURIComponent(value);
                        }
                    };

                    panel.innerHTML = items.map(function (item, index) {
                        const meta = item.meta ? '<span class="lunara-live-search-meta">' + item.meta + '</span>' : '';
                        return '<a class="lunara-live-search-item" href="' + item.url + '" data-index="' + index + '">' +
                            '<span class="lunara-live-search-kicker">' + item.kicker + '</span>' +
                            '<span class="lunara-live-search-title">' + item.title + '</span>' +
                            meta +
                        '</a>';
                    }).join('') +
                    '<a class="lunara-live-search-all-results" href="' + allResultsUrl() + '">' +
                        '<span class="lunara-live-search-kicker"><?php echo esc_js( __( 'Search Desk', 'lunara-film' ) ); ?></span>' +
                        '<span class="lunara-live-search-title"><?php echo esc_js( __( 'See all results on the record', 'lunara-film' ) ); ?></span>' +
                    '</a>';
                    panel.hidden = false;
                };

                const updateActiveItem = function () {
                    const links = panel.querySelectorAll('.lunara-live-search-item');
                    links.forEach(function (link, index) {
                        link.classList.toggle('is-active', index === activeIndex);
                    });
                };

                const fetchSuggestions = function (value) {
                    if (controller) controller.abort();
                    controller = new AbortController();
                    const url = endpoint + '&q=' + encodeURIComponent(value);

                    fetch(url, {
                        credentials: 'same-origin',
                        signal: controller.signal
                    })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (!payload || payload.success !== true || !payload.data || !Array.isArray(payload.data.items)) {
                            closePanel();
                            return;
                        }
                        renderPanel(payload.data.items);
                    })
                    .catch(function (error) {
                        if (error && error.name === 'AbortError') return;
                        closePanel();
                    });
                };

                let debounceTimer = null;
                input.addEventListener('input', function () {
                    const value = input.value.trim();
                    window.clearTimeout(debounceTimer);
                    if (value.length < 2) {
                        closePanel();
                        return;
                    }
                    debounceTimer = window.setTimeout(function () {
                        fetchSuggestions(value);
                    }, 140);
                });

                input.addEventListener('keydown', function (event) {
                    if (panel.hidden || !currentItems.length) return;

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, currentItems.length - 1);
                        updateActiveItem();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                        updateActiveItem();
                    } else if (event.key === 'Enter' && activeIndex >= 0) {
                        const link = panel.querySelector('.lunara-live-search-item[data-index="' + activeIndex + '"]');
                        if (link) {
                            event.preventDefault();
                            window.location.href = link.href;
                        }
                    } else if (event.key === 'Escape') {
                        closePanel();
                    }
                });

                form.addEventListener('focusout', function () {
                    window.setTimeout(function () {
                        if (!form.contains(document.activeElement)) {
                            closePanel();
                        }
                    }, 120);
                });

                document.addEventListener('click', function (event) {
                    if (!form.contains(event.target)) {
                        closePanel();
                    }
                });
            });
        });
        </script>
        <?php
    }
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Pull direct Oscars entity matches for the front-end search desk.
 */
if ( ! function_exists( 'lunara_get_oscars_search_matches' ) ) {
    function lunara_get_oscars_search_matches( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $table_name = $wpdb->prefix . 'academy_awards';
        $table_like = $wpdb->esc_like( $table_name );
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_like ) );

        if ( $table_exists !== $table_name ) {
            return array();
        }

        $search_term = '%' . $wpdb->esc_like( $query_text ) . '%';
        $sql         = $wpdb->prepare(
            "SELECT film, film_id, name, nominees, nominee_ids, canonical_category, category, ceremony, year, winner
             FROM {$table_name}
             WHERE film LIKE %s
                OR name LIKE %s
                OR nominees LIKE %s
                OR canonical_category LIKE %s
                OR category LIKE %s
             ORDER BY winner DESC, ceremony DESC, id DESC
             LIMIT 80",
            $search_term,
            $search_term,
            $search_term,
            $search_term,
            $search_term
        );
        $rows        = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return array();
        }

        $base_url = home_url( '/oscars/' );
        if ( class_exists( 'Academy_Awards_Table' ) ) {
            $aat = Academy_Awards_Table::get_instance();
            if ( $aat && method_exists( $aat, 'get_entity_base_url' ) ) {
                $base_url = $aat->get_entity_base_url();
            }
        }
        $base_url = trailingslashit( $base_url );

        $normalized_query = strtolower( $query_text );
        $matches          = array();

        $push_match = static function ( $key, $match ) use ( &$matches ) {
            if ( ! isset( $match['score'] ) ) {
                return;
            }

            if ( ! isset( $matches[ $key ] ) || intval( $match['score'] ) > intval( $matches[ $key ]['score'] ) ) {
                $matches[ $key ] = $match;
            }
        };

        $map_pipe_values = static function ( $values, $ids ) {
            $value_parts = array_values( array_filter( array_map( 'trim', explode( '|', (string) $values ) ), 'strlen' ) );
            $id_parts    = array_values( array_filter( array_map( 'trim', explode( '|', (string) $ids ) ), 'strlen' ) );

            if ( empty( $value_parts ) || count( $value_parts ) !== count( $id_parts ) ) {
                return array();
            }

            return array_combine( $id_parts, $value_parts );
        };

        foreach ( $rows as $row ) {
            $film    = trim( (string) ( $row['film'] ?? '' ) );
            $film_id = strtolower( trim( (string) ( $row['film_id'] ?? '' ) ) );

            if ( '' !== $film && preg_match( '/^tt\d+$/', $film_id ) ) {
                $film_score = function_exists( 'lunara_search_text_match_score' )
                    ? lunara_search_text_match_score( $film, $query_text )
                    : 0;
                if ( $film_score > 0 && intval( $row['winner'] ?? 0 ) > 0 ) {
                    $film_score += 4;
                }
            } else {
                $film_score = 0;
            }

            if ( $film_score > 0 ) {
                $push_match(
                    'title:' . $film_id,
                    array(
                        'kicker' => __( 'Oscar Title Match', 'lunara-film' ),
                        'title'  => $film,
                        'meta'   => sprintf(
                            /* translators: 1: ceremony number, 2: year */
                            __( '%1$s Ceremony / %2$s', 'lunara-film' ),
                            intval( $row['ceremony'] ?? 0 ),
                            trim( (string) ( $row['year'] ?? '' ) )
                        ),
                        'url'    => $base_url . 'title/' . rawurlencode( $film_id ) . '/',
                        'score'  => $film_score,
                    )
                );
            }

            $nominee_map = $map_pipe_values( $row['nominees'] ?? '', $row['nominee_ids'] ?? '' );
            foreach ( $nominee_map as $entity_id => $entity_label ) {
                $entity_id    = strtolower( trim( (string) $entity_id ) );
                $entity_label = trim( (string) $entity_label );
                $entity_score = function_exists( 'lunara_search_text_match_score' )
                    ? lunara_search_text_match_score( $entity_label, $query_text )
                    : 0;
                if ( $entity_score <= 0 ) {
                    continue;
                }

                if ( preg_match( '/^nm\d+$/', $entity_id ) ) {
                    $entity_type   = 'name';
                    $entity_kicker = __( 'Oscar Person Match', 'lunara-film' );
                } elseif ( preg_match( '/^co\d+$/', $entity_id ) ) {
                    $entity_type   = 'company';
                    $entity_kicker = __( 'Oscar Company Match', 'lunara-film' );
                } else {
                    continue;
                }

                $push_match(
                    $entity_type . ':' . $entity_id,
                    array(
                        'kicker' => $entity_kicker,
                        'title'  => $entity_label,
                        'meta'   => trim( (string) ( $row['category'] ?? $row['canonical_category'] ?? '' ) ),
                        'url'    => $base_url . $entity_type . '/' . rawurlencode( $entity_id ) . '/',
                        'score'  => $entity_score + ( intval( $row['winner'] ?? 0 ) > 0 ? 2 : 0 ),
                    )
                );
            }
        }

        uasort(
            $matches,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return array_slice( array_values( $matches ), 0, $limit );
    }
}

/**
 * Score a text label against a search query for title-first suggestion ranking.
 */
if ( ! function_exists( 'lunara_search_text_match_score' ) ) {
    function lunara_search_text_match_score( $label, $query_text ) {
        $label      = strtolower( trim( (string) $label ) );
        $query_text = strtolower( trim( (string) $query_text ) );

        if ( '' === $label || '' === $query_text ) {
            return 0;
        }

        if ( $label === $query_text ) {
            return 120;
        }

        if ( str_starts_with( $label, $query_text ) ) {
            return 102;
        }

        $query_length = function_exists( 'mb_strlen' ) ? mb_strlen( $query_text ) : strlen( $query_text );
        $label_words  = preg_split( '/\s+/', $label );
        $word_count   = is_array( $label_words ) ? count( array_filter( $label_words ) ) : 0;

        $tokens = preg_split( '/\s+/', $query_text );
        $tokens = is_array( $tokens ) ? array_values( array_filter( $tokens ) ) : array();

        if ( preg_match( '/(^|[^a-z0-9])' . preg_quote( $query_text, '/' ) . '([^a-z0-9]|$)/i', $label ) ) {
            if ( count( $tokens ) > 1 || $word_count <= 5 ) {
                return 88;
            }

            return 0;
        }

        if ( count( $tokens ) > 1 ) {
            $all_tokens_present = true;
            foreach ( $tokens as $token ) {
                if ( false === strpos( $label, $token ) ) {
                    $all_tokens_present = false;
                    break;
                }
            }

            if ( $all_tokens_present ) {
                return 82;
            }
        }

        if ( $query_length < 3 ) {
            return 0;
        }

        if ( false !== strpos( $label, $query_text ) && $word_count <= 5 ) {
            return 70;
        }

        return 0;
    }
}


/**
 * Poster carousel controls.
 */
function lunara_output_carousel_controls_js() {
    if ( ! is_front_page() && ! is_page( 'oscars' ) && ! is_page_template( 'page-oscars.php' ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-lunara-carousel]').forEach(function(section) {
            const track = section.querySelector('[data-lunara-carousel-track]');
            const prev = section.querySelector('[data-lunara-carousel-prev]');
            const next = section.querySelector('[data-lunara-carousel-next]');
            const dots = Array.from(section.querySelectorAll('[data-lunara-carousel-dot]'));
            if (!track) return;
            function amount() {
                const card = track.children[0];
                const styles = window.getComputedStyle(track);
                const gap = parseInt(styles.columnGap || styles.gap || 24, 10);
                return card ? card.offsetWidth + gap : 360;
            }
            function getIndexFromOffset(scrollLeft) {
                const step = amount();
                if (!step) {
                    return 0;
                }
                const total = track.children.length;
                return Math.min(total - 1, Math.max(0, Math.round(scrollLeft / step)));
            }
            function syncDots() {
                if (!dots.length) {
                    return;
                }
                const activeIndex = getIndexFromOffset(track.scrollLeft);
                dots.forEach(function(dot, index) {
                    const active = index === activeIndex;
                    dot.classList.toggle('active', active);
                    dot.setAttribute('aria-selected', active ? 'true' : 'false');
                });
            }
            function scrollToIndex(index) {
                const target = Number.isInteger(index) ? index : 0;
                const cards = track.children;
                if (!cards.length) {
                    return;
                }
                const clampedIndex = Math.min(cards.length - 1, Math.max(0, target));
                const card = cards[clampedIndex];
                if (!card || !card.scrollIntoView) {
                    return;
                }
                const trackRect = track.getBoundingClientRect();
                const cardRect = card.getBoundingClientRect();
                const targetLeft = track.scrollLeft + cardRect.left - trackRect.left;
                track.scrollTo({
                    left: Math.max(0, targetLeft),
                    behavior: reduceMotion ? 'auto' : 'smooth'
                });
            }
            function step(direction) {
                const distance = amount() * direction;
                const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                if (direction > 0 && track.scrollLeft + distance >= maxScroll - 6) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }
                if (direction < 0 && track.scrollLeft <= 6) {
                    track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                    return;
                }
                track.scrollBy({ left: distance, behavior: 'smooth' });
            }
            if (prev) {
                prev.addEventListener('click', function () {
                    step(-1);
                });
            }
            if (next) {
                next.addEventListener('click', function () {
                    step(1);
                });
            }
            if (dots.length) {
                dots.forEach(function(dot, index) {
                    dot.addEventListener('click', function() {
                        scrollToIndex(index);
                    });
                });
            }

            section.addEventListener('keydown', function(event) {
                if ('ArrowLeft' === event.key) {
                    event.preventDefault();
                    step(-1);
                } else if ('ArrowRight' === event.key) {
                    event.preventDefault();
                    step(1);
                }
            });

            let syncRaf = null;
            track.addEventListener('scroll', function () {
                if (!dots.length) {
                    return;
                }
                if (syncRaf) {
                    window.cancelAnimationFrame(syncRaf);
                }
                syncRaf = window.requestAnimationFrame(function () {
                    syncDots();
                    syncRaf = null;
                });
            }, { passive: true });

            const autoplay = parseInt(section.getAttribute('data-lunara-carousel-autoplay') || '0', 10);
            if (!reduceMotion && autoplay > 0 && track.children.length > 1 && window.innerWidth > 900) {
                let timer = null;
                const stop = function () {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                };
                const start = function () {
                    stop();
                    timer = window.setInterval(function () {
                        step(1);
                    }, autoplay);
                };
                section.addEventListener('mouseenter', stop);
                section.addEventListener('mouseleave', start);
                section.addEventListener('focusin', stop);
                section.addEventListener('focusout', start);
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        stop();
                    } else {
                        start();
                    }
                });
                start();
            }
            syncDots();
        });
    });
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Wave 2: Image fade-in on load.
 */
function lunara_output_image_fadein_js() {
    ?>
    <script>
    (function(){
        function markLoaded(img){img.classList.add('lunara-img-loaded');}
        function sanitizeSrcset(srcset){
            srcset=(srcset||'').trim();
            if(!srcset){return '';}
            return srcset.split(/,\s*(?=(?:https?:)?\/\/|\/)/).map(function(candidate){
                candidate=(candidate||'').trim();
                if(!candidate){return '';}
                var decoded=candidate.replace(/&amp;/g,'&');
                if(/(?:[?&;](?:resize|fit)=0(?:%2c|,)nan)/i.test(decoded)||/(?:[?&;](?:w|h)=0(?:&|$))/i.test(decoded)){return '';}
                return (/\s+\d+w$/.test(candidate)||/\s+\d+(?:\.\d+)?x$/.test(candidate))?candidate:'';
            }).filter(Boolean).join(', ');
        }
        function installSrcsetGuard(){
            if(window.lunaraSrcsetGuardInstalled||!window.Element)return;
            window.lunaraSrcsetGuardInstalled=true;
            var nativeSetAttribute=Element.prototype.setAttribute;
            Element.prototype.setAttribute=function(name,value){
                if(typeof name==='string'&&name.toLowerCase()==='srcset'){
                    value=sanitizeSrcset(String(value||''));
                }
                return nativeSetAttribute.call(this,name,value);
            };
            [window.HTMLImageElement,window.HTMLSourceElement].forEach(function(Constructor){
                if(!Constructor||!Constructor.prototype)return;
                var descriptor=Object.getOwnPropertyDescriptor(Constructor.prototype,'srcset');
                if(!descriptor||!descriptor.set||!descriptor.get)return;
                Object.defineProperty(Constructor.prototype,'srcset',{
                    configurable:true,
                    enumerable:descriptor.enumerable,
                    get:function(){return descriptor.get.call(this);},
                    set:function(value){descriptor.set.call(this,sanitizeSrcset(String(value||'')));}
                });
            });
        }
        function sanitizeImageSrcset(node){
            if(!node||!node.getAttribute||!node.setAttribute){return;}
            var currentSrcset=node.getAttribute('srcset')||'';
            if(!currentSrcset){return;}
            var sanitizedSrcset=sanitizeSrcset(currentSrcset);
            if(sanitizedSrcset&&sanitizedSrcset!==currentSrcset){
                node.setAttribute('srcset',sanitizedSrcset);
            }else if(!sanitizedSrcset){
                node.removeAttribute('srcset');
                node.removeAttribute('sizes');
            }
        }
        function sanitizeDocumentSrcsets(root){
            root=root||document;
            if(root.matches&&root.matches('img[srcset], source[srcset]'))sanitizeImageSrcset(root);
            root.querySelectorAll&&root.querySelectorAll('img[srcset], source[srcset]').forEach(sanitizeImageSrcset);
        }
        installSrcsetGuard();
        function hydrateLazySource(img){
            sanitizeImageSrcset(img);
            var dataSrc=img.getAttribute('data-src')||img.getAttribute('data-lazy-src')||'';
            var dataSrcset=img.getAttribute('data-srcset')||img.getAttribute('data-lazy-srcset')||'';
            var currentSrc=img.getAttribute('src')||'';
            if(dataSrcset&&!img.getAttribute('srcset')){
                var sanitizedSrcset=sanitizeSrcset(dataSrcset);
                if(sanitizedSrcset){
                    img.setAttribute('srcset',sanitizedSrcset);
                }else{
                    img.removeAttribute('data-srcset');
                    img.removeAttribute('data-lazy-srcset');
                }
            }
            if(dataSrc&&(!currentSrc||currentSrc.indexOf('data:image/gif')===0)){
                img.setAttribute('src',dataSrc);
            }
        }
        function processImg(img){
            hydrateLazySource(img);
            if(img.complete&&img.naturalWidth>1){markLoaded(img);return;}
            img.addEventListener('load',function(){markLoaded(img);});
            img.addEventListener('error',function(){markLoaded(img);});
            window.setTimeout(function(){
                if(!img.classList.contains('lunara-img-loaded')){
                    markLoaded(img);
                }
            },1800);
        }
        var sels='.lunara-review-grid-poster,.lunara-review-feature-image,.lunara-poster-card-image,.lunara-journal-home-card-image,.lunara-dispatch-archive-thumb,.lunara-dispatch-lead-image,.lunara-oscar-pick-card-image,.lunara-oscar-fact-card-poster-image,.lunara-home-pulse-poster,.aat-filmography-poster,.aat-entity-poster';
        sanitizeDocumentSrcsets(document);
        document.querySelectorAll(sels).forEach(processImg);
        if(window.MutationObserver){
            new MutationObserver(function(mutations){
                mutations.forEach(function(m){
                    if(m.type==='attributes'){
                        sanitizeImageSrcset(m.target);
                        return;
                    }
                    m.addedNodes.forEach(function(n){
                        if(n.nodeType===1){
                            sanitizeDocumentSrcsets(n);
                            if(n.matches&&n.matches(sels))processImg(n);
                            n.querySelectorAll&&n.querySelectorAll(sels).forEach(processImg);
                        }
                    });
                });
            }).observe(document.body,{attributes:true,attributeFilter:['srcset','data-srcset','data-lazy-srcset'],childList:true,subtree:true});
        }
    })();
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Wave 3: Scroll-triggered reveals.
 */
function lunara_output_scroll_reveal_js() {
    ?>
    <script>
    (function(){
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        // Only run scroll reveals on the front page — skip portal, plugin, single review, and other pages
        var isFrontPage=document.body.classList.contains('home')||document.querySelector('.lunara-front-page');
        var isPluginPage=document.querySelector('.aat-hub-page,.aat-entity-page');
        var revealSels=[];
        var staggerSels=[];
        if(isFrontPage){
            revealSels=[
                '.lunara-front-page>.lunara-home-section','.lunara-review-grid-card','.lunara-review-feature-card',
                '.lunara-poster-card','.lunara-ledger-card','.lunara-dispatch-archive-card'
            ];
            staggerSels=[
                '.lunara-review-grid','.lunara-review-related-grid'
            ];
        }
        // Entity pages get targeted reveals for stats/timeline only
        if(isPluginPage){
            revealSels=['.aat-entity-status-banner','.aat-stat','.aat-timeline-card'];
            staggerSels=['.aat-stats-bar','.aat-timeline-list'];
        }
        if(!revealSels.length)return;
        revealSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal');});
        });
        staggerSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal-stagger');});
        });
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        },{threshold:0.08,rootMargin:'0px 0px -40px 0px'});
        document.querySelectorAll('.lunara-reveal').forEach(function(el){obs.observe(el);});
    })();
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

// Sticky sidebar deferred to standalone theme (Tier 4).
// Blocksy's scroll container architecture defeats both CSS sticky and JS fixed positioning.
// The sidebar renders correctly in place; it just doesn't follow the reader yet.

/**
 * Wave 5: Oscar stats count-up animation.
 */
function lunara_output_stats_countup_js() {
    if ( ! is_singular() ) {
        return;
    }
    ?>
    <script>
    (function(){
        var stats=document.querySelectorAll('.aat-stat-number');
        if(!stats.length||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(!entry.isIntersecting)return;
                obs.unobserve(entry.target);
                var el=entry.target,text=el.textContent.trim();
                var match=text.match(/^([\d,]+)(.*)/);
                if(!match)return;
                var target=parseInt(match[1].replace(/,/g,''),10);
                var suffix=match[2];
                if(isNaN(target)||target===0)return;
                var duration=Math.min(1600,Math.max(600,target*8));
                var start=performance.now();
                function tick(now){
                    var t=Math.min(1,(now-start)/duration);
                    var ease=1-Math.pow(1-t,3);
                    var current=Math.round(target*ease);
                    el.textContent=current.toLocaleString()+suffix;
                    if(t<1)requestAnimationFrame(tick);
                }
                el.textContent='0'+suffix;
                requestAnimationFrame(tick);
            });
        },{threshold:0.3});
        stats.forEach(function(el){obs.observe(el);});
    })();
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Atmosphere V1 — The Cut. Poster match-cut for cross-document View
 * Transitions: clicking a poster card names its image `lunara-screen` at the
 * moment of navigation so the browser morphs it into the review hero (named
 * in pure CSS). Browsers without startViewTransition, modified clicks,
 * external/new-tab links, and reduced-motion users all fall through to the
 * existing tuned root fade — the fallback IS the design.
 */
function lunara_output_match_cut_js() {
    if ( is_admin() || is_customize_preview() ) {
        return;
    }
    ?>
    <script>
    (function(){
        if(!('startViewTransition' in document))return;
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var CARDS='.lunara-review-grid-card,.lunara-poster-card,.lunara-oscar-pick-card,.lunara-journal-home-card,.lunara-cinematic-hero-link';
        document.addEventListener('click',function(e){
            if(e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey)return;
            var card=e.target.closest?e.target.closest(CARDS):null;if(!card)return;
            var a=card.matches('a')?card:card.querySelector('a[href]');
            if(!a||!a.href)return;
            if(a.target&&a.target!=='_self')return;
            if(a.origin!==location.origin)return;
            var img=card.querySelector('img');if(!img)return;
            var r=img.getBoundingClientRect();
            if(r.width<1||r.bottom<0||r.top>window.innerHeight)return;
            /* Demote the current page's own hero name first — two elements
               sharing lunara-screen would abort the whole transition. */
            var hero=document.querySelector('.lunara-review-single-cinematic-hero .lunara-review-visual--hero');
            if(hero)hero.style.viewTransitionName='none';
            img.style.viewTransitionName='lunara-screen';
            img.setAttribute('data-lunara-vt','1');
        },true);
        window.addEventListener('pageshow',function(e){
            if(!e.persisted)return;
            document.querySelectorAll('[data-lunara-vt]').forEach(function(el){el.style.viewTransitionName='';el.removeAttribute('data-lunara-vt');});
            var hero=document.querySelector('.lunara-review-single-cinematic-hero .lunara-review-visual--hero');
            if(hero)hero.style.viewTransitionName='';
        });
    })();
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Build review-specific SEO/social metadata from the same reader hook used by cards.
 */
if ( ! function_exists( 'lunara_get_review_seo_summary' ) ) {
    function lunara_get_review_seo_summary( $post_id, $words = 32 ) {
        $post_id = intval( $post_id );
        $words   = max( 18, intval( $words ) );

        if ( $post_id <= 0 ) {
            return '';
        }

        $summary = '';

        if ( function_exists( 'lunara_get_review_card_pull_quote' ) ) {
            $summary = lunara_get_review_card_pull_quote( $post_id, $words );
        }

        if ( '' === trim( $summary ) ) {
            $summary = has_excerpt( $post_id )
                ? get_the_excerpt( $post_id )
                : get_post_field( 'post_content', $post_id );
        }

        $summary = strip_shortcodes( (string) $summary );
        $summary = html_entity_decode( $summary, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
        $summary = wp_strip_all_tags( $summary );
        $summary = preg_replace( '/\s+/', ' ', $summary );
        $summary = trim( (string) $summary );

        if ( '' === $summary ) {
            return '';
        }

        return wp_html_excerpt( $summary, 190, '...' );
    }
}

if ( ! function_exists( 'lunara_get_review_social_image_url' ) ) {
    function lunara_get_review_social_image_url( $post_id ) {
        $post_id = intval( $post_id );

        if ( $post_id <= 0 ) {
            return '';
        }

        $image_urls = array(
            get_post_meta( $post_id, '_lunara_review_hero_banner', true ),
            get_post_meta( $post_id, '_lunara_review_card_image', true ),
        );

        foreach ( $image_urls as $image_url ) {
            $image_url = trim( (string) $image_url );

            if ( '' === $image_url ) {
                continue;
            }

            $attachment_id = attachment_url_to_postid( $image_url );
            if ( $attachment_id > 0 ) {
                $large_url = wp_get_attachment_image_url( $attachment_id, 'large' );
                if ( $large_url ) {
                    return (string) $large_url;
                }
            }

            return esc_url_raw( $image_url );
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id  = get_post_thumbnail_id( $post_id );
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';

            if ( $thumbnail_url ) {
                return (string) $thumbnail_url;
            }

            return (string) get_the_post_thumbnail_url( $post_id, 'large' );
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_filter_review_jetpack_seo_meta_tags' ) ) {
    function lunara_filter_review_jetpack_seo_meta_tags( $meta ) {
        if ( ! is_singular( 'review' ) || ! is_array( $meta ) ) {
            return $meta;
        }

        $summary = lunara_get_review_seo_summary( get_queried_object_id() );

        if ( '' !== $summary ) {
            $meta['description'] = $summary;
        }

        return $meta;
    }
}
add_filter( 'jetpack_seo_meta_tags', 'lunara_filter_review_jetpack_seo_meta_tags', 20 );

if ( ! function_exists( 'lunara_get_brand_social_image_url' ) ) {
    function lunara_get_brand_social_image_url() {
        $candidate_ids = array(
            (int) get_option( 'site_icon' ),
            (int) get_option( 'lunara_home_identity_logo_id' ),
            (int) get_theme_mod( 'custom_logo' ),
        );

        foreach ( $candidate_ids as $attachment_id ) {
            if ( $attachment_id <= 0 ) {
                continue;
            }

            $image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
            if ( $image_url ) {
                return (string) $image_url;
            }
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_should_use_brand_social_image_fallback' ) ) {
    function lunara_should_use_brand_social_image_fallback() {
        if ( is_singular( 'review' ) ) {
            return false;
        }

        // Journal entries supply their own hero (dispatch image) — don't let the
        // brand icon override it as the social image.
        if ( is_singular( 'journal' ) && function_exists( 'lunara_get_journal_social_image_url' )
            && '' !== lunara_get_journal_social_image_url( get_queried_object_id() ) ) {
            return false;
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id > 0 && has_post_thumbnail( $post_id ) ) {
                return false;
            }
        }

        return true;
    }
}

if ( ! function_exists( 'lunara_filter_brand_jetpack_seo_meta_tags' ) ) {
    function lunara_filter_brand_jetpack_seo_meta_tags( $meta ) {
        if ( ! is_array( $meta ) || ! lunara_should_use_brand_social_image_fallback() ) {
            return $meta;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return $meta;
        }

        if ( empty( $meta['twitter:image'] ) ) {
            $meta['twitter:image'] = $image_url;
        }

        if ( empty( $meta['twitter:card'] ) ) {
            $meta['twitter:card'] = 'summary_large_image';
        }

        return $meta;
    }
}
add_filter( 'jetpack_seo_meta_tags', 'lunara_filter_brand_jetpack_seo_meta_tags', 30 );

if ( ! function_exists( 'lunara_filter_brand_jetpack_open_graph_tags' ) ) {
    function lunara_filter_brand_jetpack_open_graph_tags( $tags ) {
        if ( ! is_array( $tags ) || ! lunara_should_use_brand_social_image_fallback() ) {
            return $tags;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return $tags;
        }

        if ( empty( $tags['og:image'] ) ) {
            $tags['og:image'] = $image_url;
        }

        if ( empty( $tags['og:image:alt'] ) ) {
            $tags['og:image:alt'] = __( 'Lunara Film icon', 'lunara-film' );
        }

        return $tags;
    }
}
add_filter( 'jetpack_open_graph_tags', 'lunara_filter_brand_jetpack_open_graph_tags', 30 );

if ( ! function_exists( 'lunara_output_brand_social_image_fallback_meta' ) ) {
    function lunara_output_brand_social_image_fallback_meta() {
        if ( ! lunara_should_use_brand_social_image_fallback() ) {
            return;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return;
        }

        echo "\n" . '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
        echo '<meta property="og:image:alt" content="' . esc_attr__( 'Lunara Film icon', 'lunara-film' ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    }
}
add_action( 'wp_head', 'lunara_output_brand_social_image_fallback_meta', 99 );

if ( ! function_exists( 'lunara_filter_review_jetpack_open_graph_tags' ) ) {
    function lunara_filter_review_jetpack_open_graph_tags( $tags ) {
        if ( ! is_singular( 'review' ) || ! is_array( $tags ) ) {
            return $tags;
        }

        $post_id     = get_queried_object_id();
        $title       = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $description = lunara_get_review_seo_summary( $post_id );
        $image_url   = lunara_get_review_social_image_url( $post_id );

        if ( '' !== $title ) {
            $tags['og:title'] = $title;
        }

        if ( '' !== $description ) {
            $tags['og:description'] = $description;
        }

        if ( '' !== $image_url ) {
            $tags['og:image']     = $image_url;
            $tags['og:image:alt'] = '' !== $title ? sprintf( __( '%s artwork', 'lunara-film' ), $title ) : __( 'Review artwork', 'lunara-film' );
            unset( $tags['og:image:width'], $tags['og:image:height'] );
        }

        return $tags;
    }
}
add_filter( 'jetpack_open_graph_tags', 'lunara_filter_review_jetpack_open_graph_tags', 20 );

/**
 * Journal social cards: give shared journal links the entry's own hero image
 * and an opening-line hook, instead of falling back to the brand icon. Journal
 * heroes live in _lunara_dispatch_image_url (a meta URL, not a featured image),
 * so without this Jetpack has nothing post-specific to work with.
 */
if ( ! function_exists( 'lunara_get_journal_seo_summary' ) ) {
    function lunara_get_journal_seo_summary( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $summary = has_excerpt( $post_id )
            ? get_the_excerpt( $post_id )
            : get_post_field( 'post_content', $post_id );

        $summary = strip_shortcodes( (string) $summary );
        $summary = html_entity_decode( $summary, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
        $summary = wp_strip_all_tags( $summary );
        $summary = preg_replace( '/\s+/', ' ', $summary );
        $summary = trim( (string) $summary );

        return '' === $summary ? '' : wp_html_excerpt( $summary, 190, '...' );
    }
}

if ( ! function_exists( 'lunara_get_journal_social_image_url' ) ) {
    function lunara_get_journal_social_image_url( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $url = trim( (string) get_post_meta( $post_id, '_lunara_dispatch_image_url', true ) );

        if ( '' === $url && has_post_thumbnail( $post_id ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            $url      = $thumb_id ? (string) wp_get_attachment_image_url( $thumb_id, 'large' ) : (string) get_the_post_thumbnail_url( $post_id, 'large' );
        }

        if ( '' === $url ) {
            return '';
        }

        // Uniform, light 16:9 card via Site Accelerator (no-op for already-sized URLs).
        if ( function_exists( 'lunara_rightsize_backdrop_url' ) ) {
            $url = lunara_rightsize_backdrop_url( $url );
        }

        return esc_url_raw( $url );
    }
}

if ( ! function_exists( 'lunara_filter_journal_jetpack_open_graph_tags' ) ) {
    function lunara_filter_journal_jetpack_open_graph_tags( $tags ) {
        if ( ! is_singular( 'journal' ) || ! is_array( $tags ) ) {
            return $tags;
        }

        $post_id     = get_queried_object_id();
        $title       = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $description = lunara_get_journal_seo_summary( $post_id );
        $image_url   = lunara_get_journal_social_image_url( $post_id );

        if ( '' !== $title ) {
            $tags['og:title'] = $title;
        }

        if ( '' !== $description ) {
            $tags['og:description'] = $description;
        }

        if ( '' !== $image_url ) {
            $tags['og:image']     = $image_url;
            $tags['og:image:alt'] = '' !== $title ? sprintf( __( '%s — Lunara Film Journal', 'lunara-film' ), $title ) : __( 'Lunara Film Journal', 'lunara-film' );
            unset( $tags['og:image:width'], $tags['og:image:height'] );
        }

        return $tags;
    }
}
add_filter( 'jetpack_open_graph_tags', 'lunara_filter_journal_jetpack_open_graph_tags', 20 );

if ( ! function_exists( 'lunara_filter_journal_jetpack_seo_meta_tags' ) ) {
    function lunara_filter_journal_jetpack_seo_meta_tags( $meta ) {
        if ( ! is_singular( 'journal' ) || ! is_array( $meta ) ) {
            return $meta;
        }

        $summary = lunara_get_journal_seo_summary( get_queried_object_id() );
        if ( '' !== $summary ) {
            $meta['description'] = $summary;
        }

        return $meta;
    }
}
add_filter( 'jetpack_seo_meta_tags', 'lunara_filter_journal_jetpack_seo_meta_tags', 20 );

if ( ! function_exists( 'lunara_output_review_seo_meta' ) ) {
    function lunara_output_review_seo_meta() {
        if ( ! is_singular( 'review' ) ) {
            return;
        }

        $post_id = get_queried_object_id();

        if ( $post_id <= 0 ) {
            return;
        }

        $title       = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $description = lunara_get_review_seo_summary( $post_id );
        $url         = get_permalink( $post_id );
        $image_url   = lunara_get_review_social_image_url( $post_id );
        $site_name   = get_bloginfo( 'name' );
        $author_name = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
        $year        = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $score       = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
        $movie_name  = preg_replace( '/\s*\(\d{4}\)\s*$/', '', $title );
        $jetpack_meta_active = function_exists( 'jetpack_og_tags' ) || class_exists( 'Jetpack_SEO' );

        if ( '' === trim( (string) $movie_name ) ) {
            $movie_name = $title;
        }

        if ( '' === $description ) {
            $description = sprintf( __( 'A Lunara Film review of %s.', 'lunara-film' ), $title );
        }

        if ( ! $jetpack_meta_active ) {
            echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";

            if ( '' !== $image_url ) {
                echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
            }

            echo '<meta name="twitter:card" content="' . esc_attr( '' !== $image_url ? 'summary_large_image' : 'summary' ) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

            if ( '' !== $image_url ) {
                echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
            }
        }

        $schema = array(
            '@context'         => 'https://schema.org',
            '@type'            => 'Review',
            'headline'         => $title,
            'name'             => sprintf( __( '%s review', 'lunara-film' ), $title ),
            'description'      => $description,
            'url'              => $url,
            'mainEntityOfPage' => $url,
            'datePublished'    => get_the_date( DATE_W3C, $post_id ),
            'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
            'inLanguage'       => get_bloginfo( 'language' ),
            'author'           => array(
                '@type' => 'Person',
                'name'  => '' !== $author_name ? $author_name : __( 'Lunara Film', 'lunara-film' ),
            ),
            'publisher'        => array(
                '@type' => 'Organization',
                'name'  => $site_name,
                'url'   => home_url( '/' ),
            ),
            'itemReviewed'     => array(
                '@type' => 'Movie',
                'name'  => $movie_name,
            ),
        );

        if ( '' !== $year ) {
            $schema['itemReviewed']['dateCreated'] = $year;
        }

        // Enrich the reviewed film so Google can identify it confidently
        // (image + director + IMDb link) -- the richer the itemReviewed, the
        // more eligible the page is for the star-rating review snippet.
        if ( '' !== $image_url ) {
            $schema['itemReviewed']['image'] = $image_url;
        }

        $review_director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        if ( '' !== $review_director ) {
            $director_nodes = array();
            foreach ( preg_split( '/\s*[\/,]\s*/', $review_director, -1, PREG_SPLIT_NO_EMPTY ) as $dname ) {
                $director_nodes[] = array(
                    '@type' => 'Person',
                    'name'  => trim( $dname ),
                );
            }
            if ( ! empty( $director_nodes ) ) {
                $schema['itemReviewed']['director'] = ( 1 === count( $director_nodes ) ) ? $director_nodes[0] : $director_nodes;
            }
        }

        $review_imdb_id = trim( (string) get_post_meta( $post_id, '_lunara_imdb_title_id', true ) );
        if ( '' === $review_imdb_id ) {
            $review_imdb_id = trim( (string) get_post_meta( $post_id, '_lunara_imdb_id', true ) );
        }
        if ( '' !== $review_imdb_id && preg_match( '/^tt\d+$/', $review_imdb_id ) ) {
            $schema['itemReviewed']['sameAs'] = 'https://www.imdb.com/title/' . $review_imdb_id . '/';
        }

        // Knowledge-graph weld (§11/§15): when the reviewed film exists as a
        // movie entity, itemReviewed carries the dossier's canonical @id — the
        // review and the dossier become one node in the site graph instead of
        // two anonymous Movie objects that crawlers must reconcile.
        if ( function_exists( 'lunara_entity_movie_for_review' ) ) {
            $graph_movie_id = lunara_entity_movie_for_review( $post_id );
            if ( $graph_movie_id > 0 ) {
                $schema['itemReviewed']['@id'] = function_exists( 'lunara_entity_schema_id' )
                    ? lunara_entity_schema_id( $graph_movie_id, 'movie' )
                    : get_permalink( $graph_movie_id ) . '#movie';
                $schema['itemReviewed']['url'] = get_permalink( $graph_movie_id );
            }
        }

        if ( '' !== $image_url ) {
            $schema['image'] = $image_url;
        }

        if ( is_numeric( $score ) ) {
            $schema['reviewRating'] = array(
                '@type'       => 'Rating',
                'ratingValue' => (float) $score,
                'bestRating'  => 5,
                'worstRating' => 0,
            );
        }

        // Breadcrumb trail (Home > Reviews > Title): gives the search result a
        // clean breadcrumb line and reinforces the site structure for crawlers.
        $reviews_url = get_post_type_archive_link( 'review' );
        if ( ! $reviews_url ) {
            $reviews_url = home_url( '/reviews/' );
        }
        $breadcrumb = array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'name'     => __( 'Home', 'lunara-film' ),
                    'item'     => home_url( '/' ),
                ),
                array(
                    '@type'    => 'ListItem',
                    'position' => 2,
                    'name'     => __( 'Reviews', 'lunara-film' ),
                    'item'     => $reviews_url,
                ),
                array(
                    '@type'    => 'ListItem',
                    'position' => 3,
                    'name'     => $title,
                    'item'     => $url,
                ),
            ),
        );

        // One consolidated @graph per page (§11/§15): the Review and its
        // breadcrumb ship as a single sheet instead of two loose scripts.
        unset( $schema['@context'] );
        $graph = array(
            '@context' => 'https://schema.org',
            '@graph'   => array( $schema, $breadcrumb ),
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'lunara_output_review_seo_meta', 7 );

/**
 * Suppress Blocksy's native footer via CSS so ours is the only one.
 */
function lunara_hide_blocksy_footer_css() {
    if ( ! apply_filters( 'lunara_use_custom_footer', true ) ) {
        return;
    }

    echo '<style id="lunara-hide-blocksy-footer">.ct-footer,footer.site-footer:not(.lunara-site-footer){display:none!important;}#header .menu-item-27569 .ct-menu-badge{display:none!important;}.lunara-site-footer{position:relative;overflow:hidden;margin-top:clamp(76px,8vw,120px);padding:clamp(48px,6vw,86px) 0 54px;background:radial-gradient(circle at top center,rgba(201,169,97,.08),transparent 34%),linear-gradient(180deg,rgba(15,29,46,.95),rgba(10,21,32,.98));border-top:1px solid rgba(201,169,97,.24);color:var(--lunara-text,#FAFBFC);}.lunara-footer-inner{max-width:min(100%,1360px);margin:0 auto;padding-inline:var(--lunara-shell-pad,28px);display:grid;gap:48px;}.lunara-footer-brand{display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center;}.lunara-footer-logo{max-height:var(--lunara-logo-max,64px);width:auto;}.lunara-footer-tagline{max-width:48ch;margin:0;color:var(--lunara-text-muted,#A8A8B8);font-size:.98rem;line-height:1.55;}.lunara-footer-nav-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px;}.lunara-footer-nav-col{padding:20px;border:1px solid rgba(201,169,97,.12);border-radius:22px;background:linear-gradient(180deg,rgba(18,31,47,.82),rgba(10,20,32,.56));}.lunara-footer-col-heading{margin:0 0 16px;color:var(--lunara-gold,#c9a961);font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;}.lunara-footer-nav-col ul,.lunara-site-footer .menu{list-style:none!important;margin:0!important;padding:0!important;display:grid!important;gap:10px!important;}.lunara-footer-nav-col li,.lunara-site-footer .menu li{list-style:none!important;margin:0!important;padding:0!important;}.lunara-footer-nav-col a,.lunara-site-footer .menu a{display:inline-flex!important;align-items:center;min-height:38px;padding:8px 14px;border:1px solid rgba(201,169,97,.12);border-radius:999px;background:rgba(255,255,255,.02);color:var(--lunara-text,#FAFBFC)!important;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;text-decoration:none!important;}.lunara-footer-nav-col a:hover,.lunara-site-footer .menu a:hover{border-color:rgba(201,169,97,.32);color:var(--lunara-gold-light,#e0c481)!important;}.lunara-footer-utility{display:flex;justify-content:space-between;gap:14px 18px;flex-wrap:wrap;padding-top:24px;border-top:1px solid rgba(201,169,97,.24);color:var(--lunara-text-muted,#A8A8B8);font-size:.78rem;}.lunara-footer-utility a{color:var(--lunara-text-muted,#A8A8B8)!important;text-decoration:none!important;}@media(max-width:900px){.lunara-footer-nav-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}@media(max-width:640px){.lunara-footer-nav-grid{grid-template-columns:1fr;}.lunara-footer-utility{justify-content:center;text-align:center;}}</style>' . "\n";
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Critical Journal single-page guardrails.
 *
 * Jetpack Boost can inline only a subset of the main stylesheet on first paint,
 * so keep this small page-specific CSS in wp_head where it survives optimization.
 */
function lunara_output_journal_single_guardrail_css() {
    if ( ! is_singular( 'journal' ) ) {
        return;
    }
    ?>
    <style id="lunara-journal-single-guardrail-css">
    body.single-journal,body.single-journal #main-container{max-width:100%!important;overflow-x:hidden!important;}
    body.single-journal .lunara-journal-single-page{width:100%;max-width:min(100%,1440px)!important;margin-inline:auto!important;color:var(--lunara-text,#FAFBFC)!important;overflow-x:hidden!important;}
    body.single-journal .lunara-journal-cinematic-hero,body.single-journal .lunara-journal-cinematic-hero-header{max-width:100%!important;box-sizing:border-box!important;}
    body.single-journal .lunara-journal-cinematic-hero-header{padding-inline:clamp(18px,4vw,56px)!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-inner{margin-inline:auto!important;justify-items:center!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-frame{position:relative!important;display:block!important;width:min(calc(100% - clamp(36px,8vw,112px)),1080px)!important;max-width:100%!important;aspect-ratio:16/9!important;height:auto!important;min-height:0!important;margin-inline:auto!important;box-sizing:border-box!important;overflow:hidden!important;}
    body.single-journal .lunara-journal-cinematic-hero-media{position:absolute!important;inset:0!important;display:block!important;width:100%!important;height:100%!important;margin:0!important;}
    body.single-journal .lunara-journal-cinematic-hero-image{display:block!important;width:100%!important;height:100%!important;max-width:100%!important;object-fit:cover!important;object-position:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit{position:absolute!important;left:clamp(14px,2vw,24px)!important;right:clamp(14px,2vw,24px)!important;bottom:clamp(12px,2vw,22px)!important;z-index:5!important;display:block!important;width:fit-content!important;max-width:min(92%,720px)!important;margin:0!important;padding:8px 11px!important;border:1px solid rgba(244,239,227,.2)!important;border-radius:999px!important;background:rgba(5,11,18,.76)!important;color:rgba(244,239,227,.88)!important;font-size:.78rem!important;line-height:1.35!important;backdrop-filter:blur(10px)!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit a{color:var(--lunara-gold-light,#e0c481)!important;text-decoration:none!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit a:hover{text-decoration:underline!important;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{max-width:min(100%,980px)!important;margin-inline:auto!important;color:var(--lunara-gold-light,#e0c481)!important;text-align:center!important;text-wrap:balance;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-meta,body.single-journal .lunara-journal-single-signal{justify-content:center!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-inner{max-width:100%!important;min-width:0!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{min-width:0!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-review-single-body{width:min(calc(100% - clamp(36px,8vw,112px)),920px)!important;max-width:920px!important;margin:clamp(20px,3vw,38px) auto 0!important;padding:clamp(20px,3vw,34px)!important;box-sizing:border-box!important;border:1px solid rgba(201,169,97,.16)!important;border-radius:22px!important;background:linear-gradient(180deg,rgba(15,29,46,.72),rgba(8,16,27,.54))!important;box-shadow:0 24px 58px rgba(0,0,0,.22)!important;}
    body.single-journal .lunara-review-single-body::before{display:none!important;}
    body.single-journal .lunara-review-single-body-grid{display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;margin-inline:auto!important;}
    body.single-journal .lunara-review-single-content{width:100%!important;max-width:74ch!important;min-width:0!important;margin-inline:auto!important;overflow-wrap:break-word!important;}
    body.single-journal .lunara-review-single-content p{max-width:74ch!important;margin-inline:auto!important;font-size:clamp(1rem,1.05vw,1.12rem)!important;line-height:1.78!important;color:var(--lunara-text,#FAFBFC)!important;overflow-wrap:break-word!important;}
    body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link){display:inline!important;max-width:100%!important;color:var(--lunara-gold-light,#e0c481)!important;text-decoration:underline!important;text-decoration-color:rgba(224,196,129,.58)!important;text-decoration-thickness:1px!important;text-underline-offset:.22em!important;white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}
    body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link):hover,body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link):focus-visible{color:#f4efe3!important;text-decoration-color:rgba(244,239,227,.82)!important;}
    body.single-journal .lunara-review-single-rail{width:100%!important;max-width:74ch!important;margin:clamp(22px,3vw,34px) auto 0!important;}
    body.single-journal .lunara-review-single-rail-sticky{position:static!important;display:grid!important;gap:16px!important;}
    body.single-journal .lunara-review-single-rail-actions .lunara-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;max-width:100%!important;min-height:42px!important;padding:10px 16px!important;box-sizing:border-box!important;border:1px solid rgba(201,169,97,.28)!important;border-radius:999px!important;background:rgba(201,169,97,.08)!important;color:var(--lunara-gold-light,#e0c481)!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;}
    body.single-journal .lunara-journal-single-related{width:min(calc(100% - 80px),1160px)!important;margin:clamp(36px,5vw,72px) auto!important;padding-inline:0!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-head{margin-bottom:22px!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-kicker{color:var(--lunara-gold-light,#e0c481)!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-title{color:var(--lunara-text,#FAFBFC)!important;font-size:clamp(1.65rem,2.6vw,2.35rem)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-related-grid{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:24px!important;overflow:visible!important;padding:0!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-card{width:100%!important;min-width:0!important;max-width:none!important;min-height:0!important;overflow:hidden!important;border:1px solid rgba(201,169,97,.2)!important;border-radius:22px!important;background:linear-gradient(180deg,rgba(15,29,46,.94),rgba(10,21,32,.98))!important;box-shadow:0 24px 54px rgba(0,0,0,.28)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-link{display:grid!important;grid-template-rows:auto 1fr!important;width:100%!important;height:100%!important;color:inherit!important;text-decoration:none!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-poster-wrap{aspect-ratio:16/10!important;width:100%!important;max-height:none!important;min-height:0!important;overflow:hidden!important;border-radius:22px 22px 0 0!important;background:rgba(255,255,255,.04)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-poster-wrap img,body.single-journal .lunara-journal-single-related .lunara-review-grid-poster{display:block!important;width:100%!important;height:100%!important;object-fit:cover!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-copy{display:grid!important;gap:10px!important;align-content:start!important;padding:18px 20px 22px!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-kicker{margin:0!important;color:var(--lunara-gold-light,#e0c481)!important;font-size:.72rem!important;letter-spacing:.14em!important;text-transform:uppercase!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-title{margin:0!important;color:var(--lunara-gold,#c9a961)!important;font-size:clamp(1.05rem,1.4vw,1.28rem)!important;line-height:1.16!important;text-decoration:none!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-meta{margin:0!important;color:var(--lunara-text-muted,#A8A8B8)!important;font-size:.88rem!important;}
    body.single-journal .lunara-journal-image-carousel{width:min(calc(100% - 36px),1080px)!important;max-width:min(calc(100% - 36px),1080px)!important;margin:clamp(18px,3vw,34px) auto 0!important;box-sizing:border-box!important;overflow:hidden!important;}
    body.single-journal .lunara-journal-image-carousel-head{display:flex!important;align-items:end!important;justify-content:space-between!important;gap:16px!important;}
    body.single-journal .lunara-journal-image-carousel-controls{display:inline-flex!important;align-items:center!important;gap:8px!important;flex:0 0 auto!important;}
    body.single-journal .lunara-journal-carousel-btn{display:inline-grid!important;place-items:center!important;width:36px!important;height:36px!important;min-width:36px!important;min-height:36px!important;margin:0!important;padding:0!important;border:1px solid rgba(201,169,97,.45)!important;border-radius:999px!important;background:rgba(5,11,18,.72)!important;color:var(--lunara-gold-light,#e0c481)!important;font-size:1.1rem!important;line-height:1!important;box-shadow:0 10px 24px rgba(0,0,0,.22)!important;cursor:pointer!important;}
    body.single-journal .lunara-journal-carousel-btn:hover,body.single-journal .lunara-journal-carousel-btn:focus-visible{background:rgba(201,169,97,.18)!important;color:#f4efe3!important;outline:2px solid rgba(224,196,129,.36)!important;outline-offset:2px!important;}
    body.single-journal .lunara-journal-image-carousel-track{display:grid!important;grid-auto-flow:column!important;grid-auto-columns:minmax(280px,74%)!important;gap:14px!important;max-width:100%!important;overflow-x:auto!important;scroll-snap-type:x mandatory!important;padding:0 2px 12px!important;}
    body.single-journal .lunara-journal-image-carousel-slide{min-width:0!important;max-width:100%!important;scroll-snap-align:start!important;}
    body.single-journal .lunara-journal-image-carousel-image{display:block!important;width:100%!important;height:clamp(190px,48vw,420px)!important;max-height:420px!important;aspect-ratio:16/9!important;object-fit:cover!important;}
    @media (max-width:980px){body.single-journal .lunara-journal-single-related .lunara-review-related-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;}}
    @media (max-width:640px){body.single-journal{width:100vw!important;max-width:100vw!important;overflow-x:hidden!important;}body.single-journal .lunara-journal-single-page{width:100vw!important;max-width:100vw!important;padding-inline:0!important;overflow-x:hidden!important;}body.single-journal .lunara-journal-cinematic-hero-header{padding-inline:18px!important;text-align:center!important;}body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{width:min(100%,282px)!important;max-width:282px!important;margin-inline:auto!important;font-size:clamp(1.55rem,7.1vw,1.82rem)!important;line-height:1.12!important;white-space:normal!important;overflow-wrap:normal!important;word-break:normal!important;text-wrap:balance!important;}body.single-journal .lunara-journal-cinematic-hero-frame{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;height:clamp(240px,74vw,360px)!important;}body.single-journal .lunara-journal-cinematic-hero-image{object-position:62% center!important;}body.single-journal .lunara-journal-cinematic-hero-credit{bottom:10px!important;border-radius:12px!important;font-size:.72rem!important;}body.single-journal .lunara-review-single-body{width:calc(100vw - 48px)!important;max-width:calc(100vw - 48px)!important;margin-top:18px!important;padding:18px!important;border-radius:18px!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-body-grid{display:block!important;width:100%!important;max-width:100%!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-content{display:block!important;width:min(100%,300px)!important;max-width:300px!important;margin-inline:auto!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-content p{max-width:100%!important;margin-inline:0!important;font-size:.98rem!important;line-height:1.72!important;}body.single-journal .lunara-review-single-content p,body.single-journal .lunara-review-single-content p *{white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link),body.single-journal .lunara-review-single-content em{display:inline!important;white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}body.single-journal .lunara-review-single-rail{max-width:100%!important;}body.single-journal .lunara-journal-image-carousel{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;}body.single-journal .lunara-journal-image-carousel-head{display:grid!important;align-items:start!important;}body.single-journal .lunara-journal-image-carousel-controls{justify-self:start!important;}body.single-journal .lunara-journal-image-carousel-track{grid-auto-columns:100%!important;}body.single-journal .lunara-journal-image-carousel-image{height:190px!important;max-height:190px!important;}body.single-journal .lunara-journal-single-related{width:calc(100vw - 48px)!important;max-width:calc(100vw - 48px)!important;padding-inline:0!important;}body.single-journal .lunara-journal-single-related .lunara-review-related-grid{grid-template-columns:1fr!important;}}
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_journal_single_guardrail_css', 101 );

/**
 * Scoped Splide pilot for the homepage Oscar Facts signature lane.
 */
function lunara_enqueue_home_splide_pilot_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	$splide_css = lunara_resolve_theme_asset(
		'assets/vendor/splide/splide-core.min.css',
		array( 'assets/vendor/splide/splide-core.min.css' )
	);
	if ( $splide_css['uri'] ) {
		wp_enqueue_style(
			'lunara-splide-core',
			$splide_css['uri'],
			array(),
			lunara_theme_asset_version( $splide_css['path'] )
		);
	}

	$splide_js = lunara_resolve_theme_asset(
		'assets/vendor/splide/splide.min.js',
		array( 'assets/vendor/splide/splide.min.js' )
	);
	if ( $splide_js['uri'] ) {
		wp_enqueue_script(
			'lunara-splide',
			$splide_js['uri'],
			array(),
			lunara_theme_asset_version( $splide_js['path'] ),
			true
		);
	}

	$pilot_js = lunara_resolve_theme_asset(
		'assets/js/lunara-splide-pilot.js',
		array( 'lunara-splide-pilot.js' )
	);
	if ( $pilot_js['uri'] ) {
		wp_enqueue_script(
			'lunara-home-splide-pilot',
			$pilot_js['uri'],
			array( 'lunara-splide' ),
			lunara_theme_asset_version( $pilot_js['path'] ),
			true
		);
	}

	// Rotating cinematic hero (its own Splide cross-fade init).
	$hero_js = lunara_resolve_theme_asset(
		'assets/js/lunara-hero-carousel.js',
		array( 'lunara-hero-carousel.js' )
	);
	if ( $hero_js['uri'] ) {
		wp_enqueue_script(
			'lunara-hero-carousel',
			$hero_js['uri'],
			array( 'lunara-splide' ),
			lunara_theme_asset_version( $hero_js['path'] ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_home_splide_pilot_assets', 20 );

/**
 * Lights Down — immersive screening mode for single reviews and journal
 * entries (chrome dim past the hero, reading-progress hairline, ambient
 * accent sampled from the hero art).
 */
function lunara_enqueue_immersive_read_assets() {
	if ( is_admin() || is_feed() || ! is_singular( array( 'review', 'journal' ) ) ) {
		return;
	}

	$read_js = lunara_resolve_theme_asset(
		'assets/js/lunara-immersive-read.js',
		array( 'lunara-immersive-read.js' )
	);
	if ( $read_js['uri'] ) {
		wp_enqueue_script(
			'lunara-immersive-read',
			$read_js['uri'],
			array(),
			lunara_theme_asset_version( $read_js['path'] ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_immersive_read_assets', 20 );

/**
 * Reviews archive authority package polish.
 */
function lunara_enqueue_review_archive_dynamic_rails() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_reviews_archive = is_post_type_archive( 'review' )
        || is_page_template( 'page-reviews.php' )
        || is_page( 'reviews' );

    if ( ! $is_reviews_archive ) {
        return;
    }

    $rail_js = lunara_resolve_theme_asset(
        'assets/js/lunara-dynamic-rails.js',
        array( 'lunara-dynamic-rails.js' )
    );

    if ( $rail_js['uri'] ) {
        wp_enqueue_script(
            'lunara-dynamic-rails',
            $rail_js['uri'],
            array(),
            lunara_theme_asset_version( $rail_js['path'] ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_review_archive_dynamic_rails' );

function lunara_output_journal_archive_studio_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_journal_archive = function_exists( 'lunara_is_journal_archive_family' )
        ? lunara_is_journal_archive_family()
        : ( is_post_type_archive( 'journal' ) || is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) ) );

    if ( ! $is_journal_archive ) {
        return;
    }

    $archive_density = lunara_home_select_setting( 'lunara_journal_archive_density', 'editorial', array( 'compact', 'editorial', 'showcase' ) );
    $lead_prominence = lunara_home_select_setting( 'lunara_journal_archive_lead_prominence', 'standard', array( 'restrained', 'standard', 'feature' ) );
    $desk_rhythm     = lunara_home_select_setting( 'lunara_journal_archive_desk_rhythm', 'balanced', array( 'quick', 'balanced', 'immersive' ) );
    $section_gap     = lunara_home_brand_number_setting( 'lunara_journal_archive_section_gap', 38, 18, 86 );
    $hero_min_height = lunara_home_brand_number_setting( 'lunara_journal_archive_hero_min_height', 240, 160, 420 );
    $card_min_height = lunara_home_brand_number_setting( 'lunara_journal_archive_card_min_height', 390, 280, 560 );
    $media_min       = lunara_home_brand_number_setting( 'lunara_journal_archive_media_min_height', 220, 160, 360 );

    $shell_gap_map = array(
        'compact'   => 20,
        'editorial' => 28,
        'showcase'  => 38,
    );
    $grid_gap_map = array(
        'compact'   => 16,
        'editorial' => 24,
        'showcase'  => 30,
    );
    $excerpt_clamp_map = array(
        'compact'   => 2,
        'editorial' => 3,
        'showcase'  => 4,
    );
    $retention_gap_map = array(
        'compact'   => 12,
        'editorial' => 18,
        'showcase'  => 24,
    );
    $desk_pad_map = array(
        'quick'     => 10,
        'balanced'  => 14,
        'immersive' => 18,
    );
    $lead_media_map = array(
        'restrained' => 220,
        'standard'   => 260,
        'feature'    => 310,
    );
    $lead_title_map = array(
        'restrained' => 1.5,
        'standard'   => 1.72,
        'feature'    => 2.02,
    );

    $shell_gap     = isset( $shell_gap_map[ $archive_density ] ) ? absint( $shell_gap_map[ $archive_density ] ) : 28;
    $grid_gap      = isset( $grid_gap_map[ $archive_density ] ) ? absint( $grid_gap_map[ $archive_density ] ) : 24;
    $excerpt_clamp = isset( $excerpt_clamp_map[ $archive_density ] ) ? absint( $excerpt_clamp_map[ $archive_density ] ) : 3;
    $retention_gap = isset( $retention_gap_map[ $archive_density ] ) ? absint( $retention_gap_map[ $archive_density ] ) : 18;
    $desk_pad      = isset( $desk_pad_map[ $desk_rhythm ] ) ? absint( $desk_pad_map[ $desk_rhythm ] ) : 14;
    $lead_media    = isset( $lead_media_map[ $lead_prominence ] ) ? max( $media_min, absint( $lead_media_map[ $lead_prominence ] ) ) : max( $media_min, 260 );
    $lead_title    = isset( $lead_title_map[ $lead_prominence ] ) ? (float) $lead_title_map[ $lead_prominence ] : 1.72;

    if ( 'quick' === $desk_rhythm ) {
        $section_gap = (int) round( $section_gap * 0.86 );
    } elseif ( 'immersive' === $desk_rhythm ) {
        $section_gap = (int) round( $section_gap * 1.12 );
        $hero_min_height = (int) round( $hero_min_height * 1.08 );
    }

    $section_gap     = max( 16, min( 92, $section_gap ) );
    $hero_min_height = max( 150, min( 440, $hero_min_height ) );
    ?>
    <style id="lunara-journal-archive-studio-css">
    body.post-type-archive-journal .lunara-journal-archive-page {
        --lunara-journal-archive-section-gap: <?php echo esc_html( $section_gap ); ?>px;
        --lunara-journal-archive-shell-gap: <?php echo esc_html( $shell_gap ); ?>px;
        --lunara-journal-archive-hero-min: <?php echo esc_html( $hero_min_height ); ?>px;
        --lunara-journal-archive-card-min: <?php echo esc_html( $card_min_height ); ?>px;
        --lunara-journal-archive-media-min: <?php echo esc_html( $media_min ); ?>px;
        --lunara-journal-archive-lead-media-min: <?php echo esc_html( $lead_media ); ?>px;
        --lunara-journal-archive-grid-gap: <?php echo esc_html( $grid_gap ); ?>px;
        --lunara-journal-archive-excerpt-clamp: <?php echo esc_html( $excerpt_clamp ); ?>;
        --lunara-journal-archive-retention-gap: <?php echo esc_html( $retention_gap ); ?>px;
        --lunara-journal-archive-desk-pad: <?php echo esc_html( $desk_pad ); ?>px;
        --lunara-journal-archive-lead-title: <?php echo esc_html( $lead_title ); ?>rem;
        gap: var(--lunara-journal-archive-section-gap) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-hero {
        align-content: center !important;
        background:
            radial-gradient(circle at 88% 0%, rgba(224, 196, 129, 0.14), transparent 34%),
            linear-gradient(145deg, rgba(8, 20, 33, 0.94), rgba(13, 29, 44, 0.84)) !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 18px !important;
        display: grid !important;
        margin-bottom: 0 !important;
        min-height: var(--lunara-journal-archive-hero-min) !important;
        padding: clamp(24px, 4.4vw, 54px) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-deskbar {
        background: linear-gradient(135deg, rgba(7, 18, 30, 0.74), rgba(14, 29, 44, 0.62)) !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 16px !important;
        gap: 10px !important;
        margin: 0 !important;
        padding: var(--lunara-journal-archive-desk-pad) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-deskbar span {
        background: rgba(6, 14, 24, 0.58) !important;
        border: 1px solid rgba(224, 196, 129, 0.12) !important;
        border-radius: 999px !important;
        padding: 8px 11px !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-filters {
        background: linear-gradient(135deg, rgba(8, 20, 33, 0.78), rgba(14, 29, 44, 0.58)) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 16px !important;
        margin: 0 !important;
        padding: 10px !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-toolbar {
        align-items: center !important;
        background: rgba(7, 18, 30, 0.78) !important;
        border: 1px solid rgba(224, 196, 129, 0.16) !important;
        border-radius: 18px !important;
        display: grid !important;
        gap: var(--lunara-journal-archive-shell-gap) !important;
        grid-template-columns: minmax(220px, 0.7fr) minmax(0, 1fr) !important;
        margin: 0 !important;
        padding: clamp(16px, 2vw, 22px) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-grid {
        align-items: stretch !important;
        gap: var(--lunara-journal-archive-grid-gap) !important;
        margin-top: 0 !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card {
        border-color: rgba(224, 196, 129, 0.16) !important;
        min-height: var(--lunara-journal-archive-card-min) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-link {
        display: grid !important;
        grid-template-rows: auto 1fr !important;
        height: 100% !important;
        min-height: var(--lunara-journal-archive-card-min) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-poster-wrap {
        aspect-ratio: 16 / 10 !important;
        min-height: var(--lunara-journal-archive-media-min) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-excerpt {
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: var(--lunara-journal-archive-excerpt-clamp) !important;
        overflow: hidden !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
        grid-column: span 2 !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-link {
        grid-template-columns: minmax(320px, 0.58fr) minmax(0, 1fr) !important;
        grid-template-rows: minmax(0, 1fr) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap {
        height: 100% !important;
        min-height: var(--lunara-journal-archive-lead-media-min) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-copy {
        align-content: center !important;
        padding: clamp(22px, 3.2vw, 38px) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-title {
        font-size: clamp(1.35rem, 2.4vw, var(--lunara-journal-archive-lead-title)) !important;
        line-height: 1.08 !important;
        max-width: 16ch !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed,
    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed.is-lead {
        grid-column: auto !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed .lunara-review-grid-poster-wrap {
        display: none !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed .lunara-review-grid-link,
    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed.is-lead .lunara-review-grid-link {
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: minmax(0, 1fr) !important;
        min-height: var(--lunara-journal-archive-card-min) !important;
    }

    /* A featured "lead" entry with no image (is-text-brief) was keeping the
       two-column featured span with an empty media column. Treat it like a
       media-failed lead: a normal single-column card, with the content
       vertically balanced so it never reads as half-empty. */
    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief,
    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief.is-lead {
        grid-column: auto !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief .lunara-review-grid-poster-wrap {
        display: none !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief .lunara-review-grid-link,
    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief.is-lead .lunara-review-grid-link {
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: minmax(0, 1fr) !important;
        min-height: var(--lunara-journal-archive-card-min) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief.is-lead .lunara-review-grid-footer {
        margin-top: 20px !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed .lunara-review-grid-copy {
        align-content: start !important;
        background:
            radial-gradient(circle at 88% 0%, rgba(224, 196, 129, 0.12), transparent 34%),
            linear-gradient(145deg, rgba(9, 23, 37, 0.94), rgba(14, 31, 48, 0.86)) !important;
        min-height: var(--lunara-journal-archive-card-min) !important;
        padding: clamp(18px, 2.6vw, 28px) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed .lunara-review-grid-title,
    body.post-type-archive-journal .lunara-journal-archive-card.is-media-failed.is-lead .lunara-review-grid-title {
        max-width: 18ch !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-retention {
        background: linear-gradient(135deg, rgba(7, 18, 30, 0.72), rgba(14, 29, 44, 0.64)) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 20px !important;
        display: grid !important;
        gap: var(--lunara-journal-archive-retention-gap) !important;
        margin-top: 0 !important;
        padding: clamp(18px, 2.4vw, 30px) !important;
    }

    body.post-type-archive-journal .lunara-journal-archive-retention-grid {
        gap: var(--lunara-journal-archive-retention-gap) !important;
    }

    @media (max-width: 900px) {
        body.post-type-archive-journal .lunara-journal-archive-toolbar {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
            grid-column: span 2 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-link {
            grid-template-columns: minmax(220px, 0.46fr) minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 620px) {
        body.post-type-archive-journal .lunara-journal-archive-page {
            gap: calc(var(--lunara-journal-archive-section-gap) * 0.78) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-hero {
            border-radius: 16px !important;
            min-height: 0 !important;
            padding: 22px 18px !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card,
        body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
            grid-column: auto !important;
            min-height: 0 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-link,
        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-link {
            grid-template-columns: minmax(0, 1fr) !important;
            grid-template-rows: auto 1fr !important;
            min-height: 0 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-poster-wrap,
        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap {
            height: auto !important;
            min-height: clamp(188px, 54vw, var(--lunara-journal-archive-media-min)) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-title {
            max-width: 100% !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-deskbar span {
            border-radius: 12px !important;
            justify-content: space-between !important;
            width: 100% !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_output_journal_archive_studio_css', 6 );

function lunara_output_journal_archive_media_guard_js() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_journal_archive = function_exists( 'lunara_is_journal_archive_family' )
        ? lunara_is_journal_archive_family()
        : ( is_post_type_archive( 'journal' ) || is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) ) );

    if ( ! $is_journal_archive ) {
        return;
    }
    ?>
    <script id="lunara-journal-archive-media-guard-js">
    (function(){
        var selector = '.lunara-journal-archive-card .lunara-review-grid-poster-wrap img';

        function markFailed(img) {
            var card = img && img.closest ? img.closest('.lunara-journal-archive-card') : null;
            if (!card) {
                return;
            }
            card.classList.add('is-media-failed');
            card.classList.remove('has-media');
        }

        function inspect(img) {
            if (!img) {
                return;
            }
            if (img.complete && img.naturalWidth === 0) {
                markFailed(img);
            }
        }

        document.querySelectorAll(selector).forEach(function(img){
            img.addEventListener('error', function(){ markFailed(img); }, { once: true });
            img.addEventListener('load', function(){ inspect(img); }, { once: true });
            inspect(img);
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_journal_archive_media_guard_js', 7 );

function lunara_output_review_archive_authority_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_reviews_archive = is_post_type_archive( 'review' )
        || is_page_template( 'page-reviews.php' )
        || is_page( 'reviews' );

    if ( ! $is_reviews_archive ) {
        return;
    }

    $archive_density  = lunara_home_select_setting( 'lunara_reviews_archive_density', 'editorial', array( 'compact', 'editorial', 'showcase' ) );
    $lead_prominence  = lunara_home_select_setting( 'lunara_reviews_archive_lead_prominence', 'standard', array( 'restrained', 'standard', 'feature' ) );
    $rail_density     = lunara_home_select_setting( 'lunara_reviews_archive_rail_density', 'editorial', array( 'compact', 'editorial', 'showcase' ) );
    $section_gap      = lunara_home_brand_number_setting( 'lunara_reviews_archive_section_gap', 40, 20, 90 );
    $lead_min_height  = lunara_home_brand_number_setting( 'lunara_reviews_archive_lead_min_height', 460, 340, 640 );
    $card_min_height  = lunara_home_brand_number_setting( 'lunara_reviews_archive_card_min_height', 360, 260, 540 );
    $compact_media_w  = lunara_home_brand_number_setting( 'lunara_reviews_archive_compact_media_width', 116, 92, 150 );
    $compact_media_h  = (int) round( $compact_media_w * 1.3276 );

    $shell_gap_map = array(
        'compact'   => 28,
        'editorial' => 36,
        'showcase'  => 46,
    );
    $run_gap_map = array(
        'compact'   => 20,
        'editorial' => 26,
        'showcase'  => 32,
    );
    $rail_gap_map = array(
        'compact'   => 12,
        'editorial' => 18,
        'showcase'  => 24,
    );
    $excerpt_clamp_map = array(
        'compact'   => 2,
        'editorial' => 3,
        'showcase'  => 4,
    );
    $lead_media_map = array(
        'restrained' => 240,
        'standard'   => 270,
        'feature'    => 320,
    );
    $lead_pad_map = array(
        'restrained' => 32,
        'standard'   => 46,
        'feature'    => 58,
    );

    $shell_gap      = isset( $shell_gap_map[ $archive_density ] ) ? absint( $shell_gap_map[ $archive_density ] ) : 36;
    $run_gap        = isset( $run_gap_map[ $archive_density ] ) ? absint( $run_gap_map[ $archive_density ] ) : 26;
    $rail_gap       = isset( $rail_gap_map[ $rail_density ] ) ? absint( $rail_gap_map[ $rail_density ] ) : 18;
    $excerpt_clamp  = isset( $excerpt_clamp_map[ $rail_density ] ) ? absint( $excerpt_clamp_map[ $rail_density ] ) : 3;
    $lead_media_min = isset( $lead_media_map[ $lead_prominence ] ) ? absint( $lead_media_map[ $lead_prominence ] ) : 270;
    $lead_copy_pad  = isset( $lead_pad_map[ $lead_prominence ] ) ? absint( $lead_pad_map[ $lead_prominence ] ) : 46;
    ?>
    <style id="lunara-review-archive-authority-css">
    body.post-type-archive-review .lunara-review-archive-page,
    body.page-template-page-reviews .lunara-review-archive-page {
        --lunara-reviews-archive-section-gap: <?php echo esc_html( $section_gap ); ?>px;
        --lunara-reviews-archive-shell-gap: <?php echo esc_html( $shell_gap ); ?>px;
        --lunara-reviews-archive-run-gap: <?php echo esc_html( $run_gap ); ?>px;
        --lunara-reviews-archive-lead-min: <?php echo esc_html( $lead_min_height ); ?>px;
        --lunara-reviews-archive-lead-media-min: <?php echo esc_html( $lead_media_min ); ?>px;
        --lunara-reviews-archive-lead-copy-pad: <?php echo esc_html( $lead_copy_pad ); ?>px;
        --lunara-reviews-archive-card-min: <?php echo esc_html( $card_min_height ); ?>px;
        --lunara-reviews-archive-compact-media-width: <?php echo esc_html( $compact_media_w ); ?>px;
        --lunara-reviews-archive-compact-media-height: <?php echo esc_html( $compact_media_h ); ?>px;
        --lunara-reviews-archive-rail-gap: <?php echo esc_html( $rail_gap ); ?>px;
        --lunara-reviews-archive-excerpt-clamp: <?php echo esc_html( $excerpt_clamp ); ?>;
        gap: var(--lunara-reviews-archive-section-gap) !important;
    }

    body.post-type-archive-review .lunara-review-archive-page > .lunara-home-section,
    body.page-template-page-reviews .lunara-review-archive-page > .lunara-home-section {
        margin-bottom: calc(var(--lunara-reviews-archive-section-gap) * 0.72) !important;
    }

    body.post-type-archive-review .lunara-review-archive-hero,
    body.page-template-page-reviews .lunara-review-archive-hero {
        padding-bottom: clamp(18px, 2.8vw, 34px) !important;
        padding-top: clamp(34px, 4.8vw, 58px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-hero-shell,
    body.page-template-page-reviews .lunara-review-archive-hero-shell {
        padding: clamp(24px, 3vw, 34px) clamp(24px, 3.2vw, 38px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-shell,
    body.page-template-page-reviews .lunara-review-archive-shell {
        display: grid !important;
        gap: var(--lunara-reviews-archive-shell-gap) !important;
    }

    body.post-type-archive-review .lunara-review-archive-spotlight,
    body.page-template-page-reviews .lunara-review-archive-spotlight {
        display: grid !important;
        gap: var(--lunara-reviews-archive-run-gap) !important;
        grid-template-columns: minmax(0, 1fr) !important;
        margin-top: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-run,
    body.page-template-page-reviews .lunara-review-archive-run {
        display: grid !important;
        gap: var(--lunara-reviews-archive-run-gap) !important;
    }

    body.post-type-archive-review .lunara-review-archive-run-head,
    body.page-template-page-reviews .lunara-review-archive-run-head {
        margin: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead {
        min-height: 0 !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
        min-height: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact {
        min-height: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-link {
        display: grid !important;
        grid-template-columns: var(--lunara-reviews-archive-compact-media-width) minmax(0, 1fr) !important;
        min-height: var(--lunara-reviews-archive-compact-media-height) !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-media,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-media {
        aspect-ratio: auto !important;
        grid-column: 1 !important;
        grid-row: 1 !important;
        height: var(--lunara-reviews-archive-compact-media-height) !important;
        max-width: var(--lunara-reviews-archive-compact-media-width) !important;
        min-height: var(--lunara-reviews-archive-compact-media-height) !important;
        min-width: 0 !important;
        width: var(--lunara-reviews-archive-compact-media-width) !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-image,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-image {
        display: block !important;
        height: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-copy,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-copy {
        align-content: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-run-grid,
    body.page-template-page-reviews .lunara-review-archive-run-grid {
        margin-top: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
        display: grid !important;
        grid-template-columns: minmax(var(--lunara-reviews-archive-lead-media-min), 0.4fr) minmax(0, 1fr) !important;
        grid-template-rows: minmax(0, 1fr) !important;
        min-height: var(--lunara-reviews-archive-lead-min) !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-media,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-media {
        grid-column: 1 !important;
        grid-row: 1 !important;
        height: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-image,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-image {
        display: block !important;
        height: 100% !important;
        max-width: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-copy,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-copy {
        align-content: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
        padding: clamp(24px, 3.6vw, var(--lunara-reviews-archive-lead-copy-pad)) !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail,
    body.page-template-page-reviews .lunara-review-archive-rail {
        display: grid !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
        grid-template-columns: minmax(230px, 0.56fr) repeat(2, minmax(0, 1fr)) !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail-shell,
    body.page-template-page-reviews .lunara-review-archive-rail-shell {
        align-content: center !important;
        display: grid !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail-shell .lunara-section-title,
    body.page-template-page-reviews .lunara-review-archive-rail-shell .lunara-section-title {
        font-size: clamp(1.18rem, 2vw, 1.66rem) !important;
        line-height: 1.12 !important;
        max-width: 17ch !important;
    }

    @media (max-width: 820px) {
        body.post-type-archive-review .lunara-review-archive-hero-shell,
        body.page-template-page-reviews .lunara-review-archive-hero-shell {
            gap: 16px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list,
        body.page-template-page-reviews .lunara-review-archive-debrief-list {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list li,
        body.page-template-page-reviews .lunara-review-archive-debrief-list li {
            border: 1px solid rgba(201, 169, 97, 0.13) !important;
            border-radius: 12px !important;
            padding: 10px 11px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list span,
        body.page-template-page-reviews .lunara-review-archive-debrief-list span {
            text-align: left !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead {
            min-height: 0 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
            grid-template-columns: minmax(124px, 0.38fr) minmax(0, 1fr) !important;
            min-height: 0 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-media,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-media {
            aspect-ratio: 3 / 4 !important;
            height: auto !important;
            min-height: 0 !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-copy,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-copy {
            align-content: center !important;
            gap: 10px !important;
            padding: 18px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.post-type-archive-review .lunara-review-grid-excerpt,
        body.page-template-page-reviews .lunara-review-grid-excerpt {
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt {
            -webkit-line-clamp: var(--lunara-reviews-archive-excerpt-clamp) !important;
        }

        body.post-type-archive-review .lunara-review-grid-excerpt,
        body.page-template-page-reviews .lunara-review-grid-excerpt {
            -webkit-line-clamp: var(--lunara-reviews-archive-excerpt-clamp) !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform.lunara-review-grid,
        body.page-template-page-reviews .lunara-review-archive-uniform.lunara-review-grid {
            display: grid !important;
            gap: 12px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-link,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-link {
            display: grid !important;
            grid-template-columns: 104px minmax(0, 1fr) !important;
            min-height: 148px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-poster-wrap,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-poster-wrap {
            aspect-ratio: auto !important;
            border-radius: 14px 0 0 14px !important;
            height: 148px !important;
            min-height: 148px !important;
            width: 104px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-copy,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-copy {
            align-content: center !important;
            gap: 7px !important;
            min-width: 0 !important;
            padding: 12px 13px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-title,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-title {
            font-size: clamp(0.96rem, 4.4vw, 1.12rem) !important;
            line-height: 1.16 !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail-shell .lunara-section-title,
        body.page-template-page-reviews .lunara-review-archive-rail-shell .lunara-section-title {
            font-size: clamp(1.28rem, 6.4vw, 1.72rem) !important;
            line-height: 1.08 !important;
            max-width: 12ch !important;
        }
    }

    @media (max-width: 540px) {
        body.post-type-archive-review .lunara-review-archive-page,
        body.page-template-page-reviews .lunara-review-archive-page {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        body.post-type-archive-review .lunara-review-archive-page > .lunara-home-section,
        body.page-template-page-reviews .lunara-review-archive-page > .lunara-home-section,
        body.post-type-archive-review .lunara-review-archive-hero,
        body.page-template-page-reviews .lunara-review-archive-hero,
        body.post-type-archive-review .lunara-review-archive-shell,
        body.page-template-page-reviews .lunara-review-archive-shell {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: calc(100vw - 24px) !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: calc(100vw - 24px) !important;
        }

        body.post-type-archive-review .lunara-review-archive-hero-shell,
        body.page-template-page-reviews .lunara-review-archive-hero-shell {
            max-width: 100% !important;
            padding: 18px !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-archive-spotlight,
        body.page-template-page-reviews .lunara-review-archive-spotlight,
        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail,
        body.post-type-archive-review .lunara-review-archive-uniform.lunara-review-grid,
        body.page-template-page-reviews .lunara-review-archive-uniform.lunara-review-grid,
        body.post-type-archive-review .lunara-review-archive-grid,
        body.page-template-page-reviews .lunara-review-archive-grid {
            max-width: 100% !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list,
        body.page-template-page-reviews .lunara-review-archive-debrief-list {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
            grid-template-columns: 118px minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-title,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-title {
            font-size: clamp(1.16rem, 6vw, 1.52rem) !important;
            line-height: 1.08 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt {
            -webkit-line-clamp: 2 !important;
            font-size: 0.9rem !important;
            line-height: 1.44 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-footer,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-footer,
        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-footer,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-footer {
            display: none !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail {
            gap: 10px !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail-shell,
        body.page-template-page-reviews .lunara-review-archive-rail-shell,
        body.post-type-archive-review .lunara-review-archive-run-head,
        body.page-template-page-reviews .lunara-review-archive-run-head {
            padding: 16px !important;
            border-radius: 16px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-link {
            grid-template-columns: 88px minmax(0, 1fr) !important;
            min-height: 132px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-media,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-media {
            height: 132px !important;
            max-width: 88px !important;
            min-height: 132px !important;
            width: 88px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-copy,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-copy {
            align-content: center !important;
            gap: 6px !important;
            overflow: hidden !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-title,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-title {
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 3 !important;
            font-size: clamp(0.86rem, 4.5vw, 1rem) !important;
            line-height: 1.1 !important;
            max-width: 100% !important;
            overflow: hidden !important;
            overflow-wrap: anywhere !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-excerpt {
            display: none !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-footer,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-footer {
            display: none !important;
        }
    }

    .lunara-review-archive-page .lunara-review-archive-hero-shell {
        align-items: stretch !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 22px !important;
        box-shadow: 0 30px 72px rgba(0, 0, 0, 0.24), 0 0 0 1px rgba(255, 255, 255, 0.035) inset !important;
        display: grid !important;
        gap: clamp(18px, 2.4vw, 32px) !important;
        grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.64fr) !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-hero {
        order: 1 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-utility {
        order: 2 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-grid {
        order: 3 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-pagination {
        order: 4 !important;
        width: 100% !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-copy-wrap {
        align-content: center !important;
        display: grid !important;
        gap: clamp(14px, 1.8vw, 22px) !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-archive-hero-title {
        max-width: 12ch !important;
    }

    .lunara-review-archive-page .lunara-archive-hero-copy {
        color: rgba(238, 242, 245, 0.82) !important;
        font-size: 1.12rem !important;
        line-height: 1.62 !important;
        margin: 0 !important;
        max-width: 58ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief {
        align-content: space-between !important;
        background: linear-gradient(145deg, rgba(8, 20, 33, 0.94), rgba(18, 31, 46, 0.9)) !important;
        border: 1px solid rgba(224, 196, 129, 0.19) !important;
        border-radius: 18px !important;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.18) !important;
        display: grid !important;
        gap: 18px !important;
        margin: 0 !important;
        padding: clamp(18px, 2.2vw, 26px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-kicker {
        color: var(--lunara-gold, #d8b665) !important;
        font-size: 0.76rem !important;
        letter-spacing: 0 !important;
        margin: 0 !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list {
        display: grid !important;
        gap: 10px !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list li {
        align-items: center !important;
        background: rgba(255, 255, 255, 0.035) !important;
        border: 1px solid rgba(224, 196, 129, 0.12) !important;
        border-radius: 12px !important;
        display: flex !important;
        gap: 12px !important;
        justify-content: space-between !important;
        padding: 10px 12px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list strong {
        color: rgba(224, 196, 129, 0.9) !important;
        font-size: 0.72rem !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list span {
        color: rgba(246, 248, 250, 0.9) !important;
        font-weight: 700 !important;
        text-align: right !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions a,
    .lunara-review-archive-page .lunara-review-archive-sort-link {
        align-items: center !important;
        border: 1px solid rgba(224, 196, 129, 0.28) !important;
        border-radius: 999px !important;
        color: rgba(239, 222, 173, 0.94) !important;
        display: inline-flex !important;
        font-size: 0.76rem !important;
        font-weight: 800 !important;
        justify-content: center !important;
        letter-spacing: 0 !important;
        min-height: 38px !important;
        padding: 9px 13px !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions a:hover,
    .lunara-review-archive-page .lunara-review-archive-sort-link:hover,
    .lunara-review-archive-page .lunara-review-archive-sort-link.is-active {
        background: rgba(224, 196, 129, 0.14) !important;
        border-color: rgba(224, 196, 129, 0.52) !important;
        color: #fff4cf !important;
    }

    .lunara-review-archive-page .lunara-review-archive-utility {
        margin-bottom: clamp(18px, 2.6vw, 30px) !important;
        margin-top: clamp(-12px, -1vw, -4px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar {
        align-items: center !important;
        background: rgba(7, 18, 30, 0.78) !important;
        border: 1px solid rgba(224, 196, 129, 0.16) !important;
        border-radius: 18px !important;
        display: grid !important;
        gap: clamp(14px, 2vw, 24px) !important;
        grid-template-columns: minmax(220px, 0.72fr) minmax(0, 1fr) !important;
        padding: clamp(16px, 2vw, 22px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar-head {
        margin: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar-head .lunara-section-title {
        font-size: 1.24rem !important;
        line-height: 1.15 !important;
        max-width: 26ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-sort {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        justify-content: flex-end !important;
        align-items: center !important;
        background: linear-gradient(135deg, rgba(8, 20, 33, 0.86), rgba(18, 31, 46, 0.72)) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 16px !important;
        padding: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-sort-label {
        color: rgba(224, 196, 129, 0.78) !important;
        flex: 0 0 auto !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
        line-height: 1 !important;
        margin-right: 2px !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head {
        align-content: center !important;
        background:
            radial-gradient(circle at 20% 0%, rgba(224, 196, 129, 0.12), transparent 38%),
            linear-gradient(145deg, rgba(8, 20, 33, 0.9), rgba(13, 29, 44, 0.76)) !important;
        border: 1px solid rgba(224, 196, 129, 0.15) !important;
        border-radius: 18px !important;
        display: grid !important;
        gap: 6px !important;
        margin-top: clamp(4px, 1vw, 10px) !important;
        min-height: 100% !important;
        padding: clamp(18px, 2.2vw, 24px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head .lunara-section-title {
        font-size: clamp(1.36rem, 2.05vw, 1.8rem) !important;
        line-height: 1.12 !important;
        max-width: 13ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-suite {
        display: grid !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
        grid-template-columns: minmax(210px, 0.42fr) minmax(0, 1fr) !important;
        align-items: stretch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail .lunara-review-grid-card {
        min-height: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail .lunara-review-grid-link {
        min-height: var(--lunara-reviews-archive-card-min) !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card,
    .lunara-review-archive-page .lunara-review-grid-card {
        border-color: rgba(224, 196, 129, 0.16) !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-link,
    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-link {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-height: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-copy,
    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-copy {
        background: linear-gradient(145deg, rgba(9, 23, 37, 0.92), rgba(13, 29, 44, 0.82)) !important;
    }

    .lunara-review-archive-page .lunara-score-badge-inline {
        align-self: start !important;
        display: inline-flex !important;
        margin: 0 0 2px 0 !important;
        max-width: max-content !important;
        position: static !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.has-no-review-quote .lunara-review-grid-copy {
        gap: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.has-no-review-quote .lunara-review-grid-title {
        margin-bottom: 4px !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-footer {
        padding-left: clamp(16px, 2vw, 22px) !important;
        padding-right: clamp(16px, 2vw, 22px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-run-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        align-items: stretch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-run-grid .lunara-review-grid-card,
    .lunara-review-archive-page .lunara-review-archive-run-grid .lunara-review-grid-link {
        min-height: var(--lunara-reviews-archive-card-min) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-card {
        align-content: space-between !important;
        background:
            radial-gradient(circle at 14% 0%, rgba(224, 196, 129, 0.16), transparent 38%),
            linear-gradient(145deg, rgba(9, 23, 37, 0.94), rgba(14, 31, 48, 0.86)) !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 18px !important;
        box-shadow: 0 20px 42px rgba(0, 0, 0, 0.18) !important;
        display: grid !important;
        gap: 22px !important;
        min-height: 100% !important;
        padding: clamp(20px, 2.7vw, 32px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-card.spans-2 {
        grid-column: span 2 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy {
        display: grid !important;
        gap: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy h3 {
        color: #f2d589 !important;
        font-size: clamp(1.28rem, 2.4vw, 2rem) !important;
        line-height: 1.05 !important;
        margin: 0 !important;
        max-width: 15ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy p:last-child {
        color: rgba(239, 242, 245, 0.78) !important;
        font-size: 0.98rem !important;
        line-height: 1.58 !important;
        margin: 0 !important;
        max-width: 48ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions a {
        align-items: center !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 999px !important;
        color: rgba(239, 222, 173, 0.94) !important;
        display: inline-flex !important;
        font-size: 0.74rem !important;
        font-weight: 800 !important;
        justify-content: center !important;
        letter-spacing: 0 !important;
        min-height: 40px !important;
        padding: 9px 13px !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions a:hover,
    .lunara-review-archive-page .lunara-review-archive-retention-actions a:focus-visible {
        background: rgba(224, 196, 129, 0.14) !important;
        border-color: rgba(224, 196, 129, 0.52) !important;
        color: #fff4cf !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-suite {
        align-items: stretch !important;
        display: grid !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
        grid-template-columns: minmax(210px, 0.32fr) minmax(0, 1fr) !important;
        min-width: 0 !important;
        overflow: hidden !important;
        padding: clamp(16px, 2.1vw, 24px) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 20px !important;
        background: linear-gradient(135deg, rgba(7, 18, 30, 0.72), rgba(14, 29, 44, 0.64)) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head {
        align-content: center !important;
        display: grid !important;
        min-width: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-dynamic-rail,
    body.page-template-page-reviews .lunara-review-archive-dynamic-rail,
    .lunara-review-archive-page .lunara-review-archive-dynamic-rail {
        align-content: start !important;
        display: grid !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: auto minmax(0, 1fr) auto !important;
        min-width: 0 !important;
        position: relative !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-controls {
        display: flex !important;
        gap: 8px !important;
        justify-content: flex-end !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-control {
        align-items: center !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        padding: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.32) !important;
        border-radius: 999px !important;
        background: rgba(6, 16, 27, 0.82) !important;
        color: rgba(239, 222, 173, 0.96) !important;
        cursor: pointer !important;
        display: inline-grid !important;
        font-size: 1.32rem !important;
        justify-content: center !important;
        line-height: 1 !important;
        transition: background-color 180ms ease, border-color 180ms ease, color 180ms ease, transform 180ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-control:hover,
    .lunara-review-archive-page .lunara-review-archive-rail-control:focus-visible {
        background: rgba(224, 196, 129, 0.16) !important;
        border-color: rgba(224, 196, 129, 0.58) !important;
        color: #fff4cf !important;
        outline: 2px solid rgba(224, 196, 129, 0.28) !important;
        outline-offset: 2px !important;
        transform: translateY(-1px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track {
        display: flex !important;
        gap: var(--lunara-reviews-archive-rail-gap) !important;
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        overscroll-behavior-x: contain !important;
        padding: 1px 1px 8px !important;
        scroll-behavior: smooth !important;
        scroll-snap-type: x mandatory !important;
        scrollbar-width: none !important;
        -webkit-overflow-scrolling: touch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track::-webkit-scrollbar {
        display: none !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track:focus-visible {
        outline: 2px solid rgba(224, 196, 129, 0.38) !important;
        outline-offset: 4px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item {
        display: grid !important;
        flex: 0 0 min(360px, calc((100% - var(--lunara-reviews-archive-rail-gap)) / 2)) !important;
        min-width: 0 !important;
        scroll-snap-align: start !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item > .lunara-review-grid-card {
        height: 100% !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item .lunara-review-grid-link {
        height: 100% !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dots {
        align-items: center !important;
        display: flex !important;
        gap: 8px !important;
        justify-content: center !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dot {
        width: 9px !important;
        height: 9px !important;
        min-width: 9px !important;
        padding: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.6) !important;
        border-radius: 999px !important;
        background: rgba(244, 239, 227, 0.13) !important;
        cursor: pointer !important;
        transition: width 180ms ease, background-color 180ms ease, border-color 180ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dot.is-active {
        width: 30px !important;
        background: #d7b66f !important;
        border-color: #d7b66f !important;
    }

    .lunara-review-archive-page .lunara-review-archive-run {
        margin-top: clamp(4px, 1vw, 12px) !important;
    }

    @media (max-width: 900px) {
        .lunara-review-archive-page .lunara-review-archive-hero-shell,
        .lunara-review-archive-page .lunara-review-archive-toolbar {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-sort {
            justify-content: flex-start !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-suite,
        .lunara-review-archive-page .lunara-review-archive-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-suite {
            overflow: hidden !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: min(78vw, 340px) !important;
        }
    }

    @media (max-width: 820px) {
        .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-link,
        .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-link {
            grid-template-columns: minmax(0, 1fr) !important;
            min-height: 0 !important;
        }

        .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-copy {
            padding: 16px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-head {
            padding: 16px 0 12px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-controls {
            justify-content: flex-start !important;
        }

        .lunara-review-archive-page .lunara-review-archive-run-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-retention-card,
        .lunara-review-archive-page .lunara-review-archive-retention-card.spans-2 {
            grid-column: auto !important;
        }
    }

    @media (max-width: 540px) {
        .lunara-review-archive-page .lunara-review-archive-hero-shell,
        .lunara-review-archive-page .lunara-review-archive-toolbar,
        .lunara-review-archive-page .lunara-review-archive-debrief {
            border-radius: 16px !important;
        }

        .lunara-review-archive-page .lunara-archive-hero-title {
            max-width: 9ch !important;
        }

        .lunara-review-archive-page .lunara-review-archive-hero-actions a,
        .lunara-review-archive-page .lunara-review-archive-sort-link {
            flex: 1 1 auto !important;
            min-width: min(100%, 142px) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-sort-label {
            flex-basis: 100% !important;
        }

        .lunara-review-archive-page .lunara-review-archive-debrief-list li {
            align-items: flex-start !important;
            flex-direction: column !important;
            gap: 4px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-debrief-list span {
            text-align: left !important;
        }

        .lunara-review-archive-page .lunara-review-archive-retention-card {
            padding: 18px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: min(82vw, 300px) !important;
        }
    }

    @media (min-width: 681px) and (max-width: 900px) {
        body.post-type-archive-review .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-rail:not(.lunara-review-archive-dynamic-rail),
        body.page-template-page-reviews .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-rail:not(.lunara-review-archive-dynamic-rail) {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.post-type-archive-review .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-dynamic-rail,
        body.page-template-page-reviews .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-dynamic-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .lunara-review-archive-page .lunara-review-archive-rail-track {
            scroll-behavior: auto !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-control,
        .lunara-review-archive-page .lunara-review-archive-rail-dot {
            transition: none !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_output_review_archive_authority_css', 5 );

function lunara_review_card_image_focus_position( $value ) {
    $value = sanitize_key( (string) $value );

    $positions = array(
        'center-center' => 'center center',
        'center-top'    => 'center top',
        'center-bottom' => 'center bottom',
        'left-center'   => 'left center',
        'right-center'  => 'right center',
    );

    return isset( $positions[ $value ] ) ? $positions[ $value ] : $positions['center-center'];
}

function lunara_review_card_image_focus_setting( $key ) {
    $allowed = array( 'center-center', 'center-top', 'center-bottom', 'left-center', 'right-center' );
    $value   = sanitize_key( (string) get_theme_mod( $key, 'center-center' ) );

    if ( ! in_array( $value, $allowed, true ) ) {
        return 'center-center';
    }

    return $value;
}

function lunara_output_review_card_image_focus_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_review_focus_surface = is_post_type_archive( 'review' ) || is_page( 'reviews' ) || is_singular( 'review' ) || is_front_page();

    if ( ! $is_review_focus_surface ) {
        return;
    }

    $archive_focus = lunara_review_card_image_focus_position( lunara_review_card_image_focus_setting( 'lunara_review_archive_image_focus' ) );
    $rail_focus    = lunara_review_card_image_focus_position( lunara_review_card_image_focus_setting( 'lunara_review_rail_image_focus' ) );
    $related_focus = lunara_review_card_image_focus_position( lunara_review_card_image_focus_setting( 'lunara_review_related_image_focus' ) );
    $feature_focus = lunara_review_card_image_focus_position( lunara_review_card_image_focus_setting( 'lunara_review_feature_image_focus' ) );
    ?>
    <style id="lunara-review-card-image-focus-vars">
    body.post-type-archive-review .lunara-review-archive-page,
    body.page-template-page-reviews .lunara-review-archive-page,
    body.home .lunara-front-page,
    body.single-review .lunara-review-single-page {
        --lunara-review-archive-image-focus: <?php echo esc_html( $archive_focus ); ?>;
        --lunara-review-rail-image-focus: <?php echo esc_html( $rail_focus ); ?>;
        --lunara-review-related-image-focus: <?php echo esc_html( $related_focus ); ?>;
        --lunara-review-feature-image-focus: <?php echo esc_html( $feature_focus ); ?>;
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_card_image_focus_css', 1009 );

/**
 * Public review Debrief polish.
 *
 * The Debrief is a retention module, not a raw metadata dump. Keep this layer
 * late so the live review page presents the poster, signature facts, watch
 * links, and pairings as one intentional editorial package.
 */
function lunara_output_review_debrief_polish_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-debrief-polish-css">
    body.single-review .lunara-review-single-debrief-section {
        margin: clamp(52px, 6.5vw, 92px) auto clamp(54px, 7vw, 96px) !important;
        width: min(1180px, calc(100vw - 108px)) !important;
    }

    body.single-review .sharedaddy.sd-sharing-enabled {
        display: none !important;
    }

    body.single-review .lunara-review-single-debrief-wrap {
        display: grid !important;
        isolation: isolate !important;
        padding: clamp(24px, 3.4vw, 42px) !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 24px !important;
        background:
            radial-gradient(circle at 16% 0%, rgba(224, 196, 129, 0.18), transparent 30%),
            radial-gradient(circle at 92% 12%, rgba(112, 148, 185, 0.12), transparent 28%),
            linear-gradient(135deg, rgba(14, 28, 43, 0.94), rgba(5, 13, 22, 0.98)) !important;
        box-shadow:
            0 34px 76px rgba(0, 0, 0, 0.32),
            0 0 0 1px rgba(255, 255, 255, 0.035) inset !important;
    }

    body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
        align-items: stretch !important;
        grid-template-columns: minmax(210px, 300px) minmax(0, 1fr) !important;
        gap: clamp(22px, 3.2vw, 40px) !important;
    }

    body.single-review .lunara-review-single-debrief-media {
        align-content: start !important;
        display: grid !important;
        gap: 16px !important;
        grid-column: 1 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief-poster-shell {
        aspect-ratio: 2 / 3 !important;
        max-width: 280px !important;
        overflow: hidden !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 18px !important;
        background: rgba(255, 255, 255, 0.035) !important;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.36) !important;
    }

    body.single-review .lunara-review-single-debrief-poster {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief-media-copy {
        padding: 14px 0 0 !important;
        border-top: 1px solid rgba(224, 196, 129, 0.16) !important;
    }

    body.single-review .lunara-review-single-debrief-media-kicker {
        margin-bottom: 10px !important;
        color: rgba(244, 210, 126, 0.9) !important;
        font-size: 0.78rem !important;
        letter-spacing: 0.18em !important;
        line-height: 1.2 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief-media-title {
        color: #fafbfc !important;
        font-size: clamp(1.08rem, 1.6vw, 1.32rem) !important;
        line-height: 1.18 !important;
    }

    body.single-review .lunara-review-single-debrief-media-meta {
        margin-top: 8px !important;
        color: rgba(244, 239, 227, 0.74) !important;
        font-size: 0.9rem !important;
        letter-spacing: 0.08em !important;
    }

    body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
        align-self: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        margin-top: 0 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature {
        display: grid !important;
        gap: 18px !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
        margin: 0 !important;
        max-width: 13ch !important;
        color: #f4d27e !important;
        font-size: clamp(1.72rem, 2.9vw, 2.36rem) !important;
        line-height: 0.98 !important;
        letter-spacing: 0.06em !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-kicker {
        order: -1 !important;
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.74) !important;
        font-size: 0.74rem !important;
        letter-spacing: 0.22em !important;
        line-height: 1.2 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-list--signature {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 7px !important;
        min-width: 0 !important;
        padding: 14px 16px !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.018)) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.14) !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li strong {
        color: rgba(244, 210, 126, 0.86) !important;
        font-size: 0.7rem !important;
        letter-spacing: 0.16em !important;
        line-height: 1.25 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature .lunara-debrief-value,
    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature p {
        color: rgba(250, 251, 252, 0.92) !important;
        font-size: 0.96rem !important;
        line-height: 1.48 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row,
    body.single-review .lunara-review-single-debrief .lunara-debrief-where-row {
        grid-column: 1 / -1 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row strong {
        display: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row .lunara-debrief-value {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 10px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-oscar-ledger {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-review-watch-links {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) !important;
        gap: 10px !important;
    }

    body.single-review .lunara-review-single-debrief--pairings {
        margin-top: 20px !important;
        padding: clamp(22px, 3vw, 34px) !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 24px !important;
        background:
            radial-gradient(circle at 100% 0%, rgba(224, 196, 129, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(12, 27, 43, 0.88), rgba(6, 15, 25, 0.94)) !important;
        box-shadow: 0 24px 58px rgba(0, 0, 0, 0.22) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list {
        display: grid !important;
        gap: 14px !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list > li {
        padding: 16px !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.032) !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
        grid-template-columns: 96px minmax(0, 1fr) !important;
        gap: 18px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
        width: 96px !important;
        aspect-ratio: 2 / 3 !important;
        object-fit: cover !important;
        border-radius: 12px !important;
    }

    @media (max-width: 900px) {
        body.single-review .lunara-review-single-debrief-section {
            left: auto !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-debrief-wrap {
            padding: 20px !important;
            border-radius: 20px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 22px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: 1 !important;
            grid-row: auto !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            max-width: 220px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-list--signature {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
            grid-template-columns: 76px minmax(0, 1fr) !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
            width: 76px !important;
        }
    }

    @media (max-width: 520px) {
        body.single-review .lunara-review-single-debrief-section {
            margin: 42px auto 46px !important;
            width: min(100%, calc(100vw - 28px)) !important;
        }

        body.single-review .lunara-review-single-debrief-wrap {
            padding: 16px !important;
            border-radius: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            gap: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-media {
            justify-items: center !important;
            gap: 12px !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(100%, 188px) !important;
            max-width: 188px !important;
            margin-inline: auto !important;
            border-radius: 14px !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            width: 100% !important;
            padding-top: 12px !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-media-kicker {
            margin-bottom: 8px !important;
            letter-spacing: 0.14em !important;
        }

        body.single-review .lunara-review-single-debrief-media-title,
        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
            max-width: none !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-media-meta {
            letter-spacing: 0.04em !important;
            overflow-wrap: anywhere !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li {
            padding: 12px 13px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_debrief_polish_css', 1001 );

/**
 * Single Review reader-spine polish.
 *
 * Keep the criticism itself centered and authoritative while the utility rail
 * supports it. The Debrief already repeats the factual metadata, so the rail
 * details card is suppressed here to avoid duplicate "Review Details" moments.
 */
function lunara_output_review_reader_spine_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-reader-spine-css">
    body.single-review .lunara-review-single-body {
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-details {
        display: none !important;
    }

    body.single-review .lunara-review-single-content {
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-content p,
    body.single-review .lunara-review-single-content h2,
    body.single-review .lunara-review-single-content h3,
    body.single-review .lunara-review-single-content ul,
    body.single-review .lunara-review-single-content ol,
    body.single-review .lunara-review-single-content blockquote {
        margin-left: auto !important;
        margin-right: auto !important;
    }

    body.single-review .lunara-review-single-cinematic-hero {
        margin: 0 auto clamp(28px, 3vw, 42px) !important;
        max-width: min(100%, 820px) !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual--poster-hero {
        max-width: min(100%, 520px) !important;
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
        display: grid !important;
        place-items: center !important;
        min-height: 0 !important;
        aspect-ratio: auto !important;
        padding: clamp(8px, 1.2vw, 14px) !important;
        background:
            radial-gradient(circle at 50% 0%, rgba(224, 196, 129, 0.12), transparent 38%),
            linear-gradient(180deg, rgba(12, 26, 42, 0.96), rgba(5, 13, 22, 0.98)) !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
        width: auto !important;
        height: auto !important;
        max-width: 100% !important;
        max-height: clamp(380px, 66vh, 680px) !important;
        object-fit: contain !important;
    }

    body.single-review .lunara-review-single-ledger-card .lunara-oscar-ledger {
        display: grid !important;
        gap: 8px !important;
        justify-items: start !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-ledger-card .lunara-oscar-ledger-counts {
        display: block !important;
        color: rgba(244, 239, 227, 0.86) !important;
        font-size: 0.88rem !important;
        line-height: 1.4 !important;
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-body {
            width: min(1280px, calc(100vw - 96px)) !important;
            max-width: 1280px !important;
        }

        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 820px) minmax(220px, 270px) !important;
            justify-content: center !important;
            gap: clamp(30px, 3.2vw, 48px) !important;
        }

        body.single-review .lunara-review-single-content {
            width: 100% !important;
            max-width: 820px !important;
        }

        body.single-review .lunara-review-single-content p,
        body.single-review .lunara-review-single-content h2,
        body.single-review .lunara-review-single-content h3,
        body.single-review .lunara-review-single-content ul,
        body.single-review .lunara-review-single-content ol,
        body.single-review .lunara-review-single-content blockquote {
            max-width: 72ch !important;
        }

        body.single-review .lunara-review-single-rail {
            max-width: 270px !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            gap: 12px !important;
        }
    }

    @media (min-width: 760px) and (max-width: 1039px) {
        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, min(100%, 820px)) !important;
            justify-content: center !important;
        }

        body.single-review .lunara-review-single-content,
        body.single-review .lunara-review-single-rail {
            width: min(100%, 820px) !important;
            max-width: 820px !important;
            margin-inline: auto !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            position: static !important;
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            grid-column: 1 / -1 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_reader_spine_css', 1002 );

/**
 * Single Review desktop/tablet editorial repair.
 *
 * Large screens should feel like a trade feature package: strong poster,
 * compact navigation, readable criticism, and quiet utility support.
 */
function lunara_output_review_desktop_editorial_repair_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-desktop-editorial-repair-css">
    @media (min-width: 761px) {
        body.single-review .lunara-review-single-page {
            background:
                radial-gradient(circle at 20% 0%, rgba(224, 196, 129, 0.065), transparent 28%),
                linear-gradient(180deg, rgba(9, 20, 32, 0.98), rgba(7, 17, 28, 1)) !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100vw !important;
        }

        body.single-review .lunara-review-single-hero {
            width: min(1180px, calc(100vw - 72px)) !important;
            margin: 0 auto !important;
            padding: clamp(24px, 3.2vw, 42px) 0 18px !important;
            border-bottom: 1px solid rgba(224, 196, 129, 0.14) !important;
        }

        body.single-review .lunara-review-single-hero-inner {
            max-width: 760px !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-title {
            color: rgba(250, 251, 252, 0.96) !important;
            font-size: clamp(2.35rem, 4.1vw, 4rem) !important;
            line-height: 0.98 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-body {
            width: min(1180px, calc(100vw - 72px)) !important;
            max-width: 1180px !important;
            margin: 0 auto !important;
            padding: clamp(22px, 3vw, 34px) 0 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        body.single-review .lunara-review-single-body-grid {
            align-items: start !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            margin-bottom: clamp(22px, 2.4vw, 34px) !important;
            max-width: min(100%, 500px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            padding: clamp(8px, 1vw, 12px) !important;
            border: 1px solid rgba(224, 196, 129, 0.16) !important;
            border-radius: 8px !important;
            background:
                radial-gradient(circle at 50% 0%, rgba(224, 196, 129, 0.10), transparent 36%),
                linear-gradient(180deg, rgba(12, 27, 43, 0.98), rgba(5, 13, 22, 0.98)) !important;
            box-shadow: 0 22px 52px rgba(0, 0, 0, 0.28) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            max-height: clamp(500px, 58vh, 620px) !important;
        }

        body.single-review .lunara-reader-toc {
            margin: 0 0 clamp(26px, 3vw, 40px) !important;
            padding: 16px 18px 18px !important;
            border: 1px solid rgba(224, 196, 129, 0.18) !important;
            border-radius: 10px !important;
            background: linear-gradient(180deg, rgba(15, 30, 47, 0.88), rgba(8, 18, 30, 0.94)) !important;
        }

        body.single-review .lunara-reader-toc-kicker,
        body.single-review .lunara-reader-toc-title {
            margin: 0 0 12px !important;
            color: #e0c481 !important;
            text-align: left !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-reader-toc-link {
            display: flex !important;
            align-items: center !important;
            min-height: 42px !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            color: #e0c481 !important;
            font-size: 0.88rem !important;
            line-height: 1.2 !important;
            text-decoration: none !important;
        }

        body.single-review .lunara-review-single-content > p,
        body.single-review .lunara-review-single-content > ul,
        body.single-review .lunara-review-single-content > ol,
        body.single-review .lunara-review-single-content > blockquote {
            color: rgba(250, 251, 252, 0.95) !important;
            font-size: clamp(1.02rem, 1.08vw, 1.12rem) !important;
            line-height: 1.76 !important;
        }

        body.single-review .lunara-review-single-content > p:first-of-type {
            color: rgba(250, 251, 252, 0.98) !important;
            font-size: clamp(1.08rem, 1.22vw, 1.22rem) !important;
            line-height: 1.66 !important;
        }

        body.single-review .lunara-review-single-content > h2 {
            margin-top: clamp(30px, 3.2vw, 46px) !important;
            color: #e0c481 !important;
            font-size: clamp(1.58rem, 2vw, 2.05rem) !important;
            line-height: 1.08 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-rail .lunara-journal-rail-card,
        body.single-review .lunara-review-single-where-card {
            padding: 16px !important;
            border-radius: 10px !important;
            background: linear-gradient(180deg, rgba(15, 30, 47, 0.88), rgba(8, 18, 30, 0.94)) !important;
        }

        body.single-review .lunara-review-single-where-card {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            display: grid !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-rail-actions .lunara-btn {
            min-height: 42px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.1em !important;
        }

        body.single-review .lunara-review-related {
            width: min(1180px, calc(100vw - 72px)) !important;
            margin: clamp(48px, 5.4vw, 78px) auto 0 !important;
            padding: 24px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-related-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-body-grid {
            grid-template-columns: minmax(0, 720px) minmax(220px, 270px) !important;
            justify-content: center !important;
            gap: clamp(34px, 4vw, 64px) !important;
        }

        body.single-review .lunara-review-single-content {
            max-width: 720px !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 480px !important;
        }

        body.single-review .lunara-review-single-rail {
            padding-top: 6px !important;
        }
    }

    @media (min-width: 761px) and (max-width: 1039px) {
        body.single-review .lunara-review-single-hero,
        body.single-review .lunara-review-single-body,
        body.single-review .lunara-review-related {
            width: min(100%, calc(100vw - 48px)) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            max-width: min(100%, 480px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            max-height: 620px !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.single-review .lunara-review-single-rail {
            margin: 20px auto 0 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_desktop_editorial_repair_css', 1003 );

/**
 * Single Review mobile editorial repair.
 *
 * Phone readers should get a confident article package, not a shrunken
 * desktop proof. Keep the poster strong, compress utility modules, and make
 * related criticism behave like a retention lane instead of full-page posters.
 */
function lunara_output_review_mobile_editorial_repair_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-mobile-editorial-repair-css">
    @media (max-width: 760px) {
        body.single-review,
        body.single-review #main-container {
            background: #07131f !important;
            overflow-x: hidden !important;
        }

        body.single-review main.lunara-review-single-page,
        body.single-review .lunara-review-single-page {
            max-width: 100vw !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            padding: 0 16px 52px !important;
            width: 100vw !important;
        }

        body.single-review .lunara-review-single-hero {
            margin: 0 -16px 0 !important;
            padding: 22px 22px 18px !important;
            width: calc(100% + 32px) !important;
            border-bottom: 1px solid rgba(224, 196, 129, 0.16) !important;
            background:
                radial-gradient(circle at 86% 0%, rgba(224, 196, 129, 0.12), transparent 34%),
                linear-gradient(180deg, rgba(17, 31, 46, 0.98), rgba(7, 19, 31, 0.98)) !important;
        }

        body.single-review .lunara-review-single-hero-inner {
            gap: 12px !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-title {
            font-size: clamp(2.05rem, 10vw, 2.65rem) !important;
            line-height: 0.98 !important;
            max-width: 11ch !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-meta {
            gap: 9px !important;
            line-height: 1.35 !important;
        }

        body.single-review .lunara-review-single-body {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 20px 0 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        body.single-review .lunara-review-single-body-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 22px !important;
            width: 100% !important;
        }

        body.single-review .lunara-review-single-content {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 0 !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            order: -3 !important;
            margin: 0 auto 24px !important;
            width: min(100%, 318px) !important;
            max-width: 318px !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual--poster-hero,
        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            width: 100% !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            padding: 8px !important;
            border: 1px solid rgba(224, 196, 129, 0.2) !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(11, 24, 38, 0.98), rgba(4, 11, 19, 0.98)) !important;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.32) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            max-height: none !important;
            object-fit: contain !important;
        }

        body.single-review .lunara-reader-toc {
            order: -2 !important;
            margin: 0 0 24px !important;
            padding: 14px 14px 16px !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(16, 31, 48, 0.92), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-reader-toc-title,
        body.single-review .lunara-reader-toc-kicker {
            margin-bottom: 10px !important;
            text-align: left !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 8px !important;
            overflow: visible !important;
            padding: 0 0 4px !important;
            scroll-snap-type: none !important;
        }

        body.single-review .lunara-reader-toc-links .lunara-reader-toc-link,
        body.single-review .lunara-reader-toc-item {
            width: 100% !important;
            margin: 0 !important;
            scroll-snap-align: none !important;
        }

        body.single-review .lunara-reader-toc-link {
            display: flex !important;
            align-items: center !important;
            min-height: 42px !important;
            width: 100% !important;
            max-width: none !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            font-size: 0.9rem !important;
            line-height: 1.22 !important;
            white-space: normal !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-content > p,
        body.single-review .lunara-review-single-content > ul,
        body.single-review .lunara-review-single-content > ol,
        body.single-review .lunara-review-single-content > blockquote {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            color: rgba(250, 251, 252, 0.96) !important;
            font-size: 1.05rem !important;
            line-height: 1.76 !important;
        }

        body.single-review .lunara-review-single-content > h2,
        body.single-review .lunara-review-single-content > h3 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 30px 0 14px !important;
            color: #e0c481 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-content > h2 {
            font-size: clamp(1.36rem, 6.2vw, 1.58rem) !important;
            line-height: 1.12 !important;
        }

        body.single-review .lunara-review-single-content > h3 {
            font-size: clamp(1.24rem, 5.8vw, 1.5rem) !important;
            line-height: 1.18 !important;
        }

        body.single-review .lunara-review-single-rail {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
            position: static !important;
        }

        body.single-review .lunara-review-single-rail .lunara-journal-rail-card {
            padding: 13px 14px !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(13, 28, 44, 0.94), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-review-single-where-card {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-rail-actions .lunara-btn {
            min-height: 42px !important;
            padding: 9px 11px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.08em !important;
        }

        body.single-review .lunara-review-single-debrief-section {
            width: 100% !important;
            max-width: 100% !important;
            margin: 38px auto 42px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap,
        body.single-review .lunara-review-single-debrief--pairings {
            padding: 16px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: 112px minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: auto !important;
            grid-row: 1 !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            max-width: 112px !important;
            justify-self: start !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            display: none !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
            max-width: 100% !important;
            font-size: clamp(1.18rem, 5.8vw, 1.42rem) !important;
            line-height: 1.04 !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li,
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list > li {
            padding: 12px !important;
            border-radius: 8px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
            display: grid !important;
            grid-template-columns: 58px minmax(0, 1fr) !important;
            gap: 12px !important;
            align-items: start !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
            width: 58px !important;
            border-radius: 7px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list {
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
            font-size: 0.88rem !important;
            line-height: 1.48 !important;
        }

        body.single-review .lunara-review-related {
            display: grid !important;
            gap: 16px !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 34px 0 0 !important;
            padding: 18px 0 0 !important;
            border: 0 !important;
            border-top: 1px solid rgba(224, 196, 129, 0.18) !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        body.single-review .lunara-review-related::before {
            display: none !important;
        }

        body.single-review .lunara-review-related .lunara-home-section-head {
            display: flex !important;
            align-items: end !important;
            justify-content: space-between !important;
            gap: 14px !important;
            margin: 0 !important;
        }

        body.single-review .lunara-review-related .lunara-section-title {
            font-size: clamp(1.32rem, 7vw, 1.68rem) !important;
            line-height: 1.08 !important;
        }

        body.single-review .lunara-review-related .lunara-section-link {
            flex: 0 0 auto !important;
            font-size: 0.75rem !important;
            white-space: nowrap !important;
        }

        body.single-review .lunara-review-related-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-card {
            width: 100% !important;
            min-height: 0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            background: linear-gradient(180deg, rgba(14, 28, 43, 0.92), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-link {
            display: grid !important;
            grid-template-columns: 96px minmax(0, 1fr) !important;
            align-items: stretch !important;
            min-height: 142px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap {
            width: 96px !important;
            min-width: 96px !important;
            height: 142px !important;
            max-height: 142px !important;
            aspect-ratio: auto !important;
            border-radius: 0 !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster,
        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-copy {
            display: grid !important;
            align-content: center !important;
            gap: 7px !important;
            min-width: 0 !important;
            padding: 12px 14px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-title {
            font-size: 1rem !important;
            line-height: 1.16 !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-excerpt {
            display: none !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-meta {
            font-size: 0.78rem !important;
            line-height: 1.35 !important;
        }
    }

    @media (max-width: 380px) {
        body.single-review main.lunara-review-single-page,
        body.single-review .lunara-review-single-page {
            padding-left: 14px !important;
            padding-right: 14px !important;
        }

        body.single-review .lunara-review-single-hero {
            margin-left: -14px !important;
            margin-right: -14px !important;
            width: calc(100% + 28px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            width: min(100%, 294px) !important;
            max-width: 294px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-link {
            grid-template-columns: 86px minmax(0, 1fr) !important;
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap {
            width: 86px !important;
            min-width: 86px !important;
            height: 128px !important;
            max-height: 128px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_mobile_editorial_repair_css', 1004 );

/**
 * Single Review Pair It With programming polish.
 */
function lunara_output_review_pair_it_with_polish_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-pair-it-with-polish-css">
    body.single-review .lunara-review-single-debrief--pairings {
        box-sizing: border-box !important;
        margin: clamp(24px, 4vw, 42px) auto 0 !important;
        max-width: min(100%, 980px) !important;
        overflow: hidden !important;
        padding: clamp(18px, 2.6vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 15% 0%, rgba(224, 196, 129, 0.13), transparent 34%),
            linear-gradient(135deg, rgba(15, 32, 49, 0.98), rgba(6, 16, 27, 0.98) 58%, rgba(13, 28, 44, 0.96)) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-block--pairings {
        display: grid !important;
        gap: clamp(14px, 2vw, 20px) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairings-head {
        display: flex !important;
        align-items: end !important;
        justify-content: space-between !important;
        gap: 16px !important;
        margin: 0 !important;
        padding: 0 0 14px !important;
        border-bottom: 1px solid rgba(224, 196, 129, 0.2) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-kicker--pairings {
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.96) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.16rem, 2.1vw, 1.55rem) !important;
        font-weight: 800 !important;
        letter-spacing: 0.03em !important;
        line-height: 1.1 !important;
        text-transform: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
        display: grid !important;
        align-items: start !important;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 252px), 1fr)) !important;
        gap: clamp(12px, 1.7vw, 16px) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-row {
        display: grid !important;
        align-content: start !important;
        gap: 12px !important;
        min-width: 0 !important;
        min-height: 0 !important;
        height: auto !important;
        padding: clamp(13px, 1.5vw, 16px) !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.022)),
            rgba(7, 18, 30, 0.84) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.035) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-type {
        display: inline-flex !important;
        width: fit-content !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 6px 9px 5px !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 999px !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-value {
        display: block !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
        display: grid !important;
        grid-template-columns: minmax(72px, 96px) minmax(0, 1fr) !important;
        align-items: start !important;
        gap: clamp(11px, 1.4vw, 15px) !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb-wrap {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb {
        display: block !important;
        width: 100% !important;
        aspect-ratio: 2 / 3 !important;
        object-fit: cover !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 10px !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing-text {
        display: grid !important;
        align-content: start !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-line1 {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 7px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-pair-title {
        min-width: 0 !important;
        max-width: 100% !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(0.98rem, 1.1vw, 1.06rem) !important;
        font-weight: 800 !important;
        line-height: 1.16 !important;
        overflow-wrap: anywhere !important;
        text-decoration: none !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
        color: rgba(244, 239, 227, 0.78) !important;
        font-size: 0.91rem !important;
        line-height: 1.5 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-chip,
    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-pill {
        flex: 0 0 auto !important;
        max-width: 100% !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger {
        display: inline-flex !important;
        flex: 0 1 auto !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 6px !important;
        max-width: 100% !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-pill {
        min-height: 0 !important;
        padding: 5px 8px !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.08) !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-size: 0.66rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.1em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-counts {
        display: inline-block !important;
        color: rgba(244, 239, 227, 0.66) !important;
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        white-space: normal !important;
    }

    @media (min-width: 1120px) {
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: 92px minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 680px) {
        body.single-review .lunara-review-single-debrief--pairings {
            margin-top: 26px !important;
            padding: 14px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairings-head {
            align-items: start !important;
            padding-bottom: 12px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-row {
            gap: 10px !important;
            padding: 12px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: 66px minmax(0, 1fr) !important;
            gap: 11px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-type {
            font-size: 0.62rem !important;
            letter-spacing: 0.1em !important;
            padding: 5px 8px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-pair-title {
            font-size: 0.96rem !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
            font-size: 0.86rem !important;
            line-height: 1.44 !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-counts {
            flex-basis: 100% !important;
            font-size: 0.68rem !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_pair_it_with_polish_css', 1005 );

/**
 * Pair It With — uniform cinematic cards (the modern renderer).
 *
 * Self-contained, scoped to `.lunara-pair-cards`, so it is inert wherever the
 * cards do not appear. Loaded on single reviews and on any singular post/page
 * that embeds the [lunara_pair_it_with] shortcode.
 */
function lunara_output_pair_it_with_cards_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    // The homepage Pairing Desk showcase reuses the module verbatim, and the
    // reviews index closes with the same desk — without this CSS the cards
    // render unstyled there (mismatched sizes, stacked at natural heights).
    $should_load = is_singular( 'review' ) || is_front_page() || is_post_type_archive( 'review' );
    if ( ! $should_load && is_singular() ) {
        $current = get_post();
        if ( $current instanceof WP_Post && has_shortcode( (string) $current->post_content, 'lunara_pair_it_with' ) ) {
            $should_load = true;
        }
    }

    if ( ! $should_load ) {
        return;
    }
    ?>
    <style id="lunara-pair-it-with-vars">
    :root { --lunara-pair-aperture-mark-image: <?php echo function_exists( 'lunara_pair_aperture_mark_css_url' ) ? lunara_pair_aperture_mark_css_url() : 'none'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>; }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_pair_it_with_cards_css', 1006 );

/**
 * On-brand newsletter signup (posts to Mailchimp).
 *
 * Renders a premium Lunara-styled email capture that submits directly to the
 * site owner's Mailchimp audience via its embedded-form action URL -- no API
 * key, no plugin. Stays completely hidden until the campaign is enabled AND a
 * Mailchimp form URL is set in the Customizer, so it is safe to ship inert.
 *
 * @return string Form markup, or '' when not configured.
 */
if ( ! function_exists( 'lunara_render_newsletter_signup' ) ) {
    function lunara_render_newsletter_signup() {
        if ( ! (bool) get_theme_mod( 'lunara_newsletter_enabled', false ) ) {
            return '';
        }

        $action = trim( (string) get_theme_mod( 'lunara_mailchimp_action_url', '' ) );
        if ( '' === $action || ! wp_http_validate_url( $action ) ) {
            return '';
        }

        $heading = trim( (string) get_theme_mod( 'lunara_newsletter_heading', '' ) );
        if ( '' === $heading ) {
            $heading = __( 'Get Lunara in your inbox', 'lunara-film' );
        }
        $blurb = trim( (string) get_theme_mod( 'lunara_newsletter_blurb', '' ) );
        if ( '' === $blurb ) {
            $blurb = __( 'New reviews and Journal dispatches, straight from the Lunara desk. No spam — unsubscribe anytime.', 'lunara-film' );
        }

        // Mailchimp bot-trap field name is b_<u>_<id>, derived from the form
        // action URL's query string. Must be present and left empty by humans.
        $honeypot = '';
        $query    = (string) wp_parse_url( $action, PHP_URL_QUERY );
        if ( '' !== $query ) {
            parse_str( $query, $params );
            $u  = isset( $params['u'] ) ? preg_replace( '/[^a-z0-9]/i', '', (string) $params['u'] ) : '';
            $id = isset( $params['id'] ) ? preg_replace( '/[^a-z0-9]/i', '', (string) $params['id'] ) : '';
            if ( '' !== $u && '' !== $id ) {
                $honeypot = 'b_' . $u . '_' . $id;
            }
        }

        ob_start();
        ?>
        <section class="lunara-newsletter" aria-label="<?php esc_attr_e( 'Newsletter signup', 'lunara-film' ); ?>">
            <style>
            .lunara-newsletter{width:min(100%,1080px);margin:clamp(40px,6vw,86px) auto;padding:clamp(28px,4vw,52px);box-sizing:border-box;border:1px solid rgba(201,169,97,.28);border-radius:20px;background:radial-gradient(circle at 88% 0%,rgba(201,169,97,.12),transparent 42%),linear-gradient(180deg,rgba(15,29,46,.92),rgba(8,16,27,.96));box-shadow:0 30px 70px rgba(0,0,0,.34);}
            .lunara-newsletter-inner{display:grid;gap:clamp(14px,2vw,20px);max-width:640px;margin-inline:auto;text-align:center;}
            .lunara-newsletter-kicker{margin:0;color:var(--lunara-gold-light,#e0c481);font-size:.74rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;}
            .lunara-newsletter-heading{margin:0;color:var(--lunara-gold,#c9a961);font-size:clamp(1.5rem,3vw,2.1rem);line-height:1.12;}
            .lunara-newsletter-blurb{margin:0 auto;max-width:52ch;color:rgba(223,228,234,.86);font-size:clamp(.95rem,1vw,1.05rem);line-height:1.6;}
            .lunara-newsletter-form{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:4px;}
            .lunara-newsletter-input{flex:1 1 280px;max-width:360px;min-height:50px;padding:12px 18px;box-sizing:border-box;border:1px solid rgba(201,169,97,.34);border-radius:999px;background:rgba(5,11,18,.6);color:var(--lunara-text,#fafbfc);font-size:1rem;}
            .lunara-newsletter-input::placeholder{color:rgba(223,228,234,.5);}
            .lunara-newsletter-input:focus{outline:none;border-color:rgba(224,196,129,.8);box-shadow:0 0 0 3px rgba(201,169,97,.18);}
            .lunara-newsletter-submit{min-height:50px;padding:12px 30px;border:none;border-radius:999px;background:linear-gradient(135deg,#e0c481,#c9a961);color:#0a1525;font-family:var(--lunara-label-font,"Arial Narrow",sans-serif);font-size:.86rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;cursor:pointer;transition:transform .16s ease,box-shadow .16s ease;}
            .lunara-newsletter-submit:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(201,169,97,.32);}
            .lunara-newsletter-fineprint{margin:0;color:rgba(223,228,234,.5);font-size:.76rem;}
            @media(max-width:540px){.lunara-newsletter-form{flex-direction:column;align-items:stretch;}.lunara-newsletter-input{max-width:none;}}
            </style>
            <div class="lunara-newsletter-inner">
                <p class="lunara-newsletter-kicker"><?php esc_html_e( 'The Lunara Dispatch', 'lunara-film' ); ?></p>
                <h2 class="lunara-newsletter-heading"><?php echo esc_html( $heading ); ?></h2>
                <p class="lunara-newsletter-blurb"><?php echo esc_html( $blurb ); ?></p>
                <form class="lunara-newsletter-form" action="<?php echo esc_url( $action ); ?>" method="post" target="_blank" rel="noopener">
                    <label class="screen-reader-text" for="lunara-mce-EMAIL"><?php esc_html_e( 'Email address', 'lunara-film' ); ?></label>
                    <input type="email" name="EMAIL" id="lunara-mce-EMAIL" class="lunara-newsletter-input" placeholder="<?php esc_attr_e( 'you@email.com', 'lunara-film' ); ?>" required>
                    <?php if ( '' !== $honeypot ) : ?>
                        <div aria-hidden="true" style="position:absolute;left:-5000px;"><input type="text" name="<?php echo esc_attr( $honeypot ); ?>" tabindex="-1" value="" autocomplete="off"></div>
                    <?php endif; ?>
                    <button type="submit" class="lunara-newsletter-submit"><?php esc_html_e( 'Subscribe', 'lunara-film' ); ?></button>
                </form>
                <p class="lunara-newsletter-fineprint"><?php esc_html_e( 'No spam. Unsubscribe anytime.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}

/**
 * Single Review spoiler companion bridge.
 */
function lunara_output_review_spoiler_bridge_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-spoiler-bridge-css">
    body.single-review .lunara-spoiler-review-bridge {
        box-sizing: border-box !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: clamp(16px, 2.4vw, 28px) !important;
        margin: clamp(28px, 4vw, 48px) auto clamp(10px, 2vw, 18px) !important;
        padding: clamp(18px, 2.6vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 16px !important;
        background:
            linear-gradient(90deg, rgba(224, 196, 129, 0.12), transparent 32%),
            linear-gradient(135deg, rgba(11, 25, 40, 0.98), rgba(5, 14, 25, 0.98) 58%, rgba(15, 31, 47, 0.96)) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.045),
            0 18px 42px rgba(0, 0, 0, 0.18) !important;
    }

    body.single-review .lunara-spoiler-review-bridge-copy {
        display: grid !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-spoiler-review-bridge-kicker {
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.14em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-spoiler-review-bridge-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.18rem, 2.2vw, 1.62rem) !important;
        line-height: 1.08 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-spoiler-review-bridge-text,
    body.single-review .lunara-spoiler-review-bridge-source {
        max-width: 64ch !important;
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.74) !important;
        font-size: clamp(0.92rem, 1.2vw, 1rem) !important;
        line-height: 1.5 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-spoiler-review-bridge-source {
        color: rgba(224, 196, 129, 0.82) !important;
        font-size: 0.82rem !important;
        font-weight: 700 !important;
    }

    body.single-review .lunara-spoiler-review-bridge-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 44px !important;
        max-width: 100% !important;
        padding: 11px 18px !important;
        border: 1px solid rgba(224, 196, 129, 0.58) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.12) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        line-height: 1.1 !important;
        text-align: center !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-spoiler-review-bridge-link:hover,
    body.single-review .lunara-spoiler-review-bridge-link:focus-visible {
        border-color: rgba(244, 216, 143, 0.9) !important;
        background: rgba(224, 196, 129, 0.2) !important;
        color: #fff6dc !important;
    }

    @media (max-width: 760px) {
        body.single-review .lunara-spoiler-review-bridge {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: stretch !important;
            margin-top: 30px !important;
            padding: 16px !important;
            border-radius: 13px !important;
        }

        body.single-review .lunara-spoiler-review-bridge-link {
            width: 100% !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_spoiler_bridge_css', 1007 );

/**
 * Single Review owned share strip.
 */
function lunara_output_review_share_strip_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-share-strip-css">
    body.single-review .lunara-review-share-strip {
        box-sizing: border-box !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: clamp(14px, 2vw, 22px) !important;
        max-width: min(100%, 72ch) !important;
        margin: clamp(28px, 4vw, 46px) auto clamp(8px, 2vw, 16px) !important;
        padding: clamp(14px, 2vw, 20px) !important;
        border: 1px solid rgba(224, 196, 129, 0.24) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(90deg, rgba(224, 196, 129, 0.1), transparent 38%),
            rgba(9, 22, 36, 0.92) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.04),
            0 16px 34px rgba(0, 0, 0, 0.16) !important;
    }

    body.single-review .lunara-review-share-strip-copy {
        display: grid !important;
        gap: 6px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-share-strip-kicker,
    body.single-review .lunara-review-share-strip-title,
    body.single-review .lunara-review-share-status {
        margin: 0 !important;
    }

    body.single-review .lunara-review-share-strip-kicker {
        color: rgba(224, 196, 129, 0.88) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.16em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-share-strip-title {
        color: rgba(248, 244, 234, 0.96) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.04rem, 1.35vw, 1.18rem) !important;
        font-weight: 800 !important;
        line-height: 1.15 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-share-strip-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: flex-end !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-share-link {
        appearance: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 34px !important;
        max-width: 100% !important;
        padding: 8px 11px !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.075) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        cursor: pointer !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.68rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.09em !important;
        line-height: 1 !important;
        text-align: center !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-share-link:hover,
    body.single-review .lunara-review-share-link:focus-visible {
        border-color: rgba(244, 216, 143, 0.85) !important;
        background: rgba(224, 196, 129, 0.16) !important;
        color: #fff6dc !important;
    }

    body.single-review .lunara-review-share-copy.is-copied {
        border-color: rgba(160, 210, 172, 0.8) !important;
        color: rgba(198, 238, 206, 0.98) !important;
    }

    body.single-review .lunara-review-share-status {
        grid-column: 1 / -1 !important;
        min-height: 1em !important;
        color: rgba(244, 239, 227, 0.66) !important;
        font-size: 0.72rem !important;
        line-height: 1.2 !important;
    }

    body.single-review .sharedaddy.sd-sharing-enabled {
        display: none !important;
    }

    @media (max-width: 760px) {
        body.single-review .lunara-review-share-strip {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: stretch !important;
            padding: 14px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-share-strip-actions {
            justify-content: flex-start !important;
        }

        body.single-review .lunara-review-share-link {
            flex: 1 1 calc(50% - 8px) !important;
            min-width: 118px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_share_strip_css', 1007 );

/**
 * Single Review owned share strip behavior.
 */
function lunara_output_review_share_strip_script() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <script id="lunara-review-share-strip-js">
    (function() {
        var buttons = document.querySelectorAll('[data-lunara-copy-share]');
        if (!buttons.length) {
            return;
        }

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var url = button.getAttribute('data-share-url') || window.location.href;
                var strip = button.closest('.lunara-review-share-strip');
                var status = strip ? strip.querySelector('.lunara-review-share-status') : null;
                var setStatus = function(message) {
                    if (status) {
                        status.textContent = message;
                    }
                };
                var markCopied = function() {
                    button.classList.add('is-copied');
                    button.textContent = 'Copied';
                    setStatus('Link copied.');
                    window.setTimeout(function() {
                        button.classList.remove('is-copied');
                        button.textContent = 'Copy Link';
                        setStatus('');
                    }, 1800);
                };
                var fallbackCopy = function() {
                    var input = document.createElement('textarea');
                    input.value = url;
                    input.setAttribute('readonly', 'readonly');
                    input.style.position = 'fixed';
                    input.style.left = '-9999px';
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        markCopied();
                    } catch (error) {
                        setStatus('Copy failed.');
                    }
                    document.body.removeChild(input);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(markCopied).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            });
        });
    }());
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_review_share_strip_script', 100 );

/**
 * Single Review lower retention shelf polish.
 */
function lunara_output_review_related_retention_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-related-retention-css">
    body.single-review .lunara-review-related--retention {
        box-sizing: border-box !important;
        display: grid !important;
        gap: clamp(16px, 2.2vw, 24px) !important;
        width: min(1120px, calc(100vw - 64px)) !important;
        max-width: 100% !important;
        margin: clamp(34px, 5vw, 58px) auto 0 !important;
        padding: clamp(18px, 2.8vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 88% 10%, rgba(224, 196, 129, 0.12), transparent 31%),
            linear-gradient(135deg, rgba(11, 26, 42, 0.98), rgba(5, 15, 26, 0.99) 64%, rgba(13, 29, 45, 0.96)) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.035) !important;
        overflow: hidden !important;
    }

    body.single-review .lunara-review-related--retention::before {
        display: none !important;
    }

    body.single-review .lunara-review-related--retention .lunara-home-section-head {
        display: flex !important;
        align-items: end !important;
        justify-content: space-between !important;
        gap: 18px !important;
        margin: 0 !important;
        padding: 0 0 14px !important;
        border-bottom: 1px solid rgba(224, 196, 129, 0.18) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-home-section-kicker {
        margin: 0 0 6px !important;
        color: rgba(224, 196, 129, 0.92) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.14em !important;
        line-height: 1.15 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-related--retention .lunara-section-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(1.35rem, 2.4vw, 2rem) !important;
        line-height: 1.04 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-review-related--retention .lunara-section-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 36px !important;
        padding: 9px 13px !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 999px !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-size: 0.74rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.1em !important;
        line-height: 1.1 !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-related-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: clamp(12px, 1.7vw, 18px) !important;
        margin: 0 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-card {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.052), rgba(255, 255, 255, 0.018)),
            rgba(7, 18, 30, 0.84) !important;
        box-shadow: none !important;
        overflow: hidden !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-link {
        display: grid !important;
        grid-template-columns: minmax(110px, 136px) minmax(0, 1fr) !important;
        align-items: stretch !important;
        min-height: 188px !important;
        color: inherit !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
        width: 100% !important;
        min-width: 0 !important;
        height: 100% !important;
        min-height: 188px !important;
        max-height: none !important;
        aspect-ratio: auto !important;
        border-radius: 0 !important;
        background: rgba(255, 255, 255, 0.035) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-poster,
    body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap img {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-copy,
    body.single-review .lunara-review-related--retention .lunara-review-grid-card > .lunara-review-grid-copy {
        display: grid !important;
        align-content: center !important;
        gap: 8px !important;
        min-width: 0 !important;
        padding: clamp(14px, 1.8vw, 20px) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-kicker {
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.86) !important;
        font-size: 0.66rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        line-height: 1.1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(1.02rem, 1.35vw, 1.24rem) !important;
        line-height: 1.12 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-excerpt {
        display: -webkit-box !important;
        margin: 0 !important;
        max-width: 48ch !important;
        overflow: hidden !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: 3 !important;
        color: rgba(244, 239, 227, 0.76) !important;
        font-size: 0.88rem !important;
        line-height: 1.45 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-updated,
    body.single-review .lunara-review-related--retention .lunara-review-grid-meta {
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.62) !important;
        font-size: 0.78rem !important;
        line-height: 1.35 !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-footer,
    body.single-review .lunara-review-related--retention .lunara-review-grid-ledger {
        display: none !important;
    }

    @media (max-width: 820px) {
        body.single-review .lunara-review-related--retention {
            width: min(100%, calc(100vw - 36px)) !important;
            margin-top: 30px !important;
            padding: 15px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-home-section-head {
            align-items: start !important;
            gap: 12px !important;
            padding-bottom: 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-related-grid {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-link {
            grid-template-columns: 88px minmax(0, 1fr) !important;
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-copy,
        body.single-review .lunara-review-related--retention .lunara-review-grid-card > .lunara-review-grid-copy {
            align-content: center !important;
            gap: 6px !important;
            padding: 11px 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-title {
            font-size: 0.98rem !important;
            line-height: 1.15 !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-excerpt {
            display: none !important;
        }
    }

    @media (max-width: 430px) {
        body.single-review .lunara-review-related--retention .lunara-home-section-head {
            display: grid !important;
        }

        body.single-review .lunara-review-related--retention .lunara-section-link {
            justify-self: start !important;
            min-height: 34px !important;
            padding: 8px 11px !important;
            font-size: 0.68rem !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-link {
            grid-template-columns: 82px minmax(0, 1fr) !important;
            min-height: 122px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
            min-height: 122px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_related_retention_css', 1006 );

/**
 * Single Review full-scroll rhythm guardrails.
 */
function lunara_output_review_full_scroll_rhythm_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-full-scroll-rhythm-css">
    body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
        grid-template-columns: minmax(240px, 340px) minmax(0, 1fr) !important;
    }

    body.single-review .lunara-review-single-debrief-media {
        align-content: center !important;
        justify-items: center !important;
        text-align: center !important;
    }

    body.single-review .lunara-review-single-debrief-poster-shell {
        position: relative !important;
        width: min(100%, 320px) !important;
        max-width: 320px !important;
        margin-inline: auto !important;
        border-color: rgba(244, 210, 126, 0.34) !important;
        background:
            radial-gradient(circle at 50% 18%, rgba(244, 210, 126, 0.13), transparent 45%),
            rgba(255, 255, 255, 0.05) !important;
        box-shadow:
            0 28px 56px rgba(0, 0, 0, 0.42),
            0 0 0 1px rgba(255, 255, 255, 0.045) inset !important;
    }

    body.single-review .lunara-review-single-debrief-poster {
        filter: brightness(1.06) contrast(1.08) saturate(1.08) !important;
        object-position: center center !important;
    }

    body.single-review .lunara-review-single-debrief-media-copy {
        width: 100% !important;
        max-width: 30ch !important;
        margin-inline: auto !important;
        text-align: center !important;
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(260px, 360px) minmax(0, 1fr) !important;
        }
    }

    @media (min-width: 521px) and (max-width: 900px) {
        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(42vw, 240px) !important;
            max-width: 240px !important;
        }
    }

    @media (max-width: 520px) {
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: 1 / -1 !important;
            grid-row: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.single-review .lunara-review-single-debrief-media {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            justify-items: center !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(72vw, 210px) !important;
            max-width: 210px !important;
            min-width: 0 !important;
            margin-inline: auto !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            width: 100% !important;
            max-width: 28ch !important;
            margin-inline: auto !important;
            text-align: center !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_full_scroll_rhythm_css', 1007 );

/**
 * Emit bounded Review Single Studio controls.
 */
function lunara_get_review_single_preview_preset_values() {
    if ( ! current_user_can( 'edit_theme_options' ) || empty( $_GET['lunara-review-preset'] ) ) {
        return array();
    }

    $preset_key = sanitize_key( wp_unslash( $_GET['lunara-review-preset'] ) );
    if ( '' === $preset_key || ! function_exists( 'lunara_control_desk_review_single_preset_specs' ) ) {
        return array();
    }

    $presets = lunara_control_desk_review_single_preset_specs();
    if ( ! isset( $presets[ $preset_key ]['values'] ) || ! is_array( $presets[ $preset_key ]['values'] ) ) {
        return array();
    }

    return $presets[ $preset_key ]['values'];
}

function lunara_get_review_single_studio_select_value( $preview_values, $key, $default, $allowed ) {
    if ( isset( $preview_values[ $key ] ) ) {
        $value = sanitize_key( (string) $preview_values[ $key ] );
        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }
    }

    return lunara_home_select_setting( $key, $default, $allowed );
}

function lunara_get_review_single_studio_number_value( $preview_values, $key, $default, $min, $max ) {
    if ( isset( $preview_values[ $key ] ) ) {
        return max( absint( $min ), min( absint( $max ), absint( $preview_values[ $key ] ) ) );
    }

    return lunara_home_brand_number_setting( $key, $default, $min, $max );
}

function lunara_output_review_single_studio_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }

    $preview_values = lunara_get_review_single_preview_preset_values();

    $density = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_density',
        'editorial',
        array( 'compact', 'editorial', 'feature' )
    );
    $hero_scale = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_hero_scale',
        'standard',
        array( 'standard', 'poster-forward', 'wide-forward' )
    );
    $rail_mode = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_rail_mode',
        'balanced',
        array( 'balanced', 'minimal', 'metadata-forward' )
    );
    $debrief_prominence = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_debrief_prominence',
        'standard',
        array( 'standard', 'poster-forward', 'signature-forward' )
    );
    $pairing_density = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_pairing_density',
        'editorial',
        array( 'compact', 'editorial', 'showcase' )
    );
    $spoiler_treatment = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_spoiler_treatment',
        'standard',
        array( 'standard', 'shield-forward', 'high-contrast' )
    );
    $trailer_prominence = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_single_trailer_prominence',
        'standard',
        array( 'standard', 'centered', 'feature' )
    );

    $section_gap          = lunara_get_review_single_studio_number_value( $preview_values, 'lunara_review_single_section_gap', 48, 24, 96 );
    $debrief_poster_width = lunara_get_review_single_studio_number_value( $preview_values, 'lunara_review_single_debrief_poster_width', 320, 220, 420 );
    $related_count        = lunara_get_review_single_studio_number_value( $preview_values, 'lunara_review_related_count', 4, 2, 6 );

    $body_gap_map = array(
        'compact'   => 28,
        'editorial' => 40,
        'feature'   => 56,
    );
    $hero_max_map = array(
        'standard'       => 980,
        'poster-forward' => 760,
        'wide-forward'   => 1180,
    );
    $rail_width_map = array(
        'minimal'          => 240,
        'balanced'         => 286,
        'metadata-forward' => 334,
    );
    $pairing_gap_map = array(
        'compact'   => 14,
        'editorial' => 20,
        'showcase'  => 28,
    );
    $related_card_min_map = array(
        'compact'   => 160,
        'editorial' => 188,
        'feature'   => 220,
    );
    $related_excerpt_clamp_map = array(
        'compact'   => 2,
        'editorial' => 3,
        'feature'   => 4,
    );

    $body_gap              = $body_gap_map[ $density ];
    $hero_max              = $hero_max_map[ $hero_scale ];
    $rail_width            = $rail_width_map[ $rail_mode ];
    $pairing_gap           = $pairing_gap_map[ $pairing_density ];
    $related_card_min      = $related_card_min_map[ $density ];
    $related_excerpt_clamp = $related_excerpt_clamp_map[ $density ];

    if ( 'signature-forward' === $debrief_prominence ) {
        $debrief_poster_width = min( 420, $debrief_poster_width + 36 );
    } elseif ( 'poster-forward' === $debrief_prominence ) {
        $debrief_poster_width = min( 420, $debrief_poster_width + 18 );
    }

    if ( $related_count <= 3 ) {
        $related_card_min = max( $related_card_min, 212 );
    }
    ?>
    <style id="lunara-review-single-studio-css">
    body.single-review .lunara-review-single-page {
        --lunara-review-single-section-gap: <?php echo absint( $section_gap ); ?>px;
        --lunara-review-single-body-gap: <?php echo absint( $body_gap ); ?>px;
        --lunara-review-single-hero-max: <?php echo absint( $hero_max ); ?>px;
        --lunara-review-single-rail-width: <?php echo absint( $rail_width ); ?>px;
        --lunara-review-single-debrief-poster-width: <?php echo absint( $debrief_poster_width ); ?>px;
        --lunara-review-single-pairing-gap: <?php echo absint( $pairing_gap ); ?>px;
        --lunara-review-single-related-card-min: <?php echo absint( $related_card_min ); ?>px;
        --lunara-review-single-related-excerpt-clamp: <?php echo absint( $related_excerpt_clamp ); ?>;
    }

    body.single-review .lunara-review-single-page {
        overflow-x: clip !important;
    }

    body.single-review .lunara-review-single-hero {
        margin-bottom: var(--lunara-review-single-section-gap) !important;
    }

    body.single-review .lunara-review-single-body {
        margin-top: 0 !important;
        margin-bottom: var(--lunara-review-single-section-gap) !important;
    }

    body.single-review .lunara-review-single-body-grid {
        gap: var(--lunara-review-single-body-gap) !important;
        grid-template-columns: minmax(0, 1fr) minmax(220px, var(--lunara-review-single-rail-width)) !important;
    }

    body.single-review .lunara-review-single-content {
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-rail {
        width: min(100%, var(--lunara-review-single-rail-width)) !important;
        max-width: var(--lunara-review-single-rail-width) !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-rail-sticky {
        gap: clamp(12px, 1.4vw, 18px) !important;
    }

    body.single-review .lunara-review-single-cinematic-hero {
        max-width: var(--lunara-review-single-hero-max) !important;
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual,
    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-slot,
    body.single-review .lunara-review-single-cinematic-hero img {
        max-width: var(--lunara-review-single-hero-max) !important;
    }

    body.single-review .lunara-review-single-debrief-section {
        margin-top: var(--lunara-review-single-section-gap) !important;
        margin-bottom: var(--lunara-review-single-section-gap) !important;
    }

    body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
        grid-template-columns: minmax(220px, var(--lunara-review-single-debrief-poster-width)) minmax(0, 1fr) !important;
        gap: clamp(18px, 2.4vw, 32px) !important;
    }

    body.single-review .lunara-review-single-debrief-poster-shell {
        width: min(100%, var(--lunara-review-single-debrief-poster-width)) !important;
        max-width: var(--lunara-review-single-debrief-poster-width) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings {
        margin-top: var(--lunara-review-single-pairing-gap) !important;
    }

    body.single-review .lunara-review-related {
        margin-top: var(--lunara-review-single-section-gap) !important;
    }

    body.single-review .lunara-review-related .lunara-review-related-grid {
        grid-template-columns: repeat(auto-fit, minmax(var(--lunara-review-single-related-card-min), 1fr)) !important;
    }

    body.single-review .lunara-review-related .lunara-review-grid-excerpt {
        -webkit-line-clamp: var(--lunara-review-single-related-excerpt-clamp) !important;
        line-clamp: var(--lunara-review-single-related-excerpt-clamp) !important;
    }

    <?php if ( 'metadata-forward' === $rail_mode ) : ?>
    body.single-review .lunara-review-single-details,
    body.single-review .lunara-review-single-rail .lunara-journal-rail-card {
        padding: clamp(18px, 2vw, 24px) !important;
        border-color: rgba(224, 196, 129, 0.28) !important;
        background: linear-gradient(180deg, rgba(20, 35, 49, 0.94), rgba(7, 17, 29, 0.92)) !important;
    }
    <?php elseif ( 'minimal' === $rail_mode ) : ?>
    body.single-review .lunara-review-single-rail .lunara-journal-rail-card {
        padding: 14px !important;
    }
    <?php endif; ?>

    <?php if ( 'signature-forward' === $debrief_prominence ) : ?>
    body.single-review .lunara-review-single-debrief-section {
        padding-block: clamp(20px, 3vw, 42px) !important;
    }

    body.single-review .lunara-review-single-debrief-wrap {
        border-color: rgba(244, 210, 126, 0.34) !important;
        box-shadow: 0 34px 70px rgba(0, 0, 0, 0.34) !important;
    }
    <?php endif; ?>

    <?php if ( 'showcase' === $pairing_density ) : ?>
    body.single-review .lunara-review-single-debrief--pairings {
        padding: clamp(20px, 2.8vw, 34px) !important;
        border-color: rgba(224, 196, 129, 0.3) !important;
    }
    <?php elseif ( 'compact' === $pairing_density ) : ?>
    body.single-review .lunara-review-single-debrief--pairings {
        padding: clamp(14px, 1.8vw, 22px) !important;
    }
    <?php endif; ?>

    <?php if ( 'shield-forward' === $spoiler_treatment || 'high-contrast' === $spoiler_treatment ) : ?>
    body.single-review .lunara-full-spoiler-warning,
    body.single-review .lunara-spoiler-warning {
        margin-block: clamp(18px, 2.4vw, 30px) !important;
        border-color: rgba(244, 210, 126, <?php echo 'high-contrast' === $spoiler_treatment ? '0.48' : '0.32'; ?>) !important;
        background:
            linear-gradient(135deg, rgba(244, 210, 126, <?php echo 'high-contrast' === $spoiler_treatment ? '0.18' : '0.1'; ?>), rgba(9, 19, 31, 0.92)),
            rgba(9, 19, 31, 0.94) !important;
    }
    <?php endif; ?>

    <?php if ( 'centered' === $trailer_prominence || 'feature' === $trailer_prominence ) : ?>
    body.single-review .lunara-trailer-module,
    body.single-review .lunara-review-trailer,
    body.single-review .lunara-trailer-embed {
        width: min(100%, <?php echo 'feature' === $trailer_prominence ? '880px' : '720px'; ?>) !important;
        margin: clamp(20px, 3vw, 38px) auto !important;
    }

    body.single-review .lunara-trailer-module iframe,
    body.single-review .lunara-review-trailer iframe,
    body.single-review .lunara-trailer-embed iframe {
        width: 100% !important;
    }
    <?php endif; ?>

    @media (max-width: 900px) {
        body.single-review .lunara-review-single-body-grid {
            display: block !important;
        }

        body.single-review .lunara-review-single-rail {
            width: min(100%, 720px) !important;
            max-width: 720px !important;
            margin: clamp(24px, 6vw, 44px) auto 0 !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 520px) {
        body.single-review .lunara-review-single-page {
            --lunara-review-single-section-gap: min(<?php echo absint( $section_gap ); ?>px, 42px);
            --lunara-review-single-body-gap: min(<?php echo absint( $body_gap ); ?>px, 28px);
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(74vw, 220px) !important;
            max-width: 220px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_single_studio_css', 1008 );

function lunara_review_pair_with_image_focus_position( $focus ) {
    $map = array(
        'center-center' => 'center center',
        'center-top'    => 'center top',
        'center-bottom' => 'center bottom',
        'left-center'   => 'left center',
        'right-center'  => 'right center',
    );

    $focus = sanitize_key( (string) $focus );

    return isset( $map[ $focus ] ) ? $map[ $focus ] : $map['center-center'];
}

function lunara_output_review_pair_it_with_controls_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }

    $preview_values = lunara_get_review_single_preview_preset_values();

    $layout = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_pair_with_layout',
        'wide',
        array( 'contained', 'wide', 'feature' )
    );
    $text_depth = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_pair_with_text_depth',
        'balanced',
        array( 'tight', 'balanced', 'full' )
    );
    $mobile_stack = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_pair_with_mobile_stack',
        'editorial',
        array( 'compact', 'editorial', 'poster-led' )
    );
    $image_focus = lunara_get_review_single_studio_select_value(
        $preview_values,
        'lunara_review_pair_with_image_focus',
        'center-center',
        array( 'center-center', 'center-top', 'center-bottom', 'left-center', 'right-center' )
    );

    $columns     = lunara_get_review_single_studio_number_value( $preview_values, 'lunara_review_pair_with_columns', 1, 1, 3 );
    $thumb_width = lunara_get_review_single_studio_number_value( $preview_values, 'lunara_review_pair_with_thumb_width', 96, 64, 140 );

    $layout_width_map = array(
        'contained' => 760,
        'wide'      => 980,
        'feature'   => 1120,
    );
    $note_clamp_map = array(
        'tight'    => 2,
        'balanced' => 3,
        'full'     => 6,
    );
    $mobile_thumb_map = array(
        'compact'    => 58,
        'editorial'  => 68,
        'poster-led' => 96,
    );

    $max_width          = $layout_width_map[ $layout ];
    $note_clamp         = $note_clamp_map[ $text_depth ];
    $mobile_thumb_width = $mobile_thumb_map[ $mobile_stack ];
    $image_position     = lunara_review_pair_with_image_focus_position( $image_focus );
    ?>
    <style id="lunara-review-pair-it-with-controls-css">
    body.single-review .lunara-review-single-page {
        --lunara-review-pair-with-max-width: <?php echo absint( $max_width ); ?>px;
        --lunara-review-pair-with-columns: <?php echo absint( $columns ); ?>;
        --lunara-review-pair-with-thumb-width: <?php echo absint( $thumb_width ); ?>px;
        --lunara-review-pair-with-note-clamp: <?php echo absint( $note_clamp ); ?>;
        --lunara-review-pair-with-mobile-thumb-width: <?php echo absint( $mobile_thumb_width ); ?>px;
        --lunara-review-pair-with-image-focus: <?php echo esc_html( $image_position ); ?>;
    }

    body.single-review .lunara-review-single-debrief--pairings {
        width: min(100%, var(--lunara-review-pair-with-max-width)) !important;
        max-width: var(--lunara-review-pair-with-max-width) !important;
        margin-left: auto !important;
        margin-right: auto !important;
        box-sizing: border-box !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
        grid-template-columns: repeat(var(--lunara-review-pair-with-columns), minmax(0, 1fr)) !important;
        align-items: stretch !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
        grid-template-columns: var(--lunara-review-pair-with-thumb-width) minmax(0, 1fr) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb-wrap {
        width: var(--lunara-review-pair-with-thumb-width) !important;
        max-width: var(--lunara-review-pair-with-thumb-width) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb {
        width: 100% !important;
        aspect-ratio: 2 / 3 !important;
        object-fit: cover !important;
        object-position: var(--lunara-review-pair-with-image-focus) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: var(--lunara-review-pair-with-note-clamp) !important;
        line-clamp: var(--lunara-review-pair-with-note-clamp) !important;
        overflow: hidden !important;
    }

    <?php if ( 'feature' === $layout ) : ?>
    body.single-review .lunara-review-single-debrief--pairings {
        border-color: rgba(244, 210, 126, 0.34) !important;
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.28) !important;
    }
    <?php elseif ( 'wide' === $layout ) : ?>
    body.single-review .lunara-review-single-debrief--pairings {
        border-color: rgba(224, 196, 129, 0.26) !important;
    }
    <?php endif; ?>

    @media (max-width: 960px) {
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 680px) {
        body.single-review .lunara-review-single-debrief--pairings {
            width: 100% !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: var(--lunara-review-pair-with-mobile-thumb-width) minmax(0, 1fr) !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb-wrap {
            width: var(--lunara-review-pair-with-mobile-thumb-width) !important;
            max-width: var(--lunara-review-pair-with-mobile-thumb-width) !important;
        }
    }

    <?php if ( 'poster-led' === $mobile_stack ) : ?>
    @media (max-width: 520px) {
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb-wrap {
            width: min(42vw, 126px) !important;
            max-width: 126px !important;
            margin-inline: auto !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing-text {
            text-align: center !important;
            justify-items: center !important;
        }
    }
    <?php endif; ?>
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_pair_it_with_controls_css', 1009 );

/**
 * Compact Oscars portal guardrails.
 *
 * The /oscars/ front door inherits several homepage-scale components; keep this
 * page-specific layer late so the portal reads as an efficient ledger entry
 * point on mobile and desktop.
 */
function lunara_output_oscars_portal_compact_css() {
    if ( is_admin() || is_feed() || ! is_page( 'oscars' ) ) {
        return;
    }
    ?>
    <style id="lunara-oscars-portal-compact-css">
    body.lunara-oscars-portal-page .site-main {
        overflow-x: clip !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal {
        gap: clamp(34px, 4.8vw, 58px) !important;
        margin-inline: auto !important;
        max-width: min(100%, 1180px) !important;
        padding: clamp(14px, 2.4vw, 24px) clamp(18px, 3vw, 30px) clamp(48px, 6vw, 76px) !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section {
        border-radius: clamp(18px, 2.4vw, 26px) !important;
        box-sizing: border-box !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
        box-shadow: 0 22px 46px rgba(0, 0, 0, .22) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
        align-items: center !important;
        gap: clamp(20px, 3vw, 34px) !important;
        grid-template-columns: minmax(0, 1.25fr) minmax(240px, 330px) !important;
        padding: clamp(16px, 2.2vw, 24px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy {
        gap: clamp(13px, 1.6vw, 19px) !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-section-kicker,
    body.lunara-oscars-portal-page .lunara-home-section-kicker {
        font-size: .7rem !important;
        letter-spacing: .13em !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
        font-size: clamp(2.05rem, 4.4vw, 4rem) !important;
        letter-spacing: 0 !important;
        line-height: .98 !important;
        margin: 0 !important;
        max-width: 12.6ch !important;
        overflow-wrap: normal !important;
        text-wrap: balance !important;
        text-transform: none !important;
        word-break: normal !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
        color: rgba(244, 239, 227, .82) !important;
        font-size: clamp(.96rem, 1.14vw, 1.06rem) !important;
        line-height: 1.58 !important;
        margin: 0 !important;
        max-width: 66ch !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
        gap: 9px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
        border-radius: 999px !important;
        font-size: .82rem !important;
        line-height: 1.15 !important;
        min-height: 38px !important;
        padding: 10px 13px !important;
        white-space: normal !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
        gap: 10px !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat {
        border-radius: 15px !important;
        gap: 5px !important;
        min-width: 0 !important;
        padding: 12px 12px 11px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-label {
        font-size: .62rem !important;
        letter-spacing: .11em !important;
        line-height: 1.15 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-value {
        font-size: .9rem !important;
        line-height: 1.2 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-rail {
        align-items: stretch !important;
        display: grid !important;
        gap: 10px !important;
        grid-column: 1 / -1 !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        margin-top: clamp(4px, 1vw, 10px) !important;
        min-width: 0 !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card {
        background:
            linear-gradient(145deg, rgba(201, 169, 97, .11), rgba(13, 27, 42, .9)),
            rgba(9, 20, 32, .9) !important;
        border: 1px solid rgba(201, 169, 97, .36) !important;
        border-radius: 16px !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04) !important;
        color: rgba(248, 244, 234, .92) !important;
        display: grid !important;
        gap: 7px !important;
        min-height: 116px !important;
        min-width: 0 !important;
        padding: 15px !important;
        position: relative !important;
        text-decoration: none !important;
        transition: border-color .18s ease, transform .18s ease, background .18s ease !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card:hover {
        background:
            linear-gradient(145deg, rgba(201, 169, 97, .18), rgba(13, 27, 42, .94)),
            rgba(9, 20, 32, .94) !important;
        border-color: rgba(225, 197, 126, .66) !important;
        transform: translateY(-2px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-command-meta {
        display: block !important;
        font-size: .62rem !important;
        letter-spacing: .11em !important;
        line-height: 1.2 !important;
        min-width: 0 !important;
        overflow-wrap: anywhere !important;
        text-transform: uppercase !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-kicker {
        color: var(--lunara-gold, #d4af66) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card strong {
        color: #fffaf0 !important;
        display: block !important;
        font-family: var(--lunara-serif, Georgia, serif) !important;
        font-size: clamp(1rem, 1.35vw, 1.24rem) !important;
        letter-spacing: 0 !important;
        line-height: 1.08 !important;
        min-width: 0 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-meta {
        align-self: end !important;
        color: rgba(244, 239, 227, .64) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
        gap: 14px !important;
        justify-self: end !important;
        max-width: 330px !important;
        padding: 16px !important;
        width: 100% !important;
        border-radius: 22px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
        border-radius: 16px !important;
        max-height: 350px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
        gap: 7px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
        font-size: clamp(1.05rem, 1.7vw, 1.38rem) !important;
        line-height: 1.08 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
        font-size: .88rem !important;
        line-height: 1.45 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights,
    body.lunara-oscars-portal-page .lunara-oscars-portal-titles,
    body.lunara-oscars-portal-page .lunara-oscars-portal-research,
    body.lunara-oscars-portal-page .lunara-oscars-portal-winners,
    body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section,
    body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts {
        background:
            linear-gradient(135deg, rgba(201, 169, 97, .055), rgba(10, 22, 35, .96) 38%, rgba(7, 17, 29, .98)),
            rgba(8, 18, 30, .94) !important;
        border: 1px solid rgba(201, 169, 97, .16) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .035) !important;
        padding: clamp(20px, 3vw, 32px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-titles .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-research .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-winners .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts .lunara-home-section-header {
        border-bottom: 1px solid rgba(201, 169, 97, .14) !important;
        margin-bottom: 18px !important;
        padding-bottom: 14px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
        gap: 12px !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
        align-items: center !important;
        background: rgba(9, 20, 32, .84) !important;
        border: 1px solid rgba(201, 169, 97, .18) !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        display: grid !important;
        gap: 12px !important;
        grid-template-columns: minmax(58px, 76px) minmax(0, 1fr) !important;
        min-height: 96px !important;
        min-width: 0 !important;
        overflow: hidden !important;
        padding: 12px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:hover {
        border-color: rgba(225, 197, 126, .42) !important;
        box-shadow: none !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
        aspect-ratio: 1 / 1 !important;
        border-radius: 12px !important;
        display: block !important;
        max-width: 76px !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster img {
        height: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-copy {
        display: grid !important;
        gap: 5px !important;
        min-width: 0 !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-category {
        font-size: .58rem !important;
        letter-spacing: .12em !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-name {
        font-size: .94rem !important;
        line-height: 1.14 !important;
        margin: 0 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-film {
        font-size: .76rem !important;
        line-height: 1.25 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
        align-items: center !important;
        background: rgba(9, 20, 32, .86) !important;
        display: grid !important;
        gap: 12px !important;
        grid-template-columns: minmax(52px, 72px) minmax(0, 1fr) !important;
        min-height: 100px !important;
        overflow: hidden !important;
        padding: 10px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
        aspect-ratio: 2 / 3 !important;
        border-radius: 12px !important;
        min-height: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
        border: 1px solid rgba(201, 169, 97, .14) !important;
        border-radius: 18px 18px 0 0 !important;
        gap: 0 !important;
        overflow: hidden !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        border: 0 !important;
        border-radius: 0 !important;
        border-right: 1px solid rgba(201, 169, 97, .13) !important;
        min-height: 132px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card:last-child {
        border-right: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-shell {
        border: 1px solid rgba(201, 169, 97, .24) !important;
        border-radius: 0 0 18px 18px !important;
        border-top: 0 !important;
        overflow: hidden !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-header,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-section,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-metric-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-card {
        border-color: rgba(201, 169, 97, .18) !important;
    }

    body.lunara-oscars-portal-page .lunara-ledger-carousel-wrap {
        border: 1px solid rgba(201, 169, 97, .14) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        padding: 12px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
        gap: 12px !important;
        grid-auto-columns: minmax(260px, 31%) !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-header {
        align-items: end !important;
        gap: 18px !important;
        margin-bottom: 18px !important;
        padding-left: 4px !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-title {
        font-size: clamp(1.55rem, 2.5vw, 2.25rem) !important;
        line-height: 1.04 !important;
        margin: 0 !important;
        max-width: 16ch !important;
        padding-left: 2px !important;
        text-wrap: balance !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-summary {
        font-size: .96rem !important;
        line-height: 1.55 !important;
        margin: 0 !important;
        max-width: 58ch !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
    body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
    body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
        gap: 16px !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
        gap: 16px !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        border-radius: 18px !important;
        gap: 10px !important;
        min-width: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        padding: 16px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop {
        min-height: 150px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
        gap: 6px !important;
        padding: 12px 13px 14px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3 {
        font-size: clamp(.96rem, 1.25vw, 1.08rem) !important;
        line-height: 1.16 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
        font-size: .86rem !important;
        line-height: 1.42 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-portal-link-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-category,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-label,
    body.lunara-oscars-portal-page .lunara-oscars-research-card-kicker {
        font-size: .64rem !important;
        letter-spacing: .11em !important;
        line-height: 1.2 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
        font-size: clamp(1.25rem, 2.1vw, 1.55rem) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
        border-radius: 14px 14px 0 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card:hover {
        transform: translateY(-2px) !important;
    }

    @media (max-width: 1120px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            grid-template-columns: minmax(0, 1fr) minmax(220px, 280px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            max-width: 13.4ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
            grid-auto-columns: minmax(240px, 44%) !important;
        }
    }

    @media (max-width: 820px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            gap: 34px !important;
            padding: 12px 14px 52px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            gap: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            padding: 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            font-size: clamp(2rem, 7vw, 3rem) !important;
            line-height: 1.02 !important;
            max-width: 13ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            font-size: .96rem !important;
            line-height: 1.5 !important;
            max-width: 64ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
            min-height: 42px !important;
            padding: 9px 10px !important;
            text-align: center !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-rail {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            display: grid !important;
            gap: 12px !important;
            grid-template-columns: minmax(120px, 31%) minmax(0, 1fr) !important;
            justify-self: stretch !important;
            max-width: none !important;
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            align-self: start !important;
            max-height: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
            align-content: center !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
            font-size: clamp(1.05rem, 3.6vw, 1.3rem) !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-header {
            align-items: start !important;
            display: grid !important;
            gap: 10px !important;
            margin-bottom: 14px !important;
            padding-left: 6px !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            font-size: clamp(1.45rem, 5.4vw, 2.05rem) !important;
            max-width: 18ch !important;
            padding-left: 2px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            gap: 12px !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card {
            padding: 13px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop {
            min-height: 132px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights,
        body.lunara-oscars-portal-page .lunara-oscars-portal-titles,
        body.lunara-oscars-portal-page .lunara-oscars-portal-research,
        body.lunara-oscars-portal-page .lunara-oscars-portal-winners,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts {
            padding: 16px 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            gap: 10px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            border-radius: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card {
            border-right: 0 !important;
            border-bottom: 1px solid rgba(201, 169, 97, .13) !important;
            min-height: 98px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card:last-child {
            border-bottom: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell {
            border-radius: 16px !important;
            border-top: 1px solid rgba(201, 169, 97, .24) !important;
            margin-top: 12px !important;
        }
    }

    @media (max-width: 520px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            padding-inline: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            font-size: clamp(1.68rem, 8.1vw, 2.08rem) !important;
            line-height: 1.05 !important;
            max-width: 11.8ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            font-size: .9rem !important;
            line-height: 1.44 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
            gap: 8px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
            font-size: .78rem !important;
            min-height: 39px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
            gap: 8px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat {
            border-radius: 12px !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-value {
            font-size: .82rem !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-rail {
            gap: 8px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-card {
            align-items: center !important;
            gap: 5px !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            min-height: 74px !important;
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-kicker,
        body.lunara-oscars-portal-page .lunara-oscars-command-meta {
            font-size: .58rem !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-card strong {
            font-size: 1rem !important;
            grid-column: 1 / -1 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            grid-template-columns: minmax(104px, 36vw) minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
            display: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3 {
            font-size: .9rem !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            max-width: 11.8ch !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
            grid-template-columns: minmax(58px, 68px) minmax(0, 1fr) !important;
            min-height: 88px !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
            max-width: 68px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
            grid-template-columns: minmax(48px, 64px) minmax(0, 1fr) !important;
            min-height: 86px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
            grid-auto-columns: minmax(238px, 88%) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
            font-size: .78rem !important;
            line-height: 1.34 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
            padding: 10px !important;
        }
    }

    @media (max-width: 900px) {
        body.lunara-oscars-portal-page,
        body.lunara-oscars-portal-page .site,
        body.lunara-oscars-portal-page .site-main {
            max-width: 100vw !important;
            overflow-x: clip !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal {
            box-sizing: border-box !important;
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 100vw !important;
            overflow-x: clip !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            box-sizing: border-box !important;
            min-height: auto !important;
            max-width: 100% !important;
            overflow: hidden !important;
            padding: clamp(16px, 4vw, 24px) !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            box-sizing: border-box !important;
            display: grid !important;
            gap: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            max-width: 100% !important;
            min-height: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
        body.lunara-oscars-portal-page .lunara-oscars-command-rail,
        body.lunara-oscars-portal-page .lunara-oscars-command-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            box-sizing: border-box !important;
            max-width: 100% !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            font-size: clamp(1.76rem, 7vw, 2.5rem) !important;
            line-height: 1.06 !important;
            max-width: 100% !important;
            overflow-wrap: normal !important;
            text-wrap: balance !important;
            word-break: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            max-width: 34rem !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            align-items: center !important;
            display: grid !important;
            gap: 10px !important;
            grid-template-columns: minmax(96px, 124px) minmax(0, 1fr) !important;
            justify-self: stretch !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            align-self: center !important;
            max-height: 186px !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
            display: grid !important;
            gap: 5px !important;
            min-width: 0 !important;
            padding: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-meta {
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker {
            font-size: .58rem !important;
            letter-spacing: .08em !important;
            line-height: 1.18 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
            font-size: clamp(.96rem, 3vw, 1.2rem) !important;
            line-height: 1.13 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
            display: none !important;
        }
    }

    @media (max-width: 520px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            max-width: 100vw !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            border-radius: 18px !important;
            max-width: 100% !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            grid-template-columns: minmax(84px, 102px) minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            max-height: 154px !important;
        }
    }

    /* Portal cinematic layer moved to assets/css/lunara-shell.css. The shell
       enqueue runs after route-specific styles, while request-specific studio
       emitters below can still make the final scoped adjustment. */
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_oscars_portal_compact_css', 1001 );

/**
 * Reveal-on-scroll for the Oscars portal sections. Sections stay fully
 * visible without JS or under reduced motion; the class that hides them is
 * only added here, right before the observer starts watching.
 */
if ( ! function_exists( 'lunara_output_oscars_portal_motion_js' ) ) {
function lunara_output_oscars_portal_motion_js() {
    if ( ! function_exists( 'lunara_is_oscars_portal_page' ) || ! lunara_is_oscars_portal_page() ) {
        return;
    }
    ?>
    <script>
    (function () {
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        var sections = document.querySelectorAll('.lunara-oscars-portal > .lunara-home-section');
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-inview');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: .06, rootMargin: '0px 0px -60px 0px' });
        sections.forEach(function (section) {
            section.classList.add('lunara-oscars-reveal');
            io.observe(section);
        });
    })();
    </script>
    <?php
}
}
add_action( 'wp_footer', 'lunara_output_oscars_portal_motion_js', 60 );

/**
 * Emit bounded Oscars Dossier Studio controls.
 */
function lunara_get_oscars_dossier_preview_preset_values() {
    if ( ! current_user_can( 'edit_theme_options' ) || empty( $_GET['lunara-oscars-preset'] ) ) {
        return array();
    }

    $preset_key = sanitize_key( wp_unslash( $_GET['lunara-oscars-preset'] ) );
    if ( '' === $preset_key || ! function_exists( 'lunara_control_desk_oscars_dossier_preset_specs' ) ) {
        return array();
    }

    $presets = lunara_control_desk_oscars_dossier_preset_specs();
    if ( ! isset( $presets[ $preset_key ]['values'] ) || ! is_array( $presets[ $preset_key ]['values'] ) ) {
        return array();
    }

    return $presets[ $preset_key ]['values'];
}

function lunara_get_oscars_dossier_studio_select_value( $preview_values, $key, $default, $allowed ) {
    if ( isset( $preview_values[ $key ] ) ) {
        $value = sanitize_key( (string) $preview_values[ $key ] );
        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }
    }

    return lunara_home_select_setting( $key, $default, $allowed );
}

function lunara_get_oscars_dossier_studio_number_value( $preview_values, $key, $default, $min, $max ) {
    if ( isset( $preview_values[ $key ] ) ) {
        return max( absint( $min ), min( absint( $max ), absint( $preview_values[ $key ] ) ) );
    }

    return lunara_home_brand_number_setting( $key, $default, $min, $max );
}

function lunara_is_oscars_dossier_surface() {
    $request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';

    if ( is_page( 'oscars' ) ) {
        return true;
    }

    return false !== strpos( $request_path, '/oscars/' );
}

function lunara_output_oscars_dossier_studio_css() {
    if ( is_admin() || is_feed() || ! lunara_is_oscars_dossier_surface() ) {
        return;
    }

    $preview_values = lunara_get_oscars_dossier_preview_preset_values();

    $density = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_dossier_density',
        'balanced',
        array( 'balanced', 'dense', 'showcase' )
    );
    $ceremony_rhythm = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_ceremony_rhythm',
        'balanced',
        array( 'balanced', 'editorial', 'ledger' )
    );
    $major_race_prominence = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_major_race_prominence',
        'standard',
        array( 'standard', 'feature', 'compact' )
    );
    $profile_scale = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_profile_scale',
        'standard',
        array( 'standard', 'cinematic', 'compact' )
    );
    $profile_media_treatment = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_profile_media_treatment',
        'poster-frame',
        array( 'poster-frame', 'cinematic-crop', 'archival-fit' )
    );
    $writeup_prominence = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_writeup_prominence',
        'inline',
        array( 'inline', 'feature', 'compact' )
    );
    $related_reviews_treatment = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_related_reviews_treatment',
        'standard-grid',
        array( 'standard-grid', 'compact-rail', 'feature-strip' )
    );
    $title_image_focus = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_title_image_focus',
        'center-center',
        array( 'center-center', 'center-top', 'center-bottom', 'left-center', 'right-center' )
    );
    $saved_preset = lunara_get_oscars_dossier_studio_select_value(
        $preview_values,
        'lunara_oscars_dossier_preset',
        'historical-dossier',
        array( 'historical-dossier', 'ceremony-feature', 'compact-ledger', 'profile-spotlight' )
    );

    $section_gap = lunara_get_oscars_dossier_studio_number_value( $preview_values, 'lunara_oscars_dossier_section_gap', 48, 24, 96 );
    $card_min    = lunara_get_oscars_dossier_studio_number_value( $preview_values, 'lunara_oscars_dossier_card_min', 280, 220, 420 );
    $profile_media_width  = lunara_get_oscars_dossier_studio_number_value( $preview_values, 'lunara_oscars_profile_media_width', 340, 220, 520 );
    $profile_media_height = lunara_get_oscars_dossier_studio_number_value( $preview_values, 'lunara_oscars_profile_media_height', 500, 320, 700 );
    $related_reviews_count = lunara_get_oscars_dossier_studio_number_value( $preview_values, 'lunara_oscars_related_reviews_count', 6, 2, 8 );

    $density_scale_map = array(
        'balanced' => '1',
        'dense'    => '.88',
        'showcase' => '1.12',
    );
    $hero_max_map      = array(
        'balanced' => '1040px',
        'dense'    => '960px',
        'showcase' => '1180px',
    );
    $profile_media_map = array(
        'standard'  => '340px',
        'cinematic' => '430px',
        'compact'   => '280px',
    );
    $profile_media_fit_map = array(
        'poster-frame'   => 'cover',
        'cinematic-crop' => 'cover',
        'archival-fit'   => 'contain',
    );
    $profile_media_aspect_map = array(
        'poster-frame'   => '2 / 3',
        'cinematic-crop' => '4 / 5',
        'archival-fit'   => '2 / 3',
    );
    $writeup_max_map   = array(
        'inline'  => '880px',
        'feature' => '1020px',
        'compact' => '760px',
    );
    $race_gap_map      = array(
        'standard' => '18px',
        'feature'  => '22px',
        'compact'  => '12px',
    );
    $related_review_min_map = array(
        'standard-grid' => '280px',
        'compact-rail'  => '220px',
        'feature-strip' => '300px',
    );
    $related_media_aspect_map = array(
        'standard-grid' => '16 / 10',
        'compact-rail'  => '3 / 4',
        'feature-strip' => '16 / 9',
    );
    $image_focus_map = array(
        'center-center' => 'center center',
        'center-top'    => 'center top',
        'center-bottom' => 'center bottom',
        'left-center'   => 'left center',
        'right-center'  => 'right center',
    );

    $density_scale     = isset( $density_scale_map[ $density ] ) ? $density_scale_map[ $density ] : '1';
    $hero_max          = isset( $hero_max_map[ $density ] ) ? $hero_max_map[ $density ] : '1040px';
    $profile_media_max = absint( $profile_media_width ) . 'px';
    $profile_media_scale_max = isset( $profile_media_map[ $profile_scale ] ) ? $profile_media_map[ $profile_scale ] : '340px';
    $profile_media_fit = isset( $profile_media_fit_map[ $profile_media_treatment ] ) ? $profile_media_fit_map[ $profile_media_treatment ] : 'cover';
    $profile_media_aspect = isset( $profile_media_aspect_map[ $profile_media_treatment ] ) ? $profile_media_aspect_map[ $profile_media_treatment ] : '2 / 3';
    $writeup_max       = isset( $writeup_max_map[ $writeup_prominence ] ) ? $writeup_max_map[ $writeup_prominence ] : '880px';
    $race_gap          = isset( $race_gap_map[ $major_race_prominence ] ) ? $race_gap_map[ $major_race_prominence ] : '18px';
    $related_review_min = isset( $related_review_min_map[ $related_reviews_treatment ] ) ? $related_review_min_map[ $related_reviews_treatment ] : '280px';
    $related_media_aspect = isset( $related_media_aspect_map[ $related_reviews_treatment ] ) ? $related_media_aspect_map[ $related_reviews_treatment ] : '16 / 10';
    $image_focus       = isset( $image_focus_map[ $title_image_focus ] ) ? $image_focus_map[ $title_image_focus ] : 'center center';
    ?>
    <style id="lunara-oscars-dossier-studio-css">
    body.aat-shell-page {
        --lunara-oscars-dossier-section-gap: <?php echo esc_html( absint( $section_gap ) ); ?>px;
        --lunara-oscars-dossier-card-min: <?php echo esc_html( absint( $card_min ) ); ?>px;
        --lunara-oscars-dossier-density-scale: <?php echo esc_html( $density_scale ); ?>;
        --lunara-oscars-dossier-hero-max: <?php echo esc_html( $hero_max ); ?>;
        --lunara-oscars-profile-media-max: <?php echo esc_html( $profile_media_max ); ?>;
        --lunara-oscars-profile-media-scale-max: <?php echo esc_html( $profile_media_scale_max ); ?>;
        --lunara-oscars-profile-media-width: <?php echo esc_html( absint( $profile_media_width ) ); ?>px;
        --lunara-oscars-profile-media-height: <?php echo esc_html( absint( $profile_media_height ) ); ?>px;
        --lunara-oscars-profile-media-fit: <?php echo esc_html( $profile_media_fit ); ?>;
        --lunara-oscars-profile-media-aspect: <?php echo esc_html( $profile_media_aspect ); ?>;
        --lunara-oscars-writeup-max: <?php echo esc_html( $writeup_max ); ?>;
        --lunara-oscars-major-race-gap: <?php echo esc_html( $race_gap ); ?>;
        --lunara-oscars-related-review-min: <?php echo esc_html( $related_review_min ); ?>;
        --lunara-oscars-related-review-media-aspect: <?php echo esc_html( $related_media_aspect ); ?>;
        --lunara-oscars-related-review-count: <?php echo esc_html( absint( $related_reviews_count ) ); ?>;
        --lunara-oscars-image-focus: <?php echo esc_html( $image_focus ); ?>;
    }

    body.aat-shell-page .aat-container,
    body.aat-shell-page .aat-hub-page,
    body.aat-shell-page .aat-entity-page {
        gap: var(--lunara-oscars-dossier-section-gap) !important;
    }

    body.aat-shell-page .aat-ceremony-dossier,
    body.aat-shell-page .aat-category-dossier,
    body.aat-shell-page .aat-profile-file {
        margin-left: auto !important;
        margin-right: auto !important;
        max-width: var(--lunara-oscars-dossier-hero-max) !important;
    }

    body.aat-shell-page .aat-hub-grid,
    body.aat-shell-page .aat-winner-circle-grid,
    body.aat-shell-page .aat-crossroads-grid,
    body.aat-shell-page .aat-profile-grid {
        gap: calc(18px * var(--lunara-oscars-dossier-density-scale)) !important;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--lunara-oscars-dossier-card-min)), 1fr)) !important;
    }

    body.aat-shell-page .aat-ceremony-major-races {
        gap: var(--lunara-oscars-major-race-gap) !important;
        margin-top: calc(var(--lunara-oscars-dossier-section-gap) * .6) !important;
    }

    body.aat-shell-page .aat-ceremony-editorial-writeup {
        margin-left: auto !important;
        margin-right: auto !important;
        max-width: var(--lunara-oscars-writeup-max) !important;
    }

    body.aat-shell-page .aat-profile-file img,
    body.aat-shell-page .aat-profile-file .aat-profile-media,
    body.aat-shell-page .aat-entity-poster,
    body.aat-shell-page .aat-title-poster {
        max-width: min(100%, var(--lunara-oscars-profile-media-max)) !important;
    }

    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap,
    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap.is-person,
    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap.is-company,
    body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap,
    body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap.is-person,
    body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap.is-company {
        aspect-ratio: var(--lunara-oscars-profile-media-aspect) !important;
        display: block !important;
        flex-basis: min(100%, var(--lunara-oscars-profile-media-width)) !important;
        max-height: var(--lunara-oscars-profile-media-height) !important;
        max-width: min(100%, var(--lunara-oscars-profile-media-width)) !important;
        overflow: hidden !important;
        width: min(100%, var(--lunara-oscars-profile-media-width)) !important;
        background: linear-gradient(180deg, rgba(8, 20, 32, .94), rgba(5, 12, 21, .98)) !important;
    }

    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap img,
    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster,
    body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-portrait,
    body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap img,
    body.aat-shell-page .aat-profile-file .aat-entity-poster,
    body.aat-shell-page .aat-profile-file .aat-entity-portrait {
        aspect-ratio: var(--lunara-oscars-profile-media-aspect) !important;
        display: block !important;
        height: 100% !important;
        max-height: var(--lunara-oscars-profile-media-height) !important;
        max-width: 100% !important;
        object-fit: var(--lunara-oscars-profile-media-fit) !important;
        object-position: var(--lunara-oscars-image-focus) !important;
        width: 100% !important;
    }

    body.aat-shell-page .aat-related-reviews-grid {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--lunara-oscars-related-review-min)), 1fr)) !important;
    }

    body.aat-shell-page .aat-related-review-media {
        aspect-ratio: var(--lunara-oscars-related-review-media-aspect) !important;
    }

    body.aat-shell-page .aat-related-review-image,
    body.aat-shell-page .aat-related-review-media img,
    body.aat-shell-page .aat-filmography-poster,
    body.aat-shell-page .aat-filmography-poster-wrap img,
    body.aat-shell-page .aat-entity-poster-wrap img,
    body.aat-shell-page .aat-entity-poster,
    body.aat-shell-page .aat-entity-portrait,
    body.aat-shell-page .aat-title-poster {
        object-position: var(--lunara-oscars-image-focus) !important;
    }

    body.aat-shell-page .aat-related-treatment-compact-rail .aat-related-reviews-grid,
    body.aat-shell-page .aat-related-reviews-grid.aat-related-treatment-compact-rail {
        gap: 14px !important;
    }

    body.aat-shell-page .aat-related-treatment-compact-rail .aat-related-review-card,
    body.aat-shell-page .aat-related-review-card.aat-related-treatment-compact-rail {
        border-radius: 16px !important;
    }

    body.aat-shell-page .aat-related-treatment-compact-rail .aat-related-review-body {
        gap: 9px !important;
        padding: 15px !important;
    }

    @media (min-width: 860px) {
        body.aat-shell-page .aat-related-treatment-feature-strip .aat-related-review-card:first-child,
        body.aat-shell-page .aat-related-reviews-grid.aat-related-treatment-feature-strip .aat-related-review-card:first-child {
            display: grid !important;
            grid-column: span 2;
            grid-template-columns: minmax(240px, .95fr) minmax(0, 1fr);
        }

        body.aat-shell-page .aat-related-treatment-feature-strip .aat-related-review-card:first-child .aat-related-review-media,
        body.aat-shell-page .aat-related-reviews-grid.aat-related-treatment-feature-strip .aat-related-review-card:first-child .aat-related-review-media {
            aspect-ratio: auto !important;
            min-height: 100% !important;
        }
    }

    <?php if ( 'editorial' === $ceremony_rhythm || 'feature' === $writeup_prominence ) : ?>
    body.aat-shell-page .aat-ceremony-editorial-writeup {
        padding: clamp(22px, 3.4vw, 38px) !important;
    }
    <?php endif; ?>

    <?php if ( 'ledger' === $ceremony_rhythm || 'compact-ledger' === $saved_preset ) : ?>
    body.aat-shell-page .aat-ceremony-dossier,
    body.aat-shell-page .aat-category-dossier {
        gap: min(42px, var(--lunara-oscars-dossier-section-gap)) !important;
    }
    <?php endif; ?>

    <?php if ( 'compact' === $major_race_prominence ) : ?>
    body.aat-shell-page .aat-ceremony-major-races {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)) !important;
    }
    <?php elseif ( 'feature' === $major_race_prominence ) : ?>
    body.aat-shell-page .aat-ceremony-major-races {
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)) !important;
    }
    <?php endif; ?>

    @media (max-width: 700px) {
        body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap,
        body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap.is-person,
        body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap.is-company,
        body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap,
        body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap.is-person,
        body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap.is-company {
            flex-basis: min(100%, 260px) !important;
            max-height: min(var(--lunara-oscars-profile-media-height), 420px) !important;
            max-width: min(100%, 260px) !important;
            width: min(100%, 260px) !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_oscars_dossier_studio_css', 1002 );

/**
 * Emit bounded Utility Search Studio controls.
 */
function lunara_get_utility_search_preview_preset_values() {
    if ( ! current_user_can( 'edit_theme_options' ) || empty( $_GET['lunara-utility-preset'] ) ) {
        return array();
    }

    $preset_key = sanitize_key( wp_unslash( $_GET['lunara-utility-preset'] ) );
    if ( '' === $preset_key || ! function_exists( 'lunara_control_desk_utility_search_preset_specs' ) ) {
        return array();
    }

    $presets = lunara_control_desk_utility_search_preset_specs();
    if ( ! isset( $presets[ $preset_key ]['values'] ) || ! is_array( $presets[ $preset_key ]['values'] ) ) {
        return array();
    }

    return $presets[ $preset_key ]['values'];
}

function lunara_get_utility_search_studio_select_value( $preview_values, $key, $default, $allowed ) {
    if ( isset( $preview_values[ $key ] ) ) {
        $preview_value = sanitize_key( (string) $preview_values[ $key ] );
        $allowed       = array_map( 'sanitize_key', (array) $allowed );

        if ( in_array( $preview_value, $allowed, true ) ) {
            return $preview_value;
        }
    }

    return lunara_home_select_setting( $key, $default, $allowed );
}

function lunara_get_utility_search_studio_number_value( $preview_values, $key, $default, $min, $max ) {
    if ( isset( $preview_values[ $key ] ) ) {
        return max( absint( $min ), min( absint( $max ), absint( $preview_values[ $key ] ) ) );
    }

    return lunara_home_brand_number_setting( $key, $default, $min, $max );
}

function lunara_output_utility_search_studio_css() {
    if ( is_admin() || is_feed() || ! ( is_search() || is_404() ) ) {
        return;
    }

    $preview_values = lunara_get_utility_search_preview_preset_values();

    $density = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_search_density',
        'editorial',
        array( 'compact', 'editorial', 'showcase' )
    );
    $result_treatment = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_result_treatment',
        'cards',
        array( 'list', 'cards', 'spotlight' )
    );
    $result_media = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_result_media',
        'guarded',
        array( 'guarded', 'poster-led', 'text-led' )
    );
    $recovery_prominence = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_recovery_prominence',
        'standard',
        array( 'quiet', 'standard', 'strong' )
    );
    $lead_focus = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_search_lead_focus',
        'balanced',
        array( 'balanced', 'ledger', 'reviews', 'journal' )
    );
    $spotlight_type = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_search_spotlight_type',
        'automatic',
        array( 'automatic', 'review', 'journal', 'page' )
    );
    $reentry_primary = lunara_get_utility_search_studio_select_value(
        $preview_values,
        'lunara_utility_reentry_primary',
        'home',
        array( 'home', 'reviews', 'journal', 'oscars', 'search' )
    );

    $section_gap       = lunara_get_utility_search_studio_number_value( $preview_values, 'lunara_utility_section_gap', 42, 20, 84 );
    $result_min_height = lunara_get_utility_search_studio_number_value( $preview_values, 'lunara_utility_result_min_height', 158, 118, 260 );
    $card_grid_min     = lunara_get_utility_search_studio_number_value( $preview_values, 'lunara_utility_card_grid_min', 280, 220, 360 );

    $copy_lines_map = array(
        'compact'   => 2,
        'editorial' => 3,
        'showcase'  => 4,
    );
    $density_pad_map = array(
        'compact'   => 'clamp(18px, 3vw, 28px)',
        'editorial' => 'clamp(22px, 3.4vw, 36px)',
        'showcase'  => 'clamp(28px, 4vw, 46px)',
    );
    $density_gap_map = array(
        'compact'   => '.72',
        'editorial' => '1',
        'showcase'  => '1.22',
    );
    $media_fit_map = array(
        'guarded'    => 'cover',
        'poster-led' => 'cover',
        'text-led'   => 'contain',
    );
    $media_weight_map = array(
        'guarded'    => '1',
        'poster-led' => '1.12',
        'text-led'   => '.72',
    );
    $recovery_scale_map = array(
        'quiet'    => '.88',
        'standard' => '1',
        'strong'   => '1.18',
    );

    $copy_lines     = isset( $copy_lines_map[ $density ] ) ? $copy_lines_map[ $density ] : 3;
    $density_pad    = isset( $density_pad_map[ $density ] ) ? $density_pad_map[ $density ] : 'clamp(22px, 3.4vw, 36px)';
    $density_gap    = isset( $density_gap_map[ $density ] ) ? $density_gap_map[ $density ] : '1';
    $media_fit      = isset( $media_fit_map[ $result_media ] ) ? $media_fit_map[ $result_media ] : 'cover';
    $media_weight   = isset( $media_weight_map[ $result_media ] ) ? $media_weight_map[ $result_media ] : '1';
    $recovery_scale = isset( $recovery_scale_map[ $recovery_prominence ] ) ? $recovery_scale_map[ $recovery_prominence ] : '1';
    ?>
    <style id="lunara-utility-search-studio-css">
    body.search .lunara-search-page,
    body.error404 .lunara-404-page {
        --lunara-utility-section-gap: <?php echo esc_html( absint( $section_gap ) ); ?>px;
        --lunara-utility-result-min-height: <?php echo esc_html( absint( $result_min_height ) ); ?>px;
        --lunara-utility-result-grid-min: <?php echo esc_html( absint( $card_grid_min ) ); ?>px;
        --lunara-utility-result-media-fit: <?php echo esc_html( $media_fit ); ?>;
        --lunara-utility-result-copy-lines: <?php echo esc_html( absint( $copy_lines ) ); ?>;
        --lunara-utility-panel-pad: <?php echo esc_html( $density_pad ); ?>;
        --lunara-utility-density-gap: <?php echo esc_html( $density_gap ); ?>;
        --lunara-utility-media-weight: <?php echo esc_html( $media_weight ); ?>;
        --lunara-utility-recovery-scale: <?php echo esc_html( $recovery_scale ); ?>;
        --lunara-utility-focus-ring: <?php echo esc_html( 'ledger' === $lead_focus ? 'rgba(201, 169, 97, .34)' : 'rgba(118, 166, 196, .26)' ); ?>;
    }

    body.search .lunara-search-page,
    body.error404 .lunara-404-page {
        gap: var(--lunara-utility-section-gap);
    }

    body.error404 .lunara-404-page {
        padding-bottom: clamp(28px, 4vw, 52px) !important;
    }

    body.error404 .lunara-404-shell {
        margin-bottom: 0 !important;
    }

    body.error404 .lunara-site-footer {
        margin-top: clamp(30px, 4vw, 58px) !important;
        padding-top: clamp(36px, 5vw, 62px) !important;
    }

    body.search .lunara-search-page .lunara-home-section,
    body.error404 .lunara-404-page .lunara-home-section,
    body.search .lunara-search-oscar-shell,
    body.search .lunara-search-results-shell,
    body.search .lunara-search-empty-shell,
    body.error404 .lunara-404-shell {
        margin-block: calc(var(--lunara-utility-section-gap) * var(--lunara-utility-density-gap));
    }

    body.search .lunara-search-oscar-grid,
    body.search .lunara-search-results-grid {
        display: grid;
        gap: clamp(14px, calc(var(--lunara-utility-section-gap) * .42), 26px);
        grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--lunara-utility-result-grid-min)), 1fr));
        min-width: 0;
    }

    body.search .lunara-search-result-card,
    body.search .lunara-search-oscar-card,
    body.search .lunara-search-results-grid > article,
    body.search .lunara-search-oscar-grid > article {
        min-height: var(--lunara-utility-result-min-height);
    }

    body.search .lunara-search-result-card,
    body.search .lunara-search-oscar-card,
    body.search .lunara-search-empty-shell,
    body.error404 .lunara-404-panel {
        background:
            linear-gradient(135deg, rgba(201, 169, 97, .11), transparent 44%),
            rgba(8, 22, 36, .92);
        border: 1px solid rgba(201, 169, 97, .18);
        box-shadow: 0 18px 44px rgba(0, 0, 0, .2);
        min-width: 0;
    }

    body.search .lunara-search-page--focus-ledger .lunara-search-oscar-shell,
    body.search .lunara-search-page--focus-reviews .lunara-search-results-shell,
    body.search .lunara-search-page--focus-journal .lunara-search-results-shell {
        border: 1px solid var(--lunara-utility-focus-ring);
        border-radius: 18px;
        padding: clamp(16px, 2.4vw, 26px);
        background:
            linear-gradient(135deg, rgba(201, 169, 97, .08), transparent 45%),
            rgba(6, 18, 31, .34);
    }

    body.search .lunara-search-page--focus-reviews .lunara-search-results-head .lunara-home-section-kicker::after,
    body.search .lunara-search-page--focus-journal .lunara-search-results-head .lunara-home-section-kicker::after,
    body.search .lunara-search-page--focus-ledger .lunara-search-results-head .lunara-home-section-kicker::after {
        content: " / Focused";
        color: rgba(201, 169, 97, .72);
    }

    body.search .lunara-search-page--spotlight-review .lunara-search-results-grid > .lunara-review-grid-card:first-child,
    body.search .lunara-search-page--spotlight-journal .lunara-search-results-grid > .lunara-dispatch-archive-card:first-child,
    body.search .lunara-search-page--spotlight-page .lunara-search-results-grid > .lunara-search-result-card:first-child {
        outline: 1px solid rgba(201, 169, 97, .34);
        outline-offset: 4px;
    }

    body.search .lunara-editorial-archive-debrief-list li {
        align-items: start;
        gap: 12px;
        grid-template-columns: minmax(88px, .45fr) minmax(0, 1fr);
    }

    body.search .lunara-editorial-archive-debrief-list span {
        line-height: 1.35;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    body.error404 .lunara-404-page--primary-reviews .lunara-404-action--reviews,
    body.error404 .lunara-404-page--primary-oscars .lunara-404-action--oscars,
    body.error404 .lunara-404-page--primary-journal .lunara-404-action--journal,
    body.error404 .lunara-404-page--primary-search .lunara-404-action--search,
    body.error404 .lunara-404-page--primary-home .lunara-404-action--home {
        box-shadow: 0 0 0 1px rgba(201, 169, 97, .34), 0 18px 38px rgba(0, 0, 0, .22);
        transform: translateY(-1px);
    }

    body.search .lunara-search-empty-shell,
    body.error404 .lunara-404-panel {
        padding: calc(var(--lunara-utility-panel-pad) * var(--lunara-utility-recovery-scale));
    }

    body.search .lunara-search-result-card img,
    body.search .lunara-search-oscar-card img,
    body.search .lunara-review-grid-card img,
    body.search .lunara-dispatch-archive-card img {
        object-fit: var(--lunara-utility-result-media-fit);
        transform: scale(var(--lunara-utility-media-weight));
        transform-origin: center;
    }

    body.search .lunara-search-result-card p,
    body.search .lunara-search-oscar-card p,
    body.search .lunara-review-grid-card p,
    body.search .lunara-dispatch-archive-card p,
    body.error404 .lunara-404-panel p {
        display: -webkit-box;
        -webkit-line-clamp: var(--lunara-utility-result-copy-lines);
        line-clamp: var(--lunara-utility-result-copy-lines);
        overflow: hidden;
        -webkit-box-orient: vertical;
    }

    <?php if ( 'list' === $result_treatment ) : ?>
    body.search .lunara-search-results-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    body.search .lunara-search-result-card,
    body.search .lunara-search-results-grid > article {
        min-height: calc(var(--lunara-utility-result-min-height) * .82);
    }
    <?php elseif ( 'spotlight' === $result_treatment ) : ?>
    @media (min-width: 840px) {
        body.search .lunara-search-results-grid > article:first-child {
            grid-column: span 2;
            min-height: calc(var(--lunara-utility-result-min-height) + 46px);
        }
    }
    <?php endif; ?>

    <?php if ( 'poster-led' === $result_media ) : ?>
    body.search .lunara-search-results-grid .lunara-review-grid-card,
    body.search .lunara-search-results-grid .lunara-dispatch-archive-card {
        min-height: calc(var(--lunara-utility-result-min-height) + 42px);
    }
    <?php elseif ( 'text-led' === $result_media ) : ?>
    body.search .lunara-search-result-card img,
    body.search .lunara-search-oscar-card img,
    body.search .lunara-review-grid-card img,
    body.search .lunara-dispatch-archive-card img {
        filter: saturate(.9) contrast(.95);
    }
    <?php endif; ?>

    @media (max-width: 700px) {
        body.search .lunara-search-page,
        body.error404 .lunara-404-page {
            --lunara-utility-section-gap: min(<?php echo esc_html( absint( $section_gap ) ); ?>px, 34px);
            --lunara-utility-result-grid-min: min(<?php echo esc_html( absint( $card_grid_min ) ); ?>px, 100%);
        }

        body.search .lunara-editorial-archive-debrief-list li {
            grid-template-columns: minmax(0, 1fr);
        }

        body.search .lunara-search-oscar-grid,
        body.search .lunara-search-results-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_utility_search_studio_css', 1003 );

/**
 * Hero cinema crossfade: auto-rotating poster hero.
 */
function lunara_output_hero_cinema_js() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script>
    (function(){
        var stage=document.querySelector('.lunara-hero-cinema-stage');
        if(!stage)return;
        var slides=stage.querySelectorAll('.lunara-hero-cinema-slide');
        var pips=stage.querySelectorAll('.lunara-hero-cinema-pip');
        if(slides.length<2)return;
        var current=0;
        var interval=5500;
        var timer=null;
        var paused=false;
        function goTo(idx){
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden','true');
            if(pips[current])pips[current].classList.remove('is-active');
            current=idx%slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden','false');
            if(pips[current])pips[current].classList.add('is-active');
        }
        function next(){goTo(current+1);}
        function startAuto(){
            if(timer||paused||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
            timer=setInterval(next,interval);
        }
        function stopAuto(){if(timer){clearInterval(timer);timer=null;}}
        stage.addEventListener('mouseenter',function(){paused=true;stopAuto();});
        stage.addEventListener('mouseleave',function(){paused=false;startAuto();});
        stage.addEventListener('focusin',function(){paused=true;stopAuto();});
        stage.addEventListener('focusout',function(){paused=false;startAuto();});
        pips.forEach(function(pip){
            pip.addEventListener('click',function(){
                stopAuto();
                goTo(parseInt(pip.getAttribute('data-slide'),10));
                if(!paused)startAuto();
            });
        });
        startAuto();
    })();
    </script>
    <?php
}
// Phase 1C: behavior moved to cacheable route assets.

/**
 * Lightweight Journal image carousel controls.
 */
function lunara_output_journal_image_carousel_js() {
    if ( ! is_singular( 'journal' ) ) {
        return;
    }
    ?>
    <script>
    (function(){
        var carousels = document.querySelectorAll('[data-lunara-journal-carousel]');
        if (!carousels.length) return;

        carousels.forEach(function(carousel){
            var track = carousel.querySelector('.lunara-journal-image-carousel-track');
            var previous = carousel.querySelector('[data-lunara-carousel-action="prev"]');
            var next = carousel.querySelector('[data-lunara-carousel-action="next"]');
            if (!track || (!previous && !next)) return;

            function slideWidth() {
                var slide = track.querySelector('.lunara-journal-image-carousel-slide');
                if (!slide) return Math.max(280, Math.round(track.clientWidth * 0.86));
                var rect = slide.getBoundingClientRect();
                return Math.max(240, Math.round(rect.width + 14));
            }

            function move(direction) {
                track.scrollBy({ left: slideWidth() * direction, behavior: 'smooth' });
            }

            if (previous) previous.addEventListener('click', function(){ move(-1); });
            if (next) next.addEventListener('click', function(){ move(1); });
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_journal_image_carousel_js', 110 );

/**
 * Review sidebar scroll-follow.
 * Blocksy's #main-container overflow:clip defeats CSS position:sticky.
 * This JS-based approach manually tracks scroll and fixes the sidebar.
 */
function lunara_output_sidebar_scroll_follow_js() {
    if ( ! is_singular( 'review' ) && ! ( is_single() && has_term( '', 'lunara_director' ) ) ) {
        return;
    }

    // Keep review rail panels in normal sticky/static flow; JS fixed-follow can overlap the Debrief section.
    return;
    ?>
    <script>
    (function(){
        var sticky = document.querySelector('.lunara-review-single-rail-sticky');
        var rail   = document.querySelector('.lunara-review-single-rail');
        var bounds = document.querySelector('.lunara-review-single-page article') ||
            document.querySelector('.lunara-review-single-page') ||
            document.querySelector('.lunara-review-single-body-grid');
        if (!sticky || !rail || !bounds) return;

        var mq = window.matchMedia('(max-width: 900px)');
        var topGap = 90;
        var ticking = false;

        function pageTop(el) {
            return el.getBoundingClientRect().top + window.scrollY;
        }

        function resetRail() {
            sticky.style.position = '';
            sticky.style.top = '';
            sticky.style.left = '';
            sticky.style.width = '';
            sticky.style.transform = '';
            rail.style.minHeight = '';
            sticky.classList.remove('is-following', 'is-bottomed');
        }

        /*
         * Use transform: translateY() instead of position:fixed.
         * This keeps the element in normal flow, avoiding Blocksy's
         * overflow:clip and ancestor-transform issues entirely.
         */
        function update() {
            ticking = false;

            if (mq.matches) {
                resetRail();
                return;
            }

            /* Natural (un-translated) top of the sticky element */
            sticky.style.transform = '';               /* reset to measure natural position */
            var stickyNat  = sticky.getBoundingClientRect();
            var boundsRect = bounds.getBoundingClientRect();
            var stickyH    = sticky.offsetHeight;
            var boundsBottom = boundsRect.bottom;

            /* 1. Sidebar top hasn't scrolled past the gap — stay put */
            if (stickyNat.top >= topGap) {
                sticky.classList.remove('is-following', 'is-bottomed');
                return;
            }

            /* How far we need to shift the element down */
            var shift = topGap - stickyNat.top;

            /* 2. Clamp so it doesn't overflow past the review surface bottom */
            var maxShift = boundsBottom - stickyNat.top - stickyH - 24;
            if (maxShift < 0) maxShift = 0;
            if (shift > maxShift) {
                shift = maxShift;
                sticky.classList.remove('is-following');
                sticky.classList.add('is-bottomed');
            } else {
                sticky.classList.add('is-following');
                sticky.classList.remove('is-bottomed');
            }

            sticky.style.transform = 'translateY(' + Math.round(shift) + 'px)';
        }

        function onViewportChange() {
            resetRail();
            if (!mq.matches) {
                requestAnimationFrame(update);
            }
        }

        function onScroll() {
            if (mq.matches) {
                resetRail();
                return;
            }

            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, {passive: true});
        window.addEventListener('resize', onViewportChange);

        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', onViewportChange);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(onViewportChange);
        }

        /* Initial call after layout settles */
        requestAnimationFrame(onViewportChange);
    })();
    </script>
    <script id="lunara-review-reader-rail-follow">
    (function(){
        var rail = document.querySelector('.lunara-review-single-rail');
        var sticky = document.querySelector('.lunara-review-single-rail-sticky');
        var bounds = document.querySelector('.lunara-review-single-page article') ||
            document.querySelector('.lunara-review-single-page') ||
            document.querySelector('.lunara-review-single-body-grid');

        if (!rail || !sticky || !bounds) return;

        var mq = window.matchMedia('(max-width: 900px)');
        var topGap = 90;
        var ticking = false;

        function pageTop(el) {
            return el.getBoundingClientRect().top + window.scrollY;
        }

        function reset() {
            sticky.style.position = '';
            sticky.style.top = '';
            sticky.style.left = '';
            sticky.style.width = '';
            sticky.style.transform = '';
            rail.style.minHeight = '';
            sticky.classList.remove('is-following', 'is-bottomed');
        }

        function update() {
            ticking = false;

            if (mq.matches) {
                reset();
                return;
            }

            reset();

            var railRect = rail.getBoundingClientRect();
            var stickyHeight = sticky.offsetHeight;
            var railStart = pageTop(rail);
            var boundsEnd = pageTop(bounds) + bounds.offsetHeight;
            var desiredTop = window.scrollY + topGap;
            var bottomTop = boundsEnd - stickyHeight - 24;

            rail.style.minHeight = stickyHeight + 'px';

            if (desiredTop <= railStart) {
                return;
            }

            if (desiredTop >= bottomTop) {
                sticky.style.setProperty('position', 'absolute', 'important');
                sticky.style.setProperty('top', Math.max(0, bottomTop - railStart) + 'px', 'important');
                sticky.style.setProperty('left', '0', 'important');
                sticky.style.setProperty('width', railRect.width + 'px', 'important');
                sticky.classList.add('is-bottomed');
                return;
            }

            sticky.style.setProperty('position', 'fixed', 'important');
            sticky.style.setProperty('top', topGap + 'px', 'important');
            sticky.style.setProperty('left', railRect.left + 'px', 'important');
            sticky.style.setProperty('width', railRect.width + 'px', 'important');
            sticky.classList.add('is-following');
        }

        function schedule() {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', schedule);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(schedule);
        }

        requestAnimationFrame(update);
        window.setTimeout(update, 500);
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_sidebar_scroll_follow_js', 101 );

/**
 * Keep reviews inside the review lane instead of bleeding into standard post archives.
 */
if ( ! function_exists( 'lunara_separate_review_from_editorial_archives' ) ) {
    function lunara_separate_review_from_editorial_archives( $query ) {
        if ( is_admin() || ! ( $query instanceof WP_Query ) || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_search() || $query->is_singular( 'review' ) || $query->is_singular( 'journal' ) ) {
            return;
        }

        if ( $query->is_post_type_archive( 'review' ) ) {
            if ( function_exists( 'lunara_apply_review_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_review_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
            return;
        }

        if ( $query->is_post_type_archive( 'journal' ) || $query->is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) ) ) {
            if ( function_exists( 'lunara_apply_editorial_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_editorial_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
            return;
        }

        $requested_post_type = $query->get( 'post_type' );
        if ( 'review' === $requested_post_type || ( is_array( $requested_post_type ) && in_array( 'review', $requested_post_type, true ) ) ) {
            return;
        }

        if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
            $query->set( 'post_type', 'post' );

            if ( function_exists( 'lunara_apply_editorial_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_editorial_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
        }
    }
}
add_action( 'pre_get_posts', 'lunara_separate_review_from_editorial_archives', 12 );

/* ========================================
   NEWS GRID — auto-splits multi-story posts into card grids
   Detects H2/H3 headings as story boundaries.
   ======================================== */

/**
 * Split a standard post's content into separate story cards when it has
 * multiple H2 or H3 headings (a "roundup" or "multi-story" post).
 *
 * Requires at least 2 headings to activate. Single-story posts pass through untouched.
 */
function lunara_news_grid_content_filter( $content ) {
    if ( ! is_singular( 'post' ) || is_admin() ) {
        return $content;
    }

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return $content;
    }

    // Only apply to news-type posts. Skip if post has the _lunara_disable_news_grid meta.
    if ( '1' === (string) get_post_meta( $post_id, '_lunara_disable_news_grid', true ) ) {
        return $content;
    }

    // Split content on H2 and H3 tags.
    $parts = preg_split( '/(<h[23][^>]*>.*?<\/h[23]>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
    if ( ! is_array( $parts ) || count( $parts ) < 4 ) {
        // Fewer than 2 heading+body pairs — not a multi-story post.
        return $content;
    }

    // Build story cards: each card starts with a heading and collects content until the next heading.
    $cards   = array();
    $current = array( 'heading' => '', 'body' => '' );
    $intro   = '';

    foreach ( $parts as $part ) {
        if ( preg_match( '/^<h[23][^>]*>(.*?)<\/h[23]>$/is', $part, $m ) ) {
            // Save previous card if it has a heading.
            if ( '' !== $current['heading'] ) {
                $cards[] = $current;
            } elseif ( '' !== trim( $current['body'] ) ) {
                // Content before the first heading is an intro.
                $intro = $current['body'];
            }
            $current = array( 'heading' => trim( $m[1] ), 'body' => '' );
        } else {
            $current['body'] .= $part;
        }
    }

    // Save the last card.
    if ( '' !== $current['heading'] ) {
        $cards[] = $current;
    }

    if ( count( $cards ) < 2 ) {
        return $content;
    }

    // Extract the first image from each card's body for the card visual.
    $grid_html = '';

    if ( '' !== trim( $intro ) ) {
        $grid_html .= '<div class="lunara-news-grid-intro">' . $intro . '</div>';
    }

    $grid_html .= '<div class="lunara-news-grid">';

    foreach ( $cards as $card ) {
        $image_html = '';
        $card_body  = $card['body'];

        // Pull the first <img> or <figure> from the card body.
        if ( preg_match( '/<figure[^>]*>.*?<\/figure>/is', $card_body, $fig_match ) ) {
            $image_html = $fig_match[0];
            $card_body  = str_replace( $fig_match[0], '', $card_body );
        } elseif ( preg_match( '/<img[^>]+>/is', $card_body, $img_match ) ) {
            $image_html = $img_match[0];
            $card_body  = str_replace( $img_match[0], '', $card_body );
        }

        // Clean up empty paragraphs left after image extraction.
        $card_body = preg_replace( '/<p>\s*<\/p>/is', '', $card_body );
        $card_body = trim( $card_body );

        $grid_html .= '<article class="lunara-news-grid-card">';

        if ( '' !== $image_html ) {
            $grid_html .= '<div class="lunara-news-grid-card-image">' . $image_html . '</div>';
        }

        $grid_html .= '<div class="lunara-news-grid-card-content">';
        $grid_html .= '<h3 class="lunara-news-grid-card-title">' . $card['heading'] . '</h3>';
        $grid_html .= '<div class="lunara-news-grid-card-body">' . $card_body . '</div>';
        $grid_html .= '</div>';
        $grid_html .= '</article>';
    }

    $grid_html .= '</div>';

    return $grid_html;
}
add_filter( 'the_content', 'lunara_news_grid_content_filter', 8 );
