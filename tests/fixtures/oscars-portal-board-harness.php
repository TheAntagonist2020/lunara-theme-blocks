<?php
/**
 * Execute the Oscars Prediction Board renderer (inc/oscars-portal.php)
 * against fixture picks — the board's first behavioral coverage.
 *
 * Same extraction pattern as oscars-late-css-route-scope-harness.php: the
 * real function body is lifted from the shipped module and executed against
 * faithful stubs, so the empty-board, escaping, status-bounding, and stable-id
 * contracts are proven on the code that ships, not on a copy.
 */

$root = dirname( __DIR__, 2 );

class WP_Post {
	public $ID;
	public function __construct( $id ) { $this->ID = (int) $id; }
}

class WP_Query {
	public $posts = array();
	public function __construct( $posts = array() ) { $this->posts = $posts; }
}

class WP_Error {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }

$GLOBALS['lunara_board_test_state'] = array(
	'picks' => array(), // pick_id => array( category, film, person, status, url, year, title )
);

function lunara_get_oscar_picks( $args = array() ) {
	$posts = array();
	foreach ( array_keys( $GLOBALS['lunara_board_test_state']['picks'] ) as $pick_id ) {
		$posts[] = new WP_Post( $pick_id );
	}
	return new WP_Query( $posts );
}

function get_the_terms( $pick_id, $taxonomy ) {
	$pick = $GLOBALS['lunara_board_test_state']['picks'][ $pick_id ] ?? array();
	if ( 'oscar_pick_category' !== $taxonomy || '' === (string) ( $pick['category'] ?? '' ) ) {
		return false;
	}
	return array( (object) array( 'name' => (string) $pick['category'] ) );
}

function get_post_meta( $pick_id, $key, $single = false ) {
	$pick = $GLOBALS['lunara_board_test_state']['picks'][ $pick_id ] ?? array();
	$map  = array(
		'_lunara_pick_film'              => $pick['film'] ?? '',
		'_lunara_pick_person'            => $pick['person'] ?? '',
		'_lunara_pick_status'            => $pick['status'] ?? '',
		'_lunara_pick_oscar_entity_url'  => $pick['url'] ?? '',
		'_lunara_pick_ceremony_year'     => $pick['year'] ?? 0,
	);
	return $map[ $key ] ?? '';
}

function get_the_title( $pick_id ) {
	return (string) ( $GLOBALS['lunara_board_test_state']['picks'][ $pick_id ]['title'] ?? 'Pick ' . $pick_id );
}

// Faithful WordPress behavior for everything the renderer escapes with.
function sanitize_key( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'sanitize_key expects a scalar test value' ); } return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'absint expects a scalar test value' ); } return abs( (int) $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
/**
 * Faithful model of WP esc_url() in its default href display context — the
 * parts that matter for href safety. The previous FILTER_SANITIZE_URL stub
 * was WEAKER than WordPress: it passed javascript: hrefs straight through.
 * Real esc_url(): (1) ltrims and turns spaces into %20, then deletes every
 * character outside its allowed set — a raw double quote is REMOVED, not
 * encoded, so it can never break out of a quoted attribute; (2) in display
 * context entity-encodes a bare '&' to '&#038;' and "'" to '&#039;';
 * (3) runs wp_kses_bad_protocol against the allowed-protocol list and
 * returns '' when that changes the URL — so javascript:/data:/vbscript:
 * collapse to the empty string (this harness allows http/https plus
 * relative URLs, the only protocols the board context links).
 */
function esc_url( $value ) {
	if ( ! is_scalar( $value ) ) { throw new TypeError( 'esc_url expects a scalar test value' ); }
	$url = (string) $value;
	if ( '' === $url ) {
		return '';
	}
	$url = str_replace( ' ', '%20', ltrim( $url ) );
	$url = (string) preg_replace( '|[^a-z0-9\-~+_.?#=!&;,/:%@$\|*\'()\[\]\x80-\xff]|i', '', $url );
	if ( '' === $url ) {
		return '';
	}
	// Display context: bare ampersands and single quotes become entities.
	$url = (string) preg_replace( '/&(?!#\d+;|#x[0-9a-f]+;|[a-z][a-z0-9]{1,7};)/i', '&#038;', $url );
	$url = str_replace( '&amp;', '&#038;', $url );
	$url = str_replace( "'", '&#039;', $url );
	// Protocol allowlist: relative URLs pass; anything with a scheme must be
	// http/https or the whole URL collapses to '' exactly like esc_url().
	if ( ! in_array( substr( $url, 0, 1 ), array( '/', '#', '?' ), true ) && preg_match( '/^([a-z][a-z0-9+.\-]*):/i', $url, $scheme_match ) ) {
		if ( ! in_array( strtolower( $scheme_match[1] ), array( 'http', 'https' ), true ) ) {
			return '';
		}
	}
	return $url;
}
function __( $value, $domain = '' ) { return $value; }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function esc_attr__( $value, $domain = '' ) { return esc_attr( $value ); }

function lunara_extract_named_function( $path, $function_name, $replacement_name ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		throw new RuntimeException( 'Unable to read ' . $path );
	}

	$tokens = token_get_all( $source );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}

		$name_index = $i + 1;
		while ( $name_index < $count && ( ! is_array( $tokens[ $name_index ] ) || T_STRING !== $tokens[ $name_index ][0] ) ) {
			$name_index++;
		}

		if ( $name_index >= $count || $function_name !== $tokens[ $name_index ][1] ) {
			continue;
		}

		$function_source = '';
		$brace_depth     = 0;
		$body_started    = false;

		for ( $j = $i; $j < $count; $j++ ) {
			$token = $tokens[ $j ];
			$text  = is_array( $token ) ? $token[1] : $token;

			if ( $j === $name_index ) {
				$text = $replacement_name;
			}

			$function_source .= $text;

			if ( '{' === $text ) {
				$body_started = true;
				$brace_depth++;
			} elseif ( '}' === $text && $body_started ) {
				$brace_depth--;
				if ( 0 === $brace_depth ) {
					return $function_source;
				}
			}
		}
	}

	throw new RuntimeException( sprintf( 'Function %s was not found in %s', $function_name, $path ) );
}

$renderer_source = lunara_extract_named_function(
	$root . '/inc/oscars-portal.php',
	'lunara_render_oscars_prediction_board',
	'lunara_render_oscars_prediction_board_under_test'
);
eval( $renderer_source );

function lunara_board_render_with_picks( $picks ) {
	$GLOBALS['lunara_board_test_state']['picks'] = $picks;
	return lunara_render_oscars_prediction_board_under_test();
}

$report = array( 'cases' => array() );

$record = static function ( $name, $checks ) use ( &$report ) {
	$passed = true;
	foreach ( $checks as $check ) {
		$passed = $passed && (bool) $check;
	}
	$report['cases'][ $name ] = array( 'passed' => $passed, 'checks' => $checks );
};

// Case 1: empty picks compose the EXACT empty string, so the portal degrades
// cleanly off-season with no residue.
$empty_output = lunara_board_render_with_picks( array() );
$record(
	'empty-picks-exact-empty-string',
	array(
		'exact_empty_string' => '' === $empty_output,
	)
);

// Case 2: hostile stored strings arrive entity-escaped; no script element or
// raw fixture markup can reach the anonymous route.
$hostile_output = lunara_board_render_with_picks(
	array(
		301 => array(
			'category' => '<script>alert(1)</script> & "Best" <b>Picture</b>',
			'film'     => 'Oppenheimer & Friends',
			'person'   => 'Robert <img src=x onerror=alert(2)> Downey',
			'status'   => 'lock',
			'url'      => 'https://example.test/oscars/title/tt1/',
			'year'     => 2027,
			'title'    => 'Pick 301',
		),
	)
);
$record(
	'hostile-strings-entity-escaped',
	array(
		'category_escaped'    => false !== strpos( $hostile_output, '&lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;Best&quot; &lt;b&gt;Picture&lt;/b&gt;' ),
		'person_escaped'      => false !== strpos( $hostile_output, 'Robert &lt;img src=x onerror=alert(2)&gt; Downey' ),
		'film_escaped'        => false !== strpos( $hostile_output, 'Oppenheimer &amp; Friends' ),
		'no_script_element'   => false === strpos( $hostile_output, '<script' ),
		'no_raw_img_fixture'  => false === strpos( $hostile_output, '<img src=x' ),
		'link_href_preserved' => false !== strpos( $hostile_output, 'href="https://example.test/oscars/title/tt1/"' ),
	)
);

// Case 3: the is-status-* class is bounded through sanitize_key at meta-read
// time (this IS a sanitize_key-appropriate use — the raw meta feeds a CSS
// class token, never an identity decision), then esc_attr at emission.
$status_output = lunara_board_render_with_picks(
	array(
		311 => array(
			'category' => 'Best Director',
			'film'     => '',
			'person'   => 'Denis Villeneuve',
			'status'   => 'Locked!<X> "quote"',
			'url'      => '',
			'year'     => 0,
			'title'    => 'Pick 311',
		),
		312 => array(
			'category' => 'Best Picture',
			'film'     => 'Dune Messiah',
			'person'   => '',
			'status'   => '',
			'url'      => '',
			'year'     => 0,
			'title'    => 'Pick 312',
		),
	)
);
$record(
	'status-class-bounded-via-sanitize-key',
	array(
		'bounded_class_token' => false !== strpos( $status_output, 'is-status-lockedxquote' ),
		'bounded_status_text' => false !== strpos( $status_output, '>LOCKEDXQUOTE</span>' ),
		'no_raw_bang'         => false === strpos( $status_output, 'is-status-Locked' ),
		'no_class_injection'  => false === strpos( $status_output, 'is-status-locked!' ) && false === strpos( $status_output, 'is-status-lockedx&quot;' ),
		'statusless_row_bare' => false !== strpos( $status_output, '<li class="lunara-oscars-board-row">' ),
		'one_status_span'     => 1 === substr_count( $status_output, 'lunara-oscars-board-status' ),
	)
);

// Case 4: board shape — the stable #oscars-board id, the ceremony-year
// heading, row order, and the person+film secondary line.
$shape_output = lunara_board_render_with_picks(
	array(
		321 => array(
			'category' => 'Best Actress',
			'film'     => 'The Testament',
			'person'   => 'Amy Adams',
			'status'   => 'firm',
			'url'      => 'https://example.test/oscars/person/nm0010736/',
			'year'     => 2027,
			'title'    => 'Pick 321',
		),
		322 => array(
			'category' => 'Best Picture',
			'film'     => '',
			'person'   => '',
			'status'   => '',
			'url'      => '',
			'year'     => 2026,
			'title'    => 'Fallback &amp; Title',
		),
	)
);
$record(
	'board-shape-and-order',
	array(
		'stable_board_id'   => 1 === substr_count( $shape_output, '<section id="oscars-board"' ),
		'board_classes'     => false !== strpos( $shape_output, 'lunara-oscars-board lunara-oscars-portal-slot-board' ),
		'aria_label'        => false !== strpos( $shape_output, 'aria-label="Prediction board"' ),
		'year_heading'      => false !== strpos( $shape_output, 'The desk calls the 2027 ceremony, category by category.' ),
		'row_order'         => strpos( $shape_output, 'Best Actress' ) < strpos( $shape_output, 'Best Picture' ),
		'person_over_film'  => false !== strpos( $shape_output, '>Amy Adams</a>' ) && false !== strpos( $shape_output, '<em>The Testament</em>' ),
		'title_fallback'    => false !== strpos( $shape_output, 'Fallback &amp; Title' ),
		'status_uppercase'  => false !== strpos( $shape_output, '>FIRM</span>' ),
		'ordered_list'      => false !== strpos( $shape_output, '<ol class="lunara-oscars-board-list">' ),
	)
);

// Case 5: the anonymous-cacheable board carries zero nonce, cookie, or
// user-conditional output.
$record(
	'no-state-conditional-output',
	array(
		'no_nonce'  => false === stripos( $shape_output . $hostile_output . $status_output, 'nonce' ),
		'no_cookie' => false === stripos( $shape_output . $hostile_output . $status_output, 'cookie' ),
	)
);

// Case 6: hostile entity-URL metas attack the href lane itself. esc_url()
// must refuse a javascript: protocol outright (empty href) and strip a raw
// double quote so it can never break out of the quoted href attribute.
$hostile_url_output = lunara_board_render_with_picks(
	array(
		331 => array(
			'category' => 'Best Picture',
			'film'     => '',
			'person'   => 'Proto Call',
			'status'   => '',
			'url'      => 'javascript:alert(1)',
			'year'     => 2027,
			'title'    => 'Pick 331',
		),
		332 => array(
			'category' => 'Best Director',
			'film'     => '',
			'person'   => 'Quote Break',
			'status'   => '',
			'url'      => 'https://x/" onmouseover=alert(1) x="',
			'year'     => 2027,
			'title'    => 'Pick 332',
		),
	)
);
preg_match_all( '/href="([^"]*)"/', $hostile_url_output, $hostile_href_matches );
$hostile_href_values   = $hostile_href_matches[1];
$hrefs_have_javascript = false;
foreach ( $hostile_href_values as $hostile_href_value ) {
	if ( false !== stripos( $hostile_href_value, 'javascript:' ) ) {
		$hrefs_have_javascript = true;
	}
}
$record(
	'hostile-url-meta-neutralized',
	array(
		'two_hrefs_rendered'          => count( $hostile_href_values ) >= 2,
		'no_javascript_href'          => ! $hrefs_have_javascript,
		'no_javascript_anywhere'      => false === stripos( $hostile_url_output, 'javascript:' ),
		'bad_protocol_href_emptied'   => false !== strpos( $hostile_url_output, 'href=""' ),
		'no_double_quote_breakout'    => false === strpos( $hostile_url_output, '" onmouseover' ),
		'quote_stripped_not_breaking' => false !== strpos( $hostile_url_output, 'href="https://x/%20onmouseover=alert(1)%20x="' ),
	)
);

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

foreach ( $report['cases'] as $case ) {
	if ( ! $case['passed'] ) {
		exit( 1 );
	}
}
