<?php
/** Offline request-rule regression using captured official WordPress core output. */
define( 'ABSPATH', __DIR__ );
$checks = 0;
$hooks = array();
$registrations = array();
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][] = array( $hook, $callback, $priority, $args ); }
function update_option( ...$args ) { throw new RuntimeException( 'Rewrite compatibility must not write options.' ); }
function delete_option( ...$args ) { throw new RuntimeException( 'Rewrite compatibility must not delete options.' ); }
function flush_rewrite_rules( ...$args ) { throw new RuntimeException( 'Rewrite compatibility must not flush rules.' ); }
function __( $text, $domain = '' ) { return $text; }
function register_post_type( $name, $args ) { $GLOBALS['registrations'][] = array( 'kind' => 'post_type', 'name' => $name, 'args' => $args ); }
function register_taxonomy( $name, $types, $args ) { $GLOBALS['registrations'][] = array( 'kind' => 'taxonomy', 'name' => $name, 'args' => $args ); }
function check( $condition, $message ) { ++$GLOBALS['checks']; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function first_rule_query( $rules, $path ) {
	foreach ( $rules as $regex => $query ) {
		if ( preg_match( '~^' . $regex . '~', $path, $matches ) ) {
			return preg_replace_callback( '/\$matches\[(\d+)\]/', static function ( $m ) use ( $matches ) { return $matches[(int) $m[1]] ?? ''; }, $query );
		}
	}
	return null;
}
function extract_registration( $source, $name ) {
	$tokens = token_get_all( $source );
	for ( $i = 0, $count = count( $tokens ); $i < $count; ++$i ) {
		if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
		$j = $i + 1;
		while ( is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
		if ( ! is_array( $tokens[$j] ) || $name !== $tokens[$j][1] ) { continue; }
		$body = ''; $depth = 0; $opened = false;
		for ( $k = $i; $k < $count; ++$k ) {
			$token = $tokens[$k]; $body .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) { $opened = true; ++$depth; }
			if ( '}' === $token && 0 === --$depth && $opened ) { return $body; }
		}
	}
	throw new RuntimeException( 'Missing actual registration function: ' . $name );
}
$module = getenv( 'LUNARA_OSCAR_REWRITE_SOURCE' ) ?: dirname( __DIR__ ) . '/inc/oscar-taxonomy-rewrites.php';
require $module;
check( array( array( 'option_rewrite_rules', 'lunara_oscar_taxonomy_prefer_existing_rules', 10, 1 ) ) === $hooks, 'Compatibility must have only its read-time option filter.' );
$fixture = json_decode( file_get_contents( __DIR__ . '/fixtures/oscar-taxonomy-core-7.1-rules.json' ), true, 512, JSON_THROW_ON_ERROR );
check( 3 === count( $fixture['provenance']['sources'] ), 'Fixture must retain the three official core source hashes.' );
foreach ( $fixture['provenance']['sources'] as $source ) {
	check( str_starts_with( $source['url'], 'https://raw.githubusercontent.com/WordPress/wordpress-develop/7.1/' ) && 64 === strlen( $source['sha256'] ), 'Fixture provenance must identify exact official source content.' );
}
// Picks registration lives in inc/oscar-picks.php (3.2.90); Facts remain in functions.php.
$source = getenv( 'LUNARA_OSCAR_REGISTRATION_SOURCE' ) ? file_get_contents( getenv( 'LUNARA_OSCAR_REGISTRATION_SOURCE' ) ) : file_get_contents( dirname( __DIR__ ) . '/inc/oscar-picks.php' ) . "\n" . file_get_contents( dirname( __DIR__ ) . '/functions.php' );
foreach ( array( 'lunara_register_oscar_pick_cpt', 'lunara_register_oscar_fact_cpt' ) as $name ) { eval( extract_registration( $source, $name ) ); $name(); }
check( array( 'oscar_pick_category', 'lunara_oscar_pick', 'oscar_fact_category', 'oscar_fact' ) === array_column( $registrations, 'name' ), 'Future rule generation must register each taxonomy before its CPT.' );
$old_arguments = array_column( $fixture['provenance']['registration_arguments'], null, 'name' );
foreach ( $registrations as $registration ) { check( $registration === $old_arguments[$registration['name']], 'Registration order must be the only change to ' . $registration['name'] ); }

foreach ( $fixture['cases'] as $name => $case ) {
	$old = $case['old_rules']; $fixed = lunara_oscar_taxonomy_prefer_existing_rules( $old ); $fresh = $case['taxonomy_first_rules'];
	$prefix = 'index' === $name ? 'index.php/' : '';
	check( $old !== $fixed, "$name must reproduce and correct the old collision." );
	check( $fixed === lunara_oscar_taxonomy_prefer_existing_rules( $fixed ), "$name repeated reads must be idempotent." );
	check( $fresh === lunara_oscar_taxonomy_prefer_existing_rules( $fresh ), "$name correctly generated rules must remain byte-for-byte ordered." );
	$old_values = $old; $fixed_values = $fixed; ksort( $old_values ); ksort( $fixed_values );
	check( $old_values === $fixed_values, "$name must not add, remove, or modify rule values." );
	foreach ( array( 'oscar-picks' => array( 'oscar_pick_category', 'lunara_oscar_pick', 'best-director' ), 'oscar-facts' => array( 'oscar_fact_category', 'oscar_fact', 'genre-breakthroughs' ) ) as $base => $types ) {
		list( $taxonomy, $post_type, $term ) = $types;
		$base = $prefix . $base;
		check( 'index.php?attachment=' . $term === first_rule_query( $old, "$base/category/$term/" ), "$name fixture must capture the original attachment misroute." );
		foreach ( array( $term, 'best-picture', 'feed', 'embed', '123' ) as $slug ) {
			check( "index.php?$taxonomy=$slug" === first_rule_query( $fixed, "$base/category/$slug/" ), "$name must resolve the taxonomy term $slug." );
		}
		foreach ( array( 'feed/' => '&feed=feed', 'feed/rss2/' => '&feed=rss2', 'rss2/' => '&feed=rss2', 'page/2/' => '&paged=2', 'embed/' => '&embed=true' ) as $tail => $query ) {
			$path = "$base/category/$term/$tail";
			check( "index.php?$taxonomy=$term$query" === first_rule_query( $fixed, $path ), "$name must keep the category endpoint $tail." );
			check( first_rule_query( $fresh, $path ) === first_rule_query( $fixed, $path ), "$name compatibility and fresh core rules must agree for $tail." );
		}
		foreach ( array( '', 'page/2/', 'feed/', 'feed/rss2/', 'example-story/', 'example-story/2/', 'example-story/feed/rss2/', 'example-story/photo/', 'example-story/photo/feed/rss2/', 'example-story/photo/embed/', 'example-story/attachment/photo/' ) as $tail ) {
			$path = "$base/$tail";
			check( null !== first_rule_query( $old, $path ), "$name control route must actually match: $path." );
			check( first_rule_query( $old, $path ) === first_rule_query( $fixed, $path ), "$name ordinary archive/single/attachment route must remain unchanged: $path." );
		}
		$custom_key = "$base/category/{$term}/?$";
		$custom = array( $custom_key => 'index.php?custom_owner=early' ) + $old;
		check( 'index.php?custom_owner=early' === first_rule_query( lunara_oscar_taxonomy_prefer_existing_rules( $custom ), "$base/category/$term/" ), "$name existing early custom ownership must win." );
	}
	// An unknown rule inside the affected segment can own a deeper URL. Preserve it.
	$anchor = $prefix . 'oscar-picks/[^/]+/attachment/([^/]+)/?$';
	$interleaved = array();
	foreach ( $old as $regex => $query ) {
		$interleaved[$regex] = $query;
		if ( $regex === $anchor ) { $interleaved[$prefix . 'oscar-picks/category/best-director/page/([0-9]+)/?$'] = 'index.php?custom_owner=deep&paged=$matches[1]'; }
	}
	$interleaved_fixed = lunara_oscar_taxonomy_prefer_existing_rules( $interleaved );
	$pick_only = static function ( $key ) use ( $prefix ) { return str_starts_with( $key, $prefix . 'oscar-picks/' ); };
	check( array_filter( $interleaved, $pick_only, ARRAY_FILTER_USE_KEY ) === array_filter( $interleaved_fixed, $pick_only, ARRAY_FILTER_USE_KEY ), "$name interleaved custom rule must prevent all promotion for that family." );
	check( 'index.php?custom_owner=deep&paged=2' === first_rule_query( $interleaved_fixed, $prefix . 'oscar-picks/category/best-director/page/2/' ), "$name custom deeper route must retain its existing precedence." );
	$altered = $old;
	$altered[$prefix . 'oscar-picks/category/([^/]+)/?$'] = 'index.php?custom_taxonomy=$matches[1]';
	check( array_filter( $altered, $pick_only, ARRAY_FILTER_USE_KEY ) === array_filter( lunara_oscar_taxonomy_prefer_existing_rules( $altered ), $pick_only, ARRAY_FILTER_USE_KEY ), "$name altered query values must not be treated as owned rules." );
	$no_taxonomy = array_filter( $old, static function ( $key ) { return ! str_contains( $key, '/category/' ); }, ARRAY_FILTER_USE_KEY );
	check( $no_taxonomy === lunara_oscar_taxonomy_prefer_existing_rules( $no_taxonomy ), "$name missing taxonomy rules must not be synthesized." );
	$without_root = $old; unset( $without_root[$prefix . 'oscar-picks/category/([^/]+)/?$'] );
	check( ! array_key_exists( $prefix . 'oscar-picks/category/([^/]+)/?$', lunara_oscar_taxonomy_prefer_existing_rules( $without_root ) ), "$name missing individual taxonomy rule must stay missing." );
	$custom_after = $old + array( 'custom-last/?$' => 'index.php?last=1' );
	check( 'custom-last/?$' === array_key_last( lunara_oscar_taxonomy_prefer_existing_rules( $custom_after ) ), "$name unrelated trailing rule must stay last." );
}
foreach ( array( false, null, 'not-rules', 12, array(), array( 'valid/?$' => array( 'bad' ) ), array( 'index.php?bad=1' ) ) as $invalid ) {
	check( $invalid === lunara_oscar_taxonomy_prefer_existing_rules( $invalid ), 'Malformed or absent stored option must remain unchanged.' );
}
check( $fixture['cases']['pretty']['old_rules'] === $fixture['cases']['front']['old_rules'], 'with_front=false must exclude the post permalink front.' );
check( $fixture['cases']['pretty']['old_rules'] === $fixture['cases']['subdir']['old_rules'], 'Core permastruct rules must stay relative to a subdirectory home URL.' );
$module_source = file_get_contents( $module );
check( ! preg_match( '/\b(?:update_option|add_option|delete_option|flush_rewrite_rules|flush_rules)\s*\(/', $module_source ), 'Compatibility may not mutate persisted rewrite state.' );
$loader = file_get_contents( dirname( __DIR__ ) . '/functions-loader.php' );
check( 1 === substr_count( $loader, "require_once \$lunara_inc . 'oscar-taxonomy-rewrites.php';" ), 'Read-time correction must be loaded exactly once in the active theme loader.' );
echo "Oscar taxonomy rewrite runtime passed: $checks assertions.\n";
