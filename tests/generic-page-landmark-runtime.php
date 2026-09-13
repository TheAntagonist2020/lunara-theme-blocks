<?php
/** Render the generic page inside the real header/footer main landmark shell. */
require __DIR__ . '/site-studio-utility-recovery-fixture.php';
function have_posts() { return empty( $GLOBALS['generic_page_consumed'] ); }
function the_post() { $GLOBALS['generic_page_consumed'] = true; }
function post_class( $class ) { echo 'class="' . esc_attr( $class ) . '"'; }
function the_title() { echo 'Contact Lunara'; }
function the_content() { echo '<p>Editorial enquiries and contact details.</p>'; }
$html = utility_render( 'page.php' );
$dom = new DOMDocument();
$previous_errors = libxml_use_internal_errors( true );
$dom->loadHTML( $html );
libxml_clear_errors();
libxml_use_internal_errors( $previous_errors );
$xpath = new DOMXPath( $dom );
foreach ( array(
    'one main landmark' => $xpath->query( '//main' )->length === 1,
    'one complete page title' => $xpath->query( '//main//h1[text()="Contact Lunara"]' )->length === 1,
    'existing primary styling retained' => $xpath->query( '//main/div[@id="primary" and contains(@class,"lunara-archive-page")]' )->length === 1,
    'page body remains inside main' => $xpath->query( '//main//div[@class="entry-content"]/p' )->length === 1,
) as $message => $passed ) {
    if ( ! $passed ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}
echo "Generic page landmark: 4 checks passed.\n";
