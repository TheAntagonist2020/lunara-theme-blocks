<?php
/**
 * Theme 3.2.91: film year labels are never flattened (plan §8.1 items 6-8).
 *
 * Run: php tests/film-year-label-runtime.php
 *
 * A film's year is the Academy's own label for its first ceremony, verbatim.
 * The 6th ceremony covered 1932/33, and the theme used to print it as
 * `(int) '1932/33'` = 1932 in the /film/ and /talent/ award history, publish
 * it as JSON-LD datePublished "1932", and keep only "1932" in the Debrief
 * resolver. From 3.2.91:
 *   - the award history prints the label verbatim when it is YYYY or
 *     YYYY/YY, else an em dash;
 *   - the movie JSON-LD emits datePublished only for a plain four-digit
 *     year, as that string (a split label is not an ISO date);
 *   - Lunara_Debrief_Film_Resolver::normalize_year() keeps YYYY/YY verbatim
 *     and otherwise keeps its first four-digit year, as before.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

// ---------------------------------------------------------------------------
// Minimal WordPress surface.
// ---------------------------------------------------------------------------
$GLOBALS['lunara_test_meta']     = array();
$GLOBALS['lunara_test_singular'] = 'movie';
$GLOBALS['lunara_test_awards']   = array();

function add_action() {}
function add_filter() {}
function __( $text ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $url ) { return (string) $url; }
function esc_html__( $text ) { return esc_html( $text ); }
function esc_html_e( $text ) { echo esc_html( $text ); }
function esc_attr_e( $text ) { echo esc_attr( $text ); }
function _n( $single, $plural, $number ) { return 1 === (int) $number ? $single : $plural; }
function number_format_i18n( $number ) { return (string) $number; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function get_permalink( $post = 0 ) { return 'https://example.test/?p=' . intval( $post ); }
function get_the_title( $post = 0 ) { return 'Cavalcade'; }
function get_post_status( $post = 0 ) { return 'publish'; }
function get_the_ID() { return 77; }
function is_singular( $type = '' ) { return $type === $GLOBALS['lunara_test_singular']; }
function get_post_meta( $post_id, $key = '', $single = false ) {
	return $GLOBALS['lunara_test_meta'][ $key ] ?? '';
}
function has_post_thumbnail( $post = null ) { return false; }
function maybe_unserialize( $value ) { return $value; }
function get_post_type_archive_link( $type ) { return 'https://example.test/film/'; }
function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }

class wpdb {
	public $posts    = 'wp_posts';
	public $postmeta = 'wp_postmeta';
	public function prepare( $query, ...$args ) { return $query; }
	public function get_results( $sql, $output = null ) { return $GLOBALS['lunara_test_awards']; }
}
$GLOBALS['wpdb'] = new wpdb();

require dirname( __DIR__ ) . '/inc/entity-surfaces.php';
require dirname( __DIR__ ) . '/inc/debrief-resolver.php';

// ---------------------------------------------------------------------------
// 7. Award history (inc/entity-surfaces.php:235, :244).
// ---------------------------------------------------------------------------
$award_rows = array(
	array( 'category' => 'OUTSTANDING PRODUCTION', 'ceremony' => 6, 'year' => '1932/33', 'won' => '1' ),
	array( 'category' => 'BEST PICTURE', 'ceremony' => 97, 'year' => '2025', 'won' => '0' ),
	array( 'category' => 'DIRECTING', 'ceremony' => 5, 'year' => '1932', 'won' => '0' ),
	array( 'category' => 'ART DIRECTION', 'ceremony' => 0, 'year' => '', 'won' => '0' ),
	array( 'category' => 'WRITING', 'ceremony' => 6, 'year' => 1932, 'won' => '0' ),
	array( 'category' => 'SOUND', 'ceremony' => 0, 'year' => '0', 'won' => '0' ),
	array( 'category' => 'EDITING', 'ceremony' => 0, 'year' => 'unknown', 'won' => '0' ),
	array( 'category' => 'MUSIC', 'ceremony' => 6, 'year' => ' 1932/33 ', 'won' => '0' ),
);
$html = lunara_entity_render_award_history( $award_rows, 'movie' );
preg_match_all( '#<span class="lunara-entity-award-year">(.*?)</span>#s', $html, $year_cells );
lunara_test_assert(
	array( '1932/33', '2025', '1932', '&mdash;', '1932', '&mdash;', '&mdash;', '1932/33' ) === $year_cells[1],
	'Award history year cells must print the label verbatim (YYYY or YYYY/YY) or an em dash; got ' . json_encode( $year_cells[1] )
);
lunara_test_assert( false === strpos( $html, '>1932<' . '/span><span class="lunara-entity-award-cat">OUTSTANDING PRODUCTION' ), 'The 1932/33 row must not be flattened to 1932.' );
lunara_test_assert( '' === lunara_entity_render_award_history( array(), 'movie' ), 'No rows render nothing, as before.' );

// ---------------------------------------------------------------------------
// 8. Movie JSON-LD datePublished (inc/entity-surfaces.php:444-446).
// ---------------------------------------------------------------------------
$movie_graph = static function ( $release_year ) {
	$GLOBALS['lunara_test_meta'] = array( 'release_year' => $release_year, '_lunara_entity_id' => 'tt0023876' );
	ob_start();
	lunara_entity_output_schema();
	$out = (string) ob_get_clean();
	if ( ! preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $out, $m ) ) {
		lunara_test_assert( false, 'The movie JSON-LD script must be emitted.' );
	}
	$graph = json_decode( $m[1], true );
	lunara_test_assert( is_array( $graph ) && isset( $graph['@graph'][0]['@type'] ) && 'Movie' === $graph['@graph'][0]['@type'], 'The JSON-LD must decode to a Movie node.' );
	return $graph['@graph'][0];
};

$GLOBALS['lunara_test_awards'] = array(
	array( 'entry_id' => 1, 'category' => 'OUTSTANDING PRODUCTION', 'ceremony' => '6', 'year' => '1932/33', 'won' => '1', 'person_id' => '' ),
);

$movie = $movie_graph( '2025' );
lunara_test_assert( array_key_exists( 'datePublished', $movie ) && '2025' === $movie['datePublished'], 'release_year 2025 must emit datePublished "2025" (a string).' );
$movie = $movie_graph( '1932' );
lunara_test_assert( '1932' === ( $movie['datePublished'] ?? null ), 'release_year 1932 must still emit datePublished "1932" (the stored value until R2).' );
$movie = $movie_graph( ' 1999 ' );
lunara_test_assert( '1999' === ( $movie['datePublished'] ?? null ), 'A padded four-digit year is trimmed.' );
$movie = $movie_graph( '1932/33' );
lunara_test_assert( ! array_key_exists( 'datePublished', $movie ), 'release_year 1932/33 must emit no datePublished.' );
lunara_test_assert( is_array( $movie['award'] ?? null ) && false !== strpos( (string) $movie['award'][0], '1932/33' ), 'The award strings keep printing the row label verbatim.' );
$movie = $movie_graph( '' );
lunara_test_assert( ! array_key_exists( 'datePublished', $movie ), 'An empty release_year emits no datePublished.' );

// ---------------------------------------------------------------------------
// 9. Debrief resolver normalize_year (inc/debrief-resolver.php:271-273).
// ---------------------------------------------------------------------------
$normalize_year = new ReflectionMethod( 'Lunara_Debrief_Film_Resolver', 'normalize_year' );
if ( PHP_VERSION_ID < 80100 ) {
	$normalize_year->setAccessible( true );
}
$cases = array(
	'1932/33'        => '1932/33',
	' 1932/33 '      => '1932/33',
	'2020'           => '2020',
	'1932'           => '1932',
	'Released 1999.' => '1999',
	'1932/1933'      => '1932',
	''               => '',
	'n/a'            => '',
);
foreach ( $cases as $input => $expected ) {
	$actual = $normalize_year->invoke( null, (string) $input );
	lunara_test_assert( $expected === $actual, 'normalize_year(' . json_encode( (string) $input ) . ') must be ' . json_encode( $expected ) . ', got ' . json_encode( $actual ) );
}
lunara_test_assert( '2021' === $normalize_year->invoke( null, 2021 ), 'An integer year normalizes to its string.' );

fwrite( STDOUT, "Film year label runtime passed: award history, movie JSON-LD datePublished and the Debrief year keep 1932/33 verbatim.\n" );
exit( 0 );
