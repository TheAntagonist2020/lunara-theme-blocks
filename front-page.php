<?php
/**
 * Front Page — Customizer-driven homepage sections.
 *
 * The homepage is composed from the Lunara "Homepage Sections" Customizer
 * controls: each section renders in the configured Section Order and only when
 * its "Show X" toggle is on. The Customizer is the single source of truth —
 * flip a toggle and the section appears or hides; no block content or code
 * snippets required.
 *
 * Only registry slugs that have a real homepage renderer are wired here. The
 * data-only slugs (featured, oscar-spotlight, database, ledger, deep-cuts) are
 * intentionally skipped until renderers exist, so their toggles stay inert
 * rather than fataling.
 *
 * History: replaced the 2026-05-10 "Path B" template, which rendered frozen
 * Gutenberg block content from the Home page and ignored these Customizer
 * controls entirely (so "Show Hero" did nothing).
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main lunara-front-page">
	<?php
	if ( function_exists( 'lunara_render_home_front_door' ) ) {
		echo lunara_render_home_front_door(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		?>
		<h1 class="screen-reader-text lunara-screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Lunara Film', 'lunara-film' ) ); ?></h1>
		<?php
	}

	/*
	 * Homepage section slug => render callback. Each callback returns a
	 * self-contained <section class="lunara-home-section lunara-home-slot-{slug}">
	 * (its own escaping). Only slugs with a genuine renderer are listed.
	 */
	$lunara_section_renderers = array(
		'hero'           => 'lunara_render_cinematic_hero_carousel',
		'latest-reviews' => 'lunara_render_homepage_latest_reviews',
		'dispatch'       => 'lunara_render_homepage_journal_lane',
		'oscar-picks'    => 'lunara_render_oscar_picks_carousel',
		'oscar-facts'    => 'lunara_render_oscar_facts_carousel',
	);

	// Order the renderable sections by the Customizer "Section Order" setting.
	$lunara_order_map    = function_exists( 'lunara_get_home_section_order_map' ) ? lunara_get_home_section_order_map() : array();
	$lunara_render_slugs = array_keys( $lunara_section_renderers );
	usort(
		$lunara_render_slugs,
		static function ( $a, $b ) use ( $lunara_order_map ) {
			$order_a = isset( $lunara_order_map[ $a ] ) ? (int) $lunara_order_map[ $a ] : 99;
			$order_b = isset( $lunara_order_map[ $b ] ) ? (int) $lunara_order_map[ $b ] : 99;
			return $order_a <=> $order_b;
		}
	);

	// Render each enabled section in order; an off toggle hides it.
	foreach ( $lunara_render_slugs as $lunara_slug ) {
		$lunara_enabled = function_exists( 'lunara_home_section_is_enabled' ) ? lunara_home_section_is_enabled( $lunara_slug ) : true;
		if ( ! $lunara_enabled ) {
			continue;
		}

		$lunara_callback = $lunara_section_renderers[ $lunara_slug ];
		if ( function_exists( $lunara_callback ) ) {
			echo call_user_func( $lunara_callback ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	wp_reset_postdata();
	?>
</main>

<?php
get_footer();
