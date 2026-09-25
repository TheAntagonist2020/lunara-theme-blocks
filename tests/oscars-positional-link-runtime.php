<?php
/**
 * Theme 3.2.91: positional guards and the legacy link guard (plan §8.1
 * items 1-5, §5.3 theme rows, §5.6).
 *
 * Run: php tests/oscars-positional-link-runtime.php
 *
 * Until the corrected ledger is live, the plugin's master rows still carry
 * the legacy importer's pairing. Its normalizer drops '?' slots and splits
 * comma-joined ones, so a row's names and IDs can differ in count, and
 * pairing them by index then links a neighbour's ID. Row 965 (SPECIAL
 * AWARD, ceremony 12) is the live example: five names, four IDs once the
 * leading '?' is dropped, so index pairing gives Jean Hersholt Ralph
 * Morgan's nm0604960. The theme's four positional consumers must pair only
 * aligned lists, fall back to a lone ID only for a lone name, and consult
 * the plugin's guard (lunara_oscars_pair_is_guarded()) for every pair they
 * keep, so a known-wrong legacy pair or a never-link ID never becomes a
 * link. The search's title matches build /oscars/title/{film_id}/ from the
 * row itself and must skip a never-link title.
 *
 * Rows are taken from data/oscars.csv (1-based source_row) in the form the
 * plugin stores them (after normalize_imdb_entity_ids()).
 */

require __DIR__ . '/fixtures/oscars-ledger-reader-stub.php';

$root = dirname( __DIR__ );

require $root . '/inc/oscars-family.php';
require $root . '/inc/oscars-data.php';
require $root . '/inc/oscars-portal.php';

// functions.php cannot be loaded whole; lunara_oscar_nominee_id_for_label()
// is its only definition, so the extracted body is the live code.
lunara_test_load_functions(
	$root . '/functions.php',
	array( 'lunara_oscar_match_text', 'lunara_oscar_text_matches', 'lunara_oscar_first_id_from_list', 'lunara_oscar_nominee_id_for_label' )
);
lunara_test_assert( 1 === preg_match_all( '/function\s+lunara_oscar_nominee_id_for_label\s*\(/', file_get_contents( $root . '/functions.php' ) ), 'functions.php must hold exactly one lunara_oscar_nominee_id_for_label().' );
foreach ( glob( $root . '/inc/*.php' ) as $inc_file ) {
	lunara_test_assert( ! preg_match( '/function\s+lunara_oscar_nominee_id_for_label\s*\(/', file_get_contents( $inc_file ) ), 'No inc/ copy of lunara_oscar_nominee_id_for_label() may shadow the functions.php definition: ' . basename( $inc_file ) );
}

$rows = array(
	// SPECIAL AWARD, ceremony 12: NomineeIds '?|nm0380965|nm0604960|nm0088759|nm0619261'.
	965  => array(
		'name'        => '',
		'nominees'    => 'The Motion Picture Relief Fund|Jean Hersholt|Ralph Morgan|Ralph Block|Conrad Nagel',
		'nominee_ids' => 'nm0380965|nm0604960|nm0088759|nm0619261',
	),
	// SCIENTIFIC OR TECHNICAL AWARD (Class III), ceremony 6: '?|nm0413164|?|?'.
	276  => array(
		'name'        => '',
		'nominees'    => 'Fox Film Corporation|FRED JACKMAN|WARNER BROS. PICTURES INC.|SIDNEY SANDERS',
		'nominee_ids' => 'nm0413164',
	),
	// FILM EDITING, ceremony 69, Fargo: 'nm0001053,nm0001054' (Roderick Jaynes).
	8165 => array(
		'name'        => 'Roderick Jaynes',
		'nominees'    => 'Roderick Jaynes',
		'nominee_ids' => 'nm0001053|nm0001054',
	),
	// SOUND RECORDING, ceremony 9, Dodsworth: aligned 'co0026841|nm0481264'.
	526  => array(
		'name'        => 'United Artists Studio Sound Department, Thomas T. Moulton, Sound Director',
		'nominees'    => 'United Artists|Thomas T. Moulton',
		'nominee_ids' => 'co0026841|nm0481264',
	),
	// SOUND RECORDING, ceremony 12, Goodbye, Mr. Chips: '?|nm0914249'.
	938  => array(
		'name'        => 'Denham Studio Sound Department, A. W. Watkins, Sound Director',
		'nominees'    => 'Denham|A. W. Watkins',
		'nominee_ids' => 'nm0914249',
	),
);

$guard = new Lunara_Test_Ledger_Reader();
$guard->guard_pair( 'nm0481264', 'Thomas T. Moulton' ) // Row 526's wrong_id before-pair.
	->guard_pair( 'nm0914249', 'Denham' )               // A first-label-wins label pair (row 938).
	->never_link( 'co0058013' )                         // Row 2111's wrong_id before-ID.
	->never_link( 'tt0169446' )                         // Row 6429's FilmId before-ID.
	->never_link( 'nm0239470' );                        // An ID whose only credits are flagged.

// ---------------------------------------------------------------------------
// 0. The helper itself (plan §8.1 item 1).
// ---------------------------------------------------------------------------
lunara_test_use_reader( null );
lunara_test_assert( false === lunara_oscars_pair_is_guarded( 'co0058013', 'anything' ), 'Without a reader nothing is guarded.' );
lunara_test_use_reader( new Lunara_Test_Legacy_Reader() );
lunara_test_assert( false === lunara_oscars_pair_is_guarded( 'co0058013', 'anything' ), 'A plugin without credit_pair_is_guarded() guards nothing.' );
lunara_test_use_reader( $guard );
lunara_test_assert( true === lunara_oscars_pair_is_guarded( 'co0058013', 'anything' ), 'A never-link ID is guarded whatever the label.' );
lunara_test_assert( true === lunara_oscars_pair_is_guarded( ' CO0058013 ', '' ), 'The ID is trimmed and lowercased before the lookup.' );
lunara_test_assert( true === lunara_oscars_pair_is_guarded( 'nm0481264', 'Thomas T. Moulton' ), 'A guarded pair is guarded.' );
lunara_test_assert( false === lunara_oscars_pair_is_guarded( 'nm0481264', 'Somebody Else' ), 'A guarded ID with another label is not guarded.' );
lunara_test_assert( false === lunara_oscars_pair_is_guarded( '', 'Thomas T. Moulton' ), 'An empty ID is never guarded.' );

// ---------------------------------------------------------------------------
// 1-3. lunara_oscar_nominee_id_for_label() (functions.php; plan §8.1 item 2).
// ---------------------------------------------------------------------------
lunara_test_use_reader( null );
$nominee_id = static function ( $label, $row ) {
	return lunara_oscar_nominee_id_for_label( $label, $row['nominees'] ?: $row['name'], $row['nominee_ids'] );
};

// (1) Row 965: the counts differ, so no pair is trusted.
$hersholt = $nominee_id( 'Jean Hersholt', $rows[965] );
lunara_test_assert( 'nm0604960' !== $hersholt, 'Row 965 must never pair Jean Hersholt with Ralph Morgan\'s nm0604960.' );
lunara_test_assert( '' === $hersholt, 'Row 965 (5 names, 4 IDs) must return no ID for Jean Hersholt.' );
lunara_test_assert( '' === $nominee_id( 'Conrad Nagel', $rows[965] ), 'Row 965 must return no ID for any label, the last included.' );
lunara_test_assert( '' === $nominee_id( 'Fred Jackman', $rows[276] ), 'Row 276 (4 names, 1 ID) must not fall back to its lone ID.' );
lunara_test_assert( '' === $nominee_id( 'A. W. Watkins', $rows[938] ), 'Row 938 (2 names, 1 ID) must not fall back to its lone ID.' );
lunara_test_assert( '' === $nominee_id( 'Roderick Jaynes', $rows[8165] ), 'Row 8165 (1 name, 2 IDs) must not pair the pseudonym with one Coen.' );
lunara_test_assert( '' === lunara_oscar_nominee_id_for_label( 'Roderick Jaynes', 'Roderick Jaynes', 'nm0001053,nm0001054' ), 'A comma-joined slot counts as two IDs, as the plugin normalizer splits it.' );

// (2) Row 526: aligned lists pair by position; a guarded legacy pair does not.
lunara_test_assert( 'nm0481264' === $nominee_id( 'Thomas T. Moulton', $rows[526] ), 'Aligned row 526 must return the positional ID for Thomas T. Moulton.' );
lunara_test_assert( '' === $nominee_id( 'United Artists', $rows[526] ), 'A company ID is not a person ID.' );
lunara_test_use_reader( $guard );
lunara_test_assert( '' === $nominee_id( 'Thomas T. Moulton', $rows[526] ), 'The guarded legacy pair (nm0481264, Thomas T. Moulton) must return no ID.' );
lunara_test_assert( $guard->guard_calls > 0, 'The guard must actually be consulted.' );

// (3) One ID and one name: that ID, unless it is never-link.
$single = array( 'name' => '', 'nominees' => 'FRED JACKMAN', 'nominee_ids' => 'nm0413164' );
lunara_test_assert( 'nm0413164' === $nominee_id( 'Fred Jackman', $single ), 'One name and one ID return that ID.' );
lunara_test_assert( 'nm0413164' === lunara_oscar_nominee_id_for_label( 'Fred Jackman', '', 'nm0413164' ), 'No name and one ID return that ID.' );
lunara_test_assert( true === lunara_oscars_pair_is_guarded( 'co0058013', 'anything' ), 'co0058013 is never-link.' );
lunara_test_assert( '' === lunara_oscar_nominee_id_for_label( 'Samuel Goldwyn Productions', 'Samuel Goldwyn Productions', 'co0058013' ), 'A never-link company ID returns no ID.' );
lunara_test_assert( '' === lunara_oscar_nominee_id_for_label( 'Flagged Person', 'Flagged Person', 'nm0239470' ), 'A never-link person ID returns no ID from the single-ID branch.' );
lunara_test_assert( '' === lunara_oscar_nominee_id_for_label( 'Flagged Person', 'Flagged Person|Other Person', 'nm0239470|nm0000002' ), 'A never-link person ID returns no ID from the positional branch.' );
lunara_test_assert( 'nm0000002' === lunara_oscar_nominee_id_for_label( 'Other Person', 'Flagged Person|Other Person', 'nm0239470|nm0000002' ), 'Its unguarded neighbour still links.' );
lunara_test_assert( '' === lunara_oscar_nominee_id_for_label( '', 'FRED JACKMAN', 'nm0413164' ), 'An empty label returns no ID.' );

// ---------------------------------------------------------------------------
// 4. lunara_resolve_oscars_winner_person_id() (inc/oscars-data.php; item 3).
// ---------------------------------------------------------------------------
lunara_test_use_reader( null );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( $rows[276] ), 'Row 276 (4 names, 1 ID, empty Name) must resolve no person.' );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( $rows[938] ), 'Row 938 (2 names, 1 ID) must resolve no person.' );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( array_merge( $rows[938], array( 'name' => '' ) ) ), 'Row 938 with an empty Name must resolve no person.' );
lunara_test_assert( 'nm0413164' === lunara_resolve_oscars_winner_person_id( $single ), 'A single-name, single-ID row resolves its ID.' );
lunara_test_assert( 'nm0413164' === lunara_resolve_oscars_winner_person_id( array( 'name' => 'Fred Jackman', 'nominees' => 'Fred Jackman', 'nominee_ids' => 'NM0413164' ) ), 'The Name-matching single row resolves its lowercased ID.' );
lunara_test_assert( 'nm0481264' === lunara_resolve_oscars_winner_person_id( array( 'name' => 'Thomas T. Moulton', 'nominees' => $rows[526]['nominees'], 'nominee_ids' => $rows[526]['nominee_ids'] ) ), 'An aligned row resolves the Name-matching slot.' );
lunara_test_use_reader( $guard );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( array( 'name' => 'Thomas T. Moulton', 'nominees' => $rows[526]['nominees'], 'nominee_ids' => $rows[526]['nominee_ids'] ) ), 'A guarded aligned pair resolves no person.' );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( array( 'name' => '', 'nominees' => 'Flagged Person', 'nominee_ids' => 'nm0239470' ) ), 'A never-link single ID resolves no person.' );
lunara_test_assert( '' === lunara_resolve_oscars_winner_person_id( array( 'name' => '', 'nominees' => '', 'nominee_ids' => 'nm0239470' ) ), 'A never-link single ID with no labels at all resolves no person.' );
lunara_test_assert( 'nm0413164' === lunara_resolve_oscars_winner_person_id( $single ), 'An unguarded single row still resolves with the guard on.' );

// ---------------------------------------------------------------------------
// 5. lunara_oscars_person_index_absorb() (inc/oscars-portal.php; item 4).
// ---------------------------------------------------------------------------
lunara_test_use_reader( null );
$index = array();
lunara_oscars_person_index_absorb( $index, $rows[938] );
lunara_test_assert( array() === $index, 'Row 938 (2 names, 1 ID) must add no key, not even its whole Name string.' );
$index = array();
lunara_oscars_person_index_absorb( $index, $rows[276] );
lunara_test_assert( array() === $index, 'Row 276 (4 names, 1 ID) must add no key.' );
$index = array();
lunara_oscars_person_index_absorb( $index, array( 'name' => 'Fred Jackman', 'nominees' => 'Fred Jackman', 'nominee_ids' => 'nm0413164' ) );
lunara_test_assert( array( 'fred jackman' => 'nm0413164' ) === $index, 'A one-name, one-ID row keeps its key.' );
$index = array();
lunara_oscars_person_index_absorb( $index, array( 'name' => 'Walt Disney, Producer', 'nominees' => '', 'nominee_ids' => 'nm0000370' ) );
lunara_test_assert( array( 'walt disney producer' => 'nm0000370' ) === $index, 'A Name-only, one-ID row keeps its Name key.' );
$index = array();
lunara_oscars_person_index_absorb( $index, $rows[526] );
lunara_test_assert( isset( $index['thomas t moulton'] ) && 'nm0481264' === $index['thomas t moulton'], 'Without a guard the aligned row 526 pairs Thomas T. Moulton.' );
lunara_test_use_reader( $guard );
$index = array();
lunara_oscars_person_index_absorb( $index, $rows[526] );
lunara_test_assert( ! isset( $index['thomas t moulton'] ), 'The guarded pair (nm0481264, Thomas T. Moulton) must be skipped in the count branch.' );
$index = array();
lunara_oscars_person_index_absorb( $index, array( 'name' => '', 'nominees' => 'Denham', 'nominee_ids' => 'nm0914249' ) );
lunara_test_assert( ! isset( $index['denham'] ), 'The label pair (nm0914249, Denham) must be skipped in the count branch.' );
$index = array();
lunara_oscars_person_index_absorb( $index, array( 'name' => 'Flagged Person', 'nominees' => 'Flagged Person', 'nominee_ids' => 'nm0239470' ) );
lunara_test_assert( array() === $index, 'A never-link ID adds no key from either branch.' );

// ---------------------------------------------------------------------------
// 6. Theme search (inc/frontend.php; item 5).
// ---------------------------------------------------------------------------
$frontend = file_get_contents( $root . '/inc/frontend.php' );

// 6a. The $map_pipe_values closure, extracted verbatim.
if ( ! preg_match( '/\$map_pipe_values\s*=\s*static function/', $frontend, $closure_match, PREG_OFFSET_CAPTURE ) ) {
	lunara_test_assert( false, 'inc/frontend.php must define the $map_pipe_values closure.' );
}
$closure_source  = lunara_test_extract_balanced( substr( $frontend, $closure_match[0][1] ) );
$map_pipe_values = null;
eval( $closure_source . ';' );
lunara_test_assert( $map_pipe_values instanceof Closure, 'The extracted $map_pipe_values must evaluate to a closure.' );
lunara_test_use_reader( null );
lunara_test_assert(
	array( 'co0026841' => 'United Artists', 'nm0481264' => 'Thomas T. Moulton' ) === $map_pipe_values( $rows[526]['nominees'], $rows[526]['nominee_ids'] ),
	'Without a guard the aligned pairs are kept.'
);
lunara_test_assert( array() === $map_pipe_values( $rows[965]['nominees'], $rows[965]['nominee_ids'] ), 'Misaligned row 965 maps nothing.' );
lunara_test_use_reader( $guard );
lunara_test_assert(
	array( 'co0026841' => 'United Artists' ) === $map_pipe_values( $rows[526]['nominees'], $rows[526]['nominee_ids'] ),
	'$map_pipe_values must drop the guarded pair (nm0481264, Thomas T. Moulton).'
);
lunara_test_assert( array() === $map_pipe_values( 'Samuel Goldwyn Productions', 'co0058013' ), '$map_pipe_values must drop a never-link ID.' );

// 6b. Both search paths over stub rows, through the live function bodies.
class wpdb {
	public $prefix = 'wp_';
	public $posts  = 'wp_posts';
	public $oscars_rows = array();
	public $queries = array();

	public function esc_like( $text ) {
		return addcslashes( (string) $text, '_%\\' );
	}
	public function prepare( $query, ...$args ) {
		return $query . ' /* ' . implode( ' | ', array_map( 'strval', is_array( $args[0] ?? null ) ? $args[0] : $args ) ) . ' */';
	}
	public function get_var( $sql ) {
		return $this->prefix . 'academy' . '_awards';
	}
	public function get_results( $sql, $output = null ) {
		$this->queries[] = $sql;
		if ( false !== strpos( $sql, 'FROM ' . $this->posts ) ) {
			return array();
		}
		return $this->oscars_rows;
	}
}

lunara_test_load_functions(
	$root . '/inc/frontend.php',
	array( 'lunara_normalize_search_recovery_label', 'lunara_get_search_recovery_routes', 'lunara_search_text_match_score', 'lunara_get_oscars_search_matches' )
);

$GLOBALS['wpdb']              = new wpdb();
$GLOBALS['wpdb']->oscars_rows = array(
	array(
		'film'               => 'Just Another Missing Kid',
		'film_id'            => 'tt0169446',
		'name'               => 'John Zaritsky, Producer',
		'nominees'           => 'John Zaritsky',
		'nominee_ids'        => 'nm0953508',
		'category'           => 'DOCUMENTARY (Feature)',
		'canonical_category' => 'DOCUMENTARY (Feature)',
		'ceremony'           => 55,
		'year'               => '1982',
		'winner'             => 1,
	),
	array(
		'film'               => 'Dodsworth',
		'film_id'            => 'tt0027532',
		'name'               => $rows[526]['name'],
		'nominees'           => $rows[526]['nominees'],
		'nominee_ids'        => $rows[526]['nominee_ids'],
		'category'           => 'SOUND RECORDING',
		'canonical_category' => 'SOUND RECORDING',
		'ceremony'           => 9,
		'year'               => '1936',
		'winner'             => 0,
	),
);

$match_keys = static function ( $matches ) {
	$keys = array();
	foreach ( $matches as $match ) {
		$keys[] = (string) ( $match['url'] ?? '' );
	}
	return $keys;
};
$title_url  = 'https://example.test/oscars/title/tt0169446/';
$person_url = 'https://example.test/oscars/name/nm0481264/';

lunara_test_use_reader( null );
$recovery = $match_keys( lunara_get_search_recovery_routes( 'Just Another Missing Kid', 6 ) );
$direct   = $match_keys( lunara_get_oscars_search_matches( 'Just Another Missing Kid', 6 ) );
lunara_test_assert( in_array( $title_url, $recovery, true ), 'Control: without a guard the recovery search offers the title (the stub rows reach the title match).' );
lunara_test_assert( in_array( $title_url, $direct, true ), 'Control: without a guard the direct search offers the title.' );
lunara_test_assert( in_array( $person_url, $match_keys( lunara_get_oscars_search_matches( 'Thomas T. Moulton', 6 ) ), true ), 'Control: without a guard the direct search offers the aligned person.' );

lunara_test_use_reader( $guard );
$recovery = $match_keys( lunara_get_search_recovery_routes( 'Just Another Missing Kid', 6 ) );
$direct   = $match_keys( lunara_get_oscars_search_matches( 'Just Another Missing Kid', 6 ) );
lunara_test_assert( ! in_array( $title_url, $recovery, true ), 'The recovery search (:2211-2231) must not offer never-link tt0169446.' );
lunara_test_assert( ! in_array( $title_url, $direct, true ), 'The direct search (:2596-2610) must not offer never-link tt0169446.' );
foreach ( array_merge( $recovery, $direct ) as $url ) {
	lunara_test_assert( false === strpos( $url, 'tt0169446' ), 'No search match may carry tt0169446: ' . $url );
}
lunara_test_assert( in_array( 'https://example.test/oscars/title/tt0027532/', $match_keys( lunara_get_oscars_search_matches( 'Dodsworth', 6 ) ), true ), 'An unguarded title still matches with the guard on.' );
$moulton = $match_keys( lunara_get_oscars_search_matches( 'Thomas T. Moulton', 6 ) );
lunara_test_assert( ! in_array( $person_url, $moulton, true ), 'The direct search must not offer the guarded pair (nm0481264, Thomas T. Moulton).' );
lunara_test_assert( in_array( 'https://example.test/oscars/company/co0026841/', $match_keys( lunara_get_oscars_search_matches( 'United Artists', 6 ) ), true ), 'The row\'s unguarded company pair is still offered.' );

// ---------------------------------------------------------------------------
// 7. Absent helper: the consumers degrade to today's behaviour.
// ---------------------------------------------------------------------------
lunara_test_use_reader( null );
lunara_test_assert( 'nm0481264' === $nominee_id( 'Thomas T. Moulton', $rows[526] ), 'With no reader the aligned pair links exactly as before.' );

fwrite( STDOUT, "Oscars positional-link runtime passed: nominee-for-label, winner person, person index, search pairs and both search title matches honour counts and the legacy link guard.\n" );
exit( 0 );
