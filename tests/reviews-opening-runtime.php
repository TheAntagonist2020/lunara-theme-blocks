<?php
/**
 * Actual Reviews shell regression. Reuses the provider's isolated WordPress
 * harness, then renders the production shell and production film cards.
 * Run: php tests/reviews-opening-runtime.php [--fixtures]
 */
require __DIR__ . '/reviews-archive-studio-runtime.php';

// Additional core doubles are installed AFTER the provider suite so they
// cannot change the behavior of its existing function_exists branches.
if ( ! function_exists( 'paginate_links' ) ) { function paginate_links() { return ''; } }
if ( ! function_exists( 'get_pagenum_link' ) ) { function get_pagenum_link( $page ) { return home_url( '/reviews/' ); } }
if ( ! function_exists( 'remove_query_arg' ) ) { function remove_query_arg( $keys, $url ) { return strtok( $url, '?' ); } }
if ( ! function_exists( 'add_query_arg' ) ) { function add_query_arg( $key, $value, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . rawurlencode( $key ) . '=' . rawurlencode( $value ); } }
if ( ! function_exists( 'wp_trim_words' ) ) { function wp_trim_words( $text, $count = 55, $more = '…' ) { $words = preg_split( '/\s+/', trim( strip_tags( $text ) ) ); return implode( ' ', array_slice( $words, 0, $count ) ) . ( count( $words ) > $count ? $more : '' ); } }
if ( ! function_exists( 'wp_kses_post' ) ) { function wp_kses_post( $text ) { return $text; } }
if ( ! function_exists( 'strip_shortcodes' ) ) { function strip_shortcodes( $text ) { return $text; } }
if ( ! function_exists( 'get_bloginfo' ) ) { function get_bloginfo() { return 'UTF-8'; } }
if ( ! function_exists( 'get_post_timestamp' ) ) { function get_post_timestamp( $id, $field = 'date' ) { return strtotime( get_post( $id )->post_date ); } }
if ( ! function_exists( 'has_excerpt' ) ) { function has_excerpt( $id ) { return false; } }
if ( ! function_exists( 'get_post_field' ) ) { function get_post_field( $key, $id ) { return 'A close reading of the film and the details that stay with us.'; } }
if ( ! function_exists( 'wp_get_theme' ) ) { function wp_get_theme() { return new class { public function get( $key ) { preg_match( '/^Version:\s*(.+)$/m', file_get_contents( dirname( __DIR__ ) . '/style.css' ), $match ); return trim( $match[1] ); } }; } }

$opening_checks = 0;
$opening_assert = static function ( $condition, $message ) use ( &$opening_checks ) { ++$opening_checks; lunara_test_assert( $condition, $message ); };
$opening_fixtures = array();
$frontend_source = file_get_contents( dirname( __DIR__ ) . '/inc/frontend.php' );
$authority_start = strpos( $frontend_source, 'function lunara_output_review_archive_authority_css()' );
$authority_end = strpos( $frontend_source, "add_action( 'wp_head', 'lunara_output_review_archive_authority_css'", $authority_start );
$opening_assert( false !== $authority_start && false !== $authority_end, 'The actual dynamic authority CSS emitter is present.' );
eval( substr( $frontend_source, $authority_start, $authority_end - $authority_start ) );
$opening_defaults = lunara_reviews_archive_studio_defaults();
$retired_labels = array( 'debrief_kicker', 'debrief_depth', 'debrief_visible', 'debrief_latest', 'debrief_order', 'hero_action_run', 'hero_action_oscars', 'hero_action_journal' );
foreach ( $retired_labels as $key ) { $opening_defaults['labels'][ $key ] = 'Retired saved ' . $key; }
$_GET = array();
$lunara_test_is_review_route = true;
$lunara_test_is_reviews_page = false;
$lunara_test_is_reviews_template = false;
$lunara_test_is_director_tax = false;
$lunara_test_is_paged = false;
$lunara_test_paged_var = 0;
Lunara_Review_Image_Studio::$mode = 'custom';

foreach ( array( 'normal', 'empty', 'director', 'hero-off', 'long-copy' ) as $scenario ) {
	$lunara_test_is_director_tax = 'director' === $scenario;
	$lunara_test_is_review_route = ! $lunara_test_is_director_tax;
	$config = $opening_defaults;
	$config['section_visibility']['hero'] = 'hero-off' !== $scenario;
	$config['section_visibility']['pairing-desk'] = false;
	if ( 'long-copy' === $scenario ) {
		$config['title'] = 'The films that stay with us after the credits and the conversations that make us look again';
		$config['deck'] = 'A saved introduction with a difficult token ' . str_repeat( 'Cinema', 18 ) . ' and a generous amount of readable copy about contemporary film, festival discoveries, familiar classics, and the questions that follow us out of the theater.';
	}
	lunara_reviews_archive_studio_apply_config( $config );
	$public = lunara_reviews_archive_studio_get_public_config( false );
	$before = array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_pinned_id );
	$posts = 'empty' === $scenario ? array() : array( new WP_Post( get_post( 12 ) ), new WP_Post( get_post( 11 ) ) );
	$args = array(
		'classes' => 'lunara-review-archive-page' . ( 'director' === $scenario ? ' lunara-director-archive-page' : '' ),
		'kicker' => $public['kicker'],
		'title' => $public['title'],
		'copy' => $public['deck'],
		'posts' => $posts,
		'pagination' => '<a href="?paged=2">Next reviews</a>',
	);
	$html = lunara_render_review_archive_shell( $args );
	$opening_assert( 1 === preg_match_all( '/<h1\b/i', $html ), $scenario . ': exactly one H1 survives.' );
	foreach ( array( 'Reviews Command', 'Archive Depth', 'Visible File', 'Latest Update', 'Current Order', 'lunara-review-archive-debrief', 'lunara-review-archive-hero-actions' ) as $removed ) {
		$opening_assert( false === strpos( $html, $removed ), $scenario . ': no public statistics/action panel: ' . $removed );
	}
	$opening_assert( false === strpos( $html, 'Retired saved' ), $scenario . ': custom legacy labels cannot revive the removed panel.' );
	$opening_assert( $before === array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_pinned_id ), $scenario . ': rendering never changes canonical selection or settings.' );
	$opening_assert( false === strpos( $html, '<script' ), $scenario . ': content is server-rendered without inline scripts.' );
	if ( 'empty' === $scenario ) {
		$opening_assert( false !== strpos( $html, 'No reviews yet.' ) && false === strpos( $html, 'lunara-review-feature-card' ), 'Empty archive has a readable empty state and no invented lead.' );
	} else {
		preg_match( '/<a class="lunara-review-feature-title-link"[^>]*>(.*?)<\/a>/s', $html, $lead );
		$opening_assert( isset( $lead[1] ) && esc_html( get_the_title( 12 ) ) === $lead[1], $scenario . ': first queried film remains the lead even when an eligible newer film follows it.' );
		$opening_assert( false !== strpos( $html, get_the_title( 11 ) ), $scenario . ': the remaining archive film remains browsable.' );
		$opening_assert( false !== strpos( $html, 'Next reviews' ), $scenario . ': archive pagination survives.' );
	}
	$opening_assert( ( 'hero-off' === $scenario ) === ( false !== strpos( $html, 'lunara-review-archive-fallback-title' ) ), $scenario . ': hidden intro alone uses the accessible fallback H1.' );
	ob_start(); lunara_output_review_archive_authority_css(); $authority_markup = ob_get_clean();
	preg_match( '/<style[^>]*>(.*?)<\/style>/s', $authority_markup, $authority_css );
	$opening_assert( ! empty( $authority_css[1] ), $scenario . ': actual saved geometry variables accompany renderer markup.' );
	$opening_fixtures[ $scenario ] = array( 'html' => $html, 'authorityCss' => $authority_css[1], 'bodyClass' => 'director' === $scenario ? 'tax-lunara_director' : 'post-type-archive-review' );
}

// Execute the actual Classic labels loop, then submit only the labels it
// exposes. Omitted retired keys must retain fresh canonical values, not reset.
$config = $opening_defaults;
$classic_source = file_get_contents( dirname( __DIR__ ) . '/inc/reviews-archive-studio.php' );
preg_match( '/<div class="lunara-reviews-archive-label-grid">(.*?)<\/div>/s', $classic_source, $classic_loop );
$opening_assert( ! empty( $classic_loop[1] ), 'The Classic labels form loop is located in its actual renderer.' );
ob_start(); eval( '?>' . $classic_loop[1] ); $classic_html = ob_get_clean();
foreach ( $config['labels'] as $key => $value ) {
	$opening_assert( in_array( $key, $retired_labels, true ) === ( false === strpos( $classic_html, 'name="lunara_reviews_archive_labels[' . $key . ']"' ) ), 'Classic shows exactly the still-active label controls: ' . $key );
}
lunara_reviews_archive_studio_apply_config( $config );
$classic_request = array(
	'lunara_reviews_archive_identity' => array_intersect_key( $config, array_flip( array( 'kicker', 'title', 'deck', 'supporting_copy' ) ) ),
	'lunara_reviews_archive_labels' => array( 'toolbar_title' => 'Choose the films you want to explore' ),
	'lunara_reviews_archive_section_visibility' => $config['section_visibility'],
	'lunara_reviews_archive_section_positions' => array_combine( $config['section_order'], range( 1, count( $config['section_order'] ) ) ),
);
$candidate = lunara_reviews_archive_studio_config_from_request( $classic_request );
$saved = lunara_reviews_archive_studio_promote_config_transaction( $candidate );
$opening_assert( ! is_wp_error( $saved ), 'Classic save succeeds while omitting all retired label fields.' );
foreach ( $retired_labels as $key ) { $opening_assert( $config['labels'][ $key ] === $saved['state']['labels'][ $key ], 'Classic omission preserves saved ' . $key ); }
$opening_assert( 'Choose the films you want to explore' === $saved['state']['labels']['toolbar_title'], 'Remaining visible Classic labels still save.' );
$legacy_snapshot = $config; unset( $legacy_snapshot['selection_version'] );
$legacy_revision = lunara_reviews_archive_studio_push_revision( $legacy_snapshot );
$restored = lunara_reviews_archive_studio_restore_revision_transaction( $legacy_revision );
$opening_assert( ! is_wp_error( $restored ) && $config['labels'] === $restored['state']['labels'], 'An old revision restores retained labels without migration loss.' );

fwrite( STDOUT, 'reviews-opening-runtime: ' . $opening_checks . " assertions passed.\n" );
if ( in_array( '--fixtures', $argv, true ) ) { echo "LUNARA_OPENING_FIXTURES\n" . json_encode( $opening_fixtures ); }
