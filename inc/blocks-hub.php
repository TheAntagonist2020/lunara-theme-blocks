<?php
/**
 * Lunara Studio — editor block hub and starter patterns.
 *
 * Keeps the flagship Lunara blocks discoverable without adding any front-end
 * assets. The sidebar is editor-only; patterns are registered once at init.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_blocks_hub_enqueue_editor' ) ) {
	/**
	 * Enqueue the editor-only Lunara Studio sidebar bundle.
	 *
	 * @return void
	 */
	function lunara_blocks_hub_enqueue_editor() {
		if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
			return;
		}

		$asset = lunara_resolve_theme_asset( 'assets/js/lunara-blocks-hub.js' );
		if ( empty( $asset['path'] ) || empty( $asset['uri'] ) || ! function_exists( 'wp_enqueue_script' ) ) {
			return;
		}

		$dependencies = array( 'wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-components', 'wp-element', 'wp-blocks', 'wp-data', 'wp-i18n' );
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'lunara-dispatch-blocks', 'registered' ) ) {
			$dependencies[] = 'lunara-dispatch-blocks';
		}

		wp_enqueue_script(
			'lunara-blocks-hub',
			$asset['uri'],
			$dependencies,
			function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : null,
			true
		);

		$can_manage = current_user_can( 'edit_theme_options' );
		$front_page = function_exists( 'lunara_home_front_page_id' )
			? lunara_home_front_page_id()
			: ( 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0 );
		wp_localize_script(
			'lunara-blocks-hub',
			'LunaraSiteStudioConfig',
			array(
				'frontPageId'  => $front_page,
				'canManage'    => $can_manage,
				'siteStudioUrl' => $can_manage && function_exists( 'lunara_site_studio_admin_url' )
					? esc_url_raw( lunara_site_studio_admin_url( 'homepage-structure' ) )
					: '',
				'methodUrl'     => $can_manage && function_exists( 'lunara_site_studio_admin_url' )
					? esc_url_raw( lunara_site_studio_admin_url( 'lunara-method' ) )
					: '',
				'homepageUrl'   => esc_url_raw( home_url( '/' ) ),
			)
		);
	}
	add_action( 'enqueue_block_editor_assets', 'lunara_blocks_hub_enqueue_editor' );
}

if ( ! function_exists( 'lunara_blocks_hub_register_patterns' ) ) {
	/**
	 * Register the Lunara pattern category and lightweight starter layouts.
	 *
	 * @return void
	 */
	function lunara_blocks_hub_register_patterns() {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'lunara',
				array( 'label' => __( 'Lunara', 'lunara-film' ) )
			);
		}

		if ( ! function_exists( 'register_block_pattern' ) || ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return;
		}

		$registry       = WP_Block_Patterns_Registry::get_instance();
		$block_registry = class_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance() : null;
		$patterns = array(
			'lunara/starter-reviews-grid' => array(
				'title'       => __( 'Reviews Grid — Latest', 'lunara-film' ),
				'description' => __( 'A lead review grid ready for the Home or Reviews page.', 'lunara-film' ),
				'keywords'    => array( 'reviews', 'films', 'grid' ),
				'content'     => '<!-- wp:lunara/reviews-grid {"mode":"latest","layout":"lead","columns":3,"count":6} /-->',
			),
			'lunara/starter-journal-grid' => array(
				'title'       => __( 'Journal Grid — Latest', 'lunara-film' ),
				'description' => __( 'A lead Journal grid for dispatches and essays.', 'lunara-film' ),
				'keywords'    => array( 'journal', 'dispatch', 'grid' ),
				'content'     => '<!-- wp:lunara/journal-grid {"mode":"latest","layout":"lead","columns":2,"count":4} /-->',
			),
			'lunara/starter-media-gallery' => array(
				'title'       => __( 'Media Gallery — Wide', 'lunara-film' ),
				'description' => __( 'A wide three-column gallery for stills and visual essays.', 'lunara-film' ),
				'keywords'    => array( 'media', 'gallery', 'stills' ),
				'content'     => '<!-- wp:lunara/media-showcase {"display":"gallery","columns":3,"aspectRatio":"wide"} /-->',
			),
			'lunara/starter-pair-it-with' => array(
				'title'       => __( 'Pair It With — Three Films', 'lunara-film' ),
				'description' => __( 'A curated three-film conversation ready for editorial notes.', 'lunara-film' ),
				'keywords'    => array( 'pairing', 'films', 'debrief' ),
				'content'     => <<<'PATTERN'
<!-- wp:lunara/pairing {"source":"curated","pairings":[
  {"label":"The descent","title":"There Will Be Blood","year":"2007",
   "note":"A study in appetite, power, and consequence.","imdb":"tt0469494"},
  {"label":"The grace note","title":"Paddington 2","year":"2017",
   "note":"Kindness as a radical form of resistance.","imdb":"tt4468740"},
  {"label":"The reckoning","title":"No Country for Old Men","year":"2007",
   "note":"Fate, violence, and the silence between choices.","imdb":"tt0477348"}
]} /-->
PATTERN,
			),
			'lunara/section-cinematic-hero' => array(
				'title'       => __( 'Homepage Section — Cinematic Hero', 'lunara-film' ),
				'description' => __( 'The cinematic hero section from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'hero', 'cinematic' ),
				'content'     => '<!-- wp:lunara/cinematic-hero /-->',
			),
			'lunara/section-latest-reviews' => array(
				'title'       => __( 'Homepage Section — Latest Reviews', 'lunara-film' ),
				'description' => __( 'The curated or newest-first Reviews shelf from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'reviews', 'criticism' ),
				'content'     => '<!-- wp:lunara/latest-reviews /-->',
			),
			'lunara/section-journal-lane' => array(
				'title'       => __( 'Homepage Section — Journal Lane', 'lunara-film' ),
				'description' => __( 'The Journal lane section from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'journal', 'lane' ),
				'content'     => '<!-- wp:lunara/journal-lane /-->',
			),
			'lunara/section-oscar-picks' => array(
				'title'       => __( 'Homepage Section — Oscar Picks', 'lunara-film' ),
				'description' => __( 'The Oscar Picks carousel from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'oscars', 'picks' ),
				'content'     => '<!-- wp:lunara/oscar-picks /-->',
			),
			'lunara/section-oscar-facts' => array(
				'title'       => __( 'Homepage Section — Oscar Facts', 'lunara-film' ),
				'description' => __( 'The Oscar Facts carousel from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'oscars', 'facts' ),
				'content'     => '<!-- wp:lunara/oscar-facts /-->',
			),
			'lunara/section-pairing-desk' => array(
				'title'       => __( 'Homepage Section — Pairing Desk', 'lunara-film' ),
				'description' => __( 'The Pairing Desk signature section from Homepage Studio.', 'lunara-film' ),
				'keywords'    => array( 'homepage', 'pairing', 'debrief' ),
				'content'     => '<!-- wp:lunara/pairing-desk /-->',
			),
		);

		$dispatch_patterns = array(
			'lunara/starter-dispatch-journal-feed' => array(
				'block'       => 'lunara-dispatch/journal-feed',
				'title'       => __( 'Journal Feed — Latest', 'lunara-film' ),
				'description' => __( 'A query-driven six-story Journal grid that updates as entries are published.', 'lunara-film' ),
				'keywords'    => array( 'journal', 'dispatch', 'feed' ),
				'content'     => '<!-- wp:lunara-dispatch/journal-feed {"count":6,"layout":"grid","showImage":true,"showExcerpt":true,"excludeArchive":true} /-->',
			),
			'lunara/starter-dispatch-journal-spotlight' => array(
				'block'       => 'lunara-dispatch/journal-spotlight',
				'title'       => __( 'Journal Spotlight — Latest', 'lunara-film' ),
				'description' => __( 'A query-driven latest-story spotlight with three supporting Journal entries.', 'lunara-film' ),
				'keywords'    => array( 'journal', 'dispatch', 'spotlight' ),
				'content'     => '<!-- wp:lunara-dispatch/journal-spotlight {"count":4,"showImage":true,"showExcerpt":true,"excludeArchive":true} /-->',
			),
			'lunara/starter-dispatch-journal-lanes' => array(
				'block'       => 'lunara-dispatch/journal-lanes',
				'title'       => __( 'Journal Lanes', 'lunara-film' ),
				'description' => __( 'A query-driven directory of Journal types with live entry counts.', 'lunara-film' ),
				'keywords'    => array( 'journal', 'dispatch', 'lanes' ),
				'content'     => '<!-- wp:lunara-dispatch/journal-lanes {"showCounts":true,"hideEmpty":false} /-->',
			),
		);

		foreach ( $dispatch_patterns as $name => $pattern ) {
			$block_name = $pattern['block'];
			unset( $pattern['block'] );
			if ( $block_registry && $block_registry->is_registered( $block_name ) ) {
				$patterns[ $name ] = $pattern;
			}
		}

		foreach ( $patterns as $name => $pattern ) {
			if ( $registry->is_registered( $name ) ) {
				continue;
			}
			$pattern['categories'] = array( 'lunara' );
			register_block_pattern( $name, $pattern );
		}
	}
	add_action( 'init', 'lunara_blocks_hub_register_patterns', 20 );
}
