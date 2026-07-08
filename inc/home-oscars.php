<?php
/**
 * Homepage Oscar lanes: Oscar Picks and Oscar Facts query/render helpers.
 *
 * Extracted from the guarded monolith on 2026-07-07 so homepage runtime
 * ownership lives in focused include files. The original functions.php copies
 * remain guarded fallback until the full monolith retirement pass.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_get_oscar_picks' ) ) {
	function lunara_get_oscar_picks( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 12,
			'ceremony_year'  => 0,
			'status'         => '',
			'category'       => '',
			'ordered_ids'    => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		$ordered_ids = array();

		foreach ( (array) $args['ordered_ids'] as $ordered_id ) {
			$ordered_id = absint( $ordered_id );

			if ( $ordered_id > 0 && ! in_array( $ordered_id, $ordered_ids, true ) ) {
				$ordered_ids[] = $ordered_id;
			}
		}

		$query_args = array(
			'post_type'           => 'lunara_oscar_pick',
			'posts_per_page'      => (int) $args['posts_per_page'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array(),
			'tax_query'           => array(),
		);

		if ( ! empty( $ordered_ids ) ) {
			$query_args['post__in'] = $ordered_ids;
			$query_args['orderby']  = 'post__in';
		}

		if ( ! empty( $args['ceremony_year'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_ceremony_year',
				'value' => (int) $args['ceremony_year'],
			);
		}
		if ( ! empty( $args['status'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_status',
				'value' => sanitize_key( $args['status'] ),
			);
		}
		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'oscar_pick_category',
				'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
				'terms'    => $args['category'],
			);
		}

		if ( empty( $ordered_ids ) ) {
			// Default sort: most recent ceremony first, then most recently published pick.
			$query_args['meta_key'] = '_lunara_pick_ceremony_year';
			$query_args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			);
		}

		return new WP_Query( $query_args );
	}
}

if ( ! function_exists( 'lunara_oscar_pick_canonical_category' ) ) {
	function lunara_oscar_pick_canonical_category( $category ) {
		$key = strtolower( trim( remove_accents( (string) $category ) ) );
		$key = preg_replace( '/[^a-z0-9]+/', ' ', $key );
		$key = trim( preg_replace( '/\s+/', ' ', $key ) );

		$map = array(
			'best picture'              => 'BEST PICTURE',
			'picture'                   => 'BEST PICTURE',
			'best director'             => 'DIRECTING',
			'directing'                 => 'DIRECTING',
			'best actor'                => 'ACTOR IN A LEADING ROLE',
			'actor'                     => 'ACTOR IN A LEADING ROLE',
			'best actress'              => 'ACTRESS IN A LEADING ROLE',
			'actress'                   => 'ACTRESS IN A LEADING ROLE',
			'best supporting actor'     => 'ACTOR IN A SUPPORTING ROLE',
			'supporting actor'          => 'ACTOR IN A SUPPORTING ROLE',
			'best supporting actress'   => 'ACTRESS IN A SUPPORTING ROLE',
			'supporting actress'        => 'ACTRESS IN A SUPPORTING ROLE',
			'best adapted screenplay'   => 'WRITING (Adapted Screenplay)',
			'adapted screenplay'        => 'WRITING (Adapted Screenplay)',
			'best original screenplay'  => 'WRITING (Original Screenplay)',
			'original screenplay'       => 'WRITING (Original Screenplay)',
			'best original score'       => 'MUSIC (Original Score)',
			'original score'            => 'MUSIC (Original Score)',
			'best original song'        => 'MUSIC (Original Song)',
			'original song'             => 'MUSIC (Original Song)',
			'best cinematography'       => 'CINEMATOGRAPHY',
			'cinematography'            => 'CINEMATOGRAPHY',
			'best editing'              => 'FILM EDITING',
			'best film editing'         => 'FILM EDITING',
			'film editing'              => 'FILM EDITING',
			'best production design'    => 'PRODUCTION DESIGN',
			'production design'         => 'PRODUCTION DESIGN',
			'best costume design'       => 'COSTUME DESIGN',
			'costume design'            => 'COSTUME DESIGN',
			'best makeup and hairstyling' => 'MAKEUP AND HAIRSTYLING',
			'makeup and hairstyling'    => 'MAKEUP AND HAIRSTYLING',
			'best visual effects'       => 'VISUAL EFFECTS',
			'visual effects'            => 'VISUAL EFFECTS',
			'best sound'                => 'SOUND',
			'sound'                     => 'SOUND',
			'best international feature film' => 'INTERNATIONAL FEATURE FILM',
			'international feature film' => 'INTERNATIONAL FEATURE FILM',
			'best documentary feature'  => 'DOCUMENTARY FEATURE',
			'documentary feature'       => 'DOCUMENTARY FEATURE',
			'best animated feature'     => 'ANIMATED FEATURE FILM',
			'animated feature'          => 'ANIMATED FEATURE FILM',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}
}

if ( ! function_exists( 'lunara_render_oscar_picks_carousel' ) ) {
	function lunara_render_oscar_picks_carousel( $args = array() ) {
		$default_count    = max( 4, min( 16, absint( get_theme_mod( 'lunara_home_oscar_picks_count', 12 ) ) ) );
		$default_autoplay = max( 0, min( 12000, absint( get_theme_mod( 'lunara_home_oscar_picks_autoplay_interval', 6500 ) ) ) );
		$density_options  = array( 'compact', 'editorial', 'showcase' );
		$default_density  = sanitize_key( (string) get_theme_mod( 'lunara_home_oscar_picks_density', 'editorial' ) );

		if ( ! in_array( $default_density, $density_options, true ) ) {
			$default_density = 'editorial';
		}

		$manual_order_raw = (string) get_theme_mod( 'lunara_home_oscar_picks_manual_order', '' );
		$manual_order_ids = array();

		foreach ( preg_split( '/[\s,]+/', $manual_order_raw ) as $manual_order_id ) {
			$manual_order_id = absint( $manual_order_id );

			if ( $manual_order_id > 0 && ! in_array( $manual_order_id, $manual_order_ids, true ) ) {
				$manual_order_ids[] = $manual_order_id;
			}
		}

		$defaults = array(
			'kicker'   => __( 'Lunara Picks', 'lunara-film' ),
			'heading'  => __( 'Predicted winners â€” 98th Academy Awards', 'lunara-film' ),
			'summary'  => __( 'Behind the work, behind the scenes. The images you will not find anywhere else.', 'lunara-film' ),
			'cta_text' => __( 'See the full Oscar Ledger', 'lunara-film' ),
			'cta_url'  => home_url( '/oscars/' ),
			'count'    => $default_count,
			'autoplay' => $default_autoplay,
			'density'  => $default_density,
			'ordered_ids' => $manual_order_ids,
		);
		$args  = lunara_repair_mojibake_args( wp_parse_args( $args, $defaults ), array( 'kicker', 'heading', 'summary', 'cta_text' ) );
		$args['count']    = max( 4, min( 16, absint( $args['count'] ) ) );
		$args['autoplay'] = max( 0, min( 12000, absint( $args['autoplay'] ) ) );
		$oscar_picks_density = sanitize_key( (string) $args['density'] );

		if ( ! in_array( $oscar_picks_density, $density_options, true ) ) {
			$oscar_picks_density = 'editorial';
		}

		$query = lunara_get_oscar_picks(
			array(
				'posts_per_page' => (int) $args['count'],
				'ordered_ids'    => isset( $args['ordered_ids'] ) ? (array) $args['ordered_ids'] : array(),
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<?php $pick_count = max( 0, (int) $query->post_count ); ?>
		<section class="lunara-home-section lunara-home-slot-oscar-picks lunara-oscar-picks-section is-density-<?php echo esc_attr( $oscar_picks_density ); ?>" aria-label="Lunara Oscar Picks" data-lunara-carousel data-lunara-carousel-autoplay="<?php echo $pick_count > 1 ? (int) $args['autoplay'] : 0; ?>">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $args['heading'] ); ?></h2>
					<?php if ( ! empty( $args['summary'] ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $args['summary'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $args['cta_url'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
			</div>
			<?php if ( $pick_count > 1 ) : ?>
				<div class="lunara-oscar-picks-controls">
					<button class="lunara-carousel-control" type="button" data-lunara-carousel-prev aria-label="<?php esc_attr_e( 'Previous Oscar Pick', 'lunara-film' ); ?>">&lt;</button>
					<div class="lunara-oscar-picks-dots lunara-carousel-dots" role="tablist" aria-label="<?php esc_attr_e( 'Oscar Picks slides', 'lunara-film' ); ?>">
						<?php for ( $dot_index = 0; $dot_index < $pick_count; $dot_index++ ) : ?>
							<button
								class="lunara-carousel-dot <?php echo 0 === $dot_index ? 'active' : ''; ?>"
								type="button"
								data-lunara-carousel-dot
								role="tab"
								aria-label="<?php echo esc_attr( sprintf( __( 'Show Oscar Pick %d', 'lunara-film' ), $dot_index + 1 ) ); ?>"
								aria-selected="<?php echo 0 === $dot_index ? 'true' : 'false'; ?>"
							></button>
						<?php endfor; ?>
					</div>
					<button class="lunara-carousel-control" type="button" data-lunara-carousel-next aria-label="<?php esc_attr_e( 'Next Oscar Pick', 'lunara-film' ); ?>">&gt;</button>
				</div>
			<?php endif; ?>

			<div class="lunara-oscar-picks-track" data-lunara-carousel-track role="list" tabindex="0">
				<?php while ( $query->have_posts() ) :
					$query->the_post();
					$pick_index  = max( 0, (int) $query->current_post );
					$pid         = get_the_ID();
					$film        = (string) get_post_meta( $pid, '_lunara_pick_film', true );
					$person      = (string) get_post_meta( $pid, '_lunara_pick_person', true );
					$year        = (int) get_post_meta( $pid, '_lunara_pick_ceremony_year', true );
					$status      = (string) get_post_meta( $pid, '_lunara_pick_status', true ) ?: 'predicted';
					$ledger      = (string) get_post_meta( $pid, '_lunara_pick_oscar_entity_url', true );
					$cat_terms   = get_the_terms( $pid, 'oscar_pick_category' );
					$category    = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';
					$card_url    = lunara_resolve_oscar_pick_ledger_url( $pid, $film, $person, $year, $category );
					$has_visual  = has_post_thumbnail( $pid );
					$thumb_url   = $has_visual ? get_the_post_thumbnail_url( $pid, 'newspack-article-block-landscape-intermediate' ) : '';
					$thumb_attrs = array(
						'class'    => 'lunara-oscar-pick-card-image',
						'loading'  => 'eager',
						'decoding' => 'async',
						'sizes'    => '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 360px',
					);
					?>
					<article class="lunara-oscar-pick-card is-status-<?php echo esc_attr( $status ); ?> <?php echo $has_visual ? 'has-visual' : 'has-no-visual'; ?>" role="listitem">
						<a class="lunara-oscar-pick-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<?php if ( $has_visual ) : ?>
								<div class="lunara-oscar-pick-card-media">
									<?php echo get_the_post_thumbnail( $pid, 'newspack-article-block-landscape-intermediate', $thumb_attrs ); ?>
									<span class="lunara-oscar-pick-card-status"><?php echo esc_html( strtoupper( $status ) ); ?></span>
								</div>
							<?php endif; ?>
							<div class="lunara-oscar-pick-card-copy">
								<?php if ( ! $has_visual ) : ?>
									<span class="lunara-oscar-pick-card-status is-inline-status"><?php echo esc_html( strtoupper( $status ) ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $category ) : ?>
									<p class="lunara-oscar-pick-card-kicker"><?php echo esc_html( $category ); ?></p>
								<?php endif; ?>
								<h3 class="lunara-oscar-pick-card-title">
									<?php
									if ( '' !== $person && '' !== $film ) {
										echo esc_html( $person ) . ' &mdash; <em>' . esc_html( $film ) . '</em>';
									} elseif ( '' !== $film ) {
										echo '<em>' . esc_html( $film ) . '</em>';
									} elseif ( '' !== $person ) {
										echo esc_html( $person );
									} else {
										echo esc_html( get_the_title( $pid ) );
									}
									?>
								</h3>
								<?php if ( $year > 0 ) : ?>
									<p class="lunara-oscar-pick-card-meta"><?php echo esc_html( sprintf( __( '%d Academy Awards', 'lunara-film' ), $year ) ); ?></p>
								<?php endif; ?>
								<p class="lunara-oscar-pick-card-ledger"><?php esc_html_e( 'Open in Ledger', 'lunara-film' ); ?></p>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lunara_oscar_fact_visual_focus_options' ) ) {
	function lunara_oscar_fact_visual_focus_options() {
		return array(
			'center'       => array(
				'label' => __( 'Center', 'lunara-film' ),
				'css'   => 'center center',
			),
			'center-high'  => array(
				'label' => __( 'Center high', 'lunara-film' ),
				'css'   => 'center 38%',
			),
			'center-low'   => array(
				'label' => __( 'Center low', 'lunara-film' ),
				'css'   => 'center 58%',
			),
			'left'         => array(
				'label' => __( 'Left center', 'lunara-film' ),
				'css'   => '38% center',
			),
			'right'        => array(
				'label' => __( 'Right center', 'lunara-film' ),
				'css'   => '62% center',
			),
			'left-high'    => array(
				'label' => __( 'Left high', 'lunara-film' ),
				'css'   => '38% 38%',
			),
			'right-high'   => array(
				'label' => __( 'Right high', 'lunara-film' ),
				'css'   => '62% 38%',
			),
			'left-low'     => array(
				'label' => __( 'Left low', 'lunara-film' ),
				'css'   => '38% 58%',
			),
			'right-low'    => array(
				'label' => __( 'Right low', 'lunara-film' ),
				'css'   => '62% 58%',
			),
		);
	}
}

if ( ! function_exists( 'lunara_oscar_fact_visual_focus_css' ) ) {
	function lunara_oscar_fact_visual_focus_css( $value ) {
		$key     = lunara_sanitize_oscar_fact_visual_focus( $value );
		$options = lunara_oscar_fact_visual_focus_options();

		return isset( $options[ $key ]['css'] ) ? $options[ $key ]['css'] : 'center center';
	}
}

if ( ! function_exists( 'lunara_oscar_fact_visual_hold_ids' ) ) {
	function lunara_oscar_fact_visual_hold_ids() {
		return array( 31313, 31316, 31342 );
	}
}

if ( ! function_exists( 'lunara_get_oscar_facts' ) ) {
	function lunara_get_oscar_facts( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 8,
			'category'       => '',
			'orderby'        => 'rand',
		);
		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'           => 'oscar_fact',
			'posts_per_page'      => (int) $args['posts_per_page'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'rand' === $args['orderby'] ? 'rand' : 'date',
			'order'               => 'DESC',
		);

		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'oscar_fact_category',
					'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
					'terms'    => $args['category'],
				),
			);
		}

		if ( 'visual_priority' === $args['orderby'] ) {
			$limit         = max( 1, (int) $args['posts_per_page'] );
			$held_ids      = function_exists( 'lunara_oscar_fact_visual_hold_ids' ) ? lunara_oscar_fact_visual_hold_ids() : array();
			$priority_args = $query_args;

			$priority_args['fields']         = 'ids';
			$priority_args['posts_per_page'] = $limit;
			$priority_args['post__not_in']   = array_map( 'absint', $held_ids );
			$priority_args['meta_query']     = array(
				'relation' => 'AND',
				array(
					'key'     => '_lunara_fact_visual_verified',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			);
			$priority_args['orderby']        = 'date';

			$ordered_ids = array_map( 'absint', get_posts( $priority_args ) );

			if ( count( $ordered_ids ) < $limit ) {
				$filler_args                   = $query_args;
				$filler_args['fields']         = 'ids';
				$filler_args['posts_per_page'] = $limit - count( $ordered_ids );
				$filler_args['post__not_in']   = array_merge( $ordered_ids, array_map( 'absint', $held_ids ) );
				$filler_args['orderby']        = 'date';

				$ordered_ids = array_merge( $ordered_ids, array_map( 'absint', get_posts( $filler_args ) ) );
			}

			if ( ! empty( $ordered_ids ) ) {
				$query_args['post__in']       = $ordered_ids;
				$query_args['posts_per_page'] = count( $ordered_ids );
				$query_args['orderby']        = 'post__in';
			}
		}

		return new WP_Query( $query_args );
	}
}

if ( ! function_exists( 'lunara_render_oscar_facts_carousel' ) ) {
	function lunara_render_oscar_facts_carousel( $args = array() ) {
		$defaults = array(
			'kicker'   => __( 'Did you know?', 'lunara-film' ),
			'heading'  => __( 'Records, firsts, and Oscar arguments that still live on', 'lunara-film' ),
			'summary'  => '',
			'cta_text' => __( 'Explore the full Lunara Oscar Ledger', 'lunara-film' ),
			'cta_url'  => home_url( '/oscars/' ),
			'count'    => 8,
		);
		$args  = lunara_repair_mojibake_args( wp_parse_args( $args, $defaults ), array( 'kicker', 'heading', 'summary', 'cta_text' ) );
		$query = lunara_get_oscar_facts( array( 'posts_per_page' => (int) $args['count'], 'orderby' => 'visual_priority' ) );

		if ( ! $query->have_posts() ) {
			return '';
		}

		$fact_total = max( 0, (int) $query->post_count );

		$carousel_js = lunara_resolve_theme_asset(
			'assets/js/lunara-carousel.js',
			array( 'lunara-carousel.js' )
		);
		if ( $carousel_js['path'] ) {
			wp_enqueue_script(
				'lunara-carousel',
				$carousel_js['uri'],
				array(),
				lunara_theme_asset_version( $carousel_js['path'] ),
				true
			);
		}

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-oscar-facts lunara-oscar-facts-section" aria-label="Oscar Facts">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $args['heading'] ); ?></h2>
					<?php if ( ! empty( $args['summary'] ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $args['summary'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $args['cta_url'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
			</div>

			<div class="lunara-oscar-facts-carousel lunara-carousel" data-autoplay="6500" data-lunara-splide-pilot data-lunara-splide-autoplay="6500" data-lunara-facts-total="<?php echo esc_attr( (string) $fact_total ); ?>" style="--lunara-oscar-facts-progress:<?php echo esc_attr( $fact_total > 0 ? (string) ( 100 / $fact_total ) . '%' : '0%' ); ?>;" aria-label="<?php esc_attr_e( 'Rotating Oscar facts', 'lunara-film' ); ?>">
				<?php if ( $fact_total > 1 ) : ?>
					<div class="lunara-oscar-facts-console" aria-label="<?php esc_attr_e( 'Oscar Facts carousel status', 'lunara-film' ); ?>">
						<span class="lunara-oscar-facts-console-label"><?php esc_html_e( 'Oscar Ledger File', 'lunara-film' ); ?></span>
						<span class="lunara-oscar-facts-counter" aria-live="polite">
							<span class="lunara-oscar-facts-current">01</span>
							<span class="lunara-oscar-facts-counter-sep" aria-hidden="true">/</span>
							<span class="lunara-oscar-facts-total"><?php echo esc_html( str_pad( (string) $fact_total, 2, '0', STR_PAD_LEFT ) ); ?></span>
						</span>
						<span class="lunara-oscar-facts-progress" aria-hidden="true"><span class="lunara-oscar-facts-progress-bar"></span></span>
					</div>
				<?php endif; ?>
				<div class="lunara-oscar-facts-track" role="list">
				<?php while ( $query->have_posts() ) :
					$query->the_post();
					$fact_index  = max( 0, (int) $query->current_post );
					$pid         = get_the_ID();
					$attribution = (string) get_post_meta( $pid, '_lunara_fact_attribution', true );
					$year        = (int) get_post_meta( $pid, '_lunara_fact_year', true );
					$cat_terms   = get_the_terms( $pid, 'oscar_fact_category' );
					$category    = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';
					$body        = wp_strip_all_tags( get_the_content() );
					$excerpt_more = html_entity_decode( '&hellip;', ENT_QUOTES, 'UTF-8' );
					$body_short  = lunara_repair_mojibake_text( wp_trim_words( $body, 28, $excerpt_more ) );
					$held_ids    = function_exists( 'lunara_oscar_fact_visual_hold_ids' ) ? lunara_oscar_fact_visual_hold_ids() : array();
					$visual_ok   = '1' === (string) get_post_meta( $pid, '_lunara_fact_visual_verified', true ) && ! in_array( $pid, array_map( 'absint', $held_ids ), true );
					$has_image   = $visual_ok && has_post_thumbnail( $pid );
					$visual_treatment = 'archival' === (string) get_post_meta( $pid, '_lunara_fact_visual_treatment', true ) ? 'archival' : 'wide';
					$is_archival_visual = $has_image && 'archival' === $visual_treatment;
					$visual_focus = lunara_sanitize_oscar_fact_visual_focus( get_post_meta( $pid, '_lunara_fact_visual_focus', true ) );
					$visual_focus_css = lunara_oscar_fact_visual_focus_css( $visual_focus );
					$thumb_size = $is_archival_visual ? 'full' : 'lunara-hero-spotlight';
					$visual_image_url = $has_image ? (string) get_the_post_thumbnail_url( $pid, $thumb_size ) : '';
					$card_style_parts = array( '--lunara-fact-image-position:' . $visual_focus_css );
					if ( $is_archival_visual && '' !== $visual_image_url ) {
						$card_style_parts[] = '--lunara-fact-image-url:url(' . esc_url_raw( $visual_image_url ) . ')';
					}
					$card_style  = implode( ';', $card_style_parts ) . ';';
					$card_class  = 'lunara-oscar-fact-card lunara-carousel-slide' . ( 0 === $fact_index ? ' active' : '' ) . ( $has_image ? ' has-poster' : '' ) . ( $is_archival_visual ? ' has-archival-visual' : '' );
					$card_url    = lunara_resolve_oscar_fact_ledger_url( $pid, $category, $year );
					$thumb_attrs = array(
						'class'    => 'lunara-oscar-fact-card-poster-image',
						'loading'  => 'eager',
						'decoding' => 'async',
						'sizes'    => '(max-width: 640px) 92vw, (max-width: 980px) 44vw, 480px',
					);
					?>
					<article class="<?php echo esc_attr( $card_class ); ?>" role="listitem" data-fact-id="<?php echo esc_attr( (string) $pid ); ?>" data-slide-index="<?php echo esc_attr( (string) ( $fact_index + 1 ) ); ?>" data-visual-treatment="<?php echo esc_attr( $visual_treatment ); ?>" data-visual-focus="<?php echo esc_attr( $visual_focus ); ?>" style="<?php echo esc_attr( $card_style ); ?>"<?php echo 0 === $fact_index ? ' aria-current="true"' : ''; ?>>
						<a class="lunara-oscar-fact-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<?php if ( $has_image ) : ?>
								<div class="lunara-oscar-fact-card-poster">
									<?php echo get_the_post_thumbnail( $pid, $thumb_size, $thumb_attrs ); ?>
								</div>
							<?php endif; ?>
							<div class="lunara-oscar-fact-card-text">
								<?php if ( '' !== $category ) : ?>
									<p class="lunara-oscar-fact-card-kicker"><?php echo esc_html( $category ); ?></p>
								<?php endif; ?>
								<h3 class="lunara-oscar-fact-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
								<?php if ( '' !== $body_short ) : ?>
									<p class="lunara-oscar-fact-card-body"><?php echo esc_html( $body_short ); ?></p>
								<?php endif; ?>
								<div class="lunara-oscar-fact-card-foot">
									<?php if ( '' !== $attribution ) : ?>
										<span class="lunara-oscar-fact-card-attribution"><?php echo esc_html( $attribution ); ?><?php if ( $year > 0 ) : ?> &middot; <?php echo esc_html( (string) $year ); ?><?php endif; ?></span>
									<?php elseif ( $year > 0 ) : ?>
										<span class="lunara-oscar-fact-card-attribution"><?php echo esc_html( (string) $year ); ?></span>
									<?php endif; ?>
									<span class="lunara-oscar-fact-card-cta"><?php esc_html_e( 'Open in Ledger', 'lunara-film' ); ?> &rarr;</span>
								</div>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
				</div>
				<?php if ( $query->post_count > 1 ) : ?>
					<div class="lunara-oscar-facts-dots lunara-carousel-dots" aria-label="<?php esc_attr_e( 'Oscar fact slides', 'lunara-film' ); ?>">
						<?php for ( $dot_index = 0; $dot_index < (int) $query->post_count; $dot_index++ ) : ?>
							<button class="lunara-carousel-dot <?php echo 0 === $dot_index ? 'active' : ''; ?>" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Show Oscar fact %d', 'lunara-film' ), $dot_index + 1 ) ); ?>"></button>
						<?php endfor; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
