<?php
/**
 * Hybrid Homepage Composition (3.1.50).
 *
 * The Home page's blocks ARE the homepage: block order is section order,
 * block presence is section visibility, while compact editor cards link to
 * each public section without rendering the full site in the canvas.
 * Homepage Studio stays alive as a macro layer — applying a
 * publication package or changing order/visibility there writes through to
 * the same blocks, so both surfaces always tell the truth.
 *
 * front-page.php asks lunara_home_uses_block_composition(): when the front
 * page contains any Lunara section block, the blocks render; when it
 * contains none, the legacy Customizer-registry rendering runs untouched —
 * which is also the instant rollback (delete the blocks, old system
 * resumes).
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_home_section_block_map' ) ) {
	/**
	 * Registry slug → homepage section block. Only slugs with a genuine
	 * renderer are mapped (mirrors front-page.php's renderer table).
	 */
	function lunara_home_section_block_map() {
		return array(
			'hero'           => 'lunara/cinematic-hero',
			'latest-reviews' => 'lunara/latest-reviews',
			'pairing-desk'   => 'lunara/pairing-desk',
			'dispatch'       => 'lunara/journal-lane',
			'oscar-picks'    => 'lunara/oscar-picks',
			'oscar-facts'    => 'lunara/oscar-facts',
		);
	}
}

if ( ! function_exists( 'lunara_home_front_page_id' ) ) {
	function lunara_home_front_page_id() {
		return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	}
}

if ( ! function_exists( 'lunara_home_uses_block_composition' ) ) {
	/**
	 * True when the front page's content carries at least one Lunara section
	 * block — the signal that blocks own homepage composition.
	 */
	function lunara_home_uses_block_composition() {
		$page_id = lunara_home_front_page_id();
		if ( $page_id <= 0 ) {
			return false;
		}

		$content = (string) get_post_field( 'post_content', $page_id );
		if ( '' === trim( $content ) ) {
			return false;
		}

		foreach ( lunara_home_section_block_map() as $block_name ) {
			if ( has_block( $block_name, $content ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'lunara_render_home_block_composition' ) ) {
	/**
	 * Render the front page's blocks. do_blocks() only — no third-party
	 * the_content appendages (sharing bars, related posts) can leak into
	 * the front door, and the output stays exactly the section renderers'.
	 */
	function lunara_render_home_block_composition( $excluded_block_names = array() ) {
		$page_id = lunara_home_front_page_id();
		if ( $page_id <= 0 ) {
			return '';
		}

		$content = (string) get_post_field( 'post_content', $page_id );
		if ( '' === trim( $content ) ) {
			return '';
		}

		$excluded_block_names = array_filter( array_map( 'sanitize_text_field', (array) $excluded_block_names ) );
		if ( empty( $excluded_block_names ) ) {
			return do_blocks( $content );
		}

		$renderable_blocks = array();
		foreach ( parse_blocks( $content ) as $block ) {
			$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( '' !== $block_name && in_array( $block_name, $excluded_block_names, true ) ) {
				continue;
			}

			$renderable_blocks[] = serialize_block( $block );
		}

		return do_blocks( implode( "\n\n", $renderable_blocks ) );
	}
}

if ( ! function_exists( 'lunara_compose_home_section_blocks' ) ) {
	/**
	 * Purely compose Home page content from the given ordered slug list.
	 * Existing per-block attributes are preserved: if the current content
	 * already holds an instance of a section's block, that exact block
	 * markup is reused (so Latest Reviews overrides, hero fallback fields,
	 * etc. survive reordering).
	 *
	 * @param string   $content Existing post content.
	 * @param string[] $slugs Ordered registry slugs to compose.
	 * @return string|WP_Error Composed candidate content.
	 */
	function lunara_compose_home_section_blocks( $content, $slugs ) {
		if ( ! is_string( $content ) || ! is_array( $slugs ) ) {
			return new WP_Error( 'lunara_home_composition_invalid', __( 'The Homepage section order could not be composed.', 'lunara-film' ) );
		}

		$map     = lunara_home_section_block_map();
		$current = $content;

		// Harvest existing instances so their attributes survive.
		$existing = array();
		$blocks   = parse_blocks( $current );
		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( '' !== $name && in_array( $name, $map, true ) && ! isset( $existing[ $name ] ) ) {
				$existing[ $name ] = serialize_block( $block );
			}
		}

		$desired = array();
		foreach ( (array) $slugs as $slug ) {
			if ( ! isset( $map[ $slug ] ) ) {
				continue;
			}
			$block_name = $map[ $slug ];
			$desired[]  = isset( $existing[ $block_name ] )
				? $existing[ $block_name ]
				: '<!-- wp:' . $block_name . ' /-->';
		}

		// Replace only canonical lane slots. Every unknown/core block is copied
		// back into its exact stream position, so a Structure save can reorder
		// the six owned lanes without deleting editorial content or plugin blocks.
		$pieces        = array();
		$desired_index = 0;
		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( '' !== $name && in_array( $name, $map, true ) ) {
				if ( isset( $desired[ $desired_index ] ) ) {
					$pieces[] = $desired[ $desired_index ];
					++$desired_index;
				}
				continue;
			}

			$pieces[] = serialize_block( $block );
		}

		while ( isset( $desired[ $desired_index ] ) ) {
			$pieces[] = $desired[ $desired_index ];
			++$desired_index;
		}

		return implode( "\n\n", $pieces );
	}
}

if ( ! function_exists( 'lunara_write_home_section_blocks' ) ) {
	/**
	 * Rewrite the current Home page through the pure composer.
	 *
	 * @param string[] $slugs Ordered registry slugs to compose.
	 * @return bool|WP_Error Whether content changed, or the exact write error.
	 */
	function lunara_write_home_section_blocks( $slugs ) {
		$page_id = lunara_home_front_page_id();
		if ( $page_id <= 0 ) {
			return false;
		}

		$current     = (string) get_post_field( 'post_content', $page_id );
		$new_content = lunara_compose_home_section_blocks( $current, is_array( $slugs ) ? $slugs : array() );
		if ( is_wp_error( $new_content ) ) {
			return $new_content;
		}
		if ( $new_content === $current ) {
			return false;
		}

		$updated = wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => wp_slash( $new_content ),
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		if ( $new_content !== (string) get_post_field( 'post_content', $page_id ) ) {
			return new WP_Error( 'lunara_home_composition_readback_failed', __( 'The Homepage content could not be verified.', 'lunara-film' ) );
		}

		return true;
	}
}

if ( ! function_exists( 'lunara_sync_home_section_blocks_from_settings' ) ) {
	/**
	 * Homepage Studio write-through: recompose the Home page's blocks from
	 * the just-saved order + visibility settings. Runs only when block
	 * composition is already active — Studio never force-converts a
	 * registry-mode homepage.
	 */
	function lunara_sync_home_section_blocks_from_settings() {
		if ( ! lunara_home_uses_block_composition() ) {
			return;
		}

		$map   = lunara_home_section_block_map();
		$slugs = array_keys( $map );

		$order_map = function_exists( 'lunara_get_home_section_order_map' ) ? lunara_get_home_section_order_map() : array();
		usort(
			$slugs,
			static function ( $a, $b ) use ( $order_map ) {
				$order_a = isset( $order_map[ $a ] ) ? (int) $order_map[ $a ] : 99;
				$order_b = isset( $order_map[ $b ] ) ? (int) $order_map[ $b ] : 99;
				return $order_a <=> $order_b;
			}
		);

		$enabled = array();
		foreach ( $slugs as $slug ) {
			$on = function_exists( 'lunara_home_section_is_enabled' ) ? lunara_home_section_is_enabled( $slug ) : true;
			if ( $on ) {
				$enabled[] = $slug;
			}
		}

		lunara_write_home_section_blocks( $enabled );
	}
}
