<?php
/**
 * Execute the Oscars late-CSS route guard from both theme loading paths.
 */

$root = dirname( __DIR__, 2 );

$GLOBALS['lunara_oscars_late_css_test_state'] = array(
	'admin'      => false,
	'feed'       => false,
	'query_vars' => array(),
	'printed'    => array(),
);

function is_admin() {
	return $GLOBALS['lunara_oscars_late_css_test_state']['admin'];
}

function is_feed() {
	return $GLOBALS['lunara_oscars_late_css_test_state']['feed'];
}

function get_query_var( $key ) {
	return $GLOBALS['lunara_oscars_late_css_test_state']['query_vars'][ $key ] ?? '';
}

function lunara_print_cacheable_stylesheet( $handle, $path ) {
	$GLOBALS['lunara_oscars_late_css_test_state']['printed'][] = array( $handle, $path );
}

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

$implementations = array(
	'split'    => $root . '/inc/setup.php',
	'fallback' => $root . '/functions.php',
);

foreach ( $implementations as $name => $path ) {
	$function_source = lunara_extract_named_function(
		$path,
		'lunara_print_late_oscars_guardrail_styles',
		'lunara_print_late_oscars_guardrail_styles_' . $name
	);
	eval( $function_source );
}

$cases = array(
	'empty-vars' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array(),
		'expected'   => array(),
	),
	'portal' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array(),
		'expected'   => array(),
	),
	'entity-with-id' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array(
			'aat_entity'    => 'title',
			'aat_entity_id' => 'tt0110912',
		),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'entity-without-id' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_entity' => 'title' ),
		'expected'   => array(),
	),
	'hub-ceremony' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_hub' => 'ceremony', 'aat_hub_id' => '96' ),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'hub-category' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_hub' => 'category', 'aat_hub_id' => 'best-picture' ),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'hub-ceremonies-index' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_hub' => 'ceremonies' ),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'hub-categories-index' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_hub' => 'categories' ),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'hub-about' => array(
		'admin'      => false,
		'feed'       => false,
		'query_vars' => array( 'aat_hub' => 'about' ),
		'expected'   => array(
			array( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' ),
		),
	),
	'admin-entity' => array(
		'admin'      => true,
		'feed'       => false,
		'query_vars' => array( 'aat_entity' => 'person', 'aat_entity_id' => '123' ),
		'expected'   => array(),
	),
	'feed-hub' => array(
		'admin'      => false,
		'feed'       => true,
		'query_vars' => array( 'aat_hub' => 'about' ),
		'expected'   => array(),
	),
);

$report = array(
	'split_fallback_parity' => true,
	'cases'                 => array(),
);

foreach ( $cases as $case_name => $case ) {
	$outputs = array();

	foreach ( array_keys( $implementations ) as $implementation ) {
		$GLOBALS['lunara_oscars_late_css_test_state'] = array(
			'admin'      => $case['admin'],
			'feed'       => $case['feed'],
			'query_vars' => $case['query_vars'],
			'printed'    => array(),
		);

		$function_name = 'lunara_print_late_oscars_guardrail_styles_' . $implementation;
		$function_name();
		$outputs[ $implementation ] = $GLOBALS['lunara_oscars_late_css_test_state']['printed'];
	}

	$parity = $outputs['split'] === $outputs['fallback'];
	$passed = $parity && $outputs['split'] === $case['expected'];

	$report['split_fallback_parity'] = $report['split_fallback_parity'] && $parity;
	$report['cases'][ $case_name ]    = array(
		'passed'   => $passed,
		'expected' => $case['expected'],
		'split'    => $outputs['split'],
		'fallback' => $outputs['fallback'],
	);
}

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

foreach ( $report['cases'] as $case ) {
	if ( ! $case['passed'] ) {
		exit( 1 );
	}
}
