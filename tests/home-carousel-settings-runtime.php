<?php
/** Runtime contracts: canonical ownership, rollback, retained selection and private preview isolation. */
define( 'ABSPATH', __DIR__ . '/' );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
if ( ! function_exists( 'mb_substr' ) ) { function mb_substr( $value, $start, $length ) { return substr( $value, $start, $length ); } }
$options = array(); $filters = array(); $transients = array(); $user_id = 7; $fail_option = ''; $clock = 2000000000;
class WP_Error { public $code; public $data; public $message; public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->data = $data; $this->message = $message; } public function get_error_code() { return $this->code; } public function get_error_data() { return $this->data; } public function get_error_message() { return $this->message; } }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function add_filter( $name, $callback, $priority = 10, $args = 1 ) { $GLOBALS['filters'][ $name ][] = $callback; }
function has_action( $name, $callback = null ) { return false; }
function add_action( $name, $callback, $priority = 10, $args = 1 ) {}
function apply_filters( $name, $value ) { foreach ( $GLOBALS['filters'][ $name ] ?? array() as $callback ) { $value = $callback( $value ); } return $value; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = false ) { if ( $GLOBALS['fail_option'] === $key ) { return false; } $GLOBALS['options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function current_user_can( $capability ) { return $GLOBALS['user_id'] > 0; }
function get_current_user_id() { return $GLOBALS['user_id']; }
function is_user_logged_in() { return $GLOBALS['user_id'] > 0; }
function wp_verify_nonce( $nonce, $action ) { return 'test-nonce' === $nonce && in_array( $action, array( 'wp_rest', 'lunara_hero_feature_nonce' ), true ); }
class WP_REST_Response { public $data; public $status; public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; } }
class Carousel_Request { public $params; public $nonce = 'test-nonce'; public function __construct( $params ) { $this->params = $params; } public function get_param( $key ) { return $this->params[ $key ] ?? null; } public function get_header( $name ) { return $this->nonce; } }
function current_time( $format ) { return 'timestamp' === $format ? $GLOBALS['clock'] : '2033-05-18 00:00:00'; }
function wp_generate_uuid4() { static $n = 0; return '00000000-0000-4000-8000-' . str_pad( ++$n, 12, '0', STR_PAD_LEFT ); }
function wp_hash( $value ) { return hash( 'sha256', $value ); }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); }
function wp_parse_url( $value, $component = -1 ) { return parse_url( $value, $component ); }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
require dirname( __DIR__ ) . '/inc/site-studio-registry.php';
require dirname( __DIR__ ) . '/inc/site-studio-adapters.php';
require dirname( __DIR__ ) . '/inc/home-carousel-settings.php';
require dirname( __DIR__ ) . '/inc/site-studio-carousels.php';
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
require dirname( __DIR__ ) . '/inc/site-studio-rest.php';
if ( in_array( '--fixture', $argv ?? array(), true ) ) {
 function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
 function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
 function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
 function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
 function get_post( $id ) { return null; }
 require dirname( __DIR__ ) . '/inc/site-studio.php';
 $state = lunara_home_carousel_sanitize( array( 'mode' => 'manual', 'slides' => array( array( 'post_id' => 99 ) ) ) );
 $surface = in_array( '--journal', $argv, true ) ? 'journal-carousel' : 'hero-carousel';
 $base = 'https://example.test/wp-json/lunara-site-studio/v1/surfaces/' . $surface;
 $config = array( 'surface' => $surface, 'nonce' => 'test-nonce', 'protocol' => 'lunara-site-studio/v1', 'pageUuid' => '123e4567-e89b-42d3-a456-426614174000', 'previewInstanceArg' => 'lunara_site_studio_instance', 'previewOrigin' => 'https://example.test', 'previewRoute' => '/', 'previewQueryArg' => 'lunara_' . ( 'hero-carousel' === $surface ? 'hero' : 'journal' ) . '_carousel_preview', 'widths' => array( 'desktop' => 1440, 'mobile' => 390 ), 'markers' => array( 'hero-carousel' === $surface ? 'hero' : 'dispatch' ) );
 foreach ( array( 'state', 'preview', 'save', 'restore', 'revisions' ) as $endpoint ) { $config['endpoints'][ $endpoint ] = $base . '/' . $endpoint; }
 echo '<!doctype html><html><head><meta charset="utf-8"></head><body><div data-lunara-site-studio data-surface="' . $surface . '"><a href="/other" data-lunara-surface-card>Other surface</a><section class="lunara-site-studio-preview"><button data-preview-width="desktop">Desktop</button><button data-preview-width="mobile">Mobile</button><div class="lunara-site-studio-preview-viewport"><iframe width="1440" height="900"></iframe></div></section><aside class="lunara-site-studio-inspector">';
 lunara_site_studio_render_carousel_inspector( $surface, $state, array() );
 echo '<button data-action="preview" disabled>Preview</button><button data-action="save" disabled>Apply</button><button data-action="discard" disabled>Discard</button><p data-workspace-status></p></aside><script id="lunara-site-studio-state" type="application/json">' . json_encode( $state ) . '</script></div><script>window.LunaraSiteStudioWorkspaceConfig=' . json_encode( $config ) . ';</script><script src="/controller.js"></script></body></html>';
 exit;
}
$checks = 0;
function check( $pass, $message ) { $GLOBALS['checks']++; if ( ! $pass ) { throw new RuntimeException( $message ); } }
$legacy = array( 'enabled' => 1, 'overlay' => 73, 'slides' => array( array( 'post_id' => 99, 'cta' => 'Legacy CTA', 'focal_x' => 19 ) ), 'legacy_extra' => 'keep' );
$options['lunara_hero_command'] = $legacy;
$hero = lunara_home_carousel_settings(); check( ! $hero['adopted'] && 'manual' === $hero['mode'] && 7 === $hero['interval'], 'Legacy read seeds editable manual deck without adopting.' ); check( $options['lunara_hero_command'] === $legacy, 'Read leaves canonical legacy bytes untouched.' );
$hero['slides'][] = array( 'post_id' => 987654, 'headline' => '<b>Missing source</b>', 'focal_x' => 120, 'zoom' => 150 );
$hero['mode'] = 'auto'; $hero = lunara_site_studio_carousel_validate( $hero, 'hero' );
check( count( $hero['slides'] ) === 2 && 987654 === $hero['slides'][1]['post_id'], 'Automatic retains manual unavailable source IDs.' ); check( 100 === $hero['slides'][1]['focal_x'] && 112 === $hero['slides'][1]['zoom'] && 'Missing source' === $hero['slides'][1]['headline'], 'Overrides are bounded and stripped of markup.' );
$adapter = lunara_site_studio_hero_carousel_adapter(); $preview = $adapter->create_preview( $hero ); check( ! is_wp_error( $preview ), 'Authenticated adapter creates private preview.' ); check( $legacy === $options['lunara_hero_command'], 'Preview does not mutate canonical option.' );
$loaded = lunara_site_studio_get_private_preview( 'hero-carousel', 'theme:hero-carousel', '/', $preview['token'] ); check( $loaded === $hero, 'Owner preview loads exact candidate.' );
$user_id = 8; check( false === lunara_site_studio_get_private_preview( 'hero-carousel', 'theme:hero-carousel', '/', $preview['token'] ), 'Other user cannot read preview.' ); $user_id = 7;
check( false === lunara_site_studio_get_private_preview( 'journal-carousel', 'theme:journal-carousel', '/', $preview['token'] ), 'Other surface cannot consume preview.' );
check( lunara_site_studio_preview_install_state( 'hero-carousel', $hero, 42 ), 'Preview state installs through existing validation path.' ); check( lunara_home_carousel_settings()['adopted'] && $options['lunara_hero_command'] === $legacy, 'Private renderer sees adopted candidate without persistence.' ); unset( $GLOBALS['lunara_home_carousel_preview'] );
$saved = $adapter->save_state( $hero ); check( ! is_wp_error( $saved ) && $saved['state']['adopted'], 'Explicit Apply adopts.' ); $canonical = $options['lunara_hero_command']; unset( $canonical['carousel'] ); check( $canonical === $legacy, 'Hero extends canonical option without destroying legacy keys.' );
$journal = lunara_home_carousel_defaults( 'journal' ); $journal['mode'] = 'manual'; $journal_result = lunara_site_studio_journal_carousel_adapter()->save_state( $journal ); check( $journal_result['state']['adopted'] && array() === $journal_result['state']['slides'], 'Empty manual persists as empty; no automatic fallback.' ); check( $options['lunara_hero_command']['carousel'] === $saved['state'], 'Journal Apply does not touch Hero.' );
$restored = $adapter->restore_revision( $saved['revision_id'] ); check( ! is_wp_error( $restored ) && $options['lunara_hero_command'] === $legacy, 'Revision restore reinstates exact pre-adoption presentation.' );
$fail_option = 'lunara_hero_command'; $failed = $adapter->save_state( $hero ); check( is_wp_error( $failed ) && $options['lunara_hero_command'] === $legacy, 'Failed write reports failure and retains previous canonical value.' ); $fail_option = '';
check( is_wp_error( $adapter->validate_state( array( 'mode' => 'broken', 'slides' => array() ) ) ), 'Invalid mode is rejected.' );
$request = new Carousel_Request( array( 'surface' => 'hero-carousel', 'state' => $hero ) );
$user_id = 0; $denied = lunara_site_studio_rest_save( $request ); check( $denied->status === 401 && $options['lunara_hero_command'] === $legacy, 'Unauthenticated REST Apply is denied without mutation.' );
$user_id = 7; $request->nonce = 'invalid'; $denied = lunara_site_studio_rest_save( $request ); check( $denied->status === 403 && $options['lunara_hero_command'] === $legacy, 'Invalid nonce Apply is denied without mutation.' );
$request->nonce = 'test-nonce'; $valid_response = lunara_site_studio_rest_save( $request ); check( $valid_response->status === 200 && $valid_response->data['state']['adopted'], 'Actual REST Apply returns valid projected state envelope.' );
$clock += 1801; check( false === lunara_site_studio_get_private_preview( 'hero-carousel', 'theme:hero-carousel', '/', $preview['token'] ), 'Expired preview cannot load.' );
// Execute the real metabox/save block without booting unrelated WordPress theme code.
$functions_source = str_replace( "\r\n", "\n", file_get_contents( dirname( __DIR__ ) . '/functions.php' ) );
$metabox_start = strpos( $functions_source, "if ( ! function_exists( 'lunara_hero_featured_post_types' ) )" );
$metabox_end = strpos( $functions_source, "/**\n * Pairing Desk", $metabox_start );
check( false !== $metabox_start && false !== $metabox_end, 'The actual legacy Hero metabox block is discoverable.' );
eval( substr( $functions_source, $metabox_start, $metabox_end - $metabox_start ) );
if ( ! function_exists( 'esc_html__' ) ) { function esc_html__( $value, $domain = '' ) { return htmlspecialchars( $value, ENT_QUOTES ); } }
function esc_html_e( $value, $domain = '' ) { echo htmlspecialchars( $value, ENT_QUOTES ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function wp_nonce_field( $action, $name ) { echo '<input type="hidden" name="' . $name . '">'; }
function checked( $value ) { if ( $value ) { echo 'checked'; } }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['feature_meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['feature_meta'][ $id ][ $key ] = $value; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['feature_meta'][ $id ][ $key ] ); }
function get_post_type( $id ) { return 'review'; }
$feature_meta = array( 55 => array( '_lunara_hero_featured' => 123456 ) );
$options['lunara_hero_command'] = $legacy;
ob_start(); lunara_hero_feature_meta_callback( (object) array( 'ID' => 55 ) ); $before_markup = ob_get_clean();
check( false !== strpos( $before_markup, 'name="lunara_hero_featured"' ), 'Pre-adoption metabox retains its legacy checkbox.' );
$options['lunara_hero_command']['carousel'] = array_merge( $hero, array( 'adopted' => true ) );
ob_start(); lunara_hero_feature_meta_callback( (object) array( 'ID' => 55 ) ); $after_markup = ob_get_clean();
check( false === strpos( $after_markup, 'name="lunara_hero_featured"' ) && false !== strpos( $after_markup, 'surface=hero-carousel' ), 'Adopted Hero replaces the competing feature checkbox with a Site Studio link.' );
$_POST = array( 'lunara_hero_feature_nonce' => 'test-nonce' ); lunara_save_hero_feature_meta( 55 );
check( 123456 === get_post_meta( 55, '_lunara_hero_featured' ), 'Stale unchecked submission preserves the saved legacy timestamp after adoption.' );
$_POST['lunara_hero_featured'] = '1'; lunara_save_hero_feature_meta( 56 );
check( '' === get_post_meta( 56, '_lunara_hero_featured' ), 'Stale checked submission cannot create a legacy feature after adoption.' );
$options['lunara_hero_command'] = $legacy; unset( $_POST['lunara_hero_featured'] ); lunara_save_hero_feature_meta( 55 );
check( '' === get_post_meta( 55, '_lunara_hero_featured' ), 'Pre-adoption legacy uncheck behavior is preserved.' );
$_POST['lunara_hero_featured'] = '1'; lunara_save_hero_feature_meta( 56 );
check( get_post_meta( 56, '_lunara_hero_featured' ) > 0, 'Pre-adoption legacy feature behavior is preserved.' );
echo "PASS {$checks} carousel settings and preview contracts\n";
