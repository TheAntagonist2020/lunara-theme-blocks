<?php
/**
 * Debrief page editor: real provider, transactions, private preview install and
 * the inventories every layer must agree on (registry, preview pilot, template
 * markers, workspace JS, preview bridge JS).
 *
 * Run: php tests/site-studio-debrief-method-runtime.php
 */
require __DIR__ . '/site-studio-utility-recovery-fixture.php';
if ( ! defined( 'OBJECT' ) ) { define( 'OBJECT', 'OBJECT' ); }
function get_page_by_path( $path, $output = OBJECT, $type = 'page' ) { return $GLOBALS['debrief_page'] ?? null; }
function wp_strip_all_tags_debrief( $text ) { return strip_tags( (string) $text ); }
require_once dirname( __DIR__ ) . '/inc/debrief-method.php';
require_once dirname( __DIR__ ) . '/inc/site-studio-debrief-method.php';

$checks = 0;
function debrief_assert( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }
function debrief_snapshot() { return serialize( array( $GLOBALS['lunara_pilot_theme_mods'], $GLOBALS['lunara_pilot_options'], $GLOBALS['lunara_pilot_option_autoload'] ) ); }

$adapter  = lunara_site_studio_debrief_method_adapter();
$spec     = lunara_site_studio_debrief_method_spec();
$sections = lunara_site_studio_debrief_method_sections();
$keys     = lunara_site_studio_mod_surface_keys( $spec );

// Storage keys are unique, namespaced and never shared with another surface.
debrief_assert( count( $keys ) === count( array_unique( $keys ) ), 'Every Debrief setting owns a distinct theme mod.' );
foreach ( $keys as $key ) { debrief_assert( 0 === strpos( $key, 'lunara_debrief_' ), 'Debrief mods are namespaced: ' . $key ); }
$foreign = array_merge( lunara_site_studio_mod_surface_keys( lunara_site_studio_utility_search_spec() ), lunara_site_studio_mod_surface_keys( lunara_site_studio_utility_404_spec() ) );
debrief_assert( array() === array_intersect( $keys, $foreign ), 'Debrief mods never collide with Search/404 mods.' );
debrief_assert( array( 'hero', 'moves', 'why', 'specimen', 'desk', 'canon', 'recent', 'next' ) === array_keys( $spec ), 'Spec groups follow page order.' );
debrief_assert( lunara_site_studio_debrief_method_state_schema() === lunara_site_studio_mod_surface_schema( $spec ), 'Client schema is the storage-key-free projection of the spec.' );

// Defaults reproduce the shipped 3.2.89 copy exactly.
utility_reset();
$settings = lunara_debrief_method_settings();
$roles    = lunara_debrief_method_roles();
debrief_assert( 'A Lunara Film Signature' === $settings['hero']['kicker'] && '' === $settings['hero']['title'] && true === $settings['hero']['show_stats'], 'Unsaved hero keeps 3.2.89 defaults.' );
debrief_assert( 0 === strpos( $settings['hero']['thesis'], 'Every review on Lunara ends the same way' ), 'Unsaved thesis keeps 3.2.89 copy.' );
foreach ( $roles as $slug => $role ) {
	foreach ( array( 'question', 'copy', 'not' ) as $part ) { debrief_assert( $role[ $part ] === $settings['moves'][ $slug . '_' . $part ], "Move default {$slug}.{$part} matches the canonical role copy." ); }
}
debrief_assert( 2 === count( lunara_debrief_method_paragraphs( $settings['why']['body'] ) ), 'Why Three default keeps its two paragraphs.' );
debrief_assert( 8 === $settings['canon']['count'] && 2 === $settings['canon']['min_count'] && 8 === $settings['recent']['count'] && 0 === $settings['specimen']['review_id'], 'Default counts match 3.2.89 (8 canon, 8 recent, automatic specimen).' );

// Provider contract: read has no writes, defaults validate, every field fails in focus with zero writes.
$before  = debrief_snapshot();
$default = $adapter->read_state();
debrief_assert( $before === debrief_snapshot(), 'Read has no writes.' );
debrief_assert( ! is_wp_error( $adapter->validate_state( $default ) ), 'Current defaults validate.' );
foreach ( $spec as $group => $fields ) {
	foreach ( $fields as $field => $definition ) {
		$path = $group . '.' . $field;
		$bad  = $default; $bad[ $group ][ $field ] = array( 'wrong' );
		$error = $adapter->save_state( $bad );
		debrief_assert( is_wp_error( $error ) && array( $path ) === array_keys( lunara_site_studio_safe_validation_fields( $error ) ), 'Exact focused error for ' . $path );
		debrief_assert( $before === debrief_snapshot(), 'Invalid value has zero writes: ' . $path );
		if ( in_array( $definition['type'], array( 'text', 'textarea' ), true ) ) {
			$bad = $default; $bad[ $group ][ $field ] = str_repeat( 'é', $definition['max_length'] );
			debrief_assert( ! is_wp_error( $adapter->validate_state( $bad ) ), 'Unicode BMP limit accepted ' . $path );
			$bad[ $group ][ $field ] .= 'é';
			debrief_assert( is_wp_error( $adapter->validate_state( $bad ) ), 'Unicode BMP overflow rejected ' . $path );
		} elseif ( 'int' === $definition['type'] ) {
			foreach ( array( $definition['min'], $definition['max'] ) as $boundary ) { $bad = $default; $bad[ $group ][ $field ] = $boundary; debrief_assert( ! is_wp_error( $adapter->validate_state( $bad ) ), 'Integer boundary accepted ' . $path ); }
			foreach ( array( $definition['min'] - 1, $definition['max'] + 1, 2.5, '8', true ) as $invalid ) { $bad = $default; $bad[ $group ][ $field ] = $invalid; debrief_assert( is_wp_error( $adapter->save_state( $bad ) ) && $before === debrief_snapshot(), 'Invalid number rejected without writes ' . $path . ' ' . json_encode( $invalid ) ); }
		} elseif ( 'bool' === $definition['type'] ) {
			foreach ( array( 0, 1, 'false', 'true' ) as $invalid ) { $bad = $default; $bad[ $group ][ $field ] = $invalid; debrief_assert( is_wp_error( $adapter->save_state( $bad ) ) && $before === debrief_snapshot(), 'Toggle requires a real boolean ' . $path ); }
		}
	}
}
$partial = $default; unset( $partial['canon']['count'] );
debrief_assert( is_wp_error( $adapter->save_state( $partial ) ) && $before === debrief_snapshot(), 'Partial candidates cannot publish.' );
$extra = $default; $extra['unexpected'] = array();
debrief_assert( is_wp_error( $adapter->save_state( $extra ) ), 'Unknown candidate group rejected.' );

// Apply writes exactly the spec's mods, the public reader follows, History restores absence.
$candidate = $default;
$candidate['hero']['title']          = 'The Debrief, Explained';
$candidate['hero']['thesis']         = "Three films.\nOne argument.";
$candidate['moves']['theme_not']     = '';
$candidate['why']['body']            = "First thought.\n\nSecond thought.";
$candidate['canon']['count']         = 4;
$candidate['canon']['show_reviews']  = false;
$candidate['recent']['show']         = false;
$candidate['specimen']['review_id']  = 103064;
$saved = $adapter->save_state( $candidate );
debrief_assert( ! is_wp_error( $saved ) && $candidate === $saved['state'], 'Apply returns the canonical saved state.' );
debrief_assert( $sections === $saved['changed_sections'], 'Apply reports every page section as changed.' );
$written = array_keys( $GLOBALS['lunara_pilot_theme_mods'] ); sort( $written ); $expected = $keys; sort( $expected );
debrief_assert( $expected === $written, 'Apply writes exactly the Debrief mods.' );
$live = lunara_debrief_method_settings();
debrief_assert( 'The Debrief, Explained' === $live['hero']['title'] && 4 === $live['canon']['count'] && false === $live['recent']['show'] && 103064 === $live['specimen']['review_id'], 'Public reader follows the applied settings.' );
debrief_assert( "Three films.\nOne argument." === $live['hero']['thesis'], 'Multiline thesis survives Apply.' );
debrief_assert( array( 'First thought.', 'Second thought.' ) === lunara_debrief_method_paragraphs( $live['why']['body'] ), 'Blank lines split the Why Three body into paragraphs.' );
debrief_assert( '' === lunara_debrief_method_display_roles( $live )['theme']['not'], 'An emptied "what it is not" line is honoured.' );
$restored = $adapter->restore_revision( $saved['revision_id'] );
debrief_assert( ! is_wp_error( $restored ) && $default === $restored['state'] && array() === array_intersect( array_keys( $GLOBALS['lunara_pilot_theme_mods'] ), $keys ), 'History restores the original absence of every Debrief mod.' );
$safety = $adapter->restore_revision( $restored['safety_revision_id'] );
debrief_assert( ! is_wp_error( $safety ) && $candidate === $safety['state'], 'The restore safety revision is itself undoable.' );

// Injected write failures roll back exactly (from a clean site, so every mod is a real write).
utility_reset();
foreach ( array( 'fail', 'mismatch', 'throw_after', 'read_throw_after' ) as $mode ) {
	$before = debrief_snapshot();
	$GLOBALS['lunara_pilot_mod_fault'] = array( 'key' => $keys[0], 'mode' => $mode, 'remaining' => 1 );
	debrief_assert( is_wp_error( $adapter->save_state( $default ) ), 'Injected write failure surfaces: ' . $mode );
	debrief_assert( $before === debrief_snapshot(), 'Injected write failure rolls back exactly: ' . $mode );
}
$before = debrief_snapshot();
$GLOBALS['lunara_pilot_option_fault'] = array( 'key' => lunara_site_studio_revision_option_name( 'debrief-method' ), 'mode' => 'mismatch', 'remaining' => 1 );
debrief_assert( is_wp_error( $adapter->save_state( $default ) ) && $before === debrief_snapshot(), 'Revision failure rolls back settings and history.' );

// The public reader never trusts a stored value the editor could not have written.
utility_reset();
$GLOBALS['lunara_pilot_theme_mods'] = array( 'lunara_debrief_canon_count' => 99, 'lunara_debrief_recent_count' => 'eight', 'lunara_debrief_show_canon' => 'yes', 'lunara_debrief_hero_kicker' => str_repeat( 'x', 500 ), 'lunara_debrief_why_title' => array( 'bad' ) );
$guarded = lunara_debrief_method_settings();
debrief_assert( 8 === $guarded['canon']['count'] && 8 === $guarded['recent']['count'] && true === $guarded['canon']['show'], 'Out-of-range, non-numeric and non-boolean mods fall back to defaults.' );
debrief_assert( 'A Lunara Film Signature' === $guarded['hero']['kicker'] && 'An algorithm recommends more of the same. A critic recommends a conversation.' === $guarded['why']['title'], 'Oversized and non-string text falls back to defaults.' );

// Private preview: a valid candidate installs request-local filters only.
utility_reset();
$preview = $adapter->read_state(); $preview['hero']['kicker'] = 'Private Debrief Preview'; $preview['canon']['count'] = 3;
debrief_assert( lunara_site_studio_preview_state_safe( 'debrief-method', $preview ), 'A validated candidate is preview-safe.' );
$unsafe = $preview; $unsafe['canon']['count'] = 400;
debrief_assert( ! lunara_site_studio_preview_state_safe( 'debrief-method', $unsafe ), 'An out-of-bounds candidate is never installed.' );
$before = debrief_snapshot();
debrief_assert( lunara_site_studio_preview_install_state( 'debrief-method', $preview, 0 ), 'Preview installs the candidate.' );
$previewed = lunara_debrief_method_settings();
debrief_assert( 'Private Debrief Preview' === $previewed['hero']['kicker'] && 3 === $previewed['canon']['count'], 'The public reader renders the previewed candidate.' );
debrief_assert( $before === debrief_snapshot(), 'Preview writes nothing.' );

// Dependency: available only for a published, public page at /debrief/.
utility_reset();
$GLOBALS['debrief_page'] = null;
debrief_assert( false === lunara_site_studio_debrief_method_dependency()['available'], 'No page, no editor.' );
$GLOBALS['debrief_page'] = new WP_Post( 33090, 'page', 'draft' ); $GLOBALS['lunara_pilot_journal_url'] = home_url( '/debrief/' );
debrief_assert( false === lunara_site_studio_debrief_method_dependency()['available'], 'A draft page keeps the editor unavailable.' );
$GLOBALS['debrief_page'] = new WP_Post( 33090, 'page', 'publish' );
debrief_assert( true === lunara_site_studio_debrief_method_dependency()['available'], 'A published /debrief/ page makes the editor available.' );
$GLOBALS['lunara_pilot_journal_url'] = home_url( '/about/debrief/' );
$moved = lunara_site_studio_debrief_method_dependency();
debrief_assert( false === $moved['available'] && 'debrief_page_unavailable' === $moved['reason'] && '' !== $moved['message'], 'A page moved off the preview route explains why the editor is unavailable.' );
unset( $GLOBALS['lunara_pilot_journal_url'], $GLOBALS['debrief_page'] );

// Every layer agrees on the surface identity and its section markers.
$pilots = lunara_site_studio_preview_pilots();
debrief_assert( isset( $pilots['debrief-method'] ) && 'theme:debrief-method' === $pilots['debrief-method']['owner'] && 'lunara_debrief_method_preview' === $pilots['debrief-method']['query'] && lunara_site_studio_debrief_method_preview_route() === $pilots['debrief-method']['route'] && 'site-studio' === $pilots['debrief-method']['storage'] && $sections === $pilots['debrief-method']['markers'], 'Preview pilot matches the provider.' );
$registry = file_get_contents( dirname( __DIR__ ) . '/inc/site-studio-registry.php' );
debrief_assert( 1 === preg_match( "/'debrief-method' => array\((.*?)\n\t\t\t\),/s", $registry, $entry ) && false !== strpos( $entry[1], "'preview_query_arg'     => 'lunara_debrief_method_preview'" ) && false !== strpos( $entry[1], "'owner'                 => 'theme:debrief-method'" ) && false !== strpos( $entry[1], "'sections'              => array( '" . implode( "', '", $sections ) . "' )" ), 'Registry entry matches the provider.' );
preg_match_all( '/data-lunara-site-studio-section="([a-z-]+)"/', file_get_contents( dirname( __DIR__ ) . '/page-debrief.php' ), $template_markers );
debrief_assert( $sections === $template_markers[1], 'The template marks every section, once, in page order.' );
$quoted = "'" . implode( "','", $sections ) . "'";
$workspace_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/lunara-site-studio.js' );
debrief_assert( false !== strpos( $workspace_js, "'debrief-method':{query:'lunara_debrief_method_preview',route:'/debrief/',params:{},markers:[" . $quoted . ']}' ) && false !== strpos( $workspace_js, "'debrief-method':['preview','save','discard','reset-candidate']" ), 'Workspace JS knows the surface, its markers and its actions.' );
$bridge_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/lunara-site-studio-preview.js' );
debrief_assert( false !== strpos( $bridge_js, "'debrief-method': ['" . implode( "', '", $sections ) . "']" ), 'Preview bridge JS accepts exactly the Debrief markers.' );

// Inspector renders every control once, with no storage key exposed.
utility_reset();
$GLOBALS['debrief_page'] = new WP_Post( 33090, 'page', 'publish' );
function lunara_site_studio_render_revisions( $revisions ) { echo '<div data-revisions></div>'; }
if ( ! function_exists( 'esc_html__' ) ) { function esc_html__( $text ) { return esc_html( $text ); } }
if ( ! function_exists( 'esc_attr__' ) ) { function esc_attr__( $text ) { return esc_attr( $text ); } }
utility_load_functions( dirname( __DIR__ ) . '/inc/site-studio.php', array( 'lunara_site_studio_render_details_open', 'lunara_site_studio_render_details_close', 'lunara_site_studio_control_label', 'lunara_site_studio_choice_label', 'lunara_site_studio_render_control' ) );
ob_start(); lunara_site_studio_render_debrief_method_inspector( $adapter->read_state(), array() ); $inspector = ob_get_clean();
foreach ( $spec as $group => $fields ) { foreach ( $fields as $field => $definition ) { debrief_assert( 1 === substr_count( $inspector, 'data-field-path="' . $group . '.' . $field . '"' ), 'Inspector renders ' . $group . '.' . $field . ' once.' ); } }
foreach ( $keys as $key ) { debrief_assert( false === strpos( $inspector, $key ), 'Inspector never exposes storage key ' . $key ); }
debrief_assert( 1 === substr_count( $inspector, 'data-action="reset-candidate"' ), 'Inspector offers Reset candidate.' );
debrief_assert( false === strpos( $inspector, 'post.php?post=33090' ), 'The page-content handoff is withheld from a user who cannot edit that page.' );

echo "Debrief page editor runtime passed: {$checks} checks.\n";
