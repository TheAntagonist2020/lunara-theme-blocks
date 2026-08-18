<?php
/**
 * Oscars ledger route first-paint geometry and Dossier variable assembly.
 *
 * The plugin-owned ledger routes (ceremony/category/title/person/company —
 * every `body.aat-shell-page` request) paint from three plugin stylesheets
 * (aat-styles → aat-ceremony-dossier → aat-hub-polish) that an optimizer may
 * defer, plus the theme's inline priority-1002 Dossier emitter that cannot be
 * deferred. This module owns the two synchronous theme layers that close that
 * gap on ledger routes:
 *
 *   1. lunara_oscars_ledger_variable_css() — the pure assembly step for the
 *      `--lunara-oscars-*` custom properties. The theme-mod READS stay in
 *      inc/frontend.php (the Dossier Studio controls contract greps that file
 *      for every lunara_oscars_dossier_* key); frontend passes the resolved
 *      raw control values in and this function applies the derived maps. The
 *      maps mirror lunara_output_oscars_dossier_studio_css exactly — if a map
 *      changes there it must change here in the same commit.
 *   2. lunara_oscars_ledger_critical_css() — a compact structural seed that
 *      reserves the plugin's own geometry (container width, hub header grid,
 *      hub/winner-circle grid columns, entity/ceremony hero columns) before a
 *      deferred plugin aggregate settles. Every metric is copied from the
 *      plugin's stylesheets (academy-awards-table.css, ceremony-dossier.css);
 *      nothing here is invented, and the plugin files remain untouched.
 *
 * It also owns the ledger label-face marker: a body class the overlay
 * stylesheet keys its licensed Tiempos label rules on, resolved from the same
 * Design Tokens label role as the portal's template-root marker. The plugin
 * applies `body_class` on its virtual routes (get_header() in both templates),
 * so a theme-side body_class filter is the sanctioned wrapper hook.
 *
 * Everything in this module is edge-cache safe: theme mods and design tokens
 * only — no nonces, no cookies, no user-conditional output.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_oscars_ledger_minify_css' ) ) {
	/**
	 * Collapse authored CSS to its wire form. Same transform as the portal
	 * seed minifier; duplicated so this module stays standalone-loadable.
	 *
	 * @param string $css Authored CSS.
	 * @return string
	 */
	function lunara_oscars_ledger_minify_css( $css ) {
		$css = preg_replace( '#/\*[^!][\s\S]*?\*/#', '', (string) $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
		$css = str_replace( ';}', '}', (string) $css );
		return trim( (string) $css );
	}
}

if ( ! function_exists( 'lunara_oscars_ledger_uses_tiempos_label_face' ) ) {
	/**
	 * Whether ledger-route labels should engage the licensed Tiempos face.
	 *
	 * Delegates to the portal's Design Tokens resolver so both Oscars route
	 * families answer from the same label role. Fails closed: without the
	 * resolver the marker never appears and the overlay's device-serif
	 * fallbacks never engage Tiempos.
	 *
	 * @return bool
	 */
	function lunara_oscars_ledger_uses_tiempos_label_face() {
		return function_exists( 'lunara_oscars_portal_uses_tiempos_label_face' )
			&& lunara_oscars_portal_uses_tiempos_label_face();
	}
}

if ( ! function_exists( 'lunara_oscars_ledger_body_classes' ) ) {
	/**
	 * Stamp the resolver-driven label-face marker on ledger route bodies.
	 *
	 * Strictly additive next to the plugin's own `aat-shell-page` class; the
	 * overlay stylesheet scopes every Tiempos rule to the chained pair so the
	 * marker is inert anywhere else. Design-Tokens state only — identical for
	 * every visitor, so anonymous cacheability is preserved.
	 *
	 * @param array<int,string> $classes Body classes.
	 * @return array<int,string>
	 */
	function lunara_oscars_ledger_body_classes( $classes ) {
		$classes = (array) $classes;

		if (
			function_exists( 'lunara_is_oscars_ledger_route' )
			&& lunara_is_oscars_ledger_route()
			&& lunara_oscars_ledger_uses_tiempos_label_face()
			&& ! in_array( 'lunara-label-font-tiempos', $classes, true )
		) {
			$classes[] = 'lunara-label-font-tiempos';
		}

		return $classes;
	}
	add_filter( 'body_class', 'lunara_oscars_ledger_body_classes' );
}

if ( ! function_exists( 'lunara_oscars_ledger_variable_css' ) ) {
	/**
	 * Compose the ledger Dossier custom properties from resolved raw values.
	 *
	 * Pure assembly: no theme-mod reads, no state writes, deterministic on
	 * its input. The caller (inc/frontend.php) resolves every control through
	 * the Dossier Studio helpers so the key reads stay in that file for the
	 * dossier-controls contract; unknown or missing values degrade to the
	 * same defaults the priority-1002 emitter uses.
	 *
	 * @param array<string,mixed> $values Resolved raw control values:
	 *     density, major_race_prominence, profile_scale,
	 *     profile_media_treatment, writeup_prominence,
	 *     related_reviews_treatment, title_image_focus (select slugs);
	 *     section_gap, card_min, profile_media_width, profile_media_height,
	 *     related_reviews_count (bounded integers).
	 * @return string
	 */
	function lunara_oscars_ledger_variable_css( $values ) {
		$values = is_array( $values ) ? $values : array();

		$select = static function ( $key, $default ) use ( $values ) {
			return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? (string) $values[ $key ] : $default;
		};
		$number = static function ( $key, $default ) use ( $values ) {
			return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? absint( $values[ $key ] ) : $default;
		};

		$density                   = $select( 'density', 'balanced' );
		$major_race_prominence     = $select( 'major_race_prominence', 'standard' );
		$profile_scale             = $select( 'profile_scale', 'standard' );
		$profile_media_treatment   = $select( 'profile_media_treatment', 'poster-frame' );
		$writeup_prominence        = $select( 'writeup_prominence', 'inline' );
		$related_reviews_treatment = $select( 'related_reviews_treatment', 'standard-grid' );
		$title_image_focus         = $select( 'title_image_focus', 'center-center' );

		// Derived maps — mirrored from lunara_output_oscars_dossier_studio_css.
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

		$declarations = array(
			'--lunara-oscars-dossier-section-gap'          => $number( 'section_gap', 48 ) . 'px',
			'--lunara-oscars-dossier-card-min'             => $number( 'card_min', 280 ) . 'px',
			'--lunara-oscars-dossier-density-scale'        => isset( $density_scale_map[ $density ] ) ? $density_scale_map[ $density ] : '1',
			'--lunara-oscars-dossier-hero-max'             => isset( $hero_max_map[ $density ] ) ? $hero_max_map[ $density ] : '1040px',
			'--lunara-oscars-profile-media-max'            => $number( 'profile_media_width', 340 ) . 'px',
			'--lunara-oscars-profile-media-scale-max'      => isset( $profile_media_map[ $profile_scale ] ) ? $profile_media_map[ $profile_scale ] : '340px',
			'--lunara-oscars-profile-media-width'          => $number( 'profile_media_width', 340 ) . 'px',
			'--lunara-oscars-profile-media-height'         => $number( 'profile_media_height', 500 ) . 'px',
			'--lunara-oscars-profile-media-fit'            => isset( $profile_media_fit_map[ $profile_media_treatment ] ) ? $profile_media_fit_map[ $profile_media_treatment ] : 'cover',
			'--lunara-oscars-profile-media-aspect'         => isset( $profile_media_aspect_map[ $profile_media_treatment ] ) ? $profile_media_aspect_map[ $profile_media_treatment ] : '2 / 3',
			'--lunara-oscars-writeup-max'                  => isset( $writeup_max_map[ $writeup_prominence ] ) ? $writeup_max_map[ $writeup_prominence ] : '880px',
			'--lunara-oscars-major-race-gap'               => isset( $race_gap_map[ $major_race_prominence ] ) ? $race_gap_map[ $major_race_prominence ] : '18px',
			'--lunara-oscars-related-review-min'           => isset( $related_review_min_map[ $related_reviews_treatment ] ) ? $related_review_min_map[ $related_reviews_treatment ] : '280px',
			'--lunara-oscars-related-review-media-aspect'  => isset( $related_media_aspect_map[ $related_reviews_treatment ] ) ? $related_media_aspect_map[ $related_reviews_treatment ] : '16 / 10',
			'--lunara-oscars-related-review-count'         => (string) $number( 'related_reviews_count', 6 ),
			'--lunara-oscars-image-focus'                  => isset( $image_focus_map[ $title_image_focus ] ) ? $image_focus_map[ $title_image_focus ] : 'center center',
		);

		$pairs = array();
		foreach ( $declarations as $property => $value ) {
			$pairs[] = $property . ':' . $value;
		}

		return 'body.aat-shell-page{' . implode( ';', $pairs ) . '}';
	}
}

if ( ! function_exists( 'lunara_oscars_ledger_critical_css' ) ) {
	/**
	 * Compose the synchronous ledger route geometry seed.
	 *
	 * Reserves only what the deferred stylesheets would otherwise leave
	 * unpainted: container width/padding, the hub header grid, the
	 * hub/winner-circle/crossroads/profile grid columns, and the entity and
	 * ceremony hero two-column shells. Calibration source for every contested
	 * property is assets/css/lunara-shell.css: the plugin CSS declares these
	 * first, but the shell's body.aat-shell-page !important layer wins each
	 * contest, so the seed must reserve the SHELL's final values or first
	 * paint flips when the shell arrives. Values, verified against
	 * lunara-shell.css's last-wins cascade:
	 *   - .aat-container max-width min(1200px, calc(100vw - 36px)),
	 *     padding clamp(22px,3vw,42px) (16px @560)   — lunara-shell.css
	 *   - .aat-hub-header grid, clamp(18px,3vw,36px) gap
	 *                                                — lunara-shell.css
	 *   - .aat-entity-hero minmax(150px,210px)/minmax(0,1fr),
	 *     gap clamp(16px,2vw,24px), align-items start (@900 → 1fr)
	 *                                                — lunara-shell.css
	 *   - .aat-hub-grid minmax(240px,1fr) base       — academy-awards-table.css
	 *   - .aat-winner-circle-grid minmax(290px,1fr)  — academy-awards-table.css
	 *   - .aat-ceremony-dossier(-hero) columns/gaps (@980 → 1fr)
	 *                                                — ceremony-dossier.css
	 * Grid columns and gaps consume the `--lunara-oscars-dossier-*` variables
	 * (printed one wp_head priority earlier) exactly as the priority-1002
	 * emitter later enforces them, with the plugin's shipped metrics as var()
	 * fallbacks — so first paint, deferred-CSS paint, and final cascade agree.
	 * No !important: the plugin files and the 1002 emitter stay the authority
	 * once they arrive.
	 *
	 * @return string
	 */
	function lunara_oscars_ledger_critical_css() {
		$css = <<<'CSS'
body.aat-shell-page .aat-container{box-sizing:border-box;margin-left:auto;margin-right:auto;max-width:min(1200px,calc(100vw - 36px));padding:clamp(22px,3vw,42px)}
body.aat-shell-page .aat-hub-header{display:grid;gap:clamp(18px,3vw,36px);margin-bottom:30px;padding-bottom:22px;text-align:center}
body.aat-shell-page .aat-hub-grid,
body.aat-shell-page .aat-crossroads-grid,
body.aat-shell-page .aat-profile-grid{display:grid;gap:calc(18px * var(--lunara-oscars-dossier-density-scale,1));grid-template-columns:repeat(auto-fit,minmax(min(100%,var(--lunara-oscars-dossier-card-min,240px)),1fr))}
body.aat-shell-page .aat-winner-circle-grid{display:grid;gap:calc(18px * var(--lunara-oscars-dossier-density-scale,1));grid-template-columns:repeat(auto-fit,minmax(min(100%,var(--lunara-oscars-dossier-card-min,290px)),1fr))}
body.aat-shell-page .aat-hub-grid{margin-top:18px}
body.aat-shell-page .aat-entity-hero{align-items:start;display:grid;gap:clamp(16px,2vw,24px);grid-template-columns:minmax(150px,210px) minmax(0,1fr)}
body.aat-shell-page .aat-ceremony-dossier{display:grid;gap:clamp(20px,3vw,34px)}
body.aat-shell-page .aat-ceremony-dossier-hero{align-items:stretch;display:grid;gap:clamp(18px,3vw,32px);grid-template-columns:minmax(0,1.08fr) minmax(300px,.92fr)}
@media (max-width:980px){
body.aat-shell-page .aat-ceremony-dossier-hero{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:900px){
body.aat-shell-page .aat-entity-hero{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:560px){
body.aat-shell-page .aat-container{padding:16px}
}
CSS;

		return lunara_oscars_ledger_minify_css( $css );
	}
}
