<?php
/**
 * Modular Essay Builder — front-end renderer (Design Spec §12 / §19A).
 *
 * Renders the ACF Flexible Content modules registered by Lunara Core
 * (essay_modules on journal entries and posts) after the main content:
 * prose passages, pull-quotes, inset frames, widescreen video spreads, and
 * full-bleed cinematic banners. Every media module carries the spec's
 * media guards — hairline borders and soft drops — via the scoped CSS in
 * style.css. Per the spec's Preview Rule, nothing renders in the editor.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_render_essay_modules' ) ) {
	/**
	 * Build the full module stack for a post. '' when the builder is empty
	 * or ACF Pro is unavailable — the essay then reads exactly as before.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function lunara_render_essay_modules( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! function_exists( 'have_rows' ) ) {
			return '';
		}

		if ( ! have_rows( 'essay_modules', $post_id ) ) {
			return '';
		}

		$modules = array();

		while ( have_rows( 'essay_modules', $post_id ) ) {
			the_row();
			$layout = (string) get_row_layout();

			switch ( $layout ) {
				case 'prose':
					$text = trim( (string) get_sub_field( 'text' ) );
					if ( '' !== $text ) {
						$modules[] = '<div class="lunara-essay-module lunara-essay-prose">' . wp_kses_post( $text ) . '</div>';
					}
					break;

				case 'pullquote':
					$quote = trim( (string) get_sub_field( 'quote' ) );
					if ( '' === $quote ) {
						break;
					}
					$attribution = trim( (string) get_sub_field( 'attribution' ) );
					$html        = '<figure class="lunara-essay-module lunara-essay-pullquote">';
					$html       .= '<blockquote>' . esc_html( $quote ) . '</blockquote>';
					if ( '' !== $attribution ) {
						$html .= '<figcaption>' . esc_html( $attribution ) . '</figcaption>';
					}
					$html     .= '</figure>';
					$modules[] = $html;
					break;

				case 'inset_frame':
					$image_id = (int) get_sub_field( 'image' );
					if ( $image_id <= 0 ) {
						break;
					}
					$caption = trim( (string) get_sub_field( 'caption' ) );
					$side    = 'left' === get_sub_field( 'side' ) ? 'left' : 'right';
					$img     = wp_get_attachment_image(
						$image_id,
						'large',
						false,
						array(
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 760px) 100vw, 340px',
						)
					);
					if ( '' === $img ) {
						break;
					}
					$html  = '<figure class="lunara-essay-module lunara-essay-inset is-' . esc_attr( $side ) . '">';
					$html .= '<span class="lunara-essay-media-guard">' . $img . '</span>';
					if ( '' !== $caption ) {
						$html .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
					}
					$html     .= '</figure>';
					$modules[] = $html;
					break;

				case 'video_spread':
					$embed = trim( (string) get_sub_field( 'video' ) );
					if ( '' === $embed ) {
						break;
					}
					$note  = trim( (string) get_sub_field( 'note' ) );
					$html  = '<figure class="lunara-essay-module lunara-essay-video">';
					$html .= '<div class="lunara-essay-video-frame lunara-essay-media-guard">' . $embed . '</div>'; // oEmbed HTML from ACF, provider-sanitized.
					if ( '' !== $note ) {
						$html .= '<figcaption>' . esc_html( $note ) . '</figcaption>';
					}
					$html     .= '</figure>';
					$modules[] = $html;
					break;

				case 'cinema_banner':
					$image_id = (int) get_sub_field( 'image' );
					if ( $image_id <= 0 ) {
						break;
					}
					$kicker = trim( (string) get_sub_field( 'kicker' ) );
					$title  = trim( (string) get_sub_field( 'title' ) );
					$img    = wp_get_attachment_image(
						$image_id,
						'full',
						false,
						array(
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '100vw',
						)
					);
					if ( '' === $img ) {
						break;
					}
					$html  = '<figure class="lunara-essay-module lunara-essay-banner">';
					$html .= '<div class="lunara-essay-banner-media">' . $img . '</div>';
					if ( '' !== $kicker || '' !== $title ) {
						$html .= '<figcaption class="lunara-essay-banner-copy">';
						if ( '' !== $kicker ) {
							$html .= '<span class="lunara-essay-banner-kicker">' . esc_html( $kicker ) . '</span>';
						}
						if ( '' !== $title ) {
							$html .= '<span class="lunara-essay-banner-title">' . esc_html( $title ) . '</span>';
						}
						$html .= '</figcaption>';
					}
					$html     .= '</figure>';
					$modules[] = $html;
					break;
			}
		}

		if ( empty( $modules ) ) {
			return '';
		}

		return '<section class="lunara-essay-modules" aria-label="' . esc_attr__( 'Essay', 'lunara-film' ) . '">' . implode( '', $modules ) . '</section>';
	}
}

if ( ! function_exists( 'lunara_essay_append_modules' ) ) {
	/**
	 * Append the module stack after the main content on singular journal
	 * entries and posts. Runs once per post per request.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	function lunara_essay_append_modules( $content ) {
		static $rendered = array();

		if ( ! is_singular( array( 'journal', 'post' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = (int) get_the_ID();
		if ( $post_id <= 0 || isset( $rendered[ $post_id ] ) ) {
			return $content;
		}
		$rendered[ $post_id ] = true;

		$modules = lunara_render_essay_modules( $post_id );
		if ( '' === $modules ) {
			return $content;
		}

		return $content . $modules;
	}
	add_filter( 'the_content', 'lunara_essay_append_modules', 20 );
}
