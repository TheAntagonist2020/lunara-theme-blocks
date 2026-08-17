<?php
/**
 * Oscars route-family detectors and the plugin read-path boundary.
 *
 * The Oscars surface spans two owners: the theme's portal page (/oscars/,
 * page-oscars.php) and the plugin-owned ledger routes (aat_* query vars for
 * ceremonies, categories, titles, people, and companies). This module gives
 * both a single vocabulary so route-scoped styling and the Portal Studio can
 * target the family without re-open-coding the gates that already exist in
 * inc/setup.php and inc/frontend.php.
 *
 * It also owns lunara_oscars_reader(): the only sanctioned door from theme
 * code into Academy Awards data. Consumers call the plugin's public read
 * accessors through it behind method_exists guards and degrade to
 * hidden/empty output when an accessor is unavailable — theme-side SQL
 * against the awards table is never a fallback.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the theme-owned Oscars portal page.
 *
 * Deliberately the same two-part gate the asset pipeline already trusts
 * (inc/setup.php `lunara_enqueue_styles`, inc/frontend.php
 * `lunara_enqueue_phase1c_delivery_assets`): the resolved /oscars/ page or
 * any page assigned the page-oscars.php template.
 *
 * @return bool
 */
function lunara_is_oscars_portal_route() {
	return is_page( 'oscars' ) || is_page_template( 'page-oscars.php' );
}

/**
 * Whether the current request is a plugin-owned Oscars ledger route.
 *
 * Prefers the plugin's own get_route_context() accessor (2.7.82+), which
 * classifies the already-sanitized aat_* query vars without re-parsing raw
 * request data. The open-coded query-var test previously duplicated by the
 * late-guardrails hook (inc/setup.php) remains the fallback for older
 * plugin builds so the family never silently shrinks.
 *
 * @return bool
 */
function lunara_is_oscars_ledger_route() {
	$reader = lunara_oscars_reader();

	if ( $reader && method_exists( $reader, 'get_route_context' ) ) {
		$context = $reader->get_route_context();
		$kind    = is_array( $context ) && isset( $context['kind'] ) && is_scalar( $context['kind'] ) ? (string) $context['kind'] : 'none';

		// The portal kind is the theme page, owned by the portal detector;
		// every other resolved kind is a ledger route.
		return ! in_array( $kind, array( 'none', 'portal' ), true );
	}

	$aat_entity    = get_query_var( 'aat_entity' );
	$aat_entity_id = get_query_var( 'aat_entity_id' );
	$aat_hub       = get_query_var( 'aat_hub' );

	return ( ! empty( $aat_entity ) && ! empty( $aat_entity_id ) ) || ! empty( $aat_hub );
}

/**
 * Whether the current request is anywhere in the Oscars route family.
 *
 * @return bool
 */
function lunara_is_oscars_route_family() {
	return lunara_is_oscars_portal_route() || lunara_is_oscars_ledger_route();
}

/**
 * Add the shared family body class next to the existing route classes.
 *
 * Strictly additive: `lunara-oscars-portal-page` (inc/oscars-portal.php) and
 * the plugin's `aat-shell-page` remain untouched — the late-guardrails
 * stylesheet contract keys off aat-shell-page and must keep matching.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function lunara_oscars_family_body_classes( $classes ) {
	if ( lunara_is_oscars_route_family() && ! in_array( 'lunara-oscars-family', (array) $classes, true ) ) {
		$classes[] = 'lunara-oscars-family';
	}

	return $classes;
}
add_filter( 'body_class', 'lunara_oscars_family_body_classes' );

/**
 * The theme's single boundary to the Academy Awards plugin read API.
 *
 * Returns the plugin singleton when its class is available, null otherwise.
 * Callers must method_exists-guard every accessor and degrade to
 * hidden/empty output when the reader (or a specific accessor) is missing;
 * no caller may fall back to direct awards-table SQL.
 *
 * @return object|null
 */
function lunara_oscars_reader() {
	if ( ! class_exists( 'Academy_Awards_Table' ) || ! method_exists( 'Academy_Awards_Table', 'get_instance' ) ) {
		return null;
	}

	return Academy_Awards_Table::get_instance();
}
