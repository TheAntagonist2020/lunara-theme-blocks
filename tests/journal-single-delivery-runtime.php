<?php
/** Exercise real route output and optimization boundaries with the article fixture. */
ob_start();
require __DIR__ . '/article-layout-runtime.php';
$cases = json_decode( ob_get_clean(), true, 512, JSON_THROW_ON_ERROR );
$checks = 0;
function journal_delivery_assert( $condition, $message ) {
    ++$GLOBALS['checks'];
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
foreach ( array( 'lunara_keep_journal_single_css_direct', 'lunara_rocket_preserve_journal_single_css' ) as $name ) {
    article_fixture_function( $root . '/inc/frontend.php', $name );
}
$asset = lunara_resolve_theme_asset( 'assets/css/lunara-journal-single.css' );
journal_delivery_assert( is_file( $asset['path'] ) && filesize( $asset['path'] ) > 10000, 'Required Journal geometry asset resolves from the theme.' );
$version = lunara_theme_asset_version( $asset['path'] );
foreach ( $cases as $name => $case ) {
    $output = $case['authority'];
    if ( 'journal' !== $case['type'] ) {
        journal_delivery_assert( '' === $output, "$name must not load Journal CSS." );
        continue;
    }
    journal_delivery_assert( 1 === substr_count( $output, 'rel="stylesheet"' ), "$name prints one real stylesheet link." );
    journal_delivery_assert( false !== strpos( $output, esc_attr( $asset['uri'] . '?ver=' . $version ) ), "$name uses a versioned public asset." );
    journal_delivery_assert( false !== strpos( $output, 'media="all"' ) && false === strpos( $output, 'onload=' ), "$name must remain synchronous without JavaScript." );
    journal_delivery_assert( strpos( $output, '<link ' ) < strpos( $output, '<style ' ) && strlen( $output ) < 850, "$name keeps the late cascade position with only small candidate variables inline." );
}
foreach ( array( true, false ) as $decision ) {
    journal_delivery_assert( false === lunara_keep_journal_single_css_direct( $decision, 'lunara-journal-single' ), 'Journal style must stay out of aggregation and async delivery.' );
    journal_delivery_assert( $decision === lunara_keep_journal_single_css_direct( $decision, 'unrelated-style' ), 'Unrelated stylesheet decisions remain unchanged.' );
}
$exclusions = lunara_rocket_preserve_journal_single_css( array( 'existing.css', 'lunara-journal-single.css' ) );
journal_delivery_assert( array( 'existing.css', 'lunara-journal-single.css' ) === $exclusions, 'Rocket exclusions preserve existing entries without duplicates.' );
journal_delivery_assert( array( 'lunara-journal-single.css' ) === lunara_rocket_preserve_journal_single_css( null ), 'Missing exclusion arrays are handled safely.' );
$source = file_get_contents( $root . '/inc/frontend.php' );
foreach ( array( 'css_do_concat', 'jetpack_boost_async_style' ) as $hook ) {
    journal_delivery_assert( false !== strpos( $source, "add_filter( '$hook', 'lunara_keep_journal_single_css_direct', 10, 2 )" ), "$hook must bind the exercised boundary." );
}
journal_delivery_assert( false !== strpos( $source, "add_action( 'wp_head', 'lunara_output_journal_single_guardrail_css', 101 )" ), 'The original head ordering is retained.' );
journal_delivery_assert( false !== strpos( $source, "add_filter( 'rocket_rucss_external_exclusions', 'lunara_rocket_preserve_journal_single_css' )" ), 'The Rocket exclusion must bind the exercised function.' );
echo "Journal article CSS delivery: $checks checks passed.\n";
