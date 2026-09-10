<?php
// Execute the real public renderer with WordPress attachment/query fixtures.
$root = dirname( __DIR__ );
$source = file_get_contents( $root . '/functions.php' );
$selection_source = file_get_contents( $root . '/inc/site-studio-home-oscars.php' );
foreach ( array( 'lunara_home_oscars_selection', 'lunara_site_studio_home_oscars_ids', 'lunara_home_oscars_item_available' ) as $helper ) {
    if ( ! preg_match( '/^function ' . $helper . '\(.*?^\}/ms', $selection_source, $match ) ) { throw new RuntimeException( 'Missing real selection helper.' ); }
    eval( $match[0] );
}
$tokens = token_get_all( $source );
for ( $i = 0; $i < count( $tokens ); $i++ ) {
    if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
    $j = $i + 1;
    while ( isset( $tokens[$j] ) && ( ! is_array( $tokens[$j] ) || T_STRING !== $tokens[$j][0] ) ) { $j++; }
    if ( 'lunara_render_oscar_picks_carousel' !== ( $tokens[$j][1] ?? '' ) ) { continue; }
    $code = ''; $depth = 0; $opened = false;
    for ( $k = $i; $k < count( $tokens ); $k++ ) {
        $text = is_array( $tokens[$k] ) ? $tokens[$k][1] : $tokens[$k]; $code .= $text;
        if ( '{' === $text ) { $depth++; $opened = true; }
        elseif ( '}' === $text && 0 === --$depth && $opened ) { break; }
    }
    eval( $code ); break;
}
function check( $value, $message ) { if ( ! $value ) { throw new RuntimeException( $message ); } }
function __( $value, $domain = '' ) { return $value; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function absint( $value ) { return abs( (int) $value ); }
function get_theme_mod( $key, $default = false ) { return $default; }
function home_url( $path ) { return $path; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function lunara_repair_mojibake_args( $args, $keys ) { return $args; }
function lunara_home_oscar_picks_ceremony_year() { return 2027; }
function lunara_oscar_ceremony_ordinal_from_year( $year ) { return '99th'; }
function is_wp_error( $value ) { return false; }
function get_post_meta( $id, $key, $single ) { return array( '_lunara_pick_film'=>'A film', '_lunara_pick_person'=>'A filmmaker', '_lunara_pick_ceremony_year'=>2027, '_lunara_pick_status'=>'contender' )[$key] ?? ''; }
function get_the_terms( $id, $taxonomy ) { return array( (object) array( 'name'=>'Best Picture' ) ); }
function get_the_excerpt( $id ) { return 'The forecast explains its choice.'; }
function wp_trim_words( $value, $count, $suffix ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function lunara_oscar_pick_status_label( $status ) { return 'Contender'; }
function lunara_resolve_oscar_pick_ledger_url( ...$args ) { return '/oscars/'; }
function get_the_title( $id ) { return 'A film'; }
function has_post_thumbnail( $id ) { return 4 !== $id; }
function get_post_thumbnail_id( $id ) { return has_post_thumbnail( $id ) ? $id : 0; }
function test_art( $portrait ) { return 'data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="' . ( $portrait ? '600' : '800' ) . '" height="' . ( $portrait ? '900' : '600' ) . '"><rect width="100%" height="100%" fill="#34485c"/><circle cx="50%" cy="20%" r="60" fill="#bba470"/></svg>' ); }
function wp_get_attachment_image_src( $id, $size ) { check( 'full' === $size, 'Mobile must request the uncropped original aspect ratio.' ); return 3 === $id ? false : array( test_art( 1 === $id ), 1 === $id ? 600 : 800, 1 === $id ? 900 : 600, false ); }
function wp_get_attachment_image_srcset( $id, $size ) { check( 'full' === $size, 'Responsive candidates must match the original aspect ratio.' ); return 1 === $id ? false : test_art( false ) . ' 800w'; }
function get_the_post_thumbnail_url( $id, $size ) { return test_art( false ); }
function get_the_post_thumbnail( $id, $size, $attrs ) { check( 'newspack-article-block-landscape-intermediate' === $size, 'Desktop fallback thumbnail must remain unchanged.' ); $html = '<img src="' . test_art( false ) . '" width="600" height="450"'; foreach ( $attrs as $key=>$value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; } return $html . '>'; }
function wp_reset_postdata() {}
function get_the_ID() { return $GLOBALS['pick_id']; }
class Pick_Query {
    public $current_post = -1, $post_count;
    function __construct() { $this->post_count = $GLOBALS['pick_count']; }
    function have_posts() { return $this->current_post + 1 < $this->post_count; }
    function the_post() { $this->current_post++; $GLOBALS['pick_id'] = $this->current_post + 1; }
}
function lunara_get_oscar_picks( $args ) { return new Pick_Query(); }
$GLOBALS['pick_count'] = 4;
$html = lunara_render_oscar_picks_carousel();
if ( in_array( '--fixture', $argv, true ) ) { echo $html; exit; }
preg_match_all( '/<article\b.*?<\/article>/s', $html, $cards );
check( 4 === count( $cards[0] ), 'All four selected cards should render.' );
check( str_contains( $cards[0][0], 'is-portrait' ) && str_contains( $cards[0][0], '<picture>' ), 'Portrait originals need responsive mobile artwork and an explicit fit class.' );
check( str_contains( $cards[0][0], 'media="(max-width: 820px)"' ) && str_contains( $cards[0][0], esc_attr( test_art( true ) ) ), 'Phone source must use the uncropped portrait, including absent srcset fallback.' );
check( ! str_contains( $cards[0][1], 'is-portrait' ) && str_contains( $cards[0][1], '800w' ), 'Landscape art must preserve responsive candidates.' );
check( ! str_contains( $cards[0][2], '<picture>' ) && str_contains( $cards[0][2], '<img' ), 'Unavailable original metadata must retain the existing thumbnail.' );
check( ! str_contains( $cards[0][3], '<img' ) && str_contains( $cards[0][3], 'has-no-visual' ), 'Missing artwork must retain its readable text card.' );
$GLOBALS['pick_count'] = 1; $single = lunara_render_oscar_picks_carousel();
check( str_contains( $single, 'data-lunara-carousel-autoplay="0"' ) && ! str_contains( $single, 'data-lunara-carousel-next' ), 'One item stays static without controls.' );
$GLOBALS['pick_count'] = 0;
check( '' === lunara_render_oscar_picks_carousel(), 'No picks must hide the section.' );
echo "Homepage Oscar mobile artwork runtime passed.\n";
