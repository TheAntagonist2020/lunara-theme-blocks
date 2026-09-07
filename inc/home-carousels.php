<?php
/** Homepage carousel delivery. Legacy renderers remain authoritative until Apply. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_home_carousel_is_adopted( $kind = 'hero' ) {
	return function_exists( 'lunara_home_carousel_settings' ) && ! empty( lunara_home_carousel_settings( $kind )['adopted'] );
}

/** Resolve published sources without the legacy featured/lead ordering. */
function lunara_home_carousel_slides( $kind = 'hero' ) {
	$kind = 'journal' === $kind ? 'journal' : 'hero';
	$settings = lunara_home_carousel_settings( $kind );
	if ( empty( $settings['adopted'] ) ) { return array(); }
	$changed = function_exists( 'wp_cache_get_last_changed' ) ? wp_cache_get_last_changed( 'posts' ) : '';
	$key = md5( serialize( array( $kind, $settings, $changed ) ) );
	if ( isset( $GLOBALS['lunara_home_carousel_slide_cache'][ $key ] ) ) { return $GLOBALS['lunara_home_carousel_slide_cache'][ $key ]; }
	$types = 'journal' === $kind ? array( 'journal' ) : array( 'review', 'journal' );
	$entries = $settings['slides'];
	if ( 'auto' === $settings['mode'] ) {
		$query = new WP_Query( array(
			'post_type' => $types, 'post_status' => 'publish', 'has_password' => false,
			'posts_per_page' => 6, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
			'ignore_sticky_posts' => true, 'no_found_rows' => true,
		) );
		$entries = array_map( static function ( $post ) { return array( 'post_id' => (int) $post->ID ); }, $query->posts );
	}
	$slides = array();
	foreach ( $entries as $entry ) {
		$post = get_post( $entry['post_id'] );
		if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || ! empty( $post->post_password ) || ! in_array( $post->post_type, $types, true ) ) { continue; }
		$id = (int) $post->ID;
		$slide = function_exists( 'lunara_build_hero_slide_for_post' ) ? lunara_build_hero_slide_for_post( $id ) : null;
		if ( ! is_array( $slide ) ) {
			$slide = array(
				'image' => '', 'attachment_id' => 0, 'title' => get_the_title( $id ), 'url' => get_permalink( $id ),
				'kicker' => 'review' === $post->post_type ? __( 'Latest Review', 'lunara-film' ) : __( 'Journal', 'lunara-film' ),
				'cta' => 'review' === $post->post_type ? __( 'Read the review', 'lunara-film' ) : __( 'Read the story', 'lunara-film' ),
				'excerpt' => function_exists( 'lunara_card_excerpt' ) ? lunara_card_excerpt( $id, 28 ) : wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 28, '…' ),
			);
		}
		// Journal cards can use portrait or smaller artwork too; no wide-image gate.
		if ( 'journal' === $kind || empty( $slide['image'] ) ) {
			$image = function_exists( 'lunara_get_journal_card_image_url' ) && 'journal' === $post->post_type ? lunara_get_journal_card_image_url( $id, 'full' ) : get_the_post_thumbnail_url( $id, 'full' );
			if ( $image ) {
				$slide['image'] = $image;
				$slide['attachment_id'] = function_exists( 'lunara_hero_attachment_id_from_url' ) ? lunara_hero_attachment_id_from_url( $image ) : 0;
			}
		}
		if ( ! empty( $entry['image_id'] ) && wp_attachment_is_image( $entry['image_id'] ) ) {
			$image = wp_get_attachment_image_url( $entry['image_id'], 'full' );
			if ( $image ) { $slide['image'] = $image; $slide['attachment_id'] = (int) $entry['image_id']; }
		}
		foreach ( array( 'headline' => 'title', 'excerpt' => 'excerpt', 'kicker' => 'kicker', 'cta' => 'cta' ) as $field => $target ) {
			if ( isset( $entry[ $field ] ) && '' !== trim( $entry[ $field ] ) ) { $slide[ $target ] = $entry[ $field ]; }
		}
		foreach ( array( 'focal_x' => 50, 'focal_y' => 30, 'zoom' => 100, 'fit' => 'cover' ) as $field => $default ) { $slide[ $field ] = $entry[ $field ] ?? $default; }
		$slide['overlay'] = ! empty( $entry['overlay'] ) ? $entry['overlay'] : $settings['overlay'];
		$slide['post_id'] = $id;
		$slide['date'] = get_the_date( 'c', $id );
		$slide['date_label'] = get_the_date( 'M j, Y', $id );
		$slides[] = $slide;
	}
	$GLOBALS['lunara_home_carousel_slide_cache'][ $key ] = $slides;
	return $slides;
}

/** Only request-local memoization; WordPress owns post/query cache invalidation. */
function lunara_home_carousel_reset_delivery() { unset( $GLOBALS['lunara_home_carousel_slide_cache'] ); }
foreach ( array( 'save_post', 'deleted_post', 'transition_post_status', 'updated_post_meta', 'added_post_meta', 'deleted_post_meta', 'updated_option', 'added_option', 'deleted_option' ) as $hook ) {
	add_action( $hook, 'lunara_home_carousel_reset_delivery' );
}

function lunara_home_carousel_placeholder() {
	return '<span class="lunara-home-carousel-placeholder" aria-hidden="true"><span>LUNARA FILM</span></span>';
}

/** Splide uses this same button for its accessible play/pause state. */
function lunara_home_carousel_toggle() {
	return '<button class="splide__toggle lunara-home-carousel-toggle" type="button"><span class="splide__toggle__play">' . esc_html__( 'Play', 'lunara-film' ) . '</span><span class="splide__toggle__pause">' . esc_html__( 'Pause', 'lunara-film' ) . '</span></button>';
}

function lunara_render_home_hero_carousel( $attrs = array() ) {
	$settings = lunara_home_carousel_settings( 'hero' );
	$slides = lunara_home_carousel_slides( 'hero' );
	if ( ! $slides ) { return ''; }
	$priority = ! array_key_exists( 'first_image_is_lcp', $attrs ) || (bool) $attrs['first_image_is_lcp'];
	$label = '' !== $settings['heading'] ? $settings['heading'] : __( 'Featured stories', 'lunara-film' );
	ob_start(); ?>
	<section class="lunara-home-hero lunara-home-slot-hero lunara-cinematic-hero lunara-cinematic-hero-carousel lunara-home-curated-hero splide<?php echo count( $slides ) < 2 ? ' is-hero-static' : ''; ?>" data-lunara-site-studio-section="hero" data-lunara-hero-autoplay="<?php echo (int) $settings['interval'] * 1000; ?>" data-lunara-autoplay-enabled="<?php echo (int) $settings['autoplay']; ?>" aria-label="<?php echo esc_attr( $label ); ?>" aria-roledescription="carousel">
		<?php if ( '' !== $settings['heading'] ) : ?><h2 class="lunara-home-carousel-heading"><?php echo esc_html( $settings['heading'] ); ?></h2><?php endif; ?>
		<div class="splide__track lunara-cinematic-hero-track"><ul class="splide__list">
			<?php foreach ( $slides as $index => $slide ) { echo lunara_render_cinematic_hero_slide( $slide, $index, $priority ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?>
		</ul></div>
		<?php if ( count( $slides ) > 1 ) { echo lunara_home_carousel_toggle(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?>
	</section>
	<?php return (string) ob_get_clean();
}

function lunara_render_home_journal_carousel() {
	$settings = lunara_home_carousel_settings( 'journal' );
	$slides = lunara_home_carousel_slides( 'journal' );
	if ( ! $slides ) { return ''; }
	$heading = '' !== $settings['heading'] ? $settings['heading'] : __( 'The Journal', 'lunara-film' );
	ob_start(); ?>
	<section class="lunara-home-section lunara-home-slot-dispatch lunara-dispatches-section lunara-home-curated-journal" data-lunara-site-studio-section="dispatch" aria-label="<?php echo esc_attr( $heading ); ?>">
		<div class="lunara-home-section-head"><div><p class="lunara-home-section-kicker"><?php esc_html_e( 'Journal', 'lunara-film' ); ?></p><h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2></div><a class="lunara-section-link" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Open the Journal', 'lunara-film' ); ?></a></div>
		<div class="lunara-home-journal-carousel splide" aria-label="<?php esc_attr_e( 'Journal stories', 'lunara-film' ); ?>" data-lunara-journal-carousel data-lunara-autoplay-enabled="<?php echo (int) $settings['autoplay']; ?>" data-lunara-carousel-interval="<?php echo (int) $settings['interval'] * 1000; ?>">
			<div class="splide__track"><ul class="splide__list">
			<?php foreach ( $slides as $slide ) :
				$style = sprintf( '--carousel-focal-x:%d%%;--carousel-focal-y:%d%%;--carousel-zoom:%.2F;', $slide['focal_x'], $slide['focal_y'], $slide['zoom'] / 100 );
				$image = '';
				$image_attrs = array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'fetchpriority' => 'low', 'sizes' => '(max-width: 640px) 92vw, (max-width: 980px) 45vw, 30vw' );
				if ( ! empty( $slide['attachment_id'] ) ) { $image = wp_get_attachment_image( $slide['attachment_id'], 'full', false, $image_attrs ); }
				if ( ! $image && ! empty( $slide['image'] ) ) { $image = '<img src="' . esc_url( $slide['image'] ) . '" alt="" loading="lazy" decoding="async" fetchpriority="low" />'; }
			?>
				<li class="splide__slide"><article class="lunara-home-news-card"><a href="<?php echo esc_url( $slide['url'] ); ?>">
					<div class="lunara-home-news-media<?php echo 'full' === $slide['fit'] ? ' is-full-frame' : ''; ?>" style="<?php echo esc_attr( $style ); ?>"><?php echo $image ?: lunara_home_carousel_placeholder(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></div>
					<div class="lunara-home-news-copy"><p class="lunara-home-news-kicker"><?php echo esc_html( $slide['kicker'] ); ?></p><h3><?php echo esc_html( $slide['title'] ); ?></h3><p class="lunara-home-news-excerpt"><?php echo esc_html( $slide['excerpt'] ); ?></p><div class="lunara-home-news-meta"><time datetime="<?php echo esc_attr( $slide['date'] ); ?>"><?php echo esc_html( $slide['date_label'] ); ?></time><span><?php echo esc_html( $slide['cta'] ); ?> <span aria-hidden="true">&rarr;</span></span></div></div>
				</a></article></li>
			<?php endforeach; ?>
			</ul></div>
			<?php if ( count( $slides ) > 1 ) { echo lunara_home_carousel_toggle(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?>
		</div>
	</section>
	<?php return (string) ob_get_clean();
}

function lunara_enqueue_home_carousel_assets() {
	if ( is_admin() || ! is_front_page() || ( ! lunara_home_carousel_is_adopted( 'hero' ) && ! lunara_home_carousel_is_adopted( 'journal' ) ) ) { return; }
	foreach ( array( 'css' => 'assets/css/lunara-home-carousels.css', 'js' => 'assets/js/lunara-home-carousels.js' ) as $type => $path ) {
		$asset = lunara_resolve_theme_asset( $path, array( $path ) );
		if ( ! $asset['uri'] ) { continue; }
		if ( 'css' === $type ) { wp_enqueue_style( 'lunara-home-carousels', $asset['uri'], array( 'lunara-splide-core' ), lunara_theme_asset_version( $asset['path'] ) ); }
		else { wp_enqueue_script( 'lunara-home-carousels', $asset['uri'], array( 'lunara-splide' ), lunara_theme_asset_version( $asset['path'] ), true ); }
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_home_carousel_assets', 30 );
