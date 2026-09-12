<?php
/** Real REST boundary + real providers, executed by both archive runtime gates. */
$archive_metadata_logged_in = true;
$archive_metadata_nonce = true;
class Lunara_Archive_Metadata_Request {
	private $params;
	public function __construct( $params ) { $this->params = $params; }
	public function get_param( $key ) { return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null; }
	public function get_header( $key ) { return 'archive-test-nonce'; }
}
function is_user_logged_in() { return $GLOBALS['archive_metadata_logged_in']; }
function wp_verify_nonce( $nonce, $action ) { return $GLOBALS['archive_metadata_nonce'] && 'archive-test-nonce' === $nonce && 'wp_rest' === $action; }
function lunara_control_desk_render_reviews_archive_studio() {}
function lunara_control_desk_render_journal_archive_studio() {}
function rest_ensure_response( $data ) { return $data; }
function get_the_date( $format, $id ) { return 'Aug 10, 2026'; }
function get_the_post_thumbnail_url( $id, $size = 'medium' ) { return 'https://example.test/uploads/' . $id . '-' . $size . '.jpg'; }
function has_post_thumbnail( $id ) { return false; }
function attachment_url_to_postid( $url ) { return 0; }
if ( ! function_exists( 'wp_strip_all_tags' ) ) { function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); } }
if ( ! function_exists( 'wp_parse_args' ) ) { function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); } }
class Lunara_Review_Image_Studio {
	public static $mode = 'custom';
	public static function resolve_slot( $id, $slot ) { return array( 'mode' => self::$mode, 'url' => 'custom' === self::$mode ? 'https://image.tmdb.org/t/p/original/custom-' . $id . '.jpg' : '' ); }
}
require_once dirname( __DIR__ ) . '/inc/site-studio-rest.php';
$metadata_params = array( 'surface' => $archive_selection_kind . '-archive', 'ids' => '11,13,999999', 'q' => '', 'selection_version' => '1', 'lead_mode' => 'automatic', 'lead_id' => '13', 'lane_mode' => 'curated', 'curated_ids' => '12,13,999999' );
$metadata_request = new Lunara_Archive_Metadata_Request( $metadata_params );
$reads_before = count( $lunara_test_get_posts_args );
$archive_metadata_logged_in = false;
$denied = lunara_site_studio_archive_selection_items( $metadata_request );
$check( is_wp_error( $denied ) && 'site_studio_auth_required' === $denied->get_error_code() && $reads_before === count( $lunara_test_get_posts_args ), 'Anonymous metadata requests are rejected before story reads.' );
$archive_metadata_logged_in = true; $archive_metadata_nonce = false;
$denied = lunara_site_studio_archive_selection_items( $metadata_request );
$check( is_wp_error( $denied ) && 'site_studio_invalid_nonce' === $denied->get_error_code() && $reads_before === count( $lunara_test_get_posts_args ), 'Missing/expired nonce cannot read metadata.' );
$archive_metadata_nonce = true; $lunara_test_can_edit = false;
$denied = lunara_site_studio_archive_selection_items( $metadata_request );
$check( is_wp_error( $denied ) && 'site_studio_forbidden' === $denied->get_error_code() && $reads_before === count( $lunara_test_get_posts_args ), 'Surface capability is enforced before metadata reads.' );
$lunara_test_can_edit = true;
$before_options = serialize( $lunara_test_options );
$metadata = lunara_site_studio_archive_selection_items( $metadata_request );
$check( is_array( $metadata ) && 1 === $metadata['selection_version'] && 11 === $metadata['lead_id'] && array( 11, 12 ) === $metadata['priority_ids'], 'Metadata reports canonical effective lead and eligible priority order.' );
$by_id = array_column( $metadata['items'], null, 'id' );
$check( false === $by_id[13]['available'] && 'Unavailable selection #13' === $by_id[13]['title'] && '' === $by_id[13]['image_url'] && '' === $by_id[13]['published_date'], 'Unpublished selection metadata is fully redacted.' );
$check( false === $by_id[999999]['available'] && ! in_array( 13, $metadata['results'], true ) && ! in_array( 999999, $metadata['results'], true ), 'Deleted and unpublished records never enter search results.' );
$check( in_array( 'retained_lead_unavailable', $metadata['warnings'], true ) && in_array( 'curated_selection_unavailable', $metadata['warnings'], true ), 'Metadata explains inactive missing lead and skipped priorities.' );
if ( 'reviews' === $archive_selection_kind ) {
	$check( false !== strpos( $by_id[11]['image_url'], 'custom-11.jpg' ), 'Review metadata uses public card resolver custom artwork.' );
	Lunara_Review_Image_Studio::$mode = 'off';
	$off = lunara_site_studio_archive_selection_items( $metadata_request );
	$off_items = array_column( $off['items'], null, 'id' );
	$check( '' === $off_items[11]['image_url'], 'Explicit disabled Review art stays absent in editor metadata.' );
	Lunara_Review_Image_Studio::$mode = 'custom';
	$legacy_params = $metadata_params; $legacy_params['selection_version'] = '0';
	$legacy_metadata = lunara_site_studio_archive_selection_items( new Lunara_Archive_Metadata_Request( $legacy_params ) );
	$check( 12 === $legacy_metadata['lead_id'] && 12 === $legacy_metadata['priority_ids'][0], 'Legacy Reviews lead summary agrees with its query-first curated order.' );
} else { $check( false !== strpos( $by_id[11]['image_url'], '11-full.jpg' ), 'Journal metadata uses the uncropped featured source used by its archive.' ); }
$check( $before_options === serialize( $lunara_test_options ), 'Metadata endpoint does not persist candidate state.' );
foreach ( array( array( 'ids' => '11,11' ), array( 'curated_ids' => implode( ',', range( 1, 25 ) ) ), array( 'lead_id' => '-1' ), array( 'lead_mode' => array( 'manual' ) ), array( 'q' => array( 'secret' ) ) ) as $bad_params ) {
	$bad = lunara_site_studio_archive_selection_items( new Lunara_Archive_Metadata_Request( array_replace( $metadata_params, $bad_params ) ) );
	$check( is_wp_error( $bad ) && 'site_studio_items_invalid' === $bad->get_error_code(), 'Malformed metadata parameters fail closed.' );
}
