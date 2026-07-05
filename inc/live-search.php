<?php
/**
 * Live Global Search — REST handler + overlay panel (Design Spec §6 / §9).
 *
 * The REST API serves asynchronous lookups for the live search overlay,
 * bypassing legacy admin-ajax. One public endpoint (lunara/v1/search)
 * sweeps every public Lunara content type — reviews, journal entries,
 * stories, and (when the entity graph is live) films and talent — and
 * returns grouped, render-ready results. The overlay itself is an
 * off-canvas panel with deep blur gradients and soft gold lines, opened
 * with Cmd/Ctrl+K or "/" (or the header search trigger) and fully
 * keyboard-navigable. With JS off, search falls back to /?s= untouched.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_live_search_post_types' ) ) {
	/**
	 * Content types the live search sweeps, keyed by group label. Entity
	 * types join automatically once Lunara Core registers them.
	 */
	function lunara_live_search_post_types() {
		$types = array(
			'review'  => __( 'Reviews', 'lunara-film' ),
			'journal' => __( 'Journal', 'lunara-film' ),
			'post'    => __( 'Stories', 'lunara-film' ),
			'movie'   => __( 'Films', 'lunara-film' ),
			'person'  => __( 'Talent', 'lunara-film' ),
		);

		foreach ( array_keys( $types ) as $type ) {
			if ( ! post_type_exists( $type ) ) {
				unset( $types[ $type ] );
			}
		}

		return (array) apply_filters( 'lunara_live_search_post_types', $types );
	}
}

if ( ! function_exists( 'lunara_live_search_register_route' ) ) {
	function lunara_live_search_register_route() {
		register_rest_route(
			'lunara/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true', // Public content only.
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'callback'            => 'lunara_live_search_rest_callback',
			)
		);
	}
	add_action( 'rest_api_init', 'lunara_live_search_register_route' );
}

if ( ! function_exists( 'lunara_live_search_rest_callback' ) ) {
	/**
	 * Sweep all public types in one query and bucket the results by type.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function lunara_live_search_rest_callback( $request ) {
		$q      = trim( (string) $request->get_param( 'q' ) );
		$groups = array();

		if ( mb_strlen( $q ) < 2 ) {
			return rest_ensure_response( array( 'q' => $q, 'groups' => array() ) );
		}

		$types = lunara_live_search_post_types();
		if ( empty( $types ) ) {
			return rest_ensure_response( array( 'q' => $q, 'groups' => array() ) );
		}

		$query = new WP_Query(
			array(
				's'                      => $q,
				'post_type'              => array_keys( $types ),
				'post_status'            => 'publish',
				'posts_per_page'         => 30,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		$per_group_cap = max( 1, (int) apply_filters( 'lunara_live_search_per_group', 5 ) );

		foreach ( $query->posts as $post ) {
			$type = (string) $post->post_type;
			if ( ! isset( $types[ $type ] ) ) {
				continue;
			}
			if ( ! isset( $groups[ $type ] ) ) {
				$groups[ $type ] = array(
					'label' => (string) $types[ $type ],
					'items' => array(),
				);
			}
			if ( count( $groups[ $type ]['items'] ) >= $per_group_cap ) {
				continue;
			}

			$meta = '';
			if ( 'movie' === $type ) {
				$meta = trim( (string) get_post_meta( $post->ID, 'release_year', true ) );
			} elseif ( 'person' !== $type ) {
				$meta = (string) get_the_date( 'Y', $post );
			}

			$groups[ $type ]['items'][] = array(
				'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				'url'   => (string) get_permalink( $post ),
				'meta'  => $meta,
			);
		}

		return rest_ensure_response(
			array(
				'q'        => $q,
				'groups'   => array_values( $groups ),
				'more_url' => add_query_arg( 's', rawurlencode( $q ), home_url( '/' ) ),
			)
		);
	}
}

if ( ! function_exists( 'lunara_live_search_enqueue' ) ) {
	function lunara_live_search_enqueue() {
		if ( is_admin() ) {
			return;
		}

		$handle = 'lunara-live-search';
		$path   = get_stylesheet_directory() . '/assets/js/lunara-live-search.js';

		wp_enqueue_script(
			$handle,
			get_stylesheet_directory_uri() . '/assets/js/lunara-live-search.js',
			array(),
			file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' ),
			true
		);

		wp_localize_script(
			$handle,
			'LUNARA_LIVE_SEARCH',
			array(
				'endpoint'    => esc_url_raw( rest_url( 'lunara/v1/search' ) ),
				'placeholder' => __( 'Search films, reviews, talent, the journal…', 'lunara-film' ),
				'empty'       => __( 'Nothing in the archive matches that — yet.', 'lunara-film' ),
				'more'        => __( 'See every result', 'lunara-film' ),
				'hint'        => __( 'Esc to close · ↑↓ to move · Enter to open', 'lunara-film' ),
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'lunara_live_search_enqueue', 20 );
}

if ( ! function_exists( 'lunara_live_search_render_overlay' ) ) {
	/**
	 * The overlay shell, printed once in the footer. Hidden until armed by
	 * the script; the inner form posts to /?s= so JS-off remains intact.
	 */
	function lunara_live_search_render_overlay() {
		if ( is_admin() ) {
			return;
		}
		?>
		<div class="lunara-search-overlay" id="lunara-search-overlay" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site search', 'lunara-film' ); ?>">
			<div class="lunara-search-overlay-veil" data-lunara-search-close aria-hidden="true"></div>
			<div class="lunara-search-overlay-panel">
				<form class="lunara-search-overlay-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
					<input
						type="search"
						name="s"
						id="lunara-search-overlay-input"
						class="lunara-search-overlay-input"
						placeholder="<?php esc_attr_e( 'Search films, reviews, talent, the journal…', 'lunara-film' ); ?>"
						autocomplete="off"
						aria-label="<?php esc_attr_e( 'Search', 'lunara-film' ); ?>"
					/>
					<button type="button" class="lunara-search-overlay-close" data-lunara-search-close aria-label="<?php esc_attr_e( 'Close search', 'lunara-film' ); ?>">&times;</button>
				</form>
				<div class="lunara-search-overlay-results" id="lunara-search-overlay-results" aria-live="polite"></div>
				<p class="lunara-search-overlay-hint"><?php esc_html_e( 'Esc to close · ↑↓ to move · Enter to open', 'lunara-film' ); ?></p>
			</div>
		</div>
		<?php
	}
	add_action( 'wp_footer', 'lunara_live_search_render_overlay', 40 );
}
