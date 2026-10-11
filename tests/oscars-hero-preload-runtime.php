<?php
/**
 * Theme 3.2.102: the portal's hero backdrop is preloaded, and its posters say
 * how wide they render.
 *
 * Run: php tests/oscars-hero-preload-runtime.php
 *
 * The contract:
 *   1. On the portal route, wp_head prints one <link rel="preload" as="image"
 *      fetchpriority="high"> whose href is the backdrop URL the template
 *      paints (the Best Picture visual's backdrop, normalized to w780).
 *      Nothing is printed off the route, or when there is no backdrop.
 *   2. page-oscars.php takes its backdrop from the same resolver, so the two
 *      cannot drift.
 *   3. lunara_oscars_poster_sizes() replaces a poster's `sizes`, keeps the
 *      `auto, ` WordPress puts in front for lazy images, adds one when the
 *      <img> has a srcset but no sizes, and leaves an <img> without srcset
 *      alone.
 *   4. The hero card no longer paints an inline background-image behind a
 *      poster <img> that covers it.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

// ---------------------------------------------------------------------------
// WordPress surface.
// ---------------------------------------------------------------------------
$GLOBALS['t'] = array( 'actions' => array(), 'portal' => true, 'snapshot' => array() );
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['t']['actions'][] = array( $hook, $cb, $priority ); }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {}
function is_admin() { return false; }
function is_feed() { return false; }
function esc_url( $url ) { return str_replace( '&', '&#038;', (string) $url ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function lunara_is_oscars_portal_route() { return $GLOBALS['t']['portal']; }
function lunara_get_home_oscars_snapshot() { return $GLOBALS['t']['snapshot']; }
function lunara_normalize_visual_package_image_sizes( $visual, $poster_size = 'w500', $backdrop_size = 'w780' ) {
	if ( ! empty( $visual['backdrop_url'] ) ) {
		$visual['backdrop_url'] = preg_replace( '#/t/p/[^/]+/#', '/t/p/' . $backdrop_size . '/', $visual['backdrop_url'] );
	}
	return $visual;
}

require ABSPATH . 'inc/oscars-hero-preload.php';

// 1. The preload, on the portal route, with the template's URL.
$hooked = array_filter( $GLOBALS['t']['actions'], function ( $a ) { return 'wp_head' === $a[0] && 'lunara_oscars_hero_backdrop_preload' === $a[1]; } );
lunara_test_assert( ! empty( $hooked ), 'The backdrop preload prints from wp_head.' );
lunara_test_assert( reset( $hooked )[2] < 7, 'The preload prints before the portal critical CSS (wp_head 7), so the download starts as early as the head allows.' );

$GLOBALS['t']['snapshot'] = array( 'best_picture' => array( 'film' => 'Example', 'visual' => array( 'backdrop_url' => 'https://image.tmdb.org/t/p/original/abc.jpg', 'poster_url' => 'https://image.tmdb.org/t/p/w500/p.jpg' ) ) );
lunara_test_assert( 'https://image.tmdb.org/t/p/w780/abc.jpg' === lunara_oscars_hero_backdrop_url(), 'The resolver hands back the Best Picture backdrop at w780, as the template paints it (got "' . lunara_oscars_hero_backdrop_url() . '").' );
ob_start(); lunara_oscars_hero_backdrop_preload(); $head = ob_get_clean();
lunara_test_assert( 1 === preg_match_all( '/<link rel="preload"/', $head ), 'Exactly one preload is printed on the portal route.' );
lunara_test_assert( false !== strpos( $head, 'as="image"' ) && false !== strpos( $head, 'fetchpriority="high"' ), 'The preload is an image at high priority.' );
lunara_test_assert( false !== strpos( $head, 'href="https://image.tmdb.org/t/p/w780/abc.jpg"' ), 'The preload href is the hero backdrop URL.' );

$GLOBALS['t']['portal'] = false;
ob_start(); lunara_oscars_hero_backdrop_preload(); $off = ob_get_clean();
lunara_test_assert( '' === $off, 'Nothing is preloaded off the portal route.' );
$GLOBALS['t']['portal'] = true;

// A fresh process for the no-backdrop case (the resolver memoizes).
$probe = tempnam( sys_get_temp_dir(), 'lunara-preload' );
file_put_contents( $probe, '<?php define("ABSPATH", ' . var_export( ABSPATH, true ) . ');
function add_action() {} function add_filter() {} function is_admin() { return false; } function is_feed() { return false; }
function esc_url( $u ) { return $u; } function esc_attr( $v ) { return $v; }
function lunara_is_oscars_portal_route() { return true; }
function lunara_get_home_oscars_snapshot() { return array( "best_picture" => array( "visual" => array( "poster_url" => "x" ) ) ); }
function lunara_normalize_visual_package_image_sizes( $v ) { return $v; }
require ABSPATH . "inc/oscars-hero-preload.php";
lunara_oscars_hero_backdrop_preload(); echo "[end]";' );
$out = shell_exec( 'php ' . escapeshellarg( $probe ) );
unlink( $probe );
lunara_test_assert( '[end]' === trim( (string) $out ), 'With no backdrop, nothing is preloaded (got "' . trim( (string) $out ) . '").' );

// 2. The template uses the resolver.
$template = (string) file_get_contents( ABSPATH . 'page-oscars.php' );
lunara_test_assert( false !== strpos( $template, '$hero_backdrop_url = function_exists( \'lunara_oscars_hero_backdrop_url\' )' ), 'page-oscars.php takes $hero_backdrop_url from lunara_oscars_hero_backdrop_url(), the URL the preload names.' );

// 3. Poster sizes.
$lazy = '<img width="200" height="300" src="https://x.test/p-200x300.jpg" class="aat-entity-poster" alt="P poster" decoding="async" loading="lazy" srcset="https://x.test/p-200x300.jpg 200w, https://x.test/p.jpg 780w" sizes="auto, (max-width: 820px) 180px, 340px" />';
$out  = lunara_oscars_poster_sizes( $lazy, lunara_oscars_poster_sizes_for( 'grid' ) );
lunara_test_assert( 1 === preg_match( '/\ssizes="auto, \(max-width: 640px\) 96px, \(max-width: 900px\) 340px, 300px"/', $out ), 'A lazy poster gets the grid widths with WordPress\'s auto kept in front (got ' . $out . ').' );
lunara_test_assert( 1 === substr_count( $out, 'sizes="' ), 'Exactly one sizes attribute remains.' );
$eager = str_replace( array( 'loading="lazy"', 'sizes="auto, (max-width: 820px) 180px, 340px"' ), array( 'loading="eager" fetchpriority="high"', 'sizes="(max-width: 820px) 180px, 340px"' ), $lazy );
$out   = lunara_oscars_poster_sizes( $eager, lunara_oscars_poster_sizes_for( 'hero' ) );
lunara_test_assert( false !== strpos( $out, 'sizes="(max-width: 480px) 96px, (max-width: 900px) 124px, 300px"' ), 'An eager poster gets the hero widths with no auto (got ' . $out . ').' );
$nosizes = preg_replace( '/\ssizes="[^"]*"/', '', $lazy );
$out     = lunara_oscars_poster_sizes( $nosizes, '96px' );
lunara_test_assert( false !== strpos( $out, 'sizes="auto, 96px"' ), 'A lazy poster with srcset but no sizes gets one, with auto (got ' . $out . ').' );
$plain = '<img src="https://x.test/p.jpg" alt="" loading="lazy" />';
lunara_test_assert( $plain === lunara_oscars_poster_sizes( $plain, '96px' ), 'An <img> without srcset is left alone.' );
lunara_test_assert( $lazy === lunara_oscars_poster_sizes( $lazy, '' ), 'An empty sizes value changes nothing.' );
foreach ( array( 'hero', 'grid' ) as $slot ) {
	lunara_test_assert( false === strpos( lunara_oscars_poster_sizes_for( $slot ), 'vw' ), "The {$slot} widths are pixel widths, never a viewport fraction." );
}

// 4. The hero card drops its inline background once a poster <img> is in it.
lunara_test_assert( 1 === preg_match( '/if \( \'\' !== \$hero_feature_poster_html \) \{(?:(?!\?>).)*?lunara_oscars_poster_sizes_for\( \'hero\' \)(?:(?!\?>).)*?\$hero_feature_poster_url = \'\';/s', $template ), 'When the hero has poster markup, the template sizes it for the hero slot and clears the container background URL (the <img> covers it).' );
foreach ( array( "lunara_oscars_poster_sizes( \$sl_visual['poster_html']", "lunara_oscars_poster_sizes( \$card_visual['poster_html']" ) as $call ) {
	lunara_test_assert( false !== strpos( $template, $call ), 'The template sizes its grid posters: ' . $call );
}
$data = (string) file_get_contents( ABSPATH . 'inc/oscars-data.php' );
lunara_test_assert( false !== strpos( $data, "lunara_oscars_poster_sizes( \$visual_markup, lunara_oscars_poster_sizes_for( 'grid' ) )" ), 'The winner media link sizes its poster.' );

echo "oscars-hero-preload-runtime: all assertions passed.\n";
