<?php
/**
 * The Debrief Method — the public explainer page and its live index.
 *
 * Every review closes with the Lunara Debrief: three films in conversation
 * with the one just reviewed — a Theme Echo, a Counter-Program, and a Career
 * Context. This module backs the page that explains the method
 * (page-debrief.php, auto-applied to the page with slug "debrief") and
 * aggregates every review's pairings into a cached index: totals, the
 * most-prescribed films, and the most recent Debriefs.
 *
 * It also exposes lunara_debrief_method_link_html(), which the Pair It With
 * renderers append to their heading so every review points readers at the
 * method. The link renders only once a published "debrief" page exists, so
 * review markup is unchanged until Dalton publishes the page.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transient key for the aggregated index. Bump the suffix whenever the
 * payload shape changes so shape-old payloads are never served to new readers.
 */
if ( ! defined( 'LUNARA_DEBRIEF_INDEX_CACHE_KEY' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_CACHE_KEY', 'lunara_debrief_index_v1' );
}

if ( ! function_exists( 'lunara_debrief_method_roles' ) ) {
	/**
	 * The three moves, in canonical order. Single source for labels and copy.
	 *
	 * @return array<string,array<string,string>>
	 */
	function lunara_debrief_method_roles() {
		return array(
			'theme'   => array(
				'relation' => 'theme_echo',
				'meta'     => '_lunara_theme_echo',
				'label'    => __( 'Theme Echo', 'lunara-film' ),
				'question' => __( 'What is this film really about — and who else has said it?', 'lunara-film' ),
				'copy'     => __( 'A film that shares the same wound, obsession, or question, told in a different key. Genre and era do not matter. The echo deepens the idea you just read about.', 'lunara-film' ),
				'not'      => __( 'Not a lookalike. "If you liked this, you\'ll like that" is how an algorithm talks.', 'lunara-film' ),
			),
			'counter' => array(
				'relation' => 'counter_program',
				'meta'     => '_lunara_counter_program',
				'label'    => __( 'Counter-Program', 'lunara-film' ),
				'question' => __( 'What is the strongest argument against it?', 'lunara-film' ),
				'copy'     => __( 'A film that answers the same question the opposite way — the rebuttal, the antidote, the other side of the coin. You leave with a debate instead of a bubble.', 'lunara-film' ),
				'not'      => __( 'Not a "better movie." It is the film this one should be arguing with.', 'lunara-film' ),
			),
			'career'  => array(
				'relation' => 'career_context',
				'meta'     => '_lunara_career_context',
				'label'    => __( 'Career Context', 'lunara-film' ),
				'question' => __( 'Where does this sit in the artist\'s story?', 'lunara-film' ),
				'copy'     => __( 'A film that explains the trajectory behind this one — the earlier work that makes it legible, the influence it inherits, or the road the artist did not take.', 'lunara-film' ),
				'not'      => __( 'Not a filmography dump. One film, chosen because it changes how you see this one.', 'lunara-film' ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_debrief_method_page_url' ) ) {
	/**
	 * Permalink of the published Debrief Method page, or '' when none exists.
	 *
	 * @return string
	 */
	function lunara_debrief_method_page_url() {
		static $url = null;
		if ( null !== $url ) {
			return $url;
		}

		$url = '';
		if ( ! function_exists( 'get_page_by_path' ) ) {
			return $url;
		}

		$page = get_page_by_path( 'debrief', OBJECT, 'page' );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			$url = (string) get_permalink( $page );
		}

		return $url;
	}
}

if ( ! function_exists( 'lunara_debrief_method_link_html' ) ) {
	/**
	 * "How the Debrief works" link for the Pair It With heading on reviews.
	 *
	 * @return string Link markup, or '' when the page is not published.
	 */
	function lunara_debrief_method_link_html() {
		$url = lunara_debrief_method_page_url();
		if ( '' === $url || ( function_exists( 'is_page' ) && lunara_is_debrief_method_page() ) ) {
			return '';
		}

		return '<a class="lunara-pair-cards-method" href="' . esc_url( $url ) . '">'
			. esc_html__( 'How the Debrief works', 'lunara-film' )
			. ' <span aria-hidden="true">&rarr;</span></a>';
	}
}

if ( ! function_exists( 'lunara_debrief_method_read_pairing' ) ) {
	/**
	 * Resolve one pairing on a review to light index data (no poster work).
	 *
	 * Relational Trinity (linked movie entity) wins over the legacy text field,
	 * matching the review renderer's precedence.
	 *
	 * @param int                  $review_id Review post ID.
	 * @param array<string,string> $role      Role definition.
	 * @return array<string,string>|null
	 */
	function lunara_debrief_method_read_pairing( $review_id, $role ) {
		$movie_id = (int) get_post_meta( $review_id, $role['relation'] . '_movie', true );
		if ( $movie_id > 0 ) {
			$movie = get_post( $movie_id );
			if ( $movie && 'movie' === $movie->post_type && 'publish' === $movie->post_status ) {
				$title = trim( (string) get_the_title( $movie ) );
				if ( '' !== $title ) {
					$tt = strtolower( trim( (string) get_post_meta( $movie_id, 'imdb_title_id', true ) ) );
					return array(
						'title' => $title,
						'year'  => trim( (string) get_post_meta( $movie_id, 'release_year', true ) ),
						'tt'    => preg_match( '/^tt\d{7,8}$/', $tt ) ? $tt : '',
						'href'  => (string) get_permalink( $movie_id ),
					);
				}
			}
		}

		// Career Context keeps its legacy Craft Mirror fallback.
		$raw = ( '_lunara_career_context' === $role['meta'] && function_exists( 'lunara_get_career_context_meta' ) )
			? (string) lunara_get_career_context_meta( $review_id )
			: (string) get_post_meta( $review_id, $role['meta'], true );
		if ( '' === trim( $raw ) || ! function_exists( 'lunara_parse_pair_it_with_value' ) ) {
			return null;
		}

		$data  = lunara_parse_pair_it_with_value( $raw, $review_id, false );
		$title = '' !== (string) $data['title_base'] ? (string) $data['title_base'] : (string) $data['title'];
		if ( '' === trim( $title ) ) {
			return null;
		}

		$href = (string) $data['title_href'];
		if ( '' === $href ) {
			$href = (string) $data['imdb_href'];
		}

		return array(
			'title' => trim( $title ),
			'year'  => (string) $data['year'],
			'tt'    => (string) $data['tt'],
			'href'  => $href,
		);
	}
}

if ( ! function_exists( 'lunara_debrief_method_build_index' ) ) {
	/**
	 * Walk every published review and aggregate its Debrief pairings.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_debrief_method_build_index() {
		$roles = lunara_debrief_method_roles();

		$review_ids = get_posts(
			array(
				'post_type'              => 'review',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		if ( function_exists( 'update_meta_cache' ) && ! empty( $review_ids ) ) {
			update_meta_cache( 'post', $review_ids );
		}

		$index = array(
			'reviews_total'   => count( $review_ids ),
			'reviews_debrief' => 0,
			'reviews_trio'    => 0,
			'pairings_total'  => 0,
			'films'           => array(),
			'recent'          => array(),
		);

		foreach ( $review_ids as $review_id ) {
			$review_id = (int) $review_id;
			$pairs     = array();

			foreach ( $roles as $slug => $role ) {
				$pair = lunara_debrief_method_read_pairing( $review_id, $role );
				if ( null === $pair ) {
					continue;
				}
				$pair['role'] = $slug;
				$pairs[]      = $pair;

				$key = '' !== $pair['tt']
					? $pair['tt']
					: strtolower( remove_accents( $pair['title'] ) ) . '|' . $pair['year'];

				if ( ! isset( $index['films'][ $key ] ) ) {
					$index['films'][ $key ] = array(
						'title'   => $pair['title'],
						'year'    => $pair['year'],
						'tt'      => $pair['tt'],
						'href'    => $pair['href'],
						'count'   => 0,
						'roles'   => array( 'theme' => 0, 'counter' => 0, 'career' => 0 ),
						'reviews' => array(),
					);
				}
				++$index['films'][ $key ]['count'];
				++$index['films'][ $key ]['roles'][ $slug ];
				if ( count( $index['films'][ $key ]['reviews'] ) < 6 ) {
					$index['films'][ $key ]['reviews'][] = $review_id;
				}
			}

			if ( empty( $pairs ) ) {
				continue;
			}

			++$index['reviews_debrief'];
			$index['pairings_total'] += count( $pairs );
			if ( 3 === count( $pairs ) ) {
				++$index['reviews_trio'];
			}
			if ( count( $index['recent'] ) < 8 ) {
				$index['recent'][] = array(
					'review_id' => $review_id,
					'pairs'     => $pairs,
				);
			}
		}

		$index['unique_films'] = count( $index['films'] );

		// Keep only the most-prescribed films: count desc, then title.
		$films = array_values( $index['films'] );
		usort(
			$films,
			static function ( $a, $b ) {
				if ( $a['count'] !== $b['count'] ) {
					return $b['count'] - $a['count'];
				}
				return strcasecmp( $a['title'], $b['title'] );
			}
		);
		$index['films'] = array_slice( $films, 0, 12 );

		return $index;
	}
}

if ( ! function_exists( 'lunara_debrief_method_index' ) ) {
	/**
	 * Cached Debrief index. Rebuilt at most every 12 hours or on review save.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_debrief_method_index() {
		$cached = get_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['films'], $cached['recent'] ) ) {
			return $cached;
		}

		$index = lunara_debrief_method_build_index();
		set_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY, $index, 12 * HOUR_IN_SECONDS );

		return $index;
	}
}

if ( ! function_exists( 'lunara_debrief_method_flush_index' ) ) {
	/**
	 * Drop the cached index when a review changes.
	 *
	 * @param int $post_id Post ID.
	 */
	function lunara_debrief_method_flush_index( $post_id = 0 ) {
		if ( $post_id && 'review' !== get_post_type( $post_id ) ) {
			return;
		}
		delete_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY );
	}
	add_action( 'save_post_review', 'lunara_debrief_method_flush_index', 99 );
	add_action( 'deleted_post', 'lunara_debrief_method_flush_index' );
	add_action( 'trashed_post', 'lunara_debrief_method_flush_index' );
}

if ( ! function_exists( 'lunara_is_debrief_method_page' ) ) {
	/**
	 * Whether the current request renders the Debrief Method page.
	 *
	 * @return bool
	 */
	function lunara_is_debrief_method_page() {
		return is_page( 'debrief' ) || is_page_template( 'page-debrief.php' );
	}
}

if ( ! function_exists( 'lunara_debrief_method_enqueue_styles' ) ) {
	/**
	 * Enqueue the page sheet plus the shared pair-card components.
	 */
	function lunara_debrief_method_enqueue_styles() {
		if ( is_admin() || ! lunara_is_debrief_method_page() || ! function_exists( 'lunara_resolve_theme_asset' ) ) {
			return;
		}

		if ( ! wp_style_is( 'lunara-review-components', 'enqueued' ) ) {
			$components = lunara_resolve_theme_asset( 'assets/css/lunara-review-components.css' );
			if ( ! empty( $components['uri'] ) ) {
				wp_enqueue_style(
					'lunara-review-components',
					$components['uri'],
					array( 'lunara-style' ),
					lunara_theme_asset_version( $components['path'] )
				);
			}
		}

		$asset = lunara_resolve_theme_asset( 'assets/css/lunara-debrief-method.css' );
		if ( ! empty( $asset['uri'] ) ) {
			wp_enqueue_style(
				'lunara-debrief-method',
				$asset['uri'],
				array( 'lunara-style' ),
				lunara_theme_asset_version( $asset['path'] )
			);
		}
	}
	add_action( 'wp_enqueue_scripts', 'lunara_debrief_method_enqueue_styles', 110 );
}
