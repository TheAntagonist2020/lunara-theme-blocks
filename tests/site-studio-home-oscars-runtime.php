<?php
/** Transactions and public selection rules use real adapters and query helpers. */
require __DIR__ . '/site-studio-pilot-runtime.php';
require dirname( __DIR__ ) . '/inc/site-studio-home-oscars.php';
function get_post_meta( $id, $key, $single = true ) { if ( isset( $GLOBALS['home_oscars_meta'][$id][$key] ) ) { return $GLOBALS['home_oscars_meta'][$id][$key]; } return '_lunara_pick_ceremony_year' === $key ? ( 3 === (int) $id ? 2026 : 2027 ) : ''; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function lunara_home_oscar_picks_ceremony_year() { return 2027; }
function lunara_oscar_ceremony_ordinal_from_year( $year ) { return '99th'; }
function lunara_repair_mojibake_args( $args, $keys ) { return $args; }
class WP_Query { public $args; public $post_count = 0; public $posts = array(); public function __construct( $args ) { $this->args = $args; $GLOBALS['home_oscars_queries'][] = $args; $this->posts = isset( $GLOBALS['home_oscars_query_posts'] ) ? $GLOBALS['home_oscars_query_posts'] : array(); } public function have_posts() { return false; } }
$source = file_get_contents( dirname( __DIR__ ) . '/functions.php' );
foreach ( array( 'lunara_get_oscar_picks', 'lunara_get_oscar_facts', 'lunara_render_oscar_picks_carousel', 'lunara_render_oscar_facts_carousel' ) as $function ) {
	if ( ! preg_match( '/\tfunction ' . $function . '\(.*?^\t\}/ms', $source, $match ) ) { throw new RuntimeException( $function . ' not found' ); }
	eval( $match[0] );
}
$checks = 0;
function home_oscars_check( $condition, $message ) { global $checks; $checks++; if ( ! $condition ) { throw new RuntimeException( $message ); } }
lunara_pilot_reset();
$lunara_pilot_theme_mods = array( 'unrelated_mod' => 'keep', 'lunara_home_oscar_picks_manual_order' => '5,2' );
$original = $lunara_pilot_theme_mods;
$picks = lunara_site_studio_home_oscar_picks_adapter();
$facts = lunara_site_studio_home_oscar_facts_adapter();
$state = $picks->read_state();
home_oscars_check( 'legacy' === $state['selection']['mode'] && '5,2' === $state['selection']['ids'], 'Existing manual order is imported without adoption.' );
home_oscars_check( $original === $lunara_pilot_theme_mods, 'Opening the editor cannot rewrite saved presentation.' );
$facts_before = $facts->read_state();
$state['selection']['mode'] = 'manual'; $state['selection']['ids'] = '2,5';
$state['copy']['heading'] = 'Our Oscar board'; $state['presentation']['autoplay_interval'] = 7000;
$saved = $picks->save_state( $state );
home_oscars_check( ! is_wp_error( $saved ) && $state === $saved['state'], 'Apply must persist and return the exact candidate.' );
home_oscars_check( $facts_before === $facts->read_state() && 'keep' === $lunara_pilot_theme_mods['unrelated_mod'], 'Picks cannot change Facts or other settings.' );
$state['selection']['mode'] = 'automatic';
$automatic = $picks->save_state( $state );
home_oscars_check( '2,5' === $automatic['state']['selection']['ids'], 'Switching modes retains the manual lineup.' );
foreach ( array( '2,2', '0', '2,bad', '2, 5', implode( ',', range( 1, 49 ) ) ) as $bad ) { $invalid = $state; $invalid['selection']['ids'] = $bad; home_oscars_check( is_wp_error( $picks->validate_state( $invalid ) ), 'Malformed or oversized IDs must fail validation.' ); }
$before = $lunara_pilot_theme_mods;
$lunara_pilot_mod_fault = array( 'key' => 'lunara_home_oscar_picks_heading', 'mode' => 'throw_after', 'remaining' => 1 );
$failed = $picks->save_state( array_replace_recursive( $state, array( 'copy' => array( 'heading' => 'Fail write' ) ) ) );
home_oscars_check( is_wp_error( $failed ) && $before === $lunara_pilot_theme_mods, 'Failed writes must restore every prior setting.' );
$restored = $picks->restore_revision( $saved['revision_id'] );
home_oscars_check( ! is_wp_error( $restored ) && $original === $lunara_pilot_theme_mods, 'History restores exact missing keys and legacy presentation.' );
$lunara_pilot_posts[1] = new WP_Post( 1, 'lunara_oscar_pick', 'publish' );
$lunara_pilot_posts[2] = new WP_Post( 2, 'lunara_oscar_pick', 'private' );
$lunara_pilot_posts[3] = new WP_Post( 3, 'lunara_oscar_pick', 'publish' );
$lunara_pilot_posts[4] = new WP_Post( 4, 'oscar_fact', 'publish' );
$lunara_pilot_theme_mods['lunara_home_oscar_picks_selection_mode'] = 'manual';
$lunara_pilot_theme_mods['lunara_home_oscar_picks_selection_ids'] = '4,3,2,99,1';
home_oscars_check( array( 'mode' => 'manual', 'ids' => array( 1 ) ) === lunara_home_oscars_selection( 'picks', 2027 ), 'Manual Picks skip wrong type, ceremony, private and deleted records.' );
$lunara_pilot_theme_mods['lunara_home_oscar_picks_selection_ids'] = '';
home_oscars_check( array( 'mode' => 'manual', 'ids' => array() ) === lunara_home_oscars_selection( 'picks', 2027 ), 'An empty manual lineup cannot fall back to automatic.' );
$lunara_pilot_theme_mods['lunara_home_oscar_facts_selection_mode'] = 'manual';
$lunara_pilot_theme_mods['lunara_home_oscar_facts_selection_ids'] = '4,1,99';
home_oscars_check( array( 4 ) === lunara_home_oscars_selection( 'facts' )['ids'], 'Facts accepts only published Facts.' );
$query = lunara_get_oscar_facts( array( 'ordered_ids' => array( 9,4 ), 'posts_per_page' => 1 ) );
home_oscars_check( array( 9,4 ) === $query->args['post__in'] && 'post__in' === $query->args['orderby'] && 1 === $query->args['posts_per_page'], 'Public Facts preserve exact manual order and one-item limits.' );
$query = lunara_get_oscar_picks( array( 'ordered_ids' => array( 9,4 ), 'ceremony_year' => 2027 ) );
home_oscars_check( array( 9,4 ) === $query->args['post__in'] && 'post__in' === $query->args['orderby'] && 'publish' === $query->args['post_status'], 'Public Picks preserve exact order and publication gates.' );
home_oscars_check( false === lunara_get_oscar_picks()->args['has_password'] && false === lunara_get_oscar_facts()->args['has_password'], 'Automatic queries exclude password-protected records like the editor does.' );
foreach ( array( 'picks', 'facts' ) as $kind ) {
	$lunara_pilot_theme_mods['lunara_home_oscar_' . $kind . '_selection_mode'] = 'manual';
	$lunara_pilot_theme_mods['lunara_home_oscar_' . $kind . '_selection_ids'] = '';
	$GLOBALS['home_oscars_queries'] = array();
	$renderer = 'lunara_render_oscar_' . $kind . '_carousel';
	home_oscars_check( '' === $renderer() && empty( $GLOBALS['home_oscars_queries'] ), 'Empty manual ' . $kind . ' must hide before any fallback query.' );
}
echo "Homepage Oscars runtime: {$checks} checks passed.\n";
