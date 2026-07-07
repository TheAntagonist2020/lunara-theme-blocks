<?php
/**
 * Homepage dynamic block registration and editor previews.
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

if ( ! function_exists( 'lunara_register_homepage_blocks' ) ) {
	function lunara_register_homepage_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$common = array(
			'api_version'   => 3,
			'category'      => 'lunara',
			'editor_script' => 'lunara-blocks',
			'supports'      => array(
				'html'      => false,
				'reusable'  => false,
				'multiple'  => true,
				// Hybrid homepage composition (3.1.50): these ARE the homepage
				// kit. front-page.php renders the Home page's blocks when any
				// are present (order = block order, presence = visibility);
				// Homepage Studio packages write through to the same blocks.
				'inserter'  => true,
			),
		);

		register_block_type( 'lunara/cinematic-hero', array_merge( $common, array(
			'title'           => __( 'Homepage: Cinematic Hero', 'lunara-film' ),
			'icon'            => 'cover-image',
			'description'     => __( 'The rotating cinematic hero. Slides are curated in Control Desk → Hero Command (or auto-fill from the newest content); the override fields apply to the single-slide fallback.', 'lunara-film' ),
			'attributes'      => array(
				'overrideImageId' => array( 'type' => 'number', 'default' => 0 ),
				'overrideKicker'  => array( 'type' => 'string', 'default' => '' ),
				'overrideTitle'   => array( 'type' => 'string', 'default' => '' ),
				'overrideExcerpt' => array( 'type' => 'string', 'default' => '' ),
				'overrideUrl'     => array( 'type' => 'string', 'default' => '' ),
				'overrideCta'     => array( 'type' => 'string', 'default' => '' ),
			),
			'render_callback' => function ( $attributes ) {
				// Hero Command-aware carousel — identical to what front-page.php
				// renders; falls back internally to the static hero (with these
				// overrides) when fewer than two slides qualify.
				if ( function_exists( 'lunara_render_cinematic_hero_carousel' ) ) {
					return lunara_render_cinematic_hero_carousel( is_array( $attributes ) ? $attributes : array() );
				}
				return function_exists( 'lunara_render_cinematic_hero' ) ? lunara_render_cinematic_hero( $attributes ) : '';
			},
		) ) );

		register_block_type( 'lunara/journal-lane', array_merge( $common, array(
			'title'           => __( 'Homepage: Journal Lane', 'lunara-film' ),
			'icon'            => 'editor-ul',
			'description'     => __( 'The Journal home grid: 1 lead card + 3 supporting cards from the most recent dispatch posts.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_homepage_journal_lane' ) ? lunara_render_homepage_journal_lane() : '';
			},
		) ) );

		register_block_type( 'lunara/oscar-picks', array_merge( $common, array(
			'title'           => __( 'Homepage: Oscar Picks', 'lunara-film' ),
			'icon'            => 'awards',
			'description'     => __( 'Horizontal carousel of curated behind-the-scenes Oscar pick cards.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_oscar_picks_carousel' ) ? lunara_render_oscar_picks_carousel() : '';
			},
		) ) );

		register_block_type( 'lunara/oscar-facts', array_merge( $common, array(
			'title'           => __( 'Homepage: Oscar Facts', 'lunara-film' ),
			'icon'            => 'lightbulb',
			'description'     => __( 'Text-forward Oscar fact cards in a horizontal carousel. Image-on-the-left when a featured image is set.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_oscar_facts_carousel' ) ? lunara_render_oscar_facts_carousel() : '';
			},
		) ) );

		register_block_type( 'lunara/pairing-desk', array_merge( $common, array(
			'title'           => __( 'Homepage: Pairing Desk', 'lunara-film' ),
			'icon'            => 'screenoptions',
			'description'     => __( 'The signature Pair It With showcase — three films in conversation with the newest paired review. Copy is editable in Control Desk.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_home_pairing_desk' ) ? lunara_render_home_pairing_desk() : '';
			},
		) ) );
	}
	add_action( 'init', 'lunara_register_homepage_blocks', 100 );
}

if ( ! function_exists( 'lunara_enqueue_homepage_block_editor_assets' ) ) {
	function lunara_enqueue_homepage_block_editor_assets() {
		wp_register_script(
			'lunara-homepage-blocks-editor',
			'',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'1.1.0',
			true
		);
		wp_enqueue_script( 'lunara-homepage-blocks-editor' );

		$js = <<<'JS'
(function (blocks, element, ssrPkg, blockEditor, components, i18n) {
	if (!blocks || !element) { return; }
	var el = element.createElement;
	var Fragment = element.Fragment;
	var ServerSideRender = (ssrPkg && (ssrPkg.default || ssrPkg)) || (window.wp && window.wp.serverSideRender);
	if (!ServerSideRender) { return; }

	var InspectorControls = blockEditor && blockEditor.InspectorControls;
	var MediaUpload = blockEditor && blockEditor.MediaUpload;
	var PanelBody = components && components.PanelBody;
	var SelectControl = components && components.SelectControl;
	var TextControl = components && components.TextControl;
	var TextareaControl = components && components.TextareaControl;
	var Button = components && components.Button;
	var __ = (i18n && i18n.__) || function (s) { return s; };

	// CINEMATIC HERO with rich override controls in the sidebar
	if (!blocks.getBlockType || !blocks.getBlockType('lunara/cinematic-hero')) {
		blocks.registerBlockType('lunara/cinematic-hero', {
			apiVersion: 3,
			title: __('Lunara Cinematic Hero'),
			icon: 'cover-image',
			category: 'theme',
			description: __('Full-viewport image-first opener. Per-instance overrides for image / kicker / title / excerpt / URL / CTA.'),
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			attributes: {
				overrideImageId: { type: 'number', default: 0 },
				overrideKicker:  { type: 'string', default: '' },
				overrideTitle:   { type: 'string', default: '' },
				overrideExcerpt: { type: 'string', default: '' },
				overrideUrl:     { type: 'string', default: '' },
				overrideCta:     { type: 'string', default: '' }
			},
			edit: function (props) {
				var attrs = props.attributes;
				var setAttrs = props.setAttributes;

				var inspector = InspectorControls && PanelBody ? el(InspectorControls, {},
					el(PanelBody, { title: __('Hero Overrides'), initialOpen: true },
						el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 12px' } },
							__('Leave any field blank to use the auto value (latest review or Customizer override).')
						),
						MediaUpload ? el(MediaUpload, {
							onSelect: function (media) { setAttrs({ overrideImageId: media.id }); },
							allowedTypes: ['image'],
							value: attrs.overrideImageId,
							render: function (obj) {
								return el('div', { style: { marginBottom: '14px' } },
									el(Button, { onClick: obj.open, variant: 'secondary' },
										attrs.overrideImageId ? __('Replace hero image') : __('Select hero image')
									),
									attrs.overrideImageId ? el(Button, {
										onClick: function () { setAttrs({ overrideImageId: 0 }); },
										variant: 'tertiary',
										style: { marginLeft: '8px', color: '#cc1818' }
									}, __('Clear')) : null,
									attrs.overrideImageId ? el('div', { style: { fontSize: '11px', color: '#888', marginTop: '6px' } },
										__('Image ID: ') + attrs.overrideImageId
									) : null
								);
							}
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Kicker (override)'),
							value: attrs.overrideKicker,
							onChange: function (v) { setAttrs({ overrideKicker: v }); },
							placeholder: __('e.g. LUNARA SPOTLIGHT')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Title (override)'),
							value: attrs.overrideTitle,
							onChange: function (v) { setAttrs({ overrideTitle: v }); },
							placeholder: __('Custom headline')
						}) : null,
						TextareaControl ? el(TextareaControl, {
							label: __('Excerpt (override)'),
							value: attrs.overrideExcerpt,
							onChange: function (v) { setAttrs({ overrideExcerpt: v }); },
							placeholder: __('Optional 1-2 sentence dek')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA URL (override)'),
							value: attrs.overrideUrl,
							onChange: function (v) { setAttrs({ overrideUrl: v }); },
							placeholder: __('https://')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA button text (override)'),
							value: attrs.overrideCta,
							onChange: function (v) { setAttrs({ overrideCta: v }); },
							placeholder: __('Read the review')
						}) : null
					)
				) : null;

				var preview = el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						__('Lunara Cinematic Hero')
					),
					el(ServerSideRender, {
						block: 'lunara/cinematic-hero',
						attributes: attrs,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(no hero image â€” set an override image or publish a review with a featured image)'));
						}
					})
				);

				return el(Fragment, {}, inspector, preview);
			},
			save: function () { return null; }
		});
	}

	// LATEST REVIEWS with override controls in the sidebar
	if (!blocks.getBlockType || !blocks.getBlockType('lunara/latest-reviews')) {
		blocks.registerBlockType('lunara/latest-reviews', {
			apiVersion: 3,
			title: __('Lunara Latest Reviews'),
			icon: 'star-filled',
			category: 'theme',
			description: __('Grid of curated homepage reviews or the most recent published reviews. Per-instance overrides for source / count / heading / kicker / CTA.'),
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			attributes: {
				source:   { type: 'string', default: 'curated' },
				count:    { type: 'number', default: 8 },
				heading:  { type: 'string', default: '' },
				kicker:   { type: 'string', default: '' },
				ctaLabel: { type: 'string', default: '' },
				ctaUrl:   { type: 'string', default: '' }
			},
			edit: function (props) {
				var attrs = props.attributes;
				var setAttrs = props.setAttributes;

				var inspector = InspectorControls && PanelBody ? el(InspectorControls, {},
					el(PanelBody, { title: __('Latest Reviews Settings'), initialOpen: true },
						el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 12px' } },
							__('Default source uses the homepage curated review shelf, then falls back to featured/latest reviews.')
						),
						SelectControl ? el(SelectControl, {
							label: __('Source'),
							value: attrs.source || 'curated',
							options: [
								{ label: __('Homepage curated shelf'), value: 'curated' },
								{ label: __('Newest reviews'), value: 'latest' },
								{ label: __('Top homepage showcase'), value: 'hero' }
							],
							onChange: function (v) { setAttrs({ source: v || 'curated' }); },
							help: __('Use curated shelf when you want to feature an older review again.')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Count'),
							type: 'number',
							value: attrs.count,
							onChange: function (v) { setAttrs({ count: Math.max(1, Math.min(24, parseInt(v, 10) || 8)) }); },
							help: __('Between 1 and 24.')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Heading (override)'),
							value: attrs.heading,
							onChange: function (v) { setAttrs({ heading: v }); },
							placeholder: __('Latest Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Kicker (override)'),
							value: attrs.kicker,
							onChange: function (v) { setAttrs({ kicker: v }); },
							placeholder: __('Lunara Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA label (override)'),
							value: attrs.ctaLabel,
							onChange: function (v) { setAttrs({ ctaLabel: v }); },
							placeholder: __('All Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA URL (override)'),
							value: attrs.ctaUrl,
							onChange: function (v) { setAttrs({ ctaUrl: v }); },
							placeholder: __('https://lunarafilm.com/reviews/')
						}) : null
					)
				) : null;

				var preview = el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						__('Lunara Latest Reviews')
					),
					el(ServerSideRender, {
						block: 'lunara/latest-reviews',
						attributes: attrs,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(no published reviews yet)'));
						}
					})
				);

				return el(Fragment, {}, inspector, preview);
			},
			save: function () { return null; }
		});
	}

	// Simpler blocks (no per-instance attributes yet) â€” journal/picks/facts
	var simpleDefs = [
		{ name: 'lunara/journal-lane', title: __('Lunara Journal Lane'), icon: 'editor-ul',  description: __('The Journal home grid: 1 lead + 3 supporting cards.') },
		{ name: 'lunara/oscar-picks',  title: __('Lunara Oscar Picks'),  icon: 'awards',     description: __('Behind-the-scenes editorial Oscar picks carousel.') },
		{ name: 'lunara/oscar-facts',  title: __('Lunara Oscar Facts'),  icon: 'lightbulb',  description: __('Text-forward Oscar fact carousel.') }
	];

	simpleDefs.forEach(function (def) {
		if (blocks.getBlockType && blocks.getBlockType(def.name)) { return; }

		blocks.registerBlockType(def.name, {
			apiVersion: 3,
			title: def.title,
			icon: def.icon,
			category: 'theme',
			description: def.description,
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			edit: function () {
				return el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						def.title
					),
					el(ServerSideRender, {
						block: def.name,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(empty â€” publish a record to see this block render)'));
						}
					})
				);
			},
			save: function () { return null; }
		});
	});
})(
	window.wp && window.wp.blocks,
	window.wp && window.wp.element,
	window.wp && window.wp.serverSideRender,
	window.wp && window.wp.blockEditor,
	window.wp && window.wp.components,
	window.wp && window.wp.i18n
);
JS;

		wp_add_inline_script( 'lunara-homepage-blocks-editor', $js );
	}
	add_action( 'enqueue_block_editor_assets', 'lunara_enqueue_homepage_block_editor_assets' );
}

if ( ! function_exists( 'lunara_register_latest_reviews_block' ) ) {
	function lunara_register_latest_reviews_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'lunara/latest-reviews', array(
			'api_version' => 3,
			'category'    => 'theme',
			'title'       => __( 'Lunara Latest Reviews', 'lunara-film' ),
			'icon'        => 'star-filled',
			'description' => __( 'Grid of curated homepage reviews or newest published reviews. Per-instance source + count + heading + kicker + CTA overrides.', 'lunara-film' ),
			'supports'    => array(
				'html'     => false,
				'reusable' => false,
				'multiple' => true,
				'inserter' => true,
			),
			'attributes'  => array(
				'source'   => array( 'type' => 'string', 'default' => 'curated' ),
				'count'    => array( 'type' => 'number', 'default' => 8 ),
				'heading'  => array( 'type' => 'string', 'default' => '' ),
				'kicker'   => array( 'type' => 'string', 'default' => '' ),
				'ctaLabel' => array( 'type' => 'string', 'default' => '' ),
				'ctaUrl'   => array( 'type' => 'string', 'default' => '' ),
			),
			'render_callback' => function ( $attributes ) {
				return function_exists( 'lunara_render_homepage_latest_reviews' ) ? lunara_render_homepage_latest_reviews( $attributes ) : '';
			},
		) );
	}
	add_action( 'init', 'lunara_register_latest_reviews_block', 100 );
}
