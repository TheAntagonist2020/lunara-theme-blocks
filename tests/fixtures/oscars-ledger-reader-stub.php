<?php
/**
 * Shared stubs for the Theme 3.2.91 Oscars Ledger runtime tests
 * (oscars-positional-link-runtime.php, oscars-dataset-cache-runtime.php).
 *
 * A minimal WordPress surface that records what the theme does (hooks,
 * transients, options, cron), a stub Academy database reader whose legacy
 * link guard and dataset stamp each test sets, and a token-based function
 * extractor for the files that cannot be loaded whole (functions.php,
 * inc/frontend.php).
 *
 * The reader stub mirrors the plugin contract of plan §5.6 and §4.7:
 * credit_pair_is_guarded( $id, $label ) is true for a never-link ID whatever
 * the label, or for a guarded (ID, folded label) pair; get_dataset_stamp()
 * returns the stamp. Academy_Awards_Table::get_instance() returns whatever
 * reader the test installed, so a test can model a current plugin, an
 * older plugin without the accessors, or (with --no-reader, in a separate
 * process where the class is never declared) no plugin at all.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}
foreach ( array( 'MINUTE_IN_SECONDS' => 60, 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400, 'WEEK_IN_SECONDS' => 604800 ) as $lunara_const => $lunara_value ) {
	if ( ! defined( $lunara_const ) ) {
		define( $lunara_const, $lunara_value );
	}
}
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
// Recording WordPress surface.
// ---------------------------------------------------------------------------
$GLOBALS['lunara_test_hooks']      = array();
$GLOBALS['lunara_test_transients'] = array();
$GLOBALS['lunara_test_log']        = array();
$GLOBALS['lunara_test_options']    = array();
$GLOBALS['lunara_test_cron']       = array();
$GLOBALS['lunara_test_get_default'] = false;

function lunara_test_reset_log() {
	$GLOBALS['lunara_test_log'] = array(
		'get'    => array(),
		'set'    => array(),
		'delete' => array(),
		'option' => array(),
	);
}
lunara_test_reset_log();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['lunara_test_hooks'][ $hook ][] = array( $callback, (int) $priority, (int) $accepted_args );
	return true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_action( $hook, $callback, $priority, $accepted_args );
}
function has_action( $hook, $callback = false ) {
	foreach ( $GLOBALS['lunara_test_hooks'][ $hook ] ?? array() as $entry ) {
		if ( false === $callback || $entry[0] === $callback ) {
			return false === $callback ? true : $entry[1];
		}
	}
	return false;
}
function has_filter( $hook, $callback = false ) {
	return has_action( $hook, $callback );
}
function do_action( $hook, ...$args ) {
	$entries = $GLOBALS['lunara_test_hooks'][ $hook ] ?? array();
	usort(
		$entries,
		static function ( $a, $b ) {
			return $a[1] <=> $b[1];
		}
	);
	foreach ( $entries as $entry ) {
		call_user_func_array( $entry[0], array_slice( $args, 0, $entry[2] ) );
	}
}
function apply_filters( $hook, $value ) {
	return $value;
}

function get_transient( $key ) {
	$GLOBALS['lunara_test_log']['get'][] = $key;
	if ( array_key_exists( $key, $GLOBALS['lunara_test_transients'] ) ) {
		return $GLOBALS['lunara_test_transients'][ $key ];
	}
	return $GLOBALS['lunara_test_get_default'];
}
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['lunara_test_log']['set'][]          = array( $key, $ttl );
	$GLOBALS['lunara_test_transients'][ $key ] = $value;
	return true;
}
function delete_transient( $key ) {
	$GLOBALS['lunara_test_log']['delete'][] = $key;
	unset( $GLOBALS['lunara_test_transients'][ $key ] );
	return true;
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['lunara_test_options'] ) ? $GLOBALS['lunara_test_options'][ $key ] : $default;
}
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['lunara_test_log']['option'][]  = $key;
	$GLOBALS['lunara_test_options'][ $key ] = $value;
	return true;
}
function wp_next_scheduled( $hook, $args = array() ) {
	foreach ( $GLOBALS['lunara_test_cron'] as $event ) {
		if ( $event['hook'] === $hook ) {
			return $event['timestamp'];
		}
	}
	return false;
}
function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
	$GLOBALS['lunara_test_cron'][] = array( 'timestamp' => (int) $timestamp, 'hook' => $hook, 'recurrence' => false );
	return true;
}
function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
	$GLOBALS['lunara_test_cron'][] = array( 'timestamp' => (int) $timestamp, 'hook' => $hook, 'recurrence' => $recurrence );
	return true;
}

function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}
function trailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' ) . '/';
}
function __( $text, $domain = null ) {
	return $text;
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $text ) {
	return esc_html( $text );
}
function esc_url( $url ) {
	return (string) $url;
}
function wp_date( $format, $timestamp = null ) {
	return gmdate( $format, null === $timestamp ? time() : $timestamp );
}
function absint( $value ) {
	return abs( intval( $value ) );
}
function remove_accents( $text ) {
	return (string) $text; // The fixtures are ASCII.
}
function wp_strip_all_tags( $text ) {
	return trim( strip_tags( (string) $text ) );
}
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ?? null ) ) {
		$params = $args[0];
		$url    = (string) ( $args[1] ?? '' );
	} else {
		$params = array( (string) ( $args[0] ?? '' ) => $args[1] ?? '' );
		$url    = (string) ( $args[2] ?? '' );
	}
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $params );
}

// ---------------------------------------------------------------------------
// Source extraction for files the harness cannot load whole.
// ---------------------------------------------------------------------------

/**
 * Return the source of `function $name(...) { ... }` from $source.
 */
function lunara_test_extract_function( $source, $name ) {
	if ( ! preg_match( '/function\s+' . preg_quote( $name, '/' ) . '\s*\(/', $source, $match, PREG_OFFSET_CAPTURE ) ) {
		throw new RuntimeException( 'Missing function ' . $name );
	}
	return lunara_test_extract_balanced( substr( $source, $match[0][1] ) );
}

/**
 * Return $fragment up to and including the brace that closes its first `{`.
 */
function lunara_test_extract_balanced( $fragment ) {
	$out    = '';
	$depth  = 0;
	$opened = false;
	foreach ( token_get_all( '<?php ' . $fragment ) as $index => $token ) {
		if ( 0 === $index ) {
			continue; // The synthetic open tag.
		}
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) {
				$depth++;
			}
			$out .= $token[1];
			continue;
		}
		$out .= $token;
		if ( '{' === $token ) {
			$depth++;
			$opened = true;
		}
		if ( '}' === $token && 0 === --$depth && $opened ) {
			return $out;
		}
	}
	throw new RuntimeException( 'Unclosed block' );
}

/**
 * Define each named function from $file (skipping ones already defined).
 */
function lunara_test_load_functions( $file, $names ) {
	$source = file_get_contents( $file );
	if ( false === $source ) {
		throw new RuntimeException( 'Unreadable ' . $file );
	}
	foreach ( $names as $name ) {
		if ( ! function_exists( $name ) ) {
			eval( lunara_test_extract_function( $source, $name ) );
		}
	}
}

// ---------------------------------------------------------------------------
// Stub Academy database readers.
// ---------------------------------------------------------------------------

/**
 * The stub's fold. The plugin's lunara-fold/1 is stricter; the tests only
 * need the same fold on both sides of the lookup.
 */
function lunara_test_fold( $label ) {
	$label = strtolower( trim( (string) $label ) );
	$label = preg_replace( '/[^a-z0-9]+/', ' ', $label );
	return trim( (string) $label );
}

/**
 * A current plugin (2.8.0+): both accessors present.
 */
class Lunara_Test_Ledger_Reader {
	public $stamp = '';
	public $guard = array();
	public $guard_calls = 0;

	public function guard_pair( $imdb_id, $label ) {
		$this->guard[ 'p:' . strtolower( trim( $imdb_id ) ) . "\x1f" . lunara_test_fold( $label ) ] = true;
		return $this;
	}

	public function never_link( $imdb_id ) {
		$this->guard[ 'n:' . strtolower( trim( $imdb_id ) ) ] = true;
		return $this;
	}

	public function credit_id_is_never_link( $imdb_id ) {
		return isset( $this->guard[ 'n:' . strtolower( trim( (string) $imdb_id ) ) ] );
	}

	public function credit_pair_is_guarded( $imdb_id, $label ) {
		$this->guard_calls++;
		return $this->credit_id_is_never_link( $imdb_id )
			|| isset( $this->guard[ 'p:' . strtolower( trim( (string) $imdb_id ) ) . "\x1f" . lunara_test_fold( $label ) ] );
	}

	public function get_dataset_stamp() {
		return $this->stamp;
	}

	public function get_entity_base_url() {
		return home_url( '/oscars/' );
	}
}

/**
 * An older plugin (2.7.x): neither accessor exists.
 */
class Lunara_Test_Legacy_Reader {
	public function get_entity_base_url() {
		return home_url( '/oscars/' );
	}
}

if ( ! in_array( '--no-reader', $GLOBALS['argv'] ?? array(), true ) ) {
	// Declared only when the test models an installed plugin: the no-reader
	// process runs with the class absent, exactly as without the plugin.
	eval(
		'class Academy_Awards_Table {
			public static $reader = null;
			public static function get_instance() { return self::$reader; }
		}'
	);
}

/**
 * Install a reader (or null) behind Academy_Awards_Table::get_instance().
 */
function lunara_test_use_reader( $reader ) {
	if ( class_exists( 'Academy_Awards_Table' ) ) {
		Academy_Awards_Table::$reader = $reader;
	}
	return $reader;
}
