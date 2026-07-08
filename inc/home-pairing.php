<?php
/**
 * Homepage Pairing Desk renderer for the signature Pair It With rail.
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

if ( ! function_exists( 'lunara_get_pairing_desk_review_id' ) ) {
	function lunara_get_pairing_desk_review_id() {
		// Plain loop instead of a meta_query: scan the most recent reviews and
		// take the first one with any pairing filled. Bounded (20 posts, meta
		// primed in one round trip) and immune to meta-query edge cases.
		$ids = get_posts(
			array(
				'post_type'              => 'review',
				'post_status'            => 'publish',
				'posts_per_page'         => 20,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		update_meta_cache( 'post', array_map( 'intval', $ids ) );

		foreach ( $ids as $review_id ) {
			$review_id = (int) $review_id;
			$has_pairing =
				'' !== trim( (string) get_post_meta( $review_id, '_lunara_theme_echo', true ) ) ||
				'' !== trim( (string) get_post_meta( $review_id, '_lunara_counter_program', true ) ) ||
				'' !== trim( (string) get_post_meta( $review_id, '_lunara_career_context', true ) ) ||
				'' !== trim( (string) get_post_meta( $review_id, '_lunara_craft_mirror', true ) );

			if ( $has_pairing ) {
				return $review_id;
			}
		}

		return 0;
	}
}

if ( ! function_exists( 'lunara_render_home_pairing_desk' ) ) {
	function lunara_render_home_pairing_desk() {
		// The bail markers make live diagnosis trivial: view-source shows which
		// gate (if any) kept the section off the page.
		if ( ! function_exists( 'lunara_render_pair_it_with_cards' ) ) {
			return "\n<!-- lunara-pairing-desk: module renderer unavailable -->\n";
		}

		$review_id = lunara_get_pairing_desk_review_id();
		if ( $review_id <= 0 ) {
			return "\n<!-- lunara-pairing-desk: no published review with pairings found -->\n";
		}

		$cards = trim( (string) lunara_render_pair_it_with_cards( $review_id ) );
		if ( '' === $cards ) {
			return "\n<!-- lunara-pairing-desk: review " . (int) $review_id . " produced no cards -->\n";
		}

		$kicker = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_pairing_desk_kicker', 'The Lunara Method' )
			: 'The Lunara Method';
		$title = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_pairing_desk_title', 'Every review ends with three more films.' )
			: 'Every review ends with three more films.';
		$copy = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_pairing_desk_copy', 'A Theme Echo, a Counter-Program, and a Career Context close every Lunara review — the next three moves after the credits, argued by a critic, not served by an algorithm. No other film desk builds this rail.' )
			: 'A Theme Echo, a Counter-Program, and a Career Context close every Lunara review — the next three moves after the credits, argued by a critic, not served by an algorithm. No other film desk builds this rail.';

		$review_title = get_the_title( $review_id );
		$review_url   = get_permalink( $review_id );

		// Cinematic backdrop: the reviewed film's own qualified wide image,
		// drifting slowly behind the pairings (reduced-motion turns it off).
		$backdrop = function_exists( 'lunara_get_review_hero_image_url' )
			? trim( (string) lunara_get_review_hero_image_url( $review_id ) )
			: '';
		if ( '' !== $backdrop && function_exists( 'lunara_rightsize_backdrop_url' ) ) {
			$backdrop = lunara_rightsize_backdrop_url( $backdrop );
		}

		ob_start();
		?>
		<section id="pairing-desk" class="lunara-home-section lunara-home-slot-pairing-desk lunara-pairing-desk-section<?php echo '' !== $backdrop ? ' has-desk-backdrop' : ''; ?>" aria-label="<?php esc_attr_e( 'Pair It With showcase', 'lunara-film' ); ?>">
			<style>
				.lunara-pairing-desk-section{overflow:hidden;position:relative}
				.lunara-pairing-desk-backdrop{background-position:center 26%;background-size:cover;filter:saturate(.9);inset:-4%;position:absolute;z-index:0}
				.lunara-pairing-desk-overlay{background:radial-gradient(circle at 82% 8%,rgba(201,169,97,.16),transparent 34%),linear-gradient(180deg,rgba(5,12,21,.88),rgba(7,16,27,.8) 42%,rgba(4,10,18,.95));inset:0;position:absolute;z-index:1}
				.lunara-pairing-desk-inner{position:relative;z-index:2}
				.lunara-pairing-desk-head{display:grid;grid-template-columns:minmax(0,max-content) minmax(280px,42ch);justify-content:start;gap:14px clamp(38px,5vw,72px);align-items:end;margin-bottom:18px}
				.lunara-pairing-desk-claim{display:inline-block;margin:0 0 12px;padding:6px 13px;border:1px solid rgba(201,169,97,.5);background:rgba(7,15,26,.55);color:#e8d9b0;font-family:'Bebas Neue','Oswald',Impact,sans-serif;font-size:.85rem;letter-spacing:.32em;text-transform:uppercase}
				.lunara-pairing-desk-head .lunara-home-section-title{margin-bottom:0;max-width:14ch;text-shadow:0 3px 22px rgba(0,0,0,.5)}
				.lunara-pairing-desk-intro{display:grid;gap:10px;align-content:end;padding-bottom:4px}
				.lunara-pairing-desk-copy{margin:0;color:rgba(244,239,227,.84);font-size:.97rem;line-height:1.6;text-shadow:0 2px 14px rgba(0,0,0,.45)}
				.lunara-pairing-desk-source{margin:0;color:rgba(244,239,227,.74);font-size:.88rem}
				.lunara-pairing-desk-source a{color:var(--lunara-gold-light,#eadbb3);text-decoration:none;border-bottom:1px solid rgba(201,169,97,.4);transition:color .2s ease,border-color .2s ease}
				.lunara-pairing-desk-source a:hover{color:#fff;border-color:rgba(225,197,126,.85)}
				.lunara-pairing-desk-section .lunara-pair-cards{margin-top:0}
				.lunara-pairing-desk-section .lunara-pair-cards-head{display:none}
				.lunara-pairing-desk-section .lunara-pair-cards-grid{counter-reset:lunara-pair-act}
				.lunara-pairing-desk-section .lunara-pair-card{counter-increment:lunara-pair-act;position:relative;transition:transform .28s ease,border-color .28s ease,box-shadow .28s ease}
				.lunara-pairing-desk-section .lunara-pair-card::after{color:rgba(224,196,129,.38);content:"0" counter(lunara-pair-act);font-family:'Bebas Neue','Oswald',Impact,sans-serif;font-size:3.4rem;line-height:1;pointer-events:none;position:absolute;right:14px;top:10px;text-shadow:0 2px 10px rgba(4,10,18,.85),0 0 26px rgba(4,10,18,.7);z-index:3}
				.lunara-pairing-desk-section .lunara-pair-card:hover{border-color:rgba(225,197,126,.55);box-shadow:0 22px 48px rgba(0,0,0,.45);transform:translateY(-5px)}
				.lunara-pairing-desk-section .lunara-pair-card .lunara-pair-card-poster img{transition:transform .7s cubic-bezier(.2,.7,.2,1)}
				.lunara-pairing-desk-section .lunara-pair-card:hover .lunara-pair-card-poster img{transform:scale(1.06)}
				@media(prefers-reduced-motion:no-preference){
					.lunara-pairing-desk-backdrop{animation:lunaraPairDrift 28s ease-in-out infinite alternate}
					@keyframes lunaraPairDrift{from{transform:scale(1) translateY(0)}to{transform:scale(1.07) translateY(-1.5%)}}
					html:not(.lunara-gsap-motion) .lunara-pairing-desk-section.lunara-reveal:not(.is-visible) .lunara-pair-card{opacity:0;transform:translateY(28px)}
					html:not(.lunara-gsap-motion) .lunara-pairing-desk-section.lunara-reveal.is-visible .lunara-pair-card{opacity:1;transform:none;transition:opacity .75s ease,transform .75s cubic-bezier(.2,.7,.2,1)}
					html:not(.lunara-gsap-motion) .lunara-pairing-desk-section.lunara-reveal.is-visible .lunara-pair-card:nth-child(2){transition-delay:.16s}
					html:not(.lunara-gsap-motion) .lunara-pairing-desk-section.lunara-reveal.is-visible .lunara-pair-card:nth-child(3){transition-delay:.32s}
				}
				@media(max-width:900px){.lunara-pairing-desk-head{grid-template-columns:minmax(0,1fr)}.lunara-pairing-desk-intro{align-content:start;padding-bottom:0}}
			</style>
			<?php if ( '' !== $backdrop ) : ?>
				<div class="lunara-pairing-desk-backdrop" style="background-image:url('<?php echo esc_url( $backdrop ); ?>');" aria-hidden="true"></div>
				<div class="lunara-pairing-desk-overlay" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="lunara-pairing-desk-inner">
				<div class="lunara-pairing-desk-head">
					<div>
						<p class="lunara-pairing-desk-claim"><?php esc_html_e( 'Only on Lunara', 'lunara-film' ); ?></p>
						<p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
						<h2 class="lunara-home-section-title"><?php echo esc_html( $title ); ?></h2>
					</div>
					<div class="lunara-pairing-desk-intro">
						<p class="lunara-pairing-desk-copy"><?php echo esc_html( $copy ); ?></p>
						<p class="lunara-pairing-desk-source">
							<?php esc_html_e( 'Fresh from the debrief of', 'lunara-film' ); ?>
							<a href="<?php echo esc_url( $review_url ); ?>"><?php echo esc_html( $review_title ); ?></a>
						</p>
					</div>
				</div>
				<?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- module renderer escapes internally. ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}
