<?php
/**
 * Header Command — the Lunara header and §9 off-canvas navigation.
 *
 * The staged Blocksy exit: when the takeover switch is ON, the parent
 * theme's header is hidden and Lunara's own header bar mounts — brand,
 * primary routes, an ordinary search trigger, and an off-canvas
 * navigation panel with deep blur
 * gradients and soft gold hairlines, fully keyboard-accessible.
 *
 * The switch (option: lunara_header_takeover) defaults OFF so a deploy
 * never changes the served header sight-unseen; it is flipped from the
 * Control Desk. Once the takeover has been inspected live and approved,
 * the parent-theme Template flip becomes a one-line follow-up.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_header_takeover_enabled' ) ) {
	function lunara_header_takeover_enabled() {
		// Standalone theme (no Blocksy parent): the Lunara header IS the
		// header — there is nothing to fall back to, so the switch is moot.
		if ( ! wp_get_theme()->parent() ) {
			return true;
		}
		return (bool) apply_filters( 'lunara_header_takeover_enabled', (bool) get_option( 'lunara_header_takeover', false ) );
	}
}

if ( ! function_exists( 'lunara_header_command_rocket_exclusions' ) ) {
	/**
	 * Keep WP Rocket's unused-CSS pass away from the header/off-canvas CSS.
	 * Without this, the hide-Blocksy rule and the (crawl-time hidden)
	 * off-canvas styles get stripped — the "floating header" seen on Edge.
	 */
	function lunara_header_command_rocket_exclusions( $exclusions ) {
		$exclusions = is_array( $exclusions ) ? $exclusions : array();
		$exclusions[] = 'lunara-header-command';
		return $exclusions;
	}
	add_filter( 'rocket_rucss_inline_content_exclusions', 'lunara_header_command_rocket_exclusions' );
	add_filter( 'rocket_rucss_inline_atts_exclusions', 'lunara_header_command_rocket_exclusions' );
}

if ( ! function_exists( 'lunara_header_nav_links' ) ) {
	/**
	 * Primary routes. A menu assigned to the 'lunara-header' location wins;
	 * otherwise this curated map (gated on what actually exists) is used.
	 */
	function lunara_header_nav_links() {
		$links = array(
			array( 'label' => __( 'Home', 'lunara-film' ), 'url' => home_url( '/' ) ),
			array( 'label' => __( 'Reviews', 'lunara-film' ), 'url' => home_url( '/reviews/' ) ),
			array( 'label' => __( 'Journal', 'lunara-film' ), 'url' => home_url( '/journal/' ) ),
			array( 'label' => __( 'Oscars', 'lunara-film' ), 'url' => home_url( '/oscars/' ) ),
		);

		if ( post_type_exists( 'movie' ) ) {
			$links[] = array( 'label' => __( 'Film Index', 'lunara-film' ), 'url' => home_url( '/film/' ) );
		}
		if ( post_type_exists( 'person' ) ) {
			$links[] = array( 'label' => __( 'Talent', 'lunara-film' ), 'url' => home_url( '/talent/' ) );
		}

		return (array) apply_filters( 'lunara_header_nav_links', $links );
	}
}

if ( ! function_exists( 'lunara_header_register_menu_location' ) ) {
	function lunara_header_register_menu_location() {
		register_nav_menus( array( 'lunara-header' => __( 'Lunara Header', 'lunara-film' ) ) );
	}
	add_action( 'after_setup_theme', 'lunara_header_register_menu_location', 20 );
}

if ( ! function_exists( 'lunara_header_body_class' ) ) {
	function lunara_header_body_class( $classes ) {
		if ( lunara_header_takeover_enabled() ) {
			$classes[] = 'lunara-header-takeover';
		}
		return $classes;
	}
	add_filter( 'body_class', 'lunara_header_body_class' );
}

if ( ! function_exists( 'lunara_header_nav_markup' ) ) {
	function lunara_header_nav_markup( $context ) {
		if ( has_nav_menu( 'lunara-header' ) ) {
			return (string) wp_nav_menu(
				array(
					'theme_location' => 'lunara-header',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'echo'           => false,
					'fallback_cb'    => false,
				)
			);
		}

		$out = '';
		foreach ( lunara_header_nav_links() as $link ) {
			if ( empty( $link['label'] ) || empty( $link['url'] ) ) {
				continue;
			}
			$out .= sprintf(
				'<a class="lunara-%s-link" href="%s">%s</a>',
				esc_attr( $context ),
				esc_url( $link['url'] ),
				esc_html( $link['label'] )
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'lunara_header_brand_markup' ) ) {
	function lunara_header_brand_markup() {
		$logo_id = (int) get_theme_mod( 'custom_logo' );

		if ( $logo_id > 0 ) {
			$source = (string) wp_get_attachment_image_url( $logo_id, 'full' );

			if ( '' !== $source ) {
				$logo_420 = function_exists( 'lunara_resize_wpcom_image_url' )
					? lunara_resize_wpcom_image_url( $source, 420, 236 )
					: $source;
				$logo_840 = function_exists( 'lunara_resize_wpcom_image_url' )
					? lunara_resize_wpcom_image_url( $source, 840, 472 )
					: '';
				$alt      = trim( (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) );

				if ( '' === $alt ) {
					$site_name = trim( (string) get_bloginfo( 'name' ) );
					$alt       = sprintf( __( '%s logo', 'lunara-film' ), '' !== $site_name ? $site_name : 'Lunara Film' );
				}

				$srcset = '';
				if ( '' !== $logo_840 && $logo_840 !== $logo_420 ) {
					$srcset = sprintf(
						' srcset="%s 420w, %s 840w"',
						esc_url( $logo_420 ),
						esc_url( $logo_840 )
					);
				}
				$fetchpriority = function_exists( 'lunara_custom_logo_fetch_priority' )
					? lunara_custom_logo_fetch_priority()
					: ( is_front_page() ? 'auto' : 'high' );

				return sprintf(
					'<img class="lunara-header-logo skip-lazy no-lazy" src="%1$s"%2$s sizes="(max-width: 720px) 96px, 150px" width="420" height="236" alt="%3$s" loading="eager" decoding="async" fetchpriority="%4$s" data-no-lazy="1" data-skip-lazy="1">',
					esc_url( $logo_420 ),
					$srcset, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_attr( $alt ),
					esc_attr( $fetchpriority )
				);
			}
		}

		return '<span class="lunara-header-brand-word">Lunara</span><span class="lunara-header-brand-sub">Film</span>';
	}
}

if ( ! function_exists( 'lunara_render_header_command' ) ) {
	function lunara_render_header_command() {
		if ( ! lunara_header_takeover_enabled() || is_admin() ) {
			return;
		}
		?>
		<header class="lunara-header" data-lunara-header>
			<div class="lunara-header-inner">
				<a class="lunara-header-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<?php echo lunara_header_brand_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<nav class="lunara-header-nav" aria-label="<?php esc_attr_e( 'Primary', 'lunara-film' ); ?>">
					<?php echo lunara_header_nav_markup( 'header-nav' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</nav>
				<div class="lunara-header-actions">
					<button type="button" class="lunara-header-search" data-lunara-search-open aria-label="<?php esc_attr_e( 'Search', 'lunara-film' ); ?>" title="<?php esc_attr_e( 'Search', 'lunara-film' ); ?>">
						<svg class="lunara-header-search-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<circle cx="11" cy="11" r="8"></circle>
							<path d="m21 21-4.3-4.3"></path>
						</svg>
					</button>
					<button type="button" class="lunara-header-burger" data-lunara-nav-open aria-expanded="false" aria-controls="lunara-offcanvas" aria-label="<?php esc_attr_e( 'Open navigation', 'lunara-film' ); ?>">
						<span aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</header>
		<?php
	}
	add_action( 'wp_body_open', 'lunara_render_header_command', 5 );
}

if ( ! function_exists( 'lunara_render_offcanvas_nav' ) ) {
	function lunara_render_offcanvas_nav() {
		if ( ! lunara_header_takeover_enabled() || is_admin() ) {
			return;
		}
		?>
		<div class="lunara-offcanvas" id="lunara-offcanvas" hidden>
			<div class="lunara-offcanvas-veil" data-lunara-nav-close aria-hidden="true"></div>
			<aside class="lunara-offcanvas-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site navigation', 'lunara-film' ); ?>">
				<button type="button" class="lunara-offcanvas-close" data-lunara-nav-close aria-label="<?php esc_attr_e( 'Close navigation', 'lunara-film' ); ?>">&times;</button>
				<p class="lunara-offcanvas-kicker"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></p>
				<nav class="lunara-offcanvas-nav" aria-label="<?php esc_attr_e( 'Site navigation', 'lunara-film' ); ?>">
					<?php echo lunara_header_nav_markup( 'offcanvas' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</nav>
				<p class="lunara-offcanvas-foot"><?php esc_html_e( 'Reviews, Journal files, and the Oscar Ledger — one publication in motion.', 'lunara-film' ); ?></p>
			</aside>
		</div>
		<?php
	}
	add_action( 'wp_footer', 'lunara_render_offcanvas_nav', 42 );
}

if ( ! function_exists( 'lunara_header_command_assets' ) ) {
	function lunara_header_command_assets() {
		if ( ! lunara_header_takeover_enabled() || is_admin() ) {
			return;
		}

		$path = get_stylesheet_directory() . '/assets/js/lunara-header.js';
		wp_enqueue_script(
			'lunara-header',
			get_stylesheet_directory_uri() . '/assets/js/lunara-header.js',
			array(),
			file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' ),
			true
		);
	}
	add_action( 'wp_enqueue_scripts', 'lunara_header_command_assets', 20 );
}

if ( ! function_exists( 'lunara_header_command_css' ) ) {
	/**
	 * Header + off-canvas CSS, printed only when the takeover is live so
	 * the OFF state ships zero bytes of difference.
	 */
	function lunara_header_command_css() {
		if ( ! lunara_header_takeover_enabled() || is_admin() ) {
			return;
		}
		?>
		<style id="lunara-header-command-css">/*lunara-header-command*/
		body.lunara-header-takeover header.ct-header,
		body.lunara-header-takeover [data-header],
		body.lunara-header-takeover #header.ct-header { display: none !important; }
		/* Kill the zombie Blocksy drawer some plugin still prints after the
		   parent-theme exit (inert #offcanvas.ct-panel + its canvas). This
		   block is Rocket-excluded, so the kill survives unused-CSS passes. */
		body.lunara-header-takeover .ct-drawer-canvas,
		body.lunara-header-takeover #offcanvas.ct-panel,
		body.lunara-header-takeover #search-modal { display: none !important; visibility: hidden !important; }
		body.lunara-header-takeover { padding-top: 88px; }
		body.lunara-header-takeover.admin-bar .lunara-header { top: 32px; }
		.lunara-header {
			position: fixed; top: 0; left: 0; right: 0; z-index: 980;
			background: linear-gradient(180deg, rgba(10,21,32,.94), rgba(10,21,32,.82));
			-webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
			border-bottom: 1px solid rgba(201,169,97,.24);
		}
		.lunara-header-inner {
			display: flex; align-items: center; gap: 26px;
			max-width: var(--lunara-shell-max, 1360px); height: 88px;
			margin: 0 auto; padding: 0 var(--lunara-shell-pad, 28px);
		}
		.lunara-header-brand {
			display: inline-flex; align-items: center; gap: 7px;
			flex: 0 0 auto; min-width: clamp(205px, 16vw, 260px);
			overflow: hidden; text-decoration: none;
		}
		.lunara-header-logo {
			display: block; width: clamp(205px, 16vw, 248px); height: 64px;
			max-width: none; object-fit: cover; object-position: center;
		}
		.lunara-header-brand-word {
			color: var(--lunara-gold, #c9a961);
			font-family: var(--lunara-font-display, Georgia, serif);
			font-size: 1.32rem; letter-spacing: .04em;
		}
		.lunara-header-brand-sub {
			color: rgba(244,239,227,.62);
			font-family: var(--lunara-font-label, sans-serif);
			font-size: .72rem; letter-spacing: .3em; text-transform: uppercase;
		}
		.lunara-header-nav { display: flex; align-items: center; gap: 22px; margin-left: 8px; }
		.lunara-header-nav ul, .lunara-header-nav li,
		.lunara-offcanvas-nav ul, .lunara-offcanvas-nav li {
			list-style: none; margin: 0; padding: 0; display: block;
		}
		.lunara-header-nav li { display: inline-flex; }
		.lunara-offcanvas-nav a { display: block; }
		.lunara-header-nav a, .lunara-header-nav-link {
			color: rgba(244,239,227,.82); text-decoration: none;
			font-family: var(--lunara-font-label, sans-serif);
			font-size: .8rem; letter-spacing: .14em; text-transform: uppercase;
			padding: 8px 2px; border-bottom: 1px solid transparent;
			transition: color .18s ease, border-color .18s ease;
		}
		.lunara-header-nav a:hover, .lunara-header-nav a:focus-visible {
			color: var(--lunara-gold-light, #e0c481); border-bottom-color: rgba(201,169,97,.55);
		}
		.lunara-header-actions { display: flex; align-items: center; gap: 12px; margin-left: auto; }
		.lunara-header-search {
			display: inline-grid; place-items: center; flex: 0 0 42px;
			width: 42px; height: 42px; padding: 0; cursor: pointer;
			border: 1px solid rgba(201,169,97,.32); border-radius: 999px;
			background: rgba(7,15,24,.58); color: rgba(224,196,129,.92);
			box-shadow: inset 0 0 0 1px rgba(255,255,255,.015);
			transition: color .18s ease, border-color .18s ease, background-color .18s ease, transform .18s ease, box-shadow .18s ease;
		}
		.lunara-header-search:hover {
			color: var(--lunara-gold-light, #e0c481);
			border-color: rgba(224,196,129,.62);
			background: rgba(201,169,97,.1);
			box-shadow: 0 8px 24px rgba(0,0,0,.2);
			transform: translateY(-1px);
		}
		.lunara-header-search:focus-visible {
			outline: 2px solid var(--lunara-gold-light, #e0c481);
			outline-offset: 3px;
		}
		.lunara-header-search-icon {
			display: block; width: 18px; height: 18px;
			fill: none; stroke: currentColor; stroke-width: 1.75;
			stroke-linecap: round; stroke-linejoin: round;
		}
		.lunara-header-burger {
			position: relative; width: 42px; height: 42px; cursor: pointer;
			border: 1px solid rgba(201,169,97,.3); border-radius: 999px;
			background: rgba(244,239,227,.05);
		}
		.lunara-header-burger span, .lunara-header-burger span::before, .lunara-header-burger span::after {
			content: ""; position: absolute; left: 12px; right: 12px; height: 1.5px;
			background: rgba(224,196,129,.92); transition: transform .2s ease;
		}
		.lunara-header-burger span { top: 50%; transform: translateY(-4px); }
		.lunara-header-burger span::before { left: 0; right: 0; transform: translateY(7px); }
		.lunara-header-burger span::after { display: none; }
		@media (max-width: 1020px) {
			.lunara-header-nav { display: none; }
			body.lunara-header-takeover { padding-top: 68px; }
			.lunara-header-inner { height: 68px; gap: 14px; }
			.lunara-header-brand { min-width: 134px; }
			.lunara-header-logo { width: 134px; height: 48px; }
		}
		/* --- §9 off-canvas panel ------------------------------------ */
		.lunara-offcanvas { position: fixed; inset: 0; z-index: 990; }
		.lunara-offcanvas-veil {
			position: absolute; inset: 0; background: rgba(4,10,18,.6);
			-webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
			opacity: 0; transition: opacity .24s ease;
		}
		.lunara-offcanvas-panel {
			position: absolute; top: 0; right: 0; bottom: 0;
			width: min(420px, 92vw); padding: 84px 40px 40px;
			display: flex; flex-direction: column;
			background:
				radial-gradient(circle at top right, rgba(201,169,97,.14), transparent 42%),
				linear-gradient(200deg, rgba(15,29,46,.97), rgba(6,13,21,.99));
			-webkit-backdrop-filter: blur(18px); backdrop-filter: blur(18px);
			border-left: 1px solid rgba(201,169,97,.3);
			box-shadow: -40px 0 90px rgba(0,0,0,.5);
			transform: translateX(100%); transition: transform .3s cubic-bezier(.2,.8,.2,1);
		}
		.lunara-offcanvas.is-open .lunara-offcanvas-veil { opacity: 1; }
		.lunara-offcanvas.is-open .lunara-offcanvas-panel { transform: none; }
		.lunara-offcanvas-close {
			position: absolute; top: 22px; right: 26px; width: 42px; height: 42px;
			cursor: pointer; border: 1px solid rgba(201,169,97,.32); border-radius: 999px;
			background: transparent; color: rgba(224,196,129,.92); font-size: 1.3rem; line-height: 1;
		}
		.lunara-offcanvas-kicker {
			margin: 0 0 18px; color: var(--lunara-gold, #c9a961);
			font-family: var(--lunara-font-label, sans-serif);
			font-size: .72rem; letter-spacing: .3em; text-transform: uppercase;
		}
		.lunara-offcanvas-nav { display: flex; flex-direction: column; }
		.lunara-offcanvas-nav a, .lunara-offcanvas-link {
			padding: 15px 0; text-decoration: none;
			color: rgba(244,239,227,.9);
			font-family: var(--lunara-font-display, Georgia, serif);
			font-size: clamp(1.5rem, 4.4vw, 1.9rem); line-height: 1.15;
			border-bottom: 1px solid rgba(201,169,97,.18);
			transition: color .18s ease, padding-left .18s ease;
		}
		.lunara-offcanvas-nav a:hover, .lunara-offcanvas-nav a:focus-visible {
			color: var(--lunara-gold-light, #e0c481); padding-left: 8px;
		}
		.lunara-offcanvas-foot {
			margin: auto 0 0; padding-top: 26px;
			color: rgba(244,239,227,.5);
			font-family: var(--lunara-font-body, Georgia, serif);
			font-size: .88rem; line-height: 1.55; font-style: italic;
		}
		@media (prefers-reduced-motion: reduce) {
			.lunara-offcanvas-veil, .lunara-offcanvas-panel,
			.lunara-offcanvas-nav a, .lunara-header-nav a { transition: none; }
		}
		</style>
		<?php
	}
	add_action( 'wp_head', 'lunara_header_command_css', 48 );
}

if ( ! function_exists( 'lunara_header_takeover_toggle_handler' ) ) {
	/**
	 * Control Desk switch: flip the takeover on or off.
	 */
	function lunara_header_takeover_toggle_handler() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'lunara_header_takeover_toggle' ) ) {
			wp_die( esc_html__( 'Header takeover toggle rejected.', 'lunara-film' ) );
		}
		update_option( 'lunara_header_takeover', empty( get_option( 'lunara_header_takeover' ) ) ? 1 : 0, true );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		$target = function_exists( 'lunara_control_desk_admin_url' )
			? lunara_control_desk_admin_url( array( 'tab' => 'system-status' ) )
			: admin_url( 'admin.php?page=lunara-control-desk' );
		wp_safe_redirect( $target );
		exit;
	}
	add_action( 'admin_post_lunara_header_takeover_toggle', 'lunara_header_takeover_toggle_handler' );
}

if ( ! function_exists( 'lunara_header_takeover_render_switch_panel' ) ) {
	/**
	 * The Control Desk panel (rendered from the System Status tab).
	 */
	function lunara_header_takeover_render_switch_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$on = lunara_header_takeover_enabled();
		?>
		<section class="lunara-control-desk-panel">
			<div class="lunara-control-desk-panel-header">
				<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Header Command', 'lunara-film' ); ?></p>
				<h2><?php esc_html_e( 'The Lunara header and off-canvas navigation', 'lunara-film' ); ?></h2>
				<p class="lunara-control-desk-intro"><?php esc_html_e( 'The staged parent-theme exit. When on, the Blocksy header is replaced by the Lunara header bar and the deep-blur off-canvas navigation. Flip it, inspect the live site, and the Template flip becomes the approved follow-up.', 'lunara-film' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lunara_header_takeover_toggle" />
				<?php wp_nonce_field( 'lunara_header_takeover_toggle' ); ?>
				<button type="submit" class="button <?php echo $on ? '' : 'button-primary'; ?>">
					<?php $on ? esc_html_e( 'Takeover is ON — switch back to the Blocksy header', 'lunara-film' ) : esc_html_e( 'Enable the Lunara header takeover', 'lunara-film' ); ?>
				</button>
			</form>
		</section>
		<?php
	}
}
