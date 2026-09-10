<?php
/** Method persistence, public selection, artwork and metadata regression contract. */
define( 'LUNARA_METHOD_BOOTSTRAP_ONLY', true );
require __DIR__ . '/site-studio-pilot-runtime.php';
$checks = 0;
function method_check( $condition, $message ) { global $checks; $checks++; if ( ! $condition ) { throw new RuntimeException( $message ); } }
method_check( function_exists( 'lunara_site_studio_method_backdrop_defaults' ), 'Method framing defaults must exist.' );
$defaults = array( 'hidden' => false, 'focal_x' => 50, 'focal_y' => 26, 'fit' => 'cover', 'zoom' => 100 );
$lunara_pilot_theme_mods = array( 'lunara_home_pairing_desk_review_id' => 201 );
$state = lunara_site_studio_lunara_method_read_state();
method_check( $state['backdrop'] === $defaults && 'manual' === $state['review_mode'], 'Legacy ID determines initial mode and preserves center/26 framing.' );
$old_keys = array_slice( lunara_site_studio_lunara_method_keys(), 0, 5 );
$historical = array( 'mods' => lunara_site_studio_raw_mod_snapshot( $old_keys ) );
method_check( lunara_site_studio_valid_method_revision_config( $historical ), 'Historical five-mod snapshots remain restorable.' );
$id = lunara_site_studio_private_revision( 'lunara-method', $historical, 'save' );
$state['review_mode'] = 'automatic'; $state['backdrop']['focal_x'] = 17; $state['backdrop']['zoom'] = 109;
method_check( ! is_wp_error( lunara_site_studio_lunara_method_save_state( $state ) ), 'Automatic accepts and retains manual IDs.' );
method_check( $state['backdrop'] === lunara_method_backdrop_settings(), 'Apply persists every exact backdrop framing value.' );
method_check( 201 === get_theme_mod( 'lunara_home_pairing_desk_review_id' ) && 'automatic' === get_theme_mod( 'lunara_home_pairing_desk_review_mode' ), 'Mode change persists without discarding the manual ID.' );
$restored = lunara_site_studio_lunara_method_restore_revision( $id );
method_check( ! is_wp_error( $restored ) && $restored['state']['backdrop'] === $defaults && 'manual' === $restored['state']['review_mode'] && ! array_key_exists( 'lunara_home_pairing_desk_review_mode', $lunara_pilot_theme_mods ) && ! array_key_exists( 'lunara_home_pairing_desk_backdrop', $lunara_pilot_theme_mods ), 'Historical restore removes additive overrides exactly.' );
foreach ( array( array( 'hidden', 1 ), array( 'focal_x', '50' ), array( 'focal_y', -1 ), array( 'zoom', 113 ), array( 'fit', 'contain' ) ) as $bad ) { $candidate = $state; $candidate['backdrop'][ $bad[0] ] = $bad[1]; method_check( is_wp_error( lunara_site_studio_lunara_method_validate_state( $candidate ) ), 'Invalid framing type/range is rejected: ' . $bad[0] ); }
$candidate = $state; $candidate['review_mode'] = 'auto'; method_check( is_wp_error( lunara_site_studio_lunara_method_validate_state( $candidate ) ), 'Mode enum is exact.' );
$candidate = $state; $candidate['backdrop']['extra'] = true; method_check( is_wp_error( lunara_site_studio_lunara_method_validate_state( $candidate ) ), 'Unknown framing fields fail closed.' );
$state['review_id'] = 999; $state['review_mode'] = 'manual';
method_check( ! is_wp_error( lunara_site_studio_lunara_method_validate_state( $state ) ), 'Unavailable retained Manual IDs are valid candidates with editor warnings.' );
$before = $lunara_pilot_theme_mods; $lunara_pilot_mod_fault = array( 'key' => 'lunara_home_pairing_desk_backdrop', 'mode' => 'fail', 'remaining' => 1 );
method_check( is_wp_error( lunara_site_studio_lunara_method_save_state( $state ) ) && $before === $lunara_pilot_theme_mods, 'Failed additive write rolls back original mod presence.' );
$method_query = array(); $source_version = 1;
function get_posts( $args ) { global $method_query, $lunara_pilot_posts; $method_query = $args; return isset( $args['fields'] ) ? array( 202, 201 ) : array( $lunara_pilot_posts[201] ); }
function update_meta_cache() {}
function get_post_meta( $id, $key, $single ) { return 201 === $id && '_lunara_theme_echo' === $key ? 'A pairing' : ''; }
function get_the_title( $id ) { return 201 === $id ? 'Published Review' : 'PRIVATE SECRET TITLE'; }
function get_the_date( $format, $id ) { return 'Sep 10, 2026'; }
function get_permalink( $id ) { return 'https://example.test/reviews/published/'; }
function lunara_get_review_hero_image_url( $id ) { global $source_version; return 'https://example.test/current-hero-' . $source_version . '.jpg'; }
function wp_attachment_is_image( $id ) { return 301 === $id; }
function wp_get_attachment_image_url( $id, $size ) { return 301 === $id ? 'https://example.test/custom.jpg' : false; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_url( $text ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_attr_e( $text ) { echo esc_attr( $text ); }
function esc_html_e( $text ) { echo esc_html( $text ); }
function lunara_render_pair_it_with_cards( $id ) { return '<div class="lunara-pair-cards">Unchanged cards</div>'; }
$lunara_pilot_posts[201] = new WP_Post( 201, 'review', 'publish' );
$lunara_pilot_posts[202] = new WP_Post( 202, 'review', 'private' );
$source = file_get_contents( dirname( __DIR__ ) . '/functions.php' );
$start = strpos( $source, "if ( ! function_exists( 'lunara_get_pairing_desk_review_id' ) )" );
$end = strpos( $source, "if ( ! function_exists( 'lunara_get_hero_featured_slides' ) )", $start );
eval( substr( $source, $start, $end - $start ) );
foreach ( array( 'cover', 'full', 'hidden' ) as $fixture_fit ) {
	if ( ! in_array( '--fixture-framing=' . $fixture_fit, $argv, true ) ) { continue; }
	$lunara_pilot_theme_mods = array(
		'lunara_home_pairing_desk_review_id' => 201,
		'lunara_home_pairing_desk_review_mode' => 'manual',
		'lunara_home_pairing_desk_backdrop' => array( 'hidden' => 'hidden' === $fixture_fit, 'focal_x' => 17, 'focal_y' => 81, 'fit' => 'full' === $fixture_fit ? 'full' : 'cover', 'zoom' => 109 ),
	);
	echo json_encode( array( 'state' => lunara_site_studio_lunara_method_read_state(), 'html' => lunara_render_home_pairing_desk() ) );
	exit;
}
$lunara_pilot_theme_mods = array( 'lunara_home_pairing_desk_review_id' => 202 );
method_check( 201 === lunara_get_pairing_desk_review_id(), 'Legacy invalid curated Review still falls back before Apply.' );
$lunara_pilot_theme_mods['lunara_home_pairing_desk_review_mode'] = 'manual';
method_check( 0 === lunara_get_pairing_desk_review_id() && false === strpos( lunara_render_home_pairing_desk(), '<section' ), 'Applied unavailable Manual choice hides the Method.' );
$lunara_pilot_theme_mods['lunara_home_pairing_desk_review_id'] = 0;
method_check( 0 === lunara_get_pairing_desk_review_id(), 'Applied empty Manual choice does not fall back.' );
$lunara_pilot_theme_mods['lunara_home_pairing_desk_review_mode'] = 'automatic';
$lunara_pilot_theme_mods['lunara_home_pairing_desk_review_id'] = 202;
method_check( 201 === lunara_get_pairing_desk_review_id() && 20 === $method_query['posts_per_page'] && array( 'date' => 'DESC', 'ID' => 'DESC' ) === $method_query['orderby'], 'Automatic ignores retained IDs and explicitly orders a bounded eligible scan by date.' );
$before = $lunara_pilot_theme_mods;
$metadata = lunara_site_studio_method_metadata( new Lunara_Pilot_REST_Request( array( 'mode' => 'automatic', 'review_id' => '202', 'image_id' => '999' ) ) )->data;
method_check( 201 === $metadata['item']['id'] && '' === $metadata['image_url'] && false === strpos( json_encode( $metadata ), 'PRIVATE SECRET' ) && $before === $lunara_pilot_theme_mods, 'Read-only Automatic metadata excludes private retained selections and clears missing attachment URLs.' );
$manual = lunara_site_studio_method_item( 202 );
method_check( ! $manual['available'] && 'Unavailable Review' === $manual['title'] && '' === $manual['image_url'], 'Manual unavailable metadata never reveals the private title or image.' );
$source_version = 2; $metadata = lunara_site_studio_method_item( 201 );
method_check( false !== strpos( $metadata['image_url'], 'current-hero-2' ) && false !== strpos( lunara_render_home_pairing_desk(), 'current-hero-2' ), 'Metadata and public renderer resolve current canonical source artwork.' );
$framing = $defaults; $framing['focal_x'] = 17; $framing['focal_y'] = 81; $framing['zoom'] = 109;
$lunara_pilot_theme_mods['lunara_home_pairing_desk_backdrop'] = $framing;
$html = lunara_render_home_pairing_desk();
method_check( false !== strpos( $html, '--lunara-method-x:17%;--lunara-method-y:81%;--lunara-method-fit:cover;--lunara-method-zoom:1.09;' ) && false !== strpos( $html, 'Unchanged cards' ), 'Public rendering consumes exact framing without changing pairing content.' );
$lunara_pilot_theme_mods['lunara_home_pairing_desk_backdrop_id'] = 999;
method_check( false === strpos( lunara_render_home_pairing_desk(), 'class="lunara-pairing-desk-backdrop' ), 'Applied missing backdrop does not silently substitute source artwork.' );
unset( $lunara_pilot_theme_mods['lunara_home_pairing_desk_review_mode'] );
method_check( false !== strpos( lunara_render_home_pairing_desk(), 'current-hero-2' ), 'Historical missing attachment retains old source fallback before Apply.' );
$lunara_pilot_theme_mods['lunara_home_pairing_desk_backdrop']['hidden'] = true;
method_check( false === strpos( lunara_render_home_pairing_desk(), 'class="lunara-pairing-desk-backdrop' ), 'Explicit Remove image hides the decorative artwork.' );
lunara_site_studio_method_search( new Lunara_Pilot_REST_Request( array( 'search' => 'film' ) ) );
method_check( 20 === $method_query['posts_per_page'] && 'publish' === $method_query['post_status'] && false === $method_query['has_password'], 'Search is bounded to published unprotected Reviews.' );
method_check( 400 === lunara_site_studio_method_metadata( new Lunara_Pilot_REST_Request( array( 'mode' => 'manual', 'review_id' => '1.5', 'image_id' => '0' ) ) )->status, 'Metadata rejects malformed identifiers.' );
function lunara_control_desk_admin_url( $args ) { return admin_url( 'admin.php?page=lunara-control-desk' ); }
function lunara_control_desk_bounded_return_url( $key, $surface, $fallback ) { return $fallback; }
function check_admin_referer( $action, $field ) { method_check( 'lunara_save_pairing_desk_copy' === $action && 'lunara_pairing_desk_copy_nonce' === $field, 'Retired Method handler retains its nonce check.' ); }
function wp_safe_redirect( $url ) { method_check( false !== strpos( $url, 'surface=lunara-method' ), 'Retired Method handler redirects into the shared editor.' ); throw new RuntimeException( 'redirect completed' ); }
$control = file_get_contents( dirname( __DIR__ ) . '/inc/control-desk.php' );
$start = strpos( $control, 'function lunara_control_desk_save_pairing_desk_copy()' );
$end = strpos( $control, "\n}\n", $start ) + 2;
eval( substr( $control, $start, $end - $start ) );
$_POST = array(); $before = $lunara_pilot_theme_mods;
try { lunara_control_desk_save_pairing_desk_copy(); } catch ( RuntimeException $error ) { method_check( 'redirect completed' === $error->getMessage(), 'Retired handler completes through the shared destination.' ); }
method_check( $before === $lunara_pilot_theme_mods, 'Legacy saves with absent Method inputs cannot clear or overwrite Method settings.' );
$start = strpos( $control, 'function lunara_control_desk_save_homepage_studio()' ); $end = strpos( $control, "\n}\n", $start );
method_check( false === strpos( substr( $control, $start, $end - $start ), 'lunara_home_pairing_desk_' ), 'Unrelated classic Homepage save has no Method presentation writes.' );
echo "Method persistence/public/metadata checks: {$checks} passed.\n";
