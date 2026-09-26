<?php
/**
 * Reader discovery (Theme 3.2.90): tag archives gather Journal entries and
 * Reviews, Reviews and Journal entries appear in Jetpack's sitemaps, Reviews
 * are labelled plainly on shared archive cards, and a Journal lead image is
 * never shipped as decorative. Real functions are extracted from the theme.
 *
 * Run: php tests/archive-discovery-runtime.php
 */
define( 'ABSPATH', __DIR__ . '/' );

$checks = 0;
function discovery_assert( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }

class WP_Query {
	public $vars = array(); public $flags = array(); public $main = true;
	public function __construct( $flags = array(), $vars = array() ) { $this->flags = $flags; $this->vars = $vars; }
	public function is_main_query() { return $this->main; }
	public function get( $key ) { return $this->vars[ $key ] ?? ''; }
	public function set( $key, $value ) { $this->vars[ $key ] = $value; }
	public function __call( $name, $args ) {
		$flag = $this->flags[ $name ] ?? false;
		return is_array( $flag ) ? in_array( $args[0] ?? null, $flag, true ) || ( is_array( $args[0] ?? null ) && array_intersect( $args[0], $flag ) ) : (bool) $flag;
	}
}
class WP_Term { public $slug; public $name; public function __construct( $slug, $name ) { $this->slug = $slug; $this->name = $name; } }
function is_admin() { return false; }
function add_action() {}
function add_filter() {}
function __( $text ) { return $text; }
function post_type_exists( $type ) { return in_array( $type, array( 'post', 'page', 'review', 'journal' ), true ); }
function get_post_type( $id ) { return $GLOBALS['discovery_types'][ $id ] ?? 'post'; }
function get_the_terms( $id, $taxonomy ) { return $GLOBALS['discovery_terms'][ $id ][ $taxonomy ] ?? false; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['discovery_meta'][ $id ][ $key ] ?? ''; }
function get_post_thumbnail_id( $id ) { return $GLOBALS['discovery_thumbs'][ $id ] ?? 0; }
function wp_get_attachment_caption( $id ) { return $GLOBALS['discovery_captions'][ $id ] ?? ''; }
function get_the_title( $id ) { return $GLOBALS['discovery_titles'][ $id ] ?? ''; }
function wp_strip_all_tags( $text ) { return trim( strip_tags( (string) $text ) ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function lunara_apply_editorial_archive_sort_args( $vars ) { return array( 'orderby' => 'date', 'order' => 'DESC' ); }
function lunara_get_journal_kicker( $id ) { return ''; }

function discovery_extract( $file, $name ) {
	$tokens = token_get_all( file_get_contents( dirname( __DIR__ ) . '/' . $file ) );
	foreach ( $tokens as $i => $token ) {
		if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) { continue; }
		$j = $i + 1; while ( isset( $tokens[ $j ] ) && ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] ) ) { ++$j; }
		if ( ( $tokens[ $j ][1] ?? '' ) !== $name ) { continue; }
		$code = ''; $depth = 0; $opened = false;
		for ( $k = $i; $k < count( $tokens ); ++$k ) {
			$part = is_array( $tokens[ $k ] ) ? $tokens[ $k ][1] : $tokens[ $k ]; $code .= $part;
			if ( '{' === $part || ( is_array( $tokens[ $k ] ) && in_array( $tokens[ $k ][0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) { ++$depth; $opened = true; }
			elseif ( '}' === $part && 0 === --$depth && $opened ) { eval( $code ); return; }
		}
	}
	throw new RuntimeException( 'Missing function ' . $name );
}
discovery_extract( 'inc/frontend.php', 'lunara_separate_review_from_editorial_archives' );
discovery_extract( 'inc/geo.php', 'lunara_geo_sitemap_post_types' );
discovery_extract( 'inc/geo.php', 'lunara_geo_news_sitemap_post_types' );
discovery_extract( 'inc/review-rendering.php', 'lunara_get_dispatch_type_label' );
foreach ( array( 'lunara_journal_field_has_value', 'lunara_get_journal_field_value', 'lunara_get_journal_hero_alt' ) as $name ) { discovery_extract( 'inc/journal-family.php', $name ); }

// ---- Tag archives -------------------------------------------------------------------
$tag = new WP_Query( array( 'is_tag' => true ) );
lunara_separate_review_from_editorial_archives( $tag );
discovery_assert( array( 'journal', 'review', 'post' ) === $tag->get( 'post_type' ), 'A tag archive gathers Journal entries, Reviews and Posts.' );
discovery_assert( 'date' === $tag->get( 'orderby' ) && 'DESC' === $tag->get( 'order' ), 'Tag archives keep the editorial sort.' );
foreach ( array( 'is_category', 'is_author', 'is_date', 'is_home' ) as $flag ) {
	$query = new WP_Query( array( $flag => true ) );
	lunara_separate_review_from_editorial_archives( $query );
	discovery_assert( 'post' === $query->get( 'post_type' ), 'Editorial archives other than tags keep the Posts lane: ' . $flag );
}
$secondary = new WP_Query( array( 'is_tag' => true ) ); $secondary->main = false;
lunara_separate_review_from_editorial_archives( $secondary );
discovery_assert( '' === $secondary->get( 'post_type' ), 'Secondary queries are never rewritten.' );
$search = new WP_Query( array( 'is_tag' => true, 'is_search' => true ) );
lunara_separate_review_from_editorial_archives( $search );
discovery_assert( '' === $search->get( 'post_type' ), 'Search keeps its own post types.' );

// ---- Sitemaps -----------------------------------------------------------------------------------
discovery_assert( array( 'post', 'page', 'review', 'journal' ) === lunara_geo_sitemap_post_types( array( 'post', 'page' ) ), 'Jetpack sitemaps list Reviews and Journal entries.' );
discovery_assert( array( 'post', 'page', 'review', 'journal' ) === lunara_geo_sitemap_post_types( array( 'post', 'page', 'review' ) ), 'Sitemap post types are never duplicated.' );
discovery_assert( array( 'review', 'journal' ) === lunara_geo_sitemap_post_types( null ), 'A malformed filter value still yields a valid list.' );
discovery_assert( array( 'page', 'post', 'journal' ) === lunara_geo_news_sitemap_post_types( array( 'page', 'post' ) ), 'The news sitemap lists Journal entries, not Reviews.' );

// ---- Card labels ---------------------------------------------------------------------------------------
$GLOBALS['discovery_types'] = array( 10 => 'review', 11 => 'journal', 12 => 'post' );
$GLOBALS['discovery_terms'] = array( 10 => array( 'category' => array( new WP_Term( 'news', 'News' ) ) ), 11 => array( 'journal_type' => array( new WP_Term( 'trailer', 'Trailer' ) ) ), 12 => array( 'category' => array( new WP_Term( 'essays', 'Essays' ) ) ) );
discovery_assert( 'Review' === lunara_get_dispatch_type_label( 10 ), 'A Review on a shared archive is labelled Review, whatever its categories.' );
discovery_assert( 'Trailer' === lunara_get_dispatch_type_label( 11 ) && 'Essay' === lunara_get_dispatch_type_label( 12 ), 'Journal and Post labels are unchanged.' );

// ---- Journal lead-image alt -------------------------------------------------------------------------------
$GLOBALS['discovery_titles'] = array( 20 => 'Peele&#8217;s Next Film Stalls', 21 => 'Plain Title' );
$GLOBALS['discovery_thumbs'] = array( 20 => 500, 21 => 501 );
$GLOBALS['discovery_meta'] = array( 20 => array( 'journal_image_alt' => 'Foundation alt' ), 500 => array( '_wp_attachment_image_alt' => 'Attachment alt' ) );
discovery_assert( 'Foundation alt' === lunara_get_journal_hero_alt( 20 ), 'The canonical Foundation alt wins.' );
unset( $GLOBALS['discovery_meta'][20] );
discovery_assert( 'Attachment alt' === lunara_get_journal_hero_alt( 20 ), 'The attachment alt comes next.' );
unset( $GLOBALS['discovery_meta'][500] ); $GLOBALS['discovery_captions'] = array( 500 => '<em>Jordan Peele on set</em>' );
discovery_assert( 'Jordan Peele on set' === lunara_get_journal_hero_alt( 20 ), 'Then the attachment caption, without markup.' );
$GLOBALS['discovery_captions'] = array();
discovery_assert( "Lead image for Peele\u{2019}s Next Film Stalls" === lunara_get_journal_hero_alt( 20 ), 'Finally the entry it leads, with entities decoded.' );
discovery_assert( 'Lead image for Plain Title' === lunara_get_journal_hero_alt( 21 ), 'The fallback never leaves the alt empty.' );

// ---- The template uses the helper and renders older/newer navigation --------------------------------------
$single = file_get_contents( dirname( __DIR__ ) . '/single-journal.php' );
discovery_assert( false !== strpos( $single, 'lunara_get_journal_hero_alt( $post_id )' ), 'single-journal.php resolves the lead alt through the helper.' );
discovery_assert( false !== strpos( $single, "get_adjacent_post( false, '', true )" ) && false !== strpos( $single, "get_adjacent_post( false, '', false )" ) && false !== strpos( $single, 'rel="<?php echo \'older\' === $journal_direction ? \'prev\' : \'next\'; ?>"' ), 'single-journal.php links the older and newer entries with rel prev/next.' );

echo "Archive discovery runtime passed: {$checks} checks.\n";
