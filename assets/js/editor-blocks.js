/* InsightX Lang — block-level language visibility + Language switcher block. No build step. */
( function ( wp, cfg ) {
	'use strict';
	if ( ! wp || ! cfg ) return;

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

	var options = [ { value: '', label: __( 'All languages', 'insightx-lang' ) } ].concat(
		cfg.languages.map( function ( l ) {
			return { value: l.slug, label: ( l.flag ? l.flag + ' ' : '' ) + l.name };
		} )
	);

	/* Every block gets an `isxlLang` attribute (declared server-side too). */
	wp.hooks.addFilter( 'blocks.registerBlockType', 'isxl/lang-attr', function ( settings ) {
		settings.attributes = Object.assign( {}, settings.attributes, {
			isxlLang: { type: 'string', default: '' },
		} );
		return settings;
	} );

	var withLangControl = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var value = props.attributes.isxlLang || '';
			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				props.isSelected &&
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Language visibility', 'insightx-lang' ), initialOpen: value !== '' },
							el( SelectControl, {
								label: __( 'Show this block in', 'insightx-lang' ),
								value: value,
								options: options,
								onChange: function ( v ) {
									props.setAttributes( { isxlLang: v } );
								},
								__nextHasNoMarginBottom: true,
							} )
						)
					)
			);
		};
	}, 'withIsxlLangControl' );
	wp.hooks.addFilter( 'editor.BlockEdit', 'isxl/lang-control', withLangControl );

	/* Language switcher block (rendered by PHP). */
	wp.blocks.registerBlockType( 'isxl/switcher', {
		apiVersion: 3,
		title: __( 'Language switcher', 'insightx-lang' ),
		description: __( 'Links to this page in the other languages.', 'insightx-lang' ),
		category: 'widgets',
		icon: 'translation',
		keywords: [ 'language', 'ภาษา', 'switcher' ],
		attributes: {
			style: { type: 'string', default: 'list' },
			flags: { type: 'boolean', default: true },
			names: { type: 'boolean', default: true },
			hideCurrent: { type: 'boolean', default: false },
			isxlLang: { type: 'string', default: '' },
		},
		supports: { html: false },
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = wp.blockEditor.useBlockProps();
			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Switcher', 'insightx-lang' ) },
						el( SelectControl, {
							label: __( 'Style', 'insightx-lang' ),
							value: a.style,
							options: [
								{ value: 'list', label: __( 'List', 'insightx-lang' ) },
								{ value: 'dropdown', label: __( 'Dropdown', 'insightx-lang' ) },
							],
							onChange: function ( v ) { set( { style: v } ); },
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, { label: __( 'Show flags', 'insightx-lang' ), checked: a.flags, onChange: function ( v ) { set( { flags: v } ); }, __nextHasNoMarginBottom: true } ),
						el( ToggleControl, { label: __( 'Show names', 'insightx-lang' ), checked: a.names, onChange: function ( v ) { set( { names: v } ); }, __nextHasNoMarginBottom: true } ),
						el( ToggleControl, { label: __( 'Hide current language', 'insightx-lang' ), checked: a.hideCurrent, onChange: function ( v ) { set( { hideCurrent: v } ); }, __nextHasNoMarginBottom: true } )
					)
				),
				el( wp.serverSideRender, { block: 'isxl/switcher', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp, window.isxlBlocks );
