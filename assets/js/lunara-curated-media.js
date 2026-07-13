/**
 * Curated Media — editor UI for lunara/media-showcase.
 *
 * The image sibling of the curated grid editor. Pick images from the media
 * library, reorder their slots, feature one (jump to slot 1), or drop it —
 * then flip the whole set between gallery / slider / carousel from one dial,
 * with a live server-rendered preview of the exact front-end markup.
 */
( function ( blocks, blockEditor, components, element, i18n, serverSideRender, apiFetch, url ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var useState = element.useState;
	var useEffect = element.useEffect;
	var __ = i18n.__;

	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var Button = components.Button;
	var Notice = components.Notice;
	var ServerSideRender = ( serverSideRender && ( serverSideRender.default || serverSideRender ) ) || serverSideRender;

	// id → { thumb, alt } cache so the slot list can show real thumbnails.
	var mediaCache = {};

	function hydrateThumbs( ids ) {
		var missing = ids.filter( function ( id ) {
			return ! mediaCache[ id ];
		} );
		if ( ! missing.length ) {
			return Promise.resolve();
		}
		return apiFetch( {
			path: url.addQueryArgs( '/wp/v2/media', {
				include: missing.join( ',' ),
				per_page: missing.length,
				_fields: 'id,source_url,alt_text,media_details'
			} )
		} ).then( function ( results ) {
			( results || [] ).forEach( function ( item ) {
				var thumb = item.source_url;
				if ( item.media_details && item.media_details.sizes && item.media_details.sizes.thumbnail ) {
					thumb = item.media_details.sizes.thumbnail.source_url;
				}
				mediaCache[ item.id ] = { thumb: thumb, alt: item.alt_text || '' };
			} );
		} ).catch( function () {} );
	}

	function moveItem( list, from, to ) {
		var next = list.slice();
		if ( to < 0 || to >= next.length ) {
			return next;
		}
		var item = next.splice( from, 1 )[ 0 ];
		next.splice( to, 0, item );
		return next;
	}

	/**
	 * The picker: an "Add / choose images" media-library button plus the
	 * ordered slot list (feature / up / down / remove per image).
	 */
	function MediaPicker( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var ids = ( attributes.ids || [] ).map( Number );

		var tickState = useState( 0 );
		var setTick = tickState[ 1 ];

		useEffect( function () {
			if ( ids.length ) {
				hydrateThumbs( ids ).then( function () {
					setTick( function ( t ) { return t + 1; } );
				} );
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ ids.join( ',' ) ] );

		function setIds( next ) {
			setAttributes( { ids: next } );
		}

		// Merge a media-library selection: keep already-picked slots in their
		// current order (so featuring/reordering survives re-opening the modal),
		// then append newly-added images in selection order.
		function onSelect( selection ) {
			var picked = ( selection || [] ).map( function ( m ) { return Number( m.id ); } );
			( selection || [] ).forEach( function ( m ) {
				var thumb = m.url;
				if ( m.sizes && m.sizes.thumbnail ) {
					thumb = m.sizes.thumbnail.url;
				}
				mediaCache[ Number( m.id ) ] = { thumb: thumb, alt: m.alt || '' };
			} );
			var kept = ids.filter( function ( id ) {
				return picked.indexOf( id ) !== -1;
			} );
			var added = picked.filter( function ( id ) {
				return kept.indexOf( id ) === -1;
			} );
			setIds( kept.concat( added ) );
		}

		var rowStyle = {
			display: 'flex',
			alignItems: 'center',
			gap: '4px',
			padding: '4px 0',
			borderBottom: '1px solid rgba(128,128,128,0.15)'
		};
		var thumbStyle = {
			width: '40px',
			height: '40px',
			objectFit: 'cover',
			borderRadius: '4px',
			flex: '0 0 auto',
			background: 'rgba(128,128,128,0.15)'
		};

		return el( Fragment, {},
			el( MediaUploadCheck, {},
				el( MediaUpload, {
					multiple: true,
					gallery: false,
					allowedTypes: [ 'image' ],
					value: ids,
					onSelect: onSelect,
					render: function ( open ) {
						return el( Button, {
							variant: 'primary',
							onClick: open,
							style: { marginBottom: '10px' }
						}, ids.length
							? __( 'Add / choose images', 'lunara-film' )
							: __( 'Choose images', 'lunara-film' ) );
					}
				} )
			),
			el( 'p', { style: { fontSize: '11px', opacity: 0.75, marginTop: '4px' } },
				ids.length
					? __( 'Slot 1 is featured / the first slide. Reorder freely — the showcase follows this list.', 'lunara-film' )
					: __( 'Nothing picked yet — choose images from the media library to build the showcase.', 'lunara-film' )
			),
			ids.map( function ( id, index ) {
				var entry = mediaCache[ id ];
				return el( 'div', { key: 'm' + id, style: rowStyle },
					el( 'strong', { style: { fontSize: '11px', minWidth: '16px', opacity: 0.6 } }, String( index + 1 ) ),
					entry && entry.thumb
						? el( 'img', { src: entry.thumb, alt: entry.alt || '', style: thumbStyle } )
						: el( 'span', { style: thumbStyle } ),
					el( 'span', { style: { flex: '1 1 auto' } } ),
					el( Button, {
						icon: 'star-filled',
						size: 'small',
						label: __( 'Feature (move to slot 1)', 'lunara-film' ),
						disabled: 0 === index,
						onClick: function () {
							setIds( moveItem( ids, index, 0 ) );
						}
					} ),
					el( Button, {
						icon: 'arrow-up-alt2',
						size: 'small',
						label: __( 'Move up', 'lunara-film' ),
						disabled: 0 === index,
						onClick: function () {
							setIds( moveItem( ids, index, index - 1 ) );
						}
					} ),
					el( Button, {
						icon: 'arrow-down-alt2',
						size: 'small',
						label: __( 'Move down', 'lunara-film' ),
						disabled: index === ids.length - 1,
						onClick: function () {
							setIds( moveItem( ids, index, index + 1 ) );
						}
					} ),
					el( Button, {
						icon: 'no-alt',
						size: 'small',
						isDestructive: true,
						label: __( 'Remove', 'lunara-film' ),
						onClick: function () {
							setIds( ids.filter( function ( existing ) {
								return existing !== id;
							} ) );
						}
					} )
				);
			} )
		);
	}

	blocks.registerBlockType( 'lunara/media-showcase', {
		apiVersion: 3,
		title: __( 'Lunara Media Showcase — Gallery / Slider / Carousel', 'lunara-film' ),
		icon: 'format-gallery',
		category: 'lunara',
		description: __( 'Hand-pick and reorder images from the media library, then present them as a gallery, slider, or carousel. Every presentation dial is per-instance.', 'lunara-film' ),
		supports: { html: false, anchor: true, align: [ 'wide', 'full' ], multiple: true },
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var display = attributes.display || 'gallery';

			function set( key ) {
				return function ( value ) {
					var next = {};
					next[ key ] = value;
					setAttributes( next );
				};
			}

			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Images', 'lunara-film' ), initialOpen: true },
						el( MediaPicker, { attributes: attributes, setAttributes: setAttributes } )
					),
					el( PanelBody, { title: __( 'Presentation', 'lunara-film' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Display as', 'lunara-film' ),
							value: display,
							options: [
								{ label: __( 'Gallery (grid)', 'lunara-film' ), value: 'gallery' },
								{ label: __( 'Slider (scroll rail)', 'lunara-film' ), value: 'slider' },
								{ label: __( 'Carousel (one at a time)', 'lunara-film' ), value: 'carousel' }
							],
							onChange: set( 'display' )
						} ),
						'gallery' === display && el( RangeControl, {
							label: __( 'Columns', 'lunara-film' ),
							value: attributes.columns,
							min: 2,
							max: 5,
							onChange: set( 'columns' )
						} ),
						'gallery' === display && el( ToggleControl, {
							label: __( 'Feature first image (mosaic)', 'lunara-film' ),
							checked: !! attributes.featureFirst,
							onChange: set( 'featureFirst' )
						} ),
						el( SelectControl, {
							label: __( 'Aspect ratio', 'lunara-film' ),
							value: attributes.aspectRatio,
							options: [
								{ label: __( 'Natural (uncropped)', 'lunara-film' ), value: 'auto' },
								{ label: __( 'Square (1:1)', 'lunara-film' ), value: 'square' },
								{ label: __( 'Portrait (3:4)', 'lunara-film' ), value: 'portrait' },
								{ label: __( 'Landscape (4:3)', 'lunara-film' ), value: 'landscape' },
								{ label: __( 'Wide (16:9)', 'lunara-film' ), value: 'wide' },
								{ label: __( 'Cinemascope (21:9)', 'lunara-film' ), value: 'cinema' }
							],
							onChange: set( 'aspectRatio' )
						} ),
						'carousel' !== display && el( SelectControl, {
							label: __( 'Gap', 'lunara-film' ),
							value: attributes.gap,
							options: [
								{ label: __( 'Tight', 'lunara-film' ), value: 'tight' },
								{ label: __( 'Normal', 'lunara-film' ), value: 'normal' },
								{ label: __( 'Roomy', 'lunara-film' ), value: 'roomy' }
							],
							onChange: set( 'gap' )
						} ),
						el( ToggleControl, {
							label: __( 'Rounded corners', 'lunara-film' ),
							checked: !! attributes.rounded,
							onChange: set( 'rounded' )
						} )
					),
					el( PanelBody, { title: __( 'Behavior', 'lunara-film' ), initialOpen: false },
						'carousel' === display && el( ToggleControl, {
							label: __( 'Autoplay', 'lunara-film' ),
							checked: !! attributes.autoplay,
							onChange: set( 'autoplay' )
						} ),
						'carousel' === display && !! attributes.autoplay && el( RangeControl, {
							label: __( 'Autoplay speed (ms)', 'lunara-film' ),
							value: attributes.autoplaySpeed,
							min: 1500,
							max: 20000,
							step: 500,
							onChange: set( 'autoplaySpeed' )
						} ),
						'carousel' === display && el( ToggleControl, {
							label: __( 'Show dots', 'lunara-film' ),
							checked: !! attributes.showDots,
							onChange: set( 'showDots' )
						} ),
						'slider' === display && el( ToggleControl, {
							label: __( 'Show arrows', 'lunara-film' ),
							checked: !! attributes.showArrows,
							onChange: set( 'showArrows' )
						} ),
						el( SelectControl, {
							label: __( 'Link images to', 'lunara-film' ),
							value: attributes.linkTo,
							options: [
								{ label: __( 'Nothing', 'lunara-film' ), value: 'none' },
								{ label: __( 'Media file', 'lunara-film' ), value: 'media' },
								{ label: __( 'Attachment page', 'lunara-film' ), value: 'attachment' }
							],
							onChange: set( 'linkTo' )
						} ),
						el( ToggleControl, {
							label: __( 'Show captions', 'lunara-film' ),
							checked: !! attributes.showCaptions,
							onChange: set( 'showCaptions' )
						} )
					),
					el( PanelBody, { title: __( 'Section header', 'lunara-film' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show section header', 'lunara-film' ),
							checked: !! attributes.showHeader,
							onChange: set( 'showHeader' )
						} ),
						!! attributes.showHeader && el( Fragment, {},
							el( TextControl, { label: __( 'Kicker', 'lunara-film' ), value: attributes.kicker || '', onChange: set( 'kicker' ) } ),
							el( TextControl, { label: __( 'Heading', 'lunara-film' ), value: attributes.heading || '', onChange: set( 'heading' ) } ),
							el( TextControl, { label: __( 'CTA label', 'lunara-film' ), value: attributes.ctaLabel || '', onChange: set( 'ctaLabel' ) } ),
							el( TextControl, { label: __( 'CTA URL', 'lunara-film' ), value: attributes.ctaUrl || '', onChange: set( 'ctaUrl' ) } )
						)
					)
				),
				el( 'div', { className: 'lunara-block-preview' },
					( attributes.ids && attributes.ids.length )
						? ( ServerSideRender
							? el( ServerSideRender, { block: 'lunara/media-showcase', attributes: attributes } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'Preview unavailable.', 'lunara-film' ) ) )
						: el( Notice, { status: 'info', isDismissible: false }, __( 'Choose images to build the showcase.', 'lunara-film' ) )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender,
	window.wp.apiFetch,
	window.wp.url
);
