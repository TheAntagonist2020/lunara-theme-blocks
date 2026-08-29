<?php
/**
 * Isolated behavioral contract for the Oscars ceremony winner map.
 *
 * Run: php tests/oscars-winner-map-runtime.php
 *
 * Regression origin: lunara_get_home_oscars_snapshot() built a winner map
 * internally and never returned it. The Oscars portal read
 * $snapshot['winner_map'], got nothing, produced zero winner cards, and its
 * Latest Ceremony Winners section therefore never rendered — for the entire
 * life of the section. The homepage rotating lane was unaffected because it
 * rebuilds the map from the rollup itself, which is exactly why the defect
 * survived: one of the two surfaces always looked fine.
 *
 * The contract here is the transport, not the presentation: whatever the
 * snapshot promises its consumers must actually be inside the array it
 * returns, and must survive the transient round trip.
 */

define( 'ABSPATH', __DIR__ . '/' );

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

// ---------------------------------------------------------------------------
// Minimal WordPress surface.
// ---------------------------------------------------------------------------
$lunara_test_transients  = array();
$lunara_test_set_calls   = array();

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

function get_transient( $key ) {
	global $lunara_test_transients;
	return array_key_exists( $key, $lunara_test_transients ) ? $lunara_test_transients[ $key ] : false;
}
function set_transient( $key, $value, $ttl = 0 ) {
	global $lunara_test_transients, $lunara_test_set_calls;
	$lunara_test_transients[ $key ] = $value;
	$lunara_test_set_calls[]        = array( $key, $ttl );
	return true;
}
function delete_transient( $key ) {
	global $lunara_test_transients;
	unset( $lunara_test_transients[ $key ] );
	return true;
}
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function add_action() {}
function add_filter() {}
function apply_filters( $tag, $value ) { return $value; }
function esc_url( $url ) { return $url; }
function esc_html( $text ) { return $text; }
function esc_attr( $text ) { return $text; }
function number_format_i18n( $number ) { return (string) $number; }
function sanitize_title( $title ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $title ) ), '-' );
}
function wp_date( $format ) { return gmdate( $format ); }
function get_theme_mod( $key, $default = false ) { return $default; }
function get_option( $key, $default = false ) { return $default; }
function __( $text ) { return $text; }
function _x( $text ) { return $text; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }
function absint( $value ) { return abs( intval( $value ) ); }
function wp_get_attachment_image_url( $id, $size = 'large' ) { return 'https://img.test/attachment-' . intval( $id ) . '.jpg'; }
function wp_get_attachment_image( $id, $size = 'large', $icon = false, $attr = array() ) {
	return '<img src="https://img.test/attachment-' . intval( $id ) . '.jpg" alt="" />';
}
function wp_get_attachment_metadata( $id ) { return array( 'width' => 500, 'height' => 750 ); }
function get_post_thumbnail_id( $post = null ) { return 0; }
function get_permalink( $post = null ) { return 'https://example.test/?p=' . intval( $post ); }
function get_the_title( $post = null ) { return 'Fixture Title'; }
function get_posts( $args = array() ) { return array(); }
function get_post_meta( $id, $key = '', $single = false ) { return $single ? '' : array(); }
function has_post_thumbnail( $post = null ) { return false; }

// ---------------------------------------------------------------------------
// Academy Awards fixture. Two winners share DIRECTING so the tie rule is
// exercised, and one row carries an empty canonical category so the skip
// branch is exercised.
// ---------------------------------------------------------------------------
$lunara_test_rollup = array(
	'year'        => '2025',
	'winner_rows' => array(
		array( 'canonical_category' => 'BEST PICTURE', 'film' => 'Anora', 'film_id' => 'tt28607951', 'name' => '' ),
		array( 'canonical_category' => 'DIRECTING', 'film' => 'Anora', 'film_id' => 'tt28607951', 'name' => 'Sean Baker' ),
		array( 'canonical_category' => 'DIRECTING', 'film' => 'Tie Runner-Up', 'film_id' => 'tt00000002', 'name' => 'Second Director' ),
		array( 'canonical_category' => '', 'film' => 'Unclassified', 'film_id' => 'tt00000003', 'name' => '' ),
		array( 'canonical_category' => '   ', 'film' => 'Whitespace Only', 'film_id' => 'tt00000004', 'name' => '' ),
	),
	'categories'  => array( 'BEST PICTURE', 'DIRECTING' ),
);

class Academy_Awards_Table {
	public static function get_instance() { static $i = null; return $i ?: $i = new self(); }
	public function get_max_ceremony() { return 97; }
	public function get_ceremony_rollup( $ceremony ) {
		global $lunara_test_rollup;
		return 97 === intval( $ceremony ) ? $lunara_test_rollup : array();
	}
	public function ordinal( $n ) { return $n . 'th'; }
	public function get_ceremony_year( $n ) { return '2025'; }
	public function get_ceremony_url( $n ) { return 'https://example.test/oscars/ceremony/' . $n . '/'; }
	public function get_database_url() { return 'https://example.test/oscars/'; }
	public function get_categories_index_url() { return 'https://example.test/oscars/categories/'; }
	public function get_category_url( $c ) { return 'https://example.test/oscars/category/' . sanitize_title( $c ) . '/'; }
	public function format_category_display( $c ) { return ucwords( strtolower( $c ) ); }
	public function get_title_visual_package( $imdb_id, $size = 'large' ) {
		return array( 'poster_url' => 'https://img.test/' . $imdb_id . '.jpg' );
	}
	public function build_entity_url_from_id( $imdb_id ) {
		return 'https://example.test/oscars/title/' . $imdb_id . '/';
	}
}

require dirname( __DIR__ ) . '/inc/home-sections.php';

// ---------------------------------------------------------------------------
// 1. The map reducer itself.
// ---------------------------------------------------------------------------
lunara_test_assert( function_exists( 'lunara_build_oscars_winner_map' ), 'The shared winner-map reducer must exist.' );

$map = lunara_build_oscars_winner_map( $lunara_test_rollup['winner_rows'] );

lunara_test_assert( 2 === count( $map ), 'Only rows with a non-empty canonical category may enter the map.' );
lunara_test_assert( isset( $map['BEST PICTURE'], $map['DIRECTING'] ), 'Each canonical category must key its own winner.' );
lunara_test_assert( 'Sean Baker' === $map['DIRECTING']['name'], 'The first row of a tied category wins; later rows must not overwrite it.' );
lunara_test_assert( ! isset( $map[''] ), 'An empty canonical category must never become a map key.' );
lunara_test_assert( ! isset( $map['   '] ), 'A whitespace-only canonical category must be trimmed away, not keyed.' );

lunara_test_assert( array() === lunara_build_oscars_winner_map( array() ), 'An empty row set must reduce to an empty map.' );
lunara_test_assert( array() === lunara_build_oscars_winner_map( null ), 'A null row set must reduce to an empty map, not a warning.' );
lunara_test_assert( array() === lunara_build_oscars_winner_map( 'not-an-array' ), 'A scalar row set must reduce to an empty map.' );
lunara_test_assert(
	array() === lunara_build_oscars_winner_map( array( 'scalar-row', 42 ) ),
	'Non-array rows must be skipped rather than indexed into.'
);

// ---------------------------------------------------------------------------
// 2. The snapshot must actually SHIP the map. This is the regression pin:
//    the map existed, and the key did not.
// ---------------------------------------------------------------------------
$snapshot = lunara_get_home_oscars_snapshot();

lunara_test_assert( is_array( $snapshot ) && ! empty( $snapshot ), 'The fixture ceremony must produce a snapshot.' );
lunara_test_assert( array_key_exists( 'winner_map', $snapshot ), 'The snapshot must expose winner_map — the Oscars portal reads that exact key.' );
lunara_test_assert( ! empty( $snapshot['winner_map'] ), 'A ceremony with winner rows must ship a populated winner_map.' );
lunara_test_assert( $map === $snapshot['winner_map'], 'The snapshot map must be the shared reducer output, not a divergent hand-built copy.' );

// The portal turns that map into cards. If this yields nothing, the section
// stays dark no matter how correct the transport is.
$cards = lunara_build_oscars_ceremony_winner_cards( $snapshot['winner_map'], Academy_Awards_Table::get_instance(), 12 );
lunara_test_assert( ! empty( $cards ), 'A populated winner_map must produce at least one winner card.' );
foreach ( $cards as $card ) {
	lunara_test_assert( is_array( $card['_visual'] ?? null ), 'Every winner card must carry an array _visual package; the portal renders from it.' );
}

// Both portal winner lanes use one renderer for their visual destination.
// A posterless card keeps its separately rendered named text destination, but
// must not manufacture an empty media anchor. Visual links carry their own
// accessible name because plugin poster markup may legitimately have alt="".
lunara_test_assert( function_exists( 'lunara_render_oscars_winner_media_link' ), 'The shared winner media-link renderer must exist.' );

$posterless_card = array(
	'primary_label'      => 'Anora',
	'canonical_category' => 'BEST PICTURE',
	'film_url'           => 'https://example.test/oscars/title/tt28607951/',
	'_visual'            => array(),
);
lunara_test_assert(
	'' === lunara_render_oscars_winner_media_link( $posterless_card, 'https://example.test/oscars/ceremony/97/' ),
	'A posterless winner must emit no empty media anchor; its named text destination remains authoritative.'
);

$poster_card             = $posterless_card;
$poster_card['_visual']  = array( 'poster_url' => 'https://img.test/anora.jpg' );
$poster_link             = lunara_render_oscars_winner_media_link( $poster_card, 'https://example.test/oscars/ceremony/97/' );
lunara_test_assert( 1 === substr_count( $poster_link, 'class="lunara-ceremony-winner-media-link"' ), 'A visual winner must emit exactly one media destination.' );
lunara_test_assert( false !== strpos( $poster_link, 'href="https://example.test/oscars/title/tt28607951/"' ), 'The winner visual must use the canonical film destination when available.' );
lunara_test_assert( false !== strpos( $poster_link, 'aria-label="View Anora Oscar winner details"' ), 'The winner media destination must expose a name derived from the canonical winner title.' );
lunara_test_assert( false !== strpos( $poster_link, 'alt="Anora poster"' ), 'A generated poster image must use the canonical winner title as its alternative text.' );

$person_card = array(
	'primary_label'      => 'Sean Baker',
	'canonical_category' => 'DIRECTING',
	'primary_url'        => 'https://example.test/oscars/name/nm0048918/',
	'_visual'            => array( 'poster_html' => '<img src="https://img.test/sean.jpg" alt="" />' ),
);
$person_link = lunara_render_oscars_winner_media_link( $person_card, 'https://example.test/oscars/ceremony/97/' );
lunara_test_assert( false !== strpos( $person_link, 'aria-label="View Sean Baker Oscar winner details"' ), 'Plugin-supplied visual markup must still receive an anchor name from canonical winner context.' );
lunara_test_assert( false !== strpos( $person_link, 'alt=""' ), 'The shared renderer must preserve plugin-owned poster markup instead of rewriting its internals.' );

// ---------------------------------------------------------------------------
// 3. Cache identity. Adding a key to the payload without moving the cache
//    version would serve shape-old snapshots to shape-new readers for the
//    life of the TTL.
// ---------------------------------------------------------------------------
$cache_keys = array();
foreach ( $lunara_test_set_calls as $call ) {
	$cache_keys[] = $call[0];
}
lunara_test_assert(
	in_array( 'lunara_home_oscars_snapshot_v7', $cache_keys, true ),
	'The snapshot must cache under v7; the payload gained winner_map and the version must move with it.'
);
lunara_test_assert(
	! in_array( 'lunara_home_oscars_snapshot_v6', $cache_keys, true ),
	'The retired v6 key must no longer be written.'
);

// The cached round trip must preserve the key, not just the first build.
$second = lunara_get_home_oscars_snapshot();
lunara_test_assert( ! empty( $second['winner_map'] ), 'A cache hit must return winner_map too, or the section renders only on cold loads.' );
lunara_test_assert( 1 === count( $lunara_test_set_calls ), 'The second read must be served from cache, not rebuilt.' );

// The import flush must clear the key the reader actually uses, plus the
// retired one still sitting in storage on an upgraded site.
$GLOBALS['lunara_test_transients']['lunara_home_oscars_snapshot_v7'] = array( 'stale' => true );
$GLOBALS['lunara_test_transients']['lunara_home_oscars_snapshot_v6'] = array( 'stale' => true );
lunara_flush_oscars_home_transients();
lunara_test_assert( false === get_transient( 'lunara_home_oscars_snapshot_v7' ), 'The import flush must clear the live snapshot key.' );
lunara_test_assert( false === get_transient( 'lunara_home_oscars_snapshot_v6' ), 'The import flush must also clear the retired key an upgraded site still holds.' );

// ---------------------------------------------------------------------------
// 4. Portal source pins. The template must not go dark again on a snapshot
//    that predates v7 — an object cache can outlive a deploy.
// ---------------------------------------------------------------------------
$portal = file_get_contents( dirname( __DIR__ ) . '/page-oscars.php' );
lunara_test_assert( false !== $portal, 'page-oscars.php must be readable.' );
lunara_test_assert(
	1 === preg_match( '/\$winner_map\s*=\s*\(array\)\s*\(\s*\$snapshot\[\s*\'winner_map\'\s*\]\s*\?\?\s*array\(\)\s*\)/', $portal ),
	'The portal must read the snapshot winner_map key.'
);
lunara_test_assert(
	1 === preg_match( '/if\s*\(\s*empty\(\s*\$winner_map\s*\)\s*&&\s*function_exists\(\s*\'lunara_build_oscars_winner_map\'\s*\)\s*\)/', $portal ),
	'The portal must rebuild the map from the rollup when a pre-v7 snapshot omits it.'
);
lunara_test_assert(
	false === strpos( $portal, "\$wcard['_visual'];" ),
	'The winner card visual must be read defensively, not as a bare required key.'
);

fwrite( STDOUT, "oscars-winner-map-runtime: all assertions passed.\n" );
