<?php
/**
 * Oscars Portal — dedicated /oscars/ front-door rendering.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * True when rendering the dedicated Oscars portal front door.
 */
function lunara_is_oscars_portal_page() {
    return ! is_admin() && is_page( 'oscars' );
}

/**
 * Build the dedicated Oscars portal markup for the /oscars/ page.
 */
function lunara_render_oscars_portal_markup() {
    $aat                = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
    $snapshot           = function_exists( 'lunara_get_home_oscars_snapshot' ) ? lunara_get_home_oscars_snapshot() : array();
    $database_spotlight = function_exists( 'lunara_get_home_database_spotlight' ) ? lunara_get_home_database_spotlight() : array();
    $deep_cuts          = function_exists( 'lunara_get_home_deep_cuts' ) ? lunara_get_home_deep_cuts() : array();
    $linked_reviews     = function_exists( 'lunara_oscars_linked_reviews_query' ) ? lunara_oscars_linked_reviews_query( 4 ) : new WP_Query();

    $hero_kicker       = lunara_theme_mod_text( 'lunara_oscars_portal_kicker', 'The Lunara Oscar Ledger' );
    $hero_title        = lunara_theme_mod_text( 'lunara_oscars_portal_title', 'Academy Awards history, treated like a living editorial system.' );
    $hero_copy         = '';
    $explore_heading   = lunara_theme_mod_text( 'lunara_oscars_portal_explore_heading', 'Start anywhere in the ledger.' );
    $reviews_heading   = lunara_theme_mod_text( 'lunara_oscars_portal_reviews_heading', 'Reviews Inside the Ledger' );
    $deep_cuts_heading = lunara_theme_mod_text( 'lunara_oscars_portal_deep_cuts_heading', 'Oscar Deep Cuts' );

    $best_picture      = is_array( $snapshot['best_picture'] ?? null ) ? $snapshot['best_picture'] : array();
    $best_visual       = is_array( $best_picture['visual'] ?? null ) ? $best_picture['visual'] : array();
    $spotlights        = array_slice( (array) ( $snapshot['spotlights'] ?? array() ), 0, 6 );
    $title_cards       = array_slice( (array) ( $database_spotlight['cards'] ?? array() ), 0, 5 );

    $database_url      = ( $aat && method_exists( $aat, 'get_database_url' ) ) ? $aat->get_database_url() : trim( (string) ( $snapshot['database_url'] ?? ( $database_spotlight['database_url'] ?? home_url( '/oscars/' ) ) ) );
    $categories_url    = ( $aat && method_exists( $aat, 'get_categories_index_url' ) ) ? $aat->get_categories_index_url() : trim( (string) ( $snapshot['categories_url'] ?? ( $database_spotlight['categories_url'] ?? home_url( '/oscars/categories/' ) ) ) );
    $ceremony_url      = ( $aat && method_exists( $aat, 'get_ceremony_url' ) && ! empty( $snapshot['ceremony'] ) ) ? $aat->get_ceremony_url( intval( $snapshot['ceremony'] ) ) : trim( (string) ( $snapshot['ceremony_url'] ?? home_url( '/oscars/ceremony/' ) ) );
    $ceremony_label    = trim( (string) ( $snapshot['ceremony_label'] ?? 'Latest Ceremony' ) );
    $year_label        = trim( (string) ( $snapshot['year_label'] ?? '' ) );
    $about_url         = ( $aat && method_exists( $aat, 'get_about_url' ) ) ? $aat->get_about_url() : home_url( '/oscars/about/' );
    $ceremonies_url    = ( $aat && method_exists( $aat, 'get_ceremonies_index_url' ) ) ? $aat->get_ceremonies_index_url() : home_url( '/oscars/ceremonies/' );
    $hero_backdrop_url = trim( (string) ( $best_visual['backdrop_url'] ?? '' ) );
    $hero_style        = '';

    if ( '' !== $hero_backdrop_url ) {
        $hero_style = "background-image: linear-gradient(120deg, rgba(7,16,27,.92) 0%, rgba(7,16,27,.86) 48%, rgba(7,16,27,.97) 100%), url('" . esc_url( $hero_backdrop_url ) . "'); background-size: cover; background-position: center;";
    }

    $hero_title_card = array();
    if ( ! empty( $best_picture ) ) {
        $hero_title_card = array(
            'title'     => trim( (string) ( $best_picture['film'] ?? '' ) ),
            'url'       => trim( (string) ( $best_picture['film_url'] ?? $ceremony_url ) ),
            'visual'    => $best_visual,
            'eyebrow'   => 'Latest Best Picture',
            'meta_line' => trim( (string) $ceremony_label . ( $year_label !== '' ? ' / ' . $year_label : '' ) ),
            'body'      => trim( (string) ( $snapshot['summary'] ?? $snapshot['winner_record'] ?? '' ) ),
        );
    } elseif ( ! empty( $title_cards[0] ) ) {
        $hero_title_card = array(
            'title'     => trim( (string) ( $title_cards[0]['title'] ?? '' ) ),
            'url'       => trim( (string) ( $title_cards[0]['url'] ?? $database_url ) ),
            'visual'    => is_array( $title_cards[0]['visual'] ?? null ) ? $title_cards[0]['visual'] : array(),
            'eyebrow'   => 'Featured Portal Entry',
            'meta_line' => trim( (string) ( $title_cards[0]['categories_line'] ?? '' ) ),
            'body'      => 'Open a poster-led entry point into the ledger and move outward into categories, ceremonies, and related people pages.',
        );
    }

    $portal_stats = array(
        array(
            'label' => 'Ceremony',
            'value' => $ceremony_label,
        ),
        array(
            'label' => 'Year',
            'value' => $year_label !== '' ? $year_label : 'Live',
        ),
        array(
            'label' => 'Rows',
            'value' => number_format_i18n( intval( $database_spotlight['records_total'] ?? 0 ) ),
        ),
        array(
            'label' => 'Categories',
            'value' => number_format_i18n( intval( $database_spotlight['categories_total'] ?? 0 ) ),
        ),
    );

    // Backdrop images keyed by portal card — iconic Oscar titles.
    $portal_backdrop_map = array(
        'Ceremonies' => 'tt7286456',  // Joker
        'Categories' => 'tt1375666',  // Inception
        'Ledger'     => 'tt0111161',  // The Shawshank Redemption
        'About'      => 'tt0068646',  // The Godfather
    );
    $portal_backdrops = array();
    if ( class_exists( 'Academy_Awards_Table' ) ) {
        $aat_inst = Academy_Awards_Table::get_instance();
        if ( $aat_inst && method_exists( $aat_inst, 'get_title_visual_package' ) ) {
            foreach ( $portal_backdrop_map as $key => $imdb ) {
                $vis = $aat_inst->get_title_visual_package( $imdb, 'large' );
                $portal_backdrops[ $key ] = $vis['backdrop_url'] ?? '';
            }
        }
    }

    $portal_links = array(
        array(
            'kicker'   => 'Ceremonies',
            'title'    => 'Ceremony Archive',
            'copy'     => '',
            'url'      => $ceremonies_url,
            'backdrop' => $portal_backdrops['Ceremonies'] ?? '',
        ),
        array(
            'kicker'   => 'Categories',
            'title'    => 'Category History',
            'copy'     => '',
            'url'      => $categories_url,
            'backdrop' => $portal_backdrops['Categories'] ?? '',
        ),
        array(
            'kicker'   => 'Ledger',
            'title'    => 'Full Ledger',
            'copy'     => '',
            'url'      => $database_url,
            'backdrop' => $portal_backdrops['Ledger'] ?? '',
        ),
        array(
            'kicker'   => 'About',
            'title'    => 'Ledger Method',
            'copy'     => '',
            'url'      => $about_url,
            'backdrop' => $portal_backdrops['About'] ?? '',
        ),
    );

    ob_start();
    ?>
    <main id="primary" class="site-main lunara-oscars-portal">
        <?php if ( empty( $snapshot ) && empty( $database_spotlight ) ) : ?>
            <section class="lunara-home-section lunara-archive-hero">
                <p class="lunara-archive-hero-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php echo esc_html( get_the_title() ); ?></h1>
            </section>
        <?php else : ?>
            <section class="lunara-home-section lunara-oscars-portal-hero"<?php if ( '' !== $hero_style ) : ?> style="<?php echo esc_attr( $hero_style ); ?>"<?php endif; ?>>
                <div class="lunara-oscars-portal-hero-grid">
                    <div class="lunara-oscars-portal-copy">
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
                        <h1 class="lunara-home-hero-title"><?php echo esc_html( $hero_title ); ?></h1>
                        <?php if ( '' !== trim( (string) $hero_copy ) ) : ?>
                            <p class="lunara-home-hero-copy"><?php echo esc_html( $hero_copy ); ?></p>
                        <?php endif; ?>

                        <div class="lunara-oscars-portal-actions">
                            <a class="lunara-button lunara-button-primary" href="<?php echo esc_url( $ceremony_url ); ?>">Latest Ceremony</a>
                            <a class="lunara-button lunara-button-secondary" href="<?php echo esc_url( $database_url ); ?>">Open Full Ledger</a>
                            <a class="lunara-button-ghost" href="<?php echo esc_url( $categories_url ); ?>">Browse Categories</a>
                        </div>

                        <div class="lunara-oscars-portal-stat-grid">
                            <?php foreach ( $portal_stats as $stat ) : ?>
                                <div class="lunara-oscars-portal-stat">
                                    <span class="lunara-oscars-portal-stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
                                    <strong class="lunara-oscars-portal-stat-value"><?php echo esc_html( $stat['value'] ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ( ! empty( $hero_title_card ) ) : ?>
                        <a class="lunara-oscars-portal-feature-card" href="<?php echo esc_url( $hero_title_card['url'] ?? $database_url ); ?>">
                            <div class="lunara-oscars-portal-feature-poster">
                                <?php if ( ! empty( $hero_title_card['visual']['poster_html'] ) ) : ?>
                                    <?php echo $hero_title_card['visual']['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php elseif ( ! empty( $hero_title_card['visual']['poster_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $hero_title_card['visual']['poster_url'] ); ?>" alt="<?php echo esc_attr( $hero_title_card['title'] ); ?>" loading="lazy" decoding="async" />
                                <?php elseif ( ! empty( $hero_title_card['visual']['card_fallback_html'] ) ) : ?>
                                    <?php echo $hero_title_card['visual']['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php else : ?>
                                    <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Portal</div><div class="aat-fallback-title small"><?php echo esc_html( $hero_title_card['title'] ); ?></div></div></div>
                                <?php endif; ?>
                            </div>
                            <div class="lunara-oscars-portal-feature-copy">
                                <p class="lunara-oscars-portal-feature-kicker"><?php echo esc_html( $hero_title_card['eyebrow'] ); ?></p>
                                <h2><?php echo esc_html( $hero_title_card['title'] ); ?></h2>
                                <?php if ( '' !== trim( (string) $hero_title_card['meta_line'] ) ) : ?>
                                    <p class="lunara-oscars-portal-feature-meta"><?php echo esc_html( $hero_title_card['meta_line'] ); ?></p>
                                <?php endif; ?>
                                <?php if ( '' !== trim( (string) $hero_title_card['body'] ) ) : ?>
                                    <p class="lunara-oscars-portal-feature-body"><?php echo esc_html( $hero_title_card['body'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lunara-home-section lunara-oscars-portal-links-section">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker">Explore the Portal</p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $explore_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-oscars-portal-link-grid">
                    <?php foreach ( $portal_links as $portal_link ) :
                        $bd_url   = trim( (string) ( $portal_link['backdrop'] ?? '' ) );
                        $bd_style = '' !== $bd_url ? 'background-image:url(' . esc_url( $bd_url ) . ')' : '';
                        $bd_class = '' !== $bd_url ? ' has-backdrop' : '';
                    ?>
                        <a class="lunara-oscars-portal-link-card<?php echo $bd_class; ?>" href="<?php echo esc_url( $portal_link['url'] ); ?>"<?php if ( '' !== $bd_style ) : ?> style="<?php echo esc_attr( $bd_style ); ?>"<?php endif; ?>>
                            <p class="lunara-oscars-portal-link-kicker"><?php echo esc_html( $portal_link['kicker'] ); ?></p>
                            <h3><?php echo esc_html( $portal_link['title'] ); ?></h3>
                            <?php if ( '' !== trim( (string) $portal_link['copy'] ) ) : ?>
                                <p><?php echo esc_html( $portal_link['copy'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ( ! empty( $spotlights ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-spotlights">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker"><?php echo esc_html( $ceremony_label ); ?></p>
                            <h2 class="lunara-home-section-title">Latest Ceremony, category by category.</h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-spotlight-grid">
                        <?php foreach ( $spotlights as $spotlight ) :
                            $sl_visual = is_array( $spotlight['visual'] ?? null ) ? $spotlight['visual'] : array();
                            $sl_winner = intval( $spotlight['winner'] ?? 0 );
                        ?>
                            <a class="lunara-oscars-portal-spotlight-card<?php echo $sl_winner ? ' is-winner' : ''; ?>" href="<?php echo esc_url( $spotlight['url'] ?? $database_url ); ?>">
                                <?php if ( ! empty( $sl_visual['poster_html'] ) ) : ?>
                                    <div class="lunara-oscars-spotlight-poster">
                                        <?php echo $sl_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                <?php elseif ( ! empty( $sl_visual['poster_url'] ) ) : ?>
                                    <div class="lunara-oscars-spotlight-poster">
                                        <img src="<?php echo esc_url( $sl_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $spotlight['primary_label'] ?? '' ); ?>" loading="lazy" decoding="async" />
                                    </div>
                                <?php else : ?>
                                    <div class="lunara-oscars-spotlight-poster lunara-oscars-spotlight-poster--fallback">
                                        <span><?php echo esc_html( $spotlight['primary_label'] ?? '' ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="lunara-oscars-spotlight-card-copy">
                                    <p class="lunara-oscars-portal-spotlight-category"><?php echo esc_html( $spotlight['category_label'] ?? 'Category' ); ?></p>
                                    <h3><?php echo esc_html( $spotlight['primary_label'] ?? $spotlight['film'] ?? '' ); ?></h3>
                                    <?php if ( ! empty( $spotlight['secondary_label'] ) ) : ?>
                                        <p class="lunara-oscars-portal-spotlight-secondary"><?php echo esc_html( $spotlight['secondary_label'] ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $spotlight['year'] ) ) : ?>
                                        <p class="lunara-oscars-portal-spotlight-meta"><?php echo esc_html( $spotlight['year'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( ! empty( $title_cards ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-titles">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Poster-Led Entry Points</p>
                            <h2 class="lunara-home-section-title">Open the ledger through the films themselves.</h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-title-grid">
                        <?php foreach ( $title_cards as $card ) : ?>
                            <?php $card_visual = is_array( $card['visual'] ?? null ) ? $card['visual'] : array(); ?>
                            <a class="lunara-oscars-portal-title-card" href="<?php echo esc_url( $card['url'] ?? $database_url ); ?>">
                                <div class="lunara-oscars-portal-title-media">
                                    <?php if ( ! empty( $card_visual['poster_html'] ) ) : ?>
                                        <?php echo $card_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php elseif ( ! empty( $card_visual['poster_url'] ) ) : ?>
                                        <img src="<?php echo esc_url( $card_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $card['title'] ?? 'Oscar title' ); ?>" loading="lazy" decoding="async" />
                                    <?php elseif ( ! empty( $card_visual['card_fallback_html'] ) ) : ?>
                                        <?php echo $card_visual['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php else : ?>
                                        <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Title</div><div class="aat-fallback-title small"><?php echo esc_html( $card['title'] ?? '' ); ?></div></div></div>
                                    <?php endif; ?>
                                </div>
                                <div class="lunara-oscars-portal-title-copy">
                                    <h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
                                    <?php if ( ! empty( $card['year'] ) ) : ?>
                                        <p class="lunara-oscars-portal-title-year"><?php echo esc_html( $card['year'] ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $card['categories_line'] ) ) : ?>
                                        <p class="lunara-oscars-portal-title-line"><?php echo esc_html( $card['categories_line'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $linked_reviews instanceof WP_Query && $linked_reviews->have_posts() ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-reviews">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Criticism Meets the Ledger</p>
                            <h2 class="lunara-home-section-title"><?php echo esc_html( $reviews_heading ); ?></h2>
                        </div>
                    </div>

                    <div class="lunara-review-grid lunara-review-archive-grid">
                        <?php while ( $linked_reviews->have_posts() ) : $linked_reviews->the_post(); ?>
                            <?php echo lunara_render_review_grid_card( get_the_ID() ); ?>
                        <?php endwhile; ?>
                    </div>
                </section>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <?php if ( ! empty( $deep_cuts ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-deep-cuts">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Rotating Stats</p>
                            <h2 class="lunara-home-section-title"><?php echo esc_html( $deep_cuts_heading ); ?></h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-facts-grid">
                        <?php foreach ( $deep_cuts as $cut ) : ?>
                            <a class="lunara-oscars-portal-fact-card" href="<?php echo esc_url( $cut['url'] ?? $database_url ); ?>">
                                <p class="lunara-oscars-portal-fact-label"><?php echo esc_html( $cut['label'] ?? '' ); ?></p>
                                <strong class="lunara-oscars-portal-fact-value"><?php echo esc_html( $cut['value'] ?? '' ); ?></strong>
                                <?php if ( ! empty( $cut['context'] ) ) : ?>
                                    <p class="lunara-oscars-portal-fact-context"><?php echo esc_html( $cut['context'] ); ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php

    return trim( (string) ob_get_clean() );
}

/**
 * Replace the default page content with the custom Oscars portal.
 */
function lunara_replace_oscars_portal_content( $content ) {
    if ( ! lunara_is_oscars_portal_page() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    return lunara_render_oscars_portal_markup();
}
// The dedicated page-oscars.php template is now the source of truth for /oscars/.
// Keep the helper available, but do not hijack the page content at runtime.

/**
 * Force the Oscars portal to render through a dedicated shell.
 *
 * Some theme/plugin combinations can bypass the normal singular content flow
 * and surface archive-style shells on the /oscars/ page. Rendering directly
 * here keeps the front door stable without touching the database layer.
 */
function lunara_render_oscars_portal_direct() {
    if ( ! lunara_is_oscars_portal_page() || is_admin() || is_feed() || is_embed() || is_preview() ) {
        return;
    }

    get_header();
    ?>
    <main id="main" class="site-main">
        <?php echo lunara_render_oscars_portal_markup(); ?>
    </main>
    <?php
    get_footer();
    exit;
}
// The dedicated page-oscars.php template is now the source of truth for /oscars/.
// Keep the helper available, but do not short-circuit normal template loading.

/**
 * Add a body class so the portal can be styled without relying on generic page shells.
 */
function lunara_oscars_portal_body_class( $classes ) {
    if ( lunara_is_oscars_portal_page() ) {
        $classes[] = 'lunara-oscars-portal-page';
    }

    return $classes;
}
add_filter( 'body_class', 'lunara_oscars_portal_body_class' );

if ( ! function_exists( 'lunara_render_oscars_prediction_board' ) ) {
	/**
	 * The Board — the desk's predictions for the upcoming ceremony, as a
	 * dense ranked ledger (category → call → status), not another card
	 * grid. Powered by the same lunara_oscar_pick posts that feed the
	 * homepage carousel; publishing a pick updates both surfaces. Empty
	 * board renders nothing, so the portal degrades cleanly off-season.
	 */
	function lunara_render_oscars_prediction_board() {
		if ( ! function_exists( 'lunara_get_oscar_picks' ) ) {
			return '';
		}

		$picks = lunara_get_oscar_picks( array( 'posts_per_page' => 30 ) );
		$posts = ( $picks instanceof WP_Query ) ? $picks->posts : (array) $picks;
		if ( empty( $posts ) ) {
			return '';
		}

		$ceremony_year = 0;
		$rows          = array();
		foreach ( $posts as $pick ) {
			$pick_id  = $pick instanceof WP_Post ? $pick->ID : absint( $pick );
			$terms    = get_the_terms( $pick_id, 'oscar_pick_category' );
			$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
			$film     = trim( (string) get_post_meta( $pick_id, '_lunara_pick_film', true ) );
			$person   = trim( (string) get_post_meta( $pick_id, '_lunara_pick_person', true ) );
			$status   = sanitize_key( (string) get_post_meta( $pick_id, '_lunara_pick_status', true ) );
			$url      = trim( (string) get_post_meta( $pick_id, '_lunara_pick_oscar_entity_url', true ) );
			$year     = absint( get_post_meta( $pick_id, '_lunara_pick_ceremony_year', true ) );
			if ( $year > $ceremony_year ) {
				$ceremony_year = $year;
			}
			$call = '' !== $person ? $person : $film;
			if ( '' === $call ) {
				$call = html_entity_decode( get_the_title( $pick_id ), ENT_QUOTES, 'UTF-8' );
			}
			$rows[] = array(
				'category' => $category,
				'call'     => $call,
				'film'     => ( '' !== $person && '' !== $film ) ? $film : '',
				'status'   => $status,
				'url'      => $url,
			);
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$heading = $ceremony_year
			? sprintf( /* translators: %d: ceremony year */ __( 'The desk calls the %d ceremony, category by category.', 'lunara-film' ), $ceremony_year )
			: __( 'The desk calls the next ceremony, category by category.', 'lunara-film' );

		ob_start();
		?>
		<section id="oscars-board" class="lunara-home-section lunara-oscars-board lunara-oscars-portal-slot-board" aria-label="<?php esc_attr_e( 'Prediction board', 'lunara-film' ); ?>">
			<div class="lunara-home-section-header">
				<div>
					<p class="lunara-home-section-kicker"><?php esc_html_e( 'The Board', 'lunara-film' ); ?></p>
					<h2 class="lunara-oscars-board-title"><?php echo esc_html( $heading ); ?></h2>
				</div>
			</div>
			<ol class="lunara-oscars-board-list">
				<?php foreach ( $rows as $row ) : ?>
					<li class="lunara-oscars-board-row<?php echo '' !== $row['status'] ? ' is-status-' . esc_attr( $row['status'] ) : ''; ?>">
						<span class="lunara-oscars-board-category"><?php echo esc_html( $row['category'] ); ?></span>
						<span class="lunara-oscars-board-call">
							<?php if ( '' !== $row['url'] ) : ?>
								<a href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['call'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $row['call'] ); ?>
							<?php endif; ?>
							<?php if ( '' !== $row['film'] ) : ?>
								<em><?php echo esc_html( $row['film'] ); ?></em>
							<?php endif; ?>
						</span>
						<?php if ( '' !== $row['status'] ) : ?>
							<span class="lunara-oscars-board-status"><?php echo esc_html( strtoupper( $row['status'] ) ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<p class="lunara-oscars-board-note"><?php esc_html_e( 'Calls move as the season moves — argued by the desk, revised in the open, settled on ceremony night.', 'lunara-film' ); ?></p>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}
