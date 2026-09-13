<?php
/**
 * Reviews archive first-paint structural CSS.
 *
 * The cacheable route stylesheet owns the complete visual treatment. This
 * module emits a compact, universal structural guard for every Reviews lane.
 * This keeps both the default and saved Archive Studio orders stable while an
 * older optimizer payload is replaced by the current cacheable stylesheet.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lunara_reviews_archive_resolved_label_font_slug' ) ) {
	/**
	 * Resolve the Studio label role without allowing one malformed field to
	 * corrupt the rest of the design-token document.
	 *
	 * @return string
	 */
	function lunara_reviews_archive_resolved_label_font_slug() {
		$default = 'tiempos-text';
		$tokens  = function_exists( 'lunara_get_design_tokens' ) ? lunara_get_design_tokens() : array();
		$fonts   = isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array();
		$slug    = isset( $fonts['label'] ) && is_scalar( $fonts['label'] ) ? (string) $fonts['label'] : $default;
		$choices = function_exists( 'lunara_design_token_font_choices' ) ? lunara_design_token_font_choices() : array();

		return is_array( $choices ) && isset( $choices[ $slug ] ) ? $slug : $default;
	}
}

if ( ! function_exists( 'lunara_reviews_archive_uses_tiempos_label_face' ) ) {
	/**
	 * Whether the default Studio label role should use the licensed route face.
	 *
	 * @return bool
	 */
	function lunara_reviews_archive_uses_tiempos_label_face() {
		return 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug();
	}
}

if ( ! function_exists( 'lunara_reviews_archive_minify_structural_css' ) ) {
    function lunara_reviews_archive_minify_structural_css( $css ) {
        $css = preg_replace( '#/\*[^!][\s\S]*?\*/#', '', (string) $css );
        $css = preg_replace( '/\s+/', ' ', $css );
        $css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
        $css = str_replace( ';}', '}', (string) $css );
        return trim( (string) $css );
    }
}

if ( ! function_exists( 'lunara_reviews_archive_critical_css' ) ) {
    function lunara_reviews_archive_critical_css( $lane_orders, $visibility = array() ) {
        // Saved values arrive through the higher-specificity authority
        // variables. The structural guard remains universal by design, so a
        // hidden or reordered lane is already stable if it becomes visible.

        $core = <<<'CSS'
&{--b:"Tiempos Text",Georgia,"Times New Roman","Iowan Old Style","Palatino Linotype",serif;--h:"Tiempos Headline","Tiempos Text",Georgia,"Times New Roman","Iowan Old Style",serif;display:flex!important;flex-direction:column!important}
&>.lunara-review-archive-slot-hero{order:var(--lunara-reviews-archive-order-hero,1)!important}
&>:is(.lunara-review-archive-slot-utility,.lunara-review-archive-slot-grid){order:var(--lunara-reviews-archive-order-grid,2)!important}
&>.lunara-review-archive-slot-pagination{order:var(--lunara-reviews-archive-order-pagination,3)!important}
&>.lunara-review-archive-slot-pairing-desk{order:var(--lunara-reviews-archive-order-pairing,4)!important}
&>.lunara-review-archive-slot-retention{margin:0!important;order:var(--lunara-reviews-archive-order-grid,2)!important;width:100%!important}
& .lunara-review-archive-gallery{min-width:0!important;width:100%!important}
&>.lunara-review-archive-slot-grid{gap:var(--lunara-reviews-archive-shell-gap,36px)!important}
&>.lunara-review-archive-slot-pagination{margin:0 auto!important;width:100%!important}
&>.lunara-home-section{margin-bottom:calc(var(--lunara-reviews-archive-section-gap,40px)*.72)!important}
& .lunara-pairing-desk-section{overflow:hidden!important;position:relative!important}
& .lunara-pairing-desk-inner{position:relative!important;z-index:2!important}
@media(max-width:820px){& .lunara-pairing-desk-backdrop{display:none!important}}
@media(max-width:540px){&{box-sizing:border-box!important;gap:clamp(24px,calc(var(--lunara-reviews-archive-section-gap,40px)*.65),32px)!important;max-width:100%!important;min-width:0!important;padding-inline:16px!important;width:100%!important}&>.lunara-home-section{margin-bottom:0!important}& :is(.lunara-home-section,.lunara-review-archive-hero,.lunara-review-archive-shell){margin-inline:auto!important;max-width:100%!important;padding-inline:0!important;width:100%!important}}
CSS;

        $hero = <<<'CSS'
&>.lunara-review-archive-slot-hero{height:auto!important;min-height:0!important;padding-bottom:0!important;padding-top:clamp(20px,2.5vw,32px)!important}
& .lunara-review-archive-hero-shell{align-items:start!important;background:none!important;border:0!important;border-radius:0!important;box-shadow:none!important;display:grid!important;gap:0!important;grid-template-columns:minmax(0,1fr)!important;height:auto!important;min-height:0!important;overflow:visible!important;padding:0!important}
& .lunara-review-archive-hero-shell::before{content:none!important;display:none!important}
& .lunara-review-archive-hero-copy-wrap{align-content:start!important;display:grid!important;gap:10px!important;grid-template-columns:minmax(0,1fr)!important;min-width:0!important}
& :is(.lunara-archive-hero-kicker,.lunara-archive-hero-title){font-family:var(--h)!important}
& .lunara-archive-hero-title{color:var(--lunara-gold,#d8b665)!important;font-size:clamp(32px,3.6vw,48px)!important;hyphens:none!important;letter-spacing:-.02em!important;line-height:1.1!important;margin:0!important;max-width:none!important;overflow-wrap:break-word!important;word-break:normal!important}
& .lunara-archive-hero-copy{color:rgba(238,242,245,.82)!important;font-family:var(--b)!important;font-size:1rem!important;line-height:1.55!important;margin:0!important;max-width:70ch!important;overflow-wrap:anywhere!important}
CSS;

        $utility = <<<'CSS'
& .lunara-review-archive-toolbar-head :is(.lunara-home-section-kicker,.lunara-section-title){font-family:var(--h)!important}
& :is(.lunara-review-archive-sort-label,.lunara-review-archive-sort-link,.lunara-review-archive-year-filter label,.lunara-review-archive-year-filter select,.lunara-review-archive-year-filter button){font-family:var(--b)!important}
& .lunara-review-archive-year-filter select{font-size:.833333rem!important;height:38px!important;line-height:1.7!important;width:auto!important}
@media(max-width:540px){& .lunara-review-archive-utility{margin-top:0!important}& .lunara-review-archive-toolbar{border-radius:16px!important;display:grid!important;gap:12px!important;grid-template-columns:minmax(0,1fr)!important;margin:0!important;padding:12px!important}& .lunara-review-archive-toolbar-head{display:none!important}& .lunara-review-archive-sort{background:none!important;border:0!important;border-radius:0!important;display:flex!important;flex-wrap:wrap!important;gap:8px!important;overflow:visible!important;padding:0!important}& .lunara-review-archive-sort-link{border-radius:999px!important;flex:1 1 calc(50% - 4px)!important;font-size:.76rem!important;letter-spacing:0!important;line-height:1.25!important;max-width:100%!important;min-height:44px!important;min-width:0!important;padding:10px 12px!important;text-align:center!important;white-space:normal!important}& .lunara-review-archive-sort-label{flex-basis:100%!important}& .lunara-review-archive-year-filter{display:grid!important;grid-template-columns:auto minmax(0,1fr) auto!important;min-width:0!important;width:100%!important}& .lunara-review-archive-year-filter label{grid-column:auto!important;margin:0!important}& .lunara-review-archive-year-filter :is(select,button){height:44px!important;max-width:100%!important;min-height:44px!important;min-width:0!important;width:100%!important}}
@media(max-width:359px){& .lunara-review-archive-year-filter{grid-template-columns:minmax(0,1fr) auto!important}& .lunara-review-archive-year-filter label{grid-column:1/-1!important}}
CSS;

        $grid = <<<'CSS'
& .is-lead .lunara-review-feature-copy{display:grid!important;gap:16px!important}
& .is-lead .lunara-review-feature-title{font-family:var(--h)!important;font-size:clamp(1.9rem,3.4vw,2.8rem)!important;line-height:1.05!important;margin:0!important}
& .is-lead .lunara-review-feature-title-link{font-family:var(--b)!important}
& .is-lead .lunara-review-feature-excerpt{font-family:var(--b)!important;font-size:1.04rem!important;line-height:1.78!important;margin:0!important}
& .is-lead .lunara-review-feature-footer{align-items:center!important;display:flex!important;flex-wrap:wrap!important;gap:14px!important;justify-content:space-between!important;margin-top:auto!important;padding-top:10px!important}
& .lunara-review-feature-ledger{align-items:center!important;display:flex!important}
& .lunara-review-feature-ledger>a{align-items:center!important;display:inline-flex!important;flex-wrap:wrap!important;gap:10px!important;margin-top:2px!important}
& .lunara-oscar-ledger-pill{align-items:center!important;display:inline-flex!important;font-family:var(--b)!important;font-size:.66rem!important;font-weight:700!important;letter-spacing:.12em!important;line-height:1!important;margin-left:10px!important;padding:4px 10px!important;text-transform:uppercase!important}
& .lunara-oscar-ledger-counts{font-family:var(--b)!important;font-size:14px!important}
& .lunara-review-feature-cta{align-items:center!important;display:inline-flex!important;font-family:var(--b)!important;font-size:.8rem!important;font-weight:600!important;justify-content:center!important;letter-spacing:.12em!important;min-height:44px!important;padding:10px 16px 2px!important;text-transform:uppercase!important}
@media(max-width:820px){& .is-lead .lunara-review-feature-copy{gap:10px!important}& .is-lead .lunara-review-feature-excerpt{-webkit-box-orient:vertical!important;-webkit-line-clamp:var(--lunara-reviews-archive-excerpt-clamp,3)!important;display:-webkit-box!important;overflow:hidden!important}}
@media(max-width:768px){& .lunara-oscar-ledger-pill{margin-left:8px!important;padding:4px 9px!important}}
@media(max-width:600px){& .lunara-oscar-ledger-counts{font-size:13px!important}}
@media(max-width:900px){& .lunara-review-grid-title{-webkit-line-clamp:unset!important;display:block!important;height:auto!important;max-height:none!important;min-height:0!important;overflow:visible!important;overflow-wrap:anywhere!important}}
@media(max-width:540px){& .is-lead .lunara-review-feature-title{font-size:clamp(1.16rem,6vw,1.52rem)!important;line-height:1.08!important}& .is-lead .lunara-review-feature-excerpt{-webkit-line-clamp:2!important;font-size:.9rem!important;line-height:1.44!important}& .is-lead .lunara-review-feature-footer{display:none!important}}
CSS;

        $pairing = <<<'CSS'
& .lunara-pairing-desk-head{align-items:end!important;display:grid!important;gap:14px clamp(38px,5vw,72px)!important;grid-template-columns:minmax(0,max-content) minmax(280px,42ch)!important;justify-content:start!important;margin-bottom:18px!important}
& .lunara-pairing-desk-intro{align-content:end!important;display:grid!important;gap:10px!important;padding-bottom:4px!important}
& .lunara-pairing-desk-claim{border:1px solid transparent!important;display:inline-block!important;font-size:.85rem!important;letter-spacing:.32em!important;margin:0 0 12px!important;padding:6px 13px!important;text-transform:uppercase!important}
& .lunara-pairing-desk-head .lunara-home-section-title{font-size:clamp(1.7rem,3vw,2.3rem)!important;letter-spacing:-.02em!important;line-height:.98!important;margin:0!important;max-width:14ch!important}
& .lunara-pairing-desk-copy{font-size:.97rem!important;line-height:1.6!important;margin:0!important}
& .lunara-pair-cards{border:1px solid transparent!important;box-sizing:border-box!important;margin:0 auto!important;max-width:min(100%,1040px)!important;padding:clamp(20px,2.8vw,32px)!important}
& .lunara-pair-cards-grid{align-items:stretch!important;display:grid!important;gap:clamp(14px,1.8vw,22px)!important;grid-template-columns:repeat(auto-fit,minmax(min(100%,236px),1fr))!important}
& .lunara-pair-card{border:1px solid transparent!important;display:flex!important;flex-direction:column!important;min-width:0!important;overflow:hidden!important}
& .lunara-pair-card-poster{aspect-ratio:2/3!important;overflow:hidden!important;position:relative!important}
& .lunara-pair-card-poster img{display:block!important;height:100%!important;object-fit:cover!important;width:100%!important}
& .lunara-pair-card-body{display:flex!important;flex:1!important;flex-direction:column!important;gap:9px!important;padding:15px 15px 17px!important}
& .lunara-pair-card-role{font-family:"Tiempos Text","Segoe UI",Arial,sans-serif!important;font-size:.72rem!important;font-weight:700!important;letter-spacing:.26em!important;line-height:1!important;margin:0!important;text-transform:uppercase!important}
& .lunara-pair-card-title{font-family:var(--b)!important;font-size:clamp(1.05rem,1.4vw,1.22rem)!important;font-weight:400!important;line-height:1.16!important;margin:0!important}
& .lunara-pair-card-title-link{-webkit-box-orient:vertical!important;-webkit-line-clamp:2!important;display:-webkit-box!important;margin-top:-8px!important;overflow:hidden!important;overflow-wrap:anywhere!important;padding-top:8px!important;text-decoration:none!important}
& .lunara-pair-card-year{font-size:.86em!important}
& .lunara-pair-card-note{font-family:var(--b)!important;font-size:.9rem!important;line-height:1.5!important;margin:0!important;text-wrap:pretty!important}
& .lunara-pair-card-chips{align-items:center!important;display:flex!important;flex-wrap:wrap!important;gap:8px!important;margin-top:auto!important;padding-top:12px!important}
& .lunara-pair-card-chip{align-items:center!important;border:1px solid transparent!important;display:inline-flex!important;font-size:.78rem!important;gap:5px!important;line-height:1!important;padding:6px 12px!important}
@media(min-width:880px){& .lunara-pair-cards-grid[data-count="3"]{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:900px){& .lunara-pairing-desk-head{grid-template-columns:minmax(0,1fr)!important}& .lunara-pairing-desk-intro{align-content:start!important;padding-bottom:0!important}}
@media(max-width:680px){& .lunara-pair-cards{padding:16px!important}& .lunara-pair-cards-grid{gap:12px!important;grid-template-columns:minmax(0,1fr)!important}& .lunara-pair-card{align-items:start!important;display:grid!important;grid-template-columns:116px minmax(0,1fr)!important}& .lunara-pair-card-poster{align-self:start!important;height:auto!important}& .lunara-pair-card-chips{margin-top:8px!important}& .lunara-pair-card-body{padding:13px 14px!important}}
@media(max-width:640px){& .lunara-pairing-desk-head .lunara-home-section-title{font-size:clamp(1.25rem,5vw,1.75rem)!important;line-height:1.15!important}}
CSS;

        // Native nesting keeps every rule owned by the renderer's attributed
        // archive wrapper without repeating the long composite selector.
        return lunara_reviews_archive_minify_structural_css(
            '#primary.lra{' . $core . $hero . $utility . $grid . $pairing . '}'
        );
    }
}
