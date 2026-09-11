<?php
// Execute the real public renderer with WordPress attachment/query fixtures.
$root = dirname( __DIR__ );
define( 'ABSPATH', $root . '/' );
require_once $root . '/inc/home-oscar-artwork.php';
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
    if ( ! in_array( $tokens[$j][1] ?? '', array( 'lunara_render_oscar_picks_carousel', 'lunara_render_oscar_facts_carousel', 'lunara_oscar_fact_visual_focus_options', 'lunara_sanitize_oscar_fact_visual_focus', 'lunara_oscar_fact_visual_focus_css' ), true ) ) { continue; }
    $code = ''; $depth = 0; $opened = false;
    for ( $k = $i; $k < count( $tokens ); $k++ ) {
        $text = is_array( $tokens[$k] ) ? $tokens[$k][1] : $tokens[$k]; $code .= $text;
        if ( '{' === $text ) { $depth++; $opened = true; }
        elseif ( '}' === $text && 0 === --$depth && $opened ) { break; }
    }
    eval( $code ); $i = $k;
}
function check( $value, $message ) { if ( ! $value ) { throw new RuntimeException( $message ); } }
function __( $value, $domain = '' ) { return $value; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function absint( $value ) { return abs( (int) $value ); }
function get_theme_mod( $key, $default = false ) { return $GLOBALS['theme_mods'][$key] ?? $default; }
function home_url( $path ) { return $path; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function lunara_repair_mojibake_args( $args, $keys ) { return $args; }
function lunara_home_oscar_picks_ceremony_year() { return 2027; }
function lunara_oscar_ceremony_ordinal_from_year( $year ) { return '99th'; }
function is_wp_error( $value ) { return false; }
function get_post_meta( $id, $key, $single ) {
    return array(
        '_lunara_pick_film' => ! empty( $GLOBALS['long_titles'] ) ? 'An Ambitious Film With an Equally Long Title Across the Academy Awards Season' : 'A film',
        '_lunara_pick_person' => ! empty( $GLOBALS['long_titles'] ) ? 'A Cinematographer With a Long Name' : 'A filmmaker',
        '_lunara_pick_ceremony_year' => 2027, '_lunara_pick_status' => 'contender',
        '_lunara_fact_visual_verified' => 105 === $id ? '0' : '1',
        '_lunara_fact_visual_treatment' => 107 === $id ? 'archival' : 'wide',
        '_lunara_fact_visual_focus' => 'right-high', '_lunara_fact_year' => 1962,
        '_lunara_fact_attribution' => 'Academy records',
    )[$key] ?? '';
}
function get_the_terms( $id, $taxonomy ) { return array( (object) array( 'name'=>'Best Picture' ) ); }
function get_the_excerpt( $id ) { return 'The forecast explains its choice.'; }
function wp_trim_words( $value, $count, $suffix ) { return $value; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function lunara_oscar_pick_status_label( $status ) { return 'Contender'; }
function lunara_resolve_oscar_pick_ledger_url( ...$args ) { return '/oscars/'; }
function lunara_resolve_oscar_fact_ledger_url( ...$args ) { return '/oscars/'; }
function lunara_oscar_fact_visual_hold_ids() { return array( 106 ); }
function lunara_repair_mojibake_text( $value ) { return $value; }
function lunara_resolve_theme_asset( ...$args ) { return array( 'path' => false, 'uri' => '' ); }
function get_the_content() { return 'The historical record explains why this achievement still matters, with enough context to read the card on a narrow phone.'; }
function get_the_title( $id ) { return ! empty( $GLOBALS['long_titles'] ) ? 'A Remarkable Academy Awards Record With a Long Title That Must Stay Readable on a Narrow Phone' : 'A film'; }
function has_post_thumbnail( $id ) { return ! in_array( $id, array( 4, 104 ), true ); }
function get_post_thumbnail_id( $id ) { return has_post_thumbnail( $id ) ? $id : 0; }
function test_art( $portrait, $replacement = false ) { return 'data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="' . ( $portrait ? '600' : '800' ) . '" height="' . ( $portrait ? '900' : '600' ) . '"><rect width="100%" height="100%" fill="' . ( $replacement ? '#70413a' : '#34485c' ) . '"/><circle cx="50%" cy="20%" r="60" fill="#bba470"/></svg>' ); }
function test_portrait( $id ) { return in_array( $id, array( 1, 101, 107, 202 ), true ); }
function wp_attachment_is_image( $id ) { return $id > 0 && $id !== 999; }
function wp_get_attachment_image_url( $id, $size ) { check( 'full' === $size, 'The real resolver must request the full source.' ); return wp_attachment_is_image( $id ) ? test_art( test_portrait( $id ), $id >= 200 ) : false; }
function wp_get_attachment_image_src( $id, $size ) { check( 'full' === $size, 'Mobile must request the uncropped original aspect ratio.' ); return in_array( $id, array( 3, 103, 999 ), true ) ? false : array( test_art( test_portrait( $id ), $id >= 200 ), test_portrait( $id ) ? 600 : 800, test_portrait( $id ) ? 900 : 600, false ); }
function wp_get_attachment_image_srcset( $id, $size ) { check( 'full' === $size, 'Responsive candidates must match the original aspect ratio.' ); return test_portrait( $id ) ? false : test_art( false, $id >= 200 ) . ' 800w'; }
function get_the_post_thumbnail_url( $id, $size ) { return test_art( false ); }
function test_image_tag( $url, $width, $height, $attrs ) { $html = '<img src="' . esc_url( $url ) . '" width="' . $width . '" height="' . $height . '"'; foreach ( $attrs as $key=>$value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; } return $html . '>'; }
function get_the_post_thumbnail( $id, $size, $attrs ) {
    $expected = $id < 100 ? 'newspack-article-block-landscape-intermediate' : ( 107 === $id ? 'full' : 'lunara-hero-spotlight' );
    check( $expected === $size, 'Desktop fallback thumbnail must remain unchanged.' );
    $portrait = 'full' === $size && test_portrait( $id );
    return test_image_tag( test_art( $portrait ), $portrait ? 600 : 800, $portrait ? 900 : 600, $attrs );
}
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
    check( 'full' === $size, 'An explicit override must use the uncropped source on every device.' );
    return test_image_tag( wp_get_attachment_image_url( $id, $size ), test_portrait( $id ) ? 600 : 800, test_portrait( $id ) ? 900 : 600, $attrs );
}
function wp_reset_postdata() {}
function get_the_ID() { return $GLOBALS['pick_id']; }
class Pick_Query {
    public $current_post = -1, $post_count, $start;
    function __construct( $count, $start = 1 ) { $this->post_count = $count; $this->start = $start; }
    function have_posts() { return $this->current_post + 1 < $this->post_count; }
    function the_post() { $this->current_post++; $GLOBALS['pick_id'] = $this->current_post + $this->start; }
}
function lunara_get_oscar_picks( $args ) { return new Pick_Query( $GLOBALS['pick_count'] ); }
function lunara_get_oscar_facts( $args ) { return new Pick_Query( $GLOBALS['fact_count'], 101 ); }
function test_overrides() {
    $full = array( 'image_id' => 201, 'fit' => 'full', 'focal_x' => 29, 'focal_y' => 67, 'zoom' => 112 );
    $cover = array( 'image_id' => 202, 'fit' => 'cover', 'focal_x' => 23, 'focal_y' => 71, 'zoom' => 112 );
    $source = array( 'image_id' => 0, 'fit' => 'full', 'focal_x' => 50, 'focal_y' => 50, 'zoom' => 100 );
    $missing = array_replace( $cover, array( 'image_id' => 999 ) );
    $GLOBALS['theme_mods'] = array(
        'lunara_home_oscar_picks_artwork_overrides' => json_encode( (object) array( 1 => $full, 2 => $cover, 3 => $source, 4 => $missing ) ),
        'lunara_home_oscar_facts_artwork_overrides' => json_encode( (object) array( 101 => $full, 102 => $cover, 103 => $source, 104 => $missing, 105 => $full, 106 => $full, 107 => $cover ) ),
    );
}
$GLOBALS['pick_count'] = 4;
$GLOBALS['fact_count'] = 7;
if ( in_array( '--framing-fixture', $argv, true ) ) {
    $GLOBALS['long_titles'] = true;
    if ( in_array( '--overrides', $argv, true ) ) { test_overrides(); }
    echo lunara_render_oscar_picks_carousel() . lunara_render_oscar_facts_carousel(); exit;
}
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
$facts = lunara_render_oscar_facts_carousel();
preg_match_all( '/<article\b.*?<\/article>/s', $facts, $fact_cards );
check( 7 === count( $fact_cards[0] ), 'All seven Facts must remain readable, even when their artwork is unavailable.' );
check( str_contains( $fact_cards[0][0], 'is-portrait' ) && str_contains( $fact_cards[0][0], '<picture>' ), 'Verified portrait Facts must use the uncropped mobile source.' );
check( str_contains( $fact_cards[0][0], 'media="(max-width: 820px)"' ) && str_contains( $fact_cards[0][0], esc_attr( test_art( true ) ) ), 'Facts must also retain the no-srcset original fallback.' );
check( str_contains( $fact_cards[0][1], '800w' ), 'Landscape Facts must retain responsive candidates.' );
check( ! str_contains( $fact_cards[0][2], '<picture>' ) && str_contains( $fact_cards[0][2], '<img' ), 'Missing full metadata must retain the old Fact thumbnail.' );
foreach ( array( 3, 4, 5 ) as $index ) { check( ! str_contains( $fact_cards[0][$index], '<img' ), 'Missing, unverified, and held Facts cannot expose artwork.' ); }
check( str_contains( $fact_cards[0][6], 'has-archival-visual' ) && str_contains( $fact_cards[0][6], 'data-visual-focus="right-high"' ), 'Unsaved Facts retain their archival treatment and focus.' );
$GLOBALS['pick_count'] = 4;
test_overrides();
foreach ( array( 'picks' => lunara_render_oscar_picks_carousel(), 'facts' => lunara_render_oscar_facts_carousel() ) as $kind => $overridden ) {
    preg_match_all( '/<article\b.*?<\/article>/s', $overridden, $override_cards );
    check( str_contains( $override_cards[0][0], 'has-artwork-override' ) && str_contains( $override_cards[0][0], esc_attr( test_art( false, true ) ) ) && ! str_contains( $override_cards[0][0], '<picture>' ), $kind . ': explicit artwork must replace the public source on desktop and mobile.' );
    check( str_contains( $override_cards[0][1], esc_attr( test_art( true, true ) ) ), $kind . ': cover framing must use the selected portrait.' );
    check( str_contains( $override_cards[0][2], '<img' ), $kind . ': source-image framing must still work without full dimension metadata.' );
    check( ! str_contains( $override_cards[0][3], '<img' ), $kind . ': a deleted override must not silently substitute a different source.' );
    if ( 'facts' === $kind ) {
        foreach ( array( 4, 5 ) as $index ) { check( ! str_contains( $override_cards[0][$index], '<img' ), 'Homepage overrides must never bypass Fact verification or visual hold.' ); }
    }
}
$GLOBALS['fact_count'] = 1;
$single_fact = lunara_render_oscar_facts_carousel();
check( str_contains( $single_fact, 'data-autoplay="0"' ) && ! str_contains( $single_fact, 'lunara-oscar-facts-dots' ), 'One Fact stays static without controls.' );
$GLOBALS['fact_count'] = 0;
check( '' === lunara_render_oscar_facts_carousel(), 'No Facts must hide the section.' );
echo "Homepage Oscar mobile artwork runtime passed.\n";
