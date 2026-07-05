<?php
/**
 * Hero Command — total granular control of the homepage cinematic hero.
 *
 * The command deck is an ordered, hand-curated slide list stored in one
 * option. When enabled with at least one renderable slide, the curated list
 * IS the hero: any mix of reviews, journal pieces, essays, or pages, in the
 * exact order set here, with per-slide kicker and CTA overrides and overlay
 * intensity control (global dial + per-slide override). When disabled or
 * empty, the automatic newest-content feed keeps running exactly as before —
 * the deck never degrades the hero, it only takes command of it.
 *
 * Admin surface: the "Hero Command" studio inside Control Desk → Theme
 * Studio (lunara_control_desk_render_hero_command_studio, called from the
 * theme-studio tab). Saving purges the WP Rocket home cache so the hero
 * changes are visible immediately.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * Settings model
 * ========================================================================== */

if ( ! function_exists( 'lunara_hero_command_default_settings' ) ) {
	function lunara_hero_command_default_settings() {
		return array(
			'enabled' => 0,
			'overlay' => 100,
			'slides'  => array(),
		);
	}
}

if ( ! function_exists( 'lunara_hero_command_post_types' ) ) {
	/**
	 * Post types the deck can feature. Mirrors the Spotlight Campaign's
	 * allow-list so every hero curation surface speaks the same language.
	 */
	function lunara_hero_command_post_types() {
		return (array) apply_filters(
			'lunara_hero_command_post_types',
			array( 'review', 'journal', 'post', 'page' )
		);
	}
}

if ( ! function_exists( 'lunara_hero_command_max_slides' ) ) {
	/**
	 * Upper bound on deck size — a sanity rail, not a creative limit.
	 * "Feature all reviews if we wanted" is the requirement; 48 slides of
	 * native-lazy images is still a healthy page.
	 */
	function lunara_hero_command_max_slides() {
		return max( 1, (int) apply_filters( 'lunara_hero_command_max_slides', 48 ) );
	}
}

if ( ! function_exists( 'lunara_hero_command_sanitize' ) ) {
	/**
	 * Normalize a raw settings payload into the canonical shape.
	 *
	 * @param mixed $raw Anything claiming to be Hero Command settings.
	 * @return array{enabled:int,overlay:int,slides:array<int,array{post_id:int,kicker:string,cta:string,overlay:int}>}
	 */
	function lunara_hero_command_sanitize( $raw ) {
		$defaults = lunara_hero_command_default_settings();
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		$clean            = $defaults;
		$clean['enabled'] = empty( $raw['enabled'] ) ? 0 : 1;

		$overlay          = isset( $raw['overlay'] ) ? (int) $raw['overlay'] : 100;
		$clean['overlay'] = max( 20, min( 100, $overlay > 0 ? $overlay : 100 ) );

		$allowed_types = lunara_hero_command_post_types();
		$max_slides    = lunara_hero_command_max_slides();
		$seen_ids      = array();

		if ( isset( $raw['slides'] ) && is_array( $raw['slides'] ) ) {
			foreach ( $raw['slides'] as $entry ) {
				if ( count( $clean['slides'] ) >= $max_slides ) {
					break;
				}
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$post_id = isset( $entry['post_id'] ) ? absint( $entry['post_id'] ) : 0;
				if ( $post_id < 1 || isset( $seen_ids[ $post_id ] ) ) {
					continue;
				}

				$post = get_post( $post_id );
				if ( ! ( $post instanceof WP_Post )
					|| 'publish' !== $post->post_status
					|| ! in_array( $post->post_type, $allowed_types, true ) ) {
					continue;
				}

				$slide_overlay = isset( $entry['overlay'] ) ? (int) $entry['overlay'] : 0;
				$slide_overlay = $slide_overlay > 0 ? max( 20, min( 100, $slide_overlay ) ) : 0;

				$seen_ids[ $post_id ] = true;
				$clean['slides'][]    = array(
					'post_id' => $post_id,
					'kicker'  => mb_substr( sanitize_text_field( (string) ( $entry['kicker'] ?? '' ) ), 0, 60 ),
					'cta'     => mb_substr( sanitize_text_field( (string) ( $entry['cta'] ?? '' ) ), 0, 40 ),
					'overlay' => $slide_overlay,
				);
			}
		}

		return $clean;
	}
}

if ( ! function_exists( 'lunara_hero_command_settings' ) ) {
	function lunara_hero_command_settings() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = lunara_hero_command_sanitize( get_option( 'lunara_hero_command', array() ) );
		}
		return $cache;
	}
}

/* ==========================================================================
 * Slide feed
 * ========================================================================== */

if ( ! function_exists( 'lunara_hero_command_slides' ) ) {
	/**
	 * Build the curated deck into renderable hero slides.
	 *
	 * Empty array means "the deck is not in command" (disabled, empty, or no
	 * entry could produce a qualifying wide image) — the automatic feed then
	 * runs untouched. Slides that fail to build are skipped, never blanked.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function lunara_hero_command_slides() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$cache    = array();
		$settings = lunara_hero_command_settings();

		if ( empty( $settings['enabled'] ) || empty( $settings['slides'] )
			|| ! function_exists( 'lunara_build_hero_slide_for_post' ) ) {
			return $cache;
		}

		foreach ( $settings['slides'] as $entry ) {
			$slide = lunara_build_hero_slide_for_post( (int) $entry['post_id'], (string) $entry['kicker'] );
			if ( ! is_array( $slide ) || empty( $slide['image'] ) ) {
				continue;
			}
			if ( '' !== trim( (string) $entry['cta'] ) ) {
				$slide['cta'] = (string) $entry['cta'];
			}
			if ( (int) $entry['overlay'] > 0 ) {
				$slide['overlay'] = (int) $entry['overlay'];
			}
			$cache[] = $slide;
		}

		return $cache;
	}
}

/* ==========================================================================
 * Overlay intensity
 * ========================================================================== */

if ( ! function_exists( 'lunara_hero_overlay_percent' ) ) {
	/**
	 * Effective overlay strength for a slide: per-slide override when the
	 * slide carries one, otherwise the global dial. 100 = the shipped
	 * gradient exactly; lower percentages lift the veil proportionally.
	 *
	 * The global dial applies even when the deck itself is disabled, so the
	 * overlay can be tuned without committing to manual curation.
	 *
	 * @param array $data Slide data (may carry an 'overlay' key).
	 * @return int 20–100.
	 */
	function lunara_hero_overlay_percent( $data = array() ) {
		$settings = lunara_hero_command_settings();
		$percent  = (int) $settings['overlay'];

		if ( is_array( $data ) && ! empty( $data['overlay'] ) ) {
			$percent = (int) $data['overlay'];
		}

		$percent = $percent > 0 ? $percent : 100;
		return max( 20, min( 100, (int) apply_filters( 'lunara_hero_overlay_percent', $percent, $data ) ) );
	}
}

if ( ! function_exists( 'lunara_hero_overlay_style_attr' ) ) {
	/**
	 * Inline style attribute for the hero overlay div — '' at full strength
	 * so default markup stays byte-identical to pre-Hero-Command output.
	 *
	 * @param array $data Slide data.
	 * @return string '' or ' style="opacity:0.xx"'.
	 */
	function lunara_hero_overlay_style_attr( $data = array() ) {
		$percent = lunara_hero_overlay_percent( $data );
		if ( $percent >= 100 ) {
			return '';
		}
		return ' style="opacity:' . esc_attr( number_format( $percent / 100, 2, '.', '' ) ) . '"';
	}
}

/* ==========================================================================
 * Admin: AJAX post search for the deck builder
 * ========================================================================== */

if ( ! function_exists( 'lunara_hero_command_ajax_search' ) ) {
	function lunara_hero_command_ajax_search() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( 'lunara_hero_command', 'nonce' );

		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$query = new WP_Query(
			array(
				'post_type'      => lunara_hero_command_post_types(),
				'post_status'    => 'publish',
				's'              => $term,
				'posts_per_page' => 12,
				'no_found_rows'  => true,
			)
		);

		$results = array();
		foreach ( $query->posts as $post ) {
			$slide     = function_exists( 'lunara_build_hero_slide_for_post' )
				? lunara_build_hero_slide_for_post( $post->ID )
				: null;
			$type_obj  = get_post_type_object( $post->post_type );
			$results[] = array(
				'id'    => (int) $post->ID,
				'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				'type'  => $type_obj ? (string) $type_obj->labels->singular_name : (string) $post->post_type,
				'date'  => (string) get_the_date( '', $post ),
				'ready' => is_array( $slide ) && ! empty( $slide['image'] ),
			);
		}

		wp_send_json_success( $results );
	}
	add_action( 'wp_ajax_lunara_hero_command_search', 'lunara_hero_command_ajax_search' );
}

/* ==========================================================================
 * Admin: save handler
 * ========================================================================== */

if ( ! function_exists( 'lunara_hero_command_save' ) ) {
	function lunara_hero_command_save() {
		$redirect = function_exists( 'lunara_control_desk_admin_url' )
			? lunara_control_desk_admin_url( array( 'tab' => 'theme-studio' ) )
			: admin_url( 'admin.php' );
		$redirect .= '#lunara-theme-studio-hero-command';

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_safe_redirect( add_query_arg( 'lunara_notice', 'hero_command_forbidden', $redirect ) );
			exit;
		}

		check_admin_referer( 'lunara_save_hero_command', 'lunara_hero_command_nonce' );

		$raw = array(
			'enabled' => isset( $_POST['lunara_hero_command_enabled'] ) ? 1 : 0,
			'overlay' => isset( $_POST['lunara_hero_command_overlay'] ) ? (int) $_POST['lunara_hero_command_overlay'] : 100,
			'slides'  => isset( $_POST['lunara_hero_command_slides'] ) && is_array( $_POST['lunara_hero_command_slides'] )
				? array_values( wp_unslash( $_POST['lunara_hero_command_slides'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field below.
				: array(),
		);

		update_option( 'lunara_hero_command', lunara_hero_command_sanitize( $raw ), true );

		// The hero is the homepage LCP band — make the change visible now.
		if ( function_exists( 'rocket_clean_home' ) ) {
			rocket_clean_home();
		}
		if ( function_exists( 'rocket_clean_used_css' ) ) {
			rocket_clean_used_css();
		}

		wp_safe_redirect( add_query_arg( 'lunara_notice', 'hero_command_saved', $redirect ) );
		exit;
	}
	add_action( 'admin_post_lunara_save_hero_command', 'lunara_hero_command_save' );
}

/* ==========================================================================
 * Admin: the Hero Command studio (Control Desk → Theme Studio)
 * ========================================================================== */

if ( ! function_exists( 'lunara_control_desk_render_hero_command_studio' ) ) {
	function lunara_control_desk_render_hero_command_studio() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			?>
			<section id="lunara-theme-studio-hero-command" class="lunara-control-desk-homepage-studio">
				<div class="lunara-control-desk-panel-header">
					<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Hero Command', 'lunara-film' ); ?></p>
					<h3><?php esc_html_e( 'Hero curation requires theme editing permission', 'lunara-film' ); ?></h3>
				</div>
			</section>
			<?php
			return;
		}

		$settings   = lunara_hero_command_settings();
		$deck       = $settings['slides'];
		$live_count = count( lunara_hero_command_slides() );
		?>
		<section id="lunara-theme-studio-hero-command" class="lunara-control-desk-homepage-studio">
			<div class="lunara-control-desk-panel-header">
				<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Hero Command', 'lunara-film' ); ?></p>
				<h3><?php esc_html_e( 'Total control of the cinematic hero', 'lunara-film' ); ?></h3>
				<p class="lunara-control-desk-subtle"><?php esc_html_e( 'Feature any mix in any order — one review and three journal pieces, all essays, every review on the site. Per-slide kicker and call-to-action, overlay intensity from featherweight to full cinema. Leave the deck off and the hero keeps curating itself from the newest content.', 'lunara-film' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="lunara-hero-command-form">
				<input type="hidden" name="action" value="lunara_save_hero_command" />
				<?php wp_nonce_field( 'lunara_save_hero_command', 'lunara_hero_command_nonce' ); ?>

				<div class="lunara-control-desk-homepage-card">
					<div class="lunara-control-desk-card-head">
						<div>
							<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Command Status', 'lunara-film' ); ?></p>
							<h3><?php esc_html_e( 'Who is flying the hero', 'lunara-film' ); ?></h3>
							<p class="lunara-control-desk-subtle"><?php esc_html_e( 'The overlay dial applies to the hero in both modes; the deck below only takes over while enabled with at least one live slide.', 'lunara-film' ); ?></p>
						</div>
						<div class="lunara-control-desk-status-pill">
							<strong><?php esc_html_e( 'Right now', 'lunara-film' ); ?></strong>
							<span>
								<?php
								if ( $settings['enabled'] && $live_count > 0 ) {
									/* translators: %d: number of live curated slides. */
									echo esc_html( sprintf( _n( 'Manual — %d curated slide', 'Manual — %d curated slides', $live_count, 'lunara-film' ), $live_count ) );
								} elseif ( $settings['enabled'] ) {
									esc_html_e( 'Enabled, but no slide is live yet — automatic feed showing', 'lunara-film' );
								} else {
									esc_html_e( 'Automatic — newest reviews and journal entries', 'lunara-film' );
								}
								?>
							</span>
						</div>
					</div>

					<div class="lunara-hero-command-status-grid">
						<label class="lunara-hero-command-enable <?php echo $settings['enabled'] ? 'is-enabled' : 'is-disabled'; ?>">
							<input type="checkbox" name="lunara_hero_command_enabled" value="1" <?php checked( (bool) $settings['enabled'] ); ?> />
							<span>
								<strong><?php esc_html_e( 'Enable Hero Command', 'lunara-film' ); ?></strong>
								<small><?php esc_html_e( 'The curated deck below replaces the automatic feed — exact slides, exact order, no cap at six.', 'lunara-film' ); ?></small>
							</span>
						</label>

						<label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
							<span>
								<strong><?php esc_html_e( 'Overlay intensity', 'lunara-film' ); ?></strong>
								<small><?php esc_html_e( '100 is the shipped gradient. Lower it to lift the veil off the imagery; text shadow keeps titles readable.', 'lunara-film' ); ?></small>
							</span>
							<input type="range" min="20" max="100" step="5" value="<?php echo esc_attr( $settings['overlay'] ); ?>" data-lunara-brand-range />
							<span class="lunara-control-desk-brand-number-value">
								<input type="number" name="lunara_hero_command_overlay" min="20" max="100" step="5" value="<?php echo esc_attr( $settings['overlay'] ); ?>" data-lunara-brand-number />
								<em><?php esc_html_e( '%', 'lunara-film' ); ?></em>
							</span>
						</label>
					</div>
				</div>

				<div class="lunara-control-desk-homepage-card">
					<div class="lunara-control-desk-card-head">
						<div>
							<p class="lunara-control-desk-kicker"><?php esc_html_e( 'The Deck', 'lunara-film' ); ?></p>
							<h3><?php esc_html_e( 'Curated slides, top card leads', 'lunara-film' ); ?></h3>
							<p class="lunara-control-desk-subtle"><?php esc_html_e( 'Blank kicker or CTA falls back to the smart per-type default. Per-slide overlay of 0 inherits the global dial. A slide without a wide (landscape) image waits safely off-air until its artwork lands.', 'lunara-film' ); ?></p>
						</div>
					</div>

					<ol class="lunara-hero-command-deck" id="lunara-hero-command-deck">
						<?php foreach ( $deck as $slide_index => $entry ) : ?>
							<?php
							$deck_post = get_post( (int) $entry['post_id'] );
							if ( ! ( $deck_post instanceof WP_Post ) ) {
								continue;
							}
							$built    = function_exists( 'lunara_build_hero_slide_for_post' )
								? lunara_build_hero_slide_for_post( $deck_post->ID )
								: null;
							$is_live  = is_array( $built ) && ! empty( $built['image'] );
							$type_obj = get_post_type_object( $deck_post->post_type );
							?>
							<li class="lunara-hero-command-row" data-hero-row>
								<input type="hidden" name="lunara_hero_command_slides[<?php echo (int) $slide_index; ?>][post_id]" value="<?php echo (int) $deck_post->ID; ?>" data-hero-field="post_id" />
								<div class="lunara-hero-command-row-main">
									<span class="lunara-hero-command-order" data-hero-order><?php echo (int) $slide_index + 1; ?></span>
									<div class="lunara-hero-command-row-id">
										<strong><?php echo esc_html( get_the_title( $deck_post ) ); ?></strong>
										<small>
											<?php echo esc_html( $type_obj ? $type_obj->labels->singular_name : $deck_post->post_type ); ?>
											· <?php echo esc_html( get_the_date( '', $deck_post ) ); ?>
											<?php if ( $is_live ) : ?>
												<em class="lunara-hero-command-pill is-live"><?php esc_html_e( 'LIVE', 'lunara-film' ); ?></em>
											<?php else : ?>
												<em class="lunara-hero-command-pill is-waiting"><?php esc_html_e( 'Waiting — needs a wide image', 'lunara-film' ); ?></em>
											<?php endif; ?>
										</small>
									</div>
									<span class="lunara-hero-command-row-actions">
										<button type="button" class="button button-small" data-hero-move="up" aria-label="<?php esc_attr_e( 'Move up', 'lunara-film' ); ?>">&uarr;</button>
										<button type="button" class="button button-small" data-hero-move="down" aria-label="<?php esc_attr_e( 'Move down', 'lunara-film' ); ?>">&darr;</button>
										<button type="button" class="button button-small lunara-hero-command-remove" data-hero-remove><?php esc_html_e( 'Remove', 'lunara-film' ); ?></button>
									</span>
								</div>
								<div class="lunara-hero-command-row-fields">
									<label>
										<span><?php esc_html_e( 'Kicker', 'lunara-film' ); ?></span>
										<input type="text" name="lunara_hero_command_slides[<?php echo (int) $slide_index; ?>][kicker]" value="<?php echo esc_attr( $entry['kicker'] ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'Smart default', 'lunara-film' ); ?>" data-hero-field="kicker" />
									</label>
									<label>
										<span><?php esc_html_e( 'Call to action', 'lunara-film' ); ?></span>
										<input type="text" name="lunara_hero_command_slides[<?php echo (int) $slide_index; ?>][cta]" value="<?php echo esc_attr( $entry['cta'] ); ?>" maxlength="40" placeholder="<?php esc_attr_e( 'Smart default', 'lunara-film' ); ?>" data-hero-field="cta" />
									</label>
									<label class="lunara-hero-command-overlay-field">
										<span><?php esc_html_e( 'Overlay', 'lunara-film' ); ?></span>
										<input type="number" name="lunara_hero_command_slides[<?php echo (int) $slide_index; ?>][overlay]" value="<?php echo esc_attr( $entry['overlay'] > 0 ? $entry['overlay'] : '' ); ?>" min="20" max="100" step="5" placeholder="<?php esc_attr_e( 'Global', 'lunara-film' ); ?>" data-hero-field="overlay" />
									</label>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
					<p class="lunara-hero-command-empty" id="lunara-hero-command-empty" <?php echo empty( $deck ) ? '' : 'hidden'; ?>>
						<?php esc_html_e( 'The deck is empty. Search below to add the first slide.', 'lunara-film' ); ?>
					</p>

					<div class="lunara-hero-command-add">
						<label for="lunara-hero-command-search"><strong><?php esc_html_e( 'Add to the deck', 'lunara-film' ); ?></strong></label>
						<input type="search" id="lunara-hero-command-search" placeholder="<?php esc_attr_e( 'Search reviews, journal entries, essays, pages…', 'lunara-film' ); ?>" autocomplete="off" />
						<div class="lunara-hero-command-results" id="lunara-hero-command-results" hidden></div>
					</div>
				</div>

				<div class="lunara-control-desk-homepage-footer">
					<div>
						<strong><?php esc_html_e( 'The hero updates on save', 'lunara-film' ); ?></strong>
						<span><?php esc_html_e( 'The homepage cache is purged automatically so the new deck screens immediately.', 'lunara-film' ); ?></span>
					</div>
					<div class="lunara-control-desk-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Hero Command', 'lunara-film' ); ?></button>
						<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View the hero', 'lunara-film' ); ?></a>
					</div>
				</div>
			</form>

			<style>
				.lunara-hero-command-status-grid { display: grid; grid-template-columns: minmax(260px, 1fr) minmax(260px, 1fr); gap: 16px; align-items: start; }
				@media (max-width: 900px) { .lunara-hero-command-status-grid { grid-template-columns: 1fr; } }
				.lunara-hero-command-enable { display: flex; gap: 10px; align-items: flex-start; padding: 14px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; }
				.lunara-hero-command-enable.is-enabled { border-color: #2271b1; box-shadow: inset 3px 0 0 #2271b1; }
				.lunara-hero-command-enable small { display: block; color: #646970; margin-top: 2px; }
				.lunara-hero-command-deck { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
				.lunara-hero-command-row { border: 1px solid #dcdcde; border-radius: 8px; background: #fff; padding: 12px 14px; }
				.lunara-hero-command-row-main { display: flex; align-items: center; gap: 12px; }
				.lunara-hero-command-order { flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%; background: #1d2327; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; }
				.lunara-hero-command-row-id { flex: 1 1 auto; min-width: 0; }
				.lunara-hero-command-row-id strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
				.lunara-hero-command-row-id small { color: #646970; }
				.lunara-hero-command-pill { font-style: normal; font-size: 10px; font-weight: 700; letter-spacing: .04em; border-radius: 999px; padding: 2px 8px; margin-left: 6px; vertical-align: 1px; }
				.lunara-hero-command-pill.is-live { background: #edfaef; color: #007017; border: 1px solid #68de7c; }
				.lunara-hero-command-pill.is-waiting { background: #fcf9e8; color: #996800; border: 1px solid #f0c33c; }
				.lunara-hero-command-row-actions { flex: 0 0 auto; display: inline-flex; gap: 4px; }
				.lunara-hero-command-row-fields { display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 10px; margin-top: 10px; }
				@media (max-width: 900px) { .lunara-hero-command-row-fields { grid-template-columns: 1fr; } }
				.lunara-hero-command-row-fields label span { display: block; font-size: 11px; font-weight: 600; color: #646970; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
				.lunara-hero-command-row-fields input { width: 100%; }
				.lunara-hero-command-empty { color: #646970; font-style: italic; }
				.lunara-hero-command-add { margin-top: 16px; position: relative; }
				.lunara-hero-command-add label { display: block; margin-bottom: 4px; }
				.lunara-hero-command-add input[type="search"] { width: 100%; max-width: 520px; }
				.lunara-hero-command-results { position: absolute; z-index: 20; margin-top: 4px; width: 100%; max-width: 520px; max-height: 280px; overflow-y: auto; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.12); }
				.lunara-hero-command-results button { display: block; width: 100%; text-align: left; padding: 9px 12px; border: 0; border-bottom: 1px solid #f0f0f1; background: transparent; cursor: pointer; }
				.lunara-hero-command-results button:hover:not(:disabled) { background: #f6f7f7; }
				.lunara-hero-command-results button:disabled { opacity: .5; cursor: default; }
				.lunara-hero-command-results button strong { display: block; }
				.lunara-hero-command-results button small { color: #646970; }
			</style>

			<script>
			(function () {
				'use strict';

				var form    = document.getElementById('lunara-hero-command-form');
				var deck    = document.getElementById('lunara-hero-command-deck');
				var empty   = document.getElementById('lunara-hero-command-empty');
				var search  = document.getElementById('lunara-hero-command-search');
				var results = document.getElementById('lunara-hero-command-results');
				if (!form || !deck || !search || !results) {
					return;
				}

				var ajaxUrl   = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
				var ajaxNonce = <?php echo wp_json_encode( wp_create_nonce( 'lunara_hero_command' ) ); ?>;
				var i18n      = {
					live:    <?php echo wp_json_encode( __( 'LIVE', 'lunara-film' ) ); ?>,
					waiting: <?php echo wp_json_encode( __( 'Waiting — needs a wide image', 'lunara-film' ) ); ?>,
					inDeck:  <?php echo wp_json_encode( __( 'Already in the deck', 'lunara-film' ) ); ?>,
					none:    <?php echo wp_json_encode( __( 'No matches. Try another title.', 'lunara-film' ) ); ?>,
					kicker:  <?php echo wp_json_encode( __( 'Kicker', 'lunara-film' ) ); ?>,
					cta:     <?php echo wp_json_encode( __( 'Call to action', 'lunara-film' ) ); ?>,
					overlay: <?php echo wp_json_encode( __( 'Overlay', 'lunara-film' ) ); ?>,
					global:  <?php echo wp_json_encode( __( 'Global', 'lunara-film' ) ); ?>,
					smart:   <?php echo wp_json_encode( __( 'Smart default', 'lunara-film' ) ); ?>,
					remove:  <?php echo wp_json_encode( __( 'Remove', 'lunara-film' ) ); ?>
				};

				function deckIds() {
					return Array.prototype.map.call(
						deck.querySelectorAll('input[data-hero-field="post_id"]'),
						function (input) { return parseInt(input.value, 10); }
					);
				}

				function renumber() {
					var rows = deck.querySelectorAll('[data-hero-row]');
					Array.prototype.forEach.call(rows, function (row, index) {
						var order = row.querySelector('[data-hero-order]');
						if (order) { order.textContent = String(index + 1); }
						Array.prototype.forEach.call(row.querySelectorAll('[data-hero-field]'), function (input) {
							var field = input.getAttribute('data-hero-field');
							input.name = 'lunara_hero_command_slides[' + index + '][' + field + ']';
						});
					});
					if (empty) { empty.hidden = rows.length > 0; }
				}

				function makeField(labelText, field, opts) {
					var label = document.createElement('label');
					var span  = document.createElement('span');
					span.textContent = labelText;
					var input = document.createElement('input');
					input.type = opts.type || 'text';
					input.setAttribute('data-hero-field', field);
					if (opts.maxlength) { input.maxLength = opts.maxlength; }
					if (opts.min) { input.min = opts.min; }
					if (opts.max) { input.max = opts.max; }
					if (opts.step) { input.step = opts.step; }
					if (opts.placeholder) { input.placeholder = opts.placeholder; }
					if (opts.className) { label.className = opts.className; }
					label.appendChild(span);
					label.appendChild(input);
					return label;
				}

				function addRow(item) {
					var row = document.createElement('li');
					row.className = 'lunara-hero-command-row';
					row.setAttribute('data-hero-row', '');

					var hidden = document.createElement('input');
					hidden.type = 'hidden';
					hidden.value = String(item.id);
					hidden.setAttribute('data-hero-field', 'post_id');
					row.appendChild(hidden);

					var main = document.createElement('div');
					main.className = 'lunara-hero-command-row-main';

					var order = document.createElement('span');
					order.className = 'lunara-hero-command-order';
					order.setAttribute('data-hero-order', '');
					main.appendChild(order);

					var id = document.createElement('div');
					id.className = 'lunara-hero-command-row-id';
					var strong = document.createElement('strong');
					strong.textContent = item.title;
					var small = document.createElement('small');
					small.textContent = item.type + ' · ' + item.date + ' ';
					var pill = document.createElement('em');
					pill.className = 'lunara-hero-command-pill ' + (item.ready ? 'is-live' : 'is-waiting');
					pill.textContent = item.ready ? i18n.live : i18n.waiting;
					small.appendChild(pill);
					id.appendChild(strong);
					id.appendChild(small);
					main.appendChild(id);

					var actions = document.createElement('span');
					actions.className = 'lunara-hero-command-row-actions';
					[['up', '↑'], ['down', '↓']].forEach(function (move) {
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'button button-small';
						btn.setAttribute('data-hero-move', move[0]);
						btn.textContent = move[1];
						actions.appendChild(btn);
					});
					var removeBtn = document.createElement('button');
					removeBtn.type = 'button';
					removeBtn.className = 'button button-small lunara-hero-command-remove';
					removeBtn.setAttribute('data-hero-remove', '');
					removeBtn.textContent = i18n.remove;
					actions.appendChild(removeBtn);
					main.appendChild(actions);
					row.appendChild(main);

					var fields = document.createElement('div');
					fields.className = 'lunara-hero-command-row-fields';
					fields.appendChild(makeField(i18n.kicker, 'kicker', { maxlength: 60, placeholder: i18n.smart }));
					fields.appendChild(makeField(i18n.cta, 'cta', { maxlength: 40, placeholder: i18n.smart }));
					fields.appendChild(makeField(i18n.overlay, 'overlay', { type: 'number', min: 20, max: 100, step: 5, placeholder: i18n.global, className: 'lunara-hero-command-overlay-field' }));
					row.appendChild(fields);

					deck.appendChild(row);
					renumber();
				}

				deck.addEventListener('click', function (event) {
					var target = event.target;
					if (target.hasAttribute('data-hero-remove')) {
						var row = target.closest('[data-hero-row]');
						if (row) { row.remove(); renumber(); }
						return;
					}
					if (target.hasAttribute('data-hero-move')) {
						var moveRow = target.closest('[data-hero-row]');
						if (!moveRow) { return; }
						if (target.getAttribute('data-hero-move') === 'up' && moveRow.previousElementSibling) {
							moveRow.parentNode.insertBefore(moveRow, moveRow.previousElementSibling);
						} else if (target.getAttribute('data-hero-move') === 'down' && moveRow.nextElementSibling) {
							moveRow.parentNode.insertBefore(moveRow.nextElementSibling, moveRow);
						}
						renumber();
					}
				});

				var searchTimer = null;
				search.addEventListener('input', function () {
					window.clearTimeout(searchTimer);
					var term = search.value.trim();
					if (term.length < 2) {
						results.hidden = true;
						results.textContent = '';
						return;
					}
					searchTimer = window.setTimeout(function () {
						var body = new window.FormData();
						body.append('action', 'lunara_hero_command_search');
						body.append('nonce', ajaxNonce);
						body.append('term', term);
						window.fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
							.then(function (response) { return response.json(); })
							.then(function (payload) {
								results.textContent = '';
								var items = (payload && payload.success && payload.data) ? payload.data : [];
								if (!items.length) {
									var none = document.createElement('button');
									none.type = 'button';
									none.disabled = true;
									none.textContent = i18n.none;
									results.appendChild(none);
								}
								var current = deckIds();
								items.forEach(function (item) {
									var btn = document.createElement('button');
									btn.type = 'button';
									var strong = document.createElement('strong');
									strong.textContent = item.title;
									var small = document.createElement('small');
									var inDeck = current.indexOf(item.id) !== -1;
									small.textContent = item.type + ' · ' + item.date
										+ ' · ' + (inDeck ? i18n.inDeck : (item.ready ? i18n.live : i18n.waiting));
									btn.appendChild(strong);
									btn.appendChild(small);
									if (inDeck) { btn.disabled = true; }
									btn.addEventListener('click', function () {
										addRow(item);
										results.hidden = true;
										results.textContent = '';
										search.value = '';
										search.focus();
									});
									results.appendChild(btn);
								});
								results.hidden = false;
							})
							.catch(function () {
								results.hidden = true;
							});
					}, 280);
				});

				document.addEventListener('click', function (event) {
					if (!results.hidden && !results.contains(event.target) && event.target !== search) {
						results.hidden = true;
					}
				});

				renumber();
			})();
			</script>
		</section>
		<?php
	}
}
