<?php
define( 'LUNARA_SITE_STUDIO_RUNTIME_BOOTSTRAP_ONLY', true );
require __DIR__ . '/site-studio-runtime.php';
function get_theme_mod( $key, $default = false ) { return apply_filters( 'theme_mod_' . $key, $default ); }
function get_option( $key, $default = false ) { return $default; }
function set_theme_mod() { throw new RuntimeException( 'Preview must never write public theme settings.' ); }
function update_option() { throw new RuntimeException( 'Preview must never write public options.' ); }
require dirname( __DIR__ ) . '/inc/site-studio-home-oscars.php';
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
foreach ( array( 'picks', 'facts' ) as $kind ) {
	$surface = 'home-oscar-' . $kind;
	$state = lunara_site_studio_mod_surface_read_state( lunara_site_studio_home_oscars_spec( $kind ) );
	$state['copy']['heading'] = 'Private ' . $kind . ' heading';
	$state['selection']['mode'] = 'manual'; $state['selection']['ids'] = '4,2';
	$state['artwork']['overrides'] = '{"4":{"image_id":44,"fit":"full","focal_x":17,"focal_y":81,"zoom":108}}';
	lunara_test_assert( lunara_site_studio_preview_state_safe( $surface, $state ), 'Private state accepts a validated exact candidate.' );
	$bad = $state; $bad['selection']['ids'] = '4,4';
	lunara_test_assert( ! lunara_site_studio_preview_state_safe( $surface, $bad ), 'Private preview rejects malformed IDs before installing filters.' );
	$bad = $state; $bad['artwork']['overrides'] = '{"4":{"image_id":44,"fit":"stretch","focal_x":17,"focal_y":81,"zoom":108}}';
	lunara_test_assert( ! lunara_site_studio_preview_state_safe( $surface, $bad ), 'Private preview rejects malformed artwork before installing filters.' );
	lunara_test_assert( lunara_site_studio_preview_install_state( $surface, $state, 42 ), 'Preview installs request-local presentation.' );
	lunara_test_assert( $state['copy']['heading'] === get_theme_mod( 'lunara_home_oscar_' . $kind . '_heading' ), 'Public renderer sees the private heading in this request only.' );
	lunara_test_assert( '4,2' === get_theme_mod( 'lunara_home_oscar_' . $kind . '_selection_ids' ), 'Private selection order reaches the renderer.' );
	lunara_test_assert( $state['artwork']['overrides'] === get_theme_mod( 'lunara_home_oscar_' . $kind . '_artwork_overrides' ), 'Private artwork reaches the same request-local theme mod as the public resolver.' );
	lunara_test_assert( 44 === lunara_home_oscar_artwork_overrides( $kind )[4]['image_id'], 'The real artwork decoder consumes the exact private candidate.' );
	$pilot = lunara_site_studio_preview_pilots()[$surface];
	$registered = lunara_site_studio_get_surface( $surface );
	lunara_test_assert( $pilot['query'] === $registered['preview_query_arg'] && $pilot['owner'] === $registered['owner'] && '/' === $pilot['route'], 'Preview route and owner match the registered surface.' );
}
echo "Homepage Oscars preview: 18 checks passed without public writes.\n";
