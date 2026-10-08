<?php
/**
 * MySQL refuses an aggregate alias inside an ORDER BY expression:
 *   SELECT x, COUNT(*) AS total, SUM(...) AS wins ... ORDER BY (total / wins)
 *   -> "Reference 'total' not supported (reference to group function)"
 * A bare alias (ORDER BY total) is fine; an alias inside an expression is not.
 * Two Deep Cuts queries shipped that way and failed on every /oscars/ render
 * (logged as WordPress database errors, so the stats quietly went missing).
 *
 * This reads every SQL literal under inc/ that has both GROUP BY and ORDER BY,
 * collects its "<aggregate> AS alias" names, and fails if any of those aliases
 * appears inside a parenthesised ORDER BY expression.
 *
 * Run: php tests/oscars-sql-group-alias-contract.php
 */

$root     = dirname( __DIR__ );
$failures = array();
$queries  = 0;

foreach ( glob( $root . '/inc/*.php' ) as $file ) {
	$source = (string) file_get_contents( $file );
	// Every double-quoted PHP string literal (possessive quantifiers: no backtracking on 7,000-line files).
	if ( ! preg_match_all( '/"((?:[^"\\\\]++|\\\\.)*+)"/s', $source, $matches, PREG_OFFSET_CAPTURE ) ) {
		continue;
	}
	foreach ( $matches[1] as $hit ) {
		$sql = $hit[0];
		if ( ! preg_match( '/\bSELECT\b/i', $sql ) || ! preg_match( '/\bGROUP BY\b/i', $sql ) || ! preg_match( '/\bORDER BY\b/i', $sql ) ) {
			continue;
		}
		++$queries;
		$line = substr_count( substr( $source, 0, $hit[1] ), "\n" ) + 1;
		// Aggregate aliases in the SELECT list.
		if ( ! preg_match_all( '/\b(?:COUNT|SUM|AVG|MIN|MAX|GROUP_CONCAT)\s*\((?:[^()]++|\([^()]*+\))*+\)\s+AS\s+`?([A-Za-z_][A-Za-z0-9_]*)`?/i', $sql, $aliases ) ) {
			continue;
		}
		if ( ! preg_match( '/\bORDER BY\s+(.+?)(?:\bLIMIT\b|$)/is', $sql, $order ) ) {
			continue;
		}
		// An ORDER BY that repeats the aggregate is the correct form; the alias
		// name may legitimately appear inside its string literals ('won'), so
		// strip those before looking for the alias.
		$order_by = preg_replace( "/'(?:[^'\\\\]++|\\\\.)*+'/", "''", $order[1] );
		if ( strpos( $order_by, '(' ) === false ) {
			continue; // a bare alias is allowed
		}
		foreach ( array_unique( $aliases[1] ) as $alias ) {
			if ( preg_match( '/\(\s*[^()]*\b' . preg_quote( $alias, '/' ) . '\b[^()]*\)/i', $order_by ) ) {
				$failures[] = basename( $file ) . ':' . $line . " uses the aggregate alias '{$alias}' inside an ORDER BY expression; MySQL rejects it (\"Reference '{$alias}' not supported\"). Repeat the aggregate instead: " . trim( preg_replace( '/\s+/', ' ', $order[1] ) );
			}
		}
	}
}

if ( $queries < 2 ) {
	$failures[] = "Expected to find grouped, ordered SQL under inc/; found {$queries}. The query regex is probably broken.";
}

if ( $failures ) {
	fwrite( STDERR, implode( "\n", array_unique( $failures ) ) . "\n" );
	exit( 1 );
}

echo "Oscars SQL group-alias contract OK: {$queries} grouped queries checked.\n";
