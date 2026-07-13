<?php
/**
 * Execute the full-spoiler runtime loader from both theme loading paths.
 */

$root = dirname( __DIR__, 2 );

$GLOBALS['lunara_spoiler_runtime_test_state'] = array(
	'admin'         => false,
	'feed'          => false,
	'singular_type' => '',
	'post_id'       => 0,
	'full_spoiler'  => false,
	'asset_exists'  => true,
	'scripts'       => array(),
	'script_data'   => array(),
);

function is_admin() {
	return $GLOBALS['lunara_spoiler_runtime_test_state']['admin'];
}

function is_feed() {
	return $GLOBALS['lunara_spoiler_runtime_test_state']['feed'];
}

function is_singular( $post_type = '' ) {
	$singular_type = $GLOBALS['lunara_spoiler_runtime_test_state']['singular_type'];
	return '' === $post_type ? '' !== $singular_type : $post_type === $singular_type;
}

function get_queried_object_id() {
	return $GLOBALS['lunara_spoiler_runtime_test_state']['post_id'];
}

function lunara_is_full_spoiler_review( $post_id ) {
	return $post_id === $GLOBALS['lunara_spoiler_runtime_test_state']['post_id']
		&& $GLOBALS['lunara_spoiler_runtime_test_state']['full_spoiler'];
}

function lunara_resolve_theme_asset( $relative_path ) {
	if ( ! $GLOBALS['lunara_spoiler_runtime_test_state']['asset_exists'] ) {
		return array();
	}

	return array(
		'path' => '/theme/' . $relative_path,
		'uri'  => 'https://theme.test/' . $relative_path,
	);
}

function lunara_theme_asset_version( $path ) {
	return 'test-' . basename( $path );
}

function wp_enqueue_script( $handle, $src, $dependencies, $version, $in_footer ) {
	$GLOBALS['lunara_spoiler_runtime_test_state']['scripts'][] = array(
		$handle,
		$src,
		$dependencies,
		$version,
		$in_footer,
	);
}

function wp_script_add_data( $handle, $key, $value ) {
	$GLOBALS['lunara_spoiler_runtime_test_state']['script_data'][] = array( $handle, $key, $value );
}

function lunara_extract_spoiler_runtime_function( $path, $replacement_name, $function_name ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		throw new RuntimeException( 'Unable to read ' . $path );
	}

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
	$function_source = lunara_extract_spoiler_runtime_function(
		$path,
		'lunara_enqueue_review_spoiler_gate_runtime_' . $name,
		'lunara_enqueue_review_spoiler_gate_runtime'
	);
	eval( $function_source );

	$exclusion_source = lunara_extract_spoiler_runtime_function(
		$path,
		'lunara_review_spoiler_gate_delay_exclusions_' . $name,
		'lunara_review_spoiler_gate_delay_exclusions'
	);
	eval( $exclusion_source );
}

$expected_script = array(
	array(
		'lunara-review-spoiler-gate',
		'https://theme.test/assets/js/lunara-review-spoiler-gate.js',
		array(),
		'test-lunara-review-spoiler-gate.js',
		true,
	),
);
$expected_data = array(
	array( 'lunara-review-spoiler-gate', 'strategy', 'defer' ),
);

$cases = array(
	'home' => array( false, false, '', 0, false, true, array(), array() ),
	'ordinary-review' => array( false, false, 'review', 101, false, true, array(), array() ),
	'full-spoiler-review' => array( false, false, 'review', 102, true, true, $expected_script, $expected_data ),
	'journal-single' => array( false, false, 'journal', 103, true, true, array(), array() ),
	'admin-full-spoiler' => array( true, false, 'review', 104, true, true, array(), array() ),
	'feed-full-spoiler' => array( false, true, 'review', 105, true, true, array(), array() ),
	'missing-post-id' => array( false, false, 'review', 0, true, true, array(), array() ),
	'missing-asset' => array( false, false, 'review', 106, true, false, array(), array() ),
);

$report = array(
	'split_fallback_parity' => true,
	'exclusion_parity'      => true,
	'cases'                 => array(),
);

$exclusion_outputs = array();
foreach ( array_keys( $implementations ) as $implementation ) {
	$function_name = 'lunara_review_spoiler_gate_delay_exclusions_' . $implementation;
	$exclusion_outputs[ $implementation ] = array(
		'array' => $function_name( array( 'existing.js', 'lunara-review-spoiler-gate.js' ) ),
		'null'  => $function_name( null ),
	);
}
$report['exclusion_parity'] = $exclusion_outputs['split'] === $exclusion_outputs['fallback']
	&& $exclusion_outputs['split']['array'] === array( 'existing.js', 'lunara-review-spoiler-gate.js' )
	&& $exclusion_outputs['split']['null'] === array( 'lunara-review-spoiler-gate.js' );
$report['exclusions'] = $exclusion_outputs;

foreach ( $cases as $case_name => $case ) {
	list( $admin, $feed, $singular_type, $post_id, $full_spoiler, $asset_exists, $expected_scripts, $expected_data_for_case ) = $case;
	$outputs = array();

	foreach ( array_keys( $implementations ) as $implementation ) {
		$GLOBALS['lunara_spoiler_runtime_test_state'] = array(
			'admin'         => $admin,
			'feed'          => $feed,
			'singular_type' => $singular_type,
			'post_id'       => $post_id,
			'full_spoiler'  => $full_spoiler,
			'asset_exists'  => $asset_exists,
			'scripts'       => array(),
			'script_data'   => array(),
		);

		$function_name = 'lunara_enqueue_review_spoiler_gate_runtime_' . $implementation;
		$function_name();
		$outputs[ $implementation ] = array(
			'scripts'     => $GLOBALS['lunara_spoiler_runtime_test_state']['scripts'],
			'script_data' => $GLOBALS['lunara_spoiler_runtime_test_state']['script_data'],
		);
	}

	$parity = $outputs['split'] === $outputs['fallback'];
	$passed = $parity
		&& $outputs['split']['scripts'] === $expected_scripts
		&& $outputs['split']['script_data'] === $expected_data_for_case;

	$report['split_fallback_parity'] = $report['split_fallback_parity'] && $parity;
	$report['cases'][ $case_name ]    = array(
		'passed'   => $passed,
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

if ( ! $report['exclusion_parity'] ) {
	exit( 1 );
}
