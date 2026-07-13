/**
 * Curated Grids — editor UI for lunara/reviews-grid and lunara/journal-grid.
 *
 * The whole point of these blocks is on-a-whim curation: search any published
 * review / journal entry from the sidebar, add it to the grid, drag its slot
 * up or down, feature it (jump to slot 1), or drop it — with a live
 * server-rendered preview of the exact front-end markup underneath.
 */
( function ( blocks, blockEditor, components, element, i18n, serverSideRender, apiFetch, url ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var useState = element.useState;
	var useEffect = element.useEffect;
	var __ = i18n.__;

	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var Button = components.Button;
	var Spinner = components.Spinner;
	var Notice = components.Notice;
	var ServerSideRender = ( serverSideRender && ( serverSideRender.default || serverSideRender ) ) || serverSideRender;

	// Shared id → { title, subtype } cache so re-selecting a block doesn't refetch.
	var titleCache = {};

	function searchPosts( subtypes, term ) {
		return apiFetch( {
			path: url.addQueryArgs( '/wp/v2/search', {
				search: term,
				type: 'post',
				subtype: subtypes.join( ',' ),
				per_page: 10,
				_fields: 'id,title,subtype'
			} )
		} );
	}

	function hydrateTitles( subtypes, ids ) {
		var missing = ids.filter( function ( id ) {
			return ! titleCache[ id ];
		} );
		if ( ! missing.length ) {
			return Promise.resolve();
		}
		return apiFetch( {
			path: url.addQueryArgs( '/wp/v2/search', {
				include: missing.join( ',' ),
				type: 'post',
				subtype: subtypes.join( ',' ),
				per_page: missing.length,
				_fields: 'id,title,subtype'
			} )
		} ).then( function ( results ) {
			( results || [] ).forEach( function ( item ) {
				titleCache[ item.id ] = { title: item.title, subtype: item.subtype };
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
	 * The curation picker: search box + results + the ordered slot list.
	 */
	function CurationPicker( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var subtypes = props.subtypes;
		var slotOneLabel = props.slotOneLabel;

		var searchState = useState( '' );
		var term = searchState[ 0 ];
		var setTerm = searchState[ 1 ];

		var resultsState = useState( [] );
		var results = resultsState[ 0 ];
		var setResults = resultsState[ 1 ];

		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];

		var tickState = useState( 0 );
		var setTick = tickState[ 1 ];

		var postIds = ( attributes.postIds || [] ).map( Number );

		useEffect( function () {
			if ( postIds.length ) {
				hydrateTitles( subtypes, postIds ).then( function () {
					setTick( function ( t ) { return t + 1; } );
				} );
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ postIds.join( ',' ) ] );

		useEffect( function () {
			if ( ! term || term.length < 2 ) {
				setResults( [] );
				return;
			}
			var alive = true;
			setBusy( true );
			var timer = setTimeout( function () {
				searchPosts( subtypes, term ).then( function ( found ) {
					if ( ! alive ) {
						return;
					}
					( found || [] ).forEach( function ( item ) {
						titleCache[ item.id ] = { title: item.title, subtype: item.subtype };
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

		function setIds( ids ) {
			setAttributes( { postIds: ids } );
		}

		function label( id ) {
			return titleCache[ id ] ? titleCache[ id ].title : '#' + id;
		}

		var rowStyle = {
			display: 'flex',
			alignItems: 'center',
			gap: '4px',
			padding: '4px 0',
			borderBottom: '1px solid rgba(128,128,128,0.15)'
		};
		var titleStyle = { flex: '1 1 auto', fontSize: '12px', lineHeight: 1.3, overflow: 'hidden' };

		return el( Fragment, {},
			el( TextControl, {
				label: __( 'Add to the grid', 'lunara-film' ),
				placeholder: __( 'Search by title…', 'lunara-film' ),
				value: term,
				onChange: setTerm
			} ),
			busy && el( Spinner, {} ),
			! busy && term.length >= 2 && ! results.length && el( 'p', { style: { fontSize: '12px', opacity: 0.7 } }, __( 'No matches.', 'lunara-film' ) ),
			results.map( function ( item ) {
				var already = postIds.indexOf( Number( item.id ) ) !== -1;
				return el( 'div', { key: 'r' + item.id, style: rowStyle },
					el( 'span', { style: titleStyle }, item.title ),
					el( Button, {
						variant: 'secondary',
						size: 'small',
						disabled: already,
						onClick: function () {
							setIds( postIds.concat( [ Number( item.id ) ] ) );
						}
					}, already ? __( 'Added', 'lunara-film' ) : __( 'Add', 'lunara-film' ) )
				);
			} ),
			el( 'p', { style: { fontSize: '11px', opacity: 0.75, marginTop: '12px' } },
				postIds.length
					? slotOneLabel
					: __( 'Nothing picked yet — the grid runs on the newest entries until you add picks.', 'lunara-film' )
			),
			postIds.map( function ( id, index ) {
				return el( 'div', { key: 'p' + id, style: rowStyle },
					el( 'strong', { style: { fontSize: '11px', minWidth: '18px', opacity: 0.6 } }, String( index + 1 ) ),
					el( 'span', { style: titleStyle }, label( id ) ),
					el( Button, {
						icon: 'star-filled',
						size: 'small',
						label: __( 'Feature (move to slot 1)', 'lunara-film' ),
						disabled: 0 === index,
						onClick: function () {
							setIds( moveItem( postIds, index, 0 ) );
						}
					} ),
					el( Button, {
						icon: 'arrow-up-alt2',
						size: 'small',
						label: __( 'Move up', 'lunara-film' ),
						disabled: 0 === index,
						onClick: function () {
							setIds( moveItem( postIds, index, index - 1 ) );
						}
					} ),
					el( Button, {
						icon: 'arrow-down-alt2',
						size: 'small',
						label: __( 'Move down', 'lunara-film' ),
						disabled: index === postIds.length - 1,
						onClick: function () {
							setIds( moveItem( postIds, index, index + 1 ) );
						}
					} ),
					el( Button, {
						icon: 'no-alt',
						size: 'small',
						isDestructive: true,
						label: __( 'Remove', 'lunara-film' ),
						onClick: function () {
							setIds( postIds.filter( function ( existing ) {
								return existing !== id;
							} ) );
						}
					} )
				);
			} )
		);
	}

	function registerCuratedGrid( config ) {
		blocks.registerBlockType( config.name, {
			apiVersion: 3,
			title: config.title,
			icon: config.icon,
			category: 'lunara',
			description: config.description,
			supports: { html: false, anchor: true, multiple: true },
			edit: function ( props ) {
				var attributes = props.attributes;
				var setAttributes = props.setAttributes;
				var curated = 'curated' === attributes.mode;

				function set( key ) {
					return function ( value ) {
						var next = {};
						next[ key ] = value;
						setAttributes( next );
					};
				}

				return el( Fragment, {},
					el( InspectorControls, {},
						el( PanelBody, { title: __( 'Curation', 'lunara-film' ), initialOpen: true },
							el( SelectControl, {
								label: __( 'Source', 'lunara-film' ),
								value: attributes.mode || 'latest',
								options: [
									{ label: __( 'Newest first (automatic)', 'lunara-film' ), value: 'latest' },
									{ label: __( 'Hand-picked (you choose every slot)', 'lunara-film' ), value: 'curated' }
								],
								onChange: set( 'mode' )
							} ),
							el( RangeControl, {
								label: curated
									? __( 'Total cards (picks + auto-fill)', 'lunara-film' )
									: __( 'Number of cards', 'lunara-film' ),
								value: attributes.count,
								min: 1,
								max: 24,
								onChange: set( 'count' )
							} ),
							curated && el( ToggleControl, {
								label: __( 'Auto-fill empty slots with newest entries', 'lunara-film' ),
								checked: !! attributes.autoFill,
								onChange: set( 'autoFill' )
							} ),
							curated && el( CurationPicker, {
								attributes: attributes,
								setAttributes: setAttributes,
								subtypes: config.searchSubtypes,
								slotOneLabel: config.slotOneLabel
							} ),
							! curated && config.autoFilterControls && config.autoFilterControls( attributes, set )
						),
						el( PanelBody, { title: __( 'Layout', 'lunara-film' ), initialOpen: false },
							el( SelectControl, {
								label: __( 'Grid style', 'lunara-film' ),
								value: attributes.layout,
								options: config.layoutOptions,
								onChange: set( 'layout' )
							} ),
							el( RangeControl, {
								label: __( 'Columns', 'lunara-film' ),
								value: attributes.columns,
								min: 2,
								max: 4,
								onChange: set( 'columns' )
							} )
						),
						el( PanelBody, { title: __( 'Cards', 'lunara-film' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Show excerpt', 'lunara-film' ),
								checked: !! attributes.showExcerpt,
								onChange: set( 'showExcerpt' )
							} ),
							!! attributes.showExcerpt && el( RangeControl, {
								label: __( 'Excerpt length (words)', 'lunara-film' ),
								value: attributes.excerptWords,
								min: 8,
								max: 80,
								onChange: set( 'excerptWords' )
							} ),
							config.cardControls && config.cardControls( attributes, set ),
							el( TextControl, {
								label: __( 'Card kicker label', 'lunara-film' ),
								help: config.cardKickerHelp,
								value: attributes.cardKicker || '',
								onChange: set( 'cardKicker' )
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
						ServerSideRender
							? el( ServerSideRender, { block: config.name, attributes: attributes } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'Preview unavailable.', 'lunara-film' ) )
					)
				);
			},
			save: function () {
				return null;
			}
		} );
	}

	registerCuratedGrid( {
		name: 'lunara/reviews-grid',
		title: __( 'Lunara Reviews Grid — Curated', 'lunara-film' ),
		icon: 'star-filled',
		description: __( 'Hand-pick and reorder exactly which reviews appear (slot 1 is the featured slot), or run on the newest reviews. Layout, columns, score/excerpt display, and section header are all per-instance.', 'lunara-film' ),
		searchSubtypes: [ 'review' ],
		slotOneLabel: __( 'Slot 1 is the featured slot in the lead layout. Reorder freely — the grid follows this list.', 'lunara-film' ),
		cardKickerHelp: __( 'Small label above each card title. Default: "Lunara Review".', 'lunara-film' ),
		layoutOptions: [
			{ label: __( 'Uniform grid', 'lunara-film' ), value: 'grid' },
			{ label: __( 'Featured lead + grid', 'lunara-film' ), value: 'lead' }
		],
		cardControls: function ( attributes, set ) {
			return el( ToggleControl, {
				key: 'showScore',
				label: __( 'Show star score', 'lunara-film' ),
				checked: !! attributes.showScore,
				onChange: set( 'showScore' )
			} );
		},
		autoFilterControls: function ( attributes, set ) {
			return el( TextControl, {
				key: 'categoryFilter',
				label: __( 'Category filter (slug)', 'lunara-film' ),
				help: __( 'Optional — limit the automatic feed to one category.', 'lunara-film' ),
				value: attributes.categoryFilter || '',
				onChange: set( 'categoryFilter' )
			} );
		}
	} );

	registerCuratedGrid( {
		name: 'lunara/journal-grid',
		title: __( 'Lunara Journal Grid — Curated', 'lunara-film' ),
		icon: 'editor-ul',
		description: __( 'Hand-pick and reorder exactly which journal entries appear (slot 1 is the lead card), or run on the newest entries. Lead/uniform layout, columns, type/date/excerpt display, and section header are all per-instance.', 'lunara-film' ),
		searchSubtypes: [ 'journal', 'post' ],
		slotOneLabel: __( 'Slot 1 is the lead card in the lead layout. Reorder freely — the grid follows this list.', 'lunara-film' ),
		cardKickerHelp: __( 'Small label above each supporting card title. Default: "From the desk".', 'lunara-film' ),
		layoutOptions: [
			{ label: __( 'Lead card + supporting grid', 'lunara-film' ), value: 'lead' },
			{ label: __( 'Uniform grid', 'lunara-film' ), value: 'grid' }
		],
		cardControls: function ( attributes, set ) {
			return el( Fragment, { key: 'journalCardControls' },
				el( ToggleControl, {
					label: __( 'Show dispatch type', 'lunara-film' ),
					checked: !! attributes.showType,
					onChange: set( 'showType' )
				} ),
				el( ToggleControl, {
					label: __( 'Show date', 'lunara-film' ),
					checked: !! attributes.showDate,
					onChange: set( 'showDate' )
				} )
			);
		},
		autoFilterControls: function ( attributes, set ) {
			return el( Fragment, { key: 'journalAutoFilters' },
				el( ToggleControl, {
					label: __( 'Blend in standard posts', 'lunara-film' ),
					checked: !! attributes.includePosts,
					onChange: set( 'includePosts' )
				} ),
				el( TextControl, {
					label: __( 'Journal type filter (slug)', 'lunara-film' ),
					help: __( 'Optional — limit the automatic feed to one journal type (journal entries only).', 'lunara-film' ),
					value: attributes.typeFilter || '',
					onChange: set( 'typeFilter' )
				} )
			);
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
