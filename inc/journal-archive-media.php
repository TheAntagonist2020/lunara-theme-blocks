<?php
/**
 * Journal archive card image delivery.
 *
 * The public archive keeps its settled 16:10 object-fit chamber. This module
 * changes resource selection only: it asks WordPress for the uncropped source
 * so native same-ratio derivatives remain eligible, then adds a small, honest
 * WordPress.com Image CDN srcset only when WordPress has no compatible set.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LUNARA_JOURNAL_ARCHIVE_MIN_SOURCE_WIDTH' ) ) {
	define( 'LUNARA_JOURNAL_ARCHIVE_MIN_SOURCE_WIDTH', 768 );
}

if ( ! function_exists( 'lunara_journal_archive_card_is_visual_lead' ) ) {
	/**
	 * The visual lead belongs only to page one of the main Journal archive.
	 *
	 * @param int $card_index One-based card position in the current query.
	 * @return bool
	 */
	function lunara_journal_archive_card_is_visual_lead( $card_index ) {
		return 1 === absint( $card_index )
			&& is_post_type_archive( 'journal' )
			&& ! is_paged();
	}
}

if ( ! function_exists( 'lunara_journal_archive_card_image_attributes' ) ) {
	/**
	 * Build one stable loading/sizing contract for Journal archive cards.
	 *
	 * @param bool   $is_visual_lead Whether this card owns page-one LCP priority.
	 * @param string $alt            Editor-owned alt with post-title fallback.
	 * @return array<string,string>
	 */
	function lunara_journal_archive_card_image_attributes( $is_visual_lead, $alt ) {
		return array(
			'class'         => 'lunara-review-grid-poster',
			'loading'       => $is_visual_lead ? 'eager' : 'lazy',
			'fetchpriority' => $is_visual_lead ? 'high' : 'auto',
			'decoding'      => 'async',
			'sizes'         => '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 31vw, 380px',
			'alt'           => trim( (string) $alt ),
		);
	}
}

if ( ! function_exists( 'lunara_journal_archive_image_attribute' ) ) {
	/**
	 * Read one final IMG attribute without reinterpreting the markup.
	 *
	 * @param string $html Image markup.
	 * @param string $name Attribute name.
	 * @return string
	 */
	function lunara_journal_archive_image_attribute( $html, $name ) {
		$name = preg_replace( '/[^a-z0-9:_-]/i', '', (string) $name );
		if ( '' === $name ) {
			return '';
		}

		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '\s*=\s*(["\'])(.*?)\1/is', (string) $html, $match ) ) {
			return html_entity_decode( (string) $match[2], ENT_QUOTES, 'UTF-8' );
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_journal_archive_has_valid_srcset' ) ) {
	/**
	 * Confirm that final markup contains at least two valid native candidates.
	 *
	 * @param string $html Image markup.
	 * @return bool
	 */
	function lunara_journal_archive_has_valid_srcset( $html ) {
		$srcset = trim( lunara_journal_archive_image_attribute( $html, 'srcset' ) );
		if ( '' === $srcset ) {
			return false;
		}
		if ( function_exists( 'lunara_sanitize_srcset_value' ) ) {
			$srcset = lunara_sanitize_srcset_value( $srcset );
		}
		if ( '' === $srcset ) {
			return false;
		}

		$candidates = preg_split( '/,\s*(?=(?:https?:)?\/\/|\/)/', $srcset );
		if ( ! is_array( $candidates ) ) {
			return false;
		}
		$valid = array();
		foreach ( $candidates as $candidate ) {
			$candidate = trim( (string) $candidate );
			if ( preg_match( '/\s+(?:\d+w|\d+(?:\.\d+)?x)$/', $candidate ) ) {
				$valid[ $candidate ] = true;
			}
		}

		return count( $valid ) >= 2;
	}
}

if ( ! function_exists( 'lunara_journal_archive_local_attachment_url' ) ) {
	/**
	 * Resolve only a local upload URL, including an existing i0.wp.com proxy.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	function lunara_journal_archive_local_attachment_url( $attachment_id ) {
		$url = function_exists( 'wp_get_attachment_url' ) ? wp_get_attachment_url( $attachment_id ) : '';
		$url = is_string( $url ) ? esc_url_raw( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) ) : '';
		if ( '' === $url ) {
			return '';
		}

		$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$site_host = (string) preg_replace( '/^www\./', '', $site_host );
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$url_host  = (string) preg_replace( '/^www\./', '', $url_host );
		$url_path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$url_scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		$url_port   = wp_parse_url( $url, PHP_URL_PORT );
		if ( 'https' !== $url_scheme || null !== $url_port ) {
			return '';
		}

		if ( $site_host === $url_host && 0 === strpos( $url_path, '/wp-content/uploads/' ) ) {
			return $url;
		}

		if ( preg_match( '/^i[0-2]\.wp\.com$/', $url_host ) && '' !== $site_host ) {
			$proxy_prefix = '/' . $site_host . '/wp-content/uploads/';
			if ( 0 === strpos( strtolower( $url_path ), strtolower( $proxy_prefix ) ) ) {
				$local_path = substr( $url_path, strlen( '/' . $site_host ) );
				return esc_url_raw( home_url( $local_path ) );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_journal_archive_image_dimensions' ) ) {
	/**
	 * Resolve honest full-source dimensions from attachment metadata/markup.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $html          Final image markup.
	 * @return array{width:int,height:int}
	 */
	function lunara_journal_archive_image_dimensions( $attachment_id, $html ) {
		$metadata = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$width    = is_array( $metadata ) && isset( $metadata['width'] ) && is_scalar( $metadata['width'] ) ? absint( $metadata['width'] ) : 0;
		$height   = is_array( $metadata ) && isset( $metadata['height'] ) && is_scalar( $metadata['height'] ) ? absint( $metadata['height'] ) : 0;

		if ( $width <= 0 ) {
			$width = absint( lunara_journal_archive_image_attribute( $html, 'width' ) );
		}
		if ( $height <= 0 ) {
			$height = absint( lunara_journal_archive_image_attribute( $html, 'height' ) );
		}

		return array( 'width' => $width, 'height' => $height );
	}
}

if ( ! function_exists( 'lunara_journal_archive_cdn_srcset' ) ) {
	/**
	 * Build at most six downscale-only WordPress.com CDN candidates.
	 *
	 * @param string $source_url Local source URL.
	 * @param int    $source_width Intrinsic width.
	 * @param int    $source_height Intrinsic height.
	 * @return string
	 */
	function lunara_journal_archive_cdn_srcset( $source_url, $source_width, $source_height ) {
		$source_url    = trim( (string) $source_url );
		$source_width  = absint( $source_width );
		$source_height = absint( $source_height );
		if ( '' === $source_url || $source_width < 2 || $source_height < 2 || ! function_exists( 'jetpack_photon_url' ) ) {
			return '';
		}

		$ceiling = min( 1920, $source_width );
		$widths  = array_values(
			array_filter(
				array( 320, 480, 768, 1200, 1600, 1920 ),
				static function ( $width ) use ( $ceiling ) {
					return $width <= $ceiling;
				}
			)
		);

		if ( $ceiling > 0 && ! in_array( $ceiling, $widths, true ) ) {
			$widths[] = $ceiling;
		}
		if ( count( $widths ) < 2 && $ceiling >= 2 ) {
			array_unshift( $widths, max( 1, (int) floor( $ceiling / 2 ) ) );
		}
		$widths = array_slice( array_values( array_unique( array_map( 'absint', $widths ) ) ), 0, 6 );
		sort( $widths, SORT_NUMERIC );

		$candidates = array();
		foreach ( $widths as $width ) {
			if ( $width <= 0 || $width > $source_width ) {
				continue;
			}
			$url    = jetpack_photon_url(
				$source_url,
				array(
					'w'       => $width,
					'quality' => 86,
					'strip'   => 'all',
				),
				'https'
			);
			$url = is_string( $url ) ? esc_url_raw( $url ) : '';
			$cdn_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			$cdn_scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
			$cdn_port = wp_parse_url( $url, PHP_URL_PORT );
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			if (
				'' === $url
				|| 'https' !== $cdn_scheme
				|| null !== $cdn_port
				|| ! preg_match( '/^i[0-2]\.wp\.com$/', $cdn_host )
				|| ! isset( $query['w'] )
				|| $width !== absint( $query['w'] )
				|| isset( $query['h'] )
				|| isset( $query['resize'] )
				|| isset( $query['fit'] )
			) {
				continue;
			}
			$candidates[] = $url . ' ' . $width . 'w';
		}

		if ( count( $candidates ) < 2 ) {
			return '';
		}

		$srcset = implode( ', ', $candidates );
		return function_exists( 'lunara_sanitize_srcset_value' )
			? lunara_sanitize_srcset_value( $srcset )
			: $srcset;
	}
}

if ( ! function_exists( 'lunara_journal_archive_inject_srcset' ) ) {
	/**
	 * Add the bounded fallback to one final IMG tag.
	 *
	 * @param string $html   Final image markup.
	 * @param string $srcset Validated srcset.
	 * @param string $sizes  Route sizes contract.
	 * @return string
	 */
	function lunara_journal_archive_inject_srcset( $html, $srcset, $sizes ) {
		if ( '' === trim( (string) $html ) || '' === trim( (string) $srcset ) ) {
			return '';
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( (string) $html );
			if ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
				$processor->set_attribute( 'srcset', $srcset );
				if ( '' !== trim( (string) $sizes ) ) {
					$processor->set_attribute( 'sizes', $sizes );
				}
				return $processor->get_updated_html();
			}
		}

		$attributes = ' srcset="' . esc_attr( $srcset ) . '"';
		if ( '' !== trim( (string) $sizes ) && '' === lunara_journal_archive_image_attribute( $html, 'sizes' ) ) {
			$attributes .= ' sizes="' . esc_attr( $sizes ) . '"';
		}

		$updated = preg_replace( '/<img\b/i', '<img' . $attributes, (string) $html, 1 );
		return is_string( $updated ) ? $updated : '';
	}
}

if ( ! function_exists( 'lunara_journal_archive_card_image_markup' ) ) {
	/**
	 * Return responsive attachment markup or an empty text-card fallback.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $attributes    WordPress image attributes.
	 * @return string
	 */
	function lunara_journal_archive_card_image_markup( $attachment_id, $attributes ) {
		$attachment_id = absint( $attachment_id );
		$attributes    = is_array( $attributes ) ? $attributes : array();
		if (
			$attachment_id <= 0
			|| 'attachment' !== get_post_type( $attachment_id )
			|| ! wp_attachment_is_image( $attachment_id )
			|| ! function_exists( 'wp_get_attachment_image' )
		) {
			return '';
		}

		$route_html = (string) wp_get_attachment_image( $attachment_id, 'lunara-hero-spotlight', false, $attributes );
		if ( '' === trim( $route_html ) ) {
			return '';
		}
		$route_dimensions = lunara_journal_archive_image_dimensions( $attachment_id, $route_html );
		if ( $route_dimensions['width'] < LUNARA_JOURNAL_ARCHIVE_MIN_SOURCE_WIDTH || $route_dimensions['height'] < 2 ) {
			return '';
		}
		if ( lunara_journal_archive_has_valid_srcset( $route_html ) ) {
			return $route_html;
		}

		$full_html = (string) wp_get_attachment_image( $attachment_id, 'full', false, $attributes );
		if ( '' === trim( $full_html ) ) {
			return '';
		}
		if ( lunara_journal_archive_has_valid_srcset( $full_html ) ) {
			return $full_html;
		}

		$source_url = lunara_journal_archive_local_attachment_url( $attachment_id );
		$dimensions = lunara_journal_archive_image_dimensions( $attachment_id, $full_html );
		$srcset     = lunara_journal_archive_cdn_srcset( $source_url, $dimensions['width'], $dimensions['height'] );
		if ( '' === $srcset ) {
			return '';
		}

		$sizes = isset( $attributes['sizes'] ) && is_scalar( $attributes['sizes'] ) ? (string) $attributes['sizes'] : '';
		return lunara_journal_archive_inject_srcset( $full_html, $srcset, $sizes );
	}
}
