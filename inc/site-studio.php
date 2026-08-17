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

if ( ! function_exists( 'lunara_site_studio_surfaces' ) ) {
	/**
	 * Focused Site Studio destinations.
	 *
	 * @return array<string,array<string,string>>
	 */
	function lunara_site_studio_surfaces() {
		return array(
			'lunara-method'      => array(
				'group'       => __( 'Homepage', 'lunara-film' ),
				'label'       => __( 'Lunara Method', 'lunara-film' ),
				'description' => __( 'Edit the words, featured Review, and backdrop for the signature three-film showcase.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_pairing_desk_form',
			),
			'homepage-structure' => array(
				'group'       => __( 'Homepage', 'lunara-film' ),
				'label'       => __( 'Homepage Structure', 'lunara-film' ),
				'description' => __( 'See the six live lanes, choose their presence, and set desktop and mobile order.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_homepage_studio',
			),
			'reviews-archive'    => array(
				'group'       => __( 'Archives', 'lunara-film' ),
				'label'       => __( 'Reviews Archive', 'lunara-film' ),
				'description' => __( 'Control the Reviews desk density, lead chamber, cards, and companion rail.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_reviews_archive_studio',
			),
			'journal-archive'    => array(
				'group'       => __( 'Archives', 'lunara-film' ),
				'label'       => __( 'Journal Archive', 'lunara-film' ),
				'description' => __( 'Control the live-desk rhythm, lead file, filters, cards, and retention lane.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_journal_archive_studio',
			),
			'oscars-portal'      => array(
				'group'       => __( 'Oscars', 'lunara-film' ),
				'label'       => __( 'Oscars Portal', 'lunara-film' ),
				'description' => __( 'Compose the /oscars/ portal copy, eleven-slot order and visibility, and bounded geometry.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_oscars_portal_studio',
			),
			'oscars-ledger'      => array(
				'group'       => __( 'Oscars', 'lunara-film' ),
				'label'       => __( 'Oscars Ledger Routes', 'lunara-film' ),
				'description' => __( 'Tune ceremony, category, title, and person dossiers while the Academy plugin owns the data.', 'lunara-film' ),
				'renderer'    => 'lunara_control_desk_render_oscars_dossier_studio',
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_admin_url' ) ) {
	/**
	 * Build a bounded direct link into Site Studio.
	 *
	 * @param string $surface Surface key.
	 * @return string
	 */
	function lunara_site_studio_admin_url( $surface = 'lunara-method' ) {
		$surfaces = lunara_site_studio_surfaces();
		$surface  = sanitize_key( (string) $surface );
		if ( ! isset( $surfaces[ $surface ] ) ) {
			$surface = 'lunara-method';
		}

		return add_query_arg(
			array(
				'page'    => 'lunara-site-studio',
				'surface' => $surface,
			),
			admin_url( 'admin.php' )
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_current_surface' ) ) {
	/**
	 * Resolve the requested surface against the allowlist.
	 *
	 * @return string
	 */
	function lunara_site_studio_current_surface() {
		$surface = isset( $_GET['surface'] ) ? sanitize_key( wp_unslash( $_GET['surface'] ) ) : 'lunara-method';
		return isset( lunara_site_studio_surfaces()[ $surface ] ) ? $surface : 'lunara-method';
	}
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
				if ( function_exists( $active['renderer'] ) ) {
					call_user_func( $active['renderer'], 'site-studio' );
				}
				?>
			</div>
		</div>
		<?php
	}
}
