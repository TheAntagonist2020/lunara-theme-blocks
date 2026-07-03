<?php
/**
 * The Still Gallery — a screening-room shot reel for essays.
 *
 * Shortcode: [lunara_shot_reel title="The Shots of 2026" kicker="The Still Gallery" ids="12,34,56"]
 *
 * Authoring contract (voice-friendly, zero markup beyond one shortcode):
 * - `ids` optional. Without it, the reel uses every image ATTACHED to the post
 *   (uploaded via this post's Add Media), in media-library order.
 * - Each image's CAPTION becomes the film plate line ("Sinners (2026) — dir. Ryan
 *   Coogler / DP Autumn Durald Arkapaw"), and its DESCRIPTION becomes the
 *   curator's note under it. Alt text stays alt text.
 *
 * Presentation: full-bleed, one shot per viewport, scroll-snapped, letterboxed
 * with object-fit: contain so compositions are never cropped. Caption plates
 * rise as each scene enters; a gold counter tracks position; the site chrome
 * dims to 25% while the reader is inside the reel. JS-off readers get a clean
 * stacked gallery; reduced-motion readers get everything with no animation.
 *
 * @package LunaraFilm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_shot_reel_shortcode' ) ) {
	function lunara_shot_reel_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'  => '',
				'kicker' => __( 'The Still Gallery', 'lunara-film' ),
				'ids'    => '',
			),
			$atts,
			'lunara_shot_reel'
		);

		$post_id = get_the_ID();

		$attachment_ids = array();
		if ( '' !== trim( (string) $atts['ids'] ) ) {
			$attachment_ids = array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) );
		} elseif ( $post_id ) {
			$attached = get_attached_media( 'image', $post_id );
			foreach ( $attached as $attachment ) {
				$attachment_ids[] = (int) $attachment->ID;
			}
		}

		if ( empty( $attachment_ids ) ) {
			return '';
		}

		$scenes = array();
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment = get_post( $attachment_id );
			if ( ! ( $attachment instanceof WP_Post ) || 'attachment' !== $attachment->post_type ) {
				continue;
			}

			$image = wp_get_attachment_image(
				$attachment_id,
				'full',
				false,
				array(
					'class'    => 'lunara-shot-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '100vw',
				)
			);
			if ( '' === $image ) {
				continue;
			}

			$scenes[] = array(
				'image' => $image,
				'plate' => trim( (string) $attachment->post_excerpt ),   // media caption
				'note'  => trim( (string) $attachment->post_content ),   // media description
			);
		}

		if ( empty( $scenes ) ) {
			return '';
		}

		$total = count( $scenes );

		ob_start();
		lunara_shot_reel_output_assets();
		?>
		<section class="lunara-shot-reel" data-lunara-shot-reel aria-label="<?php echo esc_attr( '' !== $atts['title'] ? $atts['title'] : __( 'Still gallery', 'lunara-film' ) ); ?>">
			<?php if ( '' !== trim( (string) $atts['title'] ) ) : ?>
				<header class="lunara-shot-reel-head">
					<p class="lunara-shot-reel-kicker"><?php echo esc_html( $atts['kicker'] ); ?></p>
					<h2 class="lunara-shot-reel-title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<p class="lunara-shot-reel-hint" aria-hidden="true"><?php esc_html_e( 'Scroll', 'lunara-film' ); ?> <span>&darr;</span></p>
				</header>
			<?php endif; ?>
			<?php foreach ( $scenes as $index => $scene ) : ?>
				<figure class="lunara-shot-scene" data-lunara-shot-scene>
					<div class="lunara-shot-frame"><?php echo $scene['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<span class="lunara-shot-counter" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?><em>/ <?php echo esc_html( str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ); ?></em></span>
					<?php if ( '' !== $scene['plate'] || '' !== $scene['note'] ) : ?>
						<figcaption class="lunara-shot-plate">
							<?php if ( '' !== $scene['plate'] ) : ?>
								<p class="lunara-shot-plate-film"><?php echo esc_html( $scene['plate'] ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $scene['note'] ) : ?>
								<p class="lunara-shot-plate-note"><?php echo esc_html( $scene['note'] ); ?></p>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
	add_shortcode( 'lunara_shot_reel', 'lunara_shot_reel_shortcode' );
}

if ( ! function_exists( 'lunara_shot_reel_output_assets' ) ) {
	/**
	 * Scoped CSS + JS for the reel, emitted once per page just before the
	 * first reel. The dim/reveal script only ever ADDS presentation classes,
	 * so JS-off readers see the full stacked gallery untouched.
	 */
	function lunara_shot_reel_output_assets() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		?>
		<style id="lunara-shot-reel-css">
		.lunara-shot-reel{position:relative;width:100vw;margin-left:calc(50% - 50vw);background:var(--lunara-navy-deep,#060d15);}
		.lunara-shot-reel-head{display:grid;gap:10px;justify-items:center;text-align:center;min-height:38svh;align-content:center;padding:48px 20px 34px;}
		.lunara-shot-reel-kicker{margin:0;color:var(--lunara-gold-light,#eadbb3);font-size:.74rem;letter-spacing:.22em;text-transform:uppercase;}
		.lunara-shot-reel-title{margin:0;color:#fff;font-size:clamp(1.9rem,4.4vw,3.4rem);line-height:1.04;max-width:18ch;text-wrap:balance;}
		.lunara-shot-reel-hint{margin:6px 0 0;color:rgba(234,219,179,.6);font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;}
		.lunara-shot-reel-hint span{display:inline-block;}
		.lunara-shot-scene{position:relative;display:grid;margin:0;min-height:100svh;align-items:center;justify-items:center;padding:clamp(28px,4vh,52px) 0;scroll-snap-align:start;}
		.lunara-shot-frame{display:grid;place-items:center;width:100%;min-width:0;}
		.lunara-shot-frame img,.lunara-shot-img{display:block;width:auto;max-width:min(96vw,1680px);height:auto;max-height:78svh;object-fit:contain;box-shadow:0 30px 80px rgba(0,0,0,.5);}
		.lunara-shot-counter{position:absolute;top:clamp(16px,3vh,34px);right:clamp(18px,3vw,44px);color:var(--lunara-gold,#c9a961);font-family:'Bebas Neue','Oswald',Impact,sans-serif;font-size:1.5rem;letter-spacing:.08em;}
		.lunara-shot-counter em{font-style:normal;color:rgba(201,169,97,.45);font-size:1.05rem;margin-left:4px;}
		.lunara-shot-plate{width:min(92vw,880px);margin:clamp(14px,2.4vh,24px) auto 0;padding:0 8px;text-align:center;}
		.lunara-shot-plate-film{margin:0;color:var(--lunara-gold-light,#eadbb3);font-size:.8rem;letter-spacing:.16em;text-transform:uppercase;}
		.lunara-shot-plate-note{margin:8px 0 0;color:rgba(244,239,227,.86);font-size:clamp(.98rem,1.2vw,1.12rem);line-height:1.6;}
		/* Chrome dim while inside the reel — the theater goes dark around the screen. */
		body.lunara-reel-dim #header,body.lunara-reel-dim #main-container>footer,body.lunara-reel-dim .site-footer{opacity:.25;transition:opacity .6s ease;}
		#header,.site-footer{transition:opacity .6s ease;}
		@media (prefers-reduced-motion: no-preference){
			html:has(.lunara-shot-reel){scroll-snap-type:y proximity;}
			/* The hidden from-state exists ONLY once JS has tagged the document —
			   JS-off readers always see plates and counters in full. */
			html.lunara-reel-js .lunara-shot-scene .lunara-shot-plate,html.lunara-reel-js .lunara-shot-scene .lunara-shot-counter{opacity:0;transform:translateY(14px);transition:opacity .7s ease .12s,transform .7s cubic-bezier(.2,.7,.2,1) .12s;}
			html.lunara-reel-js .lunara-shot-scene.is-on .lunara-shot-plate,html.lunara-reel-js .lunara-shot-scene.is-on .lunara-shot-counter{opacity:1;transform:none;}
			.lunara-shot-reel-hint span{animation:lunara-reel-hint 1.8s ease-in-out infinite;}
			@keyframes lunara-reel-hint{0%,100%{transform:translateY(0)}50%{transform:translateY(5px)}}
		}
		@media (prefers-reduced-motion: reduce){
			body.lunara-reel-dim #header,body.lunara-reel-dim .site-footer{transition:none;}
		}
		@media (max-width:640px){
			.lunara-shot-frame img,.lunara-shot-img{max-width:100vw;max-height:64svh;}
			.lunara-shot-counter{font-size:1.15rem;}
		}
		@media print{.lunara-shot-scene{min-height:0;}}
		</style>
		<script id="lunara-shot-reel-js">
		(function(){
			if(!('IntersectionObserver' in window))return;
			var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			function init(){
				var reels=document.querySelectorAll('[data-lunara-shot-reel]');
				if(!reels.length)return;
				/* Dim only while a reel crosses the middle band of the viewport
				   (rootMargin shrinks the observation box to the central 30%),
				   tracked as a Set so initial not-intersecting callbacks can
				   never drift a counter negative. */
				var inView=new Set();
				var dimObs=new IntersectionObserver(function(entries){
					entries.forEach(function(entry){
						if(entry.isIntersecting){inView.add(entry.target);}else{inView.delete(entry.target);}
					});
					document.body.classList.toggle('lunara-reel-dim',inView.size>0);
				},{rootMargin:'-35% 0px -35% 0px',threshold:0});
				reels.forEach(function(reel){dimObs.observe(reel);});
				if(reduce)return; /* plates stay fully visible; only the reveal choreography is skipped */
				/* Tag the document ONLY now — this class is what arms the hidden
				   from-states in CSS, so JS-off readers never get hidden text. */
				document.documentElement.classList.add('lunara-reel-js');
				var sceneObs=new IntersectionObserver(function(entries){
					entries.forEach(function(entry){
						if(entry.isIntersecting){entry.target.classList.add('is-on');sceneObs.unobserve(entry.target);}
					});
				},{threshold:.35});
				document.querySelectorAll('[data-lunara-shot-scene]').forEach(function(scene){sceneObs.observe(scene);});
			}
			if(document.readyState!=='loading'){init();}
			else{document.addEventListener('DOMContentLoaded',init);}
		})();
		</script>
		<?php
	}
}
