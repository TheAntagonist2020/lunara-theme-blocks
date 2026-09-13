<?php
/** Shared artwork contracts against each real provider, adapter and REST permission boundary. */
require_once dirname( __DIR__ ) . '/inc/site-studio-archive-media.php';
$media_checks = 0;
$media_check = static function ( $condition, $message ) use ( &$media_checks ) { ++$media_checks; lunara_test_assert( $condition, $message ); };
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data; private $status; private $headers;
		public function __construct( $data, $status = 200, $headers = array() ) { $this->data = $data; $this->status = $status; $this->headers = $headers; }
		public function get_data() { return $this->data; }
		public function get_status() { return $this->status; }
		public function get_headers() { return $this->headers; }
	}
}
$archive_media_sources = array(); $archive_media_source_reads = array(); $archive_media_denied_ids = array( 605 );
function wp_get_attachment_image_src( $id, $size ) {
	$GLOBALS['archive_media_source_reads'][] = array( $id, $size );
	return isset( $GLOBALS['archive_media_sources'][$id] ) ? $GLOBALS['archive_media_sources'][$id] : false;
}
foreach ( array_merge( range( 501, 512 ), range( 601, 611 ) ) as $id ) {
	$lunara_test_posts[$id] = (object) array( 'ID' => $id, 'post_type' => 'attachment', 'post_status' => 'inherit' );
	$lunara_test_images[$id] = 'image/jpeg';
	$archive_media_sources[$id] = array( 'https://example.test/uploads/' . $id . '-spotlight.jpg', 1600, 900, true );
}
$lunara_test_posts[601]->post_status = 'private';
$lunara_test_posts[602]->post_status = 'draft';
$lunara_test_posts[603]->post_status = 'trash';
$lunara_test_posts[604]->post_type = 'review';
$lunara_test_images[606] = 'application/pdf';
$archive_media_sources[607] = false;
$archive_media_sources[608] = array( 'javascript:secret()', 1600, 900, true );
$archive_media_sources[609] = array( 'https://example.test/zero.jpg', 0, 900, true );
$archive_media_sources[610] = array( 'https://example.test/shape.jpg', array( 100 ), 900, true );

$surface = $archive_selection_kind . '-archive';
$adapter = call_user_func( 'lunara_site_studio_' . $archive_selection_kind . '_archive_adapter' );
$base = call_user_func( $prefix . 'defaults' );
$base['labels']['toolbar_title'] = 'Keep this Classic title';
$base['lead_mode'] = 'manual'; $base['lead_id'] = 12;
$initial = call_user_func( $prefix . 'promote_config_transaction', $base );
$media_check( ! is_wp_error( $initial ), 'Artwork baseline saves through its original provider.' );
$candidate = $adapter->read_state();
$candidate['gallery'] = array( 'kicker' => 'Archive Images', 'title' => 'At the movies', 'copy' => 'Selected views.', 'items' => array() );
foreach ( array( 502, 501 ) as $index => $id ) {
	$candidate['gallery']['items'][] = array( 'order' => $index + 1, 'attachment_id' => $id, 'alt' => 'Saved placement alt ' . $id, 'caption' => 'Frame ' . $id, 'link_url' => 'https://example.test/read/', 'credit' => 'Photographer', 'source' => 'Film studio', 'source_url' => 'https://example.test/source/', 'focal_x' => 23 + $index, 'focal_y' => 71 - $index );
}
$candidate['retention'][0] = array_replace( $candidate['retention'][0], array( 'order' => 3, 'label' => 'Continue here', 'destination' => 'custom', 'url' => 'https://example.test/next/', 'image_id' => 501, 'image_alt' => 'Continuation alt', 'image_credit' => 'Photographer', 'image_source' => 'Film studio', 'image_source_url' => 'https://example.test/source/', 'focal_x' => 17, 'focal_y' => 83 ) );
$candidate['retention'][2]['order'] = 1;
$candidate['retention'][1]['visible'] = false;
$candidate['labels']['retention_kicker'] = 'Continue Reading';
$candidate['labels']['retention_title'] = 'More from Lunara';
if ( 'journal' === $archive_selection_kind ) { $candidate['retention'][0]['title'] = 'Another perspective'; $candidate['retention'][0]['copy'] = 'Follow the next file.'; }
else { $candidate['labels']['retention_copy'] = 'Choose your next destination.'; }
$before_options = serialize( $lunara_test_options );
$before_pin = 'reviews' === $archive_selection_kind ? $lunara_test_pinned_id : null;
$preview = $adapter->create_preview( $candidate );
$media_check( ! is_wp_error( $preview ), 'Shared artwork creates an existing provider-owned preview.' );
$_GET['lunara_' . $archive_selection_kind . '_preview'] = $preview['token'];
$private = call_user_func( $prefix . 'get_public_config' );
$media_check( $candidate['gallery'] === $private['gallery'] && $candidate['retention'] === $private['retention'], 'Private provider reads include all ordered artwork, metadata and focal changes.' );
$markup = call_user_func( $prefix . 'render_gallery', $private['gallery'] );
$media_check( false !== strpos( $markup, 'Frame 502' ) && false !== strpos( $markup, '--lunara-gallery-focus-x:23%;--lunara-gallery-focus-y:71%' ), 'Real gallery SSR renders private caption and focal values.' );
unset( $_GET['lunara_' . $archive_selection_kind . '_preview'] );
$media_check( $before_options === serialize( $lunara_test_options ) && ( null === $before_pin || $before_pin === $lunara_test_pinned_id ), 'Artwork preview cannot write public options, history or Review pin.' );
$saved = $adapter->save_state( $candidate );
$media_check( ! is_wp_error( $saved ), 'Artwork Apply uses the original provider transaction.' );
$public = $adapter->read_state();
$media_check( $candidate['gallery'] === $public['gallery'] && $candidate['retention'] === $public['retention'] && $candidate['labels']['retention_title'] === $public['labels']['retention_title'], 'Apply persists exactly the editor artwork order, framing, visibility and headings.' );
$media_check( 'Keep this Classic title' === $public['labels']['toolbar_title'] && 0 === $public['selection_version'] && 12 === $public['lead_id'], 'Artwork Apply preserves unrelated Classic labels and legacy story ownership.' );
$media_check( false !== strpos( serialize( $lunara_test_actions_fired ), 'lunara_' . $archive_selection_kind . '_archive_studio_invalidate_routes' ), 'Artwork Apply retains canonical route invalidation.' );
$next = $public; $next['gallery']['items'] = array(); $next['retention'][0]['image_id'] = 0;
$cleared = $adapter->save_state( $next );
$media_check( ! is_wp_error( $cleared ) && '' === call_user_func( $prefix . 'render_gallery', $cleared['state']['gallery'] ), 'Clearing the gallery is a saved zero-output state.' );
$restored = $adapter->restore_revision( $cleared['revision_id'] );
$media_check( ! is_wp_error( $restored ) && $public['gallery'] === $restored['state']['gallery'] && $public['retention'] === $restored['state']['retention'] && ! empty( $restored['safety_revision_id'] ), 'History restores exact artwork and continuation fields with a safety revision.' );
$bad_cases = array();
$bad = $public; $bad['gallery']['items'][0]['source_url'] = 'javascript:secret()'; $bad_cases[] = array( $bad, 'gallery' );
$bad = $public; $bad['gallery']['items'][0]['attachment_id'] = 999999; $bad_cases[] = array( $bad, 'gallery' );
$bad = $public; $bad['gallery']['items'][] = $bad['gallery']['items'][0]; $bad_cases[] = array( $bad, 'gallery' );
$bad = $public; $bad['gallery']['items'] = 'not-an-array'; $bad_cases[] = array( $bad, 'gallery' );
$bad = $public; $bad['retention'] = array(); $bad_cases[] = array( $bad, 'retention' );
$bad = $public; $bad['retention'][0]['image_credit'] = ''; $bad_cases[] = array( $bad, 'retention' );
$bad = $public; $bad['retention'][0]['destination'] = 'invented'; $bad_cases[] = array( $bad, 'retention' );
$bad = $public; $bad['labels']['retention_title'] = ''; $bad_cases[] = array( $bad, 'labels.retention_title' );
foreach ( $bad_cases as $case ) {
	$before_options = serialize( $lunara_test_options ); $before_previews = serialize( $lunara_test_transients );
	foreach ( array( 'save_state', 'create_preview' ) as $method ) {
		$error = $adapter->$method( $case[0] );
		$media_check( is_wp_error( $error ) && isset( lunara_site_studio_safe_validation_fields( $error )[$case[1]] ), 'Invalid artwork is blocked with a safe, visible field anchor.' );
	}
	$media_check( $before_options === serialize( $lunara_test_options ) && $before_previews === serialize( $lunara_test_transients ), 'Rejected artwork creates no public or private state.' );
}

$params = array( 'surface' => $surface, 'image_ids' => '502,501,601,602,603,604,605,606,607,608,609,610,999999' );
$request = new Lunara_Archive_Metadata_Request( $params );
foreach ( array( 'authentication', 'nonce', 'capability' ) as $gate ) {
	$archive_metadata_logged_in = 'authentication' !== $gate; $archive_metadata_nonce = 'nonce' !== $gate; $lunara_test_can_edit = 'capability' !== $gate;
	$reads = count( $archive_media_source_reads ); $headers = $lunara_test_nocache_calls;
	$denied = lunara_site_studio_archive_media( $request );
	$media_check( is_wp_error( $denied ) && $reads === count( $archive_media_source_reads ) && $headers < $lunara_test_nocache_calls, 'Metadata denials are private and occur before derivative reads.' );
}
$archive_metadata_logged_in = true; $archive_metadata_nonce = true; $lunara_test_can_edit = true;
$before_options = serialize( $lunara_test_options ); $before_previews = serialize( $lunara_test_transients );
$response = lunara_site_studio_archive_media( $request );
$media_check( $response instanceof WP_REST_Response && false !== strpos( $response->get_headers()['Cache-Control'], 'no-store' ), 'Successful metadata responses explicitly forbid storage.' );
$images = $response->get_data()['images'];
$media_check( array_map( 'intval', explode( ',', $params['image_ids'] ) ) === array_column( $images, 'id' ), 'Metadata preserves the exact requested sequence including missing IDs.' );
foreach ( $images as $index => $image ) {
	$media_check( array( 'id', 'available', 'url', 'width', 'height', 'default_alt' ) === array_keys( $image ), 'Every image response has exactly the agreed six safe fields.' );
	if ( $index < 2 ) { $media_check( true === $image['available'] && 1600 === $image['width'] && 900 === $image['height'] && 'Media Library alt ' . $image['id'] === $image['default_alt'] && false !== strpos( $image['url'], '-spotlight.jpg' ), 'Available artwork uses public derivative dimensions and default alt.' ); }
	else { $media_check( false === $image['available'] && '' === $image['url'] && '' === $image['default_alt'] && 0 === $image['width'] && 0 === $image['height'], 'Private, deleted, unreadable and malformed media expose no URL, dimensions or alt.' ); }
}
$media_check( array( array( 502, 'lunara-hero-spotlight' ), array( 501, 'lunara-hero-spotlight' ), array( 607, 'lunara-hero-spotlight' ), array( 608, 'lunara-hero-spotlight' ), array( 609, 'lunara-hero-spotlight' ), array( 610, 'lunara-hero-spotlight' ) ) === $archive_media_source_reads, 'Only readable image attachments reach the exact public derivative resolver.' );
foreach ( array( '501,501', '0', '-501', '501.0', '0501', '501,', ' 501', '10000000000', '999999999999999999999', true, 501, array( 501 ), implode( ',', range( 1, 16 ) ) ) as $bad_ids ) {
	$reads = count( $archive_media_source_reads );
	$error = lunara_site_studio_archive_media( new Lunara_Archive_Metadata_Request( array( 'surface' => $surface, 'image_ids' => $bad_ids ) ) );
	$media_check( is_wp_error( $error ) && 'site_studio_media_invalid' === $error->get_error_code() && $reads === count( $archive_media_source_reads ), 'Malformed, duplicate or excessive metadata IDs fail before image reads.' );
}
$empty = lunara_site_studio_archive_media( new Lunara_Archive_Metadata_Request( array( 'surface' => $surface ) ) );
$media_check( array( 'images' => array() ) === $empty->get_data(), 'An empty artwork request is a valid empty metadata response.' );
$maximum = lunara_site_studio_archive_media( new Lunara_Archive_Metadata_Request( array( 'surface' => $surface, 'image_ids' => implode( ',', range( 501, 515 ) ) ) ) );
$media_check( 15 === count( $maximum->get_data()['images'] ), 'All twelve gallery plus three continuation attachments fit the endpoint bound.' );
foreach ( array( null, array( 'surface' => $surface ), new Lunara_Archive_Metadata_Request( array( 'surface' => array( $surface ) ) ), new Lunara_Archive_Metadata_Request( array( 'surface' => $surface . '!', 'image_ids' => '501' ) ), new Lunara_Archive_Metadata_Request( array( 'surface' => 'global-design', 'image_ids' => '501' ) ) ) as $bad_request ) {
	$reads = count( $archive_media_source_reads ); $headers = $lunara_test_nocache_calls;
	$error = lunara_site_studio_archive_media( $bad_request );
	$media_check( is_wp_error( $error ) && $reads === count( $archive_media_source_reads ) && $headers < $lunara_test_nocache_calls, 'Malformed request objects and nonarchive surfaces fail privately before media lookup.' );
}
$media_check( $before_options === serialize( $lunara_test_options ) && $before_previews === serialize( $lunara_test_transients ), 'Metadata remains read-only and never manufactures a preview or revision.' );
fwrite( STDOUT, 'archive-media-' . $archive_selection_kind . ': ' . $media_checks . " assertions passed.\n" );
