<?php

define( 'ABSPATH', __DIR__ );
define( 'LUNARA_JOURNAL_FOUNDATION_VERSION', '1.2.1' );

class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public $taxonomy;

	public function __construct( $term_id, $name, $slug, $taxonomy = '' ) {
		$this->term_id = $term_id;
		$this->name    = $name;
		$this->slug    = $slug;
		$this->taxonomy = $taxonomy;
	}
}

$GLOBALS['lunara_test_fields']          = array();
$GLOBALS['lunara_test_meta']            = array();
$GLOBALS['lunara_test_terms']           = array();
$GLOBALS['lunara_test_filter_terms']    = array();
$GLOBALS['lunara_test_registered_cpts'] = array();
$GLOBALS['lunara_test_registered_tax']  = array();
$GLOBALS['lunara_test_cpt_exists']      = false;
$GLOBALS['lunara_test_tax_exists']      = false;

function add_action() {}
function add_filter() {}
function __($text) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function esc_url_raw( $value ) { return trim( (string) $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function absint( $value ) { return abs( (int) $value ); }
function is_wp_error( $value = null ) { return false; }
function get_theme_mod( $key, $default = '' ) { return $default; }
function get_the_ID() { return 1; }
function get_field( $key, $post_id ) { return $GLOBALS['lunara_test_fields'][ $post_id ][ $key ] ?? null; }
function get_post_meta( $post_id, $key ) { return $GLOBALS['lunara_test_meta'][ $post_id ][ $key ] ?? ''; }
function get_the_terms( $post_id, $taxonomy ) { return $GLOBALS['lunara_test_terms'][ $post_id ][ $taxonomy ] ?? array(); }
function get_term( $term_id, $taxonomy ) { return $GLOBALS['lunara_test_terms']['by_id'][ $taxonomy ][ $term_id ] ?? null; }
function get_terms( $args ) {
	$terms = $GLOBALS['lunara_test_filter_terms'][ $args['taxonomy'] ] ?? array();
	return isset( $args['number'] ) ? array_slice( $terms, 0, (int) $args['number'] ) : $terms;
}
function wp_list_pluck( $items, $field ) { return array_map( static function ( $item ) use ( $field ) { return $item->{$field}; }, $items ); }
function post_type_exists() { return (bool) $GLOBALS['lunara_test_cpt_exists']; }
function taxonomy_exists( $taxonomy = '' ) { return (bool) $GLOBALS['lunara_test_tax_exists']; }
function register_post_type( $post_type, $args ) { $GLOBALS['lunara_test_registered_cpts'][ $post_type ] = $args; }
function register_taxonomy( $taxonomy, $object_type, $args ) { $GLOBALS['lunara_test_registered_tax'][ $taxonomy ] = compact( 'object_type', 'args' ); }
function register_taxonomy_for_object_type( $taxonomy, $post_type ) { $GLOBALS['lunara_test_registered_tax'][ $taxonomy ] = array( 'attached' => $post_type ); }

require dirname( __DIR__, 2 ) . '/inc/journal-cpt.php';
require dirname( __DIR__, 2 ) . '/inc/journal-family.php';

$GLOBALS['lunara_test_fields'][1] = array(
	'journal_kicker'          => 'Canonical Kicker',
	'journal_deck'            => 'Canonical deck.',
	'journal_primary_section' => 91,
	'journal_priority'        => 'high',
	'journal_image_source_url' => 'https://images.tmdb.org/t/p/original/example.jpg',
	'journal_source_items'    => array(
		array(
			'source_headline'    => 'Canonical source headline',
			'source_publication' => 'Canonical Trade',
			'source_author'      => 'Reporter',
			'source_url'         => 'https://example.com/source',
		),
	),
);
$GLOBALS['lunara_test_meta'][1] = array(
	'_lunara_journal_kicker'     => 'Legacy Kicker',
	'_lunara_journal_signal_note' => 'Legacy note.',
	'_lunara_journal_featured'   => '0',
);
$GLOBALS['lunara_test_terms']['by_id']['journal_section'][91] = new WP_Term( 91, 'Awards Season', 'awards-season', 'journal_section' );
$GLOBALS['lunara_test_terms'][1]['journal_section'] = array( new WP_Term( 91, 'Awards Season', 'awards-season', 'journal_section' ) );
$GLOBALS['lunara_test_terms'][1]['journal_topic'] = array( new WP_Term( 92, 'Best Picture', 'best-picture', 'journal_topic' ) );

$GLOBALS['lunara_test_meta'][2] = array(
	'_lunara_journal_kicker'      => 'Legacy Only',
	'_lunara_journal_signal_note' => 'Legacy only note.',
	'_lunara_journal_featured'    => '1',
	'_lunara_journal_source_name' => 'Legacy Trade',
	'_lunara_journal_source_url'  => 'https://legacy.example/source',
);
$GLOBALS['lunara_test_terms'][2]['journal_type'] = array( new WP_Term( 7, 'Legacy Type', 'legacy-type', 'journal_type' ) );

$GLOBALS['lunara_test_fields'][3]['journal_priority'] = 'normal';
$GLOBALS['lunara_test_meta'][3]['_lunara_journal_featured'] = '1';

$GLOBALS['lunara_test_cpt_exists'] = true;
lunara_register_journal_cpt();
$cpt_yielded = empty( $GLOBALS['lunara_test_registered_cpts'] );
$GLOBALS['lunara_test_cpt_exists'] = false;
lunara_register_journal_cpt();
$cpt_fallback = isset( $GLOBALS['lunara_test_registered_cpts']['journal'] )
	&& 'journal' === $GLOBALS['lunara_test_registered_cpts']['journal']['has_archive']
	&& 'journal' === $GLOBALS['lunara_test_registered_cpts']['journal']['rewrite']['slug'];

$GLOBALS['lunara_test_tax_exists'] = true;
lunara_register_journal_type_taxonomy();
$taxonomy_yielded = isset( $GLOBALS['lunara_test_registered_tax']['journal_type']['attached'] );
$GLOBALS['lunara_test_registered_tax'] = array();
$GLOBALS['lunara_test_tax_exists'] = false;
lunara_register_journal_type_taxonomy();
$taxonomy_fallback = isset( $GLOBALS['lunara_test_registered_tax']['journal_type'] )
	&& 'journal-type' === $GLOBALS['lunara_test_registered_tax']['journal_type']['args']['rewrite']['slug'];

$GLOBALS['lunara_test_tax_exists'] = true;
for ( $term_index = 1; $term_index <= 12; $term_index++ ) {
	$GLOBALS['lunara_test_filter_terms']['journal_section'][] = new WP_Term(
		$term_index,
		'Section ' . $term_index,
		'section-' . $term_index,
		'journal_section'
	);
}
$current_filter_term = new WP_Term( 99, 'Current Section', 'current-section', 'journal_section' );
$bounded_filter_terms = lunara_get_journal_archive_filter_terms( 'journal_section', 8 );
$active_filter_terms  = lunara_get_journal_archive_filter_terms( 'journal_section', 8, $current_filter_term );

echo json_encode(
	array(
		'foundation_active'          => lunara_journal_foundation_is_active(),
		'canonical_kicker'           => 'Canonical Kicker' === lunara_get_journal_kicker( 1 ),
		'legacy_kicker'              => 'Legacy Only' === lunara_get_journal_kicker( 2 ),
		'canonical_deck'             => 'Canonical deck.' === lunara_get_journal_signal_note( 1 ),
		'legacy_note'                => 'Legacy only note.' === lunara_get_journal_signal_note( 2 ),
		'canonical_section'          => 'Awards Season' === lunara_get_journal_section_label( 1 ),
		'legacy_section'             => 'Legacy Type' === lunara_get_journal_section_label( 2 ),
		'canonical_source'           => 'Canonical Trade' === lunara_get_journal_source_items( 1 )[0]['publication'],
		'legacy_source'              => 'Legacy Trade' === lunara_get_journal_source_items( 2 )[0]['publication'],
		'canonical_featured'         => lunara_journal_is_featured( 1 ),
		'canonical_normal_overrides' => ! lunara_journal_is_featured( 3 ),
		'legacy_featured'            => lunara_journal_is_featured( 2 ),
		'image_source_isolated'       => 'images.tmdb.org' === lunara_get_journal_image_source_pair( 1, 0 )['name']
			&& 'https://images.tmdb.org/t/p/original/example.jpg' === lunara_get_journal_image_source_pair( 1, 0 )['url'],
		'canonical_primary_terms'     => 'journal_section' === lunara_get_journal_primary_classification_terms( 1 )[0]->taxonomy,
		'legacy_primary_terms'        => 'journal_type' === lunara_get_journal_primary_classification_terms( 2 )[0]->taxonomy,
		'canonical_related_query'     => 'journal_section' === lunara_get_journal_related_tax_query( 1 )[0]['taxonomy']
			&& 'journal_topic' === lunara_get_journal_related_tax_query( 1 )[1]['taxonomy'],
		'legacy_related_query'        => 'journal_type' === lunara_get_journal_related_tax_query( 2 )[0]['taxonomy'],
		'bounded_filter_terms'         => 8 === count( $bounded_filter_terms ),
		'active_filter_term_retained' => 9 === count( $active_filter_terms )
			&& in_array( 99, array_map( 'absint', wp_list_pluck( $active_filter_terms, 'term_id' ) ), true ),
		'cpt_yielded'                => $cpt_yielded,
		'cpt_fallback'               => $cpt_fallback,
		'taxonomy_yielded'           => $taxonomy_yielded,
		'taxonomy_fallback'          => $taxonomy_fallback,
	)
);
