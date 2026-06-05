<?php
/**
 * Journal Custom Post Type
 *
 * Registers the `journal` post type, the `journal_type` taxonomy, and
 * the per-post meta box.  This CPT consolidates what was previously
 * scattered across standard WordPress posts + categories
 * (news, reactions, think-pieces, podcast).
 *
 * URL structure: /journal/{post-slug}/
 * Archive:       /journal/
 * Type archive:  /journal-type/{term-slug}/
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   1.  POST TYPE
   ========================================================================= */

function lunara_register_journal_cpt() {
	$labels = array(
		'name'                  => __( 'Journal',                 'lunara-film' ),
		'singular_name'         => __( 'Journal Entry',           'lunara-film' ),
		'add_new'               => __( 'Add New Entry',           'lunara-film' ),
		'add_new_item'          => __( 'Add New Journal Entry',   'lunara-film' ),
		'edit_item'             => __( 'Edit Journal Entry',      'lunara-film' ),
		'new_item'              => __( 'New Journal Entry',       'lunara-film' ),
		'view_item'             => __( 'View Journal Entry',      'lunara-film' ),
		'view_items'            => __( 'View Journal',            'lunara-film' ),
		'search_items'          => __( 'Search Journal',          'lunara-film' ),
		'not_found'             => __( 'No journal entries found.','lunara-film' ),
		'not_found_in_trash'    => __( 'No journal entries in trash.', 'lunara-film' ),
		'all_items'             => __( 'All Journal Entries',     'lunara-film' ),
		'menu_name'             => __( 'Journal',                 'lunara-film' ),
		'name_admin_bar'        => __( 'Journal Entry',           'lunara-film' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_nav_menus'  => true,
		'show_in_rest'       => true,   // Gutenberg + REST API
		'query_var'          => true,
		'rewrite'            => array(
			'slug'       => 'journal',
			'with_front' => false,
		),
		'capability_type'    => 'post',
		'has_archive'        => 'journal',
		'hierarchical'       => false,
		'menu_position'      => 6,       // right below Posts
		'menu_icon'          => 'dashicons-book-alt',
		'supports'           => array(
			'title',
			'editor',
			'thumbnail',
			'excerpt',
			'author',
			'comments',
			'revisions',
		),
		'taxonomies'         => array( 'journal_type', 'post_tag' ),
	);

	register_post_type( 'journal', $args );
}
add_action( 'init', 'lunara_register_journal_cpt' );


/* =========================================================================
   2.  TAXONOMY — journal_type
   Replaces the old category-based type labels (news / reaction / essay /
   think-piece / podcast).  Each term gets its own archive URL.
   ========================================================================= */

function lunara_register_journal_type_taxonomy() {
	$labels = array(
		'name'              => __( 'Journal Types',         'lunara-film' ),
		'singular_name'     => __( 'Journal Type',          'lunara-film' ),
		'search_items'      => __( 'Search Journal Types',  'lunara-film' ),
		'all_items'         => __( 'All Journal Types',     'lunara-film' ),
		'edit_item'         => __( 'Edit Journal Type',     'lunara-film' ),
		'update_item'       => __( 'Update Journal Type',   'lunara-film' ),
		'add_new_item'      => __( 'Add New Journal Type',  'lunara-film' ),
		'new_item_name'     => __( 'New Journal Type Name', 'lunara-film' ),
		'menu_name'         => __( 'Journal Types',         'lunara-film' ),
	);

	$args = array(
		'labels'            => $labels,
		'hierarchical'      => false,   // tag-style, not category-style
		'public'            => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => false,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => array(
			'slug'         => 'journal-type',
			'with_front'   => false,
			'hierarchical' => false,
		),
		'show_admin_column' => true,    // column in Journal list table
	);

	register_taxonomy( 'journal_type', array( 'journal' ), $args );
}
add_action( 'init', 'lunara_register_journal_type_taxonomy' );


/**
 * Register the per-post meta fields with the REST API so they can be set
 * by Make.com / Zapier / curl / any external automation. Without this,
 * underscore-prefixed meta is "protected" and rejected by the REST handler.
 */
function lunara_register_journal_meta_for_rest() {
	$fields = array(
		'_lunara_journal_kicker'      => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
		'_lunara_journal_signal_note' => array( 'type' => 'string',  'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_journal_featured'    => array( 'type' => 'string',  'sanitize' => 'sanitize_text_field' ),
	);

	foreach ( $fields as $key => $cfg ) {
		register_post_meta( 'journal', $key, array(
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => $cfg['type'],
			'sanitize_callback' => $cfg['sanitize'],
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}
add_action( 'init', 'lunara_register_journal_meta_for_rest' );


/**
 * Seed default journal type terms the first time the theme activates.
 * Runs on after_switch_theme so it won't overwrite edits on updates.
 */
function lunara_seed_journal_type_terms() {
	$defaults = array(
		'News'        => 'news',
		'Reaction'    => 'reaction',
		'Essay'       => 'essay',
		'Think Piece' => 'think-piece',
		'Podcast'     => 'podcast',
		'Dispatch'    => 'dispatch',
	);

	foreach ( $defaults as $name => $slug ) {
		if ( ! term_exists( $slug, 'journal_type' ) ) {
			wp_insert_term( $name, 'journal_type', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'after_switch_theme', 'lunara_seed_journal_type_terms' );


/* =========================================================================
   3.  META BOX — per-post journal controls
   ========================================================================= */

/**
 * Register the meta box on the journal edit screen.
 */
function lunara_journal_add_meta_box() {
	add_meta_box(
		'lunara_journal_meta',
		__( 'Journal Details', 'lunara-film' ),
		'lunara_journal_meta_box_render',
		'journal',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'lunara_journal_add_meta_box' );


/**
 * Render the meta box fields.
 */
function lunara_journal_meta_box_render( $post ) {
	wp_nonce_field( 'lunara_journal_meta_save', 'lunara_journal_meta_nonce' );

	$kicker      = get_post_meta( $post->ID, '_lunara_journal_kicker',      true );
	$signal_note = get_post_meta( $post->ID, '_lunara_journal_signal_note', true );
	$is_featured = get_post_meta( $post->ID, '_lunara_journal_featured',    true );
	?>
	<style>
		.lunara-meta-field { margin: 0 0 14px; }
		.lunara-meta-field label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #1e1e1e; }
		.lunara-meta-field input[type="text"],
		.lunara-meta-field textarea { width: 100%; box-sizing: border-box; }
		.lunara-meta-field textarea { height: 72px; resize: vertical; }
		.lunara-meta-field .description { margin-top: 4px; font-size: 11px; color: #757575; }
	</style>

	<div class="lunara-meta-field">
		<label for="lunara_journal_kicker"><?php esc_html_e( 'Kicker / Label Override', 'lunara-film' ); ?></label>
		<input
			type="text"
			id="lunara_journal_kicker"
			name="lunara_journal_kicker"
			value="<?php echo esc_attr( $kicker ); ?>"
			placeholder="<?php esc_attr_e( 'e.g. Breaking, Exclusive, Long Read', 'lunara-film' ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Overrides the Journal Type label on cards and single pages. Leave blank to use the type term.', 'lunara-film' ); ?></p>
	</div>

	<div class="lunara-meta-field">
		<label for="lunara_journal_signal_note"><?php esc_html_e( 'Signal Note', 'lunara-film' ); ?></label>
		<textarea
			id="lunara_journal_signal_note"
			name="lunara_journal_signal_note"
			placeholder="<?php esc_attr_e( 'Short contextual note shown on cards (falls back to type default).', 'lunara-film' ); ?>"
		><?php echo esc_textarea( $signal_note ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Overrides the global signal note for this entry\'s Journal Type.', 'lunara-film' ); ?></p>
	</div>

	<div class="lunara-meta-field">
		<label>
			<input
				type="checkbox"
				name="lunara_journal_featured"
				value="1"
				<?php checked( $is_featured, '1' ); ?>
			/>
			<?php esc_html_e( 'Feature this entry (lead position on homepage)', 'lunara-film' ); ?>
		</label>
	</div>
	<?php
}


/**
 * Save the meta box fields.
 */
function lunara_journal_meta_box_save( $post_id ) {
	// Security checks.
	if (
		! isset( $_POST['lunara_journal_meta_nonce'] ) ||
		! wp_verify_nonce( $_POST['lunara_journal_meta_nonce'], 'lunara_journal_meta_save' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['lunara_journal_kicker'] ) ) {
		update_post_meta(
			$post_id,
			'_lunara_journal_kicker',
			sanitize_text_field( wp_unslash( $_POST['lunara_journal_kicker'] ) )
		);
	}

	if ( isset( $_POST['lunara_journal_signal_note'] ) ) {
		update_post_meta(
			$post_id,
			'_lunara_journal_signal_note',
			sanitize_textarea_field( wp_unslash( $_POST['lunara_journal_signal_note'] ) )
		);
	}

	update_post_meta(
		$post_id,
		'_lunara_journal_featured',
		isset( $_POST['lunara_journal_featured'] ) ? '1' : '0'
	);
}
add_action( 'save_post_journal', 'lunara_journal_meta_box_save' );

/**
 * Shared last-updated admin column for Journal and standard posts.
 */
function lunara_editorial_admin_columns( $columns ) {
	$updated_columns = array();

	foreach ( $columns as $key => $label ) {
		$updated_columns[ $key ] = $label;

		if ( 'date' === $key ) {
			$updated_columns['lunara_last_updated'] = __( 'Last Updated', 'lunara-film' );
		}
	}

	if ( ! isset( $updated_columns['lunara_last_updated'] ) ) {
		$updated_columns['lunara_last_updated'] = __( 'Last Updated', 'lunara-film' );
	}

	return $updated_columns;
}
add_filter( 'manage_journal_posts_columns', 'lunara_editorial_admin_columns' );
add_filter( 'manage_posts_columns', 'lunara_editorial_admin_columns' );

function lunara_render_editorial_admin_column( $column, $post_id ) {
	if ( 'lunara_last_updated' !== $column ) {
		return;
	}

	$modified = get_the_modified_date( 'M j, Y', $post_id );
	$time     = get_the_modified_date( 'g:i a', $post_id );

	if ( ! $modified ) {
		echo '&mdash;';
		return;
	}

	echo esc_html( $modified );

	if ( $time ) {
		echo '<br><small>' . esc_html( $time ) . '</small>';
	}
}
add_action( 'manage_journal_posts_custom_column', 'lunara_render_editorial_admin_column', 10, 2 );
add_action( 'manage_posts_custom_column', 'lunara_render_editorial_admin_column', 10, 2 );

function lunara_editorial_sortable_admin_columns( $columns ) {
	$columns['lunara_last_updated'] = 'modified';
	return $columns;
}
add_filter( 'manage_edit-journal_sortable_columns', 'lunara_editorial_sortable_admin_columns' );
add_filter( 'manage_edit-post_sortable_columns', 'lunara_editorial_sortable_admin_columns' );


/* =========================================================================
   4.  REWRITE HELPERS
   ========================================================================= */

/**
 * Flush rewrite rules when theme activates so /journal/ resolves immediately.
 */
function lunara_flush_journal_rewrites() {
	lunara_register_journal_cpt();
	lunara_register_journal_type_taxonomy();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lunara_flush_journal_rewrites' );


/* =========================================================================
   5.  HELPER — get the kicker label for a journal post
   ========================================================================= */

/**
 * Return the display kicker for a journal entry:
 *   1. Per-post kicker override  (_lunara_journal_kicker)
 *   2. First journal_type term name
 *   3. Customizer default kicker  (lunara_post_signal_kicker_default)
 *   4. Hard fallback: "Journal"
 *
 * @param  int    $post_id
 * @return string
 */
function lunara_get_journal_kicker( $post_id = 0 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$normalize_kicker = static function( $label ) {
		$label = trim( (string) $label );
		if ( in_array( $label, array( 'News', 'Dispatch', 'Dispatches', 'Dispatches & Audio' ), true ) ) {
			return __( 'Journal', 'lunara-film' );
		}

		return $label;
	};

	$override = trim( (string) get_post_meta( $post_id, '_lunara_journal_kicker', true ) );
	if ( $override !== '' ) {
		return $normalize_kicker( $override );
	}

	$terms = get_the_terms( $post_id, 'journal_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return $normalize_kicker( $terms[0]->name );
	}

	$customizer_default = trim( (string) get_theme_mod( 'lunara_post_signal_kicker_default', '' ) );
	if ( $customizer_default !== '' ) {
		return $normalize_kicker( $customizer_default );
	}

	return __( 'Journal', 'lunara-film' );
}


/**
 * Return the signal note for a journal entry, following the same fallback chain
 * as lunara_get_journal_kicker() but for the longer contextual blurb.
 *
 * @param  int    $post_id
 * @return string
 */
function lunara_get_journal_signal_note( $post_id = 0 ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$override = trim( (string) get_post_meta( $post_id, '_lunara_journal_signal_note', true ) );
	if ( $override !== '' ) {
		return $override;
	}

	// Try to match the first journal_type term to a customizer signal note.
	// (Customizer settings still use the lunara_post_signal_{type} naming —
	//  no need to rename those user-set values.)
	$terms = get_the_terms( $post_id, 'journal_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$term_slug    = $terms[0]->slug;
		$mod_key      = 'lunara_post_signal_' . str_replace( '-', '_', $term_slug );
		$customizer_note = trim( (string) get_theme_mod( $mod_key, '' ) );
		if ( $customizer_note !== '' ) {
			return $customizer_note;
		}
	}

	return '';
}
