<?php
/**
 * Design Tokens — dial-level control over the house palette and voice.
 *
 * The :root tokens in style.css remain the shipped defaults. This module is
 * the single runtime emitter for the six shared palette variables: explicit
 * lunara_design_tokens values win, missing keys inherit compatible Customizer
 * theme mods, and absent/invalid values use the shipped defaults. Non-default
 * font-role selections share the same wp_head layer. Reset deletes the option
 * so compatible Customizer values or shipped defaults resume. The override
 * block carries a marker and is excluded from WP Rocket's unused-CSS pass.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_design_token_color_specs' ) ) {
	/**
	 * The color dials: CSS var, label, shipped default.
	 */
	function lunara_design_token_color_specs() {
		return array(
			'gold'         => array( 'var' => '--lunara-gold', 'label' => __( 'Lunara Gold', 'lunara-film' ), 'default' => '#c9a961' ),
			'gold_light'   => array( 'var' => '--lunara-gold-light', 'label' => __( 'Gold — Light', 'lunara-film' ), 'default' => '#e0c481' ),
			'bg_primary'   => array( 'var' => '--lunara-bg-primary', 'label' => __( 'Ground — Deep Navy', 'lunara-film' ), 'default' => '#0a1520' ),
			'bg_secondary' => array( 'var' => '--lunara-bg-secondary', 'label' => __( 'Surface — Card Navy', 'lunara-film' ), 'default' => '#0f1d2e' ),
			'text'         => array( 'var' => '--lunara-text', 'label' => __( 'Primary Text', 'lunara-film' ), 'default' => '#fafbfc' ),
			'text_muted'   => array( 'var' => '--lunara-text-muted', 'label' => __( 'Muted Text', 'lunara-film' ), 'default' => '#a8a8b8' ),
		);
	}
}

if ( ! function_exists( 'lunara_design_token_font_choices' ) ) {
	/**
	 * The faces a role can be dialed to. Every stack ships local woff2s
	 * (or system fallbacks), so any assignment is safe.
	 */
	function lunara_design_token_font_choices() {
		return array(
			'tiempos-text'     => array( 'label' => 'Tiempos Text', 'stack' => '"Tiempos Text", Georgia, "Times New Roman", "Iowan Old Style", "Palatino Linotype", serif' ),
			'tiempos-headline' => array( 'label' => 'Tiempos Headline', 'stack' => '"Tiempos Headline", "Tiempos Text", Georgia, "Times New Roman", "Iowan Old Style", serif' ),
			'gt-sectra'        => array( 'label' => 'GT Sectra Display', 'stack' => '"GT Sectra Display", "Tiempos Headline", Georgia, "Times New Roman", serif' ),
			'canela-deck'      => array( 'label' => 'Canela Deck', 'stack' => '"Canela Deck", "Tiempos Headline", Georgia, "Times New Roman", serif' ),
			'bebas'            => array( 'label' => 'Bebas Neue', 'stack' => '"Bebas Neue", "Oswald", "Arial Narrow", sans-serif' ),
			'georgia'          => array( 'label' => 'Georgia (system)', 'stack' => 'Georgia, "Times New Roman", "Iowan Old Style", "Palatino Linotype", serif' ),
		);
	}
}

if ( ! function_exists( 'lunara_design_token_font_role_specs' ) ) {
	/**
	 * The typographic roles: CSS var, label, shipped default face.
	 */
	function lunara_design_token_font_role_specs() {
		return array(
			'body'      => array( 'var' => '--lunara-font-body', 'label' => __( 'Body — the reading voice', 'lunara-film' ), 'default' => 'tiempos-text' ),
			'display'   => array( 'var' => '--lunara-font-display', 'label' => __( 'Display — headlines', 'lunara-film' ), 'default' => 'tiempos-headline' ),
			'signature' => array( 'var' => '--lunara-font-signature', 'label' => __( 'Signature — feature titles', 'lunara-film' ), 'default' => 'gt-sectra' ),
			'glamour'   => array( 'var' => '--lunara-font-glamour', 'label' => __( 'Glamour — rare display', 'lunara-film' ), 'default' => 'canela-deck' ),
			'label'     => array( 'var' => '--lunara-font-label', 'label' => __( 'Label — kickers and chips', 'lunara-film' ), 'default' => 'tiempos-text' ),
		);
	}
}

if ( ! function_exists( 'lunara_get_design_tokens' ) ) {
	function lunara_get_design_tokens() {
		$saved = get_option( 'lunara_design_tokens', array() );
		return is_array( $saved ) ? $saved : array();
	}
}

if ( ! function_exists( 'lunara_design_token_customizer_color_mods' ) ) {
	/**
	 * Compatibility map for the six historical Customizer settings.
	 *
	 * Customizer continues to save these theme mods. Design Tokens is their one
	 * runtime CSS emitter, so an unset token key can inherit the existing value
	 * without two competing :root blocks.
	 *
	 * @return array<string,string>
	 */
	function lunara_design_token_customizer_color_mods() {
		return array(
			'bg_primary'   => 'lunara_bg_primary',
			'bg_secondary' => 'lunara_bg_secondary',
			'gold'         => 'lunara_accent_color',
			'gold_light'   => 'lunara_accent_soft_color',
			'text'         => 'lunara_text_color',
			'text_muted'   => 'lunara_muted_text_color',
		);
	}
}

if ( ! function_exists( 'lunara_design_tokens_output_css' ) ) {
	/**
	 * Print the sole runtime layer for the six shared palette variables.
	 *
	 * A present Design Tokens key wins even when it stores the shipped default;
	 * only a missing key falls back to its compatible Customizer theme mod.
	 */
	function lunara_design_tokens_output_css() {
		if ( is_admin() ) {
			return;
		}

		$lines   = array();
		$tokens  = lunara_get_design_tokens();
		$colors  = isset( $tokens['colors'] ) && is_array( $tokens['colors'] ) ? $tokens['colors'] : array();
		$fonts   = isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array();
		$choices = lunara_design_token_font_choices();
		$mods    = lunara_design_token_customizer_color_mods();

		foreach ( lunara_design_token_color_specs() as $key => $spec ) {
			$candidate = array_key_exists( $key, $colors )
				? $colors[ $key ]
				: get_theme_mod( $mods[ $key ], $spec['default'] );
			$hex = is_scalar( $candidate ) ? sanitize_hex_color( (string) $candidate ) : false;
			$hex = $hex ? $hex : $spec['default'];
			$lines[] = $spec['var'] . ':' . $hex . ';';
		}

		foreach ( lunara_design_token_font_role_specs() as $role => $spec ) {
			if ( empty( $fonts[ $role ] ) || $fonts[ $role ] === $spec['default'] || ! isset( $choices[ $fonts[ $role ] ] ) ) {
				continue;
			}
			$lines[] = $spec['var'] . ':' . $choices[ $fonts[ $role ] ]['stack'] . ';';
		}

		if ( empty( $lines ) ) {
			return;
		}
		echo '<style id="lunara-design-tokens-css">/*lunara-design-tokens*/:root{' . implode( '', $lines ) . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values sanitized above.
	}
	add_action( 'wp_head', 'lunara_design_tokens_output_css', 44 );
}

if ( ! function_exists( 'lunara_design_tokens_rocket_exclusions' ) ) {
	function lunara_design_tokens_rocket_exclusions( $exclusions ) {
		$exclusions   = is_array( $exclusions ) ? $exclusions : array();
		$exclusions[] = 'lunara-design-tokens';
		$exclusions[] = 'lunara-critical-shell-repair';
		return $exclusions;
	}
	add_filter( 'rocket_rucss_inline_content_exclusions', 'lunara_design_tokens_rocket_exclusions' );
	add_filter( 'rocket_rucss_inline_atts_exclusions', 'lunara_design_tokens_rocket_exclusions' );
}

if ( ! function_exists( 'lunara_design_tokens_save_handler' ) ) {
	function lunara_design_tokens_save_handler() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'lunara_design_tokens_save' ) ) {
			wp_die( esc_html__( 'Design tokens request rejected.', 'lunara-film' ) );
		}

		if ( isset( $_POST['lunara_tokens_reset'] ) ) {
			delete_option( 'lunara_design_tokens' );
		} else {
			$colors = array();
			foreach ( array_keys( lunara_design_token_color_specs() ) as $key ) {
				if ( isset( $_POST[ 'token_color_' . $key ] ) ) {
					$hex = sanitize_hex_color( wp_unslash( $_POST[ 'token_color_' . $key ] ) );
					if ( $hex ) {
						$colors[ $key ] = $hex;
					}
				}
			}
			$fonts   = array();
			$choices = lunara_design_token_font_choices();
			foreach ( array_keys( lunara_design_token_font_role_specs() ) as $role ) {
				if ( isset( $_POST[ 'token_font_' . $role ] ) ) {
					$slug = sanitize_key( wp_unslash( $_POST[ 'token_font_' . $role ] ) );
					if ( isset( $choices[ $slug ] ) ) {
						$fonts[ $role ] = $slug;
					}
				}
			}
			update_option( 'lunara_design_tokens', array( 'colors' => $colors, 'fonts' => $fonts ), true );
		}

		$target = function_exists( 'lunara_control_desk_admin_url' )
			? lunara_control_desk_admin_url( array( 'tab' => 'system-status' ) )
			: admin_url( 'admin.php?page=lunara-control-desk' );
		wp_safe_redirect( $target );
		exit;
	}
	add_action( 'admin_post_lunara_design_tokens_save', 'lunara_design_tokens_save_handler' );
}

if ( ! function_exists( 'lunara_design_tokens_render_panel' ) ) {
	/**
	 * The Control Desk panel (rendered from the System Status tab).
	 */
	function lunara_design_tokens_render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tokens  = lunara_get_design_tokens();
		$colors  = isset( $tokens['colors'] ) && is_array( $tokens['colors'] ) ? $tokens['colors'] : array();
		$fonts   = isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array();
		$choices = lunara_design_token_font_choices();
		?>
		<section class="lunara-control-desk-panel">
			<div class="lunara-control-desk-panel-header">
				<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Design Tokens', 'lunara-film' ); ?></p>
				<h2><?php esc_html_e( 'Dial-level control over the palette and the voice', 'lunara-film' ); ?></h2>
				<p class="lunara-control-desk-intro"><?php esc_html_e( 'These dials are the primary palette and type controls site-wide. The six shared colors are emitted once from here; Reset returns each missing color to its compatible Classic control or shipped default.', 'lunara-film' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lunara_design_tokens_save" />
				<?php wp_nonce_field( 'lunara_design_tokens_save' ); ?>
				<div class="lunara-control-desk-status-grid">
					<?php foreach ( lunara_design_token_color_specs() as $key => $spec ) : ?>
						<?php $value = ! empty( $colors[ $key ] ) ? $colors[ $key ] : $spec['default']; ?>
						<article class="lunara-control-desk-status-card is-ready">
							<p class="lunara-control-desk-kicker"><?php echo esc_html( $spec['label'] ); ?></p>
							<label>
								<input type="color" name="token_color_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
								<code><?php echo esc_html( $spec['var'] ); ?></code>
							</label>
							<span><?php echo esc_html( sprintf( /* translators: %s: default hex */ __( 'Shipped default: %s', 'lunara-film' ), $spec['default'] ) ); ?></span>
						</article>
					<?php endforeach; ?>
					<?php foreach ( lunara_design_token_font_role_specs() as $role => $spec ) : ?>
						<?php $value = ! empty( $fonts[ $role ] ) ? $fonts[ $role ] : $spec['default']; ?>
						<article class="lunara-control-desk-status-card is-ready">
							<p class="lunara-control-desk-kicker"><?php echo esc_html( $spec['label'] ); ?></p>
							<label>
								<select name="token_font_<?php echo esc_attr( $role ); ?>">
									<?php foreach ( $choices as $slug => $choice ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $value, $slug ); ?>><?php echo esc_html( $choice['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<code><?php echo esc_html( $spec['var'] ); ?></code>
							</label>
							<span><?php echo esc_html( sprintf( /* translators: %s: default face label */ __( 'Shipped default: %s', 'lunara-film' ), $choices[ $spec['default'] ]['label'] ) ); ?></span>
						</article>
					<?php endforeach; ?>
				</div>
				<p style="display:flex;gap:10px;margin-top:14px;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save the dials', 'lunara-film' ); ?></button>
					<button type="submit" name="lunara_tokens_reset" value="1" class="button"><?php esc_html_e( 'Reset to shipped defaults', 'lunara-film' ); ?></button>
				</p>
			</form>
		</section>
		<?php
	}
}
