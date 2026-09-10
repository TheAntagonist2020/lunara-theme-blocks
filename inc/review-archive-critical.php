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
&>.lunara-review-archive-slot-grid{gap:var(--lunara-reviews-archive-shell-gap,36px)!important}
&>.lunara-review-archive-slot-pagination{margin:0 auto!important;width:100%!important}
&>.lunara-home-section{margin-bottom:calc(var(--lunara-reviews-archive-section-gap,40px)*.72)!important}
& .lunara-pairing-desk-section{overflow:hidden!important;position:relative!important}
& .lunara-pairing-desk-inner{position:relative!important;z-index:2!important}
@media(max-width:820px){& .lunara-pairing-desk-backdrop{display:none!important}}
@media(max-width:540px){&{padding-inline:0!important}& :is(.lunara-home-section,.lunara-review-archive-hero,.lunara-review-archive-shell){margin-inline:auto!important;max-width:calc(100vw - 24px)!important;padding-inline:0!important;width:calc(100vw - 24px)!important}}
CSS;

        $hero = <<<'CSS'
&>.lunara-review-archive-slot-hero{padding-bottom:clamp(18px,2.8vw,34px)!important;padding-top:clamp(34px,4.8vw,58px)!important}
& .lunara-review-archive-hero-shell{align-items:stretch!important;display:grid!important;gap:clamp(18px,2.4vw,32px)!important;grid-template-columns:minmax(0,1.1fr) minmax(280px,.64fr)!important;padding:clamp(24px,3vw,34px) clamp(24px,3.2vw,38px)!important}
& .lunara-review-archive-hero-copy-wrap{align-content:center!important;display:grid!important;gap:clamp(14px,1.8vw,22px)!important;min-width:0!important}
& :is(.lunara-archive-hero-kicker,.lunara-archive-hero-title){font-family:var(--h)!important}
& :is(.lunara-archive-hero-copy,.lunara-review-archive-debrief,.lunara-review-archive-debrief-kicker,.lunara-review-archive-debrief-list,.lunara-review-archive-hero-actions){font-family:var(--b)!important}
& .lunara-review-archive-debrief{align-content:space-between!important;display:grid!important;gap:18px!important;margin:0!important;padding:clamp(18px,2.2vw,26px)!important}
& .lunara-review-archive-debrief-kicker{font-size:.76rem!important;letter-spacing:0!important;margin:0!important}
& .lunara-review-archive-debrief-list{display:grid!important;gap:10px!important;list-style:none!important;margin:0!important;padding:0!important}
& .lunara-review-archive-debrief-list li{align-items:center!important;display:flex!important;gap:12px!important;justify-content:space-between!important;padding:10px 12px!important}
& .lunara-review-archive-debrief-list strong{font-size:.72rem!important;letter-spacing:0!important}
& .lunara-review-archive-debrief-list span{font-weight:700!important;text-align:right!important}
& .lunara-review-archive-hero-actions{display:flex!important;flex-wrap:wrap!important;gap:8px!important}
& .lunara-review-archive-hero-actions a{align-items:center!important;display:inline-flex!important;font-size:.76rem!important;font-weight:800!important;justify-content:center!important;letter-spacing:0!important;min-height:38px!important;padding:9px 13px!important;text-transform:uppercase!important}
@media(max-width:900px){& .lunara-review-archive-hero-shell{grid-template-columns:minmax(0,1fr)!important}}
@media(max-width:820px){& .lunara-review-archive-hero-shell{gap:16px!important}& .lunara-review-archive-debrief-list{gap:8px!important;grid-template-columns:repeat(2,minmax(0,1fr))!important}& .lunara-review-archive-debrief-list li{padding:10px 11px!important}& .lunara-review-archive-debrief-list span{text-align:left!important}}
@media(max-width:540px){& .lunara-review-archive-hero-shell{padding:18px!important}& .lunara-review-archive-debrief-list{grid-template-columns:minmax(0,1fr)!important}& .lunara-review-archive-debrief-list li{align-items:flex-start!important;flex-direction:column!important;gap:4px!important}& .lunara-review-archive-hero-actions a{flex:1 1 auto!important;min-width:min(100%,142px)!important}}
CSS;

        $utility = <<<'CSS'
& .lunara-review-archive-toolbar-head :is(.lunara-home-section-kicker,.lunara-section-title){font-family:var(--h)!important}
& :is(.lunara-review-archive-sort-label,.lunara-review-archive-sort-link,.lunara-review-archive-year-filter label,.lunara-review-archive-year-filter select,.lunara-review-archive-year-filter button){font-family:var(--b)!important}
& .lunara-review-archive-year-filter select{font-size:.833333rem!important;height:38px!important;line-height:1.7!important;width:auto!important}
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
@media(max-width:600px){& .lunara-oscar-ledger-pill{margin-left:8px!important;padding:4px 9px!important}& .lunara-oscar-ledger-counts{font-size:13px!important}}
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
