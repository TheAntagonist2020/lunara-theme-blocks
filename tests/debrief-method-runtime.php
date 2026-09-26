<?php
/**
 * The Debrief Method page: live index (identity merging, caps, cache version and
 * invalidation), settings-driven rendering of page-debrief.php, and the poster
 * `sizes` helper. WordPress is stubbed; the parser and title normalizer are the
 * real ones, extracted from inc/debrief.php.
 *
 * Run: php tests/debrief-method-runtime.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
define( 'HOUR_IN_SECONDS', 3600 );

$checks = 0;
function dm_assert( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }

class WP_Post {
	public $ID; public $post_type; public $post_status; public $post_password = ''; public $post_title;
	public function __construct( $id, $type, $status, $title = '' ) { $this->ID = $id; $this->post_type = $type; $this->post_status = $status; $this->post_title = $title; }
}

// ---- WordPress seams -------------------------------------------------------
$GLOBALS['dm'] = array();
function dm_reset() {
	$GLOBALS['dm'] = array( 'posts' => array(), 'meta' => array(), 'mods' => array(), 'transients' => array(), 'transient_sets' => 0, 'deleted' => array(), 'actions' => $GLOBALS['dm']['actions'] ?? array(), 'queries' => 0 );
}
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['dm']['actions'][ $hook ][] = array( $callback, $args ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {}
function apply_filters( $hook, $value ) { return $value; }
function do_action_ref( $hook, ...$args ) { foreach ( $GLOBALS['dm']['actions'][ $hook ] ?? array() as $entry ) { call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) ); } }
function __( $text ) { return $text; }
function _n( $single, $plural, $count ) { return 1 === (int) $count ? $single : $plural; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $url ) { return esc_html( $url ); }
function esc_html__( $text ) { return esc_html( $text ); }
function esc_html_e( $text ) { echo esc_html( $text ); }
function esc_attr_e( $text ) { echo esc_attr( $text ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( (string) $path, '/' ); }
function remove_accents( $text ) { return strtr( (string) $text, array( 'é' => 'e', 'è' => 'e', 'á' => 'a', 'ö' => 'o' ) ); }
function wp_strip_all_tags( $text ) { return trim( strip_tags( (string) $text ) ); }
function sanitize_text_field( $text ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) ); }
function sanitize_textarea_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function wp_list_pluck( $list, $field ) { return array_map( static function ( $row ) use ( $field ) { return $row[ $field ]; }, (array) $list ); }
function get_theme_mod( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['dm']['mods'] ) ? $GLOBALS['dm']['mods'][ $key ] : $default; }
function get_transient( $key ) { return $GLOBALS['dm']['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { ++$GLOBALS['dm']['transient_sets']; $GLOBALS['dm']['transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { $GLOBALS['dm']['deleted'][] = $key; unset( $GLOBALS['dm']['transients'][ $key ] ); return true; }
function get_post( $id ) { return $GLOBALS['dm']['posts'][ (int) $id ] ?? null; }
function get_post_type( $id ) { $post = get_post( $id ); return $post ? $post->post_type : false; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['dm']['meta'][ (int) $id ][ $key ] ?? ''; }
function update_meta_cache( $type, $ids ) { return true; }
function get_the_title( $post = 0 ) { $post = is_object( $post ) ? $post : ( $post ? get_post( $post ) : ( $GLOBALS["post"] ?? null ) ); return $post ? $post->post_title : ''; }
function get_permalink( $post ) { $post = is_object( $post ) ? $post : get_post( $post ); return $post ? 'https://example.test/' . $post->post_type . '/' . $post->ID . '/' : ''; }
function get_posts( $args ) {
	++$GLOBALS['dm']['queries'];
	$ids = array();
	foreach ( $GLOBALS['dm']['posts'] as $post ) { if ( $args['post_type'] === $post->post_type && 'publish' === $post->post_status && '' === $post->post_password ) { $ids[] = $post->ID; } }
	rsort( $ids ); // Fixture IDs increase with date: DESC by ID is DESC by date.
	return $ids;
}
function is_page( $slug = '' ) { return ! empty( $GLOBALS['dm']['is_debrief'] ); }
function is_page_template( $template = '' ) { return false; }
function lunara_imdb_title_map() { return array( 'barbarian|2022' => 'tt15791034' ); }
function lunara_get_internal_title_reference_url( $tt, $post_id = 0 ) { return ''; }
function lunara_get_oscar_ledger_counts( $tt ) { return array( 'noms' => 0, 'wins' => 0 ); }

// ---- Real code under test ---------------------------------------------------
function dm_extract( $file, $name ) {
	$tokens = token_get_all( file_get_contents( $file ) );
	foreach ( $tokens as $i => $token ) {
		if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) { continue; }
		$j = $i + 1; while ( isset( $tokens[ $j ] ) && ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] ) ) { ++$j; }
		if ( ( $tokens[ $j ][1] ?? '' ) !== $name ) { continue; }
		$code = ''; $depth = 0; $opened = false;
		for ( $k = $i; $k < count( $tokens ); ++$k ) {
			$part = is_array( $tokens[ $k ] ) ? $tokens[ $k ][1] : $tokens[ $k ]; $code .= $part;
			if ( '{' === $part || ( is_array( $tokens[ $k ] ) && in_array( $tokens[ $k ][0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) { ++$depth; $opened = true; }
			elseif ( '}' === $part && 0 === --$depth && $opened ) { return $code; }
		}
	}
	throw new RuntimeException( 'Missing function ' . $name );
}
$debrief_source = dirname( __DIR__ ) . '/inc/debrief.php';
foreach ( array( 'lunara_normalize_title_key', 'lunara_parse_pair_it_with_value', 'lunara_poster_html_with_sizes' ) as $name ) { eval( dm_extract( $debrief_source, $name ) ); }
function lunara_get_career_context_meta( $post_id ) { $value = get_post_meta( $post_id, '_lunara_career_context', true ); return '' !== trim( (string) $value ) ? $value : get_post_meta( $post_id, '_lunara_craft_mirror', true ); }
dm_reset();
require dirname( __DIR__ ) . '/inc/debrief-method.php';

// ---- Fixtures --------------------------------------------------------------
function dm_review( $id, $title, $meta = array(), $status = 'publish' ) { $GLOBALS['dm']['posts'][ $id ] = new WP_Post( $id, 'review', $status, $title ); $GLOBALS['dm']['meta'][ $id ] = $meta; }
function dm_movie( $id, $title, $tt, $year, $status = 'publish' ) { $GLOBALS['dm']['posts'][ $id ] = new WP_Post( $id, 'movie', $status, $title ); $GLOBALS['dm']['meta'][ $id ] = array( 'imdb_title_id' => $tt, 'release_year' => $year ); }
function dm_catalogue() {
	dm_reset();
	dm_movie( 501, '2001: A Space Odyssey', 'tt0062622', '1968' );
	dm_movie( 502, 'Hereditary', 'tt7784604', '2018', 'draft' );
	dm_review( 110, 'Resident Evil — The Worst Night of His Life', array( 'theme_echo_movie' => 501, '_lunara_counter_program' => 'Pontypool (2008) — All sound, no rollercoaster.', '_lunara_career_context' => 'Barbarian (2022) — The rug-pull debut.' ) );
	dm_review( 109, 'The Dog Stars &#8212; Going Nowhere Beautifully', array( '_lunara_theme_echo' => '2001: A Space Odyssey (Kubrick, 1968) — The ur-text of cosmic loneliness.' ) );
	dm_review( 108, 'Tony — The Picture Stays With the Kid', array( '_lunara_craft_mirror' => 'Hereditary (2018) — Grief as a haunted house.' ) );
	dm_review( 107, 'No Debrief Yet', array() );
	dm_review( 106, 'Draft Review', array( '_lunara_theme_echo' => 'Jaws (1975) — Never counted.' ), 'draft' );
	dm_review( 105, 'The Invite — Nice Sweaters', array( 'counter_program_movie' => 502, '_lunara_counter_program' => 'Hereditary (2018) — Grief again.', '_lunara_career_context' => '2001: A Space Odyssey | tt0062622 — Same film, by ID.' ) );
}

// ---- Title/year normalization -------------------------------------------------
dm_assert( array( '2001: A Space Odyssey', '1968' ) === lunara_debrief_method_split_title_year( '2001: A Space Odyssey (Kubrick, 1968)', '' ), 'A director-and-year parenthetical becomes the year.' );
dm_assert( array( 'Jaws', '1975' ) === lunara_debrief_method_split_title_year( 'Jaws (1975)', '' ), 'A bare year parenthetical becomes the year.' );
dm_assert( array( 'Jaws (Spielberg)', '' ) === lunara_debrief_method_split_title_year( 'Jaws (Spielberg)', '' ), 'A parenthetical without a year is left alone.' );
dm_assert( array( '1917', '' ) === lunara_debrief_method_split_title_year( '1917', '' ), 'A numeric title is never mistaken for a year.' );
dm_assert( array( 'Title (Kubrick, 1968)', '1970' ) === lunara_debrief_method_split_title_year( 'Title (Kubrick, 1968)', '1970' ), 'An explicit year wins; the title is untouched.' );

// ---- Index ----------------------------------------------------------------------
dm_catalogue();
$index = lunara_debrief_method_build_index();
dm_assert( 2 === $index['version'] && LUNARA_DEBRIEF_INDEX_VERSION === $index['version'], 'The payload records its version.' );
dm_assert( 5 === $index['reviews_total'], 'Only published reviews are walked.' );
dm_assert( 4 === $index['reviews_debrief'], 'Reviews without pairings are not counted as debriefed.' );
dm_assert( 1 === $index['reviews_trio'], 'Only full-trio reviews count as trios.' );
dm_assert( 7 === $index['pairings_total'], 'Every resolved pairing is counted.' );
dm_assert( 4 === $index['unique_films'], 'Linked, text and ID-only entries of one film are one title (2001, Pontypool, Barbarian, Hereditary).' );
$top = $index['films'][0];
dm_assert( '2001: A Space Odyssey' === $top['title'] && '1968' === $top['year'] && 'tt0062622' === $top['tt'] && 3 === $top['count'], 'The canon leader merges the linked movie, the "(Kubrick, 1968)" text and the ID-only entry.' );
dm_assert( array( 'theme' => 2, 'counter' => 0, 'career' => 1 ) === $top['roles'], 'Role counts follow the merged film.' );
dm_assert( array( 110, 109, 105 ) === $top['reviews'], 'A film lists the reviews that prescribed it, newest first.' );
dm_assert( 'https://example.test/movie/501/' === $top['href'], 'The linked movie supplies the canonical link.' );
$hereditary = null; foreach ( $index['films'] as $film ) { if ( 'Hereditary' === $film['title'] ) { $hereditary = $film; } }
dm_assert( $hereditary && 2 === $hereditary['count'] && array( 'theme' => 0, 'counter' => 1, 'career' => 1 ) === $hereditary['roles'], 'A draft movie link falls back to the text field and still merges with the Craft Mirror entry.' );
dm_assert( array( 110, 109, 108, 105 ) === wp_list_pluck( $index['recent'], 'review_id' ), 'Recent Debriefs are newest first and skip reviews without pairings.' );
dm_assert( array( 'theme', 'counter', 'career' ) === wp_list_pluck( $index['recent'][0]['pairs'], 'role' ), 'Pairs keep canonical role order.' );

dm_reset();
for ( $i = 1; $i <= 30; $i++ ) { dm_review( $i, 'Review ' . $i, array( '_lunara_theme_echo' => 'Film ' . $i . ' (2001) — Note.' ) ); }
$big = lunara_debrief_method_build_index();
dm_assert( LUNARA_DEBRIEF_INDEX_MAX_RECENT === count( $big['recent'] ) && 30 === $big['recent'][0]['review_id'], 'Recent is capped at the stored maximum, newest first.' );
dm_assert( LUNARA_DEBRIEF_INDEX_MAX_FILMS === count( $big['films'] ) && 30 === $big['unique_films'], 'The films list is capped; the distinct count is not.' );

// ---- Cache: version, retired keys, invalidation ---------------------------------
dm_catalogue();
$GLOBALS['dm']['transients']['lunara_debrief_index_v2'] = array( 'films' => array(), 'recent' => array() ); // v1-shaped payload under the new key.
$fresh = lunara_debrief_method_index();
dm_assert( 2 === $fresh['version'] && 1 === $GLOBALS['dm']['transient_sets'] && 1 === $GLOBALS['dm']['queries'], 'A payload without the current shape is rebuilt, never served.' );
$again = lunara_debrief_method_index();
dm_assert( $fresh === $again && 1 === $GLOBALS['dm']['queries'], 'A current payload is served from cache.' );
$GLOBALS['dm']['transients']['lunara_debrief_index_v1'] = array( 'films' => array(), 'recent' => array() );
lunara_debrief_method_flush_index( 110 );
dm_assert( ! isset( $GLOBALS['dm']['transients']['lunara_debrief_index_v2'] ) && ! isset( $GLOBALS['dm']['transients']['lunara_debrief_index_v1'] ), 'A review change clears the current and retired keys.' );
lunara_debrief_method_index(); $GLOBALS['dm']['deleted'] = array();
$GLOBALS['dm']['posts'][900] = new WP_Post( 900, 'journal', 'publish', 'Journal' );
lunara_debrief_method_flush_index( 900 );
dm_assert( array() === $GLOBALS['dm']['deleted'], 'Unrelated post types never flush the index.' );
lunara_debrief_method_flush_index( 501 );
dm_assert( in_array( 'lunara_debrief_index_v2', $GLOBALS['dm']['deleted'], true ), 'A linked movie change flushes the index.' );
$GLOBALS['dm']['deleted'] = array();
lunara_debrief_method_flush_index( 999999, new WP_Post( 999999, 'review', 'trash' ) );
dm_assert( in_array( 'lunara_debrief_index_v2', $GLOBALS['dm']['deleted'], true ), 'A permanently deleted review flushes via the hook\'s post object.' );
$GLOBALS['dm']['deleted'] = array();
lunara_debrief_method_flush_on_meta( 1, 110, '_edit_lock' );
lunara_debrief_method_flush_on_meta( 1, 900, '_lunara_theme_echo' );
dm_assert( array() === $GLOBALS['dm']['deleted'], 'Unrelated meta, and pairing-named meta on other types, never flush.' );
foreach ( array( '_lunara_theme_echo', '_lunara_counter_program', '_lunara_career_context', '_lunara_craft_mirror', 'theme_echo_movie', 'counter_program_movie', 'career_context_movie' ) as $key ) {
	$GLOBALS['dm']['deleted'] = array();
	lunara_debrief_method_flush_on_meta( 1, 110, $key );
	dm_assert( in_array( 'lunara_debrief_index_v2', $GLOBALS['dm']['deleted'], true ), 'Pairing meta flushes the index: ' . $key );
}
foreach ( array( 'save_post_review', 'save_post_movie', 'deleted_post', 'trashed_post', 'untrashed_post', 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) { dm_assert( ! empty( $GLOBALS['dm']['actions'][ $hook ] ), 'Invalidation is wired to ' . $hook ); }

// ---- Specimen and canon ------------------------------------------------------------
dm_catalogue();
$index = lunara_debrief_method_build_index();
dm_assert( 110 === lunara_debrief_method_specimen_id( $index, 0 ), 'Automatic specimen is the newest full trio.' );
dm_assert( 108 === lunara_debrief_method_specimen_id( $index, 108 ), 'A pinned review with a Debrief is honoured.' );
dm_assert( 110 === lunara_debrief_method_specimen_id( $index, 106 ), 'A pinned draft falls back to automatic.' );
dm_assert( 110 === lunara_debrief_method_specimen_id( $index, 107 ), 'A pinned review without pairings falls back to automatic.' );
dm_assert( 110 === lunara_debrief_method_specimen_id( $index, 501 ), 'A pinned non-review falls back to automatic.' );
dm_assert( 0 === lunara_debrief_method_specimen_id( array( 'recent' => array() ), 0 ), 'No Debriefs, no specimen.' );
dm_assert( array( '2001: A Space Odyssey', 'Hereditary' ) === wp_list_pluck( lunara_debrief_method_canon( $index, 8, 2 ), 'title' ), 'The canon keeps films prescribed at least twice.' );
dm_assert( array( '2001: A Space Odyssey' ) === wp_list_pluck( lunara_debrief_method_canon( $index, 8, 3 ), 'title' ), 'The minimum-prescriptions setting narrows the canon.' );
dm_assert( array( '2001: A Space Odyssey' ) === wp_list_pluck( lunara_debrief_method_canon( $index, 1, 2 ), 'title' ), 'The count setting caps the canon.' );
dm_assert( 2 === count( lunara_debrief_method_canon( $index, 8, 1 ) ), 'The canon never admits single prescriptions, whatever is stored.' );
dm_assert( 'Tony' === lunara_debrief_method_review_label( 108 ) && 'The Dog Stars' === lunara_debrief_method_review_label( 109 ) && 'No Debrief Yet' === lunara_debrief_method_review_label( 107 ), 'Review labels keep the film, including after a texturized dash.' );

// ---- Poster sizes helper -----------------------------------------------------------------
$attachment = '<img width="1333" height="2000" src="a.jpg" class="x" alt="A" loading="lazy" srcset="a-300.jpg 300w, a.jpg 1333w" sizes="auto, (max-width: 1333px) 100vw, 1333px" />';
dm_assert( false !== strpos( lunara_poster_html_with_sizes( $attachment, '(min-width: 1000px) 240px, 76px', 'lazy' ), 'sizes="auto, (min-width: 1000px) 240px, 76px"' ), 'Lazy attachment posters get auto plus the real rendered width.' );
dm_assert( false !== strpos( lunara_poster_html_with_sizes( $attachment, '160px', 'eager' ), 'sizes="160px"' ), 'Eager posters get the plain hint.' );
dm_assert( 1 === substr_count( lunara_poster_html_with_sizes( $attachment, '160px', 'lazy' ), 'sizes=' ), 'The hint replaces rather than duplicates sizes.' );
$missing = '<img src="a.jpg" srcset="a-300.jpg 300w, a.jpg 1333w" alt="A">';
dm_assert( false !== strpos( lunara_poster_html_with_sizes( $missing, '160px', 'lazy' ), '<img sizes="auto, 160px"' ), 'A srcset without sizes gains one.' );
$plain = '<img src="https://image.tmdb.org/t/p/w500/x.jpg" alt="A">';
dm_assert( $plain === lunara_poster_html_with_sizes( $plain, '160px', 'lazy' ) && $attachment === lunara_poster_html_with_sizes( $attachment, '', 'lazy' ), 'Markup without a srcset, or without a hint, is untouched.' );

// ---- Template rendering -----------------------------------------------------------------
function get_header() { echo '<main>'; }
function get_footer() { echo '</main>'; }
function the_post() {}
function get_the_ID() { return 33090; }
function get_the_content() { return $GLOBALS['dm']['page_content'] ?? ''; }
function has_post_thumbnail( $id ) { return false; }
function _prime_post_caches( $ids, $terms = true, $meta = true ) { $GLOBALS['dm']['primed'] = $ids; }
function lunara_render_pair_it_with_cards( $review_id ) { return '<section class="lunara-pair-cards" data-review="' . (int) $review_id . '"></section>'; }
function lunara_get_title_poster_html( $tt, $size, $class, $title, $loading, $sizes = '' ) { return '<img class="' . esc_attr( $class ) . '" src="https://example.test/' . esc_attr( $tt ) . '.jpg" srcset="x 300w" sizes="' . esc_attr( $sizes ) . '" alt="' . esc_attr( $title ) . ' poster">'; }
function dm_render() {
	$GLOBALS['dm']['posts'][33090] = new WP_Post( 33090, 'page', 'publish', 'Lunara Debrief' );
	$GLOBALS['post'] = $GLOBALS['dm']['posts'][33090];
	ob_start(); include dirname( __DIR__ ) . '/page-debrief.php'; return ob_get_clean();
}
dm_catalogue();
$html = dm_render();
preg_match_all( '/data-lunara-site-studio-section="([a-z-]+)"/', $html, $markers );
dm_assert( array( 'hero', 'moves', 'why', 'specimen', 'canon', 'recent', 'next' ) === $markers[1], 'Default render shows every section with content; the desk seat waits for page content.' );
dm_assert( false !== strpos( $html, '<h1 class="lunara-debrief-title">Lunara Debrief</h1>' ), 'An empty heading setting falls back to the page title.' );
dm_assert( false !== strpos( $html, 'data-review="110"' ), 'The specimen is the newest full trio.' );
dm_assert( 2 === substr_count( $html, '<li class="lunara-debrief-canon-item">' ), 'Canon lists both repeat films.' );
dm_assert( false !== strpos( $html, 'sizes="(min-width: 1000px) 240px, 76px"' ), 'Canon posters carry their rendered-width hint.' );
dm_assert( false !== strpos( $html, '<span class="lunara-debrief-canon-year">(1968)</span>' ) && false === strpos( $html, '(Kubrick, 1968)' ), 'The canon shows a clean title and year.' );
dm_assert( 1 === preg_match( '/In the Debriefs for<\/span>\s*<a href="https:\/\/example.test\/review\/110\/">Resident Evil<\/a>, <a href="https:\/\/example.test\/review\/109\/">The Dog Stars<\/a>, <a href="https:\/\/example.test\/review\/105\/">The Invite<\/a>/', $html ), 'Each canon film links the reviews that prescribed it.' );
dm_assert( 4 === substr_count( $html, '<li class="lunara-debrief-recent-item">' ), 'Recent Debriefs list every debriefed review up to the count.' );
dm_assert( ! empty( $GLOBALS['dm']['primed'] ) && in_array( 105, $GLOBALS['dm']['primed'], true ), 'Linked reviews are primed in one query.' );
dm_assert( 1 === preg_match( '/<div class="lunara-debrief-why-body">(.*?)<\/div>/s', $html, $why ) && 2 === substr_count( $why[1], '<p>' ), 'Why Three renders its two default paragraphs.' );

$GLOBALS['dm']['mods'] = array(
	'lunara_debrief_hero_title'         => 'The Debrief, Explained',
	'lunara_debrief_hero_kicker'        => '',
	'lunara_debrief_show_stats'         => false,
	'lunara_debrief_show_why'           => false,
	'lunara_debrief_show_canon'         => false,
	'lunara_debrief_recent_count'       => 2,
	'lunara_debrief_show_next'          => false,
	'lunara_debrief_counter_not'        => '',
	'lunara_debrief_theme_question'     => 'What wound does it share? <script>alert(1)</script>',
	'lunara_debrief_specimen_review_id' => 108,
	'lunara_debrief_desk_kicker'        => 'Dalton Writes',
);
$GLOBALS['dm']['page_content'] = '<p>The manifesto.</p>';
$html = dm_render();
preg_match_all( '/data-lunara-site-studio-section="([a-z-]+)"/', $html, $markers );
dm_assert( array( 'hero', 'moves', 'specimen', 'desk', 'recent' ) === $markers[1], 'Hidden sections disappear; the desk seat appears with page content.' );
dm_assert( false !== strpos( $html, '>The Debrief, Explained</h1>' ) && false === strpos( $html, 'lunara-debrief-stats' ) && false === strpos( $html, 'A Lunara Film Signature' ), 'Heading, stats and an emptied kicker follow the settings.' );
dm_assert( false === strpos( $html, '<script>' ) && false !== strpos( $html, 'What wound does it share? alert(1)' ), 'Saved text is sanitized and escaped.' );
dm_assert( 2 === substr_count( $html, 'lunara-debrief-move-not' ), 'An emptied "what it is not" line is omitted for that move only.' );
dm_assert( false !== strpos( $html, 'data-review="108"' ), 'A pinned specimen is honoured.' );
dm_assert( false !== strpos( $html, '>Dalton Writes</p>' ) && false !== strpos( $html, '<p>The manifesto.</p>' ), 'The desk seat uses its kicker and the page content.' );
dm_assert( 2 === substr_count( $html, '<li class="lunara-debrief-recent-item">' ), 'The recent count setting caps the list.' );

echo "Debrief Method runtime passed: {$checks} checks.\n";
