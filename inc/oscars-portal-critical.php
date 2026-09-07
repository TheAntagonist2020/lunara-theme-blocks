<?php
/**
 * Oscars portal first-paint geometry.
 *
 * The portal template already emits the exact saved slot order in the DOM.
 * This compact route-scoped seed reserves that geometry before an optimizer's
 * deferred aggregate can settle. The slot `order:initial` rule is purely
 * forward-defensive: no numeric `order` declarations exist in any portal
 * layer today, and the rule keeps CSS from ever becoming a second order
 * owner if one appears. The container declarations are calibrated to the
 * standing body.lunara-oscars-portal-page authority in lunara-shell.css
 * (grid, minmax(0,1fr) column, min(1720px, calc(100vw - 48px)) width) so
 * the seed reserves the true final geometry instead of flipping it; the
 * cacheable route stylesheet remains the complete visual authority.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_oscars_portal_resolved_label_font_slug' ) ) {
	/**
	 * Resolve the Studio label role without allowing one malformed field to
	 * corrupt the rest of the design-token document.
	 *
	 * @return string
	 */
	function lunara_oscars_portal_resolved_label_font_slug() {
		$default = 'tiempos-text';
		$tokens  = function_exists( 'lunara_get_design_tokens' ) ? lunara_get_design_tokens() : array();
		$fonts   = isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array();
		$slug    = isset( $fonts['label'] ) && is_scalar( $fonts['label'] ) ? (string) $fonts['label'] : $default;
		$choices = function_exists( 'lunara_design_token_font_choices' ) ? lunara_design_token_font_choices() : array();

		return is_array( $choices ) && isset( $choices[ $slug ] ) ? $slug : $default;
	}
}

if ( ! function_exists( 'lunara_oscars_portal_uses_tiempos_label_face' ) ) {
	/**
	 * Whether the default Studio label role should use the licensed route face.
	 *
	 * @return bool
	 */
	function lunara_oscars_portal_uses_tiempos_label_face() {
		return 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug();
	}
}

if ( ! function_exists( 'lunara_oscars_portal_minify_structural_css' ) ) {
	function lunara_oscars_portal_minify_structural_css( $css ) {
		$css = preg_replace( '#/\*[^!][\s\S]*?\*/#', '', (string) $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
		$css = str_replace( ';}', '}', (string) $css );
		return trim( (string) $css );
	}
}

if ( ! function_exists( 'lunara_oscars_portal_variable_css' ) ) {
	/**
	 * Compose the portal geometry custom properties from the resolved config.
	 *
	 * Provenance-gated exactly like the template root stamp: variables emit
	 * only inside an authorized private preview or after an explicit Studio
	 * save. A site that never saved the Studio emits nothing here and keeps
	 * the shipped clamp geometry through the seed fallbacks.
	 *
	 * @param array<string,mixed> $config Resolved Oscars Portal Studio config.
	 * @return string
	 */
	function lunara_oscars_portal_variable_css( $config ) {
		$declarations = lunara_oscars_portal_variable_declarations( $config );

		return '' === $declarations
			? ''
			: '#primary.lunara-oscars-portal{' . $declarations . '}';
	}
}

if ( ! function_exists( 'lunara_oscars_portal_rhythm_value_maps' ) ) {
	/**
	 * Calibrated values behind each rhythm choice.
	 *
	 * The Studio validates against `lunara_oscars_portal_studio_rhythm_specs()`
	 * and these maps must carry exactly those keys — a choice that validates but
	 * has no value here would save cleanly and change nothing, which is the
	 * defect class that left `card_min_height` inert for its whole existence.
	 * `tests/oscars-portal-fluid-contract.ps1` pins the two key sets equal.
	 *
	 * @return array<string,array<string,string>>
	 */
	function lunara_oscars_portal_rhythm_value_maps() {
		return array(
			'density'         => array(
				'standard' => '16px',
				'compact'  => '12px',
				'showcase' => '22px',
			),
			'lead_prominence' => array(
				'balanced' => 'minmax(0,1.25fr) minmax(260px,clamp(300px,24vw,440px))',
				'feature'  => 'minmax(0,1.6fr) minmax(220px,clamp(260px,20vw,360px))',
				'gallery'  => 'minmax(0,1fr) minmax(320px,clamp(360px,30vw,520px))',
			),
			'board_rhythm'    => array(
				'standard' => '190px',
				'gallery'  => '230px',
				'dense'    => '150px',
			),
		);
	}
}

if ( ! function_exists( 'lunara_oscars_portal_variable_declarations' ) ) {
	/**
	 * Compose the portal custom-property declarations from a resolved config.
	 *
	 * Single source for both emitters: the route seed wraps these in
	 * `#primary.lunara-oscars-portal{…}`, and `page-oscars.php` stamps the same
	 * string onto the root's style attribute. They previously carried separate
	 * copies of the property list and gating rules, so adding a control meant
	 * editing both and any divergence was silent.
	 *
	 * Fail-closed and all-or-nothing: a single missing or non-scalar value emits
	 * nothing, so an unsaved site keeps the shipped clamp geometry through the
	 * seed's var() fallbacks rather than a half-stamped root.
	 *
	 * @param array<string,mixed> $config Resolved Oscars Portal Studio config.
	 * @return string Declarations without a trailing semicolon, or ''.
	 */
	function lunara_oscars_portal_variable_declarations( $config ) {
		$config       = is_array( $config ) ? $config : array();
		$presentation = isset( $config['presentation'] ) && is_array( $config['presentation'] )
			? $config['presentation']
			: array();

		$provenance = ! empty( $config['_preview'] )
			|| ( function_exists( 'lunara_oscars_portal_studio_has_saved_presentation' ) && lunara_oscars_portal_studio_has_saved_presentation() );

		if ( ! $provenance ) {
			return '';
		}

		$numbers = array(
			'section_gap'       => '--lunara-oscars-portal-section-gap',
			'hero_min_height'   => '--lunara-oscars-portal-hero-min-height',
			'card_min_height'   => '--lunara-oscars-portal-card-min-height',
			'winners_min_width' => '--lunara-oscars-portal-winners-min-width',
		);
		$choices = array(
			'density'         => '--lunara-oscars-portal-grid-gap',
			'lead_prominence' => '--lunara-oscars-portal-hero-columns',
			'board_rhythm'    => '--lunara-oscars-portal-board-min-width',
		);

		$maps  = lunara_oscars_portal_rhythm_value_maps();
		$parts = array();

		foreach ( $numbers as $key => $property ) {
			if ( ! isset( $presentation[ $key ] ) || ! is_scalar( $presentation[ $key ] ) ) {
				return '';
			}
			$parts[] = $property . ':' . absint( $presentation[ $key ] ) . 'px';
		}

		foreach ( $choices as $key => $property ) {
			if ( ! isset( $presentation[ $key ] ) || ! is_scalar( $presentation[ $key ] ) ) {
				return '';
			}
			$choice = sanitize_key( (string) $presentation[ $key ] );
			if ( ! isset( $maps[ $key ][ $choice ] ) ) {
				return '';
			}
			$parts[] = $property . ':' . $maps[ $key ][ $choice ];
		}

		return implode( ';', $parts );
	}
}

if ( ! function_exists( 'lunara_oscars_portal_critical_css' ) ) {
	/**
	 * Compose the synchronous route geometry seed.
	 *
	 * Selector altitude is deliberate: `#primary.lunara-oscars-portal` outranks
	 * every migrated portal layer, so this seed is the standing order-and-shell
	 * authority while var() fallbacks reproduce the shipped clamp geometry on
	 * sites that never saved the Studio. Breakpoints (1500 up, 1120/900/820/782/520) are
	 * the exact geometry breakpoints of the migrated portal CSS.
	 *
	 * @return string
	 */
	function lunara_oscars_portal_critical_css() {
		$css = <<<'CSS'
#primary.lunara-oscars-portal{box-sizing:border-box;display:grid!important;grid-template-columns:minmax(0,1fr)!important;justify-items:stretch!important;gap:var(--lunara-oscars-portal-section-gap,clamp(34px,4.8vw,58px))!important;margin-inline:auto!important;max-width:1720px!important;min-width:0!important;overflow-x:clip!important;padding:clamp(14px,2.4vw,30px) clamp(18px,3vw,40px) clamp(48px,6vw,90px)!important;width:min(1720px,calc(100vw - 48px))!important}
#primary.lunara-oscars-portal>:is([class*="lunara-oscars-portal-slot-"],.lunara-oscars-navigator){box-sizing:border-box;max-width:100%;min-width:0;order:initial!important}
#primary.lunara-oscars-portal>.lunara-home-section{max-width:100%!important;min-width:0!important;overflow:hidden!important;width:100%!important}
#primary.lunara-oscars-portal>.lunara-oscars-portal-slot-hero{min-height:var(--lunara-oscars-portal-hero-min-height,clamp(360px,42vh,540px))!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{align-items:center!important;display:grid!important;gap:clamp(20px,3vw,34px)!important;grid-template-columns:var(--lunara-oscars-portal-hero-columns,minmax(0,1.25fr) minmax(260px,clamp(300px,24vw,440px)))!important;min-height:inherit!important;padding:clamp(16px,2.2vw,32px)!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{display:grid!important;gap:10px!important;grid-template-columns:repeat(4,minmax(0,1fr))!important}
#primary.lunara-oscars-portal .lunara-oscars-command-rail{display:grid!important;gap:10px!important;grid-column:1/-1!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;min-width:0!important;width:100%!important}
#primary.lunara-oscars-portal .lunara-oscars-board-list{display:grid!important;gap:clamp(8px,.8vw,14px)!important;grid-template-columns:repeat(auto-fill,minmax(min(100%,var(--lunara-oscars-portal-board-min-width,190px)),1fr))!important;list-style:none!important;margin:clamp(18px,2vw,28px) 0 0!important;padding:0!important}
#primary.lunara-oscars-portal .lunara-oscars-board-row{align-content:space-between!important;align-items:start!important;aspect-ratio:2/3!important;display:grid!important;gap:8px 10px!important;grid-template-areas:"status" "category" "call"!important;grid-template-columns:minmax(0,1fr)!important;grid-template-rows:auto auto 1fr!important;min-width:0!important;overflow:hidden!important;padding:clamp(10px,.9vw,16px)!important;position:relative!important}
#primary.lunara-oscars-portal .lunara-oscars-board-art{display:block!important;inset:0!important;margin:0!important;position:absolute!important}
#primary.lunara-oscars-portal .lunara-oscars-board-art img{display:block!important;height:100%!important;object-fit:cover!important;width:100%!important}
#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){display:grid!important;gap:var(--lunara-oscars-portal-grid-gap,16px)!important;grid-template-columns:repeat(4,minmax(0,1fr))!important}
#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-research-card-grid)>*{min-height:var(--lunara-oscars-portal-card-min-height,0)!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{display:grid!important;gap:var(--lunara-oscars-portal-grid-gap,16px)!important;grid-template-columns:repeat(5,minmax(0,1fr))!important}
#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{display:grid!important;gap:clamp(10px,1vw,16px)!important;grid-template-columns:repeat(auto-fill,minmax(min(100%,var(--lunara-oscars-portal-winners-min-width,200px)),1fr))!important}
@media(min-width:1500px){#primary.lunara-oscars-portal .lunara-oscars-portal-spotlight-grid{grid-template-columns:repeat(6,minmax(0,1fr))!important}}
@media(max-width:1120px){#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{grid-template-columns:minmax(0,1fr) minmax(220px,280px)!important}#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{grid-template-columns:repeat(auto-fill,minmax(min(100%,150px),1fr))!important}}
@media(max-width:900px){#primary.lunara-oscars-portal{max-width:100vw!important}#primary.lunara-oscars-portal>.lunara-home-section{min-height:auto!important}#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{gap:16px!important;grid-template-columns:minmax(0,1fr)!important;padding:0!important}}
@media(max-width:820px){#primary.lunara-oscars-portal{gap:var(--lunara-oscars-portal-section-gap,34px)!important;padding:12px 14px 52px!important}#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-title-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){gap:12px!important;grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-research-card-grid{grid-template-columns:minmax(0,1fr)!important}#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-command-rail{grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{gap:8px!important;grid-template-columns:repeat(2,minmax(0,1fr))!important}}
@media(max-width:782px){#primary.lunara-oscars-portal .lunara-oscars-board-list{gap:8px!important;grid-template-columns:repeat(auto-fill,minmax(min(100%,136px),1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-board-row{gap:6px 8px!important;padding:9px!important}}
@media(max-width:520px){#primary.lunara-oscars-portal{padding-left:10px!important;padding-right:10px!important}#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{gap:8px!important}#primary.lunara-oscars-portal .lunara-oscars-command-rail{gap:8px!important;grid-template-columns:minmax(0,1fr)!important}#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{grid-template-columns:minmax(0,1fr)!important}}
CSS;

		return lunara_oscars_portal_minify_structural_css( $css );
	}
}
