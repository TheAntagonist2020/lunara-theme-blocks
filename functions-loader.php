<?php
/**
 * Lunara Film Premium — Child Theme Functions (Loader)
 *
 * Split from monolithic functions.php into 12 include files on 2026-04-05.
 * Load order respects cross-file dependencies.
 *
 * @package Lunara_Film
 * @version 3.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$lunara_inc = get_stylesheet_directory() . '/inc/';

// Layer 0 — Foundation (no dependencies).
require_once $lunara_inc . 'setup.php';
require_once $lunara_inc . 'helpers.php';

// Layer 1 — Independent modules.
require_once $lunara_inc . 'customizer.php';
require_once $lunara_inc . 'reviews-cpt.php';
require_once $lunara_inc . 'journal-cpt.php';    // Journal CPT + journal_type taxonomy + per-post meta
require_once $lunara_inc . 'journal-family.php'; // Canonical fields, taxonomy routes, and legacy presentation adapters
require_once $lunara_inc . 'editorial-meta.php';
require_once $lunara_inc . 'trailers.php';
require_once $lunara_inc . 'publish-guards.php';
require_once $lunara_inc . 'carousel.php';
require_once $lunara_inc . 'control-desk.php';
// Legacy homepage shortcodes are intentionally not booted from inc/ anymore.
// The monolithic fallback in functions.php still covers older page content while
// the canonical live homepage path remains the section-based front-page template.

// Layer 2 — Shared hub (Debrief data adapters and legacy renderer).
require_once $lunara_inc . 'debrief-resolver.php';
require_once $lunara_inc . 'debrief.php';
require_once $lunara_inc . 'debrief-public.php';
require_once $lunara_inc . 'shot-reel.php';   // The Still Gallery — [lunara_shot_reel] screening-room shot essays

// Layer 3 — Card rendering (depends on debrief).
require_once $lunara_inc . 'review-rendering.php';

// Layer 4 — Query layer (depends on card builders + debrief).
require_once $lunara_inc . 'queries.php';

// Layer 5 — Home page sections (depends on all above).
require_once $lunara_inc . 'home-sections.php';

// Layer 6 — Oscars portal (depends on home-sections + card builders).
require_once $lunara_inc . 'oscars-portal.php';

// Layer 7 — Frontend output (footer, nav, search, content filters, animations).
require_once $lunara_inc . 'blocks.php';
require_once $lunara_inc . 'block-migration.php';
require_once $lunara_inc . 'frontend.php';
require_once $lunara_inc . 'cinematic-home.php';

// Layer 8 — Admin tools (admin-only; no front-end dependencies).
require_once $lunara_inc . 'portrait-organizer.php';

// Layer 9 — Entity surfaces (Phase 2B): movie/person dossiers, archives,
// and their JSON-LD. Depends only on the Lunara Core entity models.
require_once $lunara_inc . 'entity-surfaces.php';

// Layer 10 — Hero Command: curated hero deck + overlay intensity. Front-end
// feed override plus the Control Desk studio and its save/search handlers.
require_once $lunara_inc . 'hero-command.php';

// Layer 11 — Modular Essay Builder (Design Spec §12): renders the ACF
// flexible-content modules registered by Lunara Core after essay content.
require_once $lunara_inc . 'essay-builder.php';

// Layer 12 — Live Global Search (Design Spec §6): REST endpoint + overlay
// command palette. The branded surface is /search/?q=; /?s= remains legacy-safe.
require_once $lunara_inc . 'live-search.php';

// Layer 13 — Hybrid homepage composition: the Home page's blocks render the
// homepage when present; Homepage Studio saves write through to the blocks.
require_once $lunara_inc . 'home-blocks.php';

// Layer 13b — Curated Grids: fully customizable, per-instance Reviews Grid
// and Journal Grid blocks (hand-picked slots, auto-fill, layout + display
// dials). Depends on card helpers (review-rendering) and CPTs.
require_once $lunara_inc . 'curated-grids.php';

// Layer 14 — GEO (Design Spec §16): serves /llms.txt, the retrieval-model
// map of the site's authoritative surfaces.
require_once $lunara_inc . 'geo.php';

// Layer 15 — Header Command (Design Spec §9): the Lunara header bar and
// off-canvas navigation behind the Control Desk takeover switch — the
// staged parent-theme exit. Default OFF; zero output while off.
require_once $lunara_inc . 'header-command.php';

// Layer 16 — Design Tokens: dial-level palette/voice overrides from the
// Control Desk, printed as a :root layer over the shipped tokens.
require_once $lunara_inc . 'design-tokens.php';

// Signal that every modular include completed. The monolithic fallback uses
// this theme-owned sentinel; LUNARA_CORE_VERSION remains owned by Lunara Core.
if ( ! defined( 'LUNARA_SPLIT_LOADER_ACTIVE' ) ) {
    define( 'LUNARA_SPLIT_LOADER_ACTIVE', true );
}
