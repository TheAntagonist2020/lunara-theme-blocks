<?php
/**
 * Homepage Journal lane helpers and renderer.
 *
 * Extracted from the guarded monolith on 2026-07-07 so homepage runtime
 * ownership lives in focused include files. The original functions.php copies
 * remain guarded fallback until the full monolith retirement pass.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
			$raw_ids = preg_split( '/[\s,;|]+/', $raw_ids );
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

if ( ! function_exists( 'lunara_get_journal_first_meta_value' ) ) {
	function lunara_get_journal_first_meta_value( $object_id, $keys ) {
		foreach ( (array) $keys as $key ) {
			$value = trim( (string) get_post_meta( $object_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_get_journal_visual_context' ) ) {
	/**
	 * Return the compact visual provenance state used by Journal public cards.
	 *
	 * @param int $post_id Journal post ID.
	 * @return array
	 */
	function lunara_get_journal_visual_context( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return array();
		}

		$carousel_ids  = function_exists( 'lunara_get_journal_carousel_ids' ) ? lunara_get_journal_carousel_ids( $post_id ) : array();
		$attachment_id = get_post_thumbnail_id( $post_id );
		$attachment_id = $attachment_id ? absint( $attachment_id ) : ( ! empty( $carousel_ids[0] ) ? absint( $carousel_ids[0] ) : 0 );
		$credit        = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_credit',
				'_lunara_image_credit',
				'_lunara_dispatch_image_credit',
			)
		);
		$source_name   = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_source_name',
				'_lunara_source_name',
				'_lunara_dispatch_source_label',
			)
		);
		$source_url    = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_source_url',
				'_lunara_source_url',
				'_lunara_dispatch_source_url',
			)
		);

		if ( $attachment_id ) {
			if ( '' === $credit ) {
				$credit = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_credit',
						'_lunara_dispatch_image_credit',
					)
				);
			}

			if ( '' === $source_name ) {
				$source_name = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_source_name',
						'_lunara_dispatch_source_label',
					)
				);
			}

			if ( '' === $source_url ) {
				$source_url = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_source_url',
						'_lunara_dispatch_source_url',
					)
				);
			}
		}

		$source_label = $source_name ? $source_name : $credit;

		return array(
			'attachment_id'  => $attachment_id,
			'credit'         => sanitize_text_field( $credit ),
			'source_name'    => sanitize_text_field( $source_name ),
			'source_url'     => esc_url_raw( $source_url ),
			'source_label'   => sanitize_text_field( $source_label ),
			'has_provenance' => '' !== $source_label,
			'carousel_ids'   => $carousel_ids,
			'carousel_count' => count( $carousel_ids ),
			'has_carousel'   => count( $carousel_ids ) >= 2,
		);
	}
}

if ( ! function_exists( 'lunara_render_journal_card_provenance' ) ) {
	/**
	 * Render non-clickable Journal card provenance badges. Cards are usually anchors,
	 * so this intentionally avoids nested links while still surfacing source quality.
	 *
	 * @param int    $post_id Journal post ID.
	 * @param string $context Optional rendering context.
	 */
	function lunara_render_journal_card_provenance( $post_id, $context = 'card' ) {
		$visual = function_exists( 'lunara_get_journal_visual_context' )
			? lunara_get_journal_visual_context( $post_id )
			: array();

		if ( empty( $visual['has_provenance'] ) && empty( $visual['has_carousel'] ) ) {
			return;
		}

		$classes = array( 'lunara-journal-card-provenance' );
		if ( '' !== $context ) {
			$classes[] = 'is-' . sanitize_html_class( $context );
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php esc_attr_e( 'Journal visual provenance', 'lunara-film' ); ?>">
			<?php if ( ! empty( $visual['has_provenance'] ) ) : ?>
				<span class="lunara-journal-card-provenance-pill is-source">
					<?php
					printf(
						/* translators: %s: image source or credit. */
						esc_html__( 'Visual source: %s', 'lunara-film' ),
						esc_html( $visual['source_label'] )
					);
					?>
				</span>
			<?php endif; ?>
			<?php if ( ! empty( $visual['has_carousel'] ) ) : ?>
				<span class="lunara-journal-card-provenance-pill is-carousel">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: carousel image count. */
							_n( '%d still', '%d stills', absint( $visual['carousel_count'] ), 'lunara-film' ),
							absint( $visual['carousel_count'] )
						)
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'lunara_render_homepage_journal_lane' ) ) {
	function lunara_render_homepage_journal_lane() {
		// Pull copy from theme mods (with defaults + legacy normalization).
		$kicker = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_kicker', 'Journal' )
			: 'Journal';
		$heading = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_heading', 'Fresh movement from the Lunara Journal' )
			: 'Fresh movement from the Lunara Journal';
		$copy = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_copy', '' )
			: '';
		$copy = '';
		$button_label = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_button_label', 'Open the Journal' )
			: 'Open the Journal';
		$button_url = function_exists( 'lunara_home_dispatch_archive_url' )
			? lunara_home_dispatch_archive_url()
			: home_url( '/journal/' );

		// Legacy text normalization (matches what front-page.php was doing).
		if ( 'Dispatches & Audio' === trim( (string) $kicker ) )                                 { $kicker = 'Journal'; }
		if ( 'News, Reactions, and the Lunara Journal' === trim( (string) $heading ) )          { $heading = 'Fresh movement from the Lunara Journal'; }

		// Query: 4 most recent dispatches.
		$dispatches = function_exists( 'lunara_home_dispatches_query' )
			? lunara_home_dispatches_query( 4 )
			: new WP_Query( array(
				'post_type'      => array( 'journal', 'post' ),
				'posts_per_page' => 4,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			) );

		if ( ! ( $dispatches instanceof WP_Query ) || ! $dispatches->have_posts() ) {
			return '';
		}

		$dispatch_posts = is_array( $dispatches->posts )
			? array_filter( $dispatches->posts, static function ( $p ) { return $p instanceof WP_Post; } )
			: array();
		if ( count( $dispatch_posts ) > 4 ) {
			$dispatch_posts = array_slice( $dispatch_posts, 0, 4 );
		}
		if ( empty( $dispatch_posts ) ) {
			return '';
		}

		$dispatch_display_type = static function ( $post_id ) {
			return function_exists( 'lunara_get_dispatch_type_label' )
				? lunara_get_dispatch_type_label( $post_id )
				: 'Dispatch';
		};
		$dispatch_meta_label = static function ( $post_id ) {
			$date = get_the_date( 'M j, Y', $post_id );
			return $date ? $date : '';
		};
		$latest_post_id     = (int) $dispatch_posts[0]->ID;
		$latest_update      = get_the_modified_date( 'M j, g:i A', $latest_post_id );
		$desk_types         = array();
		foreach ( $dispatch_posts as $desk_post ) {
			$type_label = $dispatch_display_type( (int) $desk_post->ID );
			if ( '' !== trim( (string) $type_label ) ) {
				$desk_types[] = trim( (string) $type_label );
			}
		}
		$desk_types      = array_slice( array_values( array_unique( $desk_types ) ), 0, 3 );
		$desk_type_label = implode( ' / ', $desk_types );

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-dispatch lunara-dispatches-section" aria-label="Journal">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
					<?php if ( ! empty( $copy ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $copy ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_label ); ?></a>
			</div>

			<div class="lunara-journal-home-deskbar" aria-label="<?php esc_attr_e( 'Journal desk status', 'lunara-film' ); ?>">
				<span><strong><?php esc_html_e( 'Latest file:', 'lunara-film' ); ?></strong> <?php echo esc_html( $latest_update ); ?></span>
				<span><strong><?php esc_html_e( 'On the board:', 'lunara-film' ); ?></strong> <?php echo esc_html( sprintf( _n( '%d file', '%d files', count( $dispatch_posts ), 'lunara-film' ), count( $dispatch_posts ) ) ); ?></span>
				<?php if ( '' !== $desk_type_label ) : ?>
					<span><strong><?php esc_html_e( 'Desk mix:', 'lunara-film' ); ?></strong> <?php echo esc_html( $desk_type_label ); ?></span>
				<?php endif; ?>
			</div>

			<div class="lunara-journal-home-grid" aria-label="Lunara Journal homepage stories">
				<?php foreach ( $dispatch_posts as $dispatch_index => $dispatch_post ) :
					setup_postdata( $dispatch_post );
					$pid         = (int) $dispatch_post->ID;
					$is_lead     = ( 0 === $dispatch_index );
					$card_url    = get_permalink( $pid );
					$thumb_size  = 'lunara-hero-spotlight';
					$has_visual  = has_post_thumbnail( $pid );
					$thumb_url   = $has_visual ? get_the_post_thumbnail_url( $pid, $thumb_size ) : '';
					$thumb_attrs = array(
						'class'    => 'lunara-journal-home-card-image skip-lazy no-lazy',
						'loading'  => 'eager',
						'decoding' => 'async',
						'sizes'    => '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 46vw, 620px',
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
								<p class="lunara-journal-home-card-kicker"><?php echo esc_html( $is_lead ? __( 'Lead file', 'lunara-film' ) : __( 'From the desk', 'lunara-film' ) ); ?></p>
								<p class="lunara-dispatch-type"><?php echo esc_html( $dispatch_display_type( $pid ) ); ?></p>
								<?php lunara_render_journal_card_provenance( $pid, 'home' ); ?>
								<?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
									<?php echo lunara_render_trailer_card_badge( $pid, 'journal-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
								<h3 class="lunara-journal-home-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
								<p class="lunara-journal-home-card-excerpt"><?php
									echo esc_html(
										function_exists( 'lunara_card_excerpt' )
											? lunara_card_excerpt( $pid, $is_lead ? 30 : 18 )
											: wp_trim_words( get_the_excerpt( $pid ), $is_lead ? 30 : 18 )
									);
								?></p>
								<div class="lunara-journal-home-card-meta">
									<span><?php echo esc_html( $dispatch_meta_label( $pid ) ); ?></span>
									<span class="lunara-journal-home-card-cta"><?php esc_html_e( 'Read file', 'lunara-film' ); ?></span>
								</div>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
