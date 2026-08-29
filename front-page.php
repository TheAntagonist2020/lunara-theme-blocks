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

<div id="primary" class="site-main lunara-front-page">
	<?php
	$lunara_front_door = '';
	if ( function_exists( 'lunara_render_home_front_door' ) ) {
		$lunara_front_door = (string) lunara_render_home_front_door();
	}
	$lunara_front_door_has_canonical_hero = '' !== $lunara_front_door && false !== strpos( $lunara_front_door, 'data-lunara-home-hero-source=' );

	$lunara_uses_block_composition = function_exists( 'lunara_home_uses_block_composition' ) && lunara_home_uses_block_composition();
	$lunara_block_composition      = $lunara_uses_block_composition && function_exists( 'lunara_render_home_block_composition' )
		? (string) lunara_render_home_block_composition( $lunara_front_door_has_canonical_hero ? array( 'lunara/cinematic-hero' ) : array() )
		: '';

	// Keep Home from shipping without a semantic H1, independent of whichever
	// editable front-door renderer or block composition is active. Existing
	// authored H1s remain authoritative; otherwise this screen-reader heading
	// adds semantics without moving layout. The structured parser avoids false
	// matches in scripts or comments; the regex is only for WordPress versions
	// predating that parser.
	$lunara_home_has_h1  = false;
	$lunara_heading_parts = array( $lunara_front_door, $lunara_block_composition );
	foreach ( $lunara_heading_parts as $lunara_heading_part ) {
		if ( '' === $lunara_heading_part ) {
			continue;
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$lunara_heading_processor = new WP_HTML_Tag_Processor( $lunara_heading_part );
			$lunara_home_has_h1       = $lunara_heading_processor->next_tag( array( 'tag_name' => 'H1' ) );
		} else {
			$lunara_home_has_h1 = (bool) preg_match( '/<h1(?:\s|>)/i', $lunara_heading_part );
		}

		if ( $lunara_home_has_h1 ) {
			break;
		}
	}

	if ( ! $lunara_home_has_h1 ) {
		?>
		<h1 class="screen-reader-text lunara-screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Lunara Film', 'lunara-film' ) ); ?></h1>
		<?php
	}

	if ( '' !== $lunara_front_door ) {
		echo $lunara_front_door; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/*
	 * Hybrid composition (3.1.50): when the Home page carries Lunara section
	 * blocks, the blocks ARE the homepage — order is block order, presence is
	 * visibility, and compact editor cards link back to the public result. With no
	 * section blocks present, the Customizer registry below renders exactly
	 * as before (which is also the rollback: remove the blocks, this resumes).
	 */
	if ( $lunara_uses_block_composition ) {
		echo $lunara_block_composition; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_reset_postdata();
		?>
</div>

<?php
		get_footer();
		return;
	}

	/*
	 * Homepage section slug => render callback. Each callback returns a
	 * self-contained <section class="lunara-home-section lunara-home-slot-{slug}">
	 * (its own escaping). Only slugs with a genuine renderer are listed.
	 */
	$lunara_section_renderers = array(
		'hero'           => 'lunara_render_cinematic_hero_carousel',
		'latest-reviews' => 'lunara_render_homepage_latest_reviews',
		'pairing-desk'   => 'lunara_render_home_pairing_desk',
		'dispatch'       => 'lunara_render_homepage_journal_lane',
		'oscar-picks'    => 'lunara_render_oscar_picks_carousel',
		'oscar-facts'    => 'lunara_render_oscar_facts_carousel',
	);

	// The cinematic front-door wrapper already contains the canonical Home
	// hero. Do not render the legacy Customizer hero a second time beneath it.
	// A non-cinematic Front Desk keeps the legacy hero available, preserving
	// the existing fallback when the cinematic front door is disabled.
	if ( $lunara_front_door_has_canonical_hero ) {
		unset( $lunara_section_renderers['hero'] );
	}

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
			if ( 'hero' === $lunara_slug ) {
				echo call_user_func( $lunara_callback, array( 'first_image_is_lcp' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo call_user_func( $lunara_callback ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	wp_reset_postdata();
	?>
</div>

<?php
get_footer();
