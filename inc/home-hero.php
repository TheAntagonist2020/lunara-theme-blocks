<?php
/**
 * Homepage cinematic hero renderers, hero curation helpers, and hero customizer controls.
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

if ( ! function_exists( 'lunara_register_cinematic_hero_customizer' ) ) {
	function lunara_register_cinematic_hero_customizer( $wp_customize ) {
		$wp_customize->add_section( 'lunara_cinematic_hero_section', array(
			'title'       => __( 'Lunara Cinematic Hero', 'lunara-film' ),
			'description' => __( 'Controls the full-viewport homepage hero. Image priority: this override (if set) â†’ featured image of the most recent review.', 'lunara-film' ),
			'priority'    => 30,
		) );

		$wp_customize->add_setting( 'lunara_hero_override_image', array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'lunara_hero_override_image',
				array(
					'label'       => __( 'Hero Image (override)', 'lunara-film' ),
					'description' => __( 'Upload a curated cinematic backdrop. Falls back to the latest review\'s featured image if blank.', 'lunara-film' ),
					'section'     => 'lunara_cinematic_hero_section',
				)
			)
		);

		$wp_customize->add_setting( 'lunara_hero_override_kicker', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_kicker', array(
			'label'       => __( 'Kicker text (override)', 'lunara-film' ),
			'description' => __( 'Optional. Default is "Latest Review". Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_title', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_title', array(
			'label'       => __( 'Headline (override)', 'lunara-film' ),
			'description' => __( 'Optional. Default is the latest review\'s title. Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_excerpt', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_excerpt', array(
			'label'       => __( 'Excerpt line (override)', 'lunara-film' ),
			'description' => __( 'Optional 1-2 sentence dek. Default is auto-generated from the latest review.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'textarea',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_url', array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_url', array(
			'label'       => __( 'CTA URL (override)', 'lunara-film' ),
			'description' => __( 'Where the hero click-through goes. Default is the latest review permalink.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'url',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_cta', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_cta', array(
			'label'       => __( 'CTA button text (override)', 'lunara-film' ),
			'description' => __( 'Default is "Read the review". Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );
	}
	add_action( 'customize_register', 'lunara_register_cinematic_hero_customizer' );
}

if ( ! function_exists( 'lunara_get_cinematic_hero_data' ) ) {
	function lunara_get_cinematic_hero_data( $attrs = array() ) {
		// Three-tier priority: per-instance block attribute â†’ Customizer override â†’ auto from latest review.
		$attrs = is_array( $attrs ) ? $attrs : array();
		$attr_image_id   = isset( $attrs['overrideImageId'] ) ? (int) $attrs['overrideImageId'] : 0;
		$attr_kicker     = isset( $attrs['overrideKicker'] )  ? trim( (string) $attrs['overrideKicker'] )  : '';
		$attr_title      = isset( $attrs['overrideTitle'] )   ? trim( (string) $attrs['overrideTitle'] )   : '';
		$attr_excerpt    = isset( $attrs['overrideExcerpt'] ) ? trim( (string) $attrs['overrideExcerpt'] ) : '';
		$attr_url        = isset( $attrs['overrideUrl'] )     ? trim( (string) $attrs['overrideUrl'] )     : '';
		$attr_cta        = isset( $attrs['overrideCta'] )     ? trim( (string) $attrs['overrideCta'] )     : '';

		$override_image = trim( (string) get_theme_mod( 'lunara_hero_override_image', '' ) );
		$latest_review  = lunara_get_latest_review_post();

		// Image priority: block attribute â†’ Customizer URL â†’ latest review featured â†’ null.
		$image_url     = '';
		$attachment_id = 0;
		if ( $attr_image_id > 0 ) {
			$attachment_id = $attr_image_id;
			$image_url     = (string) wp_get_attachment_image_url( $attr_image_id, 'full' );
		} elseif ( '' !== $override_image ) {
			$image_url     = $override_image;
			$attachment_id = (int) attachment_url_to_postid( $override_image );
		} elseif ( $latest_review ) {
			// Reviews store their art in meta (TMDB imports), not as the WP
			// featured image — prefer the purpose-built hero banner, then the
			// TMDB backdrop, then the card/poster art; fall back to a real
			// featured image only if one is actually set.
			$review_id        = (int) $latest_review->ID;
			$image_candidates = array(
				get_post_meta( $review_id, '_lunara_review_hero_banner', true ),
				get_post_meta( $review_id, '_lunara_tmdb_backdrop_url', true ),
				get_post_meta( $review_id, '_lunara_review_card_image', true ),
				get_post_meta( $review_id, '_lunara_tmdb_poster_url', true ),
			);
			foreach ( $image_candidates as $candidate ) {
				$candidate = trim( (string) $candidate );
				if ( '' !== $candidate ) {
					$image_url     = $candidate;
					$attachment_id = (int) attachment_url_to_postid( $candidate );
					break;
				}
			}
			if ( '' === $image_url && has_post_thumbnail( $review_id ) ) {
				$attachment_id = (int) get_post_thumbnail_id( $review_id );
				$image_url     = (string) wp_get_attachment_image_url( $attachment_id, 'full' );
			}
		}

		if ( '' === $image_url ) {
			return null; // No image available â€” caller hides the section.
		}

		// Defaults from latest review, then override with Customizer if set.
		$kicker  = $latest_review ? __( 'Latest Review', 'lunara-film' ) : __( 'Lunara', 'lunara-film' );
		$title   = $latest_review ? get_the_title( $latest_review ) : get_bloginfo( 'name' );
		$excerpt = '';
		if ( $latest_review ) {
			$excerpt = trim( (string) get_the_excerpt( $latest_review ) );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( $latest_review->post_content ), 24, '…' );
			}
		}
		$url = $latest_review ? get_permalink( $latest_review ) : home_url( '/' );
		$cta = __( 'Read the review', 'lunara-film' );

		// Customizer overrides win when set.
		$override_kicker  = trim( (string) get_theme_mod( 'lunara_hero_override_kicker', '' ) );
		$override_title   = trim( (string) get_theme_mod( 'lunara_hero_override_title', '' ) );
		$override_excerpt = trim( (string) get_theme_mod( 'lunara_hero_override_excerpt', '' ) );
		$override_url     = trim( (string) get_theme_mod( 'lunara_hero_override_url', '' ) );
		$override_cta     = trim( (string) get_theme_mod( 'lunara_hero_override_cta', '' ) );

		if ( '' !== $override_kicker )  { $kicker  = $override_kicker; }
		if ( '' !== $override_title )   { $title   = $override_title; }
		if ( '' !== $override_excerpt ) { $excerpt = $override_excerpt; }
		if ( '' !== $override_url )     { $url     = $override_url; }
		if ( '' !== $override_cta )     { $cta     = $override_cta; }

		// Per-instance block attributes win over everything (highest priority).
		if ( '' !== $attr_kicker )  { $kicker  = $attr_kicker; }
		if ( '' !== $attr_title )   { $title   = $attr_title; }
		if ( '' !== $attr_excerpt ) { $excerpt = $attr_excerpt; }
		if ( '' !== $attr_url )     { $url     = $attr_url; }
		if ( '' !== $attr_cta )     { $cta     = $attr_cta; }

		return array(
			'image_url'     => $image_url,
			'attachment_id' => $attachment_id, // 0 if URL-only (no library record)
			'kicker'        => $kicker,
			'title'         => $title,
			'excerpt'       => $excerpt,
			'url'           => $url,
			'cta'           => $cta,
		);
	}
}

if ( ! function_exists( 'lunara_render_cinematic_hero' ) ) {
	function lunara_render_cinematic_hero( $attrs = array() ) {
		$data = lunara_get_cinematic_hero_data( is_array( $attrs ) ? $attrs : array() );
		if ( null === $data ) {
			return '';
		}

		// Build the <img> tag â€” use wp_get_attachment_image() if we have the
		// attachment ID (gives us native srcset/sizes for responsive loading),
		// otherwise fall back to a raw <img> with the URL.
		// loading="eager" + fetchpriority="high" tells the browser this is the
		// LCP element and to fetch it immediately (huge mobile perf win).
		$img_attrs = array(
			'class'         => 'lunara-cinematic-hero-img',
			'alt'           => '',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'sizes'         => '100vw',
		);

		if ( $data['attachment_id'] > 0 ) {
			$img_html = wp_get_attachment_image( $data['attachment_id'], 'full', false, $img_attrs );
		} else {
			$attr_string = '';
			foreach ( $img_attrs as $k => $v ) {
				$attr_string .= ' ' . $k . '="' . esc_attr( $v ) . '"';
			}
			$img_html = '<img src="' . esc_url( $data['image_url'] ) . '"' . $attr_string . ' />';
		}

		ob_start();
		?>
		<section class="lunara-home-hero lunara-cinematic-hero lunara-home-slot-hero" aria-label="<?php esc_attr_e( 'Featured', 'lunara-film' ); ?>">
			<a class="lunara-cinematic-hero-link" href="<?php echo esc_url( $data['url'] ); ?>" aria-label="<?php echo esc_attr( $data['title'] ); ?>">
				<div class="lunara-cinematic-hero-bg" aria-hidden="true">
					<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="lunara-cinematic-hero-overlay" aria-hidden="true"<?php echo function_exists( 'lunara_hero_overlay_style_attr' ) ? lunara_hero_overlay_style_attr( array() ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
				<div class="lunara-cinematic-hero-shell">
					<div class="lunara-cinematic-hero-content">
						<p class="lunara-cinematic-hero-kicker"><?php echo esc_html( $data['kicker'] ); ?></p>
						<h1 class="lunara-cinematic-hero-title"><?php echo esc_html( $data['title'] ); ?></h1>
						<?php if ( '' !== $data['excerpt'] ) : ?>
							<p class="lunara-cinematic-hero-excerpt"><?php echo esc_html( $data['excerpt'] ); ?></p>
						<?php endif; ?>
						<span class="lunara-cinematic-hero-cta"><?php echo esc_html( $data['cta'] ); ?> <span aria-hidden="true">&rarr;</span></span>
					</div>
				</div>
			</a>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_rightsize_backdrop_url' ) ) {
	function lunara_rightsize_backdrop_url( $url, $variant = 'w1280' ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return $url;
		}

		$crop = (string) apply_filters( 'lunara_hero_crop_dimensions', '1600,900' );

		// TMDB: downscale the full-resolution original to a crisp, light, uniform
		// 16:9 hero through Site Accelerator (Photon). Stretching the small w1280
		// across a full-bleed banner was the source of the softness; downscaling a
		// 3840px original to 1600x900 is sharp and ~7x lighter than the original.
		if ( preg_match( '#image\.tmdb\.org/t/p/(?:w\d+|original)/(.+)$#i', $url, $m ) ) {
			$file = (string) preg_replace( '/\?.*$/', '', ltrim( $m[1], '/' ) );
			return add_query_arg(
				array(
					'resize'  => $crop,
					'quality' => 86,
					'ssl'     => 1,
				),
				'https://i0.wp.com/image.tmdb.org/t/p/original/' . $file
			);
		}

		// Local / Jetpack-proxied uploads: request a uniform 16:9 hero crop via
		// Site Accelerator so heavy originals are downscaled for speed and every
		// hero is the same shape. (Photon never upscales, so undersized sources
		// are handled separately by the eligibility floor, not here.)
		if ( false !== strpos( $url, '/wp-content/uploads/' )
			&& false === strpos( $url, 'resize=' )
			&& false === strpos( $url, 'fit=' )
			&& false === stripos( $url, '?w=' ) ) {
			$url = add_query_arg(
				array(
					'resize'  => $crop,
					'quality' => 86,
					'ssl'     => 1,
				),
				$url
			);
		}

		return $url;
	}
}

if ( ! function_exists( 'lunara_hero_image_qualifies' ) ) {
	function lunara_hero_image_qualifies( $url, $min_width = 0 ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}

		if ( $min_width <= 0 ) {
			/**
			 * Filter the minimum width an image needs to qualify for the hero.
			 *
			 * @param int $min_width Default 1280.
			 */
			$min_width = (int) apply_filters( 'lunara_hero_min_width', 1280 );
		}

		// TMDB sized URLs: derive the width straight from the size token.
		if ( preg_match( '#image\.tmdb\.org/t/p/(w(\d+)|original)/#', $url, $m ) ) {
			if ( 'original' === $m[1] ) {
				return true; // TMDB originals are large landscape backdrops.
			}
			return (int) $m[2] >= $min_width;
		}

		// Local uploads: measure via attachment metadata when resolvable.
		$base = (string) preg_replace( '/\?.*$/', '', $url );
		if ( function_exists( 'attachment_url_to_postid' ) ) {
			$att_id = attachment_url_to_postid( $base );
			if ( $att_id ) {
				$meta = wp_get_attachment_metadata( $att_id );
				if ( is_array( $meta ) && ! empty( $meta['width'] ) ) {
					$w = (int) $meta['width'];
					$h = (int) ( $meta['height'] ?? 0 );
					return $w >= $min_width && $w >= $h; // Wide enough AND landscape.
				}
			}
		}

		// Unknown / unresolvable external image: don't prune (rare path).
		return true;
	}
}

if ( ! function_exists( 'lunara_hero_qualify_or_blank' ) ) {
	function lunara_hero_qualify_or_blank( $url ) {
		$url = trim( (string) $url );
		return ( '' !== $url && lunara_hero_image_qualifies( $url ) ) ? $url : '';
	}
}

if ( ! function_exists( 'lunara_get_review_hero_image_url' ) ) {
	function lunara_get_review_hero_image_url( $review_id ) {
		$review_id  = (int) $review_id;
		$candidates = array(
			get_post_meta( $review_id, '_lunara_review_hero_banner', true ),
			get_post_meta( $review_id, '_lunara_tmdb_backdrop_url', true ),
			get_post_meta( $review_id, '_lunara_review_card_image', true ),
			get_post_meta( $review_id, '_lunara_tmdb_poster_url', true ),
		);
		foreach ( $candidates as $candidate ) {
			$candidate = trim( (string) $candidate );
			if ( '' !== $candidate ) {
				return $candidate;
			}
		}
		if ( has_post_thumbnail( $review_id ) ) {
			return (string) wp_get_attachment_image_url( get_post_thumbnail_id( $review_id ), 'full' );
		}
		return '';
	}
}

if ( ! function_exists( 'lunara_get_cinematic_hero_slides' ) ) {
	function lunara_get_cinematic_hero_slides( $max = 6 ) {
		// Hero Command — when the command deck is enabled with at least one
		// renderable slide, the curated list IS the hero: exact slides, exact
		// order, no cap at $max. The automatic feed below stays the fallback.
		if ( function_exists( 'lunara_hero_command_slides' ) ) {
			$commanded = lunara_hero_command_slides();
			if ( ! empty( $commanded ) ) {
				return $commanded;
			}
		}

		$max   = max( 1, (int) $max ); $pool = $max + 6;
		$items = array();

		// Latest reviews.
		if ( function_exists( 'lunara_latest_reviews_query' ) ) {
			$reviews = lunara_latest_reviews_query( $pool );
			if ( $reviews instanceof WP_Query && ! empty( $reviews->posts ) ) {
				foreach ( $reviews->posts as $review ) {
					$image = lunara_hero_qualify_or_blank( lunara_get_review_hero_image_url( $review->ID ) );
					if ( ! $image ) {
						continue;
					}
					$excerpt = function_exists( 'lunara_get_review_card_pull_quote' )
						? lunara_get_review_card_pull_quote( $review->ID, 30, true )
						: wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt( $review ) ), 30, '…' );
					$items[] = array(
						'date'    => (string) $review->post_date,
						'kicker'  => __( 'Latest Review', 'lunara-film' ),
						'title'   => get_the_title( $review->ID ),
						'excerpt' => (string) $excerpt,
						'url'     => get_permalink( $review->ID ),
						'cta'     => __( 'Read the review', 'lunara-film' ),
						'image'   => lunara_rightsize_backdrop_url( $image ),
					);
				}
			}
		}

		// Latest journal entries.
		if ( function_exists( 'lunara_home_dispatches_query' ) ) {
			$journal = lunara_home_dispatches_query( $pool );
			if ( $journal instanceof WP_Query && ! empty( $journal->posts ) ) {
				foreach ( $journal->posts as $entry ) {
					$image = function_exists( 'lunara_get_journal_card_image_url' )
						? lunara_hero_qualify_or_blank( lunara_get_journal_card_image_url( $entry->ID, 'full' ) )
						: '';
					if ( ! $image ) {
						continue;
					}
					$excerpt = function_exists( 'lunara_card_excerpt' )
						? lunara_card_excerpt( $entry->ID, 28 )
						: wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt( $entry ) ), 28, '…' );
					$items[] = array(
						'date'    => (string) $entry->post_date,
						'kicker'  => __( 'From the Journal', 'lunara-film' ),
						'title'   => get_the_title( $entry->ID ),
						'excerpt' => (string) $excerpt,
						'url'     => get_permalink( $entry->ID ),
						'cta'     => __( 'Read the entry', 'lunara-film' ),
						'image'   => lunara_rightsize_backdrop_url( $image ),
					);
				}
			}
		}

		wp_reset_postdata();

		// Newest first.
		usort(
			$items,
			static function ( $a, $b ) {
				return strcmp( (string) $b['date'], (string) $a['date'] );
			}
		);

		// Homepage Hero curation — posts featured via the editor checkbox take
		// the front slides (most recently featured first); the automatic
		// newest-content pool fills whatever slots remain. Duplicates of a
		// featured post are removed from the pool so nothing appears twice.
		if ( function_exists( 'lunara_get_hero_featured_slides' ) ) {
			$featured_slides = lunara_get_hero_featured_slides( $max );
			if ( ! empty( $featured_slides ) ) {
				$featured_urls = array_map(
					static function ( $slide ) {
						return (string) $slide['url'];
					},
					$featured_slides
				);
				$items = array_values(
					array_filter(
						$items,
						static function ( $item ) use ( $featured_urls ) {
							return ! in_array( (string) $item['url'], $featured_urls, true );
						}
					)
				);
				$items = array_merge( $featured_slides, $items );
			}
		}

		// Curation v1 — Spotlight Campaign lead. When enabled with a valid
		// published post, that post takes the very first slide (overriding the
		// newest/featured lead). Any duplicate of the same post elsewhere in the
		// pool is removed so it doesn't appear twice.
		$spotlight_slide = function_exists( 'lunara_build_spotlight_hero_slide' )
			? lunara_build_spotlight_hero_slide()
			: null;

		if ( is_array( $spotlight_slide ) && ! empty( $spotlight_slide['image'] ) ) {
			$spotlight_url = (string) $spotlight_slide['url'];
			$items         = array_values(
				array_filter(
					$items,
					static function ( $item ) use ( $spotlight_url ) {
						return (string) $item['url'] !== $spotlight_url;
					}
				)
			);
			array_unshift( $items, $spotlight_slide );
		}

		return array_slice( $items, 0, $max );
	}
}

if ( ! function_exists( 'lunara_build_hero_slide_for_post' ) ) {
	/**
	 * Build a cinematic-hero slide for any public post (review, journal, post).
	 * Returns null when no usable wide hero image can be resolved.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Kicker text; '' falls back to a per-type default.
	 * @return array|null
	 */
	function lunara_build_hero_slide_for_post( $post_id, $label = '' ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
			return null;
		}

		$post_type = get_post_type( $post_id );

		// Resolve the best available image for this post type.
		$image = '';
		if ( 'review' === $post_type && function_exists( 'lunara_get_review_hero_image_url' ) ) {
			$image = lunara_get_review_hero_image_url( $post_id );
		} elseif ( function_exists( 'lunara_get_journal_card_image_url' ) ) {
			$image = lunara_get_journal_card_image_url( $post_id, 'full' );
		}

		if ( ! $image ) {
			$image = (string) get_the_post_thumbnail_url( $post_id, 'full' );
		}

		if ( function_exists( 'lunara_hero_qualify_or_blank' ) ) {
			$image = lunara_hero_qualify_or_blank( $image );
		}

		if ( ! $image ) {
			return null;
		}

		if ( function_exists( 'lunara_rightsize_backdrop_url' ) ) {
			$image = lunara_rightsize_backdrop_url( $image );
		}

		if ( '' === trim( (string) $label ) ) {
			$label = 'review' === $post_type
				? __( 'Featured Review', 'lunara-film' )
				: __( "Editor's Pick", 'lunara-film' );
		}

		// Excerpt: reuse existing card-excerpt helpers when available.
		if ( 'review' === $post_type && function_exists( 'lunara_get_review_card_pull_quote' ) ) {
			$excerpt = lunara_get_review_card_pull_quote( $post_id, 30, true );
		} elseif ( function_exists( 'lunara_card_excerpt' ) ) {
			$excerpt = lunara_card_excerpt( $post_id, 30 );
		} else {
			$excerpt = wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ), 30, '…' );
		}

		$cta = 'review' === $post_type
			? __( 'Read the review', 'lunara-film' )
			: __( 'Read the story', 'lunara-film' );

		return array(
			'date'    => (string) $post->post_date,
			'kicker'  => (string) $label,
			'title'   => get_the_title( $post_id ),
			'excerpt' => (string) $excerpt,
			'url'     => get_permalink( $post_id ),
			'cta'     => $cta,
			'image'   => $image,
		);
	}
}

if ( ! function_exists( 'lunara_hero_featured_post_types' ) ) {
	function lunara_hero_featured_post_types() {
		return apply_filters( 'lunara_hero_featured_post_types', array( 'review', 'journal', 'post' ) );
	}
}

if ( ! function_exists( 'lunara_add_hero_feature_meta_box' ) ) {
	function lunara_add_hero_feature_meta_box() {
		foreach ( lunara_hero_featured_post_types() as $hero_post_type ) {
			add_meta_box(
				'lunara_hero_feature_meta',
				__( 'Homepage Hero', 'lunara-film' ),
				'lunara_hero_feature_meta_callback',
				$hero_post_type,
				'side',
				'high'
			);
		}
	}
	add_action( 'add_meta_boxes', 'lunara_add_hero_feature_meta_box' );

	function lunara_hero_feature_meta_callback( $post ) {
		wp_nonce_field( 'lunara_hero_feature_nonce', 'lunara_hero_feature_nonce' );
		$featured = (bool) get_post_meta( $post->ID, '_lunara_hero_featured', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="lunara_hero_featured" value="1" <?php checked( $featured ); ?> />
				<strong><?php esc_html_e( 'Feature in the homepage hero carousel', 'lunara-film' ); ?></strong>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'Puts this at the front of the big rotating hero on the homepage. Feature several and the most recently featured leads. Needs a wide (landscape) image to qualify. Uncheck and update to release the slot.', 'lunara-film' ); ?></p>
		<?php
	}

	function lunara_save_hero_feature_meta( $post_id ) {
		if ( ! isset( $_POST['lunara_hero_feature_nonce'] ) ) return;
		if ( ! wp_verify_nonce( $_POST['lunara_hero_feature_nonce'], 'lunara_hero_feature_nonce' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( ! in_array( get_post_type( $post_id ), lunara_hero_featured_post_types(), true ) ) return;

		$was_featured = (bool) get_post_meta( $post_id, '_lunara_hero_featured', true );

		if ( ! empty( $_POST['lunara_hero_featured'] ) ) {
			// Keep the original feature time on a plain re-save so an older
			// feature isn't accidentally promoted over a newer one.
			if ( ! $was_featured ) {
				update_post_meta( $post_id, '_lunara_hero_featured', time() );
			}
		} elseif ( $was_featured ) {
			delete_post_meta( $post_id, '_lunara_hero_featured' );
		}
	}
	add_action( 'save_post', 'lunara_save_hero_feature_meta' );
}

if ( ! function_exists( 'lunara_get_hero_featured_slides' ) ) {
	function lunara_get_hero_featured_slides( $max = 6 ) {
		$ids = get_posts(
			array(
				'post_type'      => lunara_hero_featured_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $max ),
				'fields'         => 'ids',
				'meta_key'       => '_lunara_hero_featured',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$slides = array();
		foreach ( (array) $ids as $featured_id ) {
			$slide = lunara_build_hero_slide_for_post( (int) $featured_id );
			if ( is_array( $slide ) && ! empty( $slide['image'] ) ) {
				$slides[] = $slide;
			}
		}

		return $slides;
	}
}

if ( ! function_exists( 'lunara_render_cinematic_hero_slide' ) ) {
	function lunara_render_cinematic_hero_slide( $data, $index = 0 ) {
		$is_first     = ( 0 === (int) $index );
		$image_markup = $is_first
			? '<img src="' . esc_url( $data['image'] ) . '" class="lunara-cinematic-hero-img" alt="" loading="eager" decoding="async" fetchpriority="high" sizes="100vw" />'
			: '<img src="' . esc_url( $data['image'] ) . '" class="lunara-cinematic-hero-img" alt="" decoding="async" fetchpriority="low" sizes="100vw" />';

		ob_start();
		?>
		<li class="splide__slide lunara-cinematic-hero-slide<?php echo $is_first ? ' is-active' : ''; ?>">
			<a class="lunara-cinematic-hero-link" href="<?php echo esc_url( $data['url'] ); ?>" aria-label="<?php echo esc_attr( $data['title'] ); ?>">
				<div class="lunara-cinematic-hero-bg" aria-hidden="true">
					<?php echo $image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="lunara-cinematic-hero-overlay" aria-hidden="true"<?php echo function_exists( 'lunara_hero_overlay_style_attr' ) ? lunara_hero_overlay_style_attr( $data ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
				<div class="lunara-cinematic-hero-shell">
					<div class="lunara-cinematic-hero-content">
						<p class="lunara-cinematic-hero-kicker"><?php echo esc_html( $data['kicker'] ); ?></p>
						<h2 class="lunara-cinematic-hero-title"><?php echo esc_html( $data['title'] ); ?></h2>
						<?php if ( '' !== trim( (string) $data['excerpt'] ) ) : ?>
							<p class="lunara-cinematic-hero-excerpt"><?php echo esc_html( $data['excerpt'] ); ?></p>
						<?php endif; ?>
						<span class="lunara-cinematic-hero-cta"><?php echo esc_html( $data['cta'] ); ?> <span aria-hidden="true">&rarr;</span></span>
					</div>
				</div>
			</a>
		</li>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_render_cinematic_hero_carousel' ) ) {
	function lunara_render_cinematic_hero_carousel( $attrs = array() ) {
		$slides = lunara_get_cinematic_hero_slides( 6 );

		// A single curated Hero Command slide still renders through the
		// carousel shell — the hero JS detects one slide and stays static, so
		// the deck's slide (not the auto-latest) is what screens.
		$command_live = function_exists( 'lunara_hero_command_slides' )
			&& count( lunara_hero_command_slides() ) > 0;

		if ( count( $slides ) < 1 || ( count( $slides ) < 2 && ! $command_live ) ) {
			return function_exists( 'lunara_render_cinematic_hero' )
				? lunara_render_cinematic_hero( is_array( $attrs ) ? $attrs : array() )
				: '';
		}

		$interval = (int) apply_filters( 'lunara_hero_autoplay_interval', 6500 );

		ob_start();
		?>
		<section class="lunara-home-hero lunara-home-slot-hero lunara-cinematic-hero lunara-cinematic-hero-carousel splide" data-lunara-hero-autoplay="<?php echo esc_attr( (string) $interval ); ?>" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured', 'lunara-film' ); ?>">
			<div class="splide__track lunara-cinematic-hero-track">
				<ul class="splide__list">
					<?php
					foreach ( $slides as $slide_index => $slide_data ) {
						echo lunara_render_cinematic_hero_slide( $slide_data, $slide_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</ul>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
