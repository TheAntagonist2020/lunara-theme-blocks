<?php
/**
 * Runtime regression for safe Homepage Structure write-through.
 *
 * @package Lunara_Film
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_home_test_content = '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' . "\n\n"
	. '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me between lanes.<!-- /wp:core/paragraph -->' . "\n\n"
	. '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->';
$lunara_home_test_update = null;

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

function parse_blocks( $content ) {
	return array(
		array( 'blockName' => 'core/heading', 'serialized' => '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' ),
		array( 'blockName' => 'lunara/cinematic-hero', 'serialized' => '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' ),
		array( 'blockName' => 'core/paragraph', 'serialized' => '<!-- wp:core/paragraph -->Keep me between lanes.<!-- /wp:core/paragraph -->' ),
		array( 'blockName' => 'lunara/latest-reviews', 'serialized' => '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' ),
		array( 'blockName' => 'core/paragraph', 'serialized' => '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->' ),
	);
}

function serialize_block( $block ) {
	return $block['serialized'];
}

function wp_update_post( $post ) {
	global $lunara_home_test_update;
	$lunara_home_test_update = $post;
	return $post['ID'];
}

function sanitize_text_field( $value ) {
	return (string) $value;
}

function has_block() {
	return false;
}

function do_blocks( $content ) {
	return $content;
}

require dirname( __DIR__ ) . '/inc/home-blocks.php';

$changed = lunara_write_home_section_blocks( array( 'latest-reviews', 'hero' ) );
lunara_home_test_assert( true === $changed, 'A requested lane reorder must update the front page.' );
lunara_home_test_assert( is_array( $lunara_home_test_update ), 'Homepage Structure must issue one explicit update on save.' );

$expected = '<!-- wp:core/heading -->Before<!-- /wp:core/heading -->' . "\n\n"
	. '<!-- wp:lunara/latest-reviews {"source":"curated","count":8} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me between lanes.<!-- /wp:core/paragraph -->' . "\n\n"
	. '<!-- wp:lunara/cinematic-hero {"overrideTitle":"Stored hero"} /-->' . "\n\n"
	. '<!-- wp:core/paragraph -->Keep me after lanes.<!-- /wp:core/paragraph -->';

lunara_home_test_assert( $expected === $lunara_home_test_update['post_content'], 'Reordering canonical lanes must preserve every unknown block in its original slot and retain canonical attributes.' );

echo "home-block composition runtime: all assertions passed.\n";
