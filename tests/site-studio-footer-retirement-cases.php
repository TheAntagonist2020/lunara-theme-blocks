<?php
/** Execute the actual six-setting registration and retirement against the shared Footer lifecycle. */
$customizer_source = file_get_contents( getenv( 'LUNARA_FOOTER_CUSTOMIZER_SOURCE' ) ?: dirname( __DIR__ ) . '/inc/customizer.php' );
$registration_start = strpos( $customizer_source, "    \$wp_customize->add_section( 'lunara_footer_options'" );
$registration_end = strpos( $customizer_source, '    // ── PANEL 3: REVIEWS', $registration_start );
footer_assert( false !== $registration_start && false !== $registration_end, 'Actual Footer Customizer registration is available to the retirement regression.' );
footer_assert( (bool) preg_match( "/add_action\( 'customize_register', 'lunara_retire_footer_customizer_controls', 100 \);/", $customizer_source ), 'Footer retirement runs after ordinary Customizer registration.' );
eval( footer_extract_function( $customizer_source, 'lunara_retire_footer_customizer_controls' ) );
$wp_customize = new class {
    public $settings = array( 'unrelated_setting' => array() );
    public $controls = array( 'unrelated_control' => array() );
    public function add_section( $key, $args ) {}
    public function add_setting( $key, $args ) { $this->settings[$key] = $args; }
    public function add_control( $key, $args ) { $this->controls[$key] = $args; }
    public function remove_setting( $key ) { unset( $this->settings[$key] ); }
    public function remove_control( $key ) { unset( $this->controls[$key] ); }
    public function publish( $submitted ) {
        $writes = 0;
        foreach ( $submitted as $key => $value ) {
            // WordPress can save only registered setting objects, including values from stale changesets.
            if ( ! isset( $this->settings[$key] ) ) { continue; }
            $sanitize = $this->settings[$key]['sanitize_callback'] ?? null;
            set_theme_mod( $key, $sanitize ? call_user_func( $sanitize, $value ) : $value );
            $writes++;
        }
        return $writes;
    }
};
eval( substr( $customizer_source, $registration_start, $registration_end - $registration_start ) );
$footer_keys = lunara_site_studio_mod_surface_keys( lunara_site_studio_footer_spec() );
foreach ( $footer_keys as $key ) { footer_assert( isset( $wp_customize->settings[$key], $wp_customize->controls[$key] ), 'Regression loads actual former Footer writer: ' . $key ); }

footer_fixture_reset();
$lunara_pilot_theme_mods = array( 'lunara_footer_tagline' => 'Saved closing line', 'lunara_footer_show_logo' => '1', 'lunara_footer_col2_heading' => '', 'lunara_footer_copyright' => 'Saved credit', 'unrelated_setting' => 'Keep' );
$legacy = $lunara_pilot_theme_mods;
$stale_submission = array();
foreach ( $footer_keys as $key ) { $stale_submission[$key] = 'lunara_footer_show_logo' === $key ? 0 : 'Stale Customizer value'; }
lunara_retire_footer_customizer_controls( $wp_customize );
footer_assert( array( 'unrelated_setting' ) === array_keys( $wp_customize->settings ) && array( 'unrelated_control' ) === array_keys( $wp_customize->controls ), 'Only the six Footer controls and setting writers retire; unrelated registration remains.' );
footer_assert( $legacy === $lunara_pilot_theme_mods, 'Retirement preserves saved raw values, blank headings, missing defaults and unrelated mods.' );

$candidate = footer_candidate();
$candidate['brand']['tagline'] = 'Shared Footer closing line';
$candidate['columns']['editorial'] = 'Shared Editorial';
$saved = lunara_site_studio_footer_save_state( $candidate );
footer_assert( ! is_wp_error( $saved ) && str_contains( footer_fixture_render(), 'Shared Footer closing line' ), 'Shared Apply and real Footer output remain available after old writer retirement.' );
$before_stale = serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options ) );
footer_assert( 0 === $wp_customize->publish( $stale_submission ) && $before_stale === serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options ) ), 'A stale six-field Customizer Publish cannot change shared settings or History.' );

$preview_candidate = $saved['state']; $preview_candidate['brand']['tagline'] = 'Private Footer closing line';
$token = lunara_site_studio_store_private_preview( 'site-footer', 'theme:site-footer', '/', $preview_candidate );
footer_fixture_request( $token );
$public_before = $lunara_pilot_theme_mods;
footer_assert( is_array( lunara_site_studio_resolve_private_preview() ) && str_contains( footer_fixture_render(), 'Private Footer closing line' ) && $public_before === $lunara_pilot_theme_mods, 'Retired Customizer does not affect shared private Preview or public storage isolation.' );
unset( $GLOBALS['footer_filters'] );
$restored = lunara_site_studio_footer_restore_revision( $saved['revision_id'] );
footer_assert( ! is_wp_error( $restored ) && $legacy === $lunara_pilot_theme_mods, 'Shared History restores exact raw legacy state after Customizer retirement.' );
$redone = lunara_site_studio_footer_restore_revision( $restored['safety_revision_id'] );
footer_assert( ! is_wp_error( $redone ) && $saved['state'] === $redone['state'], 'The restore-safety revision recovers the shared Footer candidate.' );
