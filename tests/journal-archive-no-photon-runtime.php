<?php
/** Verify the responsive fallback fails closed when Jetpack Photon is absent. */
define( 'ABSPATH', __DIR__ . '/' );
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( (string) $url, $component ); }
function esc_url_raw( $url ) { return (string) $url; }
require dirname( __DIR__ ) . '/inc/journal-archive-media.php';
if ( '' !== lunara_journal_archive_cdn_srcset( 'https://example.test/wp-content/uploads/no-photon.jpg', 1800, 1200 ) ) {
	fwrite( STDERR, "FAIL: Missing Photon must not produce guessed candidates.\n" );
	exit( 1 );
}
fwrite( STDOUT, "Journal archive no-Photon fallback passed.\n" );
