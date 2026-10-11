<?php
/**
 * Theme 3.2.101: the font diet.
 *
 * Run: php tests/fonts-runtime.php
 *
 * Measured on 2026-10-08: every page loaded 214-357 KB of fonts in 5-8 files,
 * and 2-4 of them were TTFs. The TTFs came from WordPress core's Font Library
 * (the `<style class="wp-fonts-local">` block), which declared Tiempos Text,
 * Tiempos Headline, GT Sectra and Canela a second time, from
 * /wp-content/uploads/fonts/*.ttf, on top of the theme's own woff2 faces. The
 * three label-face preloads also pointed at a cut the diet drops.
 *
 * The contract:
 *   1. inc/fonts.php unhooks the Font Library's front-end output. The theme is
 *      the only source of @font-face.
 *   2. style.css declares Tiempos as four woff2 files only: Text Regular, Text
 *      Italic, Text Semibold, Headline Semibold. No .ttf anywhere.
 *   3. The two kept weights carry ranges, so every weight the CSS asks for
 *      resolves to a real face and nothing is synthesized.
 *   4. Each Tiempos family has a metric-matched Georgia fallback face
 *      (size-adjust + ascent/descent overrides), and the stacks in
 *      inc/design-tokens.php and style.css name it right after the family.
 *   5. No preload points at a file the CSS no longer declares.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

// ---------------------------------------------------------------------------
// Minimal WordPress surface: record what inc/fonts.php hooks and unhooks.
// ---------------------------------------------------------------------------
$GLOBALS['fonts_test'] = array( 'added' => array(), 'removed' => array(), 'is_admin' => false );
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['fonts_test']['added'][] = array( $hook, $callback, $priority ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['fonts_test']['added'][] = array( $hook, $callback, $priority ); }
function remove_action( $hook, $callback, $priority = 10 ) { $GLOBALS['fonts_test']['removed'][] = array( $hook, $callback, $priority ); return true; }
function is_admin() { return $GLOBALS['fonts_test']['is_admin']; }
function apply_filters( $hook, $value ) { return $value; }

require ABSPATH . 'inc/fonts.php';

// 1. The Font Library's front-end output is unhooked, at the point WordPress adds it (wp_head, priority 50).
lunara_test_assert( function_exists( 'lunara_fonts_unhook_font_library' ), 'inc/fonts.php defines lunara_fonts_unhook_font_library().' );
$registered = array_filter( $GLOBALS['fonts_test']['added'], function ( $a ) { return 'init' === $a[0] && 'lunara_fonts_unhook_font_library' === $a[1]; } );
lunara_test_assert( ! empty( $registered ), 'lunara_fonts_unhook_font_library() runs on init.' );
lunara_fonts_unhook_font_library();
$removed = array_map( function ( $r ) { return $r[0] . ':' . $r[1] . ':' . $r[2]; }, $GLOBALS['fonts_test']['removed'] );
lunara_test_assert( in_array( 'wp_head:wp_print_font_faces:50', $removed, true ), 'wp_print_font_faces is removed from wp_head at priority 50.' );
lunara_test_assert( in_array( 'wp_head:wp_print_font_faces_from_style_variations:50', $removed, true ), 'wp_print_font_faces_from_style_variations is removed from wp_head at priority 50.' );
$GLOBALS['fonts_test']['removed']  = array();
$GLOBALS['fonts_test']['is_admin'] = true;
lunara_fonts_unhook_font_library();
lunara_test_assert( empty( $GLOBALS['fonts_test']['removed'] ), 'The editor keeps its Font Library faces; only the front end is unhooked.' );

// 2. style.css declares exactly the four Tiempos woff2 files, and no .ttf anywhere.
$css = (string) file_get_contents( ABSPATH . 'style.css' );
lunara_test_assert( false === stripos( $css, '.ttf' ), 'style.css references no .ttf file.' );
preg_match_all( '/@font-face\s*\{([^}]*)\}/s', $css, $faces );
$tiempos = array();
foreach ( $faces[1] as $decl ) {
	if ( ! preg_match( '/font-family:\s*[\'"]?(Tiempos (?:Text|Headline))[\'"]?\s*;/', $decl, $fam ) ) {
		continue;
	}
	preg_match( '/url\([\'"]?([^\'")]+)/', $decl, $src );
	preg_match( '/font-weight:\s*([^;]+);/', $decl, $weight );
	preg_match( '/font-style:\s*([^;]+);/', $decl, $style );
	$tiempos[] = array( 'family' => $fam[1], 'file' => basename( $src[1] ?? '' ), 'weight' => trim( $weight[1] ?? '' ), 'style' => trim( $style[1] ?? 'normal' ), 'decl' => $decl );
}
$files = array_column( $tiempos, 'file' );
sort( $files );
lunara_test_assert(
	$files === array( 'TiemposText-Italic.woff2', 'TiemposText-Regular.woff2', 'TiemposText-Semibold.woff2', 'hinted-TiemposHeadline-Semibold.woff2' ),
	'Tiempos is exactly four woff2 files: Text Regular, Text Italic, Text Semibold, Headline Semibold (got ' . implode( ', ', $files ) . ').'
);

// 3. Weight ranges: Text Semibold answers 600-900, Headline Semibold answers 400-900.
foreach ( $tiempos as $face ) {
	if ( 'TiemposText-Semibold.woff2' === $face['file'] ) {
		lunara_test_assert( '600 900' === $face['weight'], 'Text Semibold is declared for weights 600-900 (got "' . $face['weight'] . '").' );
	}
	if ( 'hinted-TiemposHeadline-Semibold.woff2' === $face['file'] ) {
		lunara_test_assert( '400 900' === $face['weight'], 'Headline Semibold is declared for weights 400-900 (got "' . $face['weight'] . '").' );
	}
	lunara_test_assert( false !== strpos( $face['decl'], 'font-display: swap' ), $face['file'] . ' keeps font-display: swap.' );
}

// 4. Metric-matched fallbacks exist and the stacks name them right after the family.
foreach ( array( 'Tiempos Text Fallback', 'Tiempos Headline Fallback' ) as $fallback ) {
	$found = false;
	foreach ( $faces[1] as $decl ) {
		if ( false === strpos( $decl, "'" . $fallback . "'" ) && false === strpos( $decl, '"' . $fallback . '"' ) ) {
			continue;
		}
		$found = true;
		lunara_test_assert( preg_match( '/src:\s*local\(/', $decl ), $fallback . ' is a local() face (Georgia), never a download.' );
		lunara_test_assert( preg_match( '/size-adjust:\s*1\d\d(\.\d+)?%/', $decl ), $fallback . ' carries a size-adjust in the 100-199% range.' );
		lunara_test_assert( preg_match( '/ascent-override:\s*\d/', $decl ) && preg_match( '/descent-override:\s*\d/', $decl ), $fallback . ' carries ascent and descent overrides.' );
	}
	lunara_test_assert( $found, $fallback . ' is declared in style.css.' );
}
$tokens = (string) file_get_contents( ABSPATH . 'inc/design-tokens.php' );
lunara_test_assert( false !== strpos( $tokens, '"Tiempos Text", "Tiempos Text Fallback", Georgia' ), 'The tiempos-text stack names the fallback right after the family.' );
lunara_test_assert( false !== strpos( $tokens, '"Tiempos Headline", "Tiempos Headline Fallback", "Tiempos Text"' ), 'The tiempos-headline stack names the fallback right after the family.' );
lunara_test_assert( false !== strpos( $css, '--lunara-font-display: "Tiempos Headline", "Tiempos Headline Fallback"' ), 'The style.css default display stack names the fallback.' );

// 5. No preload points at a file the CSS does not declare.
$frontend = (string) file_get_contents( ABSPATH . 'inc/frontend.php' );
preg_match_all( '#lunara-fonts/v1/([A-Za-z0-9_.-]+\.woff2)#', $frontend, $preloads );
$declared = array_merge( $files, array( 'bebas-neue-latin-400.woff2' ) );
foreach ( array_unique( $preloads[1] ) as $file ) {
	lunara_test_assert( in_array( $file, $declared, true ), "inc/frontend.php preloads {$file}, which style.css no longer declares." );
}

echo "fonts-runtime: all assertions passed.\n";
