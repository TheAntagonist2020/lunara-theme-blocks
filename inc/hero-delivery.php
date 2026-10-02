<?php
/**
 * Homepage cinematic hero image delivery.
 *
 * Attachment-backed slides are rendered once through WordPress's responsive
 * image pipeline. The final filtered markup is then parsed and shared by the
 * visible hero and its HTML preload, preventing WordPress.com/Jetpack from
 * selecting a different LCP request than the one the theme announced.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_hero_empty_image_descriptor' ) ) {
	/**
	 * Return the stable descriptor shape used by hero rendering and hints.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_hero_empty_image_descriptor() {
		return array(
			'html'          => '',
			'src'           => '',
			'srcset'        => '',
			'sizes'         => '',
			'width'         => 0,
			'height'        => 0,
			'attachment_id' => 0,
		);
	}
}

if ( ! function_exists( 'lunara_hero_attachment_id_from_url' ) ) {
	/**
	 * Resolve a local upload URL, including its i0.wp.com proxy form, to media.
	 *
	 * @param string $url Image URL.
	 * @return int
	 */
	function lunara_hero_attachment_id_from_url( $url ) {
		static $cache = array();

		$url = trim( html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' ) );
		if ( '' === $url || ! function_exists( 'attachment_url_to_postid' ) ) {
			return 0;
		}

		$lookup_url = (string) preg_replace( '/[?#].*$/', '', $url );
		$parts     = parse_url( $lookup_url );
		$host      = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
		$path      = isset( $parts['path'] ) ? ltrim( (string) $parts['path'], '/' ) : '';
		$site_host = function_exists( 'home_url' ) ? strtolower( (string) parse_url( home_url( '/' ), PHP_URL_HOST ) ) : '';
		$site_host = (string) preg_replace( '/^www\./', '', $site_host );
		$local_url = '';

		if ( (string) preg_replace( '/^www\./', '', $host ) === $site_host && 0 === strpos( $path, 'wp-content/uploads/' ) ) {
			$local_url = home_url( '/' . $path );
		} elseif ( '' === $host && 0 === strpos( $path, 'wp-content/uploads/' ) ) {
			$local_url = home_url( '/' . $path );
		}

		if ( '' === $local_url && preg_match( '/^i\d+\.wp\.com$/', $host ) && '' !== $path && '' !== $site_host ) {
			$slash      = strpos( $path, '/' );
			$proxy_host = false === $slash ? $path : substr( $path, 0, $slash );
			$proxy_path = false === $slash ? '' : substr( $path, $slash + 1 );

			$proxy_host = (string) preg_replace( '/^www\./', '', strtolower( $proxy_host ) );
			if ( $proxy_host === $site_host && 0 === strpos( $proxy_path, 'wp-content/uploads/' ) ) {
				$local_url = home_url( '/' . $proxy_path );
			}
		}

		// TMDB and every other external host are URL-only sources. Avoid a
		// database lookup for each automatic-feed candidate.
		if ( '' === $local_url ) {
			return 0;
		}

		if ( array_key_exists( $local_url, $cache ) ) {
			return $cache[ $local_url ];
		}

		$cache[ $local_url ] = max( 0, (int) attachment_url_to_postid( $local_url ) );
		return $cache[ $local_url ];
	}
}

if ( ! function_exists( 'lunara_hero_image_presentation' ) ) {
	/**
	 * Preserve the existing focal point, zoom and fit geometry.
	 *
	 * @param array<string,mixed> $data Slide or static hero data.
	 * @return array{class:string,style:string,fit:string}
	 */
	function lunara_hero_image_presentation( $data ) {
		$data         = is_array( $data ) ? $data : array();
		$focal_x      = max( 0, min( 100, isset( $data['focal_x'] ) ? (int) $data['focal_x'] : 50 ) );
		$focal_y      = max( 0, min( 100, isset( $data['focal_y'] ) ? (int) $data['focal_y'] : 30 ) );
		$zoom_percent = max( 100, min( 112, isset( $data['zoom'] ) ? (int) $data['zoom'] : 100 ) );
		$zoom_start   = $zoom_percent / 100;
		$zoom_end     = min( 1.17, $zoom_start + 0.05 );
		$fit          = isset( $data['fit'] ) && 'full' === (string) $data['fit'] ? 'full' : 'cover';

		return array(
			'class' => 'lunara-cinematic-hero-img' . ( 'full' === $fit ? ' is-full-frame' : '' ),
			'style' => sprintf(
				'--lunara-hero-focal-x:%d%%;--lunara-hero-focal-y:%d%%;--lunara-hero-zoom-start:%.2F;--lunara-hero-zoom-end:%.2F;',
				$focal_x,
				$focal_y,
				$zoom_start,
				$zoom_end
			),
			'fit'   => $fit,
		);
	}
}

if ( ! function_exists( 'lunara_hero_parse_final_image_markup' ) ) {
	/**
	 * Read the attributes that survived every WordPress image-markup filter.
	 *
	 * @param string $html Final wp_get_attachment_image() markup.
	 * @return array<string,string>
	 */
	function lunara_hero_parse_final_image_markup( $html ) {
		$attributes = array();
		$names      = array( 'src', 'srcset', 'sizes', 'width', 'height' );

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( (string) $html );
			if ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
				foreach ( $names as $name ) {
					$value = $processor->get_attribute( $name );
					if ( is_string( $value ) || is_numeric( $value ) ) {
						$attributes[ $name ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
					}
				}
				return $attributes;
			}
		}

		foreach ( $names as $name ) {
			if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*(["\'])(.*?)\1/is', (string) $html, $match ) ) {
				$attributes[ $name ] = html_entity_decode( (string) $match[2], ENT_QUOTES, 'UTF-8' );
			}
		}

		return $attributes;
	}
}

if ( ! function_exists( 'lunara_hero_url_dimensions' ) ) {
	/**
	 * Read honest dimensions only from an explicit resize/fit request.
	 *
	 * @param string $url Image URL.
	 * @return array{width:int,height:int}
	 */
	function lunara_hero_url_dimensions( $url ) {
		$url = html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' );
		if ( preg_match( '/(?:[?&](?:resize|fit)=)(\d+)(?:,|%2C)(\d+)/i', $url, $match ) ) {
			return array(
				'width'  => (int) $match[1],
				'height' => (int) $match[2],
			);
		}

		return array( 'width' => 0, 'height' => 0 );
	}
}

if ( ! function_exists( 'lunara_build_cinematic_hero_image_descriptor' ) ) {
	/**
	 * Build the final image markup and resource-selection descriptor once.
	 *
	 * @param array<string,mixed> $data        Slide or static hero data.
	 * @param bool                $is_priority Whether this is the actual LCP.
	 * @return array<string,mixed>
	 */
	function lunara_build_cinematic_hero_image_descriptor( $data, $is_priority = true ) {
		static $cache = array();

		$data          = is_array( $data ) ? $data : array();
		$image         = isset( $data['image'] ) ? trim( (string) $data['image'] ) : '';
		$image         = '' !== $image ? $image : ( isset( $data['image_url'] ) ? trim( (string) $data['image_url'] ) : '' );
		$attachment_id = isset( $data['attachment_id'] ) ? max( 0, (int) $data['attachment_id'] ) : 0;
		$attachment_id = $attachment_id > 0 ? $attachment_id : lunara_hero_attachment_id_from_url( $image );
		$presentation  = lunara_hero_image_presentation( $data );
		$cache_key     = md5(
			serialize(
				array(
					'image'         => $image,
					'attachment_id' => $attachment_id,
					'class'         => $presentation['class'],
					'style'         => $presentation['style'],
					'priority'      => (bool) $is_priority,
				)
			)
		);

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$descriptor = lunara_hero_empty_image_descriptor();
		$attributes = array(
			'class'         => $presentation['class'],
			'style'         => $presentation['style'],
			'alt'           => '',
			'loading'       => $is_priority ? 'eager' : 'lazy',
			'decoding'      => 'async',
			'fetchpriority' => $is_priority ? 'high' : 'low',
			'sizes'         => '100vw',
		);

		if ( $is_priority ) {
			$attributes['class']          .= ' skip-lazy no-lazy';
			$attributes['data-no-lazy']   = '1';
			$attributes['data-skip-lazy'] = '1';
		}

		if ( $attachment_id > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			// Keep the attachment uncropped. The native srcset still lets the
			// browser choose a right-sized candidate, while Full Frame and the
			// existing focal-position controls retain every source pixel.
			$html = (string) wp_get_attachment_image( $attachment_id, 'full', false, $attributes );
			if ( '' !== $html ) {
				$final                       = lunara_hero_parse_final_image_markup( $html );
				$descriptor['html']          = $html;
				$descriptor['src']           = isset( $final['src'] ) ? (string) $final['src'] : '';
				$descriptor['srcset']        = isset( $final['srcset'] ) ? trim( (string) $final['srcset'] ) : '';
				$descriptor['sizes']         = isset( $final['sizes'] ) ? trim( (string) $final['sizes'] ) : '';
				$descriptor['width']         = isset( $final['width'] ) ? max( 0, (int) $final['width'] ) : 0;
				$descriptor['height']        = isset( $final['height'] ) ? max( 0, (int) $final['height'] ) : 0;
				$descriptor['attachment_id'] = $attachment_id;
				$cache[ $cache_key ]         = $descriptor;
				return $descriptor;
			}
		}

		if ( '' === $image ) {
			$cache[ $cache_key ] = $descriptor;
			return $descriptor;
		}

		$dimensions = lunara_hero_url_dimensions( $image );
		$html_attrs = $attributes;
		if ( $dimensions['width'] > 0 && $dimensions['height'] > 0 ) {
			$html_attrs['width']  = $dimensions['width'];
			$html_attrs['height'] = $dimensions['height'];
		}

		$html = '<img src="' . esc_url( $image ) . '"';
		foreach ( $html_attrs as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		$html .= ' />';

		$descriptor['html']   = $html;
		$descriptor['src']    = esc_url_raw( $image );
		$descriptor['sizes']  = '100vw';
		$descriptor['width']  = $dimensions['width'];
		$descriptor['height'] = $dimensions['height'];
		$cache[ $cache_key ]  = $descriptor;

		return $descriptor;
	}
}

if ( ! function_exists( 'lunara_render_cinematic_hero_image' ) ) {
	/**
	 * Return the cached final image markup for a hero surface.
	 *
	 * @param array<string,mixed> $data        Slide or static hero data.
	 * @param bool                $is_priority Whether this image is the LCP.
	 * @return string
	 */
	function lunara_render_cinematic_hero_image( $data, $is_priority = true ) {
		$descriptor = lunara_build_cinematic_hero_image_descriptor( $data, $is_priority );
		return (string) $descriptor['html'];
	}
}

if ( ! function_exists( 'lunara_resolve_home_cinematic_hero_lcp_data' ) ) {
	/**
	 * Resolve the exact data branch used by the native front-door renderer.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_resolve_home_cinematic_hero_lcp_data() {
		$slides       = function_exists( 'lunara_get_home_cinematic_hero_slides' ) ? lunara_get_home_cinematic_hero_slides() : array();
		$slides       = is_array( $slides ) ? $slides : array();
		$command_live = function_exists( 'lunara_hero_command_slides' ) && count( (array) lunara_hero_command_slides() ) > 0;

		if ( count( $slides ) < 1 || ( count( $slides ) < 2 && ! $command_live ) ) {
			$static = function_exists( 'lunara_get_cinematic_hero_data' ) ? lunara_get_cinematic_hero_data() : null;
			if ( ! is_array( $static ) ) {
				return array();
			}
			$static['image'] = isset( $static['image_url'] ) ? (string) $static['image_url'] : ( isset( $static['image'] ) ? (string) $static['image'] : '' );
			return $static;
		}

		return isset( $slides[0] ) && is_array( $slides[0] ) ? $slides[0] : array();
	}
}

if ( ! function_exists( 'lunara_home_cinematic_hero_preload_is_allowed' ) ) {
	/**
	 * Check route and ownership before predicting a native hero image.
	 *
	 * @return bool
	 */
	function lunara_home_cinematic_hero_preload_is_allowed() {
		if ( is_admin() || ! is_front_page() || ! function_exists( 'lunara_home_cinematic_front_door_is_enabled' ) || ! lunara_home_cinematic_front_door_is_enabled() ) {
			return false;
		}

		$shortcode     = function_exists( 'lunara_home_plugin_hero_shortcode' ) ? lunara_home_plugin_hero_shortcode() : '';
		$shortcode_tag = function_exists( 'lunara_home_extract_shortcode_tag' ) ? lunara_home_extract_shortcode_tag( $shortcode ) : '';
		if (
			function_exists( 'lunara_home_plugin_hero_is_allowed' )
			&& lunara_home_plugin_hero_is_allowed()
			&& '' !== $shortcode
			&& '' !== $shortcode_tag
			&& shortcode_exists( $shortcode_tag )
		) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'lunara_get_home_cinematic_hero_preload_descriptor' ) ) {
	/**
	 * Get the final responsive descriptor used by both hero and head hint.
	 *
	 * @return array<string,mixed>
	 */
	function lunara_get_home_cinematic_hero_preload_descriptor() {
		if ( ! lunara_home_cinematic_hero_preload_is_allowed() ) {
			return lunara_hero_empty_image_descriptor();
		}

		return lunara_build_cinematic_hero_image_descriptor( lunara_resolve_home_cinematic_hero_lcp_data(), true );
	}
}

if ( ! function_exists( 'lunara_get_home_cinematic_hero_preload_url' ) ) {
	/**
	 * Backward-compatible URL accessor for diagnostics and URL-only fallbacks.
	 *
	 * @return string
	 */
	function lunara_get_home_cinematic_hero_preload_url() {
		$descriptor = lunara_get_home_cinematic_hero_preload_descriptor();
		return (string) $descriptor['src'];
	}
}

if ( ! function_exists( 'lunara_get_home_cinematic_hero_http_link_value' ) ) {
	/**
	 * Build an HTTP Link value only when there is one exact URL-only resource.
	 *
	 * Responsive image selection needs viewport information, which is not
	 * available to an HTTP response header; the HTML imagesrcset hint owns that
	 * case instead.
	 *
	 * @return string
	 */
	function lunara_get_home_cinematic_hero_http_link_value() {
		if ( ! lunara_home_cinematic_hero_preload_is_allowed() ) {
			return '';
		}

		$data          = lunara_resolve_home_cinematic_hero_lcp_data();
		$image         = isset( $data['image'] ) ? trim( (string) $data['image'] ) : '';
		$image         = '' !== $image ? $image : ( isset( $data['image_url'] ) ? trim( (string) $data['image_url'] ) : '' );
		$attachment_id = isset( $data['attachment_id'] ) ? max( 0, (int) $data['attachment_id'] ) : 0;
		$attachment_id = $attachment_id > 0 ? $attachment_id : lunara_hero_attachment_id_from_url( $image );

		// Do not build final attachment markup during template_redirect. Later
		// image filters may not be registered yet, and responsive HTTP preloads
		// cannot select by viewport anyway. wp_head builds the final descriptor.
		if ( $attachment_id > 0 ) {
			return '';
		}

		$descriptor = lunara_build_cinematic_hero_image_descriptor( $data, true );
		if ( '' !== $descriptor['srcset'] ) {
			return '';
		}
		if ( '' === $descriptor['src'] ) {
			return '';
		}

		return '<' . $descriptor['src'] . '>; rel=preload; as=image; fetchpriority=high';
	}
}

if ( ! function_exists( 'lunara_send_home_cinematic_hero_preload_header' ) ) {
	/** Send the exact URL-only hero preload as an HTTP response hint. */
	function lunara_send_home_cinematic_hero_preload_header() {
		if ( headers_sent() ) {
			return;
		}

		$link = lunara_get_home_cinematic_hero_http_link_value();
		if ( '' !== $link ) {
			header( 'Link: ' . $link, false );
		}
	}
	add_action( 'template_redirect', 'lunara_send_home_cinematic_hero_preload_header', 0 );
}

if ( ! function_exists( 'lunara_preload_home_cinematic_hero_image' ) ) {
	/** Emit the standards-based in-document hero preload. */
	function lunara_preload_home_cinematic_hero_image() {
		$descriptor = lunara_get_home_cinematic_hero_preload_descriptor();
		if ( '' === $descriptor['src'] ) {
			return;
		}

		if ( '' !== $descriptor['srcset'] ) {
			printf(
				'<link id="lunara-home-hero-preload" rel="preload" as="image" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
				esc_attr( $descriptor['srcset'] ),
				esc_attr( '' !== $descriptor['sizes'] ? $descriptor['sizes'] : '100vw' )
			);
			return;
		}

		printf(
			'<link id="lunara-home-hero-preload" rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
			esc_url( $descriptor['src'] )
		);
	}
	add_action( 'wp_head', 'lunara_preload_home_cinematic_hero_image', 1 );
}
