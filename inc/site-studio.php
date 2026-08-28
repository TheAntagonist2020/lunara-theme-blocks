<?php
/**
 * Lunara Site Studio — focused, admin-only presentation controls.
 *
 * This is a router over controls the theme already owns. It deliberately
 * renders one selected surface at a time so editing the Homepage or an
 * Archive never boots the entire Control Desk studio.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_register_site_studio_page' ) ) {
	/**
	 * Register Site Studio directly beneath the Lunara menu.
	 *
	 * @return void
	 */
	function lunara_register_site_studio_page() {
		add_submenu_page(
			'lunara-control-desk',
			__( 'Lunara Site Studio', 'lunara-film' ),
			__( 'Site Studio', 'lunara-film' ),
			'edit_theme_options',
			'lunara-site-studio',
			'lunara_render_site_studio_page',
			1
		);
	}
	add_action( 'admin_menu', 'lunara_register_site_studio_page', 20 );
}

if ( ! function_exists( 'lunara_render_site_studio_page' ) ) {
	/**
	 * Render only the selected Homepage or Archive control surface.
	 *
	 * @return void
	 */
	function lunara_render_site_studio_page() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Lunara Site Studio.', 'lunara-film' ) );
		}

		$surfaces       = lunara_site_studio_surfaces();
		$active_surface = lunara_site_studio_current_surface();
		$active         = $surfaces[ $active_surface ];
		if ( ! current_user_can( $active['capability'] ) ) {
			wp_die( esc_html__( 'You do not have permission to open this Site Studio destination.', 'lunara-film' ) );
		}
		foreach ( $surfaces as $surface_id => $surface ) {
			if ( ! current_user_can( $surface['capability'] ) ) {
				unset( $surfaces[ $surface_id ] );
			}
		}
		$availability   = lunara_site_studio_surface_availability( $active );
		// Navigation groups derive from the surface registry (first-appearance
		// order), so registering a surface never needs a second hardcoded list.
		$groups         = array_values( array_unique( array_column( $surfaces, 'group' ) ) );
		?>
		<div class="wrap lunara-control-desk lunara-site-studio">
			<div class="lunara-control-desk-hero">
				<div>
					<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Lunara', 'lunara-film' ); ?></p>
					<h1><?php esc_html_e( 'Site Studio', 'lunara-film' ); ?></h1>
					<p class="lunara-control-desk-intro"><?php esc_html_e( 'Choose one surface, make the change, and preview it. Nothing else is loaded into this workspace.', 'lunara-film' ); ?></p>
				</div>
				<div class="lunara-control-desk-actions">
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Homepage', 'lunara-film' ); ?></a>
				</div>
			</div>

			<?php if ( function_exists( 'lunara_control_desk_render_notice' ) ) { lunara_control_desk_render_notice(); } ?>

			<nav class="lunara-site-studio-nav" aria-label="<?php echo esc_attr( __( 'Lunara Site Studio surfaces', 'lunara-film' ) ); ?>">
				<?php foreach ( $groups as $group ) : ?>
					<div class="lunara-site-studio-nav-group">
						<strong><?php echo esc_html( $group ); ?></strong>
						<div>
							<?php foreach ( $surfaces as $surface => $spec ) : ?>
								<?php if ( $group !== $spec['group'] ) { continue; } ?>
								<a class="<?php echo $surface === $active_surface ? 'is-active' : ''; ?>" href="<?php echo esc_url( lunara_site_studio_admin_url( $surface ) ); ?>"<?php echo $surface === $active_surface ? ' aria-current="page"' : ''; ?>>
									<span><?php echo esc_html( $spec['label'] ); ?></span>
									<small><?php echo esc_html( $spec['description'] ); ?></small>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</nav>

			<div class="lunara-site-studio-workspace" data-lunara-site-studio-surface="<?php echo esc_attr( $active_surface ); ?>">
				<?php
				if ( empty( $availability['available'] ) ) {
					?>
					<div class="notice notice-warning lunara-site-studio-unavailable">
						<p><strong><?php esc_html_e( 'This Site Studio destination is unavailable.', 'lunara-film' ); ?></strong></p>
						<p><?php echo esc_html( $availability['message'] ); ?></p>
						<?php if ( ! empty( $active['classic_url'] ) ) : ?>
							<p><a class="button" href="<?php echo esc_url( admin_url( $active['classic_url'] ) ); ?>"><?php esc_html_e( 'Open Classic controls', 'lunara-film' ); ?></a></p>
						<?php endif; ?>
					</div>
					<?php
				} elseif ( is_callable( $active['renderer'] ) ) {
					if ( ! lunara_site_studio_boundary_guard( 'renderer', $active_surface ) ) {
						?>
						<div class="notice notice-warning lunara-site-studio-unavailable"><p><?php esc_html_e( 'This destination is temporarily unavailable. Use its Classic controls instead.', 'lunara-film' ); ?></p></div>
						<?php
					} else {
						try {
							call_user_func( $active['renderer'], 'site-studio' );
						} catch ( Throwable $error ) {
							?>
							<div class="notice notice-warning lunara-site-studio-unavailable"><p><?php esc_html_e( 'This destination is temporarily unavailable. Use its Classic controls instead.', 'lunara-film' ); ?></p></div>
							<?php
						} finally {
							lunara_site_studio_boundary_guard( 'renderer', $active_surface, false );
						}
					}
				} else {
					?>
					<div class="notice notice-warning lunara-site-studio-unavailable"><p><?php esc_html_e( 'This destination has no available editor. Use its Classic controls instead.', 'lunara-film' ); ?></p></div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}
}
