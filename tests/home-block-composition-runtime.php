<?php
/**
 * Runtime regression for safe Homepage Structure write-through.
 *
 * @package Lunara_Film
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_home_test_content = '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' . "\n\n"
	. '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep C:\\\\Cinema between lanes.<!-- /wp:core/paragraph -->' . "\n\n"
	. '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->';
$lunara_home_test_update = null;
$lunara_home_test_update_error = false;
$lunara_home_test_wp_error_flag = null;
$lunara_home_test_has_blocks = false;

function lunara_home_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function get_option( $key ) {
	if ( 'show_on_front' === $key ) {
		return 'page';
	}
	if ( 'page_on_front' === $key ) {
		return 4055;
	}
	return null;
}

function get_post_field( $field, $post_id ) {
	global $lunara_home_test_content;
	return 'post_content' === $field && 4055 === $post_id ? $lunara_home_test_content : '';
}

function apply_filters( $hook, $value ) {
	return $value;
}

function parse_blocks( $content ) {
	return array(
		array( 'blockName' => 'core/heading', 'serialized' => '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' ),
		array( 'blockName' => 'lunara/cinematic-hero', 'serialized' => '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' ),
		array( 'blockName' => 'core/paragraph', 'serialized' => '<!-- wp:core/paragraph -->Keep C:\\\\Cinema between lanes.<!-- /wp:core/paragraph -->' ),
		array( 'blockName' => 'lunara/latest-reviews', 'serialized' => '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' ),
		array( 'blockName' => 'core/paragraph', 'serialized' => '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->' ),
	);
}

function serialize_block( $block ) {
	return $block['serialized'];
}

class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

function is_wp_error( $value ) { return $value instanceof WP_Error; }

function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : addslashes( (string) $value ); }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value ); }

function wp_update_post( $post, $wp_error = false ) {
	global $lunara_home_test_update, $lunara_home_test_update_error, $lunara_home_test_wp_error_flag, $lunara_home_test_content;
	$lunara_home_test_wp_error_flag = $wp_error;
	if ( $lunara_home_test_update_error ) {
		return $wp_error ? new WP_Error( 'injected_home_update_failure' ) : 0;
	}
	$lunara_home_test_update = wp_unslash( $post );
	$lunara_home_test_content = $lunara_home_test_update['post_content'];
	return $post['ID'];
}

function sanitize_text_field( $value ) {
	return (string) $value;
}

function has_block( $name, $content ) {
	global $lunara_home_test_has_blocks;
	return $lunara_home_test_has_blocks && false !== strpos( (string) $name, 'lunara/' );
}

function do_blocks( $content ) {
	return $content;
}

require dirname( __DIR__ ) . '/inc/home-blocks.php';

$composed = lunara_compose_home_section_blocks( $lunara_home_test_content, array( 'latest-reviews', 'hero' ) );
lunara_home_test_assert( is_string( $composed ), 'The pure Homepage composer must return candidate content without writing.' );
lunara_home_test_assert( null === $lunara_home_test_update, 'The pure Homepage composer must perform no post write.' );

$changed = lunara_write_home_section_blocks( array( 'latest-reviews', 'hero' ) );
lunara_home_test_assert( true === $changed, 'A requested lane reorder must update the front page.' );
lunara_home_test_assert( is_array( $lunara_home_test_update ), 'Homepage Structure must issue one explicit update on save.' );
lunara_home_test_assert( true === $lunara_home_test_wp_error_flag, 'Homepage Structure must request WP_Error propagation from wp_update_post.' );

$expected = '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' . "\n\n"
	. '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep C:\\\\Cinema between lanes.<!-- /wp:core/paragraph -->' . "\n\n"
	. '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->';

lunara_home_test_assert( $expected === $lunara_home_test_update['post_content'], 'Reordering canonical lanes must preserve every unknown block in its original slot and retain canonical attributes.' );

$lunara_home_test_content = "\n" . $expected . "\n";
$lunara_home_test_update = null;
$byte_change = lunara_write_home_section_blocks( array( 'latest-reviews', 'hero' ) );
lunara_home_test_assert( true === $byte_change && $expected === $lunara_home_test_update['post_content'], 'Homepage writer no-op detection must use byte equality, not trim-equivalence that hides leading/trailing-byte changes.' );

$lunara_home_test_update_error = true;
$failure = lunara_write_home_section_blocks( array( 'hero', 'latest-reviews' ) );
lunara_home_test_assert( is_wp_error( $failure ) && 'injected_home_update_failure' === $failure->get_error_code(), 'Homepage Structure must propagate the exact wp_update_post failure.' );

$lunara_home_test_has_blocks = true;
$sync_failure = lunara_sync_home_section_blocks_from_settings();
lunara_home_test_assert( is_wp_error( $sync_failure ) && 'injected_home_update_failure' === $sync_failure->get_error_code(), 'Legacy Homepage synchronization must propagate the exact writer error to its Control Desk callers.' );

echo "home-block composition runtime: all assertions passed.\n";
