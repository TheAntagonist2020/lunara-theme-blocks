<?php
/** Academy dossier presentation; the Ledger plugin retains all records and artwork. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_oscars_ledger_spec() {
    $fields = array();
    foreach ( lunara_control_desk_oscars_dossier_select_specs() as $mod => $definition ) {
        $fields[ substr( $mod, strlen( 'lunara_oscars_' ) ) ] = array( 'mod' => $mod, 'type' => 'select', 'default' => $definition['default'], 'allowed' => array_keys( $definition['options'] ), 'label' => $definition['label'], 'help' => $definition['note'] );
    }
    foreach ( lunara_control_desk_oscars_dossier_number_specs() as $mod => $definition ) {
        $fields[ substr( $mod, strlen( 'lunara_oscars_' ) ) ] = array( 'mod' => $mod, 'type' => 'int', 'value_scale' => 1, 'default' => $definition['default'], 'min' => $definition['min'], 'max' => $definition['max'], 'label' => $definition['label'], 'help' => $definition['note'] );
    }
    $fields['dossier_preset']['label'] = __( 'Preset package', 'lunara-film' );
    $fields['dossier_preset']['help'] = __( 'Selecting a package updates all candidate settings. Fine-tune them below, then Preview or Apply.', 'lunara-film' );
    return array( 'presentation' => $fields );
}
function lunara_site_studio_oscars_ledger_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_oscars_ledger_spec() ); }
function lunara_site_studio_oscars_ledger_read_state() { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_oscars_ledger_spec() ); }
function lunara_site_studio_oscars_ledger_validate_state( $candidate ) { return lunara_site_studio_mod_surface_validate_state( $candidate, lunara_site_studio_oscars_ledger_spec(), 'site_studio_oscars_ledger' ); }
function lunara_site_studio_oscars_ledger_save_state( $candidate ) { return lunara_site_studio_mod_surface_save_state( $candidate, 'oscars-ledger', lunara_site_studio_oscars_ledger_spec(), array( 'ceremony', 'category', 'title', 'person' ), 'site_studio_oscars_ledger' ); }
function lunara_site_studio_oscars_ledger_restore_revision( $id ) { return lunara_site_studio_mod_surface_restore_revision( $id, 'oscars-ledger', lunara_site_studio_oscars_ledger_spec(), 'site_studio_oscars_ledger' ); }
function lunara_site_studio_oscars_ledger_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'oscars-ledger', 'theme:oscars-ledger-presentation', array( 'read' => 'lunara_site_studio_oscars_ledger_read_state', 'validate' => 'lunara_site_studio_oscars_ledger_validate_state', 'save' => 'lunara_site_studio_oscars_ledger_save_state', 'restore' => 'lunara_site_studio_oscars_ledger_restore_revision' ) ); }

/** Fixed public examples, never an editor-supplied URL. Tokens retain canonical ceremony scope. */
function lunara_site_studio_oscars_ledger_preview_routes() {
    return array( 'ceremony' => '/oscars/ceremony/98/', 'category' => '/oscars/category/best-picture/', 'film' => '/oscars/title/tt30144839/', 'person' => '/oscars/name/nm0000759/' );
}
function lunara_site_studio_oscars_ledger_preview_request_path() {
    foreach ( lunara_site_studio_oscars_ledger_preview_routes() as $route ) {
        if ( lunara_site_studio_preview_request_path( $route ) ) { return true; }
    }
    return false;
}
function lunara_site_studio_oscars_ledger_packages() {
    $packages = array();
    foreach ( lunara_control_desk_oscars_dossier_preset_specs() as $name => $definition ) {
        $state = array( 'presentation' => array() );
        foreach ( $definition['values'] as $mod => $value ) { $state['presentation'][ substr( $mod, strlen( 'lunara_oscars_' ) ) ] = $value; }
        if ( ! is_wp_error( lunara_site_studio_oscars_ledger_validate_state( $state ) ) ) { $packages[ $name ] = $state['presentation']; }
    }
    return $packages;
}
add_filter( 'lunara_site_studio_surfaces', static function ( $surfaces ) {
    if ( isset( $surfaces['oscars-ledger'] ) ) {
        $surfaces['oscars-ledger'] = array_merge( $surfaces['oscars-ledger'], array( 'classic_url' => 'admin.php?page=lunara-site-studio&surface=oscars-ledger', 'supports_preview' => true, 'preview_route' => '/oscars/ceremony/98/', 'preview_query_arg' => 'lunara_oscars_ledger_preview', 'preview_params' => array(), 'adapter_factory' => 'lunara_site_studio_oscars_ledger_adapter', 'state_schema_callback' => 'lunara_site_studio_oscars_ledger_state_schema' ) );
    }
    return $surfaces;
} );
function lunara_site_studio_render_oscars_ledger_inspector( $state, $revisions, $classic_url = '' ) {
    $spec = lunara_site_studio_oscars_ledger_spec();
    echo '<p>' . esc_html__( 'These settings apply across Academy pages. Preview a representative Ceremony, Category, Film or Person before Apply.', 'lunara-film' ) . '</p><div data-ledger-preview-targets>';
    foreach ( array( 'ceremony' => 'Ceremony', 'category' => 'Category', 'film' => 'Film', 'person' => 'Person' ) as $key => $label ) { echo '<button type="button" class="button" data-ledger-preview-route="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</button> '; }
    echo '</div><div data-ledger-packages="' . esc_attr( wp_json_encode( lunara_site_studio_oscars_ledger_packages() ) ) . '">';
    lunara_site_studio_render_details_open( 'presentation', __( 'Dossier presentation', 'lunara-film' ), true, array( 'ceremony', 'category', 'title', 'person' ) );
    foreach ( $spec['presentation'] as $field => $definition ) { lunara_site_studio_render_control( 'presentation.' . $field, $state['presentation'][ $field ], $definition ); }
    lunara_site_studio_render_details_close();
    echo '</div>';
    lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) );
    echo '<button type="button" class="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button>';
    lunara_site_studio_render_details_close();
    lunara_site_studio_render_revisions( $revisions );
}
