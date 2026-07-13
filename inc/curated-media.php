<?php
/**
 * Curated Media — one fully customizable image block that renders as a
 * gallery, a slider, or a carousel.
 *
 * The image sibling of the curated grid blocks (inc/curated-grids.php): the
 * whole editorial decision — which images, in what order, featured where, and
 * how they are presented — lives in block attributes, so any page can carry
 * its own image showcase and change it on a whim.
 *
 *   - lunara/media-showcase → lunara_render_media_showcase()
 *
 * What "fully customizable" means here:
 *   - Source: a hand-picked, reorderable list of media-library images. Slot 1
 *     is the featured / lead / first slide. Add, reorder, feature, or drop an
 *     image from the sidebar — the block follows the list.
 *   - Presentation: switch the SAME picked set between three displays without
 *     rebuilding anything —
 *       • gallery  → responsive grid (2–5 column dial, optional feature-first
 *                    mosaic where slot 1 spans large),
 *       • slider   → horizontal scroll-snap rail with peek + optional arrows,
 *       • carousel → one-at-a-time fading slides reusing the theme's existing
 *                    .lunara-carousel contract (autoplay, dots, swipe, keys).
 *   - Frame dials: aspect ratio, gap, rounded corners, per-image caption,
 *     and where each image links (nowhere / the file / its attachment page).
 *   - Section header: per-instance kicker/heading/CTA, or no header at all.
 *
 * The carousel display emits the exact markup + classes the theme's
 * lunara-carousel.js already drives, so it inherits autoplay, pause-on-hover,
 * swipe, keyboard nav, and reduced-motion handling for free.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_media_showcase_attribute_schema' ) ) {
	/**
	 * Attribute schema for the media showcase block. Every presentation dial
	 * is a block attribute so the whole showcase is per-instance.
	 *
	 * @return array
	 */
	function lunara_media_showcase_attribute_schema() {
		return array(
			'ids'           => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'number' ) ),
			'display'       => array( 'type' => 'string', 'default' => 'gallery' ),
			'columns'       => array( 'type' => 'number', 'default' => 3 ),
			'featureFirst'  => array( 'type' => 'boolean', 'default' => false ),
			'aspectRatio'   => array( 'type' => 'string', 'default' => 'landscape' ),
			'gap'           => array( 'type' => 'string', 'default' => 'normal' ),
			'rounded'       => array( 'type' => 'boolean', 'default' => true ),
			'linkTo'        => array( 'type' => 'string', 'default' => 'none' ),
			'showCaptions'  => array( 'type' => 'boolean', 'default' => false ),
			'autoplay'      => array( 'type' => 'boolean', 'default' => true ),
			'autoplaySpeed' => array( 'type' => 'number', 'default' => 5000 ),
			'showArrows'    => array( 'type' => 'boolean', 'default' => true ),
			'showDots'      => array( 'type' => 'boolean', 'default' => true ),
			'showHeader'    => array( 'type' => 'boolean', 'default' => false ),
			'heading'       => array( 'type' => 'string', 'default' => '' ),
			'kicker'        => array( 'type' => 'string', 'default' => '' ),
			'ctaLabel'      => array( 'type' => 'string', 'default' => '' ),
			'ctaUrl'        => array( 'type' => 'string', 'default' => '' ),
		);
	}
}

if ( ! function_exists( 'lunara_media_showcase_sanitize_ids' ) ) {
	/**
	 * Sanitize a picked list into ordered image-attachment IDs, preserving the
	 * editor's slot order and dropping anything that is not a real image.
	 *
	 * @param mixed $ids Raw attribute value.
	 * @return int[]
	 */
	function lunara_media_showcase_sanitize_ids( $ids ) {
		$clean = array();
		foreach ( (array) $ids as $raw ) {
			$id = absint( $raw );
			if ( $id <= 0 || in_array( $id, $clean, true ) ) {
				continue;
			}
			$post = get_post( $id );
			if ( ! ( $post instanceof WP_Post ) || 'attachment' !== $post->post_type ) {
				continue;
			}
			if ( 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) {
				continue;
			}
			$clean[] = $id;
		}
		return $clean;
	}
}

if ( ! function_exists( 'lunara_media_showcase_ratio_value' ) ) {
	/**
	 * Map an aspect-ratio slug to a CSS aspect-ratio value ('' = natural).
	 *
	 * @param string $slug Aspect ratio slug.
	 * @return string
	 */
	function lunara_media_showcase_ratio_value( $slug ) {
		$map = array(
			'auto'      => '',
			'square'    => '1 / 1',
			'portrait'  => '3 / 4',
			'landscape' => '4 / 3',
			'wide'      => '16 / 9',
			'cinema'    => '21 / 9',
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : $map['landscape'];
	}
}

if ( ! function_exists( 'lunara_media_showcase_gap_value' ) ) {
	/**
	 * Map a gap slug to a CSS length.
	 *
	 * @param string $slug Gap slug.
	 * @return string
	 */
	function lunara_media_showcase_gap_value( $slug ) {
		$map = array(
			'tight'  => '8px',
			'normal' => '20px',
			'roomy'  => '36px',
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : $map['normal'];
	}
}

if ( ! function_exists( 'lunara_media_showcase_link_href' ) ) {
	/**
	 * Resolve the link target for one image, per the linkTo dial.
	 *
	 * @param int    $id      Attachment ID.
	 * @param string $link_to none|media|attachment.
	 * @return string Empty string when the image should not link anywhere.
	 */
	function lunara_media_showcase_link_href( $id, $link_to ) {
		if ( 'media' === $link_to ) {
			return (string) wp_get_attachment_image_url( $id, 'full' );
		}
		if ( 'attachment' === $link_to ) {
			return (string) get_attachment_link( $id );
		}
		return '';
	}
}

if ( ! function_exists( 'lunara_media_showcase_figure' ) ) {
	/**
	 * One gallery / slider figure. Reuses wp_get_attachment_image so alt text,
	 * srcset, and sizes come straight from the media library.
	 *
	 * @param int   $id    Attachment ID.
	 * @param array $opts  { item_class, size, link_to, show_caption }.
	 * @return string
	 */
	function lunara_media_showcase_figure( $id, $opts ) {
		$item_class   = isset( $opts['item_class'] ) ? (string) $opts['item_class'] : 'lunara-media-item';
		$size         = isset( $opts['size'] ) ? $opts['size'] : 'large';
		$link_to      = isset( $opts['link_to'] ) ? (string) $opts['link_to'] : 'none';
		$show_caption = ! empty( $opts['show_caption'] );

		$img = wp_get_attachment_image(
			$id,
			$size,
			false,
			array(
				'class'    => 'lunara-media-image',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( '' === $img ) {
			$url = wp_get_attachment_image_url( $id, 'large' );
			if ( '' === (string) $url ) {
				return '';
			}
			$img = sprintf(
				'<img class="lunara-media-image" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_url( $url ),
				esc_attr( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) )
			);
		}

		$href = lunara_media_showcase_link_href( $id, $link_to );
		if ( '' !== $href ) {
			$img = sprintf( '<a class="lunara-media-link" href="%s">%s</a>', esc_url( $href ), $img );
		}

		$caption_html = '';
		if ( $show_caption ) {
			$caption = wp_get_attachment_caption( $id );
			if ( '' !== (string) $caption ) {
				$caption_html = sprintf( '<figcaption class="lunara-media-caption">%s</figcaption>', esc_html( $caption ) );
			}
		}

		return sprintf(
			'<figure class="%s">%s%s</figure>',
			esc_attr( $item_class ),
			$img,
			$caption_html
		);
	}
}

if ( ! function_exists( 'lunara_render_media_showcase' ) ) {
	/**
	 * Render callback for lunara/media-showcase.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	function lunara_render_media_showcase( $attrs = array() ) {
		$attrs = is_array( $attrs ) ? $attrs : array();

		$ids = lunara_media_showcase_sanitize_ids( isset( $attrs['ids'] ) ? $attrs['ids'] : array() );
		if ( empty( $ids ) ) {
			return '';
		}

		$display = isset( $attrs['display'] ) ? sanitize_key( $attrs['display'] ) : 'gallery';
		if ( ! in_array( $display, array( 'gallery', 'slider', 'carousel' ), true ) ) {
			$display = 'gallery';
		}

		$columns       = isset( $attrs['columns'] ) ? max( 2, min( 5, (int) $attrs['columns'] ) ) : 3;
		$feature_first = ! empty( $attrs['featureFirst'] );
		$ratio         = lunara_media_showcase_ratio_value( isset( $attrs['aspectRatio'] ) ? sanitize_key( $attrs['aspectRatio'] ) : 'landscape' );
		$gap           = lunara_media_showcase_gap_value( isset( $attrs['gap'] ) ? sanitize_key( $attrs['gap'] ) : 'normal' );
		$rounded       = ! isset( $attrs['rounded'] ) || (bool) $attrs['rounded'];
		$link_to       = isset( $attrs['linkTo'] ) ? sanitize_key( $attrs['linkTo'] ) : 'none';
		if ( ! in_array( $link_to, array( 'none', 'media', 'attachment' ), true ) ) {
			$link_to = 'none';
		}
		$show_caption = ! empty( $attrs['showCaptions'] );

		$head = '';
		if ( ! empty( $attrs['showHeader'] ) && function_exists( 'lunara_curated_grid_section_head' ) ) {
			$head = lunara_curated_grid_section_head(
				$attrs,
				array(
					'heading'  => __( 'Gallery', 'lunara-film' ),
					'kicker'   => '',
					'ctaLabel' => '',
					'ctaUrl'   => '',
				)
			);
		}

		$style_vars = sprintf(
			'--lunara-media-cols: %d; --lunara-media-gap: %s;%s',
			$columns,
			$gap,
			'' !== $ratio ? ' --lunara-media-ratio: ' . $ratio . ';' : ''
		);

		$root_classes = 'lunara-media-showcase lunara-media-display-' . $display;
		$root_classes .= '' !== $ratio ? ' has-ratio' : ' is-ratio-auto';
		$root_classes .= $rounded ? ' is-rounded' : '';

		ob_start();
		?>
		<section class="lunara-home-section lunara-media-showcase-block <?php echo esc_attr( $root_classes ); ?>" style="<?php echo esc_attr( $style_vars ); ?>" data-media-display="<?php echo esc_attr( $display ); ?>" aria-label="<?php esc_attr_e( 'Image showcase', 'lunara-film' ); ?>">
			<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			if ( 'carousel' === $display ) {
				echo lunara_media_showcase_render_carousel( $ids, $attrs, $ratio, $link_to, $show_caption ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo lunara_media_showcase_render_rail_or_grid( $ids, $display, $feature_first, $link_to, $show_caption, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_media_showcase_render_rail_or_grid' ) ) {
	/**
	 * Gallery grid or slider rail. Both are figure lists; the display class and
	 * CSS drive the difference, so switching one attribute reflows the set.
	 *
	 * @param int[]  $ids           Ordered attachment IDs.
	 * @param string $display       gallery|slider.
	 * @param bool   $feature_first Gallery: first image spans large.
	 * @param string $link_to       Link dial.
	 * @param bool   $show_caption  Whether to print captions.
	 * @param array  $attrs         Block attributes (for slider arrows).
	 * @return string
	 */
	function lunara_media_showcase_render_rail_or_grid( $ids, $display, $feature_first, $link_to, $show_caption, $attrs ) {
		$is_slider   = 'slider' === $display;
		$item_base   = $is_slider ? 'lunara-media-slide' : 'lunara-media-item';
		$track_class = $is_slider ? 'lunara-media-slider-track' : 'lunara-media-gallery-grid';
		$show_arrows = $is_slider && ( ! isset( $attrs['showArrows'] ) || (bool) $attrs['showArrows'] ) && count( $ids ) > 1;

		if ( $is_slider && $show_arrows ) {
			lunara_media_showcase_enqueue_runtime();
		}

		ob_start();
		if ( $is_slider ) {
			echo '<div class="lunara-media-slider" data-lunara-media-slider>';
			if ( $show_arrows ) {
				printf(
					'<button type="button" class="lunara-media-arrow lunara-media-arrow-prev" data-dir="-1" aria-label="%s">&#8249;</button>',
					esc_attr__( 'Previous', 'lunara-film' )
				);
			}
		}
		?>
		<div class="<?php echo esc_attr( $track_class ); ?>">
			<?php
			foreach ( $ids as $index => $id ) :
				$item_class = $item_base;
				if ( ! $is_slider && $feature_first && 0 === $index ) {
					$item_class .= ' is-feature';
				}
				echo lunara_media_showcase_figure( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$id,
					array(
						'item_class'   => $item_class,
						'size'         => ( ! $is_slider && $feature_first && 0 === $index ) ? 'large' : 'medium_large',
						'link_to'      => $link_to,
						'show_caption' => $show_caption,
					)
				);
			endforeach;
			?>
		</div>
		<?php
		if ( $is_slider ) {
			if ( $show_arrows ) {
				printf(
					'<button type="button" class="lunara-media-arrow lunara-media-arrow-next" data-dir="1" aria-label="%s">&#8250;</button>',
					esc_attr__( 'Next', 'lunara-film' )
				);
			}
			echo '</div>';
		}
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_media_showcase_render_carousel' ) ) {
	/**
	 * Carousel display — emits the theme's existing .lunara-carousel contract
	 * so lunara-carousel.js drives autoplay, dots, swipe, and keyboard nav.
	 *
	 * @param int[]  $ids          Ordered attachment IDs.
	 * @param array  $attrs        Block attributes.
	 * @param string $ratio        CSS aspect ratio ('' = natural).
	 * @param string $link_to      Link dial.
	 * @param bool   $show_caption Whether to print captions.
	 * @return string
	 */
	function lunara_media_showcase_render_carousel( $ids, $attrs, $ratio, $link_to, $show_caption ) {
		lunara_media_showcase_enqueue_carousel_runtime();

		$autoplay = ! isset( $attrs['autoplay'] ) || (bool) $attrs['autoplay'];
		$speed    = isset( $attrs['autoplaySpeed'] ) ? max( 1500, min( 20000, (int) $attrs['autoplaySpeed'] ) ) : 5000;
		$show_dots = ( ! isset( $attrs['showDots'] ) || (bool) $attrs['showDots'] ) && count( $ids ) > 1;

		ob_start();
		?>
		<div class="lunara-carousel lunara-media-carousel<?php echo '' !== $ratio ? ' has-ratio' : ''; ?>" data-autoplay="<?php echo esc_attr( (string) ( $autoplay ? $speed : 0 ) ); ?>">
			<?php foreach ( $ids as $index => $id ) :
				$img_url = wp_get_attachment_image_url( $id, 'full' );
				if ( '' === (string) $img_url ) {
					continue;
				}
				$alt     = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
				$caption = $show_caption ? (string) wp_get_attachment_caption( $id ) : '';
				$href    = lunara_media_showcase_link_href( $id, $link_to );
				?>
				<div class="lunara-carousel-slide lunara-media-carousel-slide <?php echo 0 === $index ? 'active' : ''; ?>" style="background-image: url('<?php echo esc_url( $img_url ); ?>');" role="img" aria-label="<?php echo esc_attr( '' !== $alt ? $alt : get_the_title( $id ) ); ?>">
					<?php if ( '' !== $caption ) : ?>
						<div class="lunara-carousel-overlay">
							<?php if ( '' !== $href ) : ?>
								<a href="<?php echo esc_url( $href ); ?>" class="lunara-carousel-link">
							<?php endif; ?>
							<p class="lunara-carousel-subtitle"><?php echo esc_html( $caption ); ?></p>
							<?php if ( '' !== $href ) : ?>
								</a>
							<?php endif; ?>
						</div>
					<?php elseif ( '' !== $href ) : ?>
						<a href="<?php echo esc_url( $href ); ?>" class="lunara-carousel-link lunara-media-carousel-cover" aria-hidden="true" tabindex="-1"></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if ( $show_dots ) : ?>
				<div class="lunara-carousel-dots">
					<?php foreach ( $ids as $index => $id ) : ?>
						<button class="lunara-carousel-dot <?php echo 0 === $index ? 'active' : ''; ?>" data-slide="<?php echo (int) $index; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'lunara-film' ), $index + 1 ) ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_media_showcase_enqueue_carousel_runtime' ) ) {
	/**
	 * Enqueue the shared dependency-free carousel driver on demand.
	 */
	function lunara_media_showcase_enqueue_carousel_runtime() {
		if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
			return;
		}
		$asset = lunara_resolve_theme_asset( 'assets/js/lunara-carousel.js', array( 'lunara-carousel.js' ) );
		if ( ! empty( $asset['path'] ) ) {
			wp_enqueue_script(
				'lunara-carousel',
				$asset['uri'],
				array(),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
				true
			);
		}
	}
}

if ( ! function_exists( 'lunara_media_showcase_enqueue_runtime' ) ) {
	/**
	 * Enqueue the tiny slider-arrow runtime on demand.
	 */
	function lunara_media_showcase_enqueue_runtime() {
		if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
			return;
		}
		$asset = lunara_resolve_theme_asset( 'assets/js/lunara-media-showcase.js', array( 'lunara-media-showcase.js' ) );
		if ( ! empty( $asset['path'] ) ) {
			wp_enqueue_script(
				'lunara-media-showcase',
				$asset['uri'],
				array(),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
				true
			);
		}
	}
}

if ( ! function_exists( 'lunara_register_media_showcase_block' ) ) {
	function lunara_register_media_showcase_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset = function_exists( 'lunara_resolve_theme_asset' )
			? lunara_resolve_theme_asset( 'assets/js/lunara-curated-media.js' )
			: array();

		if ( ! empty( $asset['path'] ) ) {
			wp_register_script(
				'lunara-curated-media',
				$asset['uri'],
				array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-api-fetch', 'wp-url' ),
				function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
				true
			);
		}

		register_block_type(
			'lunara/media-showcase',
			array(
				'api_version'     => 3,
				'category'        => 'lunara',
				'editor_script'   => 'lunara-curated-media',
				'title'           => __( 'Lunara Media Showcase — Gallery / Slider / Carousel', 'lunara-film' ),
				'icon'            => 'format-gallery',
				'description'     => __( 'A fully customizable image showcase: hand-pick and reorder images from the media library (slot 1 is featured), then present the same set as a responsive gallery, a horizontal slider, or an autoplay carousel. Aspect ratio, columns, gap, captions, links, and section header are all per-instance.', 'lunara-film' ),
				'attributes'      => lunara_media_showcase_attribute_schema(),
				'supports'        => array(
					'html'     => false,
					'anchor'   => true,
					'align'    => array( 'wide', 'full' ),
					'multiple' => true,
					'inserter' => true,
				),
				'render_callback' => 'lunara_render_media_showcase',
			)
		);
	}
	add_action( 'init', 'lunara_register_media_showcase_block', 100 );
}
