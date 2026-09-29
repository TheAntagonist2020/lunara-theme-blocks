<?php
/**
 * The Debrief Method — the public explainer page, its settings, and its live index.
 *
 * Every review closes with the Lunara Debrief: three films in conversation
 * with the one just reviewed — an Echo, a Counter, and a
 * Context. This module backs the page that explains the method
 * (page-debrief.php, auto-applied to the page with slug "debrief") and
 * aggregates every review's pairings into a cached index: totals, the
 * most-prescribed films, and the most recent Debriefs.
 *
 * The page's words, section visibility and counts are theme mods described by
 * lunara_debrief_method_settings_spec(). Site Studio edits them through the
 * shared Preview/Apply/History transaction (inc/site-studio-debrief-method.php);
 * this module only reads them, so the page renders identically with or without
 * the editor loaded.
 *
 * It also exposes lunara_debrief_method_link_html(), which the Pair It With
 * renderers append to their heading so every review points readers at the
 * method. The link renders only once a published "debrief" page exists.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transient key for the aggregated index. Bump the suffix whenever the
 * payload shape changes so shape-old payloads are never served to new readers,
 * and add the retired key to lunara_debrief_method_retired_cache_keys().
 *
 * v2 (3.2.90): films carry merged identity aliases and their reviews; recent
 * holds up to twelve Debriefs; payload records its own version.
 * v3 (3.2.95): same shape; titles no longer carry a note that followed the year
 * with a full stop ("Under the Skin (2013). The opposite argument…"). Bumped so
 * the corrected titles replace stored ones on deploy instead of after the TTL.
 */
if ( ! defined( 'LUNARA_DEBRIEF_INDEX_CACHE_KEY' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_CACHE_KEY', 'lunara_debrief_index_v3' );
}

if ( ! defined( 'LUNARA_DEBRIEF_INDEX_VERSION' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_VERSION', 3 );
}

/** Upper bounds the index stores; the page's counts are clamped to these. */
if ( ! defined( 'LUNARA_DEBRIEF_INDEX_MAX_RECENT' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_MAX_RECENT', 12 );
}
if ( ! defined( 'LUNARA_DEBRIEF_INDEX_MAX_FILMS' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_MAX_FILMS', 24 );
}
if ( ! defined( 'LUNARA_DEBRIEF_INDEX_MAX_FILM_REVIEWS' ) ) {
	define( 'LUNARA_DEBRIEF_INDEX_MAX_FILM_REVIEWS', 6 );
}

if ( ! function_exists( 'lunara_debrief_method_retired_cache_keys' ) ) {
	/**
	 * Earlier index keys, cleared on every flush so no retired payload lingers.
	 *
	 * @return array<int,string>
	 */
	function lunara_debrief_method_retired_cache_keys() {
		return array( 'lunara_debrief_index_v1', 'lunara_debrief_index_v2' );
	}
}

if ( ! function_exists( 'lunara_debrief_method_roles' ) ) {
	/**
	 * The three moves, in canonical order. Single source for labels and the
	 * default explainer copy. Labels are the canonical role names shared with
	 * every review's Pair It With cards, so they are deliberately not editable.
	 *
	 * @return array<string,array<string,string>>
	 */
	function lunara_debrief_method_roles() {
		return array(
			'theme'   => array(
				'relation' => 'theme_echo',
				'meta'     => '_lunara_theme_echo',
				'label'    => __( 'Echo', 'lunara-film' ),
				'question' => __( 'What is this film really about — and who else has said it?', 'lunara-film' ),
				'copy'     => __( 'A film that shares the same wound, obsession, or question, told in a different key. Genre and era do not matter. The echo deepens the idea you just read about.', 'lunara-film' ),
				'not'      => __( 'Not a lookalike. "If you liked this, you\'ll like that" is how an algorithm talks.', 'lunara-film' ),
			),
			'counter' => array(
				'relation' => 'counter_program',
				'meta'     => '_lunara_counter_program',
				'label'    => __( 'Counter', 'lunara-film' ),
				'question' => __( 'What is the strongest argument against it?', 'lunara-film' ),
				'copy'     => __( 'A film that answers the same question the opposite way — the rebuttal, the antidote, the other side of the coin. You leave with a debate instead of a bubble.', 'lunara-film' ),
				'not'      => __( 'Not a "better movie." It is the film this one should be arguing with.', 'lunara-film' ),
			),
			'career'  => array(
				'relation' => 'career_context',
				'meta'     => '_lunara_career_context',
				'label'    => __( 'Context', 'lunara-film' ),
				'question' => __( 'Where does this sit in the artist\'s story?', 'lunara-film' ),
				'copy'     => __( 'A film that explains the trajectory behind this one — the earlier work that makes it legible, the influence it inherits, or the road the artist did not take.', 'lunara-film' ),
				'not'      => __( 'Not a filmography dump. One film, chosen because it changes how you see this one.', 'lunara-film' ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_debrief_method_settings_spec' ) ) {
	/**
	 * Every editable setting on the Debrief page: group => field => definition.
	 *
	 * The shape matches the Site Studio mod-surface contract (mod, type,
	 * default, bounds, label, help), so the editor consumes it unchanged.
	 * Defaults reproduce the 3.2.89 page exactly; an unsaved site renders the
	 * same words it always has.
	 *
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	function lunara_debrief_method_settings_spec() {
		$text = static function ( $mod, $default, $limit, $label, $type = 'text', $help = '' ) {
			$definition = array(
				'mod'                  => $mod,
				'type'                 => $type,
				'default'              => $default,
				'max_length'           => $limit,
				'preserve_legacy_read' => true,
				'label'                => $label,
			);
			if ( '' !== $help ) {
				$definition['help'] = $help;
			}
			return $definition;
		};
		$toggle = static function ( $mod, $label, $default = true ) {
			return array( 'mod' => $mod, 'type' => 'bool', 'default' => $default, 'label' => $label );
		};
		$number = static function ( $mod, $default, $min, $max, $label, $help ) {
			return array( 'mod' => $mod, 'type' => 'int', 'value_scale' => 1, 'default' => $default, 'min' => $min, 'max' => $max, 'label' => $label, 'help' => $help );
		};

		$roles = lunara_debrief_method_roles();
		$moves = array(
			'kicker' => $text( 'lunara_debrief_moves_kicker', __( 'The Three Moves', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
			'title'  => $text( 'lunara_debrief_moves_title', __( 'Every pairing answers a different question.', 'lunara-film' ), 220, __( 'Heading', 'lunara-film' ) ),
		);
		foreach ( $roles as $slug => $role ) {
			/* translators: %s: Debrief move name, e.g. Echo. */
			$moves[ $slug . '_question' ] = $text( 'lunara_debrief_' . $slug . '_question', $role['question'], 220, sprintf( __( '%s: the question it answers', 'lunara-film' ), $role['label'] ) );
			/* translators: %s: Debrief move name. */
			$moves[ $slug . '_copy' ] = $text( 'lunara_debrief_' . $slug . '_copy', $role['copy'], 600, sprintf( __( '%s: what it is', 'lunara-film' ), $role['label'] ), 'textarea' );
			/* translators: %s: Debrief move name. */
			$moves[ $slug . '_not' ] = $text( 'lunara_debrief_' . $slug . '_not', $role['not'], 300, sprintf( __( '%s: what it is not', 'lunara-film' ), $role['label'] ), 'text', __( 'Leave empty to omit this line.', 'lunara-film' ) );
		}

		return array(
			'hero'     => array(
				'kicker'     => $text( 'lunara_debrief_hero_kicker', __( 'A Lunara Film Signature', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
				'title'      => $text( 'lunara_debrief_hero_title', '', 160, __( 'Heading', 'lunara-film' ), 'text', __( 'Leave empty to use the page title.', 'lunara-film' ) ),
				'thesis'     => $text( 'lunara_debrief_hero_thesis', __( 'Every review on Lunara ends the same way — with three more films. Not a list of lookalikes. An argument in three moves: one that deepens the film, one that challenges it, and one that explains where it came from.', 'lunara-film' ), 600, __( 'Thesis', 'lunara-film' ), 'textarea' ),
				'show_stats' => $toggle( 'lunara_debrief_show_stats', __( 'Show the live totals', 'lunara-film' ) ),
			),
			'moves'    => $moves,
			'why'      => array(
				'show'   => $toggle( 'lunara_debrief_show_why', __( 'Show this section', 'lunara-film' ) ),
				'kicker' => $text( 'lunara_debrief_why_kicker', __( 'Why Three', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
				'title'  => $text( 'lunara_debrief_why_title', __( 'An algorithm recommends more of the same. A critic recommends a conversation.', 'lunara-film' ), 220, __( 'Heading', 'lunara-film' ) ),
				'body'   => $text(
					'lunara_debrief_why_body',
					__( 'A single recommendation just agrees with you. Three chosen on purpose form a triangle around the film: the Echo shows what it shares with cinema history, the Counter tests its argument, and the Context shows how it was made. Read all three and you understand the film you just read about better.', 'lunara-film' )
						. "\n\n"
						. __( 'Every pairing is chosen by hand, carries a one-line reason, and links into the Lunara Oscar Ledger, so each Debrief also leads into the site\'s Academy Awards history.', 'lunara-film' ),
					2000,
					__( 'Body', 'lunara-film' ),
					'textarea',
					__( 'Separate paragraphs with a blank line.', 'lunara-film' )
				),
			),
			'specimen' => array(
				'show'      => $toggle( 'lunara_debrief_show_specimen', __( 'Show this section', 'lunara-film' ) ),
				'kicker'    => $text( 'lunara_debrief_specimen_kicker', __( 'A Debrief in the Wild', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
				'lead'      => $text( 'lunara_debrief_specimen_lead', __( 'From the review of', 'lunara-film' ), 120, __( 'Heading lead-in', 'lunara-film' ), 'text', __( 'Shown before the linked review title.', 'lunara-film' ) ),
				'review_id' => $number( 'lunara_debrief_specimen_review_id', 0, 0, 999999999, __( 'Featured Review ID', 'lunara-film' ), __( '0 features the newest review with all three pairings. A Review ID pins that review while it stays published with a Debrief.', 'lunara-film' ) ),
			),
			'desk'     => array(
				'kicker' => $text( 'lunara_debrief_desk_kicker', __( 'From the Desk', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ), 'text', __( 'The section shows the Debrief page\'s own editor content, and only when it has some.', 'lunara-film' ) ),
			),
			'canon'    => array(
				'show'         => $toggle( 'lunara_debrief_show_canon', __( 'Show on your WordPress dashboard (never public)', 'lunara-film' ) ),
				'kicker'       => $text( 'lunara_debrief_canon_kicker', __( 'The Debrief Canon', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
				'title'        => $text( 'lunara_debrief_canon_title', __( 'The films the desk keeps returning to.', 'lunara-film' ), 220, __( 'Heading', 'lunara-film' ) ),
				'count'        => $number( 'lunara_debrief_canon_count', 8, 2, LUNARA_DEBRIEF_INDEX_MAX_FILMS, __( 'Films shown', 'lunara-film' ), __( 'The most-prescribed films, most frequent first.', 'lunara-film' ) ),
				'min_count'    => $number( 'lunara_debrief_canon_min_count', 2, 2, 10, __( 'Minimum prescriptions', 'lunara-film' ), __( 'A film joins the canon once this many reviews have prescribed it.', 'lunara-film' ) ),
				'show_reviews' => $toggle( 'lunara_debrief_canon_show_reviews', __( 'List the reviews that prescribed each film', 'lunara-film' ) ),
			),
			'recent'   => array(
				'show'   => $toggle( 'lunara_debrief_show_recent', __( 'Show this section', 'lunara-film' ) ),
				'kicker' => $text( 'lunara_debrief_recent_kicker', __( 'Recent Debriefs', 'lunara-film' ), 120, __( 'Kicker', 'lunara-film' ) ),
				'title'  => $text( 'lunara_debrief_recent_title', __( 'Read the review, then follow the three.', 'lunara-film' ), 220, __( 'Heading', 'lunara-film' ) ),
				'count'  => $number( 'lunara_debrief_recent_count', 8, 1, LUNARA_DEBRIEF_INDEX_MAX_RECENT, __( 'Debriefs shown', 'lunara-film' ), __( 'The newest reviews with at least one pairing.', 'lunara-film' ) ),
			),
			'next'     => array(
				'show'           => $toggle( 'lunara_debrief_show_next', __( 'Show the closing links', 'lunara-film' ) ),
				'reviews_kicker' => $text( 'lunara_debrief_next_reviews_kicker', __( 'Every review, every Debrief', 'lunara-film' ), 120, __( 'Reviews link: kicker', 'lunara-film' ) ),
				'reviews_label'  => $text( 'lunara_debrief_next_reviews_label', __( 'Browse the Reviews', 'lunara-film' ), 120, __( 'Reviews link: label', 'lunara-film' ) ),
				'oscars_kicker'  => $text( 'lunara_debrief_next_oscars_kicker', __( 'Where the pairings lead', 'lunara-film' ), 120, __( 'Oscars link: kicker', 'lunara-film' ) ),
				'oscars_label'   => $text( 'lunara_debrief_next_oscars_label', __( 'The Oscar Ledger', 'lunara-film' ), 120, __( 'Oscars link: label', 'lunara-film' ) ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_debrief_method_text_length' ) ) {
	/**
	 * Character length as the editor counts it (JavaScript string.length).
	 *
	 * @param string $value Text.
	 * @return int
	 */
	function lunara_debrief_method_text_length( $value ) {
		if ( function_exists( 'lunara_site_studio_utility_recovery_text_length' ) ) {
			return lunara_site_studio_utility_recovery_text_length( $value );
		}
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}
}

if ( ! function_exists( 'lunara_debrief_method_settings' ) ) {
	/**
	 * The effective page settings: saved theme mods, validated per field, with
	 * the shipped default for any value that is missing or out of bounds.
	 *
	 * Reads get_theme_mod() on every call so a Site Studio private preview,
	 * which filters the same theme mods, renders its candidate state.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function lunara_debrief_method_settings() {
		$settings = array();
		foreach ( lunara_debrief_method_settings_spec() as $group => $fields ) {
			$settings[ $group ] = array();
			foreach ( $fields as $field => $definition ) {
				$default = $definition['default'];
				$value   = get_theme_mod( $definition['mod'], $default );

				if ( 'bool' === $definition['type'] ) {
					if ( ! is_bool( $value ) ) {
						$value = in_array( $value, array( 0, 1, '0', '1' ), true ) ? (bool) (int) $value : (bool) $default;
					}
				} elseif ( 'int' === $definition['type'] ) {
					$valid = is_int( $value ) || ( is_string( $value ) && 1 === preg_match( '/^-?\d+$/D', $value ) );
					$value = $valid ? (int) $value : (int) $default;
					if ( $value < $definition['min'] || $value > $definition['max'] ) {
						$value = (int) $default;
					}
				} else {
					if ( ! is_string( $value ) ) {
						$value = (string) $default;
					}
					$value = 'textarea' === $definition['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
					if ( lunara_debrief_method_text_length( $value ) > $definition['max_length'] ) {
						$value = (string) $default;
					}
				}

				$settings[ $group ][ $field ] = $value;
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'lunara_debrief_method_display_roles' ) ) {
	/**
	 * The three moves with their explainer copy taken from the settings.
	 *
	 * @param array<string,array<string,mixed>> $settings Page settings.
	 * @return array<string,array<string,string>>
	 */
	function lunara_debrief_method_display_roles( $settings ) {
		$roles = lunara_debrief_method_roles();
		foreach ( $roles as $slug => $role ) {
			foreach ( array( 'question', 'copy', 'not' ) as $part ) {
				$key = $slug . '_' . $part;
				if ( isset( $settings['moves'][ $key ] ) ) {
					$roles[ $slug ][ $part ] = (string) $settings['moves'][ $key ];
				}
			}
		}
		return $roles;
	}
}

if ( ! function_exists( 'lunara_debrief_method_paragraphs' ) ) {
	/**
	 * Split plain editor text into paragraphs on blank lines.
	 *
	 * @param string $text Plain text.
	 * @return array<int,string>
	 */
	function lunara_debrief_method_paragraphs( $text ) {
		$parts = preg_split( '/\R\s*\R/u', trim( (string) $text ) );
		$parts = is_array( $parts ) ? $parts : array( (string) $text );
		return array_values(
			array_filter(
				array_map(
					static function ( $part ) {
						return trim( (string) preg_replace( '/\s*\R\s*/u', ' ', $part ) );
					},
					$parts
				),
				'strlen'
			)
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

if ( ! function_exists( 'lunara_debrief_method_split_title_year' ) ) {
	/**
	 * Separate a trailing year parenthetical that the shared parser leaves in
	 * the title, e.g. "2001: A Space Odyssey (Kubrick, 1968)". The parser only
	 * recognises a bare "(1968)"; anything else would make the same film look
	 * like two different titles in the index.
	 *
	 * A note that follows the year with a full stop, colon or semicolon rather
	 * than a dash ("Under the Skin (2013). The opposite argument…") is cut
	 * away first, so it never becomes part of the title.
	 *
	 * @param string $title Title as parsed.
	 * @param string $year  Year as parsed (may be empty).
	 * @return array{0:string,1:string}
	 */
	function lunara_debrief_method_split_title_year( $title, $year ) {
		$title = trim( (string) $title );
		$year  = trim( (string) $year );
		if ( preg_match( '/^(.+?\((?:[^()]*?\b)?(?:18|19|20)\d{2}\))\s*[.:;!?]+\s+\S/u', $title, $match ) ) {
			$title = trim( $match[1] );
		}
		if ( '' === $year && preg_match( '/^(.+?)\s*\(([^()]*?)\b((?:18|19|20)\d{2})\)\s*$/u', $title, $match ) ) {
			$title = trim( $match[1] );
			$year  = $match[3];
		}
		return array( $title, $year );
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
					list( $title, $year ) = lunara_debrief_method_split_title_year( $title, (string) get_post_meta( $movie_id, 'release_year', true ) );
					return array(
						'title' => $title,
						'year'  => $year,
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
		// "Title | tt1234567 — note" leaves the pipe behind once the ID is lifted out.
		$title = trim( $title, " \t\n\r\0\x0B|" );
		list( $title, $year ) = lunara_debrief_method_split_title_year( $title, (string) $data['year'] );
		if ( '' === $title ) {
			return null;
		}

		$href = (string) $data['title_href'];
		if ( '' === $href ) {
			$href = (string) $data['imdb_href'];
		}

		return array(
			'title' => $title,
			'year'  => $year,
			'tt'    => (string) $data['tt'],
			'href'  => $href,
		);
	}
}

if ( ! function_exists( 'lunara_debrief_method_title_key' ) ) {
	/**
	 * Accent-, case- and punctuation-insensitive title|year identity.
	 *
	 * @param string $title Title.
	 * @param string $year  Year (may be empty).
	 * @return string
	 */
	function lunara_debrief_method_title_key( $title, $year ) {
		$normalized = function_exists( 'lunara_normalize_title_key' )
			? lunara_normalize_title_key( $title )
			: strtolower( trim( (string) preg_replace( '/[^a-z0-9]+/i', ' ', remove_accents( (string) $title ) ) ) );
		return 'title:' . $normalized . '|' . $year;
	}
}

if ( ! function_exists( 'lunara_debrief_method_build_index' ) ) {
	/**
	 * Walk every published review and aggregate its Debrief pairings.
	 *
	 * A film is one entry however it was entered: an IMDb ID and a title|year
	 * both resolve to the same entry, so a linked movie and a legacy text
	 * pairing of the same title are counted together.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_debrief_method_build_index() {
		$roles = lunara_debrief_method_roles();

		$review_ids = get_posts(
			array(
				'post_type'              => 'review',
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => -1,
				'orderby'                => array( 'date' => 'DESC', 'ID' => 'DESC' ),
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		$review_ids = array_map( 'intval', is_array( $review_ids ) ? $review_ids : array() );
		if ( function_exists( 'update_meta_cache' ) && ! empty( $review_ids ) ) {
			update_meta_cache( 'post', $review_ids );
		}

		$index = array(
			'version'         => LUNARA_DEBRIEF_INDEX_VERSION,
			'reviews_total'   => count( $review_ids ),
			'reviews_debrief' => 0,
			'reviews_trio'    => 0,
			'pairings_total'  => 0,
			'unique_films'    => 0,
			'films'           => array(),
			'recent'          => array(),
		);
		$films   = array();
		$aliases = array();

		foreach ( $review_ids as $review_id ) {
			$pairs = array();

			foreach ( $roles as $slug => $role ) {
				$pair = lunara_debrief_method_read_pairing( $review_id, $role );
				if ( null === $pair ) {
					continue;
				}
				$pair['role'] = $slug;
				$pairs[]      = $pair;

				$keys = array();
				if ( '' !== $pair['tt'] ) {
					$keys[] = 'tt:' . $pair['tt'];
				}
				if ( '' !== $pair['year'] || '' === $pair['tt'] ) {
					$keys[] = lunara_debrief_method_title_key( $pair['title'], $pair['year'] );
				}

				$id = null;
				foreach ( $keys as $key ) {
					if ( isset( $aliases[ $key ] ) ) {
						$id = $aliases[ $key ];
						break;
					}
				}
				if ( null === $id ) {
					$id           = count( $films );
					$films[ $id ] = array(
						'title'   => $pair['title'],
						'year'    => $pair['year'],
						'tt'      => $pair['tt'],
						'href'    => $pair['href'],
						'count'   => 0,
						'roles'   => array_fill_keys( array_keys( $roles ), 0 ),
						'reviews' => array(),
					);
				}
				foreach ( $keys as $key ) {
					$aliases[ $key ] = $id;
				}

				// A linked movie or IMDb-identified entry is the better label.
				if ( '' === $films[ $id ]['tt'] && '' !== $pair['tt'] ) {
					$films[ $id ]['tt']   = $pair['tt'];
					$films[ $id ]['href'] = '' !== $pair['href'] ? $pair['href'] : $films[ $id ]['href'];
				}
				if ( '' === $films[ $id ]['year'] && '' !== $pair['year'] ) {
					$films[ $id ]['year'] = $pair['year'];
				}
				if ( '' === $films[ $id ]['href'] && '' !== $pair['href'] ) {
					$films[ $id ]['href'] = $pair['href'];
				}

				++$films[ $id ]['count'];
				++$films[ $id ]['roles'][ $slug ];
				if ( count( $films[ $id ]['reviews'] ) < LUNARA_DEBRIEF_INDEX_MAX_FILM_REVIEWS && ! in_array( $review_id, $films[ $id ]['reviews'], true ) ) {
					$films[ $id ]['reviews'][] = $review_id;
				}
			}

			if ( empty( $pairs ) ) {
				continue;
			}

			++$index['reviews_debrief'];
			$index['pairings_total'] += count( $pairs );
			if ( count( $pairs ) === count( $roles ) ) {
				++$index['reviews_trio'];
			}
			if ( count( $index['recent'] ) < LUNARA_DEBRIEF_INDEX_MAX_RECENT ) {
				$index['recent'][] = array(
					'review_id' => $review_id,
					'pairs'     => $pairs,
				);
			}
		}

		$index['unique_films'] = count( $films );

		// Keep only the most-prescribed films: count desc, then title.
		usort(
			$films,
			static function ( $a, $b ) {
				if ( $a['count'] !== $b['count'] ) {
					return $b['count'] - $a['count'];
				}
				return strcasecmp( $a['title'], $b['title'] );
			}
		);
		$index['films'] = array_slice( $films, 0, LUNARA_DEBRIEF_INDEX_MAX_FILMS );

		return $index;
	}
}

if ( ! function_exists( 'lunara_debrief_method_index_is_current' ) ) {
	/**
	 * Whether a cached payload has the shape this reader expects.
	 *
	 * @param mixed $index Cached value.
	 * @return bool
	 */
	function lunara_debrief_method_index_is_current( $index ) {
		return is_array( $index )
			&& isset( $index['version'], $index['films'], $index['recent'], $index['reviews_debrief'], $index['pairings_total'], $index['unique_films'] )
			&& LUNARA_DEBRIEF_INDEX_VERSION === $index['version']
			&& is_array( $index['films'] )
			&& is_array( $index['recent'] );
	}
}

if ( ! function_exists( 'lunara_debrief_method_index' ) ) {
	/**
	 * Cached Debrief index. Rebuilt at most every 12 hours or when a review's
	 * pairings, a review's status or a linked movie changes.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_debrief_method_index() {
		$cached = get_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY );
		if ( lunara_debrief_method_index_is_current( $cached ) ) {
			return $cached;
		}

		$index = lunara_debrief_method_build_index();
		set_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY, $index, 12 * HOUR_IN_SECONDS );

		return $index;
	}
}

if ( ! function_exists( 'lunara_debrief_method_review_has_debrief' ) ) {
	/**
	 * Whether a post is a published, public review with at least one pairing.
	 *
	 * @param int $review_id Candidate post ID.
	 * @return bool
	 */
	function lunara_debrief_method_review_has_debrief( $review_id ) {
		$review_id = (int) $review_id;
		$post      = $review_id > 0 ? get_post( $review_id ) : null;
		if ( ! $post || 'review' !== $post->post_type || 'publish' !== $post->post_status || '' !== (string) $post->post_password ) {
			return false;
		}
		foreach ( lunara_debrief_method_roles() as $role ) {
			if ( null !== lunara_debrief_method_read_pairing( $review_id, $role ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'lunara_debrief_method_specimen_id' ) ) {
	/**
	 * The review whose Debrief the page shows as its specimen.
	 *
	 * A pinned review wins while it is published with a Debrief; otherwise the
	 * newest review carrying the full trio, else the newest with any pairing.
	 *
	 * @param array<string,mixed> $index     Debrief index.
	 * @param int                 $pinned_id Pinned review ID, 0 for automatic.
	 * @return int
	 */
	function lunara_debrief_method_specimen_id( $index, $pinned_id = 0 ) {
		$pinned_id = (int) $pinned_id;
		if ( $pinned_id > 0 && lunara_debrief_method_review_has_debrief( $pinned_id ) ) {
			return $pinned_id;
		}

		$recent = isset( $index['recent'] ) && is_array( $index['recent'] ) ? $index['recent'] : array();
		$trio   = count( lunara_debrief_method_roles() );
		foreach ( $recent as $entry ) {
			if ( $trio === count( $entry['pairs'] ) ) {
				return (int) $entry['review_id'];
			}
		}

		return empty( $recent ) ? 0 : (int) $recent[0]['review_id'];
	}
}

if ( ! function_exists( 'lunara_debrief_method_canon' ) ) {
	/**
	 * The canon: the most-prescribed films, bounded by the page settings.
	 *
	 * @param array<string,mixed> $index     Debrief index.
	 * @param int                 $limit     Maximum films.
	 * @param int                 $min_count Minimum prescriptions to qualify.
	 * @return array<int,array<string,mixed>>
	 */
	function lunara_debrief_method_canon( $index, $limit, $min_count ) {
		$films = isset( $index['films'] ) && is_array( $index['films'] ) ? $index['films'] : array();
		$films = array_values(
			array_filter(
				$films,
				static function ( $film ) use ( $min_count ) {
					return (int) $film['count'] >= max( 2, (int) $min_count );
				}
			)
		);
		return array_slice( $films, 0, max( 0, (int) $limit ) );
	}
}

if ( ! function_exists( 'lunara_debrief_method_review_label' ) ) {
	/**
	 * Short label for a review link: the film before the review's " — " title
	 * line, so "Tony — The Picture Stays With the Kid" reads "Tony".
	 *
	 * @param int $review_id Review post ID.
	 * @return string
	 */
	function lunara_debrief_method_review_label( $review_id ) {
		// get_the_title() is texturized, so a dash may arrive as &#8212;; decode before splitting.
		$title = trim( html_entity_decode( wp_strip_all_tags( (string) get_the_title( (int) $review_id ) ), ENT_QUOTES, 'UTF-8' ) );
		$parts = preg_split( '/\s+[\x{2014}\x{2013}]\s+/u', $title, 2 );
		return is_array( $parts ) && '' !== trim( (string) $parts[0] ) ? trim( (string) $parts[0] ) : $title;
	}
}

if ( ! function_exists( 'lunara_debrief_method_flush_index' ) ) {
	/**
	 * Drop the cached index (and every retired key) when a review or a linked
	 * movie changes.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object, when the hook supplies it.
	 */
	function lunara_debrief_method_flush_index( $post_id = 0, $post = null ) {
		if ( $post_id ) {
			$type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );
			if ( ! in_array( $type, array( 'review', 'movie' ), true ) ) {
				return;
			}
		}
		delete_transient( LUNARA_DEBRIEF_INDEX_CACHE_KEY );
		foreach ( lunara_debrief_method_retired_cache_keys() as $retired ) {
			delete_transient( $retired );
		}
	}
	add_action( 'save_post_review', 'lunara_debrief_method_flush_index', 99, 2 );
	add_action( 'save_post_movie', 'lunara_debrief_method_flush_index', 99, 2 );
	add_action( 'deleted_post', 'lunara_debrief_method_flush_index', 10, 2 );
	add_action( 'trashed_post', 'lunara_debrief_method_flush_index' );
	add_action( 'untrashed_post', 'lunara_debrief_method_flush_index' );
}

if ( ! function_exists( 'lunara_debrief_method_pairing_meta_keys' ) ) {
	/**
	 * Review meta that feeds the index, for writes that bypass save_post
	 * (importers, REST meta updates, the MCP tools).
	 *
	 * @return array<int,string>
	 */
	function lunara_debrief_method_pairing_meta_keys() {
		$keys = array( '_lunara_craft_mirror' );
		foreach ( lunara_debrief_method_roles() as $role ) {
			$keys[] = $role['meta'];
			$keys[] = $role['relation'] . '_movie';
		}
		return $keys;
	}
}

if ( ! function_exists( 'lunara_debrief_method_flush_on_meta' ) ) {
	/**
	 * Flush when pairing meta on a review is added, changed or removed.
	 *
	 * @param mixed  $meta_ids  Meta ID(s); unused.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 */
	function lunara_debrief_method_flush_on_meta( $meta_ids, $object_id, $meta_key ) {
		unset( $meta_ids );
		if ( in_array( (string) $meta_key, lunara_debrief_method_pairing_meta_keys(), true ) && 'review' === get_post_type( (int) $object_id ) ) {
			lunara_debrief_method_flush_index();
		}
	}
	add_action( 'added_post_meta', 'lunara_debrief_method_flush_on_meta', 10, 3 );
	add_action( 'updated_post_meta', 'lunara_debrief_method_flush_on_meta', 10, 3 );
	add_action( 'deleted_post_meta', 'lunara_debrief_method_flush_on_meta', 10, 3 );
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
	 * Enqueue the page sheet, the shared pair-card components and the
	 * constellation script.
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

		$orbit = lunara_resolve_theme_asset( 'assets/js/lunara-debrief-orbit.js' );
		if ( ! empty( $orbit['uri'] ) ) {
			wp_enqueue_script(
				'lunara-debrief-orbit',
				$orbit['uri'],
				array(),
				lunara_theme_asset_version( $orbit['path'] ),
				true
			);
			wp_script_add_data( 'lunara-debrief-orbit', 'strategy', 'defer' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'lunara_debrief_method_enqueue_styles', 110 );
}

/* ========================================
   THE CONSTELLATION (3.2.95)
   The featured Debrief, animated: the reviewed film at the centre, gold
   lines drawing out to its three pairings, the triangle closing around it,
   then the next Debrief. Server-rendered and complete without JavaScript
   (the first Debrief shows statically); assets/js/lunara-debrief-orbit.js
   draws the lines and runs the sequence, and honours reduced motion.
   ======================================== */

if ( ! defined( 'LUNARA_DEBRIEF_ORBIT_MAX_SLIDES' ) ) {
	define( 'LUNARA_DEBRIEF_ORBIT_MAX_SLIDES', 6 );
}

if ( ! function_exists( 'lunara_debrief_method_orbit_entries' ) ) {
	/**
	 * The Debriefs the constellation cycles through: the featured review
	 * first, then the newest other reviews carrying the full trio.
	 *
	 * @param array<string,mixed> $index       Debrief index.
	 * @param int                 $specimen_id Featured review ID (0 for none).
	 * @param int                 $limit       Maximum Debriefs.
	 * @return array<int,array<string,mixed>>
	 */
	function lunara_debrief_method_orbit_entries( $index, $specimen_id, $limit = LUNARA_DEBRIEF_ORBIT_MAX_SLIDES ) {
		$roles   = array_keys( lunara_debrief_method_roles() );
		$entries = array();
		$first   = null;

		foreach ( (array) ( $index['recent'] ?? array() ) as $entry ) {
			$pairs = array();
			foreach ( (array) $entry['pairs'] as $pair ) {
				$pairs[ $pair['role'] ] = $pair;
			}
			if ( count( array_intersect( $roles, array_keys( $pairs ) ) ) !== count( $roles ) ) {
				continue;
			}
			$row = array(
				'review_id' => (int) $entry['review_id'],
				'pairs'     => $pairs,
			);
			if ( (int) $entry['review_id'] === (int) $specimen_id ) {
				$first = $row;
			} else {
				$entries[] = $row;
			}
		}

		// A pinned specimen older than the recent window still leads, with whatever pairings it has.
		if ( null === $first && $specimen_id > 0 ) {
			$pairs = array();
			foreach ( lunara_debrief_method_roles() as $slug => $role ) {
				$pair = lunara_debrief_method_read_pairing( (int) $specimen_id, $role );
				if ( null !== $pair ) {
					$pair['role']   = $slug;
					$pairs[ $slug ] = $pair;
				}
			}
			if ( $pairs ) {
				$first = array(
					'review_id' => (int) $specimen_id,
					'pairs'     => $pairs,
				);
			}
		}

		if ( null !== $first ) {
			array_unshift( $entries, $first );
		}

		return array_slice( $entries, 0, max( 1, (int) $limit ) );
	}
}

if ( ! function_exists( 'lunara_debrief_method_poster_plate' ) ) {
	/**
	 * Title plate for a film with no artwork, in the same 2:3 box.
	 *
	 * @param string $title Film title.
	 * @return string
	 */
	function lunara_debrief_method_poster_plate( $title ) {
		return '<span class="lunara-debrief-orbit-plate"><span class="lunara-debrief-orbit-plate-mark" aria-hidden="true"></span>' . esc_html( $title ) . '</span>';
	}
}

if ( ! function_exists( 'lunara_debrief_method_review_poster_html' ) ) {
	/**
	 * Poster for the reviewed film: its IMDb-keyed poster, else the review's
	 * featured image, else a title plate.
	 *
	 * @param int    $review_id Review post ID.
	 * @param string $label     Film label.
	 * @param string $sizes     Rendered-width hint.
	 * @return string
	 */
	function lunara_debrief_method_review_poster_html( $review_id, $label, $sizes ) {
		$tt = strtolower( trim( (string) get_post_meta( $review_id, '_lunara_imdb_title_id', true ) ) );
		if ( preg_match( '#(tt\d{7,8})#', $tt, $match ) ) {
			$tt = $match[1];
		}
		if ( preg_match( '/^tt\d{7,8}$/', $tt ) && function_exists( 'lunara_get_title_poster_html' ) ) {
			$html = (string) lunara_get_title_poster_html( $tt, 'medium', 'lunara-debrief-orbit-img', $label, 'lazy', $sizes );
			if ( '' !== trim( $html ) ) {
				return $html;
			}
		}
		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $review_id ) && function_exists( 'get_the_post_thumbnail' ) ) {
			$html = (string) get_the_post_thumbnail(
				$review_id,
				'medium',
				array(
					'class'    => 'lunara-debrief-orbit-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => $sizes,
				)
			);
			if ( '' !== trim( $html ) ) {
				return $html;
			}
		}
		return lunara_debrief_method_poster_plate( $label );
	}
}

if ( ! function_exists( 'lunara_debrief_method_orbit_html' ) ) {
	/**
	 * The animated constellation markup.
	 *
	 * @param array<int,array<string,mixed>>    $entries Orbit entries.
	 * @param array<string,array<string,string>> $roles   Display roles.
	 * @return string
	 */
	function lunara_debrief_method_orbit_html( $entries, $roles ) {
		if ( empty( $entries ) ) {
			return '';
		}

		$home  = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
		$total = count( $entries );
		$html  = '<div class="lunara-debrief-orbit" data-debrief-orbit data-orbit-hold="7200" aria-roledescription="' . esc_attr__( 'carousel', 'lunara-film' ) . '">';
		$html .= '<ol class="lunara-debrief-orbit-stage">';

		foreach ( $entries as $i => $entry ) {
			$review_id = (int) $entry['review_id'];
			$label     = lunara_debrief_method_review_label( $review_id );
			$url       = (string) get_permalink( $review_id );
			$title     = wp_strip_all_tags( (string) get_the_title( $review_id ) );

			$html .= '<li class="lunara-debrief-orbit-slide' . ( 0 === $i ? ' is-active is-drawn' : '' ) . '"'
				. ' data-orbit-slide data-review="' . esc_attr( (string) $review_id ) . '"'
				. ' data-review-url="' . esc_url( $url ) . '" data-review-title="' . esc_attr( $title ) . '"'
				. ' aria-roledescription="' . esc_attr__( 'slide', 'lunara-film' ) . '"'
				/* translators: 1: slide number, 2: slide count, 3: film title. */
				. ' aria-label="' . esc_attr( sprintf( __( '%1$d of %2$d: %3$s', 'lunara-film' ), $i + 1, $total, $label ) ) . '">';

			// Lines first so the posters sit above them. JS positions each one.
			$html .= '<span class="lunara-debrief-orbit-lines" aria-hidden="true">';
			foreach ( array_keys( $roles ) as $slug ) {
				if ( isset( $entry['pairs'][ $slug ] ) ) {
					$html .= '<span class="lunara-debrief-orbit-line lunara-debrief-orbit-line--spoke lunara-debrief-orbit-line--' . esc_attr( $slug ) . '" data-from="center" data-to="' . esc_attr( $slug ) . '"><i></i></span>';
				}
			}
			$rim = array( array( 'theme', 'counter' ), array( 'counter', 'career' ), array( 'career', 'theme' ) );
			foreach ( $rim as $edge ) {
				if ( isset( $entry['pairs'][ $edge[0] ], $entry['pairs'][ $edge[1] ] ) ) {
					$html .= '<span class="lunara-debrief-orbit-line lunara-debrief-orbit-line--rim" data-from="' . esc_attr( $edge[0] ) . '" data-to="' . esc_attr( $edge[1] ) . '"><i></i></span>';
				}
			}
			$html .= '</span>';

			// The reviewed film.
			$html .= '<a class="lunara-debrief-orbit-node lunara-debrief-orbit-node--center" data-orbit-node="center" href="' . esc_url( $url ) . '">';
			$html .= '<span class="lunara-debrief-orbit-poster"><span class="lunara-debrief-orbit-halo" aria-hidden="true"></span>' . lunara_debrief_method_review_poster_html( $review_id, $label, '(min-width: 760px) 220px, 46vw' ) . '</span>';
			$html .= '<span class="lunara-debrief-orbit-caption"><span class="lunara-debrief-orbit-role">' . esc_html__( 'The Review', 'lunara-film' ) . '</span>';
			$html .= '<span class="lunara-debrief-orbit-title">' . esc_html( $label ) . '</span></span></a>';

			// The three pairings.
			foreach ( $roles as $slug => $role ) {
				if ( ! isset( $entry['pairs'][ $slug ] ) ) {
					continue;
				}
				$pair   = $entry['pairs'][ $slug ];
				$poster = ( '' !== (string) $pair['tt'] && function_exists( 'lunara_get_title_poster_html' ) )
					? (string) lunara_get_title_poster_html( $pair['tt'], 'medium', 'lunara-debrief-orbit-img', $pair['title'], 'lazy', '(min-width: 760px) 150px, 26vw' )
					: '';
				if ( '' === trim( $poster ) ) {
					$poster = lunara_debrief_method_poster_plate( $pair['title'] );
				}

				$inner  = '<span class="lunara-debrief-orbit-poster">' . $poster . '</span>';
				$inner .= '<span class="lunara-debrief-orbit-caption"><span class="lunara-debrief-orbit-role">' . esc_html( $role['label'] ) . '</span>';
				$inner .= '<span class="lunara-debrief-orbit-title">' . esc_html( $pair['title'] );
				if ( '' !== (string) $pair['year'] ) {
					$inner .= ' <span class="lunara-debrief-orbit-year">(' . esc_html( $pair['year'] ) . ')</span>';
				}
				$inner .= '</span></span>';

				$class = 'lunara-debrief-orbit-node lunara-debrief-orbit-node--' . esc_attr( $slug );
				$href  = (string) $pair['href'];
				if ( '' === $href ) {
					$html .= '<span class="' . $class . '" data-orbit-node="' . esc_attr( $slug ) . '">' . $inner . '</span>';
				} else {
					$external = '' !== $home && 0 !== strpos( $href, $home );
					$html    .= '<a class="' . $class . '" data-orbit-node="' . esc_attr( $slug ) . '" href="' . esc_url( $href ) . '"'
						. ( $external ? ' target="_blank" rel="noopener noreferrer nofollow"' : '' ) . '>' . $inner . '</a>';
				}
			}

			$html .= '</li>';
		}

		$html .= '</ol>';

		// Controls stay hidden until the script takes over.
		if ( $total > 1 ) {
			$html .= '<div class="lunara-debrief-orbit-controls" data-orbit-controls hidden>';
			$html .= '<button type="button" class="lunara-debrief-orbit-toggle" data-orbit-toggle aria-pressed="false">'
				. '<span class="lunara-debrief-orbit-toggle-pause">' . esc_html__( 'Pause', 'lunara-film' ) . '</span>'
				. '<span class="lunara-debrief-orbit-toggle-play">' . esc_html__( 'Play', 'lunara-film' ) . '</span></button>';
			$html .= '<div class="lunara-debrief-orbit-tabs" role="group" aria-label="' . esc_attr__( 'Choose a Debrief', 'lunara-film' ) . '">';
			foreach ( $entries as $i => $entry ) {
				$html .= '<button type="button" class="lunara-debrief-orbit-tab" data-orbit-go="' . esc_attr( (string) $i ) . '"'
					. ( 0 === $i ? ' aria-current="true"' : '' )
					/* translators: %s: film title. */
					. ' aria-label="' . esc_attr( sprintf( __( 'Show the Debrief for %s', 'lunara-film' ), lunara_debrief_method_review_label( (int) $entry['review_id'] ) ) ) . '">'
					. '<span class="lunara-debrief-orbit-tab-fill" aria-hidden="true"></span></button>';
			}
			$html .= '</div></div>';
		}

		$html .= '</div>';

		return $html;
	}
}

/* ========================================
   THE DEBRIEF CANON — PRIVATE (3.2.95)
   Dalton's call: the canon shows the desk returning to the same films, so it
   is an editorial instrument, not a public section. It renders only in a
   dashboard widget for editors; the Site Studio canon settings drive it.
   ======================================== */

if ( ! function_exists( 'lunara_debrief_method_canon_html' ) ) {
	/**
	 * The private canon list.
	 *
	 * @param array<string,mixed> $index    Debrief index.
	 * @param array<string,mixed> $settings Page settings.
	 * @return string
	 */
	function lunara_debrief_method_canon_html( $index, $settings ) {
		$roles = lunara_debrief_method_display_roles( $settings );
		$canon = lunara_debrief_method_canon( $index, $settings['canon']['count'], $settings['canon']['min_count'] );

		$html  = '<div class="lunara-debrief-canon-private">';
		$html .= '<p class="lunara-debrief-canon-totals">' . esc_html(
			sprintf(
				/* translators: 1: reviews with a Debrief, 2: pairings, 3: distinct films. */
				__( '%1$s reviews debriefed · %2$s pairings · %3$s distinct films', 'lunara-film' ),
				number_format_i18n( (int) $index['reviews_debrief'] ),
				number_format_i18n( (int) $index['pairings_total'] ),
				number_format_i18n( (int) $index['unique_films'] )
			)
		) . '</p>';

		if ( empty( $canon ) ) {
			$html .= '<p>' . esc_html__( 'No film has been paired often enough to join the canon yet.', 'lunara-film' ) . '</p></div>';
			return $html;
		}

		$html .= '<ol class="lunara-debrief-canon-list">';
		foreach ( $canon as $film ) {
			$bits = array();
			foreach ( $roles as $slug => $role ) {
				$n = isset( $film['roles'][ $slug ] ) ? (int) $film['roles'][ $slug ] : 0;
				if ( $n > 0 ) {
					$bits[] = sprintf( '%s ×%d', $role['label'], $n );
				}
			}

			$title = esc_html( $film['title'] );
			if ( '' !== $film['href'] ) {
				$title = '<a href="' . esc_url( $film['href'] ) . '">' . $title . '</a>';
			}
			if ( '' !== $film['year'] ) {
				$title .= ' <span class="lunara-debrief-canon-year">(' . esc_html( $film['year'] ) . ')</span>';
			}

			$html .= '<li class="lunara-debrief-canon-item"><strong class="lunara-debrief-canon-title">' . $title . '</strong> ';
			/* translators: %d: number of reviews. */
			$html .= '<span class="lunara-debrief-canon-count">' . esc_html( sprintf( _n( 'in %d review', 'in %d reviews', (int) $film['count'], 'lunara-film' ), (int) $film['count'] ) ) . '</span>';
			if ( $bits ) {
				$html .= '<br><span class="lunara-debrief-canon-roles">' . esc_html( implode( ' · ', $bits ) ) . '</span>';
			}

			if ( $settings['canon']['show_reviews'] && ! empty( $film['reviews'] ) ) {
				$links = array();
				foreach ( array_slice( (array) $film['reviews'], 0, 3 ) as $review_id ) {
					$url = (string) get_permalink( (int) $review_id );
					if ( '' !== $url ) {
						$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( lunara_debrief_method_review_label( (int) $review_id ) ) . '</a>';
					}
				}
				$more = (int) $film['count'] - count( $links );
				$list = implode( ', ', $links );
				if ( $list && $more > 0 ) {
					/* translators: %d: number of further reviews */
					$list .= ' ' . esc_html( sprintf( _n( 'and %d more', 'and %d more', $more, 'lunara-film' ), $more ) );
				}
				if ( $list ) {
					$html .= '<br><span class="lunara-debrief-canon-reviews">' . esc_html__( 'In the Debriefs for', 'lunara-film' ) . ' ' . $list . '</span>';
				}
			}
			$html .= '</li>';
		}
		$html .= '</ol></div>';

		return $html;
	}
}

if ( ! function_exists( 'lunara_debrief_method_register_canon_widget' ) ) {
	/**
	 * Register the private canon on the WordPress dashboard for editors.
	 */
	function lunara_debrief_method_register_canon_widget() {
		if ( ! function_exists( 'wp_add_dashboard_widget' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$settings = lunara_debrief_method_settings();
		if ( ! $settings['canon']['show'] ) {
			return;
		}
		wp_add_dashboard_widget(
			'lunara_debrief_canon',
			__( 'The Debrief Canon (private)', 'lunara-film' ),
			'lunara_debrief_method_render_canon_widget'
		);
	}
	add_action( 'wp_dashboard_setup', 'lunara_debrief_method_register_canon_widget' );
}

if ( ! function_exists( 'lunara_debrief_method_render_canon_widget' ) ) {
	/**
	 * Dashboard widget body.
	 */
	function lunara_debrief_method_render_canon_widget() {
		echo '<style>.lunara-debrief-canon-list{margin:8px 0 0 1.4em;list-style:decimal}.lunara-debrief-canon-item{margin:0 0 10px;line-height:1.45}.lunara-debrief-canon-count,.lunara-debrief-canon-roles,.lunara-debrief-canon-reviews,.lunara-debrief-canon-year{color:#646970}.lunara-debrief-canon-totals{margin-top:0;color:#646970}</style>';
		echo '<p>' . esc_html__( 'The films the desk returns to most. Only editors see this; it never appears on the public Debrief page.', 'lunara-film' ) . '</p>';
		echo lunara_debrief_method_canon_html( lunara_debrief_method_index(), lunara_debrief_method_settings() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value escaped in the builder.
	}
}

if ( ! function_exists( 'lunara_enqueue_pair_thread_script' ) ) {
	/**
	 * The Pair It With thread on single reviews (3.2.96): a gold line drawn
	 * through the three films as the reader arrives. Styles live in
	 * lunara-review-components.css, which single reviews already load.
	 */
	function lunara_enqueue_pair_thread_script() {
		if ( is_admin() || ! is_singular( 'review' ) || ! function_exists( 'lunara_resolve_theme_asset' ) ) {
			return;
		}
		$asset = lunara_resolve_theme_asset( 'assets/js/lunara-pair-thread.js' );
		if ( empty( $asset['uri'] ) ) {
			return;
		}
		wp_enqueue_script(
			'lunara-pair-thread',
			$asset['uri'],
			array(),
			lunara_theme_asset_version( $asset['path'] ),
			true
		);
		wp_script_add_data( 'lunara-pair-thread', 'strategy', 'defer' );
	}
	add_action( 'wp_enqueue_scripts', 'lunara_enqueue_pair_thread_script', 110 );
}
