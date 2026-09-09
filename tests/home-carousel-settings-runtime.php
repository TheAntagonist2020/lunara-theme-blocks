<?php
/** Runtime contracts: canonical ownership, rollback, retained selection and private preview isolation. */
define( 'ABSPATH', __DIR__ . '/' );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
if ( ! function_exists( 'mb_substr' ) ) { function mb_substr( $value, $start, $length ) { return substr( $value, $start, $length ); } }
$options = array(); $filters = array(); $transients = array(); $user_id = 7; $fail_option = ''; $clock = 2000000000; $metadata_posts = array(); $attachment_urls = array(); $denied_read_post = 0;
class WP_Error { public $code; public $data; public $message; public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->data = $data; $this->message = $message; } public function get_error_code() { return $this->code; } public function get_error_data() { return $this->data; } public function get_error_message() { return $this->message; } }
class WP_Post { public $ID, $post_type = 'review', $post_status = 'publish', $post_password = '', $post_date = '2026-09-08', $post_title = ''; public function __construct( $id = 0, $type = 'review', $status = 'publish', $date = '2026-09-08' ) { $this->ID = $id; $this->post_type = $type; $this->post_status = $status; $this->post_date = $date; $this->post_title = 'Story ' . $id; } }
class WP_Query { public $posts = array(); public function __construct( $args = array() ) { $this->posts = array_values( array_filter( $GLOBALS['metadata_posts'], static function ( $post ) use ( $args ) { return in_array( $post->post_type, (array) $args['post_type'], true ) && $post->post_status === $args['post_status'] && ( ! isset( $args['has_password'] ) || false !== $args['has_password'] || '' === $post->post_password ); } ) ); usort( $this->posts, static function ( $left, $right ) { $date = strcmp( $right->post_date, $left->post_date ); return 0 !== $date ? $date : $right->ID <=> $left->ID; } ); $this->posts = array_slice( $this->posts, 0, $args['posts_per_page'] ); } }
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
function current_user_can( $capability, ...$args ) { if ( 'read_post' === $capability && isset( $args[0] ) && (int) $args[0] === (int) $GLOBALS['denied_read_post'] ) { return false; } return $GLOBALS['user_id'] > 0; }
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
function get_post( $id ) { return $GLOBALS['metadata_posts'][ $id ] ?? null; }
function get_the_title( $post ) { $post = $post instanceof WP_Post ? $post : get_post( $post ); return $post ? $post->post_title : ''; }
function get_permalink( $id ) { return 'https://example.test/story/' . absint( $id ) . '/'; }
function get_the_date( $format, $id ) { return date( $format, strtotime( get_post( $id )->post_date ) ); }
function get_the_excerpt( $id ) { return 'Excerpt for story ' . absint( $id ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_trim_words( $value, $count, $more = '' ) { return $value; }
function get_the_post_thumbnail_url( $id, $size ) { return ''; }
function wp_list_pluck( $items, $field ) { return array_map( static function ( $item ) use ( $field ) { return is_array( $item ) ? ( $item[ $field ] ?? null ) : ( $item->$field ?? null ); }, $items ); }
function wp_attachment_is_image( $id ) { return isset( $GLOBALS['attachment_urls'][ $id ] ); }
function wp_get_attachment_image_url( $id, $size ) { return $GLOBALS['attachment_urls'][ $id ] ?? ''; }
require dirname( __DIR__ ) . '/inc/site-studio-registry.php';
require dirname( __DIR__ ) . '/inc/site-studio-adapters.php';
require dirname( __DIR__ ) . '/inc/home-carousel-settings.php';
require dirname( __DIR__ ) . '/inc/home-carousels.php';
require dirname( __DIR__ ) . '/inc/site-studio-carousels.php';
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
require dirname( __DIR__ ) . '/inc/site-studio-rest.php';
if ( in_array( '--fixture', $argv ?? array(), true ) ) {
 function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
 function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
 function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
 function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
 require dirname( __DIR__ ) . '/inc/site-studio.php';
 $state = lunara_home_carousel_sanitize( array( 'mode' => 'manual', 'slides' => array( array( 'post_id' => 99 ) ) ) );
 $surface = in_array( '--journal', $argv, true ) ? 'journal-carousel' : 'hero-carousel';
 $base = 'https://example.test/wp-json/lunara-site-studio/v1/surfaces/' . $surface;
 $config = array(
  'protocol' => 'lunara-site-studio/v1', 'clientVersion' => 1, 'surface' => $surface,
  'nonce' => 'test-nonce', 'pageUuid' => '123e4567-e89b-42d3-a456-426614174000',
  'previewInstanceArg' => 'lunara_site_studio_instance', 'previewOrigin' => 'https://example.test', 'previewRoute' => '/',
  'previewQueryArg' => 'lunara_' . ( 'hero-carousel' === $surface ? 'hero' : 'journal' ) . '_carousel_preview', 'previewParams' => (object) array(),
  'stateSchema' => lunara_site_studio_carousel_schema(), 'widths' => array( 'desktop' => 1440, 'tablet' => 768, 'mobile' => 390 ),
  'markers' => array( 'hero-carousel' === $surface ? 'hero' : 'dispatch' ),
  'strings' => array(
   'live' => 'Live settings loaded.', 'dirty' => 'Unsaved changes.', 'previewCurrent' => 'Preview is current.', 'previewStale' => 'Preview is out of date.',
   'saving' => 'Applying changes…', 'saved' => 'Changes applied.', 'restored' => 'Revision restored.', 'failed' => 'The request could not be completed. Your changes are still here.',
   'discardConfirm' => 'Discard your unsaved changes?', 'navigateConfirm' => 'Discard unsaved changes and open another surface?', 'hideConfirm' => 'Hide this Homepage section?',
   'removeConfirm' => 'Hide this item from the public site?', 'clearOverrideConfirm' => 'Clear this override and use the current inherited fallback?',
   'resetOverridesConfirm' => 'Reset all Global overrides?', 'resetConfirm' => 'Reset this candidate?', 'reviewAutomaticConfirm' => 'Switch this Review selection back to Automatic?',
   'clearMediaConfirm' => 'Clear this backdrop?', 'restoreConfirm' => 'Restore this revision to the live site?', 'revisionEmpty' => 'No revisions yet.',
   'revisionRestore' => 'Restore', 'revisionSaved' => 'Saved live', 'revisionSafety' => 'Safety snapshot', 'revisionRestored' => 'Restored revision', 'revisionOther' => 'Revision',
   'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile', 'searchCount' => '%d destinations', 'moved' => 'Section order updated.', 'chooseBackdrop' => 'Choose backdrop',
  ),
 );
 foreach ( array( 'state', 'preview', 'save', 'restore', 'revisions' ) as $endpoint ) { $config['endpoints'][ $endpoint ] = $base . '/' . $endpoint; }
 echo '<!doctype html><html><head><meta charset="utf-8"></head><body><div class="wrap lunara-site-studio" data-lunara-site-studio data-surface="' . $surface . '" data-workspace-state="recovery" data-dirty="false"><a class="lunara-site-studio-card" href="/other" data-lunara-surface-card>Other surface</a><div class="lunara-site-studio-workspace"><aside class="lunara-site-studio-section-rail"><ol><li data-section-control="' . ( 'hero-carousel' === $surface ? 'hero' : 'dispatch' ) . '">Carousel</li></ol></aside><section class="lunara-site-studio-preview"><div class="lunara-site-studio-widths"><button data-preview-width="desktop">Desktop</button><button data-preview-width="tablet">Tablet</button><button data-preview-width="mobile">Mobile</button></div><div class="lunara-site-studio-preview-viewport"><div class="lunara-site-studio-preview-flow"><div class="lunara-site-studio-preview-canvas"><iframe src="https://example.test/" width="1440" height="900"></iframe></div></div></div></section><aside class="lunara-site-studio-inspector" aria-busy="false">';
 lunara_site_studio_render_carousel_inspector( $surface, $state, array() );
 echo '<div class="lunara-site-studio-actions"><button type="button" class="button" data-action="preview" disabled>Preview changes</button><button type="button" class="button button-primary" data-action="save" disabled>Apply changes</button><button type="button" class="button" data-action="discard" disabled>Discard changes</button></div><p data-workspace-status>Loading editor…</p></aside></div><script id="lunara-site-studio-state" type="application/json">' . json_encode( $state ) . '</script></div><script>window.LunaraSiteStudioWorkspaceConfig=' . json_encode( $config ) . ';</script><script src="/controller.js"></script></body></html>';
 exit;
}
$checks = 0;
function check( $pass, $message ) { $GLOBALS['checks']++; if ( ! $pass ) { throw new RuntimeException( $message ); } }
$metadata_posts = array(
 201 => new WP_Post( 201, 'journal', 'publish', '2026-09-01' ), 202 => new WP_Post( 202, 'review', 'publish', '2026-09-02' ),
 203 => new WP_Post( 203, 'journal', 'publish', '2026-09-03' ), 204 => new WP_Post( 204, 'review', 'publish', '2026-09-04' ),
 205 => new WP_Post( 205, 'journal', 'publish', '2026-09-05' ), 206 => new WP_Post( 206, 'review', 'publish', '2026-09-06' ),
 207 => new WP_Post( 207, 'journal', 'publish', '2026-09-07' ), 208 => new WP_Post( 208, 'review', 'publish', '2026-09-08' ),
 209 => new WP_Post( 209, 'review', 'draft', '2026-09-09' ), 210 => new WP_Post( 210, 'page', 'publish', '2026-09-10' ),
 211 => new WP_Post( 211, 'journal', 'publish', '2026-09-11' ),
);
$metadata_posts[211]->post_password = 'private';
$attachment_urls[90] = 'https://example.test/uploads/manual-90.jpg';
$denied_read_post = 209;
$metadata = lunara_site_studio_carousel_metadata( 'hero', array( 209, 999 ), array( 90, 91 ) );
$metadata_items = get_object_vars( $metadata['items'] ); $metadata_images = get_object_vars( $metadata['images'] );
check( array( 208, 207, 206, 205, 204, 203 ) === $metadata['automatic'], 'Automatic metadata must use the same newest-first eligible six-story order as public delivery.' );
check( isset( $metadata_items['208'] ) && array( 'id', 'title', 'type', 'available', 'date', 'date_label', 'image_url', 'image_id', 'image_source', 'excerpt', 'kicker', 'cta' ) === array_keys( $metadata_items['208'] ), 'Metadata items must expose only the exact editor source shape.' );
check( '2026-09-08' === $metadata_items['208']['date'] && 'Sep 8, 2026' === $metadata_items['208']['date_label'] && 'Read the review' === $metadata_items['208']['cta'], 'Metadata must expose stable machine/display dates and inherited copy.' );
check( isset( $metadata_items['209'] ) && false === $metadata_items['209']['available'] && 'Unavailable item #209' === $metadata_items['209']['title'] && isset( $metadata_items['999'] ) && false === $metadata_items['999']['available'], 'Draft and deleted selections must remain visible without leaking titles the editor cannot read.' );
check( array( '90' => 'https://example.test/uploads/manual-90.jpg', '91' => '' ) === $metadata_images, 'Requested attachment metadata must preserve exact selected IDs and clear unavailable image URLs.' );
$metadata_request = new Carousel_Request( array( 'surface' => 'hero-carousel', 'ids' => '209,999', 'image_ids' => '90,91' ) );
$metadata_response = lunara_site_studio_carousel_metadata_response( $metadata_request );
check( 200 === $metadata_response->status && array( 208, 207, 206, 205, 204, 203 ) === $metadata_response->data['automatic'], 'Read-only metadata endpoint must return the exact shared envelope for comma-separated IDs.' );
$user_id = 0; $metadata_denied = lunara_site_studio_rest_route_permission( $metadata_request ); check( is_wp_error( $metadata_denied ) && 'site_studio_auth_required' === $metadata_denied->get_error_code(), 'Metadata route permission must reject unauthenticated reads.' );
$user_id = 7; $metadata_request->nonce = 'invalid'; $metadata_denied = lunara_site_studio_rest_route_permission( $metadata_request ); check( is_wp_error( $metadata_denied ) && 'site_studio_invalid_nonce' === $metadata_denied->get_error_code(), 'Metadata route permission must reject an invalid REST nonce.' ); $metadata_request->nonce = 'test-nonce';
$metadata_bad = lunara_site_studio_carousel_metadata_response( new Carousel_Request( array( 'surface' => 'hero-carousel', 'ids' => '209,,999' ) ) );
check( 400 === $metadata_bad->status && 'site_studio_carousel_metadata_invalid' === $metadata_bad->data['code'], 'Metadata endpoint must reject malformed ID lists without coercion.' );
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
$saved = $adapter->save_state( $hero ); check( ! is_wp_error( $saved ) && $saved['state']['adopted'], 'Explicit Apply adopts.' ); check( array( 'state', 'changed_sections', 'revision_id', 'timestamp' ) === array_keys( $saved ), 'Carousel Apply must return the generic host exact save envelope.' ); $canonical = $options['lunara_hero_command']; unset( $canonical['carousel'] ); check( $canonical === $legacy, 'Hero extends canonical option without destroying legacy keys.' );
$journal = lunara_home_carousel_defaults( 'journal' ); $journal['mode'] = 'manual'; $journal_result = lunara_site_studio_journal_carousel_adapter()->save_state( $journal ); check( $journal_result['state']['adopted'] && array() === $journal_result['state']['slides'], 'Empty manual persists as empty; no automatic fallback.' ); check( $options['lunara_hero_command']['carousel'] === $saved['state'], 'Journal Apply does not touch Hero.' );
$restored = $adapter->restore_revision( $saved['revision_id'] ); check( ! is_wp_error( $restored ) && $options['lunara_hero_command'] === $legacy, 'Revision restore reinstates exact pre-adoption presentation.' ); check( array( 'state', 'safety_revision_id', 'timestamp' ) === array_keys( $restored ), 'Carousel restore must return the generic host exact restore envelope.' );
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
