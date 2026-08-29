<?php
/** Behavioral runtime for Task 4 commit 1 pilot adapters. */

define( 'ABSPATH', __DIR__ . '/' );

set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

$lunara_pilot_options = array();
$lunara_pilot_option_autoload = array();
$lunara_pilot_option_writes = array();
$lunara_pilot_option_fault = array();
$lunara_pilot_theme_mods = array();
$lunara_pilot_mod_fault = array();
$lunara_pilot_posts = array();
$lunara_pilot_post_fault = '';
$lunara_pilot_post_writes = array();
$lunara_pilot_transients = array();
$lunara_pilot_uuid = 0;
$lunara_pilot_now = 1900000000;
$lunara_pilot_mimes = array();

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code = '', $message = '', $data = null ) { $this->code = (string) $code; $this->message = (string) $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public $post_content;
	public function __construct( $id, $type, $status, $content = '' ) { $this->ID = $id; $this->post_type = $type; $this->post_status = $status; $this->post_content = $content; }
}
class WP_REST_Response {
	public $data;
	public $status;
	public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function get_data() { return $this->data; }
}
class Lunara_Pilot_REST_Request {
	private $params;
	public function __construct( $params ) { $this->params = $params; }
	public function get_param( $key ) { return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null; }
	public function get_header( $key ) { return 'pilot-nonce'; }
}

function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $text ) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) { return sanitize_key( str_replace( ' ', '-', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( is_scalar( $value ) ? (string) $value : '' ) ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function sanitize_hex_color( $value ) { return is_string( $value ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? strtolower( $value ) : false; }
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value ); }
function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : addslashes( (string) $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_url( $value, $component = -1 ) { return parse_url( $value, $component ); }
function esc_url_raw( $value ) { return (string) $value; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' ); }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( (string) $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args ); }
function add_action() { return true; }
function add_filter() { return true; }
function remove_filter() { return true; }
function apply_filters( $hook, $value ) { return $value; }
function current_user_can( $capability ) { return 'edit_theme_options' === $capability || 'manage_options' === $capability; }
function is_user_logged_in() { return true; }
function wp_verify_nonce( $nonce, $action ) { return 'pilot-nonce' === $nonce && 'wp_rest' === $action; }
function get_current_user_id() { return 41; }
function current_time( $type, $gmt = false ) { global $lunara_pilot_now; return 'timestamp' === $type ? $lunara_pilot_now : gmdate( 'Y-m-d H:i:s', $lunara_pilot_now ); }
function wp_generate_uuid4() { global $lunara_pilot_uuid; $lunara_pilot_uuid++; return sprintf( '10000000-0000-4000-8000-%012d', $lunara_pilot_uuid ); }
function wp_hash( $value ) { return hash_hmac( 'sha256', (string) $value, 'pilot-secret' ); }
function set_transient( $key, $value, $ttl ) { global $lunara_pilot_transients; $lunara_pilot_transients[ $key ] = array( 'value' => $value, 'ttl' => $ttl ); return true; }
function get_transient( $key ) { global $lunara_pilot_transients; return isset( $lunara_pilot_transients[ $key ] ) ? $lunara_pilot_transients[ $key ]['value'] : false; }

function lunara_pilot_consume_fault( &$fault, $key ) {
	if ( empty( $fault['key'] ) || $key !== $fault['key'] || empty( $fault['remaining'] ) ) { return '' ; }
	$fault['remaining']--;
	if ( ! empty( $fault['modes'] ) && is_array( $fault['modes'] ) ) { return array_shift( $fault['modes'] ); }
	return isset( $fault['mode'] ) ? $fault['mode'] : 'fail';
}
function get_option( $key, $default = false ) { global $lunara_pilot_options; return array_key_exists( $key, $lunara_pilot_options ) ? $lunara_pilot_options[ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) {
	global $lunara_pilot_options, $lunara_pilot_option_autoload, $lunara_pilot_option_writes, $lunara_pilot_option_fault;
	$lunara_pilot_option_writes[] = array( 'key' => $key, 'value' => $value, 'autoload' => $autoload );
	$mode = lunara_pilot_consume_fault( $lunara_pilot_option_fault, $key );
	if ( 'fail' === $mode ) { return false; }
	$requested_autoload = null === $autoload ? ( array_key_exists( $key, $lunara_pilot_option_autoload ) ? $lunara_pilot_option_autoload[ $key ] : true ) : (bool) $autoload;
	if ( array_key_exists( $key, $lunara_pilot_options ) && $lunara_pilot_options[ $key ] === $value && isset( $lunara_pilot_option_autoload[ $key ] ) && $lunara_pilot_option_autoload[ $key ] === $requested_autoload ) { return false; }
	$lunara_pilot_options[ $key ] = 'mismatch' === $mode ? array( 'corrupt' => true ) : $value;
	$lunara_pilot_option_autoload[ $key ] = $requested_autoload;
	return true;
}
function delete_option( $key ) {
	global $lunara_pilot_options, $lunara_pilot_option_autoload, $lunara_pilot_option_fault;
	$mode = lunara_pilot_consume_fault( $lunara_pilot_option_fault, $key );
	if ( 'fail' === $mode ) { return false; }
	if ( ! array_key_exists( $key, $lunara_pilot_options ) ) { return false; }
	unset( $lunara_pilot_options[ $key ] );
	unset( $lunara_pilot_option_autoload[ $key ] );
	return true;
}
function get_theme_mod( $key, $default = false ) { global $lunara_pilot_theme_mods; return array_key_exists( $key, $lunara_pilot_theme_mods ) ? $lunara_pilot_theme_mods[ $key ] : $default; }
function set_theme_mod( $key, $value ) {
	global $lunara_pilot_theme_mods, $lunara_pilot_mod_fault;
	$mode = lunara_pilot_consume_fault( $lunara_pilot_mod_fault, $key );
	if ( 'fail' === $mode ) { return; }
	$lunara_pilot_theme_mods[ $key ] = 'mismatch' === $mode ? '__mismatch__' : $value;
}
function remove_theme_mod( $key ) {
	global $lunara_pilot_theme_mods, $lunara_pilot_mod_fault;
	$mode = lunara_pilot_consume_fault( $lunara_pilot_mod_fault, $key );
	if ( 'fail' === $mode ) { return; }
	unset( $lunara_pilot_theme_mods[ $key ] );
}
function get_post( $id ) { global $lunara_pilot_posts; return isset( $lunara_pilot_posts[ absint( $id ) ] ) ? $lunara_pilot_posts[ absint( $id ) ] : null; }
function get_post_field( $field, $id ) { $post = get_post( $id ); return $post && isset( $post->{$field} ) ? $post->{$field} : ''; }
function get_post_mime_type( $id ) { global $lunara_pilot_mimes; return isset( $lunara_pilot_mimes[ absint( $id ) ] ) ? $lunara_pilot_mimes[ absint( $id ) ] : ''; }
function wp_update_post( $postarr, $wp_error = false ) {
	global $lunara_pilot_posts, $lunara_pilot_post_fault, $lunara_pilot_post_writes;
	$lunara_pilot_post_writes[] = array( 'postarr' => $postarr, 'wp_error' => $wp_error );
	$postarr = wp_unslash( $postarr );
	$mode = '';
	if ( is_array( $lunara_pilot_post_fault ) && ! empty( $lunara_pilot_post_fault['remaining'] ) ) { $lunara_pilot_post_fault['remaining']--; $mode = ! empty( $lunara_pilot_post_fault['modes'] ) ? array_shift( $lunara_pilot_post_fault['modes'] ) : 'fail'; }
	elseif ( is_string( $lunara_pilot_post_fault ) ) { $mode = $lunara_pilot_post_fault; $lunara_pilot_post_fault = ''; }
	if ( 'fail' === $mode ) { return $wp_error ? new WP_Error( 'injected_post_failure' ) : 0; }
	$id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;
	if ( ! isset( $lunara_pilot_posts[ $id ] ) ) { return $wp_error ? new WP_Error( 'missing_post' ) : 0; }
	$content = isset( $postarr['post_content'] ) ? (string) $postarr['post_content'] : $lunara_pilot_posts[ $id ]->post_content;
	if ( 'mismatch' === $mode ) { $content .= '<!-- mismatch -->'; }
	$lunara_pilot_posts[ $id ]->post_content = $content;
	return $id;
}
function parse_blocks( $content ) {
	$blocks = array();
	$pattern = '~<!--\s+wp:([a-z0-9-]+/[a-z0-9-]+)(?:\s+\{.*?\})?\s*(?:/-->|-->(.*?)<!--\s+/wp:\1\s+-->)~s';
	if ( preg_match_all( $pattern, (string) $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) { $blocks[] = array( 'blockName' => $match[1], 'serialized' => $match[0] ); }
	}
	return $blocks;
}
function serialize_block( $block ) { return isset( $block['serialized'] ) ? $block['serialized'] : ''; }
function has_block( $name, $content ) { return false !== strpos( (string) $content, '<!-- wp:' . $name ); }
function do_blocks( $content ) { return $content; }

function lunara_control_desk_brand_image_is_valid( $id ) { return 0 === absint( $id ) || 0 === strpos( (string) get_post_mime_type( $id ), 'image/' ); }
function lunara_control_desk_render_homepage_studio() {}
function lunara_control_desk_render_pairing_desk_form() {}
function lunara_control_desk_homepage_order_preset_specs() {
	return array(
		'editorial-default' => array( 'desktop_order' => array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ), 'mobile_order' => array( 'hero', 'dispatch', 'latest-reviews', 'pairing-desk', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ) ),
		'journal-first' => array( 'desktop_order' => array( 'hero', 'dispatch', 'latest-reviews', 'pairing-desk', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ), 'mobile_order' => array( 'hero', 'dispatch', 'latest-reviews', 'pairing-desk', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ) ),
		'oscars-forward' => array( 'desktop_order' => array( 'hero', 'oscar-facts', 'oscar-picks', 'oscar-spotlight', 'database', 'ledger', 'latest-reviews', 'pairing-desk', 'dispatch', 'featured', 'deep-cuts' ), 'mobile_order' => array( 'hero', 'oscar-facts', 'oscar-picks', 'dispatch', 'latest-reviews', 'pairing-desk', 'oscar-spotlight', 'database', 'ledger', 'featured', 'deep-cuts' ) ),
	);
}

function lunara_pilot_assert( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}
function lunara_pilot_reset( $content = '' ) {
	global $lunara_pilot_options, $lunara_pilot_option_autoload, $lunara_pilot_option_writes, $lunara_pilot_option_fault, $lunara_pilot_theme_mods, $lunara_pilot_mod_fault, $lunara_pilot_posts, $lunara_pilot_post_fault, $lunara_pilot_post_writes, $lunara_pilot_transients, $lunara_pilot_mimes;
	$lunara_pilot_options = array( 'show_on_front' => 'page', 'page_on_front' => 101 );
	$lunara_pilot_option_autoload = array( 'show_on_front' => true, 'page_on_front' => true );
	$lunara_pilot_option_writes = array();
	$lunara_pilot_option_fault = array();
	$lunara_pilot_theme_mods = array();
	$lunara_pilot_mod_fault = array();
	$lunara_pilot_posts = array(
		101 => new WP_Post( 101, 'page', 'publish', $content ),
		201 => new WP_Post( 201, 'review', 'publish', '' ),
		202 => new WP_Post( 202, 'review', 'draft', '' ),
		203 => new WP_Post( 203, 'post', 'publish', '' ),
	);
	$lunara_pilot_post_fault = '';
	$lunara_pilot_post_writes = array();
	$lunara_pilot_transients = array();
	$lunara_pilot_mimes = array( 301 => 'image/jpeg', 302 => 'application/pdf' );
}
function lunara_pilot_block_content() {
	return '<!-- wp:core/heading {"level":2} -->Before<!-- /wp:core/heading -->' . "\n\n"
		. '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' . "\n\n"
		. '<!-- wp:plugin/kept {"path":"C:\\\\Cinema","secret":"attribute"} /-->' . "\n\n"
		. '<!-- wp:lunara/latest-reviews {"count":8} /-->' . "\n\n"
		. '<!-- wp:core/paragraph -->After<!-- /wp:core/paragraph -->';
}
function lunara_pilot_option_snapshot( $key ) { global $lunara_pilot_options; return array( 'present' => array_key_exists( $key, $lunara_pilot_options ), 'value' => array_key_exists( $key, $lunara_pilot_options ) ? $lunara_pilot_options[ $key ] : null ); }
function lunara_pilot_error_fields( $error ) { $data = is_wp_error( $error ) ? $error->get_error_data() : array(); return is_array( $data ) && isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array(); }

$theme_root = dirname( __DIR__ );
require $theme_root . '/inc/helpers.php';
require $theme_root . '/inc/home-blocks.php';
require $theme_root . '/inc/design-tokens.php';
require $theme_root . '/inc/site-studio-registry.php';
require $theme_root . '/inc/site-studio-adapters.php';
require $theme_root . '/inc/site-studio-rest.php';

$required = array(
	'lunara_site_studio_global_design_adapter', 'lunara_site_studio_global_design_state_schema',
	'lunara_site_studio_homepage_structure_adapter', 'lunara_site_studio_homepage_structure_state_schema',
	'lunara_site_studio_lunara_method_adapter', 'lunara_site_studio_lunara_method_state_schema',
	'lunara_compose_home_section_blocks',
);
foreach ( $required as $function ) { lunara_pilot_assert( function_exists( $function ), "Required pilot interface {$function} must exist." ); }

// Registry wiring and adapter resolution.
$surfaces = lunara_site_studio_surfaces();
foreach ( array( 'lunara-method', 'homepage-structure', 'reviews-archive', 'journal-archive', 'oscars-portal', 'oscars-ledger' ) as $stable_id ) { lunara_pilot_assert( isset( $surfaces[ $stable_id ] ), "Stable destination {$stable_id} must remain registered." ); }
$pilot_registry = array(
	'global-design' => 'lunara_global_design_preview',
	'homepage-structure' => 'lunara_homepage_preview',
	'lunara-method' => 'lunara_method_preview',
);
foreach ( $pilot_registry as $id => $query_arg ) {
	lunara_pilot_assert( isset( $surfaces[ $id ] ) && ! empty( $surfaces[ $id ]['available'] ), "Pilot {$id} must be available." );
	lunara_pilot_assert( 'edit_theme_options' === $surfaces[ $id ]['capability'] && true === $surfaces[ $id ]['supports_preview'] && '/' === $surfaces[ $id ]['preview_route'] && $query_arg === $surfaces[ $id ]['preview_query_arg'], "Pilot {$id} must expose the canonical capability and preview contract." );
	lunara_pilot_assert( lunara_site_studio_get_adapter( $id ) instanceof Lunara_Site_Studio_Surface_Adapter, "Pilot {$id} must resolve through the shared adapter interface." );
}
$global_surface = $surfaces['global-design'];
lunara_pilot_assert(
	'Global' === $global_surface['group']
	&& 'Global Design' === $global_surface['label']
	&& 'Control the sitewide palette and typography roles in plain language.' === $global_surface['description']
	&& array( 'brand', 'palette', 'colors', 'typography', 'fonts' ) === $global_surface['aliases']
	&& 'theme:global-design' === $global_surface['owner']
	&& array( 'colors', 'typography' ) === $global_surface['sections']
	&& 'customize.php?autofocus[section]=lunara_global_design_options' === $global_surface['classic_url']
	&& 'lunara_design_tokens_render_panel' === $global_surface['renderer'],
	'Global Design must expose the exact additive canonical bookmark and ownership metadata.'
);
echo "pilot case registry-wiring: passed.\n";

$validation_error = new WP_Error(
	'site_studio_pilot_invalid',
	'Invalid',
	array(
		'fields' => array(
			'colors' => 'Choose valid colors.', 'color_gold' => 'Choose gold.', 'color_gold_light' => 'Choose light gold.', 'color_bg_primary' => 'Choose the ground.', 'color_bg_secondary' => 'Choose the surface.', 'color_text' => 'Choose text.', 'color_text_muted' => 'Choose muted text.',
			'fonts' => 'Choose valid fonts.', 'font_body' => 'Choose body.', 'font_display' => 'Choose display.', 'font_signature' => 'Choose signature.', 'font_glamour' => 'Choose glamour.', 'font_label' => 'Choose label.',
			'kicker' => 'Enter a kicker.', 'title' => 'Enter a title.', 'copy' => 'Enter copy.', 'review_id' => 'Choose a Review.', 'backdrop_id' => 'Choose an image.',
			'preset' => 'Choose a preset.', 'desktop_order' => 'Complete desktop order.', 'mobile_order' => 'Complete mobile order.', 'visibility' => 'Complete visibility.', 'front_page' => 'Reload the front page.',
			'raw_option_key' => 'secret', 'theme_mod' => 'secret', 'revision_config' => 'secret', 'post_content' => 'private content',
		),
	)
);
$safe_fields = lunara_site_studio_safe_validation_fields( $validation_error );
$expected_safe_fields = array( 'title', 'kicker', 'colors', 'color_gold', 'color_gold_light', 'color_bg_primary', 'color_bg_secondary', 'color_text', 'color_text_muted', 'fonts', 'font_body', 'font_display', 'font_signature', 'font_glamour', 'font_label', 'copy', 'review_id', 'backdrop_id', 'preset', 'desktop_order', 'mobile_order', 'visibility', 'front_page' );
lunara_pilot_assert( $expected_safe_fields === array_keys( $safe_fields ), 'REST validation projection must include exactly the explicit pilot human field paths in stable allowlist order.' );
echo "pilot case validation-redaction: passed.\n";

// Global Design: inheritance, explicit presence, validation, rollback, restore.
lunara_pilot_reset();
$lunara_pilot_theme_mods['lunara_accent_color'] = '#123456';
$global = lunara_site_studio_global_design_adapter();
$state = $global->read_state();
lunara_pilot_assert( array( 'colors', 'fonts' ) === array_keys( $state ) && array( 'override', 'effective', 'source' ) === array_keys( $state['colors']['gold'] ), 'Global Design public state must expose only the locked human groups and per-role fields.' );
lunara_pilot_assert( null === $state['colors']['gold']['override'] && '#123456' === $state['colors']['gold']['effective'] && 'customizer' === $state['colors']['gold']['source'], 'Global Design must expose inherited color provenance without inventing an override.' );
$lunara_pilot_options['lunara_design_tokens'] = array( 'colors' => array( 'gold' => '#abcdef' ), 'fonts' => array( 'body' => 'georgia' ) );
$state = $global->read_state();
lunara_pilot_assert( '#abcdef' === $state['colors']['gold']['override'] && 'design-tokens' === $state['colors']['gold']['source'] && 'design-tokens' === $state['fonts']['body']['source'], 'Global Design must expose explicit option presence and canonical effective values.' );
$bad = $state;
$bad['colors']['gold']['override'] = 'red';
$invalid_global_color = $global->validate_state( $bad );
lunara_pilot_assert( is_wp_error( $invalid_global_color ) && 'site_studio_global_invalid' === $invalid_global_color->get_error_code() && array( 'color_gold' ) === array_keys( lunara_pilot_error_fields( $invalid_global_color ) ), 'Global Design must return the exact safe field path for an invalid color.' );
$bad = $state;
$bad['fonts']['body']['override'] = 'unknown-face';
$invalid_global_font = $global->validate_state( $bad );
lunara_pilot_assert( is_wp_error( $invalid_global_font ) && 'site_studio_global_invalid' === $invalid_global_font->get_error_code() && array( 'font_body' ) === array_keys( lunara_pilot_error_fields( $invalid_global_font ) ), 'Global Design must return the exact safe field path for an invalid font choice.' );
$lunara_pilot_option_autoload['lunara_design_tokens'] = true;
$legacy_autoload_save = $global->save_state( $state );
lunara_pilot_assert( ! is_wp_error( $legacy_autoload_save ) && false === $lunara_pilot_option_autoload['lunara_design_tokens'], 'A same-value Global save must correct legacy autoload storage to non-autoloading even though Core returns false for an unchanged value.' );
$clear = $state;
$clear['colors']['gold']['override'] = null;
$clear['fonts']['body']['override'] = null;
$saved = $global->save_state( $clear );
lunara_pilot_assert( ! is_wp_error( $saved ) && ! array_key_exists( 'lunara_design_tokens', $lunara_pilot_options ), 'Clearing the final Global overrides must delete the canonical option.' );
$design_token_writes = array_values( array_filter( $lunara_pilot_option_writes, static function ( $write ) { return 'lunara_design_tokens' === $write['key']; } ) );
lunara_pilot_assert( $design_token_writes && false === $design_token_writes[ count( $design_token_writes ) - 1 ]['autoload'], 'Global writes to lunara_design_tokens itself must request non-autoloading storage.' );
lunara_pilot_assert( array( 'colors', 'typography' ) === $saved['changed_sections'], 'Global save must report the exact canonical changed sections.' );
$restored = $global->restore_revision( $saved['revision_id'] );
lunara_pilot_assert( ! is_wp_error( $restored ) && '#abcdef' === $lunara_pilot_options['lunara_design_tokens']['colors']['gold'] && ! empty( $restored['safety_revision_id'] ), 'Global restore must create safety history and restore exact prior option presence.' );
$before = lunara_pilot_option_snapshot( 'lunara_design_tokens' );
$candidate = $global->read_state();
$candidate['colors']['gold']['override'] = '#654321';
$lunara_pilot_option_fault = array( 'key' => 'lunara_design_tokens', 'mode' => 'fail', 'remaining' => 1 );
lunara_pilot_assert( is_wp_error( $global->save_state( $candidate ) ) && $before === lunara_pilot_option_snapshot( 'lunara_design_tokens' ), 'Global option failure must leave exact prior presence/value.' );
$lunara_pilot_option_fault = array( 'key' => lunara_site_studio_revision_option_name( 'global-design' ), 'mode' => 'fail', 'remaining' => 1 );
lunara_pilot_assert( is_wp_error( $global->save_state( $candidate ) ) && $before === lunara_pilot_option_snapshot( 'lunara_design_tokens' ), 'Global revision failure must roll canonical storage back exactly.' );
$lunara_pilot_option_fault = array( 'key' => 'lunara_design_tokens', 'modes' => array( 'mismatch', 'fail' ), 'remaining' => 2 );
$rollback_failed = $global->save_state( $candidate );
lunara_pilot_assert( is_wp_error( $rollback_failed ) && 'site_studio_global_rollback_failed' === $rollback_failed->get_error_code(), 'Global write/readback failure plus rollback failure must return the distinct rollback-failed code.' );
echo "pilot case global-design: passed.\n";

// Lunara Method: fallback/removal, content ownership validation, rollback.
lunara_pilot_reset();
$method = lunara_site_studio_lunara_method_adapter();
$method_state = $method->read_state();
$defaults = lunara_home_pairing_desk_copy_defaults();
lunara_pilot_assert( array( 'kicker', 'title', 'copy', 'review_id', 'backdrop_id' ) === array_keys( $method_state ), 'Method public state must expose exactly its five canonical human fields.' );
lunara_pilot_assert( $defaults['kicker'] === $method_state['kicker'] && 0 === $method_state['review_id'], 'Method absent text/IDs must read through canonical fallback state.' );
$valid_method = array( 'kicker' => 'A kicker', 'title' => 'A title', 'copy' => 'A copy', 'review_id' => 201, 'backdrop_id' => 301 );
lunara_pilot_assert( ! is_wp_error( $method->validate_state( $valid_method ) ), 'Method must accept a published Review and valid image.' );
$bad_method = $valid_method; $bad_method['review_id'] = 202;
$invalid_method = $method->validate_state( $bad_method );
lunara_pilot_assert( is_wp_error( $invalid_method ) && 'site_studio_method_invalid' === $invalid_method->get_error_code() && array( 'review_id' ) === array_keys( lunara_pilot_error_fields( $invalid_method ) ), 'Method must return the exact safe review_id field for an unpublished Review.' );
$bad_method['review_id'] = 203;
lunara_pilot_assert( is_wp_error( $method->validate_state( $bad_method ) ), 'Method must reject a published non-Review.' );
$bad_method = $valid_method; $bad_method['backdrop_id'] = 302;
lunara_pilot_assert( is_wp_error( $method->validate_state( $bad_method ) ), 'Method must reject a non-image attachment.' );
$method_save = $method->save_state( $valid_method );
lunara_pilot_assert( ! is_wp_error( $method_save ) && 201 === get_theme_mod( 'lunara_home_pairing_desk_review_id' ), 'Method save must write the five canonical mods.' );
lunara_pilot_assert( array( 'language', 'featured-review', 'backdrop' ) === $method_save['changed_sections'], 'Method save must report the exact canonical changed sections.' );
$blank_method = array( 'kicker' => '', 'title' => '', 'copy' => '', 'review_id' => 0, 'backdrop_id' => 0 );
$blank_save = $method->save_state( $blank_method );
lunara_pilot_assert( ! is_wp_error( $blank_save ) && $defaults['title'] === $blank_save['state']['title'] && array() === $lunara_pilot_theme_mods, 'Blank Method values must remove overrides and restore public fallbacks.' );
$method_restore = $method->restore_revision( $blank_save['revision_id'] );
lunara_pilot_assert( ! is_wp_error( $method_restore ) && $valid_method === $method_restore['state'] && ! empty( $method_restore['safety_revision_id'] ), 'Method restore must apply the selected private raw-mod snapshot and create verified safety history.' );
$lunara_pilot_theme_mods = array( 'lunara_home_pairing_desk_kicker' => '', 'lunara_home_pairing_desk_review_id' => 0 );
$before_mods = $lunara_pilot_theme_mods;
$lunara_pilot_mod_fault = array( 'key' => 'lunara_home_pairing_desk_title', 'mode' => 'fail', 'remaining' => 1 );
lunara_pilot_assert( is_wp_error( $method->save_state( $valid_method ) ) && $before_mods === $lunara_pilot_theme_mods, 'Method mod/readback failure must restore exact raw presence for every managed key.' );
$lunara_pilot_option_fault = array( 'key' => lunara_site_studio_revision_option_name( 'lunara-method' ), 'mode' => 'fail', 'remaining' => 1 );
lunara_pilot_assert( is_wp_error( $method->save_state( $valid_method ) ) && $before_mods === $lunara_pilot_theme_mods, 'Method revision failure must restore exact raw presence for every managed key.' );
echo "pilot case lunara-method: passed.\n";

// Homepage validation and registry-mode non-conversion.
lunara_pilot_reset( '<!-- wp:core/paragraph -->Registry only<!-- /wp:core/paragraph -->' );
$lunara_pilot_theme_mods['lunara_home_section_order_preset'] = 'oscars-forward';
$homepage = lunara_site_studio_homepage_structure_adapter();
$home_state = $homepage->read_state();
$six = array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' );
lunara_pilot_assert( array( 'mode', 'front_page_id', 'preset', 'desktop_order', 'mobile_order', 'visibility' ) === array_keys( $home_state ), 'Homepage public state must expose exactly the locked human/concurrency fields.' );
$desktop_inventory = $home_state['desktop_order']; sort( $desktop_inventory ); $six_inventory = $six; sort( $six_inventory );
lunara_pilot_assert( $six_inventory === $desktop_inventory && $six === array_keys( $home_state['visibility'] ), 'Homepage public state must contain the exact six canonical managed slugs without historical preset lanes.' );
lunara_pilot_assert( 'oscars-forward' === $home_state['preset'] && array( 'hero', 'oscar-facts', 'oscar-picks', 'latest-reviews', 'pairing-desk', 'dispatch' ) === $home_state['desktop_order'] && array( 'hero', 'oscar-facts', 'oscar-picks', 'dispatch', 'latest-reviews', 'pairing-desk' ) === $home_state['mobile_order'], 'Homepage must project the live 11-slug preset arrays down to exactly the six managed lanes in provider order.' );
$bad_home = $home_state; $bad_home['desktop_order'][5] = 'hero';
$invalid_home = $homepage->validate_state( $bad_home );
lunara_pilot_assert( is_wp_error( $invalid_home ) && 'site_studio_homepage_invalid' === $invalid_home->get_error_code() && array( 'desktop_order' ) === array_keys( lunara_pilot_error_fields( $invalid_home ) ), 'Homepage must return the exact desktop_order field for duplicate/missing inventory.' );
$bad_home = $home_state; array_pop( $bad_home['mobile_order'] );
lunara_pilot_assert( is_wp_error( $homepage->validate_state( $bad_home ) ), 'Homepage must reject incomplete mobile inventory.' );
$bad_home = $home_state; $bad_home['front_page_id'] = 999;
lunara_pilot_assert( is_wp_error( $homepage->validate_state( $bad_home ) ), 'Homepage front-page ID must act as a concurrency assertion, not a writable setting.' );
$bad_home = $home_state; $bad_home['mode'] = 'blocks';
lunara_pilot_assert( is_wp_error( $homepage->validate_state( $bad_home ) ), 'Homepage mode must act as a concurrency assertion, not a writable setting.' );
$registry_save = $homepage->save_state( $home_state );
lunara_pilot_assert( ! is_wp_error( $registry_save ) && array() === $lunara_pilot_post_writes && 'registry' === $registry_save['state']['mode'], 'Registry-mode Homepage save must never write or convert post_content.' );
lunara_pilot_assert( array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' ) === $registry_save['changed_sections'], 'Homepage save must report the exact six managed sections.' );
echo "pilot case homepage-registry-mode: passed.\n";

// Homepage block-mode composition and exact rollback at every boundary.
lunara_pilot_reset( lunara_pilot_block_content() );
$homepage = lunara_site_studio_homepage_structure_adapter();
$candidate = $homepage->read_state();
$candidate['desktop_order'] = array( 'latest-reviews', 'hero', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' );
$candidate['mobile_order'] = array( 'hero', 'dispatch', 'latest-reviews', 'pairing-desk', 'oscar-picks', 'oscar-facts' );
$candidate['visibility']['pairing-desk'] = false;
$block_save = $homepage->save_state( $candidate );
$written_content = get_post_field( 'post_content', 101 );
lunara_pilot_assert( ! is_wp_error( $block_save ) && false !== strpos( $written_content, '<!-- wp:plugin/kept {"path":"C:\\\\Cinema","secret":"attribute"} /-->' ) && false !== strpos( $written_content, '{"overrideTitle":"Stored hero"}' ), 'Block-mode save must preserve unknown blocks, literal slashes, and first canonical attributes.' );
lunara_pilot_assert( strpos( $written_content, 'lunara/latest-reviews' ) < strpos( $written_content, 'plugin/kept' ) && strpos( $written_content, 'plugin/kept' ) < strpos( $written_content, 'lunara/cinematic-hero' ), 'Block-mode save must preserve unknown stream position while reordering owned lanes.' );

$managed_mods = array(
	'lunara_home_section_order_preset', 'lunara_home_section_order', 'lunara_home_section_mobile_order',
	'lunara_home_show_hero', 'lunara_home_show_latest_reviews', 'lunara_home_show_pairing_desk', 'lunara_home_show_dispatch', 'lunara_home_show_oscar_picks', 'lunara_home_show_oscar_facts',
);
foreach ( $managed_mods as $fault_key ) {
	lunara_pilot_reset( lunara_pilot_block_content() );
	$lunara_pilot_theme_mods = array( 'lunara_home_section_order_preset' => 'journal-first', 'lunara_home_show_pairing_desk' => '0' );
	$before_mods = $lunara_pilot_theme_mods;
	$before_content = get_post_field( 'post_content', 101 );
	$homepage = lunara_site_studio_homepage_structure_adapter();
	$candidate = $homepage->read_state();
	$candidate['preset'] = 'editorial-default';
	$candidate['visibility']['pairing-desk'] = true;
	$lunara_pilot_mod_fault = array( 'key' => $fault_key, 'mode' => 'fail', 'remaining' => 1 );
	$result = $homepage->save_state( $candidate );
	lunara_pilot_assert( is_wp_error( $result ) && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ), "Homepage failure at {$fault_key} must restore exact mods and byte-exact content." );
}
foreach ( array( 'fail', 'mismatch' ) as $post_fault ) {
	lunara_pilot_reset( lunara_pilot_block_content() );
	$before_mods = $lunara_pilot_theme_mods;
	$before_content = get_post_field( 'post_content', 101 );
	$homepage = lunara_site_studio_homepage_structure_adapter();
	$candidate = $homepage->read_state();
	$candidate['desktop_order'] = array_reverse( $candidate['desktop_order'] );
	$lunara_pilot_post_fault = $post_fault;
	$result = $homepage->save_state( $candidate );
	lunara_pilot_assert( is_wp_error( $result ) && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ), "Homepage {$post_fault} page failure must roll back exactly." );
}
lunara_pilot_reset( lunara_pilot_block_content() );
$before_mods = $lunara_pilot_theme_mods;
$before_content = get_post_field( 'post_content', 101 );
$homepage = lunara_site_studio_homepage_structure_adapter();
$candidate = $homepage->read_state();
$candidate['visibility']['oscar-facts'] = false;
$lunara_pilot_option_fault = array( 'key' => lunara_site_studio_revision_option_name( 'homepage-structure' ), 'mode' => 'fail', 'remaining' => 1 );
$result = $homepage->save_state( $candidate );
lunara_pilot_assert( is_wp_error( $result ) && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ), 'Homepage revision failure must roll back combined mods/content exactly.' );
lunara_pilot_reset( lunara_pilot_block_content() );
$homepage = lunara_site_studio_homepage_structure_adapter();
$candidate = $homepage->read_state();
$candidate['desktop_order'] = array_reverse( $candidate['desktop_order'] );
$lunara_pilot_post_fault = array( 'modes' => array( 'mismatch', 'fail' ), 'remaining' => 2 );
$rollback_failed = $homepage->save_state( $candidate );
lunara_pilot_assert( is_wp_error( $rollback_failed ) && 'site_studio_homepage_rollback_failed' === $rollback_failed->get_error_code(), 'Homepage forward verification plus rollback write failure must return the distinct rollback-failed code.' );
echo "pilot case homepage-atomic-rollback: passed.\n";

// Durable cap, restore safety, changed front-page rejection.
lunara_pilot_reset( lunara_pilot_block_content() );
$homepage = lunara_site_studio_homepage_structure_adapter();
for ( $index = 0; $index < 13; $index++ ) {
	$candidate = $homepage->read_state();
	$candidate['visibility']['oscar-facts'] = 0 === $index % 2;
	$result = $homepage->save_state( $candidate );
	lunara_pilot_assert( ! is_wp_error( $result ), 'Each durable Homepage save must succeed.' );
}
$revisions = $homepage->list_revisions();
lunara_pilot_assert( 12 === count( $revisions ) && $result['revision_id'] === $revisions[0]['id'], 'The thirteenth Homepage revision must be durable and history must remain capped at 12.' );
$private_revisions = lunara_site_studio_list_revisions( 'homepage-structure', true );
$public_revisions = $homepage->list_revisions();
lunara_pilot_assert( array( 'id', 'timestamp', 'action' ) === array_keys( $public_revisions[0] ) && false === strpos( wp_json_encode( $public_revisions ), 'config' ) && false === strpos( wp_json_encode( $public_revisions ), 'post_content' ), 'Adapter revision listing must redact every private snapshot/storage field exactly.' );
$rest_response = lunara_site_studio_rest_get_revisions( new Lunara_Pilot_REST_Request( array( 'surface' => 'homepage-structure' ) ) );
lunara_pilot_assert( $rest_response instanceof WP_REST_Response && 200 === $rest_response->status && $public_revisions === $rest_response->get_data()['revisions'] && false === strpos( wp_json_encode( $rest_response->get_data() ), 'config' ) && false === strpos( wp_json_encode( $rest_response->get_data() ), 'post_content' ), 'REST revision response must preserve the exact redacted adapter list without private content.' );

$revision_option = lunara_site_studio_revision_option_name( 'homepage-structure' );
$history_before_failed_push = $lunara_pilot_options[ $revision_option ];
$oldest_before_failed_push = $history_before_failed_push[11];
$before_mods = $lunara_pilot_theme_mods;
$before_content = get_post_field( 'post_content', 101 );
$candidate = $homepage->read_state();
$candidate['visibility']['oscar-picks'] = ! $candidate['visibility']['oscar-picks'];
$lunara_pilot_option_fault = array( 'key' => $revision_option, 'mode' => 'mismatch', 'remaining' => 1 );
$failed_push = $homepage->save_state( $candidate );
lunara_pilot_assert( is_wp_error( $failed_push ) && 'site_studio_revision_readback_failed' === $failed_push->get_error_code() && $history_before_failed_push === $lunara_pilot_options[ $revision_option ] && $oldest_before_failed_push === $lunara_pilot_options[ $revision_option ][11] && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ), 'Failed capped revision readback must restore the exact full 12-entry history including the oldest entry and all live state.' );

$oldest_id = $revisions[11]['id'];
$oldest_private = null;
foreach ( lunara_site_studio_list_revisions( 'homepage-structure', true ) as $private_revision ) { if ( $oldest_id === $private_revision['id'] ) { $oldest_private = $private_revision['config']; break; } }
$oldest_fact = ! empty( $oldest_private['mods']['lunara_home_show_oscar_facts']['present'] ) ? (string) $oldest_private['mods']['lunara_home_show_oscar_facts']['value'] : null;
$lunara_pilot_theme_mods['lunara_home_show_oscar_facts'] = '1' === $oldest_fact ? '0' : '1';
$before_mods = $lunara_pilot_theme_mods;
$before_content = get_post_field( 'post_content', 101 );
$current_raw_mods = lunara_site_studio_raw_mod_snapshot( $managed_mods );
$restore_fault_key = '';
foreach ( $managed_mods as $managed_key ) { if ( isset( $oldest_private['mods'][ $managed_key ] ) && $oldest_private['mods'][ $managed_key ] !== $current_raw_mods[ $managed_key ] ) { $restore_fault_key = $managed_key; break; } }
lunara_pilot_assert( '' !== $restore_fault_key, 'Failed oldest-target restore fixture must inject a write that materially differs from live state.' );
$lunara_pilot_mod_fault = array( 'key' => $restore_fault_key, 'mode' => 'fail', 'remaining' => 1 );
$failed_restore = $homepage->restore_revision( $oldest_id );
$after_failed_restore = $homepage->list_revisions();
lunara_pilot_assert( is_wp_error( $failed_restore ) && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ) && 12 === count( $after_failed_restore ) && 'restore-safety' === $after_failed_restore[0]['action'], 'Failed oldest-target restore must remain exact while its verified safety snapshot survives cap eviction.' );
$oldest_retained_id = $after_failed_restore[11]['id'];
$private_before_restore = lunara_site_studio_list_revisions( 'homepage-structure', true );
$selected_private = null;
foreach ( $private_before_restore as $private_revision ) { if ( $oldest_retained_id === $private_revision['id'] ) { $selected_private = $private_revision['config']; break; } }
lunara_pilot_assert( is_array( $selected_private ) && isset( $selected_private['mods'], $selected_private['post_content'] ), 'Oldest retained target must be preloaded as the exact private combined snapshot before safety capping.' );
$facts_key = 'lunara_home_show_oscar_facts';
$target_fact = ! empty( $selected_private['mods'][ $facts_key ]['present'] ) ? $selected_private['mods'][ $facts_key ]['value'] : null;
$lunara_pilot_theme_mods[ $facts_key ] = '1' === (string) $target_fact ? '0' : '1';
$lunara_pilot_posts[101]->post_content .= '<!-- live-state-must-change -->';
$restore = $homepage->restore_revision( $oldest_retained_id );
lunara_pilot_assert( ! is_wp_error( $restore ) && ! empty( $restore['safety_revision_id'] ) && $selected_private['mods'] === lunara_site_studio_raw_mod_snapshot( $managed_mods ) && $selected_private['post_content'] === get_post_field( 'post_content', 101 ), 'Homepage must apply the preloaded oldest retained target even when its safety snapshot evicts that target from the capped list.' );
$before_mods = $lunara_pilot_theme_mods; $before_content = get_post_field( 'post_content', 101 ); $before_writes = count( $lunara_pilot_post_writes );
$identity_target = $homepage->list_revisions()[1]['id'];
$before_revision_writes = count( array_filter( $lunara_pilot_option_writes, static function ( $write ) use ( $revision_option ) { return $revision_option === $write['key']; } ) );
$lunara_pilot_options['page_on_front'] = 999;
$rejected = $homepage->restore_revision( $identity_target );
$after_revision_writes = count( array_filter( $lunara_pilot_option_writes, static function ( $write ) use ( $revision_option ) { return $revision_option === $write['key']; } ) );
lunara_pilot_assert( is_wp_error( $rejected ) && 'site_studio_homepage_identity_changed' === $rejected->get_error_code() && array( 'front_page' ) === array_keys( lunara_pilot_error_fields( $rejected ) ) && $before_mods === $lunara_pilot_theme_mods && $before_content === get_post_field( 'post_content', 101 ) && $before_writes === count( $lunara_pilot_post_writes ) && $before_revision_writes === $after_revision_writes, 'Homepage restore must reject changed page_on_front with the exact field error before any live or revision mutation.' );
echo "pilot case homepage-revisions: passed.\n";

// Preview and public projection stay human-only.
foreach ( array( 'global-design' => $global, 'homepage-structure' => $homepage, 'lunara-method' => $method ) as $surface_id => $adapter ) {
	if ( 'global-design' === $surface_id ) { lunara_pilot_reset(); $candidate = $adapter->read_state(); }
	if ( 'homepage-structure' === $surface_id ) { lunara_pilot_reset( lunara_pilot_block_content() ); $candidate = $adapter->read_state(); }
	if ( 'lunara-method' === $surface_id ) { lunara_pilot_reset(); $candidate = $adapter->read_state(); }
	$preview = $adapter->create_preview( $candidate );
	lunara_pilot_assert( ! is_wp_error( $preview ) && ! empty( $preview['token'] ) && 1900001800 === $preview['expires_at'], "{$surface_id} must create a 30-minute private preview token." );
	$projected = lunara_site_studio_project_state( $surface_id, $candidate );
	$text = wp_json_encode( $projected );
	lunara_pilot_assert( ! is_wp_error( $projected ) && ! preg_match( '/lunara_|option|revision|config|token|callback/i', $text ), "{$surface_id} public projection must omit technical storage and revision internals." );
}
echo "pilot case preview-projection: passed.\n";

echo "site-studio pilot runtime: all assertions passed.\n";
