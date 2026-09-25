<?php
/**
 * Theme 3.2.91: dataset-stamped Oscars caches and the swap listener (plan
 * §8.1 items 1 and 9-11, §4.7).
 *
 * Run: php tests/oscars-dataset-cache-runtime.php
 *
 * The plugin versions its caches with one dataset stamp that changes when a
 * new dataset goes live. The theme's five long-lived Oscars transients take
 * the same stamp through lunara_oscars_dataset_cache_key() on the line after
 * their key literal, so a swap retires them without a flush; every delete
 * site deletes both the plain and the stamped key, so review saves and data
 * imports still invalidate; the live-search key carries the stamp too. With
 * no stamp (no plugin, an older plugin, pre-ledger data) every key is
 * byte-for-byte what it was, so tests/site-studio-oscars-winner-cases.php's
 * seeded showcase key still hits.
 *
 * lunara_oscars_on_ledger_swapped() listens to aat_ledger_swapped (the one
 * hook a rollback to the pre-ledger data fires): it runs the three theme
 * invalidators once and schedules one portal warm 30 s out, so the person
 * index for the new stamp is rebuilt within a minute.
 *
 * The no-plugin half runs in a child process (--no-reader) in which
 * Academy_Awards_Table is never declared.
 */

require __DIR__ . '/fixtures/oscars-ledger-reader-stub.php';

$root      = dirname( __DIR__ );
$no_reader = in_array( '--no-reader', $argv, true );

class Lunara_Test_Rest_Request {
	private $params;
	public function __construct( $params ) {
		$this->params = $params;
	}
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}
function rest_ensure_response( $data ) {
	return $data;
}

require $root . '/inc/oscars-family.php';
require $root . '/inc/queries.php';
require $root . '/inc/oscars-data.php';
require $root . '/inc/oscars-portal.php';
require $root . '/inc/live-search.php';

$day       = intval( wp_date( 'z' ) );
$tomorrow  = ( $day + 1 ) % 366;
$literals  = array(
	'person_index'  => 'lunara_oscars_person_index_v1',
	'showcase'      => 'lunara_oscars_rotating_showcase_v4_' . $day . '_10',
	'story_cards'   => 'lunara_home_ledger_story_cards_v2',
	'spotlight'     => 'lunara_home_oscar_spotlight_v1',
	'deep_cuts'     => 'lunara_home_deep_cuts_v1',
);
$builders  = array(
	'person_index' => array( 'lunara_oscars_person_name_index', array( false ) ),
	'showcase'     => array( 'lunara_get_rotating_oscars_ceremony_showcase', array( 10 ) ),
	'story_cards'  => array( 'lunara_get_home_ledger_story_cards', array() ),
	'spotlight'    => array( 'lunara_get_home_oscar_spotlight', array() ),
	'deep_cuts'    => array( 'lunara_get_home_deep_cuts', array() ),
);

/**
 * The key each builder reads first, served from a cache hit so no builder
 * does any work.
 */
function lunara_test_read_keys( $builders ) {
	$keys                              = array();
	$GLOBALS['lunara_test_get_default'] = array( 'cached' => true );
	foreach ( $builders as $name => $builder ) {
		lunara_test_reset_log();
		$result = call_user_func_array( $builder[0], $builder[1] );
		lunara_test_assert( array( 'cached' => true ) === $result, $builder[0] . '() must return the cached payload on a hit.' );
		$keys[ $name ] = $GLOBALS['lunara_test_log']['get'][0] ?? '';
	}
	$GLOBALS['lunara_test_get_default'] = false;
	return $keys;
}

/**
 * The key the live-search callback reads for $q (a cache hit).
 */
function lunara_test_live_search_key( $q ) {
	$GLOBALS['lunara_test_get_default'] = array( 'groups' => array() );
	lunara_test_reset_log();
	$response = lunara_live_search_rest_callback( new Lunara_Test_Rest_Request( array( 'q' => $q ) ) );
	$GLOBALS['lunara_test_get_default'] = false;
	lunara_test_assert( is_array( $response ) && array_key_exists( 'groups', $response ), 'The live-search callback must answer from its cache.' );
	foreach ( $GLOBALS['lunara_test_log']['get'] as $key ) {
		if ( 0 === strpos( $key, 'lunara_ls_' ) ) {
			return $key;
		}
	}
	lunara_test_assert( false, 'The live-search callback must read a lunara_ls_ key.' );
}

/**
 * Run every delete site of plan §8.1 item 9 and return the keys each deleted.
 */
function lunara_test_delete_sites() {
	$sites = array();

	lunara_test_reset_log();
	lunara_invalidate_review_query_caches( 0 );
	$sites['inc/queries.php lunara_invalidate_review_query_caches'] = $GLOBALS['lunara_test_log']['delete'];

	lunara_test_reset_log();
	lunara_invalidate_oscars_data_caches( 'full', 1 );
	$sites['inc/queries.php lunara_invalidate_oscars_data_caches'] = $GLOBALS['lunara_test_log']['delete'];

	lunara_test_reset_log();
	lunara_flush_oscars_home_transients();
	$sites['inc/oscars-data.php lunara_flush_oscars_home_transients'] = $GLOBALS['lunara_test_log']['delete'];

	// The warmer needs a reader with get_title_visual_package(); it deletes
	// the person index first and its own flush call is part of its log.
	lunara_test_reset_log();
	lunara_oscars_portal_warm_visuals();
	$sites['inc/oscars-portal.php lunara_oscars_portal_warm_visuals'] = $GLOBALS['lunara_test_log']['delete'];

	return $sites;
}

// ===========================================================================
// Child process: no plugin at all (item 12).
// ===========================================================================
if ( $no_reader ) {
	lunara_test_assert( ! class_exists( 'Academy_Awards_Table' ), 'The no-reader process must run without Academy_Awards_Table.' );
	lunara_test_assert( null === lunara_oscars_reader(), 'Without the plugin there is no reader.' );
	lunara_test_assert( '' === lunara_oscars_dataset_stamp(), 'Without the plugin the stamp is empty.' );
	lunara_test_assert( 'lunara_home_deep_cuts_v1' === lunara_oscars_dataset_cache_key( 'lunara_home_deep_cuts_v1' ), 'Without the plugin a key is unchanged.' );

	$keys = lunara_test_read_keys( $builders );
	lunara_test_assert( $literals === $keys, 'Without the plugin every key must equal its literal: ' . json_encode( $keys ) );

	lunara_test_assert( 'lunara_ls_' . md5( 'cavalcade|0' ) === lunara_test_live_search_key( 'Cavalcade' ), 'Without the plugin the live-search key must be exactly the 3.2.90 key.' );

	// tests/site-studio-oscars-winner-cases.php:28 seeds the unstamped key.
	foreach ( array( 4, 10, 16 ) as $count ) {
		$seeded = array( 'winner_cards' => array( array( 'primary_label' => 'Cached winner ' . $count ) ), 'ceremony_label' => 'Cached ceremony ' . $count );
		set_transient( 'lunara_oscars_rotating_showcase_v4_' . $day . '_' . $count, $seeded, 86400 );
		lunara_test_assert( $seeded === lunara_get_rotating_oscars_ceremony_showcase( $count ), 'The seeded unstamped showcase key for ' . $count . ' cards must still hit.' );
	}

	fwrite( STDOUT, "no-reader ok\n" );
	exit( 0 );
}

// ===========================================================================
// Main process: a stub reader (current plugin), then an older plugin.
// ===========================================================================

class Lunara_Test_Warm_Reader extends Lunara_Test_Ledger_Reader {
	public $packages = 0;
	public function get_title_visual_package( $tt, $size = 'large', $allow_remote = false ) {
		$this->packages++;
		return array();
	}
}

$reader        = lunara_test_use_reader( new Lunara_Test_Warm_Reader() );
$reader->stamp = 'abc123def456';

// ---------------------------------------------------------------------------
// 1. The helpers.
// ---------------------------------------------------------------------------
lunara_test_assert( 'abc123def456' === lunara_oscars_dataset_stamp(), 'The stamp comes from the reader.' );
lunara_test_assert( 'k__abc123def456' === lunara_oscars_dataset_cache_key( 'k' ), 'A stamped key is key . "__" . stamp.' );
$reader->stamp = ' ABC123def456 ';
lunara_test_assert( 'abc123def456' === lunara_oscars_dataset_stamp(), 'The stamp is trimmed and lowercased.' );
$reader->stamp = 'abc/123 def:456' . str_repeat( 'f', 40 );
lunara_test_assert( 1 === preg_match( '/^[a-z0-9]{1,32}$/', lunara_oscars_dataset_stamp() ), 'An odd stamp is reduced to a short key-safe token.' );
$reader->stamp = '';
lunara_test_assert( 'k' === lunara_oscars_dataset_cache_key( 'k' ), 'An empty stamp (pre-ledger data) leaves the key unchanged.' );
$reader->stamp = 'abc123def456';

// ---------------------------------------------------------------------------
// 10. The five keys, from their live inc/ definitions.
// ---------------------------------------------------------------------------
$inc_dir = realpath( $root . '/inc' ) . DIRECTORY_SEPARATOR;
foreach ( $builders as $name => $builder ) {
	$file = (string) ( new ReflectionFunction( $builder[0] ) )->getFileName();
	lunara_test_assert( 0 === strpos( realpath( $file ), $inc_dir ), $builder[0] . '() must be the live inc/ definition, got ' . $file );
}
// The functions.php copies are function_exists-guarded and so never run.
$functions_php = file_get_contents( $root . '/functions.php' );
foreach ( array( 'lunara_get_home_ledger_story_cards', 'lunara_get_home_oscar_spotlight', 'lunara_get_home_deep_cuts' ) as $dead_copy ) {
	if ( preg_match( '/function\s+' . $dead_copy . '\s*\(/', $functions_php ) ) {
		lunara_test_assert( 1 === preg_match( "/if\s*\(\s*!\s*function_exists\(\s*'" . $dead_copy . "'\s*\)\s*\)\s*\{\s*function\s+" . $dead_copy . '\s*\(/', $functions_php ), 'The functions.php copy of ' . $dead_copy . '() must stay function_exists-guarded (dead).' );
	}
}

$keys = lunara_test_read_keys( $builders );
foreach ( $literals as $name => $literal ) {
	lunara_test_assert( $literal . '__abc123def456' === $keys[ $name ], $builders[ $name ][0] . '() must read ' . $literal . '__abc123def456, read ' . $keys[ $name ] );
}

// The set side uses the same (stamped) variable: build the person index.
lunara_test_reset_log();
lunara_test_assert( array() === lunara_oscars_person_name_index( true ), 'A reader without ballots builds an empty index.' );
lunara_test_assert( array( array( 'lunara_oscars_person_index_v1__abc123def456', 12 * HOUR_IN_SECONDS ) ) === $GLOBALS['lunara_test_log']['set'], 'The person index is stored under the stamped key.' );
$data_source = file_get_contents( $root . '/inc/oscars-data.php' );
foreach ( array( 'lunara_get_rotating_oscars_ceremony_showcase', 'lunara_get_home_ledger_story_cards', 'lunara_get_home_oscar_spotlight', 'lunara_get_home_deep_cuts' ) as $builder_name ) {
	$body = lunara_test_extract_function( $data_source, $builder_name );
	lunara_test_assert( 1 === preg_match( '/\$cache_key\s*=\s*lunara_oscars_dataset_cache_key\(\s*\$cache_key\s*\);/', $body ), $builder_name . '() must stamp $cache_key on the line after its literal.' );
	lunara_test_assert( preg_match( '/set_transient\(\s*\$cache_key\s*,/', $body ) && ! preg_match( "/set_transient\(\s*'/", $body ), $builder_name . '() must store under $cache_key, never a literal.' );
}

// ---------------------------------------------------------------------------
// 11. Every delete site deletes both forms.
// ---------------------------------------------------------------------------
$expect = array(
	'inc/queries.php lunara_invalidate_review_query_caches'          => array( $literals['story_cards'], $literals['spotlight'], $literals['deep_cuts'] ),
	'inc/queries.php lunara_invalidate_oscars_data_caches'           => array( $literals['story_cards'], $literals['spotlight'], $literals['deep_cuts'] ),
	'inc/oscars-data.php lunara_flush_oscars_home_transients'        => array( $literals['story_cards'], $literals['spotlight'], $literals['deep_cuts'] ),
	'inc/oscars-portal.php lunara_oscars_portal_warm_visuals'        => array( $literals['person_index'] ),
);
for ( $limit = 4; $limit <= 16; $limit++ ) {
	$expect['inc/oscars-data.php lunara_flush_oscars_home_transients'][] = 'lunara_oscars_rotating_showcase_v4_' . $day . '_' . $limit;
	$expect['inc/oscars-data.php lunara_flush_oscars_home_transients'][] = 'lunara_oscars_rotating_showcase_v4_' . $tomorrow . '_' . $limit;
}
$sites = lunara_test_delete_sites();
foreach ( $expect as $site => $plain_keys ) {
	foreach ( $plain_keys as $plain ) {
		lunara_test_assert( in_array( $plain, $sites[ $site ], true ), $site . ' must still delete the plain key ' . $plain );
		lunara_test_assert( in_array( $plain . '__abc123def456', $sites[ $site ], true ), $site . ' must also delete the stamped key ' . $plain . '__abc123def456' );
	}
}
lunara_test_assert( $reader->packages > 0, 'The warmer ran through to its package fetches.' );
lunara_test_assert( in_array( 'lunara_home_oscars_snapshot_v7', $sites['inc/oscars-data.php lunara_flush_oscars_home_transients'], true ), 'The 15-minute snapshot key is still deleted as before.' );
lunara_test_assert( ! in_array( 'lunara_home_oscars_snapshot_v7__abc123def456', array_merge( ...array_values( $sites ) ), true ), 'The 15-minute keys are not stamped.' );

// ---------------------------------------------------------------------------
// 12 (older plugin) and 13. The live-search key.
// ---------------------------------------------------------------------------
$stamped_key = lunara_test_live_search_key( 'Cavalcade' );
lunara_test_assert( 'lunara_ls_' . md5( 'cavalcade|0|abc123def456' ) === $stamped_key, 'The live-search key must carry the dataset stamp.' );
$reader->stamp = 'fff000111222';
$other_key     = lunara_test_live_search_key( 'Cavalcade' );
lunara_test_assert( $other_key !== $stamped_key, 'The live-search key must change when the stamp changes.' );
lunara_test_assert( 'lunara_ls_' . md5( 'cavalcade|0|fff000111222' ) === $other_key, 'The new key carries the new stamp.' );
$reader->stamp = '';
lunara_test_assert( 'lunara_ls_' . md5( 'cavalcade|0' ) === lunara_test_live_search_key( 'Cavalcade' ), 'An empty stamp leaves the live-search key exactly as in 3.2.90.' );

lunara_test_use_reader( new Lunara_Test_Legacy_Reader() );
lunara_test_assert( '' === lunara_oscars_dataset_stamp(), 'An older plugin without get_dataset_stamp() gives an empty stamp.' );
lunara_test_assert( $literals === lunara_test_read_keys( $builders ), 'With an older plugin every key equals its literal.' );
lunara_test_assert( 'lunara_ls_' . md5( 'cavalcade|0' ) === lunara_test_live_search_key( 'Cavalcade' ), 'With an older plugin the live-search key is exactly the 3.2.90 key.' );

// ---------------------------------------------------------------------------
// 14. The aat_ledger_swapped listener.
// ---------------------------------------------------------------------------
$reader        = lunara_test_use_reader( new Lunara_Test_Warm_Reader() );
$reader->stamp = 'abc123def456';

lunara_test_assert( (bool) has_action( 'aat_ledger_swapped', 'lunara_oscars_on_ledger_swapped' ), 'lunara_oscars_on_ledger_swapped must be hooked on aat_ledger_swapped.' );
$registration = null;
foreach ( $GLOBALS['lunara_test_hooks']['aat_ledger_swapped'] as $entry ) {
	if ( 'lunara_oscars_on_ledger_swapped' === $entry[0] ) {
		$registration = $entry;
	}
}
lunara_test_assert( array( 'lunara_oscars_on_ledger_swapped', 10, 3 ) === $registration, 'The listener is registered at priority 10 with 3 accepted arguments.' );
lunara_test_assert( (bool) has_action( 'lunara_oscars_portal_warm_visuals_now', 'lunara_oscars_portal_warm_visuals' ), 'The scheduled warm event runs the portal warmer.' );
lunara_test_assert( (bool) has_action( 'aat_after_data_import', 'lunara_flush_oscars_home_transients' ), 'The existing import hook is unchanged.' );

/**
 * Fire aat_ledger_swapped and count each invalidator's unique side effect:
 * lunara_invalidate_oscars_data_caches() alone deletes lunara_home_lore_cards_v2,
 * lunara_flush_oscars_home_transients() alone deletes lunara_home_oscars_snapshot_v7,
 * and lunara_oscars_board_art_invalidate() alone writes the board visuals stamp.
 */
function lunara_test_fire_swap( ...$args ) {
	lunara_test_reset_log();
	$before = time();
	do_action( 'aat_ledger_swapped', ...$args );
	$after  = time();
	$count  = static function ( $needle, $haystack ) {
		return count( array_keys( $haystack, $needle, true ) );
	};
	return array(
		'invalidate' => $count( 'lunara_home_lore_cards_v2', $GLOBALS['lunara_test_log']['delete'] ),
		'flush'      => $count( 'lunara_home_oscars_snapshot_v7', $GLOBALS['lunara_test_log']['delete'] ),
		'board_art'  => $count( 'lunara_oscars_board_visuals_stamp', $GLOBALS['lunara_test_log']['option'] ),
		'sets'       => count( $GLOBALS['lunara_test_log']['set'] ),
		'before'     => $before,
		'after'      => $after,
	);
}

$GLOBALS['lunara_test_cron'] = array();
$fire = lunara_test_fire_swap( 'abc123def456', true, 'forward' );
lunara_test_assert( 1 === $fire['invalidate'], 'A swap calls lunara_invalidate_oscars_data_caches() once.' );
lunara_test_assert( 1 === $fire['flush'], 'A swap calls lunara_flush_oscars_home_transients() once.' );
lunara_test_assert( 1 === $fire['board_art'], 'A swap calls lunara_oscars_board_art_invalidate() once.' );
lunara_test_assert( 0 === $fire['sets'] && 0 === $reader->packages, 'The listener does no heavy work itself: it builds nothing and fetches nothing.' );
lunara_test_assert( 1 === count( $GLOBALS['lunara_test_cron'] ), 'A swap schedules exactly one event.' );
$event = $GLOBALS['lunara_test_cron'][0];
lunara_test_assert( 'lunara_oscars_portal_warm_visuals_now' === $event['hook'] && false === $event['recurrence'], 'The event is the single portal warm.' );
lunara_test_assert( $event['timestamp'] >= $fire['before'] + 30 && $event['timestamp'] <= $fire['after'] + 30, 'The warm is scheduled 30 s after the swap.' );

$fire = lunara_test_fire_swap( 'abc123def456', false, 'reassert' );
lunara_test_assert( 1 === $fire['invalidate'] && 1 === $fire['flush'] && 1 === $fire['board_art'], 'A second swap invalidates again.' );
lunara_test_assert( 1 === count( $GLOBALS['lunara_test_cron'] ), 'A second swap schedules no second warm while one is pending.' );

// A rollback to the pre-ledger data (empty stamp) is handled the same way.
$GLOBALS['lunara_test_cron'] = array();
$reader->stamp               = '';
$fire                        = lunara_test_fire_swap( '', false, 'pre_ledger' );
lunara_test_assert( 1 === $fire['invalidate'] && 1 === $fire['flush'] && 1 === $fire['board_art'], 'A pre-ledger restore runs every invalidator once.' );
lunara_test_assert( 1 === count( $GLOBALS['lunara_test_cron'] ) && 'lunara_oscars_portal_warm_visuals_now' === $GLOBALS['lunara_test_cron'][0]['hook'], 'A pre-ledger restore schedules the warm too.' );

// ---------------------------------------------------------------------------
// 12. No plugin at all, in a child process.
// ---------------------------------------------------------------------------
$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --no-reader 2>&1';
$output  = array();
exec( $command, $output, $status );
lunara_test_assert( 0 === $status && in_array( 'no-reader ok', $output, true ), "The no-plugin process failed:\n" . implode( "\n", $output ) );

fwrite( STDOUT, "Oscars dataset cache runtime passed: 5 stamped keys from their inc/ definitions, 4 delete sites delete both forms, stamped live-search key, unchanged keys without a stamp, and the aat_ledger_swapped listener (3 invalidators once, one warm at +30 s).\n" );
exit( 0 );
