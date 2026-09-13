<?php
/** Actual Search, 404 and Footer markup, with only WordPress data/transport seams. */
define( 'LUNARA_METHOD_BOOTSTRAP_ONLY', true );
$fixture = file_get_contents( __DIR__ . '/site-studio-utility-recovery-fixture.php' );
$fixture = str_replace( "return 'Fixture story '.\$post->ID;", "return \$GLOBALS['layout_titles'][\$post->ID] ?? ('Fixture story '.\$post->ID);", $fixture );
$fixture = str_replace( "echo \$match[0].'</body></html>';", "echo \$match[0]; lunara_render_custom_footer(); echo '</body></html>';", $fixture );
eval( '?>' . $fixture );
function bloginfo( $field ) { echo esc_html( get_bloginfo( $field ) ); }
function is_feed() { return false; }
function is_search() { return ! empty( $GLOBALS['wp_query']->is_search ); }
function wp_get_attachment_image() { return '<img alt="Lunara logo">'; }
utility_load_functions( dirname(__DIR__).'/inc/frontend.php', array(
    'lunara_home_brand_number_setting', 'lunara_home_select_setting',
    'lunara_render_footer_link_list', 'lunara_render_custom_footer',
    'lunara_get_utility_search_preview_preset_values', 'lunara_get_utility_search_studio_select_value',
    'lunara_get_utility_search_studio_number_value', 'lunara_output_utility_search_studio_css',
) );

$fixtures = array();
$long_title = 'The extraordinary, unfinished conversation between a filmmaker, a city, and everyone who still believes that cinema can change how we see the world';
foreach ( array( 'search-blank', 'search-query', 'search-no-results', '404', '404-long', 'footer-zero', 'footer-one', 'footer-custom12' ) as $scenario ) {
    utility_reset();
    $GLOBALS['wp_query'] = new WP_Query();
    $footer_only = str_starts_with( $scenario, 'footer-' );
    $missing = str_starts_with( $scenario, '404' );
    $GLOBALS['wp_query']->is_search = ! $footer_only && ! $missing;
    $GLOBALS['wp_query']->is_404 = $missing;
    $_GET = array(); $_SERVER['REQUEST_URI'] = $missing ? '/definitely-not-a-real-lunara-route/' : '/search/';
    $GLOBALS['utility_results'] = array();
    $GLOBALS['layout_titles'] = array( 401 => $long_title, 402 => 'CinemaWithoutBorders'.str_repeat( 'AndBeyond', 12 ), 403 => 'A Journal dispatch about the enduring pleasure of finding a film that surprises you' );
    if ( 'search-query' === $scenario ) {
        $_GET['q'] = 'Lunara';
        $GLOBALS['utility_results'] = array( new WP_Post(401,'review','publish'), new WP_Post(402,'review','publish'), new WP_Post(403,'journal','publish') );
        $GLOBALS['lunara_pilot_theme_mods']['lunara_search_excerpt_words'] = 50;
    } elseif ( 'search-no-results' === $scenario ) {
        $_GET['q'] = 'An extraordinarily specific film title with no matching entry';
    }
    if ( '404-long' === $scenario ) {
        $GLOBALS['lunara_pilot_theme_mods']['lunara_404_title'] = $long_title;
        $GLOBALS['lunara_pilot_theme_mods']['lunara_404_explanation'] = 'This page has moved, but the conversation continues. Return to the latest criticism, explore the Journal, or find a film, filmmaker, or Academy Awards record using the search below.';
        $GLOBALS['lunara_pilot_theme_mods']['lunara_404_reentry_title'] = 'Choose the next story, rediscover a favorite filmmaker, or start a completely unexpected journey through the archive.';
    }
    $expected_columns = array(5,4,4);
    if ( $footer_only ) {
        $columns = array('editorial'=>array(),'oscars'=>array(),'utility'=>array());
        $count = 'footer-zero' === $scenario ? 0 : ( 'footer-one' === $scenario ? 1 : 12 );
        foreach ( $columns as $key => &$rows ) {
            for ( $i=0; $i<$count; ++$i ) {
                $label = $i===0 ? 'An exceptionally long footer destination for the complete film criticism archive' : ( $i===1 ? str_repeat('LongFilm',10) : ucfirst($key).' destination '.($i+1) );
                $rows[] = array('id'=>$key.'-'.$i,'enabled'=>true,'label'=>$label,'destination'=>'custom','url'=>'/collection/'.$key.'/'.$i.'/?year=2026#films');
            }
        }
        unset($rows);
        $navigation = array('mode'=>'custom','columns'=>$columns);
        $validated = lunara_site_studio_footer_navigation_validate($navigation);
        if(is_wp_error($validated)){throw new RuntimeException('Invalid layout fixture navigation');}
        $GLOBALS['lunara_pilot_theme_mods']['lunara_footer_navigation'] = lunara_site_studio_footer_navigation_serialize($validated);
        $expected_columns = array($count,$count,$count);
        $GLOBALS['lunara_pilot_theme_mods']['lunara_footer_col1_heading'] = 'Editorial coverage from across the international film scene';
    }
    ob_start(); lunara_output_utility_search_studio_css(); $authority = ob_get_clean();
    if ( $footer_only ) { ob_start(); get_header(); get_footer(); $html=ob_get_clean(); }
    else { $html=utility_render($missing ? '404.php' : 'search.php'); }
    $fixtures[$scenario] = array(
        'html'=>$html,'authority'=>$authority,'body_class'=>$missing?'error404':($footer_only?'home':'search'),
        'footer_only'=>$footer_only,'expected_columns'=>$expected_columns,
        'titles'=>'search-query'===$scenario?array_values($GLOBALS['layout_titles']):array(),
        'footer_labels'=>$footer_only?array_merge(...array_map(static function($rows){return array_column($rows,'label');},array_values($columns))):null,
        'saved_copy'=>'404-long'===$scenario?array($long_title,$GLOBALS['lunara_pilot_theme_mods']['lunara_404_explanation'],$GLOBALS['lunara_pilot_theme_mods']['lunara_404_reentry_title']):array(),
    );
}
echo json_encode($fixtures, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
