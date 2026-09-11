<?php
/** Artwork validation, source authority, metadata and backwards-compatible transactions. */
require __DIR__ . '/site-studio-home-oscars-runtime.php';
require_once dirname( __DIR__ ) . '/inc/site-studio-carousels.php';
function wp_attachment_is_image( $id ) { return in_array( (int) $id, array( 81, 82, 83 ), true ); }
function wp_get_attachment_image_url( $id, $size ) { home_oscars_check( 'full' === $size, 'Framing must always resolve the uncropped source.' ); return wp_attachment_is_image( $id ) ? 'https://example.test/image-' . $id . '.jpg' : ''; }
function get_post_thumbnail_id( $id ) { return isset( $GLOBALS['home_oscars_thumbnails'][$id] ) ? $GLOBALS['home_oscars_thumbnails'][$id] : 0; }
function lunara_oscar_fact_visual_hold_ids() { return array( 6 ); }
function get_posts( $args ) { $GLOBALS['home_oscars_search_args'] = $args; return isset( $GLOBALS['home_oscars_search_posts'] ) ? $GLOBALS['home_oscars_search_posts'] : array(); }
function wp_list_pluck( $posts, $key ) { return array_map( static function ( $post ) use ( $key ) { return $post->$key; }, $posts ); }
function get_the_title( $post ) { return 'Published ' . $post->post_type . ' ' . $post->ID; }
function rest_ensure_response( $value ) { return $value; }
function art_json( $entries ) { ksort( $entries, SORT_NUMERIC ); return json_encode( (object) $entries ); }
function art_entry( $image = 0, $fit = 'cover', $x = 24, $y = 71, $zoom = 109 ) { return array( 'image_id' => $image, 'fit' => $fit, 'focal_x' => $x, 'focal_y' => $y, 'zoom' => $zoom ); }

lunara_pilot_reset();
$lunara_pilot_posts[1] = new WP_Post( 1, 'lunara_oscar_pick', 'publish' );
$lunara_pilot_posts[2] = new WP_Post( 2, 'lunara_oscar_pick', 'private' );
$lunara_pilot_posts[4] = new WP_Post( 4, 'oscar_fact', 'publish' );
$lunara_pilot_posts[6] = new WP_Post( 6, 'oscar_fact', 'publish' );
$GLOBALS['home_oscars_thumbnails'] = array( 1 => 81, 2 => 83, 4 => 82, 6 => 82 );
$GLOBALS['home_oscars_meta'] = array( 4 => array( '_lunara_fact_visual_verified' => '1', '_lunara_fact_visual_treatment' => 'archival', '_lunara_fact_visual_focus' => 'left-high' ), 6 => array( '_lunara_fact_visual_verified' => '1' ) );
$picks = lunara_site_studio_home_oscar_picks_adapter(); $facts = lunara_site_studio_home_oscar_facts_adapter();
$state = $picks->read_state();
home_oscars_check( '{}' === $state['artwork']['overrides'] && array() === $lunara_pilot_theme_mods, 'Opening an old workspace preserves the absence of overrides.' );
$valid = art_json( array( 1 => art_entry( 82 ) ) );
$bad_values = array( '[]', '{"01":' . json_encode( art_entry() ) . '}', '{"1":' . json_encode( art_entry() ) . ',"1":' . json_encode( art_entry() ) . '}', ' ' . $valid, art_json( array( 1 => array_merge( art_entry(), array( 'hidden' => true ) ) ) ), art_json( array( 1 => art_entry( -1 ) ) ), art_json( array( 1 => art_entry( 0, 'stretch' ) ) ), art_json( array( 1 => art_entry( 0, 'cover', 101 ) ) ), art_json( array( 1 => art_entry( 0, 'cover', 50, 50, 113 ) ) ), art_json( array_fill_keys( range( 1, 49 ), art_entry() ) ) );
foreach ( $bad_values as $bad ) { home_oscars_check( false === lunara_home_oscar_artwork_decode( $bad ), 'Malformed, unknown or oversized artwork values must fail closed.' ); }
$bad_state = $state; $bad_state['artwork']['overrides'] = ' ' . $valid;
home_oscars_check( is_wp_error( $picks->validate_state( $bad_state ) ), 'Validation cannot silently normalize noncanonical JSON into an accepted candidate.' );
$bad_state = $state; $bad_state['artwork']['overrides'] = art_json( array( 1 => art_entry( 999 ) ) );
home_oscars_check( is_wp_error( $picks->save_state( $bad_state ) ) && array() === $lunara_pilot_theme_mods, 'A new unavailable attachment is rejected before any write.' );
$state['artwork']['overrides'] = $valid;
$saved = $picks->save_state( $state );
home_oscars_check( ! is_wp_error( $saved ) && $valid === $saved['state']['artwork']['overrides'] && '{}' === $facts->read_state()['artwork']['overrides'], 'Apply stores exact placement overrides independently for Picks.' );
$before_failure = $lunara_pilot_theme_mods;
$lunara_pilot_mod_fault = array( 'key' => 'lunara_home_oscar_picks_artwork_overrides', 'mode' => 'throw_after', 'remaining' => 1 );
$failed_state = $state; $failed_state['copy']['heading'] = 'Partial write must roll back'; $failed_state['artwork']['overrides'] = art_json( array( 1 => art_entry( 81 ) ) );
home_oscars_check( is_wp_error( $picks->save_state( $failed_state ) ) && $before_failure === $lunara_pilot_theme_mods, 'A partial artwork write rolls back copy, framing and exact mod presence together.' );
$art = lunara_home_oscar_artwork( 'picks', 1 );
home_oscars_check( $art['override'] && 82 === $art['image_id'] && 81 === $art['source_id'] && $art['has_image'] && str_contains( $art['url'], 'image-82' ), 'Custom rendering resolves the selected full attachment and retains source identity.' );
home_oscars_check( '--lunara-oscar-image-fit:cover;--lunara-oscar-image-position:24% 71%;--lunara-oscar-image-zoom:1.09;' === lunara_home_oscar_artwork_style( $art ), 'Public framing matches the shared control values.' );
$old_snapshot = lunara_site_studio_raw_mod_snapshot( lunara_site_studio_mod_surface_keys( lunara_site_studio_home_oscars_spec( 'picks' ) ) );
unset( $old_snapshot['lunara_home_oscar_picks_artwork_overrides'] );
lunara_pilot_seed_revision( 'home-oscar-picks', array( 'mods' => $old_snapshot ), 'old-oscars' );
$restore = $picks->restore_revision( 'old-oscars' );
home_oscars_check( ! is_wp_error( $restore ) && ! array_key_exists( 'lunara_home_oscar_picks_artwork_overrides', $lunara_pilot_theme_mods ) && '{}' === $restore['state']['artwork']['overrides'], 'Pre-framing revisions restore the original absence of the new mod.' );
$broken_snapshot = $old_snapshot; unset( $broken_snapshot['lunara_home_oscar_picks_heading'] );
lunara_pilot_seed_revision( 'home-oscar-picks', array( 'mods' => $broken_snapshot ), 'broken-oscars' );
home_oscars_check( is_wp_error( $picks->restore_revision( 'broken-oscars' ) ), 'Legacy migration does not accept revisions missing any other managed key.' );
$lunara_pilot_theme_mods['lunara_home_oscar_picks_artwork_overrides'] = art_json( array( 1 => art_entry( 999 ) ) );
$state = $picks->read_state(); $state['artwork']['overrides'] = art_json( array( 1 => art_entry( 999, 'full' ) ) );
home_oscars_check( ! is_wp_error( $picks->save_state( $state ) ), 'A retained missing attachment remains editable.' );
$art = lunara_home_oscar_artwork( 'picks', 1 );
home_oscars_check( $art['override'] && ! $art['has_image'] && '' === $art['url'] && str_contains( $art['source_url'], 'image-81' ), 'Missing custom art never silently substitutes the source.' );
$state['artwork']['overrides'] = '{}';
home_oscars_check( ! is_wp_error( $picks->save_state( $state ) ) && 81 === lunara_home_oscar_artwork( 'picks', 1 )['image_id'], 'Removing an override restores its source.' );
$lunara_pilot_theme_mods['lunara_home_oscar_facts_artwork_overrides'] = art_json( array( 4 => art_entry( 83 ), 6 => art_entry( 83 ) ) );
home_oscars_check( ! lunara_home_oscar_artwork( 'facts', 6 )['has_image'], 'Held Fact visuals remain hidden even with a custom override.' );
$GLOBALS['home_oscars_meta'][4]['_lunara_fact_visual_verified'] = '';
home_oscars_check( ! lunara_home_oscar_artwork( 'facts', 4 )['has_image'], 'Unverified Fact visuals remain hidden even with a custom override.' );
$GLOBALS['home_oscars_meta'][4]['_lunara_fact_visual_verified'] = '1';
$source = lunara_home_oscar_artwork( 'facts', 4, false );
home_oscars_check( ! $source['override'] && 'full' === $source['fit'] && 38 === $source['focal_x'] && 38 === $source['focal_y'], 'Source defaults preserve legacy archival treatment and focus independently of saved overrides.' );
home_oscars_check( str_ends_with( lunara_home_oscar_artwork_style( array_merge( $source, array( 'zoom' => 112 ) ) ), '--lunara-oscar-image-zoom:1;' ), 'Show full image disables zoom.' );
$GLOBALS['home_oscars_query_posts'] = array( $lunara_pilot_posts[1] );
$GLOBALS['home_oscars_search_posts'] = array( $lunara_pilot_posts[1] );
$request = new Lunara_Pilot_REST_Request( array( 'surface' => 'home-oscar-picks', 'ids' => '2,1,99', 'image_ids' => '82,999', 'mode' => 'manual', 'count' => '1', 'year' => '2027', 'search' => '' ) );
$before = $lunara_pilot_theme_mods; $payload = lunara_site_studio_home_oscars_items( $request );
$items = array_column( $payload['items'], null, 'id' );
home_oscars_check( array( 1 ) === $payload['lineup'] && 'post__in' === end( $GLOBALS['home_oscars_queries'] )['orderby'], 'Metadata uses the real manual query with published eligibility and candidate count.' );
home_oscars_check( 81 === $items[1]['image_id'] && str_contains( $items[1]['image_url'], 'image-81' ) && '' === $items[2]['image_url'] && '' === $items[99]['image_url'] && 'Unavailable selection #2' === $items[2]['title'], 'Metadata exposes current public source artwork and hides unavailable source identity.' );
home_oscars_check( '' === $payload['images']->{999} && str_contains( $payload['images']->{82}, 'image-82' ) && $before === $lunara_pilot_theme_mods, 'Requested image URLs explicitly clear missing attachments without writes.' );
foreach ( array( array( 'count' => '17' ), array( 'mode' => 'unknown' ), array( 'image_ids' => '2,bad' ), array( 'image_ids' => '9999999999999999999999999' ) ) as $params ) { home_oscars_check( is_wp_error( lunara_site_studio_home_oscars_items( new Lunara_Pilot_REST_Request( array_merge( array( 'surface' => 'home-oscar-picks' ), $params ) ) ) ), 'Malformed metadata request values are rejected.' ); }
echo "Homepage Oscar artwork runtime: {$checks} checks passed.\n";
