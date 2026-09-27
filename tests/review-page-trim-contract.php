<?php
/**
 * Theme 3.2.94: single reviews drop the share card and the Browse Reviews /
 * Director Archive buttons. Reviews run in one 760px reading column (the
 * site's Additional CSS), so those buttons sat under the article as a
 * full-width row; the Related section already links the archive.
 *
 * Run: php tests/review-page-trim-contract.php
 */
$root = dirname( __DIR__ );
$read = static function ( $path ) use ( $root ) { return (string) file_get_contents( $root . '/' . $path ); };
$failures = array();
$check = static function ( $ok, $message ) use ( &$failures ) { if ( ! $ok ) { $failures[] = $message; } };

$template  = $read( 'single-review.php' );
$rendering = $read( 'inc/review-rendering.php' );
$frontend  = $read( 'inc/frontend.php' );
$css       = $read( 'assets/css/lunara-review-single.css' );
$custom    = $read( 'inc/customizer.php' );
$meta      = $read( 'inc/editorial-meta.php' );

// 1. The share card, its renderer, and its click handler are gone.
$check( false === strpos( $template, 'lunara_render_review_share_strip' ), 'The review template still renders the share card.' );
$check( false === strpos( $rendering, 'function lunara_render_review_share_strip' ), 'The share card renderer still exists.' );
$check( false === strpos( $frontend, 'lunara-review-share-strip-js' ) && false === strpos( $frontend, 'lunara_output_review_share_strip_script' ), 'The copy-link footer script still ships.' );
$check( false === strpos( $css, '.lunara-review-share-strip {' ) && false !== strpos( $css, '.sharedaddy.sd-sharing-enabled' ), 'Share card CSS remains, or the Jetpack sharing row is no longer hidden.' );

// 2. The rail buttons and their settings are gone; the archive link survives in Related.
$check( false === strpos( $template, 'lunara-review-single-rail-actions' ), 'The Browse Reviews / Director Archive buttons still render.' );
$check( false === strpos( $custom, 'lunara_review_archive_button' ) && false === strpos( $custom, 'lunara_review_director_button' ), 'Customizer still offers labels for removed buttons.' );
$check( false === strpos( $meta, 'Browse Reviews CTA Label' ), 'The review editor still offers a label for a removed button.' );
$check( 1 === substr_count( $template, 'esc_url( $archive_url )' ) && false !== strpos( $template, "'Open Reviews'" ), 'The Related section must keep the one archive link.' );

// 3. The director archive stays one click away, on the director's name in the hero.
$check( false !== strpos( $template, 'class="lunara-review-single-director-link" href="<?php echo esc_url( $director_url ); ?>"' ) && false !== strpos( $template, 'esc_html( $director )' ), 'The hero must link the director name to the director archive.' );
$check( false !== strpos( $css, 'a.lunara-review-single-director-link' ), 'The director link needs its hero styling.' );

// 4. No empty rail: it renders only with a Ledger or Dossier card to show.
$check( false !== strpos( $template, 'if ( $show_ledger_card || $dossier_movie_id > 0 ) :' ), 'The rail must not render as an empty box.' );

if ( $failures ) {
	fwrite( STDERR, "Review page trim contract failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}
echo "Review page trim contract passed.\n";
