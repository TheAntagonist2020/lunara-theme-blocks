<?php
/**
 * Standalone null-vs-zero regression for private Dispatch telemetry.
 *
 * Run: php tests/control-desk-automation-runtime.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( $text ) {
    return $text;
}

function number_format_i18n( $number, $decimals = 0 ) {
    return number_format( (float) $number, (int) $decimals, '.', ',' );
}

function absint( $value ) {
    return abs( (int) $value );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
    return esc_html( $text );
}

function esc_html__( $text ) {
    return esc_html( $text );
}

function esc_html_e( $text ) {
    echo esc_html( $text );
}

require_once dirname( __DIR__ ) . '/inc/control-desk-automation.php';

$legacy = array(
    'active'   => true,
    'last_run' => array(
        'timestamp_gmt' => '2026-08-13 20:00:00',
        'success'       => true,
        'message'       => 'Legacy report completed.',
        'created'       => 0,
        'imported'      => 0,
    ),
);

ob_start();
lunara_control_desk_render_dispatch_telemetry( $legacy );
$legacy_html = ob_get_clean();

if ( false !== strpos( $legacy_html, '$0.0000' ) || false !== strpos( $legacy_html, '0 in · 0 cached · 0 out' ) ) {
    fwrite( STDERR, "Legacy report was falsely presented as zero-cost, zero-token telemetry.\n" );
    exit( 1 );
}

if ( false === strpos( $legacy_html, 'Not reported' ) ) {
    fwrite( STDERR, "Legacy report did not render the honest Not reported fallback.\n" );
    exit( 1 );
}

$reported_zero = array(
    'active'  => true,
    'runtime' => array(
        'provider'          => 'openai',
        'model'             => 'gpt-5.4-mini',
        'max_output_tokens' => 2200,
        'source_budget'     => 3,
    ),
    'last_run' => array(
        'timestamp_gmt'          => '2026-08-14 20:00:00',
        'success'                => true,
        'message'                => 'Safe fallback completed.',
        'provider'               => 'openai',
        'requested_model'        => 'gpt-4.1',
        'effective_model'        => 'gpt-5.4-mini',
        'usage_reported'         => true,
        'input_tokens'           => 0,
        'cached_input_tokens'    => 0,
        'output_tokens'          => 0,
        'estimated_cost_usd'     => 0,
        'processed_source_items' => 0,
        'deferred_source_items'  => 0,
        'fallback_used'          => true,
        'source_packet_drafts'   => 0,
        'error_code'             => 'ai_billing_error',
    ),
);

ob_start();
lunara_control_desk_render_dispatch_telemetry( $reported_zero );
$zero_html = ob_get_clean();

foreach ( array( '$0.0000', '0 in · 0 cached · 0 out', '0 processed · 0 deferred', 'Safe source packets', 'gpt-4.1 → gpt-5.4-mini' ) as $expected ) {
    if ( false === strpos( $zero_html, $expected ) ) {
        fwrite( STDERR, "Reported-zero telemetry omitted expected value: {$expected}\n" );
        exit( 1 );
    }
}

$partial_usage = $reported_zero;
$partial_usage['last_run']['input_tokens']        = null;
$partial_usage['last_run']['cached_input_tokens'] = null;
$partial_usage['last_run']['output_tokens']       = null;
$partial_usage['last_run']['estimated_cost_usd']  = null;

ob_start();
lunara_control_desk_render_dispatch_telemetry( $partial_usage );
$partial_html = ob_get_clean();

if ( false !== strpos( $partial_html, '0 in · 0 cached · 0 out' ) || false !== strpos( $partial_html, '$0.0000' ) ) {
    fwrite( STDERR, "Partial usage telemetry converted null token or cost fields to zero.\n" );
    exit( 1 );
}

if ( false === strpos( $partial_html, 'Not reported in · Not reported cached · Not reported out' ) ) {
    fwrite( STDERR, "Partial usage telemetry did not preserve each null token field as Not reported.\n" );
    exit( 1 );
}

echo "Control Desk Dispatch telemetry runtime regression passed.\n";
