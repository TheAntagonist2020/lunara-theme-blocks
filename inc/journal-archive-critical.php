<?php
/**
 * Journal archive first-paint geometry.
 *
 * The public template already emits the exact saved section order in the DOM.
 * This compact route-scoped seed reserves that geometry before an optimizer's
 * deferred aggregate can settle, and deliberately neutralizes historic
 * numeric `order` declarations so CSS can never become a second order owner.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_journal_archive_resolved_label_font_slug' ) ) {
	/**
	 * Resolve the Studio label role without allowing one malformed field to
	 * corrupt the rest of the design-token document.
	 *
	 * @return string
	 */
	function lunara_journal_archive_resolved_label_font_slug() {
		$default = 'tiempos-text';
		$tokens  = function_exists( 'lunara_get_design_tokens' ) ? lunara_get_design_tokens() : array();
		$fonts   = isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array();
		$slug    = isset( $fonts['label'] ) && is_scalar( $fonts['label'] ) ? (string) $fonts['label'] : $default;
		$choices = function_exists( 'lunara_design_token_font_choices' ) ? lunara_design_token_font_choices() : array();

		return is_array( $choices ) && isset( $choices[ $slug ] ) ? $slug : $default;
	}
}

if ( ! function_exists( 'lunara_journal_archive_uses_tiempos_label_face' ) ) {
	/**
	 * Whether the default Studio label role should use the licensed route face.
	 *
	 * @return bool
	 */
	function lunara_journal_archive_uses_tiempos_label_face() {
		return 'tiempos-text' === lunara_journal_archive_resolved_label_font_slug();
	}
}

if ( ! function_exists( 'lunara_journal_archive_minify_structural_css' ) ) {
	function lunara_journal_archive_minify_structural_css( $css ) {
		$css = preg_replace( '#/\*[^!][\s\S]*?\*/#', '', (string) $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
		$css = str_replace( ';}', '}', (string) $css );
		return trim( (string) $css );
	}
}

if ( ! function_exists( 'lunara_journal_archive_variable_css' ) ) {
	/**
	 * Compose request-specific presentation variables from the resolved config.
	 *
	 * The caller passes the preview-aware composite configuration, so an
	 * authorized private preview uses its staged geometry without writing any
	 * Customizer owner or public option.
	 *
	 * @param array<string,mixed> $config Resolved Journal archive config.
	 * @return string
	 */
	function lunara_journal_archive_variable_css( $config ) {
		$presentation = isset( $config['presentation'] ) && is_array( $config['presentation'] )
			? $config['presentation']
			: array();
		$density = isset( $presentation['density'] ) ? sanitize_key( $presentation['density'] ) : 'editorial';
		$lead    = isset( $presentation['lead_prominence'] ) ? sanitize_key( $presentation['lead_prominence'] ) : 'standard';
		$rhythm  = isset( $presentation['desk_rhythm'] ) ? sanitize_key( $presentation['desk_rhythm'] ) : 'balanced';

		$shell_gaps = array( 'compact' => 20, 'editorial' => 28, 'showcase' => 38 );
		$grid_gaps  = array( 'compact' => 16, 'editorial' => 24, 'showcase' => 30 );
		$clamps     = array( 'compact' => 2, 'editorial' => 3, 'showcase' => 4 );
		$retention_gaps = array( 'compact' => 12, 'editorial' => 18, 'showcase' => 24 );
		$desk_pads      = array( 'quick' => 10, 'balanced' => 14, 'immersive' => 18 );
		$lead_media     = array( 'restrained' => 220, 'standard' => 260, 'feature' => 310 );
		$lead_titles    = array( 'restrained' => 1.5, 'standard' => 1.72, 'feature' => 2.02 );

		$section_gap = isset( $presentation['section_gap'] ) ? absint( $presentation['section_gap'] ) : 38;
		$hero_min    = isset( $presentation['hero_min_height'] ) ? absint( $presentation['hero_min_height'] ) : 240;
		$card_min    = isset( $presentation['card_min_height'] ) ? absint( $presentation['card_min_height'] ) : 390;
		$media_min   = isset( $presentation['media_min_height'] ) ? absint( $presentation['media_min_height'] ) : 220;
		$shell_gap   = isset( $shell_gaps[ $density ] ) ? $shell_gaps[ $density ] : 28;
		$grid_gap    = isset( $grid_gaps[ $density ] ) ? $grid_gaps[ $density ] : 24;
		$clamp       = isset( $clamps[ $density ] ) ? $clamps[ $density ] : 3;
		$retention_gap = isset( $retention_gaps[ $density ] ) ? $retention_gaps[ $density ] : 18;
		$desk_pad      = isset( $desk_pads[ $rhythm ] ) ? $desk_pads[ $rhythm ] : 14;
		$lead_media_min = isset( $lead_media[ $lead ] ) ? max( $media_min, $lead_media[ $lead ] ) : max( $media_min, 260 );
		$lead_title     = isset( $lead_titles[ $lead ] ) ? $lead_titles[ $lead ] : 1.72;

		if ( 'quick' === $rhythm ) {
			$section_gap = (int) round( $section_gap * 0.86 );
		} elseif ( 'immersive' === $rhythm ) {
			$section_gap = (int) round( $section_gap * 1.12 );
			$hero_min    = (int) round( $hero_min * 1.08 );
		}

		$section_gap = max( 16, min( 92, $section_gap ) );
		$hero_min    = max( 150, min( 440, $hero_min ) );

		return sprintf(
			'#primary.lunara-journal-archive-page{--lunara-journal-archive-section-gap:%dpx;--lunara-journal-archive-shell-gap:%dpx;--lunara-journal-archive-hero-min:%dpx;--lunara-journal-archive-card-min:%dpx;--lunara-journal-archive-media-min:%dpx;--lunara-journal-archive-lead-media-min:%dpx;--lunara-journal-archive-grid-gap:%dpx;--lunara-journal-archive-excerpt-clamp:%d;--lunara-journal-archive-retention-gap:%dpx;--lunara-journal-archive-desk-pad:%dpx;--lunara-journal-archive-lead-title:%srem}',
			$section_gap,
			$shell_gap,
			$hero_min,
			max( 280, min( 560, $card_min ) ),
			max( 160, min( 360, $media_min ) ),
			$lead_media_min,
			$grid_gap,
			$clamp,
			$retention_gap,
			$desk_pad,
			rtrim( rtrim( number_format( $lead_title, 2, '.', '' ), '0' ), '.' )
		);
	}
}

if ( ! function_exists( 'lunara_journal_archive_critical_css' ) ) {
	/**
	 * Compose the synchronous route geometry seed.
	 *
	 * @return string
	 */
	function lunara_journal_archive_critical_css() {
		$css = <<<'CSS'
#primary.lunara-journal-archive-page{box-sizing:border-box;display:flex!important;flex-direction:column!important;gap:var(--lunara-journal-archive-section-gap,38px)!important;margin-inline:auto!important;max-width:1180px!important;min-width:0!important;padding:0 clamp(16px,4vw,40px) 80px!important;width:100%!important}
#primary.lunara-journal-archive-page>[class*="lunara-journal-archive-slot-"]{box-sizing:border-box;max-width:100%;min-width:0;order:initial!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-hero{align-content:center;display:grid!important;margin:0!important;min-height:var(--lunara-journal-archive-hero-min,240px)!important;padding:clamp(24px,4.4vw,54px)!important;width:100%!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-deskbar{align-items:center;display:flex!important;flex-wrap:wrap!important;gap:10px!important;margin:0!important;padding:var(--lunara-journal-archive-desk-pad,14px)!important;width:100%!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-filters{display:grid!important;gap:10px!important;margin:0!important;width:100%!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-filters{align-items:center;box-sizing:border-box!important;display:flex!important;flex-wrap:wrap!important;gap:10px!important;margin:0!important;padding:10px!important;width:100%!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-toolbar{align-items:center;display:grid!important;gap:var(--lunara-journal-archive-shell-gap,28px)!important;grid-template-columns:minmax(220px,.7fr) minmax(0,1fr)!important;margin:0!important;padding:clamp(16px,2vw,22px)!important;width:100%!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-grid{align-items:stretch;display:grid!important;gap:var(--lunara-journal-archive-grid-gap,24px)!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;margin:0!important;width:100%!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card{box-sizing:border-box;min-height:var(--lunara-journal-archive-card-min,390px)!important;min-width:0!important;width:100%!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card .lunara-review-grid-link{display:grid!important;grid-template-rows:auto 1fr!important;height:100%!important;min-height:var(--lunara-journal-archive-card-min,390px)!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card .lunara-review-grid-poster-wrap{aspect-ratio:16/10!important;min-height:var(--lunara-journal-archive-media-min,220px)!important;overflow:hidden!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card .lunara-review-grid-poster-wrap img{display:block!important;height:100%!important;object-fit:cover!important;width:100%!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead .lunara-review-grid-poster{opacity:1!important;transition:none!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead{grid-column:span 2!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead .lunara-review-grid-link{grid-template-columns:minmax(320px,.58fr) minmax(0,1fr)!important;grid-template-rows:minmax(0,1fr)!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap{height:100%!important;min-height:var(--lunara-journal-archive-lead-media-min,260px)!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-text-brief,#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-media-failed{grid-column:auto!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-text-brief .lunara-review-grid-link,#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-media-failed .lunara-review-grid-link{grid-template-columns:minmax(0,1fr)!important;grid-template-rows:minmax(0,1fr)!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-retention{display:grid!important;gap:var(--lunara-journal-archive-retention-gap,18px)!important;grid-template-columns:minmax(0,.78fr) minmax(0,1.22fr)!important;margin:0!important;padding:clamp(18px,2.4vw,30px)!important;width:100%!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-retention-grid{display:grid!important;gap:var(--lunara-journal-archive-retention-gap,18px)!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;min-width:0!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-retention-card{box-sizing:border-box;display:flex!important;flex-direction:column!important;gap:12px!important;min-height:185px!important;min-width:0!important;overflow:hidden!important}
#primary.lunara-journal-archive-page .lunara-journal-archive-retention-media{aspect-ratio:16/9;display:block;flex:0 0 auto;margin:-16px -16px 2px;overflow:hidden;position:relative;width:calc(100% + 32px)}
#primary.lunara-journal-archive-page .lunara-journal-archive-retention-media img{display:block;height:100%!important;object-fit:cover!important;object-position:var(--lunara-retention-focus-x,50%) var(--lunara-retention-focus-y,50%)!important;width:100%!important}
#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-pagination{margin:0 auto!important;width:100%!important}
@media(max-width:900px){#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-toolbar{grid-template-columns:minmax(0,1fr)!important}#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead{grid-column:1/-1!important}#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-retention{grid-template-columns:minmax(0,1fr)!important}}
@media(max-width:620px){#primary.lunara-journal-archive-page{gap:calc(var(--lunara-journal-archive-section-gap,38px)*.78)!important;max-width:100%!important;min-width:0!important;padding-inline:0!important;width:100%!important}#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-hero{min-height:0!important;padding:22px 18px!important}#primary.lunara-journal-archive-page .lunara-journal-archive-filters,#primary.lunara-journal-archive-page .lunara-archive-sort{flex-wrap:nowrap!important;overflow-x:auto!important;padding-bottom:4px!important}#primary.lunara-journal-archive-page>.lunara-journal-archive-slot-grid{grid-template-columns:minmax(0,1fr)!important}#primary.lunara-journal-archive-page .lunara-journal-archive-card,#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead{grid-column:auto!important;min-height:0!important}#primary.lunara-journal-archive-page .lunara-journal-archive-card .lunara-review-grid-link,#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead .lunara-review-grid-link{grid-template-columns:minmax(0,1fr)!important;grid-template-rows:auto 1fr!important;min-height:0!important}#primary.lunara-journal-archive-page .lunara-journal-archive-card .lunara-review-grid-poster-wrap,#primary.lunara-journal-archive-page .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap{height:auto!important;min-height:clamp(188px,54vw,var(--lunara-journal-archive-media-min,220px))!important}#primary.lunara-journal-archive-page .lunara-journal-archive-retention-grid{grid-template-columns:minmax(0,1fr)!important}#primary.lunara-journal-archive-page .lunara-journal-archive-retention-card{min-height:0!important}}
CSS;

		return lunara_journal_archive_minify_structural_css( $css );
	}
}
