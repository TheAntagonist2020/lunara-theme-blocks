( function ( blocks, blockEditor, components, element, i18n, serverSideRender, homepageEditor ) {
    'use strict';

    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload = blockEditor.MediaUpload;
    var PanelBody = components.PanelBody;
    var Button = components.Button;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var ServerSideRender = serverSideRender;
    homepageEditor = homepageEditor || {};

    function preview( name, props ) {
        return el( 'div', { className: 'lunara-block-preview' },
            el( ServerSideRender, {
                block: name,
                attributes: props.attributes
            } )
        );
    }

    function countControl( label, value, onChange ) {
        return el( RangeControl, {
            label: label,
            value: value,
            min: 1,
            max: 24,
            onChange: function ( next ) {
                onChange( Number.isFinite( next ) ? next : 1 );
            }
        } );
    }

    function homepageSectionCard( name, title, description ) {
        var sections = homepageEditor.sections || {};
        var section = sections[ name ] || {};
        var actions = [];

        if ( section.editUrl ) {
            actions.push( el( 'a', { className: 'components-button is-primary', href: section.editUrl, key: 'edit' }, section.editLabel || __( 'Open in Site Studio', 'lunara-film' ) ) );
        }
        if ( section.viewUrl ) {
            actions.push( el( 'a', { className: 'components-button is-secondary', href: section.viewUrl, target: '_blank', rel: 'noopener noreferrer', key: 'view' }, __( 'View Section', 'lunara-film' ) ) );
        }

        return el(
            'div',
            { className: 'lunara-homepage-editor-card' + ( section.fixed ? ' is-fixed' : '' ), 'data-lunara-home-section': name },
            el(
                'div',
                { className: 'lunara-homepage-editor-card__heading' },
                el( 'span', { className: 'lunara-homepage-editor-card__kicker' }, __( 'Homepage section', 'lunara-film' ) ),
                el( 'h3', {}, title )
            ),
            el( 'p', { className: 'lunara-homepage-editor-card__description' }, section.description || description ),
            section.status ? el( 'p', { className: 'lunara-homepage-editor-card__status' }, section.status ) : null,
            section.fixed ? el( 'strong', { className: 'lunara-homepage-editor-card__fixed' }, __( 'Fixed by Hero Command', 'lunara-film' ) ) : null,
            actions.length ? el( 'div', { className: 'lunara-homepage-editor-card__actions' }, actions ) : null
        );
    }

    blocks.registerBlockType( 'lunara/home', {
        title: __( 'Lunara Home', 'lunara-film' ),
        icon: 'admin-home',
        category: 'lunara',
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return preview( 'lunara/home', props );
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/reviews', {
        title: __( 'Lunara Reviews Grid', 'lunara-film' ),
        icon: 'format-gallery',
        category: 'lunara',
        attributes: {
            count: { type: 'number', default: 6 }
        },
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Reviews', 'lunara-film' ) },
                        countControl( __( 'Number of reviews', 'lunara-film' ), props.attributes.count, function ( value ) {
                            props.setAttributes( { count: value } );
                        } )
                    )
                ),
                preview( 'lunara/reviews', props )
            ];
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/posts', {
        title: __( 'Lunara Posts Grid', 'lunara-film' ),
        icon: 'grid-view',
        category: 'lunara',
        attributes: {
            category: { type: 'string', default: '' },
            count: { type: 'number', default: 6 }
        },
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Posts', 'lunara-film' ) },
                        el( TextControl, {
                            label: __( 'Category slug', 'lunara-film' ),
                            value: props.attributes.category,
                            onChange: function ( value ) {
                                props.setAttributes( { category: value } );
                            }
                        } ),
                        countControl( __( 'Number of posts', 'lunara-film' ), props.attributes.count, function ( value ) {
                            props.setAttributes( { count: value } );
                        } )
                    )
                ),
                preview( 'lunara/posts', props )
            ];
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/carousel', {
        title: __( 'Lunara Carousel', 'lunara-film' ),
        icon: 'images-alt2',
        category: 'lunara',
        attributes: {
            set: { type: 'string', default: 'homepage' },
            limit: { type: 'number', default: -1 }
        },
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Carousel', 'lunara-film' ) },
                        el( TextControl, {
                            label: __( 'Slide set slug', 'lunara-film' ),
                            value: props.attributes.set,
                            onChange: function ( value ) {
                                props.setAttributes( { set: value } );
                            }
                        } ),
                        el( TextControl, {
                            label: __( 'Limit (-1 for all)', 'lunara-film' ),
                            type: 'number',
                            value: props.attributes.limit,
                            onChange: function ( value ) {
                                props.setAttributes( { limit: parseInt( value, 10 ) || -1 } );
                            }
                        } )
                    )
                ),
                preview( 'lunara/carousel', props )
            ];
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/still', {
        title: __( 'Lunara Still', 'lunara-film' ),
        icon: 'format-image',
        category: 'lunara',
        attributes: {
            url: { type: 'string', default: '' },
            alt: { type: 'string', default: '' },
            caption: { type: 'string', default: '' },
            kicker: { type: 'string', default: '' },
            style: { type: 'string', default: 'default' },
            loading: { type: 'string', default: 'lazy' }
        },
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Still', 'lunara-film' ) },
                        el( TextControl, {
                            label: __( 'Image URL', 'lunara-film' ),
                            value: props.attributes.url,
                            onChange: function ( value ) {
                                props.setAttributes( { url: value } );
                            }
                        } ),
                        el( TextControl, {
                            label: __( 'Alt text', 'lunara-film' ),
                            value: props.attributes.alt,
                            onChange: function ( value ) {
                                props.setAttributes( { alt: value } );
                            }
                        } ),
                        el( TextControl, {
                            label: __( 'Kicker', 'lunara-film' ),
                            value: props.attributes.kicker,
                            onChange: function ( value ) {
                                props.setAttributes( { kicker: value } );
                            }
                        } ),
                        el( TextareaControl, {
                            label: __( 'Caption', 'lunara-film' ),
                            value: props.attributes.caption,
                            onChange: function ( value ) {
                                props.setAttributes( { caption: value } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Style', 'lunara-film' ),
                            value: props.attributes.style,
                            options: [
                                { label: 'Default', value: 'default' },
                                { label: 'Full', value: 'full' },
                                { label: 'Hero', value: 'hero' },
                                { label: 'Inset', value: 'inset' },
                                { label: 'Left', value: 'left' },
                                { label: 'Right', value: 'right' },
                                { label: 'Pair', value: 'pair' }
                            ],
                            onChange: function ( value ) {
                                props.setAttributes( { style: value } );
                            }
                        } )
                    )
                ),
                preview( 'lunara/still', props )
            ];
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/debrief', {
        title: __( 'Lunara Debrief', 'lunara-film' ),
        icon: 'editor-ul',
        category: 'lunara',
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return preview( 'lunara/debrief', props );
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/pair-it-with', {
        title: __( 'Lunara Pair It With', 'lunara-film' ),
        icon: 'editor-insertmore',
        category: 'lunara',
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return preview( 'lunara/pair-it-with', props );
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/where-to-watch', {
        title: __( 'Lunara Where To Watch', 'lunara-film' ),
        icon: 'visibility',
        category: 'lunara',
        attributes: {
            imdb: { type: 'string', default: '' },
            region: { type: 'string', default: 'US' }
        },
        supports: { html: false, align: [ 'wide', 'full' ] },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Where To Watch', 'lunara-film' ) },
                        el( TextControl, {
                            label: __( 'IMDb title ID', 'lunara-film' ),
                            value: props.attributes.imdb,
                            placeholder: 'tt1234567',
                            onChange: function ( value ) {
                                props.setAttributes( { imdb: value } );
                            }
                        } ),
                        el( TextControl, {
                            label: __( 'Region', 'lunara-film' ),
                            value: props.attributes.region,
                            onChange: function ( value ) {
                                props.setAttributes( { region: value.toUpperCase() } );
                            }
                        } )
                    )
                ),
                preview( 'lunara/where-to-watch', props )
            ];
        },
        save: function () {
            return null;
        }
    } );

    /* ── Homepage section kit (focused editor, 3.2.37) ─────────────────────
       These six blocks remain the stored homepage composition. Their public
       dynamic callbacks are unchanged; compact editor cards prevent the
       block editor from downloading and laying out the entire live site. */

    function sectionBlock( name, title, icon, description ) {
        blocks.registerBlockType( name, {
            title: title,
            icon: icon,
            category: 'lunara',
            description: description,
            supports: { html: false, reusable: false, multiple: false },
            edit: function () {
                return homepageSectionCard( name, title, description );
            },
            save: function () {
                return null;
            }
        } );
    }

    blocks.registerBlockType( 'lunara/cinematic-hero', {
        title: __( 'Homepage: Cinematic Hero', 'lunara-film' ),
        icon: 'cover-image',
        category: 'lunara',
        description: __( 'The rotating cinematic hero. Slides are curated in Control Desk → Hero Command; the fields below shape the single-slide fallback.', 'lunara-film' ),
        attributes: {
            overrideImageId: { type: 'number', default: 0 },
            overrideKicker: { type: 'string', default: '' },
            overrideTitle: { type: 'string', default: '' },
            overrideExcerpt: { type: 'string', default: '' },
            overrideUrl: { type: 'string', default: '' },
            overrideCta: { type: 'string', default: '' }
        },
        supports: { html: false, reusable: false, multiple: false },
        edit: function ( props ) {
            function text( label, key ) {
                return el( TextControl, {
                    label: label,
                    value: props.attributes[ key ] || '',
                    onChange: function ( value ) {
                        var next = {};
                        next[ key ] = value || '';
                        props.setAttributes( next );
                    }
                } );
            }
            function textarea( label, key ) {
                return el( TextareaControl, {
                    label: label,
                    value: props.attributes[ key ] || '',
                    onChange: function ( value ) {
                        var next = {};
                        next[ key ] = value || '';
                        props.setAttributes( next );
                    }
                } );
            }
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Hero', 'lunara-film' ) },
                        el( 'p', { style: { opacity: 0.75 } },
                            __( 'The slide deck and overlay dial live in Control Desk → Hero Command. These overrides apply only when the hero falls back to a single static slide.', 'lunara-film' )
                        ),
                        MediaUpload && Button ? el( MediaUpload, {
                            onSelect: function ( media ) { props.setAttributes( { overrideImageId: media && media.id ? media.id : 0 } ); },
                            allowedTypes: [ 'image' ],
                            value: props.attributes.overrideImageId,
                            render: function ( mediaControl ) {
                                return el(
                                    'div',
                                    { className: 'lunara-homepage-editor-media-control' },
                                    el( Button, { onClick: mediaControl.open, variant: 'secondary' }, props.attributes.overrideImageId ? __( 'Replace fallback hero image', 'lunara-film' ) : __( 'Select fallback hero image', 'lunara-film' ) ),
                                    props.attributes.overrideImageId ? el( Button, { onClick: function () { props.setAttributes( { overrideImageId: 0 } ); }, variant: 'tertiary' }, __( 'Clear', 'lunara-film' ) ) : null
                                );
                            }
                        } ) : null,
                        text( __( 'Fallback kicker', 'lunara-film' ), 'overrideKicker' ),
                        text( __( 'Fallback title', 'lunara-film' ), 'overrideTitle' ),
                        textarea( __( 'Fallback excerpt', 'lunara-film' ), 'overrideExcerpt' ),
                        text( __( 'Fallback link URL', 'lunara-film' ), 'overrideUrl' ),
                        text( __( 'Fallback CTA label', 'lunara-film' ), 'overrideCta' )
                    )
                ),
                homepageSectionCard(
                    'lunara/cinematic-hero',
                    __( 'Homepage: Cinematic Hero', 'lunara-film' ),
                    __( 'The rotating cinematic lead. Use Hero Command while the front-door mode is active.', 'lunara-film' )
                )
            ];
        },
        save: function () {
            return null;
        }
    } );

    blocks.registerBlockType( 'lunara/latest-reviews', {
        title: __( 'Homepage: Latest Reviews', 'lunara-film' ),
        icon: 'star-filled',
        category: 'lunara',
        description: __( 'A curated or newest-first Reviews grid with per-instance heading and CTA overrides.', 'lunara-film' ),
        attributes: {
            source: { type: 'string', default: 'curated' },
            count: { type: 'number', default: 8 },
            heading: { type: 'string', default: '' },
            kicker: { type: 'string', default: '' },
            ctaLabel: { type: 'string', default: '' },
            ctaUrl: { type: 'string', default: '' }
        },
        supports: { html: false, reusable: false, multiple: false },
        edit: function ( props ) {
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Latest Reviews Settings', 'lunara-film' ) },
                        el( SelectControl, {
                            label: __( 'Source', 'lunara-film' ),
                            value: props.attributes.source || 'curated',
                            options: [
                                { label: __( 'Homepage curated shelf', 'lunara-film' ), value: 'curated' },
                                { label: __( 'Newest reviews', 'lunara-film' ), value: 'latest' },
                                { label: __( 'Top homepage showcase', 'lunara-film' ), value: 'hero' }
                            ],
                            onChange: function ( value ) { props.setAttributes( { source: value || 'curated' } ); }
                        } ),
                        el( TextControl, {
                            label: __( 'Count', 'lunara-film' ),
                            type: 'number',
                            value: props.attributes.count,
                            onChange: function ( value ) { props.setAttributes( { count: Math.max( 1, Math.min( 24, parseInt( value, 10 ) || 8 ) ) } ); }
                        } ),
                        el( TextControl, { label: __( 'Heading override', 'lunara-film' ), value: props.attributes.heading || '', onChange: function ( value ) { props.setAttributes( { heading: value || '' } ); } } ),
                        el( TextControl, { label: __( 'Kicker override', 'lunara-film' ), value: props.attributes.kicker || '', onChange: function ( value ) { props.setAttributes( { kicker: value || '' } ); } } ),
                        el( TextControl, { label: __( 'CTA label override', 'lunara-film' ), value: props.attributes.ctaLabel || '', onChange: function ( value ) { props.setAttributes( { ctaLabel: value || '' } ); } } ),
                        el( TextControl, { label: __( 'CTA URL override', 'lunara-film' ), value: props.attributes.ctaUrl || '', onChange: function ( value ) { props.setAttributes( { ctaUrl: value || '' } ); } } )
                    )
                ),
                homepageSectionCard(
                    'lunara/latest-reviews',
                    __( 'Homepage: Latest Reviews', 'lunara-film' ),
                    __( 'The curated Reviews shelf, with newest Reviews as its safe fallback.', 'lunara-film' )
                )
            ];
        },
        save: function () {
            return null;
        }
    } );

    sectionBlock(
        'lunara/journal-lane',
        __( 'Homepage: Journal Lane', 'lunara-film' ),
        'editor-ul',
        __( 'The Journal home grid: 1 lead card + 3 supporting cards from the most recent dispatch posts.', 'lunara-film' )
    );

    sectionBlock(
        'lunara/oscar-picks',
        __( 'Homepage: Oscar Picks', 'lunara-film' ),
        'awards',
        __( 'Horizontal carousel of curated behind-the-scenes Oscar pick cards.', 'lunara-film' )
    );

    sectionBlock(
        'lunara/oscar-facts',
        __( 'Homepage: Oscar Facts', 'lunara-film' ),
        'lightbulb',
        __( 'Text-forward Oscar fact cards in a horizontal carousel.', 'lunara-film' )
    );

    sectionBlock(
        'lunara/pairing-desk',
        __( 'Homepage: Pairing Desk', 'lunara-film' ),
        'screenoptions',
        __( 'The signature Pair It With showcase — three films in conversation with the newest paired review.', 'lunara-film' )
    );

    /* ── End homepage section kit ─────────────────────────────────────── */
} )(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.element,
    window.wp.i18n,
    window.wp.serverSideRender,
    window.LunaraHomepageEditorConfig
);
