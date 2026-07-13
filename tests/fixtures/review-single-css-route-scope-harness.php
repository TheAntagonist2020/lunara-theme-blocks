<?php
/**
 * Execute the Review-single CSS route guard from both theme loading paths.
 */

$root = dirname( __DIR__, 2 );

$GLOBALS['lunara_review_css_test_state'] = array(
	'admin'         => false,
	'feed'          => false,
	'singular_type' => '',
	'printed'       => array(),
);

function is_admin() {
	return $GLOBALS['lunara_review_css_test_state']['admin'];
}

function is_feed() {
	return $GLOBALS['lunara_review_css_test_state']['feed'];
}

function is_singular( $post_type = '' ) {
	$singular_type = $GLOBALS['lunara_review_css_test_state']['singular_type'];

	if ( '' === $post_type ) {
		return '' !== $singular_type;
	}

	return $post_type === $singular_type;
}

function lunara_print_cacheable_stylesheet( $handle, $path ) {
	$GLOBALS['lunara_review_css_test_state']['printed'][] = array( $handle, $path );
}

function lunara_extract_review_css_function( $path, $replacement_name ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		throw new RuntimeException( 'Unable to read ' . $path );
	}

	$function_name = 'lunara_print_review_single_guardrail_styles';
	$tokens        = token_get_all( $source );
	$count         = count( $tokens );

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
	$function_source = lunara_extract_review_css_function(
		$path,
		'lunara_print_review_single_guardrail_styles_' . $name
	);
	eval( $function_source );
}

$stylesheet = array(
	array( 'lunara-review-single-guardrails', 'assets/css/lunara-review-single-guardrails.css' ),
);

$cases = array(
	'home' => array(
		'admin'         => false,
		'feed'          => false,
		'singular_type' => '',
		'expected'      => array(),
	),
	'review-single' => array(
		'admin'         => false,
		'feed'          => false,
		'singular_type' => 'review',
		'expected'      => $stylesheet,
	),
	'journal-single' => array(
		'admin'         => false,
		'feed'          => false,
		'singular_type' => 'journal',
		'expected'      => array(),
	),
	'post-single' => array(
		'admin'         => false,
		'feed'          => false,
		'singular_type' => 'post',
		'expected'      => array(),
	),
	'reviews-archive' => array(
		'admin'         => false,
		'feed'          => false,
		'singular_type' => '',
		'expected'      => array(),
	),
	'admin-review' => array(
		'admin'         => true,
		'feed'          => false,
		'singular_type' => 'review',
		'expected'      => array(),
	),
	'feed-review' => array(
		'admin'         => false,
		'feed'          => true,
		'singular_type' => 'review',
		'expected'      => array(),
	),
);

$report = array(
	'split_fallback_parity' => true,
	'cases'                 => array(),
);

foreach ( $cases as $case_name => $case ) {
	$outputs = array();

	foreach ( array_keys( $implementations ) as $implementation ) {
		$GLOBALS['lunara_review_css_test_state'] = array(
			'admin'         => $case['admin'],
			'feed'          => $case['feed'],
			'singular_type' => $case['singular_type'],
			'printed'       => array(),
		);

		$function_name = 'lunara_print_review_single_guardrail_styles_' . $implementation;
		$function_name();
		$outputs[ $implementation ] = $GLOBALS['lunara_review_css_test_state']['printed'];
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
