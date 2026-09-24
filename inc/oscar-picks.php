<?php
/**
 * Oscar Picks — the editorial prediction data behind the Oscars portal's
 * Prediction Board and the homepage Oscar Picks rail.
 *
 * The `lunara_oscar_pick` post type, its `oscar_pick_category` taxonomy, the
 * ceremony-year helpers, the editor meta box, the admin columns, and the one
 * read path both surfaces use: lunara_get_oscar_picks(). Editorial content,
 * not plugin data: Lunara's predicted and actual winners for each ceremony,
 * with behind-the-scenes images instead of generic posters.
 *
 * Moved verbatim out of functions.php in Theme 3.2.90 so the Oscars portal no
 * longer depends on the monolith for its data. functions.php requires this file
 * at the exact position the code used to occupy: the `init` registrations must
 * keep their original order relative to the monolith's other `init` hooks,
 * because the saved rewrite rules reflect that order (see
 * inc/oscar-taxonomy-rewrites.php).
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_register_oscar_pick_cpt' ) ) {
	function lunara_register_oscar_pick_cpt() {
		register_taxonomy( 'oscar_pick_category', 'lunara_oscar_pick', array(
			'labels' => array(
				'name'          => __( 'Pick Categories', 'lunara-film' ),
				'singular_name' => __( 'Pick Category', 'lunara-film' ),
				'menu_name'     => __( 'Categories', 'lunara-film' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'oscar-picks/category', 'with_front' => false ),
		) );

		register_post_type( 'lunara_oscar_pick', array(
			'labels' => array(
				'name'          => __( 'Oscar Picks', 'lunara-film' ),
				'singular_name' => __( 'Oscar Pick', 'lunara-film' ),
				'add_new'       => __( 'Add New Pick', 'lunara-film' ),
				'add_new_item'  => __( 'Add New Oscar Pick', 'lunara-film' ),
				'edit_item'     => __( 'Edit Oscar Pick', 'lunara-film' ),
				'view_item'     => __( 'View Oscar Pick', 'lunara-film' ),
				'menu_name'     => __( 'Oscar Picks', 'lunara-film' ),
				'all_items'     => __( 'All Picks', 'lunara-film' ),
				'search_items'  => __( 'Search Picks', 'lunara-film' ),
				'not_found'     => __( 'No picks yet.', 'lunara-film' ),
			),
			'public'              => true,
			'has_archive'         => 'oscar-picks',
			'rewrite'             => array( 'slug' => 'oscar-picks', 'with_front' => false ),
			'menu_icon'           => 'dashicons-awards',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'          => array( 'oscar_pick_category' ),
			'show_in_rest'        => true,
			'capability_type'     => 'post',
		) );
	}
	add_action( 'init', 'lunara_register_oscar_pick_cpt' );
}

/**
 * Seed default categories on first activation. Idempotent.
 */
if ( ! function_exists( 'lunara_seed_oscar_pick_categories' ) ) {
	function lunara_seed_oscar_pick_categories() {
		$schema_version = 2;
		if ( (int) get_option( 'lunara_oscar_pick_categories_seeded' ) >= $schema_version ) {
			return;
		}
		if ( ! taxonomy_exists( 'oscar_pick_category' ) ) {
			return;
		}
		$defaults = array(
			'Best Picture',
			'Best Director',
			'Best Actor',
			'Best Actress',
			'Best Supporting Actor',
			'Best Supporting Actress',
			'Best Original Screenplay',
			'Best Adapted Screenplay',
			'Best Cinematography',
			'Best Editing',
			'Best Original Score',
			'Best Casting',
			'Best Production Design',
			'Best Costume Design',
			'Best Makeup and Hairstyling',
			'Best Visual Effects',
			'Best Sound',
			'Best Original Song',
			'Best International Feature',
			'Best Documentary Feature',
			'Best Animated Feature',
		);
		foreach ( $defaults as $name ) {
			if ( ! term_exists( $name, 'oscar_pick_category' ) ) {
				wp_insert_term( $name, 'oscar_pick_category' );
			}
		}
		update_option( 'lunara_oscar_pick_categories_seeded', $schema_version );
	}
	add_action( 'init', 'lunara_seed_oscar_pick_categories', 20 );
}

/**
 * Resolve the active homepage awards season without freezing the theme to one
 * ceremony. The next ceremony becomes current after March each year and can
 * still be overridden from Homepage Studio.
 */
if ( ! function_exists( 'lunara_home_oscar_picks_default_ceremony_year' ) ) {
	function lunara_home_oscar_picks_default_ceremony_year() {
		$current_year  = (int) wp_date( 'Y' );
		$current_month = (int) wp_date( 'n' );

		return $current_month >= 4 ? $current_year + 1 : $current_year;
	}
}

if ( ! function_exists( 'lunara_home_oscar_picks_ceremony_year' ) ) {
	function lunara_home_oscar_picks_ceremony_year() {
		$year = absint(
			get_theme_mod(
				'lunara_home_oscar_picks_ceremony_year',
				lunara_home_oscar_picks_default_ceremony_year()
			)
		);

		return max( 1929, min( 2100, $year ) );
	}
}

if ( ! function_exists( 'lunara_oscar_ceremony_ordinal_from_year' ) ) {
	function lunara_oscar_ceremony_ordinal_from_year( $year ) {
		$number = max( 1, absint( $year ) - 1928 );
		$last_two = $number % 100;
		$suffix = 'th';

		if ( $last_two < 11 || $last_two > 13 ) {
			switch ( $number % 10 ) {
				case 1:
					$suffix = 'st';
					break;
				case 2:
					$suffix = 'nd';
					break;
				case 3:
					$suffix = 'rd';
					break;
			}
		}

		return $number . $suffix;
	}
}

if ( ! function_exists( 'lunara_oscar_pick_sanitize_imdb_id' ) ) {
	/**
	 * Bound a pick's IMDb id to the one shape the board can use: a lowercase
	 * tt/nm prefix and five to nine digits. Anything else is dropped, so a
	 * pasted URL or a stray name never reaches the visual resolvers.
	 */
	function lunara_oscar_pick_sanitize_imdb_id( $value, $prefix = 'tt' ) {
		$prefix = 'nm' === $prefix ? 'nm' : 'tt';
		$value  = strtolower( trim( (string) $value ) );
		if ( preg_match( '/\b(' . $prefix . '\d{5,9})\b/', $value, $m ) ) {
			return $m[1];
		}
		return '';
	}
}

if ( ! function_exists( 'lunara_oscar_pick_status_labels' ) ) {
	function lunara_oscar_pick_status_labels() {
		return array(
			'front_runner' => __( 'Front-runner', 'lunara-film' ),
			'contender'    => __( 'Contender', 'lunara-film' ),
			'watchlist'    => __( 'Watchlist', 'lunara-film' ),
			'predicted'    => __( 'Predicted', 'lunara-film' ),
			'won'          => __( 'Won', 'lunara-film' ),
			'lost'         => __( 'Missed', 'lunara-film' ),
		);
	}
}

if ( ! function_exists( 'lunara_oscar_pick_status_label' ) ) {
	function lunara_oscar_pick_status_label( $status ) {
		$labels = lunara_oscar_pick_status_labels();
		$status = sanitize_key( $status );

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['predicted'];
	}
}

/**
 * Meta box: film, person, ceremony year, status, ledger URL.
 */
if ( ! function_exists( 'lunara_oscar_pick_add_meta_box' ) ) {
	function lunara_oscar_pick_add_meta_box() {
		add_meta_box(
			'lunara_oscar_pick_details',
			__( 'Pick Details', 'lunara-film' ),
			'lunara_oscar_pick_meta_box_callback',
			'lunara_oscar_pick',
			'side',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'lunara_oscar_pick_add_meta_box' );
}

if ( ! function_exists( 'lunara_oscar_pick_meta_box_callback' ) ) {
	function lunara_oscar_pick_meta_box_callback( $post ) {
		wp_nonce_field( 'lunara_oscar_pick_save', 'lunara_oscar_pick_nonce' );
		$film          = (string) get_post_meta( $post->ID, '_lunara_pick_film', true );
		$person        = (string) get_post_meta( $post->ID, '_lunara_pick_person', true );
		$ceremony_year = (string) get_post_meta( $post->ID, '_lunara_pick_ceremony_year', true );
		$status        = (string) get_post_meta( $post->ID, '_lunara_pick_status', true );
		$ledger_url    = (string) get_post_meta( $post->ID, '_lunara_pick_oscar_entity_url', true );
		$imdb_id       = (string) get_post_meta( $post->ID, '_lunara_pick_imdb_id', true );
		$person_id     = (string) get_post_meta( $post->ID, '_lunara_pick_person_id', true );
		if ( '' === $status ) {
			$status = 'predicted';
		}
		?>
		<p>
			<label for="lunara_pick_film"><strong><?php esc_html_e( 'Film', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_film" name="lunara_pick_film" value="<?php echo esc_attr( $film ); ?>" class="widefat" placeholder="One Battle After Another" />
		</p>
		<p>
			<label for="lunara_pick_person"><strong><?php esc_html_e( 'Person (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_person" name="lunara_pick_person" value="<?php echo esc_attr( $person ); ?>" class="widefat" placeholder="Sean Penn" />
			<span class="description"><?php esc_html_e( 'Leave blank for film-level picks (Best Picture, Best International Feature).', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_ceremony_year"><strong><?php esc_html_e( 'Ceremony Year', 'lunara-film' ); ?></strong></label><br>
			<input type="number" id="lunara_pick_ceremony_year" name="lunara_pick_ceremony_year" value="<?php echo esc_attr( $ceremony_year ); ?>" class="widefat" placeholder="2026" min="1929" max="2100" step="1" />
			<span class="description"><?php esc_html_e( 'Year the ceremony airs (e.g. 2026 for the 98th Academy Awards).', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_status"><strong><?php esc_html_e( 'Status', 'lunara-film' ); ?></strong></label><br>
			<select id="lunara_pick_status" name="lunara_pick_status" class="widefat">
				<?php foreach ( lunara_oscar_pick_status_labels() as $status_key => $status_label ) : ?>
					<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="lunara_pick_oscar_entity_url"><strong><?php esc_html_e( 'Lunara Ledger URL (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="url" id="lunara_pick_oscar_entity_url" name="lunara_pick_oscar_entity_url" value="<?php echo esc_attr( $ledger_url ); ?>" class="widefat" placeholder="/oscars/title/tt12345/" />
			<span class="description"><?php esc_html_e( 'Link to the film/person page in the Lunara Oscar Ledger. The card image deep-links here.', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_imdb_id"><strong><?php esc_html_e( 'Film IMDb ID (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_imdb_id" name="lunara_pick_imdb_id" value="<?php echo esc_attr( $imdb_id ); ?>" class="widefat" placeholder="tt1234567" pattern="tt[0-9]{5,9}" />
			<span class="description"><?php esc_html_e( 'Pins the poster on the Oscars portal board. Left blank, the desk matches the film title against reviews and the ledger.', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_person_id"><strong><?php esc_html_e( 'Person IMDb ID (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_person_id" name="lunara_pick_person_id" value="<?php echo esc_attr( $person_id ); ?>" class="widefat" placeholder="nm0000123" pattern="nm[0-9]{5,9}" />
			<span class="description"><?php esc_html_e( 'Pins the headshot on the board. Left blank, the desk matches the name against recent ceremony ballots. A featured image on this pick always wins over both.', 'lunara-film' ); ?></span>
		</p>
		<?php
	}
}

if ( ! function_exists( 'lunara_oscar_pick_save_meta' ) ) {
	function lunara_oscar_pick_save_meta( $post_id ) {
		if ( ! isset( $_POST['lunara_oscar_pick_nonce'] ) || ! wp_verify_nonce( $_POST['lunara_oscar_pick_nonce'], 'lunara_oscar_pick_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_lunara_pick_film'             => isset( $_POST['lunara_pick_film'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_pick_film'] ) ) : '',
			'_lunara_pick_person'           => isset( $_POST['lunara_pick_person'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_pick_person'] ) ) : '',
			'_lunara_pick_ceremony_year'    => isset( $_POST['lunara_pick_ceremony_year'] ) ? absint( $_POST['lunara_pick_ceremony_year'] ) : 0,
			'_lunara_pick_oscar_entity_url' => isset( $_POST['lunara_pick_oscar_entity_url'] ) ? esc_url_raw( wp_unslash( $_POST['lunara_pick_oscar_entity_url'] ) ) : '',
			'_lunara_pick_imdb_id'          => isset( $_POST['lunara_pick_imdb_id'] ) ? lunara_oscar_pick_sanitize_imdb_id( wp_unslash( $_POST['lunara_pick_imdb_id'] ), 'tt' ) : '',
			'_lunara_pick_person_id'        => isset( $_POST['lunara_pick_person_id'] ) ? lunara_oscar_pick_sanitize_imdb_id( wp_unslash( $_POST['lunara_pick_person_id'] ), 'nm' ) : '',
		);

		$status = isset( $_POST['lunara_pick_status'] ) ? sanitize_key( $_POST['lunara_pick_status'] ) : 'predicted';
		if ( ! array_key_exists( $status, lunara_oscar_pick_status_labels() ) ) {
			$status = 'predicted';
		}
		$fields['_lunara_pick_status'] = $status;

		foreach ( $fields as $key => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		if ( function_exists( 'lunara_oscars_pick_after_save' ) ) {
			lunara_oscars_pick_after_save( $post_id );
		}
	}
	add_action( 'save_post_lunara_oscar_pick', 'lunara_oscar_pick_save_meta' );
}

/**
 * Admin list: thumbnail, film, ceremony year, status as columns.
 */
if ( ! function_exists( 'lunara_oscar_pick_admin_columns' ) ) {
	function lunara_oscar_pick_admin_columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'title' === $key ) {
				$new['lunara_pick_thumb'] = __( 'Image', 'lunara-film' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['lunara_pick_film']     = __( 'Film', 'lunara-film' );
				$new['lunara_pick_ceremony'] = __( 'Ceremony', 'lunara-film' );
				$new['lunara_pick_status']   = __( 'Status', 'lunara-film' );
			}
		}
		return $new;
	}
	add_filter( 'manage_lunara_oscar_pick_posts_columns', 'lunara_oscar_pick_admin_columns' );

	function lunara_oscar_pick_admin_column_value( $column, $post_id ) {
		switch ( $column ) {
			case 'lunara_pick_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 80, 60 ), array( 'style' => 'border-radius:4px;object-fit:cover;' ) );
				} else {
					echo '<span style="color:#999;">&mdash;</span>';
				}
				break;
			case 'lunara_pick_film':
				echo esc_html( (string) get_post_meta( $post_id, '_lunara_pick_film', true ) ?: 'â€”' );
				break;
			case 'lunara_pick_ceremony':
				$year = (int) get_post_meta( $post_id, '_lunara_pick_ceremony_year', true );
				echo $year > 0 ? esc_html( (string) $year ) : 'â€”';
				break;
			case 'lunara_pick_status':
				$status = (string) get_post_meta( $post_id, '_lunara_pick_status', true );
				$colors = array( 'front_runner' => '#c9a961', 'contender' => '#6f91ad', 'watchlist' => '#7d7d8a', 'predicted' => '#f5a623', 'won' => '#27ae60', 'lost' => '#999' );
				$color  = isset( $colors[ $status ] ) ? $colors[ $status ] : '#999';
				echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:' . esc_attr( $color ) . ';color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;">' . esc_html( lunara_oscar_pick_status_label( $status ) ) . '</span>';
				break;
		}
	}
	add_action( 'manage_lunara_oscar_pick_posts_custom_column', 'lunara_oscar_pick_admin_column_value', 10, 2 );
}

/**
 * Query helper. Returns a WP_Query of Oscar Picks.
 */
if ( ! function_exists( 'lunara_get_oscar_picks' ) ) {
	function lunara_get_oscar_picks( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 12,
			'ceremony_year'  => 0,
			'status'         => '',
			'category'       => '',
			'ordered_ids'    => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		$ordered_ids = array();

		foreach ( (array) $args['ordered_ids'] as $ordered_id ) {
			$ordered_id = absint( $ordered_id );

			if ( $ordered_id > 0 && ! in_array( $ordered_id, $ordered_ids, true ) ) {
				$ordered_ids[] = $ordered_id;
			}
		}

		$query_args = array(
			'post_type'           => 'lunara_oscar_pick',
			'posts_per_page'      => (int) $args['posts_per_page'],
			'post_status'         => 'publish',
			'has_password'        => false,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array(),
			'tax_query'           => array(),
		);

		if ( ! empty( $ordered_ids ) ) {
			$query_args['post__in'] = $ordered_ids;
			$query_args['orderby']  = 'post__in';
		}

		if ( ! empty( $args['ceremony_year'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_ceremony_year',
				'value' => (int) $args['ceremony_year'],
			);
		}
		if ( ! empty( $args['status'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_status',
				'value' => sanitize_key( $args['status'] ),
			);
		}
		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'oscar_pick_category',
				'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
				'terms'    => $args['category'],
			);
		}

		if ( empty( $ordered_ids ) ) {
			// Default sort: most recent ceremony first, then most recently published pick.
			$query_args['meta_key'] = '_lunara_pick_ceremony_year';
			$query_args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			);
		}

		return new WP_Query( $query_args );
	}
}
