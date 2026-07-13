<?php
/**
 * Curated Grids — fully customizable Reviews Grid and Journal Grid blocks.
 *
 * Two per-instance blocks whose every editorial decision lives in block
 * attributes, so any page can carry its own curation:
 *
 *   - lunara/reviews-grid  → lunara_render_curated_reviews_grid()
 *   - lunara/journal-grid  → lunara_render_curated_journal_grid()
 *
 * What "fully customizable" means here:
 *   - Source: newest-first auto feed, or a hand-picked list of posts whose
 *     order in the picker IS the display order (slot 1 = the featured slot).
 *   - Auto-fill: hand-picked slots can top up with the newest posts so a
 *     partially curated grid never runs short.
 *   - Layout: uniform grid or lead-card layout (first slot rendered large),
 *     with a column dial for the uniform cards.
 *   - Cards: excerpt/score/type/date toggles, excerpt length, kicker label.
 *   - Section: heading/kicker/CTA overrides, or no header at all.
 *
 * Cards reuse the exact markup + CSS the homepage renderers ship
 * (.lunara-review-grid-card / .lunara-journal-home-card), so curated grids
 * look native anywhere they're dropped.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_curated_grid_attribute_schema' ) ) {
	/**
	 * Shared attribute schema for both curated grid blocks.
	 *
	 * @param array $overrides Per-block default overrides / extra attributes.
	 * @return array
	 */
	function lunara_curated_grid_attribute_schema( $overrides = array() ) {
		$schema = array(
			'mode'         => array( 'type' => 'string', 'default' => 'latest' ),
			'postIds'      => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'number' ) ),
			'autoFill'     => array( 'type' => 'boolean', 'default' => true ),
			'count'        => array( 'type' => 'number', 'default' => 6 ),
			'layout'       => array( 'type' => 'string', 'default' => 'grid' ),
			'columns'      => array( 'type' => 'number', 'default' => 3 ),
			'showHeader'   => array( 'type' => 'boolean', 'default' => true ),
			'heading'      => array( 'type' => 'string', 'default' => '' ),
			'kicker'       => array( 'type' => 'string', 'default' => '' ),
			'ctaLabel'     => array( 'type' => 'string', 'default' => '' ),
			'ctaUrl'       => array( 'type' => 'string', 'default' => '' ),
			'showExcerpt'  => array( 'type' => 'boolean', 'default' => true ),
			'excerptWords' => array( 'type' => 'number', 'default' => 24 ),
			'cardKicker'   => array( 'type' => 'string', 'default' => '' ),
		);

		foreach ( (array) $overrides as $key => $value ) {
			if ( isset( $schema[ $key ] ) && ! is_array( $value ) ) {
				$schema[ $key ]['default'] = $value;
				continue;
			}
			$schema[ $key ] = $value;
		}

		return $schema;
	}
}

if ( ! function_exists( 'lunara_curated_grid_sanitize_ids' ) ) {
	/**
	 * Sanitize a curated pick list into published post IDs of allowed types,
	 * preserving the editor's slot order.
	 *
	 * @param mixed    $ids   Raw attribute value.
	 * @param string[] $types Allowed post types.
	 * @return int[]
	 */
	function lunara_curated_grid_sanitize_ids( $ids, $types ) {
		$clean = array();
		foreach ( (array) $ids as $raw ) {
			$id = absint( $raw );
			if ( $id <= 0 || in_array( $id, $clean, true ) ) {
				continue;
			}
			$post = get_post( $id );
			if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
				continue;
			}
			if ( ! in_array( $post->post_type, $types, true ) ) {
				continue;
			}
			$clean[] = $id;
		}
		return $clean;
	}
}

if ( ! function_exists( 'lunara_curated_grid_resolve_posts' ) ) {
	/**
	 * Resolve the ordered post list a curated grid instance should render.
	 *
	 * Curated picks always render, in picker order. When auto-fill is on (or
	 * the mode is 'latest'), the newest matching posts pad the grid up to
	 * `count`, never duplicating a pick.
	 *
	 * @param array $attrs  Block attributes.
	 * @param array $config { post_types: string[], tax_query: array }.
	 * @return WP_Post[]
	 */
	function lunara_curated_grid_resolve_posts( $attrs, $config ) {
		$types = isset( $config['post_types'] ) ? (array) $config['post_types'] : array( 'post' );
		$mode  = isset( $attrs['mode'] ) && 'curated' === $attrs['mode'] ? 'curated' : 'latest';
		$count = isset( $attrs['count'] ) ? max( 1, min( 24, (int) $attrs['count'] ) ) : 6;

		$posts  = array();
		$picked = array();

		if ( 'curated' === $mode ) {
			$picked = lunara_curated_grid_sanitize_ids( isset( $attrs['postIds'] ) ? $attrs['postIds'] : array(), $types );
			foreach ( $picked as $id ) {
				$posts[] = get_post( $id );
			}
		}

		$auto_fill = ! isset( $attrs['autoFill'] ) || (bool) $attrs['autoFill'];
		$needed    = 'curated' === $mode
			? ( $auto_fill ? max( 0, $count - count( $posts ) ) : 0 )
			: $count;

		if ( $needed > 0 ) {
			$query_args = array(
				'post_type'              => $types,
				'post_status'            => 'publish',
				'posts_per_page'         => $needed,
				'post__not_in'           => $picked,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'orderby'                => 'date',
				'order'                  => 'DESC',
			);
			if ( ! empty( $config['tax_query'] ) ) {
				$query_args['tax_query'] = $config['tax_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}

			$fill = new WP_Query( $query_args );
			foreach ( $fill->posts as $post ) {
				if ( $post instanceof WP_Post ) {
					$posts[] = $post;
				}
			}
		}

		return array_values(
			array_filter(
				$posts,
				static function ( $post ) {
					return $post instanceof WP_Post;
				}
			)
		);
	}
}

if ( ! function_exists( 'lunara_curated_grid_section_head' ) ) {
	/**
	 * Shared section header (kicker + heading + CTA) for both grids.
	 *
	 * @param array $attrs    Block attributes.
	 * @param array $defaults { heading, kicker, ctaLabel, ctaUrl }.
	 * @return string
	 */
	function lunara_curated_grid_section_head( $attrs, $defaults ) {
		if ( isset( $attrs['showHeader'] ) && ! $attrs['showHeader'] ) {
			return '';
		}

		$heading   = isset( $attrs['heading'] ) ? trim( (string) $attrs['heading'] ) : '';
		$kicker    = isset( $attrs['kicker'] ) ? trim( (string) $attrs['kicker'] ) : '';
		$cta_label = isset( $attrs['ctaLabel'] ) ? trim( (string) $attrs['ctaLabel'] ) : '';
		$cta_url   = isset( $attrs['ctaUrl'] ) ? trim( (string) $attrs['ctaUrl'] ) : '';

		if ( '' === $heading ) {
			$heading = (string) $defaults['heading'];
		}
		if ( '' === $kicker ) {
			$kicker = (string) $defaults['kicker'];
		}
		if ( '' === $cta_label ) {
			$cta_label = (string) $defaults['ctaLabel'];
		}
		if ( '' === $cta_url ) {
			$cta_url = (string) $defaults['ctaUrl'];
		}

		ob_start();
		?>
		<div class="lunara-home-section-head">
			<div>
				<?php if ( '' !== $kicker ) : ?>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
				<?php endif; ?>
				<h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
			</div>
			<?php if ( '' !== $cta_label && '' !== $cta_url ) : ?>
				<a class="lunara-section-link" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_render_curated_reviews_grid' ) ) {
	/**
	 * Render callback for lunara/reviews-grid.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	function lunara_render_curated_reviews_grid( $attrs = array() ) {
		$attrs = is_array( $attrs ) ? $attrs : array();

		$tax_query = array();
		$category  = isset( $attrs['categoryFilter'] ) ? sanitize_title( (string) $attrs['categoryFilter'] ) : '';
		if ( '' !== $category ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => array( $category ),
			);
		}

		$posts = lunara_curated_grid_resolve_posts(
			$attrs,
			array(
				'post_types' => array( 'review' ),
				'tax_query'  => $tax_query,
			)
		);

		if ( empty( $posts ) ) {
			return '';
		}

		$layout        = isset( $attrs['layout'] ) && 'lead' === $attrs['layout'] ? 'lead' : 'grid';
		$columns       = isset( $attrs['columns'] ) ? max( 2, min( 4, (int) $attrs['columns'] ) ) : 3;
		$show_excerpt  = ! isset( $attrs['showExcerpt'] ) || (bool) $attrs['showExcerpt'];
		$show_score    = ! isset( $attrs['showScore'] ) || (bool) $attrs['showScore'];
		$excerpt_words = isset( $attrs['excerptWords'] ) ? max( 8, min( 80, (int) $attrs['excerptWords'] ) ) : 46;
		$card_kicker   = isset( $attrs['cardKicker'] ) ? trim( (string) $attrs['cardKicker'] ) : '';
		$mode          = isset( $attrs['mode'] ) && 'curated' === $attrs['mode'] ? 'curated' : 'latest';

		if ( '' === $card_kicker ) {
			$card_kicker = __( 'Lunara Review', 'lunara-film' );
		}

		$head = lunara_curated_grid_section_head(
			$attrs,
			array(
				'heading'  => __( 'Reviews', 'lunara-film' ),
				'kicker'   => __( 'Lunara Reviews', 'lunara-film' ),
				'ctaLabel' => __( 'All Reviews', 'lunara-film' ),
				'ctaUrl'   => home_url( '/reviews/' ),
			)
		);

		ob_start();
		?>
		<section class="lunara-home-section lunara-curated-grid-section lunara-latest-reviews-section lunara-reviews-grid-block" data-grid-mode="<?php echo esc_attr( $mode ); ?>" aria-label="<?php esc_attr_e( 'Reviews', 'lunara-film' ); ?>">
			<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="lunara-review-grid lunara-review-archive-uniform lunara-curated-grid is-layout-<?php echo esc_attr( $layout ); ?>" style="--lunara-cgrid-cols: <?php echo esc_attr( (string) $columns ); ?>;">
				<?php foreach ( $posts as $index => $post ) :
					$rid     = (int) $post->ID;
					$is_lead = ( 'lead' === $layout && 0 === $index );
					$score   = $show_score ? get_post_meta( $rid, '_lunara_score', true ) : '';
					$quote   = '';
					if ( $show_excerpt ) {
						$quote = function_exists( 'lunara_get_review_card_pull_quote' )
							? lunara_get_review_card_pull_quote( $rid, $excerpt_words )
							: wp_trim_words( wp_strip_all_tags( get_the_excerpt( $rid ) ), $excerpt_words, '...' );
					}
					$thumb_attrs = array(
						'class'         => 'lunara-review-grid-poster',
						'loading'       => 'lazy',
						'decoding'      => 'async',
						'fetchpriority' => 'low',
						'sizes'         => $is_lead
							? '(max-width: 760px) 92vw, (max-width: 1180px) 88vw, 900px'
							: '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 260px',
					);
					$image_size = $is_lead ? 'lunara-hero-spotlight' : 'newspack-article-block-portrait-intermediate';
					$image_data = function_exists( 'lunara_get_review_card_image_data' )
						? lunara_get_review_card_image_data( $rid, $image_size, $thumb_attrs )
						: array(
							'url'  => has_post_thumbnail( $rid ) ? get_the_post_thumbnail_url( $rid, 'medium_large' ) : '',
							'html' => has_post_thumbnail( $rid ) ? get_the_post_thumbnail( $rid, 'medium_large', $thumb_attrs ) : '',
						);
					$thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
					$has_thumb_html  = ! empty( $image_data['html'] );
					$use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
					$has_card_media  = $has_thumb_html || $use_fallback_bg;
					?>
					<article class="lunara-review-grid-card<?php echo $is_lead ? ' is-lead' : ''; ?> <?php echo $has_card_media ? 'has-visual' : 'has-no-visual'; ?>">
						<a class="lunara-review-grid-link" href="<?php echo esc_url( get_permalink( $rid ) ); ?>">
							<?php if ( $has_card_media ) : ?>
								<div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
									<?php if ( $has_thumb_html ) : ?>
										<?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endif; ?>
									<?php if ( $score && function_exists( 'lunara_render_stars' ) ) : ?>
										<span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<div class="lunara-review-grid-copy">
								<p class="lunara-review-grid-kicker"><?php echo esc_html( $is_lead ? __( 'Featured Review', 'lunara-film' ) : $card_kicker ); ?></p>
								<?php if ( ! $has_card_media && $score && function_exists( 'lunara_render_stars' ) ) : ?>
									<span class="lunara-score-badge is-inline-score"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
								<?php endif; ?>
								<h3 class="lunara-review-grid-title"><?php echo esc_html( get_the_title( $rid ) ); ?></h3>
								<?php if ( '' !== trim( (string) $quote ) ) : ?>
									<p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_render_curated_journal_grid' ) ) {
	/**
	 * Render callback for lunara/journal-grid.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	function lunara_render_curated_journal_grid( $attrs = array() ) {
		$attrs = is_array( $attrs ) ? $attrs : array();

		$include_posts = ! isset( $attrs['includePosts'] ) || (bool) $attrs['includePosts'];
		$type_filter   = isset( $attrs['typeFilter'] ) ? sanitize_title( (string) $attrs['typeFilter'] ) : '';

		$post_types = array( 'journal' );
		$tax_query  = array();
		if ( '' !== $type_filter ) {
			// A journal_type filter only makes sense for the journal CPT.
			$tax_query[] = array(
				'taxonomy' => 'journal_type',
				'field'    => 'slug',
				'terms'    => array( $type_filter ),
			);
		} elseif ( $include_posts ) {
			$post_types[] = 'post';
		}

		$posts = lunara_curated_grid_resolve_posts(
			$attrs,
			array(
				'post_types' => '' !== $type_filter ? array( 'journal' ) : $post_types,
				'tax_query'  => $tax_query,
			)
		);

		if ( empty( $posts ) ) {
			return '';
		}

		$layout        = isset( $attrs['layout'] ) && 'grid' === $attrs['layout'] ? 'grid' : 'lead';
		$columns       = isset( $attrs['columns'] ) ? max( 2, min( 4, (int) $attrs['columns'] ) ) : 2;
		$show_excerpt  = ! isset( $attrs['showExcerpt'] ) || (bool) $attrs['showExcerpt'];
		$show_type     = ! isset( $attrs['showType'] ) || (bool) $attrs['showType'];
		$show_date     = ! isset( $attrs['showDate'] ) || (bool) $attrs['showDate'];
		$excerpt_words = isset( $attrs['excerptWords'] ) ? max( 8, min( 80, (int) $attrs['excerptWords'] ) ) : 22;
		$card_kicker   = isset( $attrs['cardKicker'] ) ? trim( (string) $attrs['cardKicker'] ) : '';
		$mode          = isset( $attrs['mode'] ) && 'curated' === $attrs['mode'] ? 'curated' : 'latest';

		if ( '' === $card_kicker ) {
			$card_kicker = __( 'From the desk', 'lunara-film' );
		}

		$head = lunara_curated_grid_section_head(
			$attrs,
			array(
				'heading'  => __( 'Fresh movement from the Lunara Journal', 'lunara-film' ),
				'kicker'   => __( 'Journal', 'lunara-film' ),
				'ctaLabel' => __( 'Open the Journal', 'lunara-film' ),
				'ctaUrl'   => function_exists( 'lunara_home_dispatch_archive_url' ) ? lunara_home_dispatch_archive_url() : home_url( '/journal/' ),
			)
		);

		ob_start();
		?>
		<section class="lunara-home-section lunara-curated-grid-section lunara-dispatches-section lunara-journal-grid-block" data-grid-mode="<?php echo esc_attr( $mode ); ?>" aria-label="<?php esc_attr_e( 'Journal', 'lunara-film' ); ?>">
			<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="lunara-journal-home-grid lunara-curated-grid is-layout-<?php echo esc_attr( $layout ); ?>" style="--lunara-cgrid-cols: <?php echo esc_attr( (string) $columns ); ?>;" aria-label="<?php esc_attr_e( 'Lunara Journal stories', 'lunara-film' ); ?>">
				<?php foreach ( $posts as $index => $journal_post ) :
					$pid        = (int) $journal_post->ID;
					$is_lead    = ( 'lead' === $layout && 0 === $index );
					$card_url   = get_permalink( $pid );
					$thumb_size = 'lunara-hero-spotlight';
					$has_visual = has_post_thumbnail( $pid );
					$type_label = $show_type && function_exists( 'lunara_get_dispatch_type_label' )
						? lunara_get_dispatch_type_label( $pid )
						: '';
					$thumb_attrs = array(
						'class'         => 'lunara-journal-home-card-image',
						'loading'       => 'lazy',
						'decoding'      => 'async',
						'fetchpriority' => 'low',
						'sizes'         => '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 46vw, 620px',
					);
					?>
					<article class="lunara-journal-home-card<?php echo $is_lead ? ' is-lead' : ''; ?> <?php echo $has_visual ? 'has-visual' : 'has-no-visual'; ?>">
						<a class="lunara-journal-home-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<?php if ( $has_visual ) : ?>
								<div class="lunara-journal-home-card-media">
									<?php echo get_the_post_thumbnail( $pid, $thumb_size, $thumb_attrs ); ?>
								</div>
							<?php endif; ?>
							<div class="lunara-journal-home-card-copy">
								<p class="lunara-journal-home-card-kicker"><?php echo esc_html( $is_lead ? __( 'Lead file', 'lunara-film' ) : $card_kicker ); ?></p>
								<?php if ( '' !== trim( (string) $type_label ) ) : ?>
									<p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
								<?php endif; ?>
								<?php if ( function_exists( 'lunara_render_journal_card_provenance' ) ) : ?>
									<?php lunara_render_journal_card_provenance( $pid, 'home' ); ?>
								<?php endif; ?>
								<?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
									<?php echo lunara_render_trailer_card_badge( $pid, 'journal-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
								<h3 class="lunara-journal-home-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
								<?php if ( $show_excerpt ) : ?>
									<p class="lunara-journal-home-card-excerpt"><?php
										echo esc_html(
											function_exists( 'lunara_card_excerpt' )
												? lunara_card_excerpt( $pid, $is_lead ? max( $excerpt_words, 30 ) : $excerpt_words )
												: wp_trim_words( get_the_excerpt( $pid ), $excerpt_words )
										);
									?></p>
								<?php endif; ?>
								<div class="lunara-journal-home-card-meta">
									<?php if ( $show_date ) : ?>
										<span><?php echo esc_html( get_the_date( 'M j, Y', $pid ) ); ?></span>
									<?php endif; ?>
									<span class="lunara-journal-home-card-cta"><?php esc_html_e( 'Read file', 'lunara-film' ); ?></span>
								</div>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_register_curated_grid_blocks' ) ) {
	function lunara_register_curated_grid_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset = function_exists( 'lunara_resolve_theme_asset' )
			? lunara_resolve_theme_asset( 'assets/js/lunara-curated-grids.js' )
			: array();

		if ( ! empty( $asset['path'] ) ) {
			wp_register_script(
				'lunara-curated-grids',
				$asset['uri'],
				array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-api-fetch', 'wp-url' ),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
				true
			);
		}

		$common = array(
			'api_version'   => 3,
			'category'      => 'lunara',
			'editor_script' => 'lunara-curated-grids',
			'supports'      => array(
				'html'     => false,
				'anchor'   => true,
				'multiple' => true,
				'inserter' => true,
			),
		);

		register_block_type(
			'lunara/reviews-grid',
			array_merge(
				$common,
				array(
					'title'           => __( 'Lunara Reviews Grid — Curated', 'lunara-film' ),
					'icon'            => 'star-filled',
					'description'     => __( 'A fully customizable reviews grid: hand-pick and reorder exactly which reviews appear (slot 1 is the featured slot), or run on the newest reviews with optional auto-fill. Layout, columns, score/excerpt display, and section header are all per-instance.', 'lunara-film' ),
					'attributes'      => lunara_curated_grid_attribute_schema(
						array(
							'count'          => 6,
							'columns'        => 3,
							'excerptWords'   => 46,
							'showScore'      => array( 'type' => 'boolean', 'default' => true ),
							'categoryFilter' => array( 'type' => 'string', 'default' => '' ),
						)
					),
					'render_callback' => 'lunara_render_curated_reviews_grid',
				)
			)
		);

		register_block_type(
			'lunara/journal-grid',
			array_merge(
				$common,
				array(
					'title'           => __( 'Lunara Journal Grid — Curated', 'lunara-film' ),
					'icon'            => 'editor-ul',
					'description'     => __( 'A fully customizable journal grid: hand-pick and reorder exactly which journal entries appear (slot 1 is the lead), or run on the newest entries with optional auto-fill. Lead/uniform layout, columns, type/date/excerpt display, and section header are all per-instance.', 'lunara-film' ),
					'attributes'      => lunara_curated_grid_attribute_schema(
						array(
							'count'        => 4,
							'layout'       => 'lead',
							'columns'      => 2,
							'excerptWords' => 22,
							'showType'     => array( 'type' => 'boolean', 'default' => true ),
							'showDate'     => array( 'type' => 'boolean', 'default' => true ),
							'includePosts' => array( 'type' => 'boolean', 'default' => true ),
							'typeFilter'   => array( 'type' => 'string', 'default' => '' ),
						)
					),
					'render_callback' => 'lunara_render_curated_journal_grid',
				)
			)
		);
	}
	add_action( 'init', 'lunara_register_curated_grid_blocks', 100 );
}
