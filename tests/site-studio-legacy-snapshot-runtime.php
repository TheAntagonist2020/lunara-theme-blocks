<?php
/** Exact historical snapshots preserve newer ownership without accepting partial saves. */
define( 'LUNARA_METHOD_BOOTSTRAP_ONLY', true );
require __DIR__ . '/site-studio-pilot-runtime.php';

$checks = 0;
function snapshot_check( $condition, $message ) {
	global $checks;
	if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
	++$checks;
}

foreach ( array(
	'footer' => array( array( 'logo', 'tagline', 'column1', 'column2', 'column3', 'copyright' ), array( 'navigation' ) ),
	'search' => array( array( 'density', 'treatment', 'media', 'prominence', 'lead', 'spotlight', 'gap', 'height', 'grid' ), array( 'kicker', 'title', 'words', 'enabled' ) ),
) as $surface => $family ) {
	list( $legacy_keys, $new_keys ) = $family;
	$keys = array_merge( $legacy_keys, $new_keys );
	$current = array(); $old = array();
	foreach ( $keys as $index => $key ) { $current[ $key ] = array( 'present' => $index % 2 === 0, 'value' => $index % 2 === 0 ? (string) $index : null ); }
	foreach ( $legacy_keys as $index => $key ) { $old[ $key ] = array( 'present' => true, 'value' => $index === 0 ? false : " Old {$index}\n" ); }
	$expected = $current;
	foreach ( $old as $key => $entry ) { $expected[ $key ] = $entry; }
	$before_current = $current; $before_old = $old;
	$result = lunara_site_studio_merge_legacy_mod_snapshot( $old, $current, $keys, $legacy_keys );
	snapshot_check( $result === $expected, "$surface exact old snapshot restores only historical values and retains newer raw types/absence" );
	snapshot_check( $current === $before_current && $old === $before_old, "$surface inputs are never mutated" );
	$full = $current;
	foreach ( $new_keys as $key ) { $full[ $key ] = array( 'present' => false, 'value' => null ); }
	snapshot_check( $full === lunara_site_studio_merge_legacy_mod_snapshot( $full, $current, $keys, $legacy_keys ), "$surface complete pre-adoption snapshot can remove newly adopted values" );
	$bad = $old; unset( $bad[ $legacy_keys[0] ] );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $bad, $current, $keys, $legacy_keys ), "$surface arbitrary partial snapshot denied" );
	$bad = $old; $bad['unowned'] = array( 'present' => true, 'value' => 'foreign' );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $bad, $current, $keys, $legacy_keys ), "$surface unknown entry denied" );
	$bad = $old; $bad[ $legacy_keys[0] ]['present'] = '1';
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $bad, $current, $keys, $legacy_keys ), "$surface presence must remain boolean" );
	$bad = $old; $bad[ $legacy_keys[0] ] = array( 'present' => false, 'value' => 'hidden' );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $bad, $current, $keys, $legacy_keys ), "$surface missing entry cannot conceal a value" );
	$bad = $current; unset( $bad[ $new_keys[0] ] );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $old, $bad, $keys, $legacy_keys ), "$surface current snapshot must cover newer ownership exactly" );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( array_reverse( $old, true ), $current, $keys, $legacy_keys ), "$surface wrong historical order denied" );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $old, $current, $keys, array_merge( $legacy_keys, array( 'unowned' ) ) ), "$surface historical ownership cannot exceed current scope" );
	snapshot_check( false === lunara_site_studio_merge_legacy_mod_snapshot( $old, $current, $keys, array_reverse( $legacy_keys ) ), "$surface misconfigured historical order denied" );
}
echo "Legacy snapshot compatibility: {$checks} checks passed.\n";
