<?php
/**
 * Cinematic homepage front-door bridge.
 *
 * This keeps plugin-backed hero experiments behind explicit gates while the
 * homepage migrates away from shortcode-controlled structural composition.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a one-off front-door preview is requested.
 *
 * The query flag is intentionally read-only so live content can be visually QA'd
 * before the permanent theme mod is enabled.
 */
function lunara_home_cinematic_front_door_preview_is_enabled() {
	if ( is_admin() || empty( $_GET['lunara_preview'] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only visual preview flag.
	$preview = sanitize_key( wp_unslash( $_GET['lunara_preview'] ) );

	return in_array( $preview, array( 'front-door', 'front_door', 'cinematic-home' ), true );
}

/**
 * Whether the current homepage front door should be replaced by the cinematic
 * wrapper. Defaults off so the branch can ship without surprising production.
 */
function lunara_home_cinematic_front_door_is_enabled() {
	$enabled = (bool) get_theme_mod( 'lunara_home_cinematic_front_door_enabled', false );

	if ( ! $enabled && lunara_home_cinematic_front_door_preview_is_enabled() ) {
		$enabled = true;
	}

	return (bool) apply_filters( 'lunara_home_cinematic_front_door_enabled', $enabled );
}

/**
 * Whether a third-party slider shortcode is allowed to render the front door.
 */
function lunara_home_plugin_hero_is_allowed() {
	$enabled = (bool) get_theme_mod( 'lunara_home_plugin_hero_enabled', false );

	return (bool) apply_filters( 'lunara_home_plugin_hero_enabled', $enabled );
}

/**
 * Read the configured Responsive Zoom In/Out slider shortcode.
 */
function lunara_home_plugin_hero_shortcode() {
	$shortcode = trim( (string) get_theme_mod( 'lunara_home_plugin_hero_shortcode', '' ) );

	return trim( (string) apply_filters( 'lunara_home_plugin_hero_shortcode', $shortcode ) );
}

/**
 * Extract the shortcode tag from a shortcode string.
 *
 * @param string $shortcode Shortcode markup.
 * @return string
 */
function lunara_home_extract_shortcode_tag( $shortcode ) {
	if ( ! is_string( $shortcode ) || '' === trim( $shortcode ) ) {
		return '';
	}

	if ( preg_match( '/^\s*\[([A-Za-z0-9_-]+)/', $shortcode, $matches ) ) {
		return sanitize_key( $matches[1] );
	}

	return '';
}

/**
 * Render the native theme hero fallback inside the front-door wrapper.
 */
function lunara_render_native_home_hero_fallback() {
	if ( ! function_exists( 'lunara_render_cinematic_hero_carousel' ) ) {
		return '';
	}

	$fallback = trim(
		(string) lunara_render_cinematic_hero_carousel(
			array(
				'context' => 'home-front-door',
			)
		)
	);

	if ( '' === $fallback ) {
		return '';
	}

	return '<div class="lunara-home-cinematic-front-door is-native-fallback" data-lunara-home-hero-source="native">' . $fallback . '</div>';
}

/**
 * Render the Lunara wordmark for the cinematic native front door.
 */
function lunara_render_cinematic_home_wordmark() {
	$logo_id = function_exists( 'lunara_get_home_identity_logo_id' ) ? absint( lunara_get_home_identity_logo_id() ) : 0;

	if ( $logo_id ) {
		return wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'lunara-home-cinematic-wordmark skip-lazy no-lazy',
				'loading'       => 'eager',
				'decoding'      => 'async',
				'fetchpriority' => 'high',
				'alt'           => '',
			)
		);
	}

	return '<span class="lunara-home-cinematic-wordmark-fallback">' . esc_html__( 'Lunara Film', 'lunara-film' ) . '</span>';
}

/**
 * Route links used by the native cinematic homepage front door.
 *
 * @return array<int,array<string,string>>
 */
function lunara_get_cinematic_home_routes() {
	$reviews_url = get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' );
	$journal_url = get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' );

	return array(
		array(
			'label' => __( 'Reviews', 'lunara-film' ),
			'title' => __( 'Criticism', 'lunara-film' ),
			'url'   => $reviews_url,
		),
		array(
			'label' => __( 'Journal', 'lunara-film' ),
			'title' => __( 'Desk Files', 'lunara-film' ),
			'url'   => $journal_url,
		),
		array(
			'label' => __( 'Oscars', 'lunara-film' ),
			'title' => __( 'Ledger', 'lunara-film' ),
			'url'   => home_url( '/oscars/' ),
		),
		array(
			'label' => __( 'Search', 'lunara-film' ),
			'title' => __( 'Command', 'lunara-film' ),
			'url'   => function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/search/' ),
		),
	);
}

/**
 * Render the native, URL-previewable cinematic editorial front door.
 */
function lunara_render_native_cinematic_home_front_door() {
	$lead = function_exists( 'lunara_get_home_front_door_lead' )
		? lunara_get_home_front_door_lead()
		: array(
			'kicker'  => __( 'Lead Editorial', 'lunara-film' ),
			'title'   => get_bloginfo( 'name' ),
			'excerpt' => get_bloginfo( 'description' ),
			'url'     => home_url( '/' ),
			'cta'     => __( 'Enter Lunara', 'lunara-film' ),
			'image'   => '',
		);
	$journal     = function_exists( 'lunara_get_home_front_door_journal_signal' ) ? lunara_get_home_front_door_journal_signal() : array();
	$oscar       = function_exists( 'lunara_get_home_front_door_oscar_signal' ) ? lunara_get_home_front_door_oscar_signal() : array();
	$search_url  = function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/search/' );
	$search_name = function_exists( 'lunara_search_command_url' ) ? 'q' : 's';
	$lead_image  = ! empty( $lead['image'] ) ? trim( (string) $lead['image'] ) : '';
	$routes      = lunara_get_cinematic_home_routes();

	ob_start();
	?>
	<section class="lunara-home-cinematic-front-door lunara-home-cinematic-front-desk is-native-desk<?php echo $lead_image ? ' has-image' : ' has-no-image'; ?>" data-lunara-home-hero-source="native-front-desk" aria-labelledby="lunara-home-cinematic-title">
		<div class="lunara-home-cinematic-stage">
			<?php if ( $lead_image ) : ?>
				<img class="lunara-home-cinematic-backdrop skip-lazy no-lazy" src="<?php echo esc_url( $lead_image ); ?>" alt="" loading="eager" decoding="async" fetchpriority="high" />
			<?php endif; ?>
			<div class="lunara-home-cinematic-scrim" aria-hidden="true"></div>

			<div class="lunara-home-cinematic-shell">
				<div class="lunara-home-cinematic-brand">
					<p class="lunara-home-cinematic-kicker"><?php esc_html_e( 'Live editorial front desk', 'lunara-film' ); ?></p>
					<h1 id="lunara-home-cinematic-title" class="screen-reader-text lunara-screen-reader-text"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></h1>
					<div class="lunara-home-cinematic-wordmark-frame" aria-hidden="true">
						<?php echo lunara_render_cinematic_home_wordmark(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<div class="lunara-home-cinematic-copy">
					<a class="lunara-home-cinematic-lead" href="<?php echo esc_url( $lead['url'] ?? home_url( '/' ) ); ?>">
						<span class="lunara-home-cinematic-label"><?php echo esc_html( $lead['kicker'] ?? __( 'Lead Editorial', 'lunara-film' ) ); ?></span>
						<h2><?php echo esc_html( $lead['title'] ?? get_bloginfo( 'name' ) ); ?></h2>
						<?php if ( ! empty( $lead['excerpt'] ) ) : ?>
							<p><?php echo esc_html( $lead['excerpt'] ); ?></p>
						<?php endif; ?>
						<span class="lunara-home-cinematic-cta"><?php echo esc_html( $lead['cta'] ?? __( 'Read the file', 'lunara-film' ) ); ?> <span aria-hidden="true">&rarr;</span></span>
					</a>
				</div>

				<aside class="lunara-home-cinematic-signal-rail" aria-label="<?php esc_attr_e( 'Current Lunara signals', 'lunara-film' ); ?>">
					<a class="lunara-home-cinematic-signal is-journal" href="<?php echo esc_url( $journal['url'] ?? home_url( '/journal/' ) ); ?>">
						<span class="lunara-home-cinematic-label"><?php echo esc_html( $journal['label'] ?? __( 'Journal Pulse', 'lunara-film' ) ); ?></span>
						<strong><?php echo esc_html( $journal['title'] ?? __( 'Open the Journal desk', 'lunara-film' ) ); ?></strong>
						<span><?php echo esc_html( $journal['meta'] ?? __( 'Latest files from the desk', 'lunara-film' ) ); ?></span>
					</a>

					<a class="lunara-home-cinematic-signal is-ledger" href="<?php echo esc_url( $oscar['url'] ?? home_url( '/oscars/' ) ); ?>">
						<span class="lunara-home-cinematic-label"><?php echo esc_html( $oscar['label'] ?? __( 'Oscar Ledger', 'lunara-film' ) ); ?></span>
						<strong><?php echo esc_html( $oscar['title'] ?? __( 'Explore the Oscar record', 'lunara-film' ) ); ?></strong>
						<span><?php echo esc_html( $oscar['meta'] ?? __( 'Films, people, categories, and ceremonies', 'lunara-film' ) ); ?></span>
					</a>

					<form role="search" method="get" class="lunara-home-cinematic-search" action="<?php echo esc_url( $search_url ); ?>">
						<label class="lunara-home-cinematic-label" for="lunara-home-cinematic-search-input"><?php esc_html_e( 'Search Command', 'lunara-film' ); ?></label>
						<div class="lunara-home-cinematic-search-row">
							<input id="lunara-home-cinematic-search-input" type="search" name="<?php echo esc_attr( $search_name ); ?>" placeholder="<?php esc_attr_e( 'Film, critic, category, person', 'lunara-film' ); ?>" />
							<button type="submit"><?php esc_html_e( 'Run', 'lunara-film' ); ?></button>
						</div>
					</form>
				</aside>

				<nav class="lunara-home-cinematic-routes" aria-label="<?php esc_attr_e( 'Lunara section doors', 'lunara-film' ); ?>">
					<?php foreach ( $routes as $route ) : ?>
						<a href="<?php echo esc_url( $route['url'] ); ?>">
							<span><?php echo esc_html( $route['label'] ); ?></span>
							<strong><?php echo esc_html( $route['title'] ); ?></strong>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the guarded plugin-backed hero wrapper.
 */
function lunara_render_plugin_backed_home_hero() {
	if ( ! lunara_home_cinematic_front_door_is_enabled() ) {
		return '';
	}

	$shortcode     = lunara_home_plugin_hero_shortcode();
	$shortcode_tag = lunara_home_extract_shortcode_tag( $shortcode );

	if (
		lunara_home_plugin_hero_is_allowed()
		&& '' !== $shortcode
		&& '' !== $shortcode_tag
		&& shortcode_exists( $shortcode_tag )
	) {
		ob_start();
		?>
		<section class="lunara-home-cinematic-front-door lunara-home-plugin-hero is-plugin-backed" data-lunara-home-hero-source="plugin" aria-label="<?php esc_attr_e( 'Featured Lunara Film stories', 'lunara-film' ); ?>">
			<div class="lunara-home-plugin-hero-frame">
				<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme setting gated by filter/theme mod. ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	return lunara_render_native_cinematic_home_front_door();
}

/**
 * Add homepage state classes for visual QA and targeted CSS.
 *
 * @param array $classes Body classes.
 * @return array
 */
function lunara_cinematic_home_body_classes( $classes ) {
	if ( is_front_page() && lunara_home_cinematic_front_door_is_enabled() ) {
		$classes[] = 'lunara-home-cinematic-front-door-enabled';

		if ( lunara_home_plugin_hero_is_allowed() ) {
			$classes[] = 'lunara-home-plugin-hero-enabled';
		}

		if ( lunara_home_cinematic_front_door_preview_is_enabled() ) {
			$classes[] = 'lunara-home-cinematic-front-door-preview';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'lunara_cinematic_home_body_classes' );

/**
 * Front-page cinematic polish shared by the guarded hero and lower homepage.
 */
function lunara_enqueue_cinematic_home_assets() {
	if ( is_admin() || is_feed() || ! is_front_page() ) {
		return;
	}

	$css = lunara_resolve_theme_asset(
		'assets/css/lunara-cinematic-home.css',
		array( 'assets/css/lunara-cinematic-home.css' )
	);
	if ( $css['uri'] ) {
		wp_enqueue_style(
			'lunara-cinematic-home',
			$css['uri'],
			array(),
			lunara_theme_asset_version( $css['path'] )
		);
	}

	$js = lunara_resolve_theme_asset(
		'assets/js/lunara-cinematic-home.js',
		array( 'assets/js/lunara-cinematic-home.js' )
	);
	if ( $js['uri'] ) {
		wp_enqueue_script(
			'lunara-cinematic-home',
			$js['uri'],
			array(),
			lunara_theme_asset_version( $js['path'] ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_cinematic_home_assets', 25 );
