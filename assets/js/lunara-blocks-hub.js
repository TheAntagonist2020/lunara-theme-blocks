/* global window */
( function ( plugins, editPost, editor, element, components, blocks, data, i18n ) {
	'use strict';

	if ( ! plugins || ! plugins.registerPlugin || ! element || ! components || ! blocks || ! data ) {
		return;
	}

	editPost = editPost || {};
	editor   = editor || {};
	var el                           = element.createElement;
	var Fragment                     = element.Fragment;
	var __                            = i18n && i18n.__ ? i18n.__ : function ( text ) { return text; };
	var PluginSidebar                = editPost.PluginSidebar || editor.PluginSidebar;
	var PluginSidebarMoreMenuItem    = editPost.PluginSidebarMoreMenuItem || editor.PluginSidebarMoreMenuItem;
	var Panel                        = components.Panel;
	var PanelBody                    = components.PanelBody;
	var Button                       = components.Button;

	if ( ! PluginSidebar || ! PluginSidebarMoreMenuItem || ! PanelBody || ! Button ) {
		return;
	}

	var GROUPS = [
		{
			label: __( 'Content blocks', 'lunara-film' ),
			items: [
				{ name: 'lunara/reviews-grid', label: __( 'Reviews Grid', 'lunara-film' ), desc: __( 'Curate a responsive grid of reviews.', 'lunara-film' ), icon: 'star-filled' },
				{ name: 'lunara/journal-grid', label: __( 'Journal Grid', 'lunara-film' ), desc: __( 'Arrange dispatches and essays in a lead grid.', 'lunara-film' ), icon: 'editor-ul' },
				{ name: 'lunara/media-showcase', label: __( 'Media Showcase', 'lunara-film' ), desc: __( 'Build a gallery, slider, or carousel from stills.', 'lunara-film' ), icon: 'format-gallery' },
				{ name: 'lunara/pairing', label: __( 'Pair It With — Curated', 'lunara-film' ), desc: __( 'Place three films in an editorial conversation.', 'lunara-film' ), icon: 'screenoptions' }
			]
		},
		{
			label: __( 'Homepage sections', 'lunara-film' ),
			items: [
				{ name: 'lunara/cinematic-hero', label: __( 'Cinematic Hero', 'lunara-film' ), desc: __( 'The rotating cinematic lead section.', 'lunara-film' ), icon: 'cover-image' },
				{ name: 'lunara/journal-lane', label: __( 'Journal Lane', 'lunara-film' ), desc: __( 'The homepage Journal lead and supporting cards.', 'lunara-film' ), icon: 'editor-ul' },
				{ name: 'lunara/oscar-picks', label: __( 'Oscar Picks', 'lunara-film' ), desc: __( 'A horizontal carousel of Oscar picks.', 'lunara-film' ), icon: 'awards' },
				{ name: 'lunara/oscar-facts', label: __( 'Oscar Facts', 'lunara-film' ), desc: __( 'Text-forward Oscar fact cards.', 'lunara-film' ), icon: 'lightbulb' },
				{ name: 'lunara/pairing-desk', label: __( 'Pairing Desk', 'lunara-film' ), desc: __( 'The signature three-film Pair It With section.', 'lunara-film' ), icon: 'screenoptions' }
			]
		}
	];

	function insert( name ) {
		if ( ! blocks.createBlock || ! data.select || ! data.dispatch ) {
			return;
		}
		var blockEditor = data.dispatch( 'core/block-editor' );
		if ( ! blockEditor || ! blockEditor.insertBlocks ) {
			return;
		}
		blockEditor.insertBlocks( blocks.createBlock( name ) );
	}

	function blockCard( item ) {
		return el(
			'div',
			{ className: 'lunara-studio-card', key: item.name, style: { borderBottom: '1px solid #ddd', padding: '10px 0' } },
			el(
				'div',
				{ style: { display: 'flex', alignItems: 'flex-start', gap: '8px', marginBottom: '6px' } },
				el( 'span', { className: 'dashicons dashicons-' + item.icon, 'aria-hidden': true } ),
				el(
					'div',
					{},
					el( 'strong', {}, item.label ),
					el( 'p', { style: { margin: '2px 0 0', opacity: 0.72 } }, item.desc )
				)
			),
			el( Button, { variant: 'primary', onClick: function () { insert( item.name ); } }, __( 'Insert', 'lunara-film' ) )
		);
	}

	function render() {
		var children = [
			el( PluginSidebarMoreMenuItem, { target: 'lunara-studio', icon: 'video-alt2', key: 'menu' }, __( 'Lunara Studio', 'lunara-film' ) )
		];
		var panelChildren = [
			el( 'p', { key: 'intro' }, __( 'Insert Lunara’s flagship blocks and homepage sections, then fine-tune every instance in the block settings.', 'lunara-film' ) )
		];

		GROUPS.forEach( function ( group ) {
			panelChildren.push( el( 'h3', { key: group.label, style: { fontSize: '11px', letterSpacing: '0.04em', textTransform: 'uppercase', margin: '18px 0 4px' } }, group.label ) );
			group.items.forEach( function ( item ) {
				panelChildren.push( blockCard( item ) );
			} );
		} );
		panelChildren.push( el( 'p', { key: 'hint', style: { marginTop: '18px', opacity: 0.72 } }, __( 'For complete page starters, browse the Lunara category in the Patterns tab.', 'lunara-film' ) ) );

		children.push(
			el(
				PluginSidebar,
				{ name: 'lunara-studio', title: __( 'Lunara Studio', 'lunara-film' ), icon: 'video-alt2' },
				Panel ? el( Panel, {}, panelChildren ) : panelChildren
			)
		);

		return el( Fragment, {}, children );
	}

	plugins.registerPlugin( 'lunara-studio', { icon: 'video-alt2', render: render } );
} )(
	window.wp && window.wp.plugins,
	window.wp && window.wp.editPost,
	window.wp && window.wp.editor,
	window.wp && window.wp.element,
	window.wp && window.wp.components,
	window.wp && window.wp.blocks,
	window.wp && window.wp.data,
	window.wp && window.wp.i18n
);
