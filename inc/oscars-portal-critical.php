<?php
/**
 * Oscars portal first-paint geometry.
 *
 * The portal template already emits the exact saved slot order in the DOM.
 * This compact route-scoped seed reserves that geometry before an optimizer's
 * deferred aggregate can settle, and deliberately neutralizes historic
 * numeric `order` declarations so CSS can never become a second order owner.
 * Every metric derives from the migrated portal layers (the former
 * priority-1001 emitter, the lunara-shell Prediction Board block); the
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
		$config       = is_array( $config ) ? $config : array();
		$presentation = isset( $config['presentation'] ) && is_array( $config['presentation'] )
			? $config['presentation']
			: array();

		$provenance = ! empty( $config['_preview'] )
			|| ( function_exists( 'lunara_oscars_portal_studio_has_saved_presentation' ) && lunara_oscars_portal_studio_has_saved_presentation() );

		if (
			! $provenance
			|| ! isset( $presentation['section_gap'], $presentation['hero_min_height'], $presentation['card_min_height'] )
			|| ! is_scalar( $presentation['section_gap'] ) || ! is_scalar( $presentation['hero_min_height'] ) || ! is_scalar( $presentation['card_min_height'] )
		) {
			return '';
		}

		return sprintf(
			'#primary.lunara-oscars-portal{--lunara-oscars-portal-section-gap:%1$dpx;--lunara-oscars-portal-hero-min-height:%2$dpx;--lunara-oscars-portal-card-min-height:%3$dpx}',
			absint( $presentation['section_gap'] ),
			absint( $presentation['hero_min_height'] ),
			absint( $presentation['card_min_height'] )
		);
	}
}

if ( ! function_exists( 'lunara_oscars_portal_critical_css' ) ) {
	/**
	 * Compose the synchronous route geometry seed.
	 *
	 * Selector altitude is deliberate: `#primary.lunara-oscars-portal` outranks
	 * every migrated portal layer, so this seed is the standing order-and-shell
	 * authority while var() fallbacks reproduce the shipped clamp geometry on
	 * sites that never saved the Studio. Breakpoints (1120/900/820/782/520) are
	 * the exact geometry breakpoints of the migrated portal CSS.
	 *
	 * @return string
	 */
	function lunara_oscars_portal_critical_css() {
		$css = <<<'CSS'
#primary.lunara-oscars-portal{box-sizing:border-box;display:flex!important;flex-direction:column!important;gap:var(--lunara-oscars-portal-section-gap,clamp(34px,4.8vw,58px))!important;margin-inline:auto!important;max-width:min(100%,1180px)!important;min-width:0!important;overflow-x:clip!important;padding:clamp(14px,2.4vw,24px) clamp(18px,3vw,30px) clamp(48px,6vw,76px)!important;width:100%!important}
#primary.lunara-oscars-portal>:is([class*="lunara-oscars-portal-slot-"],.lunara-oscars-navigator){box-sizing:border-box;max-width:100%;min-width:0;order:initial!important}
#primary.lunara-oscars-portal>.lunara-home-section{max-width:100%!important;min-width:0!important;overflow:hidden!important;width:100%!important}
#primary.lunara-oscars-portal>.lunara-oscars-portal-slot-hero{min-height:var(--lunara-oscars-portal-hero-min-height,clamp(360px,42vh,540px))!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{align-items:center!important;display:grid!important;gap:clamp(20px,3vw,34px)!important;grid-template-columns:minmax(0,1.25fr) minmax(240px,330px)!important;min-height:inherit!important;padding:clamp(16px,2.2vw,24px)!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{display:grid!important;gap:10px!important;grid-template-columns:repeat(4,minmax(0,1fr))!important}
#primary.lunara-oscars-portal .lunara-oscars-command-rail{display:grid!important;gap:10px!important;grid-column:1/-1!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;min-width:0!important;width:100%!important}
#primary.lunara-oscars-portal .lunara-oscars-board-list{list-style:none!important;margin:clamp(18px,2.2vw,26px) 0 0!important;padding:0!important}
#primary.lunara-oscars-portal .lunara-oscars-board-row{align-items:baseline!important;display:grid!important;gap:10px clamp(14px,2vw,26px)!important;grid-template-columns:minmax(0,.72fr) minmax(0,1fr) auto!important;min-width:0!important;padding:clamp(12px,1.5vw,16px) 0!important}
#primary.lunara-oscars-portal .lunara-oscars-board-row:first-child{padding-top:0!important}
#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){display:grid!important;gap:16px!important;grid-template-columns:repeat(4,minmax(0,1fr))!important}
#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{display:grid!important;gap:16px!important;grid-template-columns:repeat(5,minmax(0,1fr))!important}
#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{display:grid!important;gap:12px!important;grid-template-columns:repeat(3,minmax(0,1fr))!important}
@media(max-width:1120px){#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{grid-template-columns:minmax(0,1fr) minmax(220px,280px)!important}#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
@media(max-width:900px){#primary.lunara-oscars-portal{max-width:100vw!important}#primary.lunara-oscars-portal>.lunara-home-section{min-height:auto!important}#primary.lunara-oscars-portal .lunara-oscars-portal-hero-grid{gap:16px!important;grid-template-columns:minmax(0,1fr)!important;padding:0!important}}
@media(max-width:820px){#primary.lunara-oscars-portal{gap:var(--lunara-oscars-portal-section-gap,34px)!important;padding:12px 14px 52px!important}#primary.lunara-oscars-portal :is(.lunara-oscars-portal-link-grid,.lunara-oscars-portal-spotlight-grid,.lunara-oscars-portal-title-grid,.lunara-oscars-portal-facts-grid,.lunara-oscars-research-card-grid){gap:12px!important;grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-research-card-grid{grid-template-columns:minmax(0,1fr)!important}#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-oscars-command-rail{grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-oscars-portal .lunara-ceremony-winners-grid{gap:10px!important;grid-template-columns:minmax(0,1fr)!important}}
@media(max-width:782px){#primary.lunara-oscars-portal .lunara-oscars-board-row{grid-template-columns:minmax(0,1fr) auto!important}#primary.lunara-oscars-portal .lunara-oscars-board-category{grid-column:1/-1!important}}
@media(max-width:520px){#primary.lunara-oscars-portal{padding-left:10px!important;padding-right:10px!important}#primary.lunara-oscars-portal .lunara-oscars-portal-stat-grid{gap:8px!important}#primary.lunara-oscars-portal .lunara-oscars-command-rail{gap:8px!important;grid-template-columns:minmax(0,1fr)!important}#primary.lunara-oscars-portal .lunara-oscars-portal-title-grid{grid-template-columns:minmax(0,1fr)!important}}
CSS;

		return lunara_oscars_portal_minify_structural_css( $css );
	}
}
