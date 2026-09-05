<?php
/**
 * Execute inc/oscars-portal-dynamic.php against stubs and report JSON cases.
 *
 * The module is pure PHP behind function_exists guards, so it is required
 * directly: the code under test is the code that ships. Only the escaping
 * and i18n primitives are stubbed; no WordPress bootstraps here.
 */

$root = dirname( __DIR__, 2 );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/' );
}

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { $v = (string) $value; return preg_match( '/^(https?:\/\/|\/|#)/i', $v ) ? htmlspecialchars( $v, ENT_QUOTES, 'UTF-8' ) : ''; }
function __( $v, $d = '' ) { return $v; }
function esc_html__( $v, $d = '' ) { return esc_html( $v ); }
function esc_attr__( $v, $d = '' ) { return esc_attr( $v ); }

require $root . '/inc/oscars-portal-dynamic.php';

$report = array( 'cases' => array() );
$record = static function ( $name, $checks ) use ( &$report ) {
	$passed = true;
	foreach ( $checks as $check ) {
		$passed = $passed && (bool) $check;
	}
	$report['cases'][ $name ] = array( 'passed' => $passed, 'checks' => $checks );
};

$record(
	'sanitize-date',
	array(
		'valid_kept'       => '2027-03-14' === lunara_oscars_sanitize_ceremony_date( ' 2027-03-14 ' ),
		'impossible_empty' => '' === lunara_oscars_sanitize_ceremony_date( '2027-02-30' ),
		'garbage_empty'    => '' === lunara_oscars_sanitize_ceremony_date( '14/03/2027' ),
		'array_empty'      => '' === lunara_oscars_sanitize_ceremony_date( array( '2027-03-14' ) ),
	)
);

$now   = gmmktime( 12, 0, 0, 9, 5, 2026 ); // 2026-09-05.
$clock = static function ( $date ) use ( $now ) {
	return lunara_oscars_season_clock( $date, $now );
};
$record(
	'season-clock-phases',
	array(
		'unset_empty'   => array() === $clock( '' ),
		'countdown'     => 'countdown' === ( $clock( '2027-03-14' )['phase'] ?? '' ) && 190 === ( $clock( '2027-03-14' )['days'] ?? 0 ),
		'season'        => 'season' === ( $clock( '2026-11-14' )['phase'] ?? '' ),
		'final'         => 'final' === ( $clock( '2026-09-20' )['phase'] ?? '' ),
		'tonight'       => 'tonight' === ( $clock( '2026-09-05' )['phase'] ?? '' ) && 0 === ( $clock( '2026-09-05' )['days'] ?? 1 ),
		'settled'       => 'settled' === ( $clock( '2026-09-01' )['phase'] ?? '' ) && -4 === ( $clock( '2026-09-01' )['days'] ?? 0 ),
		'retired_empty' => array() === $clock( '2026-08-01' ),
		'date_label'    => 'March 14, 2027' === ( $clock( '2027-03-14' )['date_label'] ?? '' ),
	)
);

$clock_html = lunara_oscars_render_season_clock( $clock( '2027-03-14' ) );
$record(
	'season-clock-render',
	array(
		'empty_for_empty'  => '' === lunara_oscars_render_season_clock( array() ),
		'carries_data_iso' => false !== strpos( $clock_html, 'data-lunara-season-clock="2027-03-14"' ),
		'carries_days'     => false !== strpos( $clock_html, '<strong class="lunara-oscars-season-days" data-lunara-season-days>190</strong>' ),
		'carries_phase'    => false !== strpos( $clock_html, 'is-phase-countdown' ),
		'tonight_copy'     => false !== strpos( lunara_oscars_render_season_clock( $clock( '2026-09-05' ) ), '>Tonight<' ),
		'settled_copy'     => false !== strpos( lunara_oscars_render_season_clock( $clock( '2026-09-01' ) ), 'days since the ceremony' ),
	)
);

$rows = array(
	array( 'status' => 'won' ),
	array( 'status' => 'front_runner' ),
	array( 'status' => 'front_runner' ),
	array( 'status' => 'contender' ),
	array( 'status' => '' ),
	array( 'status' => 'bogus' ),
	'not-a-row',
);
$summary = lunara_oscars_board_summary( $rows );
$record(
	'board-summary',
	array(
		'empty_for_empty'   => array() === lunara_oscars_board_summary( array() ),
		'total_counts_rows' => 6 === ( $summary['total'] ?? 0 ),
		'canonical_order'   => array( 'front_runner', 'contender', 'won' ) === array_column( $summary['chips'] ?? array(), 'status' ),
		'counts'            => array( 2, 1, 1 ) === array_column( $summary['chips'] ?? array(), 'count' ),
		'label_fallback'    => 'Front Runner' === lunara_oscars_board_status_label( 'front_runner' ),
	)
);

$summary_html = lunara_oscars_render_board_summary( $summary, 'Revised <Sep 5>' );
$record(
	'board-summary-render',
	array(
		'empty_for_empty' => '' === lunara_oscars_render_board_summary( array() ),
		'total_chip'      => false !== strpos( $summary_html, 'is-total"><strong>6</strong> calls' ),
		'status_chip'     => false !== strpos( $summary_html, 'is-status-front_runner"><strong>2</strong> Front Runner' ),
		'revised_escaped' => false !== strpos( $summary_html, 'Revised &lt;Sep 5&gt;' ),
		'no_revised_chip' => false === strpos( lunara_oscars_render_board_summary( $summary ), 'is-revised' ),
	)
);

$offsets = array();
for ( $day = 0; $day < 366; $day++ ) {
	$offsets[] = lunara_oscars_todays_pull_offset( $day, 2026, 1234 );
}
$record(
	'todays-pull-offset',
	array(
		'in_range'       => 0 === count( array_filter( $offsets, static function ( $o ) { return $o < 0 || $o >= 1234; } ) ),
		'deterministic'  => lunara_oscars_todays_pull_offset( 40, 2026, 1234 ) === lunara_oscars_todays_pull_offset( 40, 2026, 1234 ),
		'varies_by_day'  => count( array_unique( $offsets ) ) > 300,
		'varies_by_year' => lunara_oscars_todays_pull_offset( 40, 2026, 1234 ) !== lunara_oscars_todays_pull_offset( 40, 2027, 1234 ),
		'zero_count'     => 0 === lunara_oscars_todays_pull_offset( 40, 2026, 0 ),
	)
);

$pull = array(
	'ceremony_label' => '47th Academy Awards',
	'ceremony_url'   => 'https://example.test/oscars/ceremony/47/',
	'year_label'     => '1974',
	'category'       => 'Actress in a Leading Role',
	'category_url'   => 'https://example.test/oscars/category/actress/',
	'film'           => 'Alice <Doesn\'t> Live Here Anymore',
	'film_url'       => 'https://example.test/oscars/title/tt0071115/',
	'name'           => 'Ellen Burstyn',
	'name_url'       => 'https://example.test/oscars/person/nm0000994/',
);
$pull_html = lunara_oscars_render_todays_pull( $pull );
$record(
	'todays-pull-render',
	array(
		'empty_for_empty' => '' === lunara_oscars_render_todays_pull( array() ),
		'film_escaped'    => false !== strpos( $pull_html, 'Alice &lt;Doesn&#039;t&gt; Live Here Anymore' ),
		'film_link'       => false !== strpos( $pull_html, 'href="https://example.test/oscars/title/tt0071115/"' ),
		'person_link'     => false !== strpos( $pull_html, 'href="https://example.test/oscars/person/nm0000994/"' ),
		'no_script'       => false === strpos( $pull_html, '<script' ),
		'no_name_ok'      => false === strpos( lunara_oscars_render_todays_pull( array_merge( $pull, array( 'name' => '', 'name_url' => '' ) ) ), 'lunara-oscars-todays-pull-person' ),
	)
);

echo json_encode( $report, JSON_UNESCAPED_SLASHES );
