<?php
/**
 * Lunara trailer module.
 *
 * Adds an editorial trailer fieldset for Journal entries and renders a branded
 * responsive trailer module on public single pages.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_trailer_supported_post_types' ) ) {
	function lunara_trailer_supported_post_types() {
		return array( 'journal', 'review' );
	}
}

if ( ! function_exists( 'lunara_trailer_placement_options' ) ) {
	function lunara_trailer_placement_options() {
		return array(
			'after_first_paragraph'  => __( 'After first paragraph', 'lunara-film' ),
			'after_second_paragraph' => __( 'After second paragraph', 'lunara-film' ),
			'after_hero'             => __( 'After hero image', 'lunara-film' ),
			'end'                    => __( 'End of article', 'lunara-film' ),
		);
	}
}

if ( ! function_exists( 'lunara_sanitize_trailer_placement' ) ) {
	function lunara_sanitize_trailer_placement( $value ) {
		$value = sanitize_key( (string) $value );
		$options = lunara_trailer_placement_options();

		return isset( $options[ $value ] ) ? $value : 'after_first_paragraph';
	}
}

if ( ! function_exists( 'lunara_get_trailer_placement' ) ) {
	function lunara_get_trailer_placement( $post_id ) {
		$value = get_post_meta( (int) $post_id, '_lunara_trailer_placement', true );

		return lunara_sanitize_trailer_placement( '' !== trim( (string) $value ) ? $value : 'after_first_paragraph' );
	}
}

if ( ! function_exists( 'lunara_post_has_trailer' ) ) {
	function lunara_post_has_trailer( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( ! in_array( get_post_type( $post_id ), lunara_trailer_supported_post_types(), true ) ) {
			return false;
		}

		$url = get_post_meta( $post_id, '_lunara_trailer_url', true );
		if ( '' === trim( (string) $url ) || ! lunara_is_supported_trailer_url( $url ) ) {
			return false;
		}

		return '' !== lunara_get_trailer_embed_src( $url );
	}
}

if ( ! function_exists( 'lunara_render_trailer_card_badge' ) ) {
	function lunara_render_trailer_card_badge( $post_id, $context = 'card' ) {
		if ( ! lunara_post_has_trailer( $post_id ) ) {
			return '';
		}

		$classes = array( 'lunara-trailer-card-badge' );
		if ( '' !== trim( (string) $context ) ) {
			$classes[] = 'is-' . sanitize_html_class( $context );
		}

		return sprintf(
			'<span class="%1$s" aria-label="%2$s">%3$s</span>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr__( 'This entry includes a trailer', 'lunara-film' ),
			esc_html__( 'Watch trailer', 'lunara-film' )
		);
	}
}

if ( ! function_exists( 'lunara_trailer_badge_inline_styles' ) ) {
	function lunara_trailer_badge_inline_styles() {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="lunara-trailer-badge-inline-styles">
			.lunara-trailer-card-badge {
				display: inline-flex !important;
				align-items: center !important;
				justify-content: center !important;
				width: fit-content !important;
				max-width: 100% !important;
				margin: 0 0 8px !important;
				padding: 5px 10px !important;
				border: 1px solid rgba(124,211,255,.28) !important;
				border-radius: 999px !important;
				background: linear-gradient(180deg, rgba(124,211,255,.12), rgba(201,169,97,.06)) !important;
				color: color-mix(in srgb, rgba(124,211,255,.92) 68%, var(--lunara-gold-light, #e0c481)) !important;
				font-size: .66rem !important;
				font-weight: 700 !important;
				letter-spacing: .12em !important;
				line-height: 1 !important;
				text-transform: uppercase !important;
				white-space: nowrap !important;
			}
			.lunara-journal-card-provenance + .lunara-trailer-card-badge,
			.lunara-dispatch-type + .lunara-trailer-card-badge {
				margin-top: 2px !important;
			}
			.lunara-review-grid-footer .lunara-trailer-card-badge,
			.lunara-journal-home-card-meta .lunara-trailer-card-badge {
				margin: 0 !important;
			}
		</style>
		<?php
	}
}
add_action( 'wp_head', 'lunara_trailer_badge_inline_styles', 35 );

if ( ! function_exists( 'lunara_register_trailer_meta' ) ) {
	function lunara_register_trailer_meta() {
		$fields = array(
			'_lunara_trailer_url'       => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
			'_lunara_trailer_label'     => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
			'_lunara_trailer_credit'    => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
			'_lunara_trailer_note'      => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
			'_lunara_trailer_placement' => array( 'type' => 'string', 'sanitize' => 'lunara_sanitize_trailer_placement' ),
		);

		foreach ( lunara_trailer_supported_post_types() as $post_type ) {
			foreach ( $fields as $key => $cfg ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'show_in_rest'      => true,
						'single'            => true,
						'type'              => $cfg['type'],
						'sanitize_callback' => $cfg['sanitize'],
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}
}
add_action( 'init', 'lunara_register_trailer_meta' );

if ( ! function_exists( 'lunara_trailer_add_meta_box' ) ) {
	function lunara_trailer_add_meta_box() {
		foreach ( lunara_trailer_supported_post_types() as $post_type ) {
			add_meta_box(
				'lunara_trailer_meta',
				__( 'Lunara Trailer', 'lunara-film' ),
				'lunara_trailer_meta_box_render',
				$post_type,
				'side',
				'default'
			);
		}
	}
}
add_action( 'add_meta_boxes', 'lunara_trailer_add_meta_box' );

if ( ! function_exists( 'lunara_trailer_meta_box_render' ) ) {
	function lunara_trailer_meta_box_render( $post ) {
		wp_nonce_field( 'lunara_trailer_meta_save', 'lunara_trailer_meta_nonce' );

		$url    = get_post_meta( $post->ID, '_lunara_trailer_url', true );
		$label  = get_post_meta( $post->ID, '_lunara_trailer_label', true );
		$credit = get_post_meta( $post->ID, '_lunara_trailer_credit', true );
		$note   = get_post_meta( $post->ID, '_lunara_trailer_note', true );
		$placement = lunara_get_trailer_placement( $post->ID );
		?>
		<style>
			.lunara-trailer-meta-field { margin: 0 0 14px; }
			.lunara-trailer-meta-field label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #1e1e1e; }
			.lunara-trailer-meta-field input[type="text"],
			.lunara-trailer-meta-field input[type="url"],
			.lunara-trailer-meta-field select,
			.lunara-trailer-meta-field textarea { width: 100%; box-sizing: border-box; }
			.lunara-trailer-meta-field textarea { height: 72px; resize: vertical; }
			.lunara-trailer-meta-field .description { margin-top: 4px; font-size: 11px; color: #757575; }
		</style>

		<div class="lunara-trailer-meta-field">
			<label for="lunara_trailer_url"><?php esc_html_e( 'Trailer URL', 'lunara-film' ); ?></label>
			<input type="url" id="lunara_trailer_url" name="lunara_trailer_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://www.youtube.com/watch?v=..." />
			<p class="description"><?php esc_html_e( 'Paste a YouTube or Vimeo trailer URL.', 'lunara-film' ); ?></p>
		</div>

		<div class="lunara-trailer-meta-field">
			<label for="lunara_trailer_placement"><?php esc_html_e( 'Trailer Placement', 'lunara-film' ); ?></label>
			<select id="lunara_trailer_placement" name="lunara_trailer_placement">
				<?php foreach ( lunara_trailer_placement_options() as $value => $label_text ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $placement, $value ); ?>>
						<?php echo esc_html( $label_text ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Default trade rhythm: intro paragraph, trailer, then analysis continues.', 'lunara-film' ); ?></p>
		</div>

		<div class="lunara-trailer-meta-field">
			<label for="lunara_trailer_label"><?php esc_html_e( 'Trailer Label', 'lunara-film' ); ?></label>
			<input type="text" id="lunara_trailer_label" name="lunara_trailer_label" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Official Trailer', 'lunara-film' ); ?>" />
		</div>

		<div class="lunara-trailer-meta-field">
			<label for="lunara_trailer_credit"><?php esc_html_e( 'Source / Credit', 'lunara-film' ); ?></label>
			<input type="text" id="lunara_trailer_credit" name="lunara_trailer_credit" value="<?php echo esc_attr( $credit ); ?>" placeholder="<?php esc_attr_e( 'YouTube / Studio channel', 'lunara-film' ); ?>" />
		</div>

		<div class="lunara-trailer-meta-field">
			<label for="lunara_trailer_note"><?php esc_html_e( 'Editorial Note', 'lunara-film' ); ?></label>
			<textarea id="lunara_trailer_note" name="lunara_trailer_note" placeholder="<?php esc_attr_e( 'One sentence about why this trailer matters.', 'lunara-film' ); ?>"><?php echo esc_textarea( $note ); ?></textarea>
		</div>
		<?php
	}
}

if ( ! function_exists( 'lunara_trailer_meta_box_save' ) ) {
	function lunara_trailer_meta_box_save( $post_id ) {
		if (
			! isset( $_POST['lunara_trailer_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['lunara_trailer_meta_nonce'], 'lunara_trailer_meta_save' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'lunara_trailer_url'       => 'esc_url_raw',
			'lunara_trailer_label'     => 'sanitize_text_field',
			'lunara_trailer_credit'    => 'sanitize_text_field',
			'lunara_trailer_note'      => 'sanitize_textarea_field',
			'lunara_trailer_placement' => 'lunara_sanitize_trailer_placement',
		);

		foreach ( $fields as $field => $sanitize_callback ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}

			$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field ] ) );
			update_post_meta( $post_id, '_' . $field, $value );
		}
	}
}
foreach ( lunara_trailer_supported_post_types() as $lunara_trailer_post_type ) {
	add_action( 'save_post_' . $lunara_trailer_post_type, 'lunara_trailer_meta_box_save' );
}

if ( ! function_exists( 'lunara_is_supported_trailer_url' ) ) {
	function lunara_is_supported_trailer_url( $url ) {
		$host = wp_parse_url( (string) $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		$host = strtolower( preg_replace( '/^www\./', '', $host ) );
		return in_array(
			$host,
			array(
				'youtube.com',
				'youtu.be',
				'm.youtube.com',
				'vimeo.com',
				'player.vimeo.com',
			),
			true
		);
	}
}

if ( ! function_exists( 'lunara_get_trailer_embed_html' ) ) {
	function lunara_get_trailer_embed_html( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || ! lunara_is_supported_trailer_url( $url ) ) {
			return '';
		}

		$fallback = lunara_get_trailer_iframe_fallback( $url );
		if ( '' !== $fallback ) {
			return $fallback;
		}

		$embed = wp_oembed_get(
			$url,
			array(
				'width'  => 1280,
				'height' => 720,
			)
		);

		if ( ! is_string( $embed ) || '' === trim( $embed ) || false === stripos( $embed, '<iframe' ) ) {
			return '';
		}

		$allowed             = wp_kses_allowed_html( 'post' );
		$allowed['iframe']  = array(
			'allow'           => true,
			'allowfullscreen' => true,
			'class'           => true,
			'frameborder'     => true,
			'height'          => true,
			'loading'         => true,
			'referrerpolicy'  => true,
			'src'             => true,
			'style'           => true,
			'title'           => true,
			'width'           => true,
		);
		$allowed['blockquote']['class'] = true;

		return wp_kses( $embed, $allowed );
	}
}

if ( ! function_exists( 'lunara_get_trailer_iframe_fallback' ) ) {
	function lunara_get_trailer_iframe_fallback( $url ) {
		$src = lunara_get_trailer_embed_src( $url );
		if ( '' === $src ) {
			return '';
		}

		return sprintf(
			'<iframe src="%1$s" title="%2$s" loading="lazy" frameborder="0" allow="%3$s" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>',
			esc_url( $src ),
			esc_attr__( 'Trailer video', 'lunara-film' ),
			esc_attr( 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share' )
		);
	}
}

if ( ! function_exists( 'lunara_get_trailer_embed_src' ) ) {
	function lunara_get_trailer_embed_src( $url ) {
		$url   = esc_url_raw( (string) $url );
		$host  = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );

		if ( in_array( $host, array( 'youtube.com', 'm.youtube.com' ), true ) ) {
			parse_str( $query, $params );
			$video_id = isset( $params['v'] ) ? (string) $params['v'] : '';
			if ( '' === $video_id && preg_match( '#/(?:embed|shorts)/([A-Za-z0-9_-]{6,})#', $path, $matches ) ) {
				$video_id = $matches[1];
			}
			if ( preg_match( '/^[A-Za-z0-9_-]{6,}$/', $video_id ) ) {
				return 'https://www.youtube.com/embed/' . rawurlencode( $video_id );
			}
		}

		if ( 'youtu.be' === $host && preg_match( '#^/([A-Za-z0-9_-]{6,})#', $path, $matches ) ) {
			return 'https://www.youtube.com/embed/' . rawurlencode( $matches[1] );
		}

		if ( in_array( $host, array( 'vimeo.com', 'player.vimeo.com' ), true ) && preg_match( '#/(?:video/)?([0-9]{6,})#', $path, $matches ) ) {
			return 'https://player.vimeo.com/video/' . rawurlencode( $matches[1] );
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_get_trailer_inline_guardrails' ) ) {
	function lunara_get_trailer_inline_guardrails() {
		static $printed = false;
		if ( $printed ) {
			return '';
		}
		$printed = true;

		return '<style id="lunara-trailer-inline-guardrails">
.lunara-trailer-module{width:min(calc(100% - clamp(36px,8vw,112px)),860px)!important;margin:clamp(26px,4vw,52px) auto 0!important;box-sizing:border-box!important}
.lunara-review-single-content .lunara-trailer-module{width:100%!important}
.lunara-trailer-shell{display:grid!important;grid-template-columns:1fr!important;justify-items:center!important;gap:14px!important;padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important;overflow:visible!important;text-align:center!important}
.lunara-trailer-shell::before{display:none!important}
.lunara-trailer-copy{display:grid!important;justify-items:center!important;gap:8px!important;max-width:680px!important;text-align:center!important}
.lunara-trailer-kicker,.lunara-trailer-title,.lunara-trailer-note,.lunara-trailer-credit{margin:0!important}
.lunara-trailer-kicker{display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:5px 11px!important;border:1px solid rgba(201,169,97,.32)!important;border-radius:999px!important;background:rgba(201,169,97,.08)!important;color:rgba(224,196,129,.92)!important;font-size:.66rem!important;font-weight:700!important;letter-spacing:.16em!important;line-height:1!important;text-transform:uppercase!important}
.lunara-trailer-title{color:#fff6dd!important;font-size:clamp(1.34rem,2.1vw,1.82rem)!important;line-height:1.12!important}
.lunara-trailer-note{max-width:58ch!important;color:rgba(250,251,252,.78)!important;font-size:.96rem!important;line-height:1.6!important}
.lunara-trailer-credit{color:rgba(224,196,129,.82)!important;font-size:.72rem!important;font-weight:700!important;letter-spacing:.12em!important;text-transform:uppercase!important}
.lunara-trailer-frame{width:min(100%,720px)!important;margin:0 auto!important;box-sizing:border-box!important;border:1px solid rgba(201,169,97,.2)!important;border-radius:16px!important;background:#05080c!important;box-shadow:0 18px 42px rgba(0,0,0,.28)!important;overflow:hidden!important}
.lunara-trailer-embed{position:relative!important;width:100%!important;aspect-ratio:16/9!important;min-height:0!important}
.lunara-trailer-embed iframe,.lunara-trailer-embed embed,.lunara-trailer-embed object,.lunara-trailer-embed video{position:absolute!important;inset:0!important;display:block!important;width:100%!important;height:100%!important;min-height:0!important;border:0!important}
.lunara-trailer-facade{position:absolute!important;inset:0!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:12px!important;width:100%!important;height:100%!important;border:0!important;background-color:#05080c!important;background-position:center!important;background-size:cover!important;cursor:pointer!important}
.lunara-trailer-facade::before{content:""!important;position:absolute!important;inset:0!important;background:linear-gradient(180deg,rgba(4,10,18,.18),rgba(4,10,18,.62))!important}
.lunara-trailer-facade-play{position:relative!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;width:64px!important;height:64px!important;border-radius:999px!important;border:1px solid rgba(224,196,129,.85)!important;background:rgba(7,15,26,.72)!important;color:#e0c481!important;font-size:1.25rem!important;padding-left:5px!important;transition:transform .18s ease,background .18s ease}
.lunara-trailer-facade:hover .lunara-trailer-facade-play,.lunara-trailer-facade:focus-visible .lunara-trailer-facade-play{transform:scale(1.08);background:rgba(201,169,97,.32)!important}
.lunara-trailer-facade-label{position:relative!important;color:rgba(244,239,227,.9)!important;font-size:.78rem!important;letter-spacing:.18em!important;text-transform:uppercase!important}
@media(prefers-reduced-motion:reduce){.lunara-trailer-facade-play{transition:none!important}}
@media(max-width:520px){.lunara-trailer-module{width:min(calc(100% - 36px),720px)!important}.lunara-trailer-frame{border-radius:14px!important}}
</style>';
	}
}

if ( ! function_exists( 'lunara_render_trailer_module' ) ) {
	function lunara_render_trailer_module( $post_id = 0, array $args = array() ) {
		$post_id = $post_id ? absint( $post_id ) : get_the_ID();
		if ( $post_id <= 0 ) {
			return '';
		}

		$url    = isset( $args['url'] ) && '' !== trim( (string) $args['url'] ) ? esc_url_raw( $args['url'] ) : get_post_meta( $post_id, '_lunara_trailer_url', true );
		$label  = isset( $args['label'] ) && '' !== trim( (string) $args['label'] ) ? sanitize_text_field( $args['label'] ) : get_post_meta( $post_id, '_lunara_trailer_label', true );
		$credit = isset( $args['credit'] ) && '' !== trim( (string) $args['credit'] ) ? sanitize_text_field( $args['credit'] ) : get_post_meta( $post_id, '_lunara_trailer_credit', true );
		$note   = isset( $args['note'] ) && '' !== trim( (string) $args['note'] ) ? sanitize_textarea_field( $args['note'] ) : get_post_meta( $post_id, '_lunara_trailer_note', true );

		$embed = lunara_get_trailer_embed_html( $url );
		if ( '' === $embed ) {
			return '';
		}

		if ( '' === trim( (string) $label ) ) {
			$label = __( 'Official Trailer', 'lunara-film' );
		}

		// Click-to-load facade: ship a poster frame, not a third-party
		// iframe — kills the initial black rectangle and defers the whole
		// YouTube payload until the reader asks for it. Falls back to the
		// direct embed when no poster can be derived.
		$embed_src = '';
		if ( preg_match( '#src="([^"]+)"#', $embed, $src_match ) ) {
			$embed_src = html_entity_decode( $src_match[1], ENT_QUOTES );
		}
		$facade_poster = '';
		if ( preg_match( '#youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})#', $embed_src, $vid_match ) ) {
			$facade_poster = 'https://img.youtube.com/vi/' . rawurlencode( $vid_match[1] ) . '/hqdefault.jpg';
		}
		if ( '' === $facade_poster && has_post_thumbnail( $post_id ) ) {
			$facade_poster = (string) get_the_post_thumbnail_url( $post_id, 'large' );
		}
		$use_facade = '' !== $embed_src && '' !== $facade_poster;

		ob_start();
		?>
		<?php echo lunara_get_trailer_inline_guardrails(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<section class="lunara-trailer-module" aria-label="<?php esc_attr_e( 'Trailer', 'lunara-film' ); ?>">
			<div class="lunara-trailer-shell">
				<div class="lunara-trailer-copy">
					<p class="lunara-trailer-kicker"><?php esc_html_e( 'Watch', 'lunara-film' ); ?></p>
					<h2 class="lunara-trailer-title"><?php echo esc_html( $label ); ?></h2>
					<?php if ( '' !== trim( (string) $note ) ) : ?>
						<p class="lunara-trailer-note"><?php echo esc_html( $note ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $credit ) ) : ?>
						<p class="lunara-trailer-credit"><?php echo esc_html( $credit ); ?></p>
					<?php endif; ?>
				</div>
				<div class="lunara-trailer-frame">
					<div class="lunara-trailer-embed">
						<?php if ( $use_facade ) : ?>
							<button type="button" class="lunara-trailer-facade" data-lunara-trailer-src="<?php echo esc_url( $embed_src ); ?>" style="background-image:url('<?php echo esc_url( $facade_poster ); ?>');" aria-label="<?php esc_attr_e( 'Play trailer', 'lunara-film' ); ?>">
								<span class="lunara-trailer-facade-play" aria-hidden="true">&#9654;</span>
								<span class="lunara-trailer-facade-label"><?php esc_html_e( 'Play the trailer', 'lunara-film' ); ?></span>
							</button>
						<?php else : ?>
							<?php echo $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php if ( $use_facade ) : ?>
			<?php echo lunara_trailer_facade_loader_js(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php
		return trim( ob_get_clean() );
	}
}

if ( ! function_exists( 'lunara_trailer_facade_loader_js' ) ) {
	/**
	 * One tiny delegated listener, printed once: a facade click swaps the
	 * poster button for the real iframe with autoplay.
	 */
	function lunara_trailer_facade_loader_js() {
		static $printed = false;
		if ( $printed ) {
			return '';
		}
		$printed = true;
		return '<script id="lunara-trailer-facade-js">document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest(".lunara-trailer-facade");if(!b)return;var s=b.getAttribute("data-lunara-trailer-src");if(!s)return;var f=document.createElement("iframe");f.src=s+(s.indexOf("?")===-1?"?":"&")+"autoplay=1";f.setAttribute("allow","accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture");f.setAttribute("allowfullscreen","");f.title=b.getAttribute("aria-label")||"Trailer";b.parentNode.replaceChild(f,b);},true);</script>';
	}
}

if ( ! function_exists( 'lunara_insert_trailer_into_content_html' ) ) {
	function lunara_insert_trailer_into_content_html( $html, $post_id = 0 ) {
		$post_id = $post_id ? absint( $post_id ) : get_the_ID();
		if ( $post_id <= 0 ) {
			return $html;
		}

		$placement = lunara_get_trailer_placement( $post_id );
		if ( 'after_hero' === $placement ) {
			return $html;
		}

		$module = lunara_render_trailer_module( $post_id );
		if ( '' === $module ) {
			return $html;
		}

		if ( 'end' === $placement ) {
			return rtrim( (string) $html ) . "\n" . $module;
		}

		$target = 'after_second_paragraph' === $placement ? 2 : 1;
		$parts  = preg_split( '/(<\/p>)/i', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( empty( $parts ) || ! is_array( $parts ) ) {
			return rtrim( (string) $html ) . "\n" . $module;
		}

		$out = '';
		$paragraph_closes = 0;
		$inserted = false;

		foreach ( $parts as $part ) {
			$out .= $part;

			if ( preg_match( '/^<\/p>$/i', $part ) ) {
				$paragraph_closes++;
				if ( ! $inserted && $paragraph_closes >= $target ) {
					$out .= "\n" . $module;
					$inserted = true;
				}
			}
		}

		if ( ! $inserted ) {
			$out = rtrim( $out ) . "\n" . $module;
		}

		return $out;
	}
}

if ( ! function_exists( 'lunara_trailer_shortcode' ) ) {
	function lunara_trailer_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'    => '',
				'label'  => '',
				'credit' => '',
				'note'   => '',
			),
			$atts,
			'lunara_trailer'
		);

		return lunara_render_trailer_module(
			get_the_ID(),
			array(
				'url'    => $atts['url'],
				'label'  => $atts['label'],
				'credit' => $atts['credit'],
				'note'   => $atts['note'],
			)
		);
	}
}
add_shortcode( 'lunara_trailer', 'lunara_trailer_shortcode' );
