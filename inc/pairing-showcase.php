<?php
/**
 * Pairing Showcase — a fully customizable, per-instance "Pair It With" block.
 *
 * The signature Lunara module, freed from the review it usually hangs off.
 * The automatic Pair It With (inc/debrief.php) derives exactly three cards —
 * Theme Echo, Counter-Program, Career Context — from the CURRENT review's
 * meta. This block lets any page hand-build its own Pair It With: choose the
 * films, the role label above each, the note, the poster, and the order, and
 * change any of it on a whim.
 *
 *   - lunara/pairing → lunara_render_pairing_showcase()
 *
 * Two sources, switchable per instance:
 *   - Curated (default): a reorderable list of pairing cards. Each card is a
 *     film (title + year + optional IMDb id) with a custom role label, note,
 *     and optional poster override. Slot 1 is the featured/lead card.
 *   - Mirror a review: point at any published review and render its automatic
 *     Pair It With cards verbatim (reuses lunara_render_pair_it_with_cards).
 *
 * Cards reuse the exact resolution + markup the signature feature ships:
 * lunara_parse_pair_it_with_value() resolves posters, Oscar-ledger counts,
 * IMDb + internal links from a title/year/id, and the card assembly mirrors
 * lunara_render_pair_it_with_cards() so curated pairings look native anywhere.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_pairing_showcase_attribute_schema' ) ) {
	/**
	 * Attribute schema. Every editorial decision is a per-instance attribute.
	 *
	 * @return array
	 */
	function lunara_pairing_showcase_attribute_schema() {
		return array(
			'source'         => array( 'type' => 'string', 'default' => 'curated' ),
			'reviewId'       => array( 'type' => 'number', 'default' => 0 ),
			'pairings'       => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'object' ) ),
			'showHeader'     => array( 'type' => 'boolean', 'default' => true ),
			'heading'        => array( 'type' => 'string', 'default' => '' ),
			'subtitle'       => array( 'type' => 'string', 'default' => '' ),
		);
	}
}

if ( ! function_exists( 'lunara_pairing_showcase_sanitize_pairings' ) ) {
	/**
	 * Normalize the raw pairings attribute into clean card definitions,
	 * preserving slot order and dropping any card without a title or poster.
	 *
	 * @param mixed $raw Raw attribute value.
	 * @return array<int,array<string,mixed>>
	 */
	function lunara_pairing_showcase_sanitize_pairings( $raw ) {
		$clean = array();
		foreach ( (array) $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title    = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';
			$poster   = isset( $item['posterId'] ) ? absint( $item['posterId'] ) : 0;
			if ( '' === $title && $poster <= 0 ) {
				continue;
			}
			$year = '';
			if ( isset( $item['year'] ) && preg_match( '/\d{4}/', (string) $item['year'], $ym ) ) {
				$year = $ym[0];
			}
			$clean[] = array(
				'label'    => isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '',
				'title'    => $title,
				'year'     => $year,
				'note'     => isset( $item['note'] ) ? sanitize_textarea_field( (string) $item['note'] ) : '',
				'imdb'     => isset( $item['imdb'] ) ? sanitize_text_field( (string) $item['imdb'] ) : '',
				'posterId' => $poster,
			);
		}
		return $clean;
	}
}

if ( ! function_exists( 'lunara_pairing_showcase_build_card' ) ) {
	/**
	 * Assemble one Pair It With card from resolved data.
	 *
	 * Mirrors the card markup in lunara_render_pair_it_with_cards() so curated
	 * cards are visually identical to the automatic signature feature.
	 *
	 * @param array  $data          Parsed value data (see lunara_parse_pair_it_with_value).
	 * @param string $label         Role label above the title.
	 * @param string $slug          Card variant slug (used in the class name).
	 * @param string $note_override Explicit note; falls back to $data['note'] when ''.
	 * @param string $poster_html   Explicit poster HTML; falls back to $data['poster_html'].
	 * @param int    $post_id       Context post ID for where-to-watch (0 is fine).
	 * @return string
	 */
	function lunara_pairing_showcase_build_card( $data, $label, $slug, $note_override, $poster_html, $post_id = 0 ) {
		$title_base = '' !== (string) $data['title_base'] ? (string) $data['title_base'] : (string) $data['title'];
		if ( '' === trim( $title_base ) ) {
			return '';
		}

		$tt     = (string) $data['tt'];
		$year   = (string) $data['year'];
		$note   = '' !== trim( (string) $note_override ) ? (string) $note_override : (string) $data['note'];
		$counts = is_array( $data['counts'] ) ? $data['counts'] : array( 'noms' => 0, 'wins' => 0 );

		// Poster, or the Fallback B title plate with the aperture colophon.
		$poster_html = trim( (string) $poster_html );
		if ( '' === $poster_html ) {
			$poster_html = trim( (string) $data['poster_html'] );
		}
		if ( '' !== $poster_html ) {
			$media = '<div class="lunara-pair-card-poster">' . $poster_html . '</div>';
		} else {
			$media = '<div class="lunara-pair-card-poster lunara-pair-card-poster--plate">'
				. '<div class="lunara-pair-card-plate">'
				. '<span class="lunara-pair-card-mark" aria-hidden="true"></span>'
				. '<span class="lunara-pair-card-plate-title">' . esc_html( $title_base ) . '</span>'
				. '<span class="lunara-pair-card-plate-rule"></span>'
				. '</div></div>';
		}

		$title_inner = '<span class="lunara-pair-card-title-text">' . esc_html( $title_base ) . '</span>';
		if ( '' !== $year ) {
			$title_inner .= ' <span class="lunara-pair-card-year">(' . esc_html( $year ) . ')</span>';
		}

		$title_href = (string) $data['title_href'];
		$href_type  = (string) $data['title_href_type'];
		if ( '' !== $title_href && in_array( $href_type, array( 'review', 'oscar', 'entity' ), true ) ) {
			$title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '">' . $title_inner . '</a>';
		} elseif ( '' !== $title_href ) {
			$title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '" target="_blank" rel="noopener noreferrer nofollow">' . $title_inner . '</a>';
		} else {
			$title_html = '<span class="lunara-pair-card-title-link">' . $title_inner . '</span>';
		}

		$chips     = '';
		$imdb_href = (string) $data['imdb_href'];
		if ( '' !== $imdb_href ) {
			$chips .= '<a class="lunara-pair-card-chip lunara-pair-card-chip--imdb" href="' . esc_url( $imdb_href ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
		}
		if ( function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
			$chips .= lunara_render_oscar_ledger_pill( $tt, $counts );
		}

		$watch = function_exists( 'lunara_pair_render_where_to_watch' ) ? lunara_pair_render_where_to_watch( $tt, $post_id ) : '';

		$card  = '<article class="lunara-pair-card lunara-pair-card--' . esc_attr( $slug ) . '"';
		$card .= '' !== $tt ? ' data-pair-tt="' . esc_attr( $tt ) . '"' : '';
		$card .= '>';
		$card .= $media;
		$card .= '<div class="lunara-pair-card-body">';
		if ( '' !== trim( (string) $label ) ) {
			$card .= '<p class="lunara-pair-card-role">' . esc_html( $label ) . '</p>';
		}
		$card .= '<h4 class="lunara-pair-card-title">' . $title_html . '</h4>';
		if ( '' !== trim( $note ) ) {
			$card .= '<p class="lunara-pair-card-note">' . esc_html( $note ) . '</p>';
		}
		if ( '' !== trim( $chips ) ) {
			$card .= '<div class="lunara-pair-card-chips">' . $chips . '</div>';
		}
		if ( '' !== trim( (string) $watch ) ) {
			$card .= '<div class="lunara-pair-card-watch">' . $watch . '</div>';
		}
		$card .= '</div></article>';

		return $card;
	}
}

if ( ! function_exists( 'lunara_render_pairing_showcase' ) ) {
	/**
	 * Render callback for lunara/pairing.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	function lunara_render_pairing_showcase( $attrs = array() ) {
		$attrs = is_array( $attrs ) ? $attrs : array();

		// The signature look lives in the review-components stylesheet, which is
		// route-gated — make sure it's present wherever this block is dropped.
		lunara_pairing_showcase_enqueue_styles();

		$source = isset( $attrs['source'] ) && 'review' === $attrs['source'] ? 'review' : 'curated';

		// Mirror mode: render a chosen review's automatic Pair It With verbatim.
		if ( 'review' === $source ) {
			$review_id = isset( $attrs['reviewId'] ) ? absint( $attrs['reviewId'] ) : 0;
			$post      = $review_id ? get_post( $review_id ) : null;
			if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || 'review' !== $post->post_type ) {
				return '';
			}
			return function_exists( 'lunara_render_pair_it_with_cards' ) ? (string) lunara_render_pair_it_with_cards( $review_id ) : '';
		}

		$pairings = lunara_pairing_showcase_sanitize_pairings( isset( $attrs['pairings'] ) ? $attrs['pairings'] : array() );
		if ( empty( $pairings ) ) {
			return '';
		}

		if ( ! function_exists( 'lunara_parse_pair_it_with_value' ) ) {
			return '';
		}

		$cards = array();
		foreach ( $pairings as $index => $pair ) {
			$lookup = $pair['title'];
			if ( '' !== $pair['year'] ) {
				$lookup .= ' (' . $pair['year'] . ')';
			}
			if ( '' !== $pair['imdb'] ) {
				$lookup .= ' | ' . $pair['imdb'];
			}

			$data = lunara_parse_pair_it_with_value( $lookup, 0 );

			// A poster override always wins over the resolved poster.
			$poster_html = '';
			if ( $pair['posterId'] > 0 ) {
				$poster_html = (string) wp_get_attachment_image(
					$pair['posterId'],
					'medium',
					false,
					array( 'class' => 'lunara-pair-preview-thumb', 'loading' => 'lazy', 'decoding' => 'async' )
				);
			}

			// When only a poster + label were given (no resolvable title), still
			// show a card using the typed title as the plate/title text.
			if ( '' === trim( (string) $data['title_base'] ) && '' === trim( (string) $data['title'] ) && '' !== $pair['title'] ) {
				$data['title']      = $pair['title'];
				$data['title_base'] = $pair['title'];
			}

			$slug = 'slot-' . ( (int) $index + 1 );
			$card = lunara_pairing_showcase_build_card( $data, $pair['label'], $slug, $pair['note'], $poster_html, 0 );
			if ( '' !== $card ) {
				$cards[] = $card;
			}
		}

		if ( empty( $cards ) ) {
			return '';
		}

		$show_header = ! isset( $attrs['showHeader'] ) || (bool) $attrs['showHeader'];
		$heading     = isset( $attrs['heading'] ) ? trim( (string) $attrs['heading'] ) : '';
		$subtitle    = isset( $attrs['subtitle'] ) ? trim( (string) $attrs['subtitle'] ) : '';
		if ( '' === $heading ) {
			$heading = __( 'Pair It With', 'lunara-film' );
		}
		if ( '' === $subtitle ) {
			$subtitle = __( 'Three films in conversation with this one.', 'lunara-film' );
		}

		$html  = '<section class="lunara-pair-cards lunara-pairing-showcase-block" aria-label="' . esc_attr__( 'Pair It With', 'lunara-film' ) . '">';
		if ( $show_header ) {
			$html .= '<div class="lunara-pair-cards-head">';
			$html .= '<h3 class="lunara-pair-cards-title">' . esc_html( $heading ) . '</h3>';
			if ( '' !== trim( $subtitle ) ) {
				$html .= '<p class="lunara-pair-cards-sub">' . esc_html( $subtitle ) . '</p>';
			}
			$html .= '</div>';
		}
		$html .= '<div class="lunara-pair-cards-grid" data-count="' . count( $cards ) . '">' . implode( '', $cards ) . '</div>';
		$html .= '</section>';

		return $html;
	}
}

if ( ! function_exists( 'lunara_pairing_showcase_enqueue_styles' ) ) {
	/**
	 * Enqueue the review-components stylesheet on demand so the signature
	 * pair-card look is present on any page carrying this block.
	 */
	function lunara_pairing_showcase_enqueue_styles() {
		if ( ! function_exists( 'lunara_resolve_theme_asset' ) || ! function_exists( 'wp_style_is' ) ) {
			return;
		}
		if ( wp_style_is( 'lunara-review-components', 'enqueued' ) || wp_style_is( 'lunara-review-components', 'done' ) ) {
			return;
		}
		$asset = lunara_resolve_theme_asset( 'assets/css/lunara-review-components.css' );
		if ( ! empty( $asset['uri'] ) ) {
			wp_enqueue_style(
				'lunara-review-components',
				$asset['uri'],
				array( 'lunara-style' ),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null
			);
		}
	}
}

if ( ! function_exists( 'lunara_register_pairing_showcase_block' ) ) {
	function lunara_register_pairing_showcase_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset = function_exists( 'lunara_resolve_theme_asset' )
			? lunara_resolve_theme_asset( 'assets/js/lunara-pairing-showcase.js' )
			: array();

		if ( ! empty( $asset['path'] ) ) {
			wp_register_script(
				'lunara-pairing-showcase',
				$asset['uri'],
				array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-api-fetch', 'wp-url' ),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
				true
			);
		}

		register_block_type(
			'lunara/pairing',
			array(
				'api_version'     => 3,
				'category'        => 'lunara',
				'editor_script'   => 'lunara-pairing-showcase',
				'title'           => __( 'Lunara Pair It With — Curated', 'lunara-film' ),
				'icon'            => 'screenoptions',
				'description'     => __( 'The signature Pair It With, fully editable per instance: hand-pick each film, its role label, note, and poster, reorder the cards (slot 1 is featured), or mirror an existing review\'s automatic pairings. Posters, IMDb links, and Oscar-ledger pills resolve automatically.', 'lunara-film' ),
				'attributes'      => lunara_pairing_showcase_attribute_schema(),
				'supports'        => array(
					'html'     => false,
					'anchor'   => true,
					'align'    => array( 'wide', 'full' ),
					'multiple' => true,
					'inserter' => true,
				),
				'render_callback' => 'lunara_render_pairing_showcase',
			)
		);
	}
	add_action( 'init', 'lunara_register_pairing_showcase_block', 100 );
}
