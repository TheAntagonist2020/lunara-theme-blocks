<?php
/**
 * Read-time compatibility for the original Pick / Fact rewrite registration order.
 *
 * Existing taxonomy rules can sit behind CPT attachment rules in the saved option.
 * Promote only exact, already-present core rules; never flush or rewrite the option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the known core rule pairs for one of the two established public slugs. */
function lunara_oscar_taxonomy_rewrite_pairs( $base, $post_type, $taxonomy ) {
	$feed = '(feed|rdf|rss|rss2|atom)';
	$term = $base . '/category/([^/]+)';
	$tax_query = 'index.php?' . $taxonomy . '=$matches[1]';
	$taxonomy_rules = array(
		$term . '/feed/' . $feed . '/?$' => $tax_query . '&feed=$matches[2]',
		$term . '/' . $feed . '/?$' => $tax_query . '&feed=$matches[2]',
		$term . '/embed/?$' => $tax_query . '&embed=true',
		$term . '/page/?([0-9]{1,})/?$' => $tax_query . '&paged=$matches[2]',
		$term . '/?$' => $tax_query,
	);
	$tails = array(
		'/?$' => '',
		'/trackback/?$' => '&tb=1',
		'/feed/' . $feed . '/?$' => '&feed=$matches[2]',
		'/' . $feed . '/?$' => '&feed=$matches[2]',
		'/comment-page-([0-9]{1,})/?$' => '&cpage=$matches[2]',
		'/embed/?$' => '&embed=true',
	);
	$post_rules = array();
	foreach ( array( $base . '/[^/]+/attachment/([^/]+)', $base . '/[^/]+/([^/]+)' ) as $attachment ) {
		foreach ( $tails as $tail => $query_tail ) {
			$post_rules[ $attachment . $tail ] = 'index.php?attachment=$matches[1]' . $query_tail;
		}
	}
	// A term named "feed", "embed", or a number also overlaps ordinary single rules.
	$single = $base . '/([^/]+)';
	$single_query = 'index.php?' . $post_type . '=$matches[1]';
	foreach ( $tails as $tail => $query_tail ) {
		if ( '/?$' !== $tail ) {
			$post_rules[ $single . $tail ] = $single_query . $query_tail;
		}
	}
	$post_rules[ $single . '/page/?([0-9]{1,})/?$' ] = $single_query . '&paged=$matches[2]';
	$post_rules[ $single . '(?:/([0-9]+))?/?$' ] = $single_query . '&page=$matches[2]';
	return array( $taxonomy_rules, $post_rules );
}

/** Keep all other rule values and their relative precedence exactly as stored. */
function lunara_oscar_taxonomy_prefer_existing_rules( $rules ) {
	if ( ! is_array( $rules ) || ! $rules ) {
		return $rules;
	}
	foreach ( $rules as $regex => $query ) {
		if ( ! is_string( $regex ) || ! is_string( $query ) ) {
			return $rules;
		}
	}
	foreach ( array( '', 'index.php/' ) as $root ) {
		foreach ( array( 'oscar-picks' => array( 'lunara_oscar_pick', 'oscar_pick_category' ), 'oscar-facts' => array( 'oscar_fact', 'oscar_fact_category' ) ) as $slug => $types ) {
			list( $taxonomy_rules, $post_rules ) = lunara_oscar_taxonomy_rewrite_pairs( $root . $slug, $types[0], $types[1] );
			$anchor = null;
			$promote = array();
			$last_taxonomy = null;
			foreach ( $rules as $regex => $query ) {
				if ( null === $anchor && isset( $post_rules[ $regex ] ) && $post_rules[ $regex ] === $query ) {
					$anchor = $regex;
				}
				if ( null !== $anchor && isset( $taxonomy_rules[ $regex ] ) && $taxonomy_rules[ $regex ] === $query ) {
					$promote[ $regex ] = $query;
				}
				if ( null !== $anchor && isset( $taxonomy_rules[ $regex ] ) ) {
					$last_taxonomy = $regex;
				}
			}
			if ( ! $promote ) {
				continue;
			}
			// An interleaved custom rule may deliberately own a deeper taxonomy route.
			// Only move across a contiguous segment of exact known rules for this family.
			$inside = false;
			foreach ( $rules as $regex => $query ) {
				$inside = $inside || $regex === $anchor;
				if ( $inside && ! ( isset( $post_rules[ $regex ] ) && $post_rules[ $regex ] === $query ) && ! ( isset( $taxonomy_rules[ $regex ] ) && $taxonomy_rules[ $regex ] === $query ) ) {
					$promote = array();
					break;
				}
				if ( $regex === $last_taxonomy ) {
					break;
				}
			}
			if ( ! $promote ) {
				continue;
			}
			$ordered = array();
			foreach ( $rules as $regex => $query ) {
				if ( $regex === $anchor ) {
					$ordered += $promote;
				}
				if ( ! array_key_exists( $regex, $promote ) ) {
					$ordered[ $regex ] = $query;
				}
			}
			$rules = $ordered;
		}
	}
	return $rules;
}
add_filter( 'option_rewrite_rules', 'lunara_oscar_taxonomy_prefer_existing_rules' );
