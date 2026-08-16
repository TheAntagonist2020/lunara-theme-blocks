<?php
/**
 * CLI adapter for the pure Journal archive head-CSS composers.
 */

define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

require dirname( __DIR__ ) . '/inc/journal-archive-critical.php';

$payload = isset( $argv[1] ) ? json_decode( base64_decode( (string) $argv[1] ), true ) : array();
$config  = array(
	'presentation' => array(
		'density'          => isset( $payload['density'] ) ? $payload['density'] : 'editorial',
		'lead_prominence'  => isset( $payload['lead_prominence'] ) ? $payload['lead_prominence'] : 'standard',
		'desk_rhythm'      => isset( $payload['desk_rhythm'] ) ? $payload['desk_rhythm'] : 'balanced',
		'section_gap'      => isset( $payload['section_gap'] ) ? $payload['section_gap'] : 38,
		'hero_min_height'  => isset( $payload['hero_min_height'] ) ? $payload['hero_min_height'] : 240,
		'card_min_height'  => isset( $payload['card_min_height'] ) ? $payload['card_min_height'] : 390,
		'media_min_height' => isset( $payload['media_min_height'] ) ? $payload['media_min_height'] : 220,
	),
);

echo json_encode(
	array(
		'variables' => lunara_journal_archive_variable_css( $config ),
		'seed'      => lunara_journal_archive_critical_css(),
	)
);
