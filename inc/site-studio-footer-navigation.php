<?php
/** Footer link ownership, exact revisions and request-local previews. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_footer_navigation_keys() {
	return array_merge( lunara_site_studio_mod_surface_keys( lunara_site_studio_footer_spec() ), array( 'lunara_footer_navigation' ) );
}

function lunara_site_studio_footer_navigation_schema() {
	$schema = lunara_site_studio_mod_surface_schema( lunara_site_studio_footer_spec() );
	$row = array( '*' => array( 'id' => true, 'enabled' => true, 'label' => true, 'destination' => true, 'url' => true ) );
	$schema['navigation'] = array( 'mode' => true, 'columns' => array( 'editorial' => $row, 'oscars' => $row, 'utility' => $row ) );
	return $schema;
}

/** Resolve built-ins at request time; saved links never freeze these URLs. */
function lunara_site_studio_footer_navigation_destinations() {
	$items = array(
		'home' => array( __( 'Home', 'lunara-film' ), home_url( '/' ) ),
		'reviews' => array( __( 'Reviews', 'lunara-film' ), get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ),
		'journal' => array( __( 'Journal', 'lunara-film' ), get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' ) ),
		'about' => array( __( 'About', 'lunara-film' ), home_url( '/about/' ) ),
		'editorial-policy' => array( __( 'Editorial Policy', 'lunara-film' ), home_url( '/editorial-policy/' ) ),
		'oscars' => array( __( 'Oscars', 'lunara-film' ), home_url( '/oscars/' ) ),
		'categories' => array( __( 'Categories', 'lunara-film' ), home_url( '/oscars/categories/' ) ),
		'ceremonies' => array( __( 'Ceremonies', 'lunara-film' ), home_url( '/oscars/ceremonies/' ) ),
		'ledger' => array( __( 'Full Ledger', 'lunara-film' ), home_url( '/oscars/?view=table#oscars-research' ) ),
		'search' => array( __( 'Search', 'lunara-film' ), function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/?s=' ) ),
		'contact' => array( __( 'Contact', 'lunara-film' ), home_url( '/contact/' ) ),
		'rss' => array( __( 'RSS Feed', 'lunara-film' ), get_bloginfo( 'rss2_url' ) ),
		'privacy' => array( __( 'Privacy', 'lunara-film' ), get_privacy_policy_url() ),
	);
	$result = array();
	foreach ( $items as $key => $item ) { $result[ $key ] = array( 'label' => $item[0], 'url' => (string) $item[1], 'available' => is_string( $item[1] ) && '' !== $item[1] ); }
	return $result;
}

/** The inherited public lists and editor seeds have one owner. */
function lunara_site_studio_footer_navigation_inherited_columns() {
	$destinations = lunara_site_studio_footer_navigation_destinations();
	$groups = array( 'editorial' => array( 'home', 'reviews', 'journal', 'about', 'editorial-policy' ), 'oscars' => array( 'oscars', 'categories', 'ceremonies', 'ledger' ), 'utility' => array( 'search', 'contact', 'rss' ) );
	if ( $destinations['privacy']['available'] ) { $groups['utility'][] = 'privacy'; }
	$columns = array();
	foreach ( $groups as $column => $keys ) {
		$columns[ $column ] = array();
		foreach ( $keys as $key ) { $columns[ $column ][] = array( 'id' => $column . '-' . $key, 'enabled' => true, 'label' => $destinations[ $key ]['label'], 'destination' => $key, 'url' => '' ); }
	}
	return $columns;
}

function lunara_site_studio_footer_navigation_error( $field, $message = '' ) {
	return new WP_Error( 'site_studio_footer_invalid', __( 'The footer links were not accepted.', 'lunara-film' ), array( 'fields' => array( $field => $message ?: __( 'Check this footer link and try again.', 'lunara-film' ) ) ) );
}

/** Pure URL syntax validation: no fetches or DNS lookups, and no lossy sanitizing. */
function lunara_site_studio_footer_navigation_url( $value ) {
	if ( ! is_string( $value ) || '' === $value || strlen( $value ) > 2048 || preg_match( '/[\x00-\x20\x7f<>"\x27\\\\]/', $value ) || preg_match( '/%(?![0-9a-f]{2})|%(?:0[0-9a-f]|1[0-9a-f]|20|7f|5c)/i', $value ) ) { return false; }
	if ( '/' === substr( $value, 0, 1 ) ) { return '/' !== substr( rawurldecode( $value ), 1, 1 ) ? $value : false; }
	$parts = wp_parse_url( $value );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || preg_match( '/[%\s]/', $parts['host'] ) || false === filter_var( $value, FILTER_VALIDATE_URL ) ) { return false; }
	return $value;
}

function lunara_site_studio_footer_navigation_validate( $navigation ) {
	if ( ! is_array( $navigation ) || array( 'mode', 'columns' ) !== array_keys( $navigation ) ) { return lunara_site_studio_footer_navigation_error( 'navigation', __( 'Reload the Footer editor to get every control.', 'lunara-film' ) ); }
	if ( ! in_array( $navigation['mode'], array( 'inherited', 'custom' ), true ) ) { return lunara_site_studio_footer_navigation_error( 'navigation.mode' ); }
	if ( ! is_array( $navigation['columns'] ) || array( 'editorial', 'oscars', 'utility' ) !== array_keys( $navigation['columns'] ) ) { return lunara_site_studio_footer_navigation_error( 'navigation.columns' ); }
	$builtins = array( 'home', 'reviews', 'journal', 'about', 'editorial-policy', 'oscars', 'categories', 'ceremonies', 'ledger', 'search', 'contact', 'rss', 'privacy', 'custom' );
	$seen = array();
	foreach ( $navigation['columns'] as $column => $rows ) {
		$path = 'navigation.columns.' . $column;
		if ( ! is_array( $rows ) || array_values( $rows ) !== $rows || count( $rows ) > 12 ) { return lunara_site_studio_footer_navigation_error( $path, __( 'Use at most twelve links in each column.', 'lunara-film' ) ); }
		foreach ( $rows as $index => $row ) {
			$row_path = $path . '.' . $index;
			if ( ! is_array( $row ) || array( 'id', 'enabled', 'label', 'destination', 'url' ) !== array_keys( $row ) ) { return lunara_site_studio_footer_navigation_error( $row_path . '.label' ); }
			if ( ! is_string( $row['id'] ) || 1 !== preg_match( '/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $row['id'] ) || isset( $seen[ $row['id'] ] ) ) { return lunara_site_studio_footer_navigation_error( $row_path . '.id' ); }
			$seen[ $row['id'] ] = true;
			if ( ! is_bool( $row['enabled'] ) ) { return lunara_site_studio_footer_navigation_error( $row_path . '.enabled' ); }
			if ( ! is_string( $row['label'] ) || '' === trim( $row['label'] ) || preg_match( '/[\x00-\x1f\x7f<>]/', $row['label'] ) || 1 !== preg_match( '/^.{1,80}$/usD', $row['label'] ) || sanitize_text_field( $row['label'] ) !== $row['label'] ) { return lunara_site_studio_footer_navigation_error( $row_path . '.label', __( 'Enter a plain text label of one to eighty characters.', 'lunara-film' ) ); }
			if ( ! is_string( $row['destination'] ) || ! in_array( $row['destination'], $builtins, true ) ) { return lunara_site_studio_footer_navigation_error( $row_path . '.destination' ); }
			if ( 'custom' === $row['destination'] ? false === lunara_site_studio_footer_navigation_url( $row['url'] ) : '' !== $row['url'] ) { return lunara_site_studio_footer_navigation_error( $row_path . '.url', __( 'Use an HTTP(S) or site-relative URL for a custom link; built-in links keep their current destination.', 'lunara-film' ) ); }
		}
	}
	return $navigation;
}

function lunara_site_studio_footer_navigation_decode( $value ) {
	if ( ! is_string( $value ) || strlen( $value ) > 100000 ) { return false; }
	$object = json_decode( $value );
	if ( ! $object instanceof stdClass || array( 'version', 'columns' ) !== array_keys( get_object_vars( $object ) ) || 1 !== $object->version || ! $object->columns instanceof stdClass || array( 'editorial', 'oscars', 'utility' ) !== array_keys( get_object_vars( $object->columns ) ) ) { return false; }
	foreach ( $object->columns as $rows ) { if ( ! is_array( $rows ) ) { return false; } foreach ( $rows as $row ) { if ( ! $row instanceof stdClass ) { return false; } } }
	$decoded = json_decode( $value, true );
	$navigation = array( 'mode' => 'custom', 'columns' => $decoded['columns'] );
	return is_wp_error( lunara_site_studio_footer_navigation_validate( $navigation ) ) ? false : $navigation;
}

function lunara_site_studio_footer_navigation_status() {
	$missing = new stdClass(); $value = get_theme_mod( 'lunara_footer_navigation', $missing );
	$invalid = $value !== $missing && false === lunara_site_studio_footer_navigation_decode( $value );
	return array( 'invalid' => $invalid, 'message' => $invalid ? __( 'The saved link lists could not be read. The standard links remain visible. Apply custom lists to replace them.', 'lunara-film' ) : '' );
}

function lunara_site_studio_footer_navigation_read_state() {
	$state = lunara_site_studio_mod_surface_read_state( lunara_site_studio_footer_spec() );
	$navigation = lunara_site_studio_footer_navigation_decode( get_theme_mod( 'lunara_footer_navigation', null ) );
	$state['navigation'] = false === $navigation ? array( 'mode' => 'inherited', 'columns' => lunara_site_studio_footer_navigation_inherited_columns() ) : $navigation;
	return $state;
}

function lunara_site_studio_footer_navigation_validate_state( $candidate ) {
	if ( ! is_array( $candidate ) || array( 'brand', 'columns', 'copyright', 'navigation' ) !== array_keys( $candidate ) ) { return lunara_site_studio_footer_navigation_error( 'state', __( 'Reload the Footer editor to get every control.', 'lunara-film' ) ); }
	$scalars = $candidate; unset( $scalars['navigation'] );
	$validated = lunara_site_studio_mod_surface_validate_state( $scalars, lunara_site_studio_footer_spec(), 'site_studio_footer' );
	if ( is_wp_error( $validated ) ) { return $validated; }
	$navigation = lunara_site_studio_footer_navigation_validate( $candidate['navigation'] );
	if ( is_wp_error( $navigation ) ) { return $navigation; }
	$validated['navigation'] = $navigation;
	return $validated;
}

function lunara_site_studio_footer_navigation_serialize( $navigation ) {
	return wp_json_encode( array( 'version' => 1, 'columns' => $navigation['columns'] ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/** Keep every unchanged raw scalar, including absent defaults and old string booleans. */
function lunara_site_studio_footer_navigation_desired_snapshot( $validated, $before ) {
	$current = lunara_site_studio_footer_navigation_read_state();
	$desired = $before;
	foreach ( lunara_site_studio_footer_spec() as $group => $fields ) {
		foreach ( $fields as $field => $definition ) { if ( $validated[ $group ][ $field ] !== $current[ $group ][ $field ] ) { $desired[ $definition['mod'] ] = array( 'present' => true, 'value' => $validated[ $group ][ $field ] ); } }
	}
	if ( 'custom' === $validated['navigation']['mode'] ) { if ( $validated['navigation'] !== $current['navigation'] ) { $desired['lunara_footer_navigation'] = array( 'present' => true, 'value' => lunara_site_studio_footer_navigation_serialize( $validated['navigation'] ) ); } }
	elseif ( 'custom' === $current['navigation']['mode'] ) { $desired['lunara_footer_navigation'] = array( 'present' => false, 'value' => null ); }
	return $desired;
}

/** Reuse the shared verified writer, but do not touch entries that are unchanged. */
function lunara_site_studio_footer_navigation_apply_snapshot( $snapshot ) {
	$keys = lunara_site_studio_footer_navigation_keys();
	if ( ! lunara_site_studio_valid_mod_snapshot( $snapshot, $keys ) ) { return false; }
	try {
		foreach ( $keys as $key ) {
			$entry = array( $key => $snapshot[ $key ] );
			if ( $entry !== lunara_site_studio_raw_mod_snapshot( array( $key ) ) && ! lunara_site_studio_apply_mod_snapshot( $entry, array( $key ) ) ) { return false; }
		}
		return $snapshot === lunara_site_studio_raw_mod_snapshot( $keys );
	} catch ( Throwable $error ) { return false; }
}

function lunara_site_studio_footer_navigation_save_state( $candidate ) {
	$validated = lunara_site_studio_footer_navigation_validate_state( $candidate );
	if ( is_wp_error( $validated ) ) { return $validated; }
	$keys = lunara_site_studio_footer_navigation_keys();
	$before = lunara_site_studio_raw_mod_snapshot( $keys );
	if ( ! lunara_site_studio_valid_mod_snapshot( $before, $keys ) ) { return new WP_Error( 'site_studio_footer_invalid', __( 'The saved footer cannot be safely revised.', 'lunara-film' ) ); }
	if ( ! lunara_site_studio_footer_navigation_apply_snapshot( lunara_site_studio_footer_navigation_desired_snapshot( $validated, $before ) ) ) {
		return new WP_Error( lunara_site_studio_footer_navigation_apply_snapshot( $before ) ? 'site_studio_footer_write_failed' : 'site_studio_footer_rollback_failed', __( 'The footer could not be saved safely.', 'lunara-film' ) );
	}
	$revision_id = lunara_site_studio_private_revision( 'site-footer', array( 'mods' => $before ), 'save' );
	if ( is_wp_error( $revision_id ) ) {
		return lunara_site_studio_footer_navigation_apply_snapshot( $before ) ? $revision_id : new WP_Error( 'site_studio_footer_rollback_failed', __( 'The footer could not be restored safely.', 'lunara-film' ) );
	}
	return array( 'state' => lunara_site_studio_footer_navigation_read_state(), 'changed_sections' => array( 'footer' ), 'revision_id' => $revision_id, 'timestamp' => current_time( 'mysql' ) );
}

function lunara_site_studio_footer_navigation_restore_revision( $revision_id ) {
	$target = lunara_site_studio_private_revision_target( 'site-footer', $revision_id );
	if ( is_wp_error( $target ) ) { return $target; }
	$keys = lunara_site_studio_footer_navigation_keys();
	$current = lunara_site_studio_raw_mod_snapshot( $keys );
	$mods = is_array( $target ) && array( 'mods' ) === array_keys( $target ) ? lunara_site_studio_merge_legacy_mod_snapshot( $target['mods'], $current, $keys, lunara_site_studio_mod_surface_keys( lunara_site_studio_footer_spec() ) ) : false;
	if ( false === $mods || ! lunara_site_studio_valid_mod_snapshot( $current, $keys ) ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The selected revision is invalid.', 'lunara-film' ) ); }
	$safety_id = lunara_site_studio_private_revision( 'site-footer', array( 'mods' => $current ), 'restore-safety' );
	if ( is_wp_error( $safety_id ) ) { return $safety_id; }
	if ( ! lunara_site_studio_footer_navigation_apply_snapshot( $mods ) ) {
		return new WP_Error( lunara_site_studio_footer_navigation_apply_snapshot( $current ) ? 'site_studio_footer_restore_failed' : 'site_studio_footer_rollback_failed', __( 'The footer revision could not be restored safely.', 'lunara-film' ) );
	}
	return array( 'state' => lunara_site_studio_footer_navigation_read_state(), 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
}

/** Called only after the ordinary owner/user/route/hash/expiry token checks. */
function lunara_site_studio_footer_navigation_preview_state_safe( $state ) {
	if ( ! is_array( $state ) ) { return false; }
	if ( array( 'brand', 'columns', 'copyright' ) === array_keys( $state ) ) { return $state === lunara_site_studio_mod_surface_validate_state( $state, lunara_site_studio_footer_spec(), 'site_studio_footer' ); }
	return $state === lunara_site_studio_footer_navigation_validate_state( $state );
}

function lunara_site_studio_footer_navigation_preview_install_state( $state ) {
	if ( ! lunara_site_studio_footer_navigation_preview_state_safe( $state ) ) { return false; }
	if ( isset( $state['navigation'] ) ) {
		// Compute before installing filters: Preview must preserve the same raw
		// legacy copy and absent defaults as an unchanged-field Apply.
		$current = lunara_site_studio_raw_mod_snapshot( lunara_site_studio_footer_navigation_keys() );
		$desired = lunara_site_studio_footer_navigation_desired_snapshot( $state, $current );
	} else {
		// Authenticated pre-extension tokens retain their original six-field behavior.
		$current = array();
		$desired = lunara_site_studio_mod_surface_desired_snapshot( $state, lunara_site_studio_footer_spec() );
	}
	foreach ( $desired as $mod => $entry ) {
		if ( isset( $current[ $mod ] ) && $current[ $mod ] === $entry ) { continue; }
		$value = $entry['present'] ? $entry['value'] : null;
		add_filter( 'theme_mod_' . $mod, static function () use ( $value ) { return $value; }, PHP_INT_MAX );
	}
	return true;
}

/** Disabled/unavailable rows remain in the editor while public lists omit them. */
function lunara_site_studio_footer_navigation_resolved_columns() {
	$state = lunara_site_studio_footer_navigation_read_state();
	$destinations = lunara_site_studio_footer_navigation_destinations();
	$columns = array();
	foreach ( $state['navigation']['columns'] as $column => $rows ) {
		$columns[ $column ] = array();
		foreach ( $rows as $row ) {
			$url = 'custom' === $row['destination'] ? $row['url'] : $destinations[ $row['destination'] ]['url'];
			if ( ! $row['enabled'] || '' === $url ) { continue; }
			$columns[ $column ][] = array( 'label' => $row['label'], 'url' => $url );
		}
	}
	return $columns;
}
