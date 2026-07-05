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
require_once $lunara_inc . 'editorial-meta.php';
require_once $lunara_inc . 'trailers.php';
require_once $lunara_inc . 'publish-guards.php';
require_once $lunara_inc . 'carousel.php';
require_once $lunara_inc . 'control-desk.php';
// Legacy homepage shortcodes are intentionally not booted from inc/ anymore.
// The monolithic fallback in functions.php still covers older page content while
// the canonical live homepage path remains the section-based front-page template.

// Layer 2 — Shared hub (debrief defines functions used by everything below).
require_once $lunara_inc . 'debrief.php';
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
