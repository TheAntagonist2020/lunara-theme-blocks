<?php
/**
 * Single Journal Entry - Lunara Film
 *
 * Dedicated template for the `journal` CPT. Without this file, WordPress
 * falls back through single.php -> locate_template -> index.php, which
 * renders a generic archive-list view instead of a proper article page.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strip a duplicate hero image from journal content when the same featured
 * image is also embedded inline at the top of the post body. Auto-generated
 * journal posts (Lunara Dispatch plugin) sometimes include the hero image
 * inline, which then renders twice on the page. Match by wp-image-{id} class.
 *
 * Preserved from regressed live state on 2026-05-10 rollback.
 */
if ( ! function_exists( 'lunara_strip_duplicate_journal_hero_image' ) ) {
	function lunara_strip_duplicate_journal_hero_image( $content, $post_id ) {
		$content = (string) $content;
		if ( '' === trim( $content ) ) {
			return $content;
		}

		$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id <= 0 ) {
			return $content;
		}

		$patterns = array(
			'/<p>\s*<img\b[^>]*class="[^"]*\bwp-image-' . preg_quote( (string) $thumbnail_id, '/' ) . '\b[^"]*"[^>]*>\s*<\/p>\s*/is',
			'/<img\b[^>]*class="[^"]*\bwp-image-' . preg_quote( (string) $thumbnail_id, '/' ) . '\b[^"]*"[^>]*>\s*/is',
		);

		foreach ( $patterns as $pattern ) {
			$updated = preg_replace( $pattern, '', $content, 1, $count );
			if ( $count > 0 && null !== $updated ) {
				return ltrim( $updated );
			}
		}

		return $content;
	}
}

if ( ! function_exists( 'lunara_get_journal_hero_credit_data' ) ) {
	/**
	 * Collect featured image provenance for Journal hero captions.
	 *
	 * @param int $post_id Journal post ID.
	 * @return array
	 */
	function lunara_get_journal_hero_credit_data( $post_id ) {
		$post_id       = (int) $post_id;
		$attachment_id = (int) get_post_thumbnail_id( $post_id );

		if ( $post_id <= 0 || $attachment_id <= 0 ) {
			return array();
		}

		$first_meta_value = static function ( array $keys, $object_id ) {
			foreach ( $keys as $key ) {
				$value = trim( (string) get_post_meta( $object_id, $key, true ) );
				if ( '' !== $value ) {
					return $value;
				}
			}

			return '';
		};

		$credit = $first_meta_value(
			array(
				'_lunara_featured_image_credit',
				'_lunara_image_credit',
			),
			$post_id
		);

		if ( '' === $credit ) {
			$credit = $first_meta_value(
				array(
					'_lunara_image_credit',
					'_lunara_dispatch_image_credit',
				),
				$attachment_id
			);
		}

		if ( '' === $credit ) {
			$credit = trim( (string) wp_get_attachment_caption( $attachment_id ) );
		}

		$source_name = $first_meta_value(
			array(
				'_lunara_featured_image_source_name',
				'_lunara_source_name',
			),
			$post_id
		);

		if ( '' === $source_name ) {
			$source_name = $first_meta_value(
				array(
					'_lunara_image_source_name',
					'_lunara_dispatch_source_label',
				),
				$attachment_id
			);
		}

		$source_url = $first_meta_value(
			array(
				'_lunara_featured_image_source_url',
				'_lunara_source_url',
				'_lunara_dispatch_source_url',
			),
			$post_id
		);

		if ( '' === $source_url ) {
			$source_url = $first_meta_value(
				array(
					'_lunara_image_source_url',
					'_lunara_dispatch_source_url',
				),
				$attachment_id
			);
		}

		return array(
			'credit'      => sanitize_text_field( $credit ),
			'source_name' => sanitize_text_field( $source_name ),
			'source_url'  => esc_url_raw( $source_url ),
		);
	}
}

if ( ! function_exists( 'lunara_render_journal_hero_credit' ) ) {
	/**
	 * Render a compact visible hero-image credit when provenance exists.
	 *
	 * @param int $post_id Journal post ID.
	 * @return void
	 */
	function lunara_render_journal_hero_credit( $post_id ) {
		$data = lunara_get_journal_hero_credit_data( $post_id );
		if ( empty( $data['credit'] ) && empty( $data['source_name'] ) ) {
			return;
		}

		$source_html = '';
		if ( ! empty( $data['source_name'] ) ) {
			$source_html = esc_html( $data['source_name'] );
			if ( ! empty( $data['source_url'] ) ) {
				$source_html = sprintf(
					'<a href="%1$s" rel="nofollow noopener" target="_blank">%2$s</a>',
					esc_url( $data['source_url'] ),
					$source_html
				);
			}
		}

		$parts = array();
		if ( ! empty( $data['credit'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: image credit. */
				esc_html__( 'Image: %s', 'lunara-film' ),
				esc_html( $data['credit'] )
			);
		}

		if ( '' !== $source_html ) {
			$parts[] = sprintf(
				/* translators: %s: source outlet name. */
				esc_html__( 'Source: %s', 'lunara-film' ),
				$source_html
			);
		}

		if ( empty( $parts ) ) {
			return;
		}
		?>
		<p class="lunara-journal-cinematic-hero-credit">
			<?php echo wp_kses_post( implode( ' <span aria-hidden="true">/</span> ', $parts ) ); ?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'lunara_get_journal_carousel_ids' ) ) {
	/**
	 * Resolve curated Journal carousel attachment IDs from explicit input or post meta.
	 *
	 * @param int          $post_id Journal post ID.
	 * @param string|array $ids     Optional comma-separated or array attachment IDs.
	 * @return array
	 */
	function lunara_get_journal_carousel_ids( $post_id, $ids = '' ) {
		$post_id = (int) $post_id;
		$raw_ids = $ids;

		if ( empty( $raw_ids ) && $post_id > 0 ) {
			$raw_ids = get_post_meta( $post_id, '_lunara_journal_carousel_ids', true );
		}

		if ( empty( $raw_ids ) && $post_id > 0 ) {
			$raw_ids = get_post_meta( $post_id, '_lunara_journal_gallery_ids', true );
		}

		if ( is_string( $raw_ids ) ) {
			$raw_ids = preg_split( '/[\s,]+/', $raw_ids );
		}

		if ( ! is_array( $raw_ids ) ) {
			return array();
		}

		$attachment_ids = array();
		foreach ( $raw_ids as $raw_id ) {
			$attachment_id = absint( $raw_id );
			if ( $attachment_id <= 0 || in_array( $attachment_id, $attachment_ids, true ) ) {
				continue;
			}

			if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
				continue;
			}

			$attachment_ids[] = $attachment_id;
		}

		return $attachment_ids;
	}
}

if ( ! function_exists( 'lunara_get_journal_carousel_credit_html' ) ) {
	/**
	 * Return a compact image credit line for a Journal carousel item.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	function lunara_get_journal_carousel_credit_html( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$credit = trim( (string) get_post_meta( $attachment_id, '_lunara_image_credit', true ) );
		if ( '' === $credit ) {
			$credit = trim( (string) get_post_meta( $attachment_id, '_lunara_dispatch_image_credit', true ) );
		}

		$source_name = trim( (string) get_post_meta( $attachment_id, '_lunara_image_source_name', true ) );
		if ( '' === $source_name ) {
			$source_name = trim( (string) get_post_meta( $attachment_id, '_lunara_dispatch_source_label', true ) );
		}

		$source_url = trim( (string) get_post_meta( $attachment_id, '_lunara_image_source_url', true ) );
		if ( '' === $source_url ) {
			$source_url = trim( (string) get_post_meta( $attachment_id, '_lunara_dispatch_source_url', true ) );
		}

		$parts = array();
		if ( '' !== $credit ) {
			$parts[] = sprintf(
				/* translators: %s: image credit. */
				esc_html__( 'Image: %s', 'lunara-film' ),
				esc_html( $credit )
			);
		}

		if ( '' !== $source_name ) {
			$source_html = esc_html( $source_name );
			if ( '' !== $source_url ) {
				$source_html = sprintf(
					'<a href="%1$s" rel="nofollow noopener" target="_blank">%2$s</a>',
					esc_url( $source_url ),
					$source_html
				);
			}

			$parts[] = sprintf(
				/* translators: %s: source outlet name. */
				esc_html__( 'Source: %s', 'lunara-film' ),
				$source_html
			);
		}

		return implode( ' <span aria-hidden="true">/</span> ', $parts );
	}
}

if ( ! function_exists( 'lunara_render_journal_image_carousel' ) ) {
	/**
	 * Render a curated image carousel for visual-first Journal entries.
	 *
	 * @param int   $post_id Journal post ID.
	 * @param array $ids     Attachment IDs.
	 * @param array $args    Optional rendering args.
	 * @return string
	 */
	function lunara_render_journal_image_carousel( $post_id, array $ids, array $args = array() ) {
		$ids = lunara_get_journal_carousel_ids( $post_id, $ids );
		if ( empty( $ids ) ) {
			return '';
		}

		$heading = isset( $args['heading'] ) ? trim( (string) $args['heading'] ) : __( 'Image File', 'lunara-film' );
		$context = isset( $args['context'] ) ? trim( (string) $args['context'] ) : __( 'A curated visual pass for this Journal entry.', 'lunara-film' );
		$count   = count( $ids );

		ob_start();
		?>
		<section class="lunara-journal-image-carousel" data-lunara-journal-carousel>
			<div class="lunara-journal-image-carousel-head">
				<div>
					<p class="lunara-home-section-kicker"><?php esc_html_e( 'Visual File', 'lunara-film' ); ?></p>
					<h2><?php echo esc_html( $heading ); ?></h2>
					<?php if ( '' !== $context ) : ?>
						<p><?php echo esc_html( $context ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $count > 1 ) : ?>
					<div class="lunara-journal-image-carousel-controls" aria-label="<?php esc_attr_e( 'Image carousel controls', 'lunara-film' ); ?>">
						<button class="lunara-journal-carousel-btn" type="button" data-lunara-carousel-action="prev" aria-label="<?php esc_attr_e( 'Previous image', 'lunara-film' ); ?>">&lsaquo;</button>
						<button class="lunara-journal-carousel-btn" type="button" data-lunara-carousel-action="next" aria-label="<?php esc_attr_e( 'Next image', 'lunara-film' ); ?>">&rsaquo;</button>
					</div>
				<?php endif; ?>
			</div>

			<div class="lunara-journal-image-carousel-track" tabindex="0">
				<?php foreach ( $ids as $index => $attachment_id ) : ?>
					<?php
					$caption = trim( (string) wp_get_attachment_caption( $attachment_id ) );
					if ( '' === $caption ) {
						$caption = trim( (string) get_the_title( $attachment_id ) );
					}

					$credit_html = lunara_get_journal_carousel_credit_html( $attachment_id );
					if ( '' !== $caption && '' !== $credit_html ) {
						$caption_text = strtolower( wp_strip_all_tags( $caption ) );
						if ( false !== strpos( $caption_text, 'image:' ) || false !== strpos( $caption_text, 'credit:' ) || false !== strpos( $caption_text, 'source:' ) ) {
							$credit_html = '';
						}
					}
					?>
					<figure class="lunara-journal-image-carousel-slide">
						<?php
						echo wp_get_attachment_image(
							$attachment_id,
							'large',
							false,
							array(
								'class'    => 'lunara-journal-image-carousel-image',
								'loading'  => 0 === $index ? 'eager' : 'lazy',
								'decoding' => 'async',
								'sizes'    => '(max-width: 760px) 86vw, 720px',
							)
						);
						?>
						<?php if ( '' !== $caption || '' !== $credit_html ) : ?>
							<figcaption>
								<?php if ( '' !== $caption ) : ?>
									<span class="lunara-journal-image-carousel-caption"><?php echo esc_html( $caption ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $credit_html ) : ?>
									<span class="lunara-journal-image-carousel-credit"><?php echo wp_kses_post( $credit_html ); ?></span>
								<?php endif; ?>
							</figcaption>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return trim( ob_get_clean() );
	}
}

if ( ! function_exists( 'lunara_journal_carousel_shortcode' ) ) {
	/**
	 * Shortcode for manually placing a curated Journal carousel.
	 *
	 * Usage: [lunara_journal_carousel ids="123,456" heading="Production stills"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	function lunara_journal_carousel_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids'     => '',
				'heading' => __( 'Image File', 'lunara-film' ),
				'context' => __( 'A curated visual pass for this Journal entry.', 'lunara-film' ),
			),
			$atts,
			'lunara_journal_carousel'
		);

		return lunara_render_journal_image_carousel(
			get_the_ID(),
			lunara_get_journal_carousel_ids( get_the_ID(), $atts['ids'] ),
			array(
				'heading' => $atts['heading'],
				'context' => $atts['context'],
			)
		);
	}
}
add_shortcode( 'lunara_journal_carousel', 'lunara_journal_carousel_shortcode' );

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		$post_id     = get_the_ID();
		$kicker      = function_exists( 'lunara_get_journal_kicker' )
			? lunara_get_journal_kicker( $post_id )
			: __( 'Journal', 'lunara-film' );
		$signal_note = function_exists( 'lunara_get_journal_signal_note' )
			? lunara_get_journal_signal_note( $post_id )
			: '';

		$published    = get_the_date( 'F j, Y', $post_id );
		$author_name  = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
		$reading_time = function_exists( 'lunara_get_post_reading_time' )
			? lunara_get_post_reading_time( $post_id )
			: '';

		$has_thumb   = has_post_thumbnail( $post_id );
		$archive_url = get_post_type_archive_link( 'journal' );
		?>

		<main class="lunara-editorial-single-page lunara-journal-single-page">
		<article <?php post_class( 'lunara-journal-single lunara-review-single' ); ?>>

			<section class="lunara-journal-single-hero lunara-journal-cinematic-hero<?php echo $has_thumb ? ' has-hero-image' : ' has-no-hero-image'; ?>">
				<div class="lunara-journal-cinematic-hero-header">
					<div class="lunara-journal-cinematic-hero-inner">
						<p class="lunara-review-single-kicker"><?php echo esc_html( $kicker ); ?></p>
						<h1 class="lunara-review-single-title"><?php the_title(); ?></h1>

						<?php if ( '' !== $signal_note ) : ?>
							<p class="lunara-journal-single-signal"><?php echo esc_html( $signal_note ); ?></p>
						<?php endif; ?>

						<?php
						// Customizer-controlled visibility for byline / date / reading time.
						// Defaults: byline + date ON, reading time OFF (per Dalton 2026-04-29).
						$show_byline       = (bool) get_theme_mod( 'lunara_journal_show_byline', true );
						$show_date         = (bool) get_theme_mod( 'lunara_journal_show_date', true );
						$show_reading_time = (bool) get_theme_mod( 'lunara_journal_show_reading_time', false );

						if ( $show_byline || $show_date || $show_reading_time ) :
							$meta_parts = array();
							if ( $show_byline && '' !== $author_name ) {
								/* translators: %s: author name */
								$meta_parts[] = '<span class="lunara-review-single-meta-byline">' . esc_html( sprintf( __( 'By %s', 'lunara-film' ), $author_name ) ) . '</span>';
							}
							if ( $show_date && '' !== $published ) {
								$meta_parts[] = '<span class="lunara-review-single-meta-date">' . esc_html( $published ) . '</span>';
							}
							if ( $show_reading_time && '' !== $reading_time ) {
								$meta_parts[] = '<span class="lunara-review-single-meta-time">' . esc_html( $reading_time ) . '</span>';
							}
						?>
						<div class="lunara-review-single-meta">
							<?php echo implode( ' <span class="lunara-meta-sep" aria-hidden="true">·</span> ', $meta_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $has_thumb ) : ?>
					<div class="lunara-journal-cinematic-hero-frame">
						<figure class="lunara-journal-cinematic-hero-media">
							<?php
							echo get_the_post_thumbnail(
								$post_id,
								'lunara-hero-spotlight',
								array(
									'class'         => 'lunara-journal-cinematic-hero-image',
									'loading'       => 'eager',
									'fetchpriority' => 'high',
									'decoding'      => 'async',
									'sizes'         => '100vw',
								)
							);
							?>
						</figure>
						<?php lunara_render_journal_hero_credit( $post_id ); ?>
					</div>
				<?php endif; ?>
			</section>

			<?php
			$journal_raw_content = get_the_content();
			if ( ! has_shortcode( $journal_raw_content, 'lunara_journal_carousel' ) ) {
				echo lunara_render_journal_image_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$post_id,
					lunara_get_journal_carousel_ids( $post_id ),
					array(
						'heading' => __( 'Images from the file', 'lunara-film' ),
						'context' => __( 'Additional stills are curated when one image is not enough to carry the story.', 'lunara-film' ),
					)
				);
			}

			if (
				function_exists( 'lunara_render_trailer_module' ) &&
				function_exists( 'lunara_get_trailer_placement' ) &&
				'after_hero' === lunara_get_trailer_placement( $post_id ) &&
				! has_shortcode( $journal_raw_content, 'lunara_trailer' )
			) {
				echo lunara_render_trailer_module( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<section class="lunara-journal-single-body lunara-review-single-body">
				<div class="lunara-review-single-body-grid">

					<div class="lunara-review-single-content">
						<?php
						// Strip duplicate hero image if it appears inline (auto-generated content protection).
						$journal_content = lunara_strip_duplicate_journal_hero_image( $journal_raw_content, $post_id );
						$journal_content_html = apply_filters( 'the_content', $journal_content );
						if ( function_exists( 'lunara_insert_trailer_into_content_html' ) && ! has_shortcode( $journal_raw_content, 'lunara_trailer' ) ) {
							$journal_content_html = lunara_insert_trailer_into_content_html( $journal_content_html, $post_id );
						}
						echo $journal_content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

						<?php
						wp_link_pages(
							array(
								'before' => '<p class="lunara-journal-single-pages">' . esc_html__( 'Pages:', 'lunara-film' ),
								'after'  => '</p>',
							)
						);
						?>
					</div>

					<aside class="lunara-review-single-rail" aria-label="<?php esc_attr_e( 'Journal entry sidebar', 'lunara-film' ); ?>">
						<div class="lunara-review-single-rail-sticky">

							<?php
							$type_terms = get_the_terms( $post_id, 'journal_type' );
							if ( $type_terms && ! is_wp_error( $type_terms ) ) :
								?>
								<div class="lunara-journal-rail-card">
									<p class="lunara-journal-rail-card-label"><?php esc_html_e( 'Type', 'lunara-film' ); ?></p>
									<ul class="lunara-journal-rail-type-list">
										<?php foreach ( $type_terms as $term ) : ?>
											<li>
												<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
													<?php echo esc_html( $term->name ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php
							$tags = get_the_tags( $post_id );
							if ( $tags && ! is_wp_error( $tags ) ) :
								?>
								<div class="lunara-journal-rail-card">
									<p class="lunara-journal-rail-card-label"><?php esc_html_e( 'Tagged', 'lunara-film' ); ?></p>
									<ul class="lunara-journal-rail-tag-list">
										<?php foreach ( $tags as $tag ) : ?>
											<li>
												<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>">
													#<?php echo esc_html( $tag->name ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( $archive_url ) : ?>
								<div class="lunara-review-single-rail-actions">
									<a class="lunara-btn lunara-btn-ghost" href="<?php echo esc_url( $archive_url ); ?>">
										<?php esc_html_e( 'Back to the Journal', 'lunara-film' ); ?>
									</a>
								</div>
							<?php endif; ?>

						</div>
					</aside>

				</div>
			</section>

			<?php
			// Related journal entries (most recent in same type, excluding current).
			$related_args = array(
				'post_type'           => 'journal',
				'posts_per_page'      => 3,
				'post__not_in'        => array( $post_id ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			);

			if ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ) {
				$related_args['tax_query'] = array(
					array(
						'taxonomy' => 'journal_type',
						'field'    => 'term_id',
						'terms'    => wp_list_pluck( $type_terms, 'term_id' ),
					),
				);
			}

			$related_query = new WP_Query( $related_args );

			if ( $related_query->have_posts() ) : ?>
				<section class="lunara-journal-single-related lunara-home-section">
					<div class="lunara-home-section-head">
						<p class="lunara-home-section-kicker"><?php esc_html_e( 'More files from the desk', 'lunara-film' ); ?></p>
						<h2 class="lunara-home-section-title"><?php esc_html_e( 'Keep reading', 'lunara-film' ); ?></h2>
					</div>
					<div class="lunara-review-grid lunara-review-related-grid">
						<?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
							<?php
							$rid     = get_the_ID();
							$rkicker = function_exists( 'lunara_get_journal_kicker' )
								? lunara_get_journal_kicker( $rid )
								: __( 'Journal', 'lunara-film' );
							?>
							<article class="lunara-review-grid-card lunara-journal-rail-card">
								<a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
									<?php if ( has_post_thumbnail() ) : ?>
										<div class="lunara-review-grid-poster-wrap">
											<?php the_post_thumbnail( 'medium_large', array( 'class' => 'lunara-review-grid-poster', 'loading' => 'lazy' ) ); ?>
										</div>
									<?php endif; ?>
									<div class="lunara-review-grid-copy">
										<p class="lunara-review-grid-kicker"><?php echo esc_html( $rkicker ); ?></p>
										<h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
										<p class="lunara-review-grid-meta"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></p>
									</div>
								</a>
							</article>
						<?php endwhile; wp_reset_postdata(); ?>
					</div>
				</section>
			<?php endif; ?>

		</article>
		<?php
		if ( function_exists( 'lunara_render_newsletter_signup' ) ) {
			echo lunara_render_newsletter_signup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		</main>

	<?php endwhile; ?>
<?php endif; ?>

<?php get_footer(); ?>
