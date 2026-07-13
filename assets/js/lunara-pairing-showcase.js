/**
 * Pairing Showcase — editor UI for lunara/pairing.
 *
 * Hand-build the signature Pair It With per instance: add a pairing card,
 * fill its role label / film / note / poster, reorder the slots (slot 1 is
 * featured), feature or drop a card — or switch the source to mirror an
 * existing review's automatic pairings. Live server-rendered preview under it.
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
	var TextareaControl = components.TextareaControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var Button = components.Button;
	var Notice = components.Notice;
	var Spinner = components.Spinner;
	var ServerSideRender = ( serverSideRender && ( serverSideRender.default || serverSideRender ) ) || serverSideRender;

	// Common role labels the signature feature uses — offered as suggestions,
	// but the field is free text so any relation works.
	var LABEL_SUGGESTIONS = [ 'Theme Echo', 'Counter-Program', 'Career Context', 'Double Feature', 'Origin Story', 'The Antidote' ];

	// id → poster thumbnail cache for the override preview.
	var posterCache = {};

	function moveItem( list, from, to ) {
		var next = list.slice();
		if ( to < 0 || to >= next.length ) {
			return next;
		}
		var item = next.splice( from, 1 )[ 0 ];
		next.splice( to, 0, item );
		return next;
	}

	function hydratePosters( ids ) {
		var missing = ids.filter( function ( id ) {
			return id && ! posterCache[ id ];
		} );
		if ( ! missing.length ) {
			return Promise.resolve();
		}
		return apiFetch( {
			path: url.addQueryArgs( '/wp/v2/media', {
				include: missing.join( ',' ),
				per_page: missing.length,
				_fields: 'id,source_url,media_details'
			} )
		} ).then( function ( results ) {
			( results || [] ).forEach( function ( item ) {
				var thumb = item.source_url;
				if ( item.media_details && item.media_details.sizes && item.media_details.sizes.thumbnail ) {
					thumb = item.media_details.sizes.thumbnail.source_url;
				}
				posterCache[ item.id ] = thumb;
			} );
		} ).catch( function () {} );
	}

	// id → title cache for the chosen review, so re-opening doesn't refetch.
	var reviewTitleCache = {};

	/**
	 * Single-select review search picker — the same search-and-click UX the
	 * curated grids use, so mirror mode never asks for a raw post ID.
	 */
	function ReviewPicker( props ) {
		var value = Number( props.value || 0 );
		var onChange = props.onChange;

		var termState = useState( '' );
		var term = termState[ 0 ];
		var setTerm = termState[ 1 ];

		var resultsState = useState( [] );
		var results = resultsState[ 0 ];
		var setResults = resultsState[ 1 ];

		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];

		var labelState = useState( value && reviewTitleCache[ value ] ? reviewTitleCache[ value ] : '' );
		var label = labelState[ 0 ];
		var setLabel = labelState[ 1 ];

		// Hydrate the chosen review's title so the selection reads by name.
		useEffect( function () {
			if ( value && ! reviewTitleCache[ value ] ) {
				apiFetch( {
					path: url.addQueryArgs( '/wp/v2/search', {
						include: value,
						type: 'post',
						subtype: 'review',
						per_page: 1,
						_fields: 'id,title'
					} )
				} ).then( function ( r ) {
					if ( r && r[ 0 ] ) {
						reviewTitleCache[ value ] = r[ 0 ].title;
						setLabel( r[ 0 ].title );
					}
				} ).catch( function () {} );
			} else if ( value && reviewTitleCache[ value ] ) {
				setLabel( reviewTitleCache[ value ] );
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ value ] );

		useEffect( function () {
			if ( ! term || term.length < 2 ) {
				setResults( [] );
				return;
			}
			var alive = true;
			setBusy( true );
			var timer = setTimeout( function () {
				apiFetch( {
					path: url.addQueryArgs( '/wp/v2/search', {
						search: term,
						type: 'post',
						subtype: 'review',
						per_page: 10,
						_fields: 'id,title'
					} )
				} ).then( function ( found ) {
					if ( ! alive ) {
						return;
					}
					( found || [] ).forEach( function ( item ) {
						reviewTitleCache[ item.id ] = item.title;
					} );
					setResults( found || [] );
					setBusy( false );
				} ).catch( function () {
					if ( alive ) {
						setResults( [] );
						setBusy( false );
					}
				} );
			}, 350 );
			return function () {
				alive = false;
				clearTimeout( timer );
			};
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ term ] );

		function choose( id, title ) {
			reviewTitleCache[ id ] = title;
			setLabel( title );
			setTerm( '' );
			setResults( [] );
			onChange( id );
		}

		var rowStyle = {
			display: 'flex',
			alignItems: 'center',
			gap: '4px',
			padding: '4px 0',
			borderBottom: '1px solid rgba(128,128,128,0.15)'
		};

		if ( value ) {
			return el( Fragment, {},
				el( 'p', { style: { fontSize: '12px', margin: '0 0 6px' } },
					el( 'strong', {}, __( 'Mirroring: ', 'lunara-film' ) ),
					label || ( '#' + value )
				),
				el( Button, {
					variant: 'secondary',
					size: 'small',
					isDestructive: true,
					onClick: function () {
						setLabel( '' );
						onChange( 0 );
					}
				}, __( 'Choose a different review', 'lunara-film' ) )
			);
		}

		return el( Fragment, {},
			el( TextControl, {
				label: __( 'Review to mirror', 'lunara-film' ),
				placeholder: __( 'Search reviews by title…', 'lunara-film' ),
				value: term,
				onChange: setTerm
			} ),
			busy && el( Spinner, {} ),
			! busy && term.length >= 2 && ! results.length && el( 'p', { style: { fontSize: '12px', opacity: 0.7 } }, __( 'No matching reviews.', 'lunara-film' ) ),
			results.map( function ( item ) {
				return el( 'div', { key: 'rev' + item.id, style: rowStyle },
					el( 'span', { style: { flex: '1 1 auto', fontSize: '12px', lineHeight: 1.3 } }, item.title ),
					el( Button, {
						variant: 'secondary',
						size: 'small',
						onClick: function () {
							choose( Number( item.id ), item.title );
						}
					}, __( 'Mirror', 'lunara-film' ) )
				);
			} )
		);
	}

	function PairingEditor( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var pairings = ( attributes.pairings || [] ).slice();

		var tickState = useState( 0 );
		var setTick = tickState[ 1 ];

		var posterIds = pairings.map( function ( p ) { return Number( p.posterId || 0 ); } ).filter( Boolean );

		useEffect( function () {
			if ( posterIds.length ) {
				hydratePosters( posterIds ).then( function () {
					setTick( function ( t ) { return t + 1; } );
				} );
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ posterIds.join( ',' ) ] );

		function commit( next ) {
			setAttributes( { pairings: next } );
		}

		function update( index, key, value ) {
			var next = pairings.map( function ( p, i ) {
				if ( i !== index ) {
					return p;
				}
				var copy = Object.assign( {}, p );
				copy[ key ] = value;
				return copy;
			} );
			commit( next );
		}

		function addCard() {
			commit( pairings.concat( [ { label: '', title: '', year: '', note: '', imdb: '', posterId: 0 } ] ) );
		}

		var cardBox = {
			border: '1px solid rgba(128,128,128,0.25)',
			borderRadius: '6px',
			padding: '10px',
			marginBottom: '12px'
		};
		var rowTop = { display: 'flex', alignItems: 'center', gap: '4px', marginBottom: '6px' };

		return el( Fragment, {},
			pairings.map( function ( pair, index ) {
				var posterId = Number( pair.posterId || 0 );
				return el( 'div', { key: 'pair' + index, style: cardBox },
					el( 'div', { style: rowTop },
						el( 'strong', { style: { fontSize: '11px', opacity: 0.6, flex: '1 1 auto' } },
							( 0 === index ? __( 'Slot 1 · featured', 'lunara-film' ) : __( 'Slot ', 'lunara-film' ) + ( index + 1 ) )
						),
						el( Button, {
							icon: 'star-filled', size: 'small',
							label: __( 'Feature (move to slot 1)', 'lunara-film' ),
							disabled: 0 === index,
							onClick: function () { commit( moveItem( pairings, index, 0 ) ); }
						} ),
						el( Button, {
							icon: 'arrow-up-alt2', size: 'small',
							label: __( 'Move up', 'lunara-film' ),
							disabled: 0 === index,
							onClick: function () { commit( moveItem( pairings, index, index - 1 ) ); }
						} ),
						el( Button, {
							icon: 'arrow-down-alt2', size: 'small',
							label: __( 'Move down', 'lunara-film' ),
							disabled: index === pairings.length - 1,
							onClick: function () { commit( moveItem( pairings, index, index + 1 ) ); }
						} ),
						el( Button, {
							icon: 'no-alt', size: 'small', isDestructive: true,
							label: __( 'Remove', 'lunara-film' ),
							onClick: function () {
								commit( pairings.filter( function ( _p, i ) { return i !== index; } ) );
							}
						} )
					),
					el( TextControl, {
						label: __( 'Role label', 'lunara-film' ),
						value: pair.label || '',
						list: 'lunara-pair-labels',
						placeholder: __( 'e.g. Theme Echo', 'lunara-film' ),
						onChange: function ( v ) { update( index, 'label', v ); }
					} ),
					el( TextControl, {
						label: __( 'Film title', 'lunara-film' ),
						value: pair.title || '',
						onChange: function ( v ) { update( index, 'title', v ); }
					} ),
					el( 'div', { style: { display: 'flex', gap: '8px' } },
						el( 'div', { style: { flex: '0 0 90px' } },
							el( TextControl, {
								label: __( 'Year', 'lunara-film' ),
								value: pair.year || '',
								onChange: function ( v ) { update( index, 'year', v ); }
							} )
						),
						el( 'div', { style: { flex: '1 1 auto' } },
							el( TextControl, {
								label: __( 'IMDb ID', 'lunara-film' ),
								value: pair.imdb || '',
								placeholder: 'tt1234567',
								help: __( 'Locks the poster, links, and Oscar pill.', 'lunara-film' ),
								onChange: function ( v ) { update( index, 'imdb', v ); }
							} )
						)
					),
					el( TextareaControl, {
						label: __( 'Note', 'lunara-film' ),
						value: pair.note || '',
						onChange: function ( v ) { update( index, 'note', v ); }
					} ),
					el( MediaUploadCheck, {},
						el( MediaUpload, {
							allowedTypes: [ 'image' ],
							value: posterId,
							onSelect: function ( m ) {
								posterCache[ Number( m.id ) ] = ( m.sizes && m.sizes.thumbnail ) ? m.sizes.thumbnail.url : m.url;
								update( index, 'posterId', Number( m.id ) );
							},
							render: function ( o ) {
								return el( 'div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
									posterId && posterCache[ posterId ]
										? el( 'img', { src: posterCache[ posterId ], alt: '', style: { width: '34px', height: '50px', objectFit: 'cover', borderRadius: '3px' } } )
										: null,
									el( Button, { variant: 'secondary', size: 'small', onClick: o.open },
										posterId ? __( 'Replace poster', 'lunara-film' ) : __( 'Poster override (optional)', 'lunara-film' ) ),
									posterId
										? el( Button, { size: 'small', isDestructive: true, onClick: function () { update( index, 'posterId', 0 ); } }, __( 'Clear', 'lunara-film' ) )
										: null
								);
							}
						} )
					)
				);
			} ),
			el( 'datalist', { id: 'lunara-pair-labels' },
				LABEL_SUGGESTIONS.map( function ( s ) {
					return el( 'option', { key: s, value: s } );
				} )
			),
			el( Button, { variant: 'primary', onClick: addCard, style: { marginTop: '4px' } },
				__( 'Add pairing card', 'lunara-film' ) ),
			! pairings.length && el( 'p', { style: { fontSize: '12px', opacity: 0.7, marginTop: '8px' } },
				__( 'No cards yet — add a pairing to build the showcase.', 'lunara-film' ) )
		);
	}

	blocks.registerBlockType( 'lunara/pairing', {
		apiVersion: 3,
		title: __( 'Lunara Pair It With — Curated', 'lunara-film' ),
		icon: 'screenoptions',
		category: 'lunara',
		description: __( 'Hand-build the signature Pair It With per instance: pick each film, its role label, note, and poster; reorder freely; or mirror an existing review.', 'lunara-film' ),
		supports: { html: false, anchor: true, align: [ 'wide', 'full' ], multiple: true },
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var source = attributes.source || 'curated';

			function set( key ) {
				return function ( value ) {
					var next = {};
					next[ key ] = value;
					setAttributes( next );
				};
			}

			var hasContent = 'review' === source
				? !! attributes.reviewId
				: ( attributes.pairings && attributes.pairings.length );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Source', 'lunara-film' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Pairings come from', 'lunara-film' ),
							value: source,
							options: [
								{ label: __( 'Curated (hand-built here)', 'lunara-film' ), value: 'curated' },
								{ label: __( 'Mirror a review', 'lunara-film' ), value: 'review' }
							],
							onChange: set( 'source' )
						} ),
						'review' === source && el( ReviewPicker, {
							value: attributes.reviewId || 0,
							onChange: function ( id ) { setAttributes( { reviewId: id } ); }
						} ),
						'review' === source && el( 'p', { style: { fontSize: '11px', opacity: 0.7, marginTop: '8px' } },
							__( 'Renders that review\'s automatic Pair It With cards verbatim.', 'lunara-film' )
						)
					),
					'curated' === source && el( PanelBody, { title: __( 'Pairing cards', 'lunara-film' ), initialOpen: true },
						el( PairingEditor, { attributes: attributes, setAttributes: setAttributes } )
					),
					el( PanelBody, { title: __( 'Section header', 'lunara-film' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show section header', 'lunara-film' ),
							checked: !! attributes.showHeader,
							onChange: set( 'showHeader' )
						} ),
						!! attributes.showHeader && el( Fragment, {},
							el( TextControl, {
								label: __( 'Heading', 'lunara-film' ),
								value: attributes.heading || '',
								placeholder: __( 'Pair It With', 'lunara-film' ),
								onChange: set( 'heading' )
							} ),
							el( TextControl, {
								label: __( 'Subtitle', 'lunara-film' ),
								value: attributes.subtitle || '',
								placeholder: __( 'Three films in conversation with this one.', 'lunara-film' ),
								onChange: set( 'subtitle' )
							} )
						)
					)
				),
				el( 'div', { className: 'lunara-block-preview' },
					hasContent
						? ( ServerSideRender
							? el( ServerSideRender, { block: 'lunara/pairing', attributes: attributes } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'Preview unavailable.', 'lunara-film' ) ) )
						: el( Notice, { status: 'info', isDismissible: false },
							'review' === source
								? __( 'Search for a review to mirror its pairings.', 'lunara-film' )
								: __( 'Add a pairing card to build the showcase.', 'lunara-film' ) )
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
