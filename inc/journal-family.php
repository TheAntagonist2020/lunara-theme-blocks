<?php
/**
 * Journal public data and route adapters.
 *
 * Keeps canonical Foundation fields and taxonomies compatible with historical
 * Theme metadata without coupling presentation/query behavior to CPT ownership.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lunara_journal_field_has_value( $value ) {
	if ( null === $value || false === $value || '' === $value ) {
		return false;
	}

	return ! is_array( $value ) || ! empty( $value );
}

function lunara_get_journal_field_value( $post_id, $canonical_key, $legacy_keys = array(), $default = '' ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return $default;
	}

	$canonical_key = sanitize_key( (string) $canonical_key );
	if ( '' !== $canonical_key ) {
		$value = function_exists( 'get_field' )
			? get_field( $canonical_key, $post_id )
			: get_post_meta( $post_id, $canonical_key, true );

		if ( lunara_journal_field_has_value( $value ) ) {
			return $value;
		}
	}

	foreach ( (array) $legacy_keys as $legacy_key ) {
		$value = get_post_meta( $post_id, (string) $legacy_key, true );
		if ( lunara_journal_field_has_value( $value ) ) {
			return $value;
		}
	}

	return $default;
}

function lunara_get_journal_source_items( $post_id ) {
	$items = lunara_get_journal_field_value( $post_id, 'journal_source_items', array(), array() );
	$out   = array();

	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$normalized = array(
				'headline'    => sanitize_text_field( isset( $item['source_headline'] ) ? $item['source_headline'] : '' ),
				'publication' => sanitize_text_field( isset( $item['source_publication'] ) ? $item['source_publication'] : '' ),
				'author'      => sanitize_text_field( isset( $item['source_author'] ) ? $item['source_author'] : '' ),
				'url'         => esc_url_raw( isset( $item['source_url'] ) ? $item['source_url'] : '' ),
			);

			if ( array_filter( $normalized ) ) {
				$out[] = $normalized;
			}
		}
	}

	if ( ! empty( $out ) ) {
		return $out;
	}

	$legacy_name = lunara_get_journal_field_value(
		$post_id,
		'',
		array( '_lunara_journal_source_name', '_lunara_source_name', '_lunara_dispatch_source_label' )
	);
	$legacy_url  = lunara_get_journal_field_value(
		$post_id,
		'',
		array( '_lunara_journal_source_url', '_lunara_source_url', '_lunara_dispatch_source_url' )
	);

	if ( lunara_journal_field_has_value( $legacy_name ) || lunara_journal_field_has_value( $legacy_url ) ) {
		$out[] = array(
			'headline'    => '',
			'publication' => sanitize_text_field( (string) $legacy_name ),
			'author'      => '',
			'url'         => esc_url_raw( (string) $legacy_url ),
		);
	}

	return $out;
}

/**
 * Resolve an image-specific source name/URL pair without borrowing editorial
 * article provenance. Canonical URLs derive their own display host because
 * Foundation deliberately stores the image URL and credit, not an outlet name.
 *
 * @param int $post_id       Journal post ID.
 * @param int $attachment_id Featured image attachment ID.
 * @return array{name:string,url:string}
 */
function lunara_get_journal_image_source_pair( $post_id, $attachment_id ) {
	$post_id       = absint( $post_id );
	$attachment_id = absint( $attachment_id );
	$source_name   = '';
	$source_url    = trim( (string) lunara_get_journal_field_value( $post_id, 'journal_image_source_url' ) );

	if ( '' === $source_url ) {
		$image_source_pairs = array(
			array( $post_id, '_lunara_featured_image_source_name', '_lunara_featured_image_source_url' ),
			array( $post_id, '_lunara_image_source_name', '_lunara_image_source_url' ),
			array( $attachment_id, '_lunara_image_source_name', '_lunara_image_source_url' ),
			array( $attachment_id, '_lunara_dispatch_source_label', '_lunara_dispatch_source_url' ),
		);

		foreach ( $image_source_pairs as $image_source_pair ) {
			$pair_name = trim( (string) get_post_meta( $image_source_pair[0], $image_source_pair[1], true ) );
			$pair_url  = trim( (string) get_post_meta( $image_source_pair[0], $image_source_pair[2], true ) );

			if ( '' === $pair_name && '' === $pair_url ) {
				continue;
			}

			$source_name = $pair_name;
			$source_url  = $pair_url;
			break;
		}
	}

	if ( '' === $source_name && '' !== $source_url ) {
		$source_host = wp_parse_url( $source_url, PHP_URL_HOST );
		$source_name = $source_host
			? preg_replace( '/^www\./i', '', (string) $source_host )
			: __( 'Image source', 'lunara-film' );
	}

	return array(
		'name' => sanitize_text_field( (string) $source_name ),
		'url'  => esc_url_raw( (string) $source_url ),
	);
}

function lunara_get_journal_section_label( $post_id ) {
	$primary = lunara_get_journal_field_value( $post_id, 'journal_primary_section' );
	$term    = null;

	if ( $primary instanceof WP_Term ) {
		$term = $primary;
	} elseif ( is_array( $primary ) && isset( $primary['name'] ) ) {
		return sanitize_text_field( $primary['name'] );
	} elseif ( is_numeric( $primary ) ) {
		$term = get_term( absint( $primary ), 'journal_section' );
	}

	if ( $term instanceof WP_Term ) {
		return sanitize_text_field( $term->name );
	}

	$terms = get_the_terms( $post_id, 'journal_section' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return sanitize_text_field( $terms[0]->name );
	}

	$terms = get_the_terms( $post_id, 'journal_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return sanitize_text_field( $terms[0]->name );
	}

	return '';
}

function lunara_get_journal_primary_classification_terms( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return array();
	}

	$terms = get_the_terms( $post_id, 'journal_section' );
	if ( is_array( $terms ) && ! empty( $terms ) ) {
		return array_values( array_filter( $terms, static function ( $term ) {
			return $term instanceof WP_Term;
		} ) );
	}

	$terms = get_the_terms( $post_id, 'journal_type' );
	return is_array( $terms )
		? array_values( array_filter( $terms, static function ( $term ) {
			return $term instanceof WP_Term;
		} ) )
		: array();
}

function lunara_get_journal_related_tax_query( $post_id ) {
	$post_id           = absint( $post_id );
	$canonical_clauses = array();

	foreach ( array( 'journal_section', 'journal_topic' ) as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			continue;
		}

		$term_ids = array_values( array_filter( array_map( 'absint', wp_list_pluck( $terms, 'term_id' ) ) ) );
		if ( empty( $term_ids ) ) {
			continue;
		}

		$canonical_clauses[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $term_ids,
		);
	}

	if ( ! empty( $canonical_clauses ) ) {
		if ( count( $canonical_clauses ) > 1 ) {
			$canonical_clauses['relation'] = 'OR';
		}

		return $canonical_clauses;
	}

	$legacy_terms = get_the_terms( $post_id, 'journal_type' );
	if ( ! is_array( $legacy_terms ) || empty( $legacy_terms ) ) {
		return array();
	}

	$legacy_ids = array_values( array_filter( array_map( 'absint', wp_list_pluck( $legacy_terms, 'term_id' ) ) ) );
	if ( empty( $legacy_ids ) ) {
		return array();
	}

	return array(
		array(
			'taxonomy' => 'journal_type',
			'field'    => 'term_id',
			'terms'    => $legacy_ids,
		),
	);
}

function lunara_journal_has_section( $post_id ) {
	if ( lunara_journal_field_has_value( lunara_get_journal_field_value( $post_id, 'journal_primary_section' ) ) ) {
		return true;
	}

	$terms = get_the_terms( $post_id, 'journal_section' );
	if ( is_array( $terms ) && ! empty( $terms ) ) {
		return true;
	}

	$terms = get_the_terms( $post_id, 'journal_type' );
	return is_array( $terms ) && ! empty( $terms );
}

function lunara_journal_is_featured( $post_id ) {
	$priority = lunara_get_journal_field_value( $post_id, 'journal_priority' );
	if ( lunara_journal_field_has_value( $priority ) ) {
		return in_array( sanitize_key( (string) $priority ), array( 'high', 'breaking' ), true );
	}

	return '1' === (string) get_post_meta( $post_id, '_lunara_journal_featured', true );
}

function lunara_get_journal_kicker( $post_id = 0 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$normalize_kicker = static function ( $label ) {
		$label = trim( (string) $label );
		if ( in_array( $label, array( 'News', 'Dispatch', 'Dispatches', 'Dispatches & Audio' ), true ) ) {
			return __( 'Journal', 'lunara-film' );
		}

		return $label;
	};

	$override = trim( (string) lunara_get_journal_field_value( $post_id, 'journal_kicker', '_lunara_journal_kicker' ) );
	if ( '' !== $override ) {
		return $normalize_kicker( $override );
	}

	$section_label = lunara_get_journal_section_label( $post_id );
	if ( '' !== $section_label ) {
		return $normalize_kicker( $section_label );
	}

	$customizer_default = trim( (string) get_theme_mod( 'lunara_post_signal_kicker_default', '' ) );
	if ( '' !== $customizer_default ) {
		return $normalize_kicker( $customizer_default );
	}

	return __( 'Journal', 'lunara-film' );
}

function lunara_get_journal_signal_note( $post_id = 0 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$override = trim( (string) lunara_get_journal_field_value( $post_id, 'journal_deck', '_lunara_journal_signal_note' ) );
	if ( '' !== $override ) {
		return $override;
	}

	$terms = lunara_get_journal_primary_classification_terms( $post_id );
	if ( ! empty( $terms ) ) {
		$term_slug       = $terms[0]->slug;
		$mod_key         = 'lunara_post_signal_' . str_replace( '-', '_', $term_slug );
		$customizer_note = trim( (string) get_theme_mod( $mod_key, '' ) );
		if ( '' !== $customizer_note ) {
			return $customizer_note;
		}
	}

	return '';
}

/**
 * Return a bounded archive filter lane while preserving the active term even
 * when it falls outside the highest-count slice.
 *
 * @param string       $taxonomy    Journal taxonomy name.
 * @param int          $limit       Maximum ranked terms to request.
 * @param WP_Term|null $current_term Active taxonomy term, when applicable.
 * @return WP_Term[]
 */
function lunara_get_journal_archive_filter_terms( $taxonomy, $limit, $current_term = null ) {
	$taxonomy = sanitize_key( (string) $taxonomy );
	$limit    = max( 1, absint( $limit ) );

	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit,
		)
	);

	if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
		$terms = array();
	} else {
		$terms = array_values( array_filter( $terms, static function ( $term ) {
			return $term instanceof WP_Term;
		} ) );
	}

	if ( $current_term instanceof WP_Term && $taxonomy === $current_term->taxonomy ) {
		$term_ids = array_map( 'absint', wp_list_pluck( $terms, 'term_id' ) );
		if ( ! in_array( absint( $current_term->term_id ), $term_ids, true ) ) {
			$terms[] = $current_term;
		}
	}

	return $terms;
}

function lunara_is_journal_archive_family() {
	return is_post_type_archive( 'journal' )
		|| is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) );
}

function lunara_journal_archive_family_body_classes( $classes ) {
	if ( is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) ) ) {
		$classes[] = 'post-type-archive-journal';
		$classes[] = 'lunara-journal-taxonomy-archive';
	}

	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'lunara_journal_archive_family_body_classes' );
