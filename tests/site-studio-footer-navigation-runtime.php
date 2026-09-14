<?php
/** Footer ownership, strict input, exact recovery and actual private/public output. */
require __DIR__ . '/site-studio-footer-fixture.php';
$checks = 0;
function footer_assert( $condition, $message ) { global $checks; $checks++; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function footer_snapshot() { return lunara_site_studio_raw_mod_snapshot( lunara_site_studio_footer_navigation_keys() ); }
function footer_row( $id = 'chosen', $label = 'Selected link', $destination = 'custom', $url = '/chosen/?q=film#read' ) { return array( 'id' => $id, 'enabled' => true, 'label' => $label, 'destination' => $destination, 'url' => $url ); }
function footer_candidate() { $state = lunara_site_studio_footer_navigation_read_state(); $state['navigation'] = array( 'mode' => 'custom', 'columns' => array( 'editorial' => array( footer_row() ), 'oscars' => array(), 'utility' => array() ) ); return $state; }

footer_fixture_reset();
$default = lunara_site_studio_footer_read_state();
footer_assert( count( lunara_site_studio_footer_keys() ) === 7 && count( lunara_site_studio_mod_surface_keys( lunara_site_studio_footer_spec() ) ) === 6, 'Footer extends exactly seven keys without changing the frozen scalar spec.' );
footer_assert( 'inherited' === $default['navigation']['mode'] && array() === $lunara_pilot_theme_mods, 'Reading standard links is not adoption and writes no missing defaults.' );
$html = footer_fixture_render();
preg_match( '~<nav class="lunara-footer-nav-grid".*?</nav>~s', $html, $nav );
preg_match_all( '~<a href="([^"]+)">([^<]+)</a>~', $nav[0], $links );
footer_assert( array( 'Home', 'Reviews', 'Journal', 'About', 'Editorial Policy', 'Oscars', 'Categories', 'Ceremonies', 'Full Ledger', 'Search', 'Contact', 'RSS Feed', 'Privacy' ) === $links[2], 'Actual inherited footer retains every original label and its order.' );
footer_assert( strpos( $html, 'lunara-footer-legal' ) !== false && substr_count( $html, '>Privacy</a>' ) === 2, 'Separate legal Privacy row remains outside list ownership.' );
$GLOBALS['footer_privacy'] = '';
footer_assert( strpos( footer_fixture_render(), '>Privacy</a>' ) === false, 'Unconfigured Privacy is omitted from both inherited row locations.' );
unset( $GLOBALS['footer_privacy'] );
$copy = $default; $copy['brand']['tagline'] = 'A new closing line.';
$save = lunara_site_studio_footer_save_state( $copy );
footer_assert( ! is_wp_error( $save ) && ! array_key_exists( 'lunara_footer_navigation', $lunara_pilot_theme_mods ), 'Copy-only Apply does not adopt inherited lists.' );

footer_fixture_reset();
$lunara_pilot_theme_mods = array( 'lunara_footer_show_logo' => '1', 'lunara_footer_tagline' => '  Kept raw text  ', 'lunara_footer_col2_heading' => '', 'lunara_footer_copyright' => 'Lunara Film', 'footer-editorial-menu' => array( 123 ), 'unrelated' => 'untouched' );
$legacy = footer_snapshot(); $candidate = footer_candidate();
$save = lunara_site_studio_footer_save_state( $candidate );
footer_assert( ! is_wp_error( $save ) && is_string( $lunara_pilot_theme_mods['lunara_footer_navigation'] ), 'Applying custom lists adopts one canonical JSON mod.' );
$after = footer_snapshot(); unset( $after['lunara_footer_navigation'], $legacy['lunara_footer_navigation'] );
footer_assert( $legacy === $after && $lunara_pilot_theme_mods['footer-editorial-menu'] === array( 123 ), 'Links-only Apply preserves all six raw scalar values/absence and dormant menu data.' );
$html = footer_fixture_render();
footer_assert( strpos( $html, 'href="/chosen/?q=film#read"' ) !== false && strpos( $html, '>Selected link</a>' ) !== false && strpos( $html, '>Home</a>' ) === false, 'Actual footer renders explicit custom selection without silently substituting standard links.' );
$pre_adoption_revision = $save['revision_id'];
$raw_custom = $lunara_pilot_theme_mods['lunara_footer_navigation'];
$lunara_pilot_theme_mods['lunara_footer_navigation'] = str_replace( '{"version":1,', "{\n\"version\":1,", $raw_custom );
$spaced_json = $lunara_pilot_theme_mods['lunara_footer_navigation'];
$copy = lunara_site_studio_footer_read_state(); $copy['brand']['tagline'] = 'Copy adjustment';
footer_assert( ! is_wp_error( lunara_site_studio_footer_save_state( $copy ) ) && $spaced_json === $lunara_pilot_theme_mods['lunara_footer_navigation'], 'Copy-only Apply preserves unchanged valid JSON bytes.' );
$restored = lunara_site_studio_footer_restore_revision( $pre_adoption_revision );
footer_assert( ! is_wp_error( $restored ) && ! array_key_exists( 'lunara_footer_navigation', $lunara_pilot_theme_mods ), 'A new seven-key pre-adoption revision restores the original absent navigation mod.' );

// Exact historical six-key revisions retain current navigation, including invalid raw bytes.
$legacy = footer_snapshot(); unset( $legacy['lunara_footer_navigation'] );
$legacy['lunara_footer_tagline'] = array( 'present' => true, 'value' => 'Historical scalar copy' );
$legacy_id = lunara_site_studio_private_revision( 'site-footer', array( 'mods' => $legacy ), 'save' );
lunara_site_studio_footer_save_state( footer_candidate() );
$live_links = $lunara_pilot_theme_mods['lunara_footer_navigation'];
footer_assert( ! is_wp_error( lunara_site_studio_footer_restore_revision( $legacy_id ) ) && array_key_exists( 'lunara_footer_navigation', $lunara_pilot_theme_mods ) && $live_links === $lunara_pilot_theme_mods['lunara_footer_navigation'] && 'Historical scalar copy' === get_theme_mod( 'lunara_footer_tagline' ), 'Exact old six-key History restores copy and retains currently adopted links.' );
$lunara_pilot_theme_mods['lunara_footer_navigation'] = '{invalid saved bytes';
$before = footer_snapshot(); $invalid_read = lunara_site_studio_footer_read_state();
footer_assert( lunara_site_studio_footer_navigation_status()['invalid'] && 'inherited' === $invalid_read['navigation']['mode'] && $before === footer_snapshot(), 'Invalid stored JSON is flagged, read safely, and never repaired on read.' );
footer_assert( ! is_wp_error( lunara_site_studio_footer_save_state( $invalid_read ) ) && '{invalid saved bytes' === get_theme_mod( 'lunara_footer_navigation' ), 'Inherited Apply does not silently overwrite invalid stored navigation.' );
footer_assert( ! is_wp_error( lunara_site_studio_footer_restore_revision( $legacy_id ) ) && '{invalid saved bytes' === get_theme_mod( 'lunara_footer_navigation' ), 'Old History retains even invalid current navigation bytes without normalization.' );

foreach ( array( 'extra', 'missing', 'wrong-entry' ) as $mutation ) {
	$bad = $legacy;
	if ( 'extra' === $mutation ) { $bad['unrelated'] = array( 'present' => true, 'value' => 'x' ); }
	elseif ( 'missing' === $mutation ) { unset( $bad['lunara_footer_show_logo'] ); }
	else { $bad['lunara_footer_show_logo']['present'] = 'yes'; }
	$id = lunara_site_studio_private_revision( 'site-footer', array( 'mods' => $bad ), 'save' ); $before = footer_snapshot();
	footer_assert( is_wp_error( lunara_site_studio_footer_restore_revision( $id ) ) && $before === footer_snapshot(), 'Malformed/near-legacy History fails before any mod write: ' . $mutation );
}

footer_fixture_reset(); $default = lunara_site_studio_footer_read_state(); $candidate = footer_candidate();
$invalids = array(); $bad = $candidate; unset( $bad['navigation'] ); $invalids[] = $bad;
$bad = $candidate; $bad['navigation']['unknown'] = true; $invalids[] = $bad;
$bad = $candidate; $bad['navigation']['columns']['editorial'] = array( 1 => footer_row() ); $invalids[] = $bad;
$bad = $candidate; $bad['navigation']['columns']['oscars'][] = footer_row(); $invalids[] = $bad;
foreach ( array( 'enabled' => 'true', 'id' => 'bad id', 'label' => '<b>Bad</b>', 'destination' => 'invented', 'url' => '//foreign.test' ) as $field => $value ) { $bad = $candidate; $bad['navigation']['columns']['editorial'][0][$field] = $value; $invalids[] = $bad; }
foreach ( array( '', str_repeat( 'x', 81 ), "Two\nlines", ' spaced ' ) as $value ) { $bad = $candidate; $bad['navigation']['columns']['editorial'][0]['label'] = $value; $invalids[] = $bad; }
foreach ( array( 'javascript:alert(1)', 'data:text/plain,x', 'https://user:pass@example.test/', '/%2fexample.test/', '/bad%zz', '/bad%0aheader', "/bad\\path", 'https://bad host.test/', 'https://.bad.test/', '/x"onclick="x', str_repeat( 'x', 2049 ) ) as $url ) { $bad = $candidate; $bad['navigation']['columns']['editorial'][0]['url'] = $url; $invalids[] = $bad; }
$bad = $candidate; $bad['navigation']['columns']['editorial'][0]['destination'] = 'home'; $invalids[] = $bad;
$bad = $candidate; $bad['navigation']['columns']['editorial'] = array(); for ( $i = 0; $i < 13; $i++ ) { $bad['navigation']['columns']['editorial'][] = footer_row( 'item-' . $i ); } $invalids[] = $bad;
foreach ( $invalids as $bad ) {
	$before = serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_option_writes ) );
	$error = lunara_site_studio_footer_save_state( $bad );
	footer_assert( is_wp_error( $error ), 'Malformed structure, unsafe URL or out-of-bounds row is rejected.' );
	footer_assert( $before === serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_option_writes ) ), 'Invalid candidate creates no mods or revision writes.' );
}
foreach ( array( 0, 1, 12 ) as $count ) {
	$candidate = footer_candidate(); $candidate['navigation']['columns']['editorial'] = array();
	for ( $i = 0; $i < $count; $i++ ) { $candidate['navigation']['columns']['editorial'][] = footer_row( 'row-' . $i, str_repeat( 'é', 80 ) ); }
	footer_assert( ! is_wp_error( lunara_site_studio_footer_save_state( $candidate ) ), 'Zero/one/twelve links and eighty Unicode characters are accepted.' );
	footer_assert( count( lunara_site_studio_footer_navigation_resolved_columns()['editorial'] ) === $count, 'Explicit empty/one/many rendering keeps the requested count.' );
}
$candidate = footer_candidate(); $candidate['navigation']['columns']['editorial'] = array( footer_row( 'dynamic-review', 'Reviews', 'reviews', '' ), footer_row( 'dynamic-journal', 'Journal', 'journal', '' ), footer_row( 'dynamic-rss', 'RSS', 'rss', '' ), footer_row( 'dynamic-privacy', 'Privacy', 'privacy', '' ), footer_row( 'disabled', 'Disabled' ) ); $candidate['navigation']['columns']['editorial'][4]['enabled'] = false;
lunara_site_studio_footer_save_state( $candidate );
$GLOBALS['footer_archives'] = array( 'review' => '/new-reviews/', 'journal' => '/new-journal/' ); $GLOBALS['footer_feed'] = '/new-feed/'; $GLOBALS['footer_privacy'] = '';
$rows = lunara_site_studio_footer_navigation_resolved_columns()['editorial'];
footer_assert( array( '/new-reviews/', '/new-journal/', '/new-feed/' ) === array_column( $rows, 'url' ) && count( lunara_site_studio_footer_read_state()['navigation']['columns']['editorial'] ) === 5, 'Built-ins follow current URLs; disabled/unavailable rows remain editable but are omitted publicly.' );

// Write/readback/revision failures must recover all seven raw entries.
foreach ( array( 0, 3, 6 ) as $slot ) { foreach ( array( 'fail', 'mismatch', 'throw_after', 'read_throw_after' ) as $mode ) {
	footer_fixture_reset(); $candidate = footer_candidate(); $candidate['brand'] = array( 'show_logo' => false, 'tagline' => 'Changed tagline' ); $candidate['columns'] = array( 'editorial' => 'A', 'oscars' => 'B', 'utility' => 'C' ); $candidate['copyright']['name'] = 'Changed name'; $before = footer_snapshot();
	$lunara_pilot_mod_fault = array( 'key' => lunara_site_studio_footer_keys()[$slot], 'mode' => $mode, 'remaining' => 1 );
	$error = lunara_site_studio_footer_save_state( $candidate );
	footer_assert( is_wp_error( $error ) && $before === footer_snapshot(), 'Every key is recovered after write failure at slot ' . $slot . ': ' . $mode );
} }
foreach ( array( 'fail', 'mismatch', 'throw_after', 'read_throw_after' ) as $mode ) {
	footer_fixture_reset(); $candidate = footer_candidate(); $before = footer_snapshot();
	$lunara_pilot_option_fault = array( 'key' => lunara_site_studio_revision_option_name( 'site-footer' ), 'mode' => $mode, 'remaining' => 1 );
	footer_assert( is_wp_error( lunara_site_studio_footer_save_state( $candidate ) ) && $before === footer_snapshot(), 'Revision durability failure rolls back the navigation mod: ' . $mode );
}
footer_fixture_reset(); $saved = lunara_site_studio_footer_save_state( footer_candidate() ); $before = footer_snapshot();
$lunara_pilot_option_fault = array( 'key' => lunara_site_studio_revision_option_name( 'site-footer' ), 'mode' => 'fail', 'remaining' => 1 );
footer_assert( is_wp_error( lunara_site_studio_footer_restore_revision( $saved['revision_id'] ) ) && $before === footer_snapshot(), 'Failed restore-safety revision causes zero target writes.' );
$changed = footer_candidate(); $changed['navigation']['columns']['editorial'][0]['label'] = 'Different';
$lunara_pilot_mod_fault = array( 'key' => 'lunara_footer_navigation', 'modes' => array( 'mismatch', 'fail' ), 'remaining' => 2 );
$error = lunara_site_studio_footer_save_state( $changed );
footer_assert( is_wp_error( $error ) && 'site_studio_footer_rollback_failed' === $error->get_error_code(), 'Failed rollback is distinguished from an ordinary write failure.' );

// Real authenticated resolver applies old scalar-only tokens without replacing current links.
footer_fixture_reset(); $public = footer_candidate(); lunara_site_studio_footer_save_state( $public );
$old = lunara_site_studio_mod_surface_read_state( lunara_site_studio_footer_spec() ); $old['brand']['tagline'] = 'Private old-token copy';
// Simulate a token issued by the prior release through the unchanged private storage API.
$token = lunara_site_studio_store_private_preview( 'site-footer', 'theme:site-footer', '/', $old );
footer_fixture_request( $token ); $before = serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_transients ) );
$resolved = lunara_site_studio_resolve_private_preview();
footer_assert( is_array( $resolved ) && strpos( footer_fixture_render(), 'Private old-token copy' ) !== false && strpos( footer_fixture_render(), '>Selected link</a>' ) !== false, 'Actual resolver accepts an authenticated exact old token and renders current custom lists with private scalar copy.' );
footer_assert( $before === serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_transients ) ), 'Old-token preview changes no public state or stored token.' );
unset( $GLOBALS['footer_filters'] );
$candidate = footer_candidate(); $candidate['navigation']['columns']['editorial'][0]['label'] = 'Private new navigation';
$token = lunara_site_studio_store_private_preview( 'site-footer', 'theme:site-footer', '/', $candidate ); footer_fixture_request( $token );
$before = serialize( $lunara_pilot_theme_mods );
footer_assert( is_array( lunara_site_studio_resolve_private_preview() ) && strpos( footer_fixture_render(), '>Private new navigation</a>' ) !== false && serialize( $lunara_pilot_theme_mods ) === $before, 'Actual new-token resolver renders candidate lists while public mods remain unchanged.' );
unset( $GLOBALS['footer_filters'] );
foreach ( array( 'user', 'host', 'route', 'capability', 'front-page' ) as $invalid ) {
	footer_fixture_request( $token );
	if ( 'user' === $invalid ) { $GLOBALS['footer_user'] = 99; }
	elseif ( 'host' === $invalid ) { $_SERVER['HTTP_HOST'] = 'foreign.test'; }
	elseif ( 'route' === $invalid ) { $_SERVER['REQUEST_URI'] = '/reviews/?' . $_SERVER['QUERY_STRING']; }
	elseif ( 'capability' === $invalid ) { $GLOBALS['footer_authorized'] = false; }
	else { $GLOBALS['footer_front_page'] = false; }
	footer_assert( false === lunara_site_studio_resolve_private_preview(), 'Private footer denies wrong ' . $invalid );
	unset( $GLOBALS['footer_user'], $GLOBALS['footer_authorized'], $GLOBALS['footer_front_page'] );
}
footer_fixture_request( $token );
$key = 'lunara_site_studio_preview_' . hash( 'sha256', $token ); $original = $lunara_pilot_transients[$key];
foreach ( array( 'owner' => 'theme:other', 'token_hash' => 'invalid-hash', 'expires' => 1 ) as $field => $value ) {
	$lunara_pilot_transients[$key]['value'][$field] = $value;
	footer_assert( false === lunara_site_studio_resolve_private_preview(), 'Private footer rejects changed token ' . $field );
	$lunara_pilot_transients[$key] = $original;
}
$partial = $old; unset( $partial['brand']['show_logo'] );
$lunara_pilot_transients[$key]['value']['state'] = $partial;
footer_assert( false === lunara_site_studio_resolve_private_preview(), 'A near-legacy authenticated token is rejected rather than inflated.' );
$lunara_pilot_transients[$key] = $original;
$context = lunara_site_studio_prepare_private_preview_response( 'lunara_site_studio_resolve_private_preview' );
footer_assert( is_array( $context ) && ! empty( $GLOBALS['footer_no_store'] ) && in_array( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', lunara_site_studio_private_preview_headers(), true ), 'Actual private response preparation retains no-store behavior.' );
unset( $GLOBALS['footer_filters'] );
$inherited = lunara_site_studio_footer_read_state(); $inherited['navigation']['mode'] = 'inherited';
$token = lunara_site_studio_store_private_preview( 'site-footer', 'theme:site-footer', '/', $inherited ); footer_fixture_request( $token ); $before = serialize( $lunara_pilot_theme_mods );
footer_assert( is_array( lunara_site_studio_resolve_private_preview() ) && strpos( footer_fixture_render(), '>Home</a>' ) !== false && $before === serialize( $lunara_pilot_theme_mods ), 'Explicit inherited private preview shows standard lists without deleting the public custom mod.' );
unset( $GLOBALS['footer_filters'] );
footer_assert( ! is_wp_error( lunara_site_studio_footer_save_state( $inherited ) ) && ! array_key_exists( 'lunara_footer_navigation', $lunara_pilot_theme_mods ), 'Explicit inherited Apply removes only the navigation mod.' );

// New Preview and Apply retain unchanged raw legacy copy, including values the
// bounded editor read normalizes. Old authenticated tokens keep their old semantics.
footer_fixture_reset();
$raw_tagline = str_repeat( 'A lasting film conversation. ', 12 );
$lunara_pilot_theme_mods = array( 'lunara_footer_tagline'=>$raw_tagline, 'lunara_footer_show_logo'=>'1', 'lunara_footer_col2_heading'=>'' );
$candidate = footer_candidate(); $before = serialize($lunara_pilot_theme_mods);
$token = lunara_site_studio_store_private_preview('site-footer','theme:site-footer','/',$candidate); footer_fixture_request($token);
footer_assert( is_array(lunara_site_studio_resolve_private_preview()) && str_contains(footer_fixture_render(),esc_html($raw_tagline)), 'Links-only private Preview retains raw long legacy tagline exactly like Apply.' );
footer_assert( get_theme_mod('lunara_footer_show_logo')==='1' && get_theme_mod('lunara_footer_col2_heading')==='' && get_theme_mod('lunara_footer_col1_heading','Absent heading')==='Absent heading' && $before===serialize($lunara_pilot_theme_mods), 'New Preview preserves unchanged scalar type, blank and absent defaults without writes.' );
unset($GLOBALS['footer_filters']);
$save=lunara_site_studio_footer_save_state($candidate);
footer_assert( !is_wp_error($save) && str_contains(footer_fixture_render(),esc_html($raw_tagline)), 'Links-only Apply matches the raw legacy presentation shown by Preview.' );
$candidate=lunara_site_studio_footer_read_state(); $candidate['brand']['tagline']='An intentional replacement';
$token=lunara_site_studio_store_private_preview('site-footer','theme:site-footer','/',$candidate); footer_fixture_request($token);
$before=serialize($lunara_pilot_theme_mods);
footer_assert( is_array(lunara_site_studio_resolve_private_preview()) && str_contains(footer_fixture_render(),'An intentional replacement') && $before===serialize($lunara_pilot_theme_mods), 'An explicitly changed scalar still previews privately before Apply.' );
unset($GLOBALS['footer_filters']);
require __DIR__ . '/site-studio-footer-retirement-cases.php';
echo "Footer navigation runtime passed: {$checks} checks.\n";
