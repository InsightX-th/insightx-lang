/* InsightX Lang — "Language" panel in the post editor sidebar. No build step. */
( function ( wp, cfg ) {
	'use strict';
	if ( ! wp || ! cfg ) return;

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var C = wp.components;
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( ! Panel ) return;

	var currentLang = cfg.lang;

	/*
	 * Collections of translated types/taxonomies fetched by the editor
	 * (Query Loop, Latest Posts, link search, category picker) get the post's
	 * language unless the request already names one.
	 */
	var bases = cfg.restBases.map( function ( b ) { return b.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ); } ).join( '|' );
	var collection = new RegExp( '^/wp/v2/(' + bases + ')(\\?|$)' );
	wp.apiFetch.use( function ( options, next ) {
		var method = ( options.method || 'GET' ).toUpperCase();
		if ( method === 'GET' && options.path && collection.test( options.path ) && ! /[?&]lang=/.test( options.path ) ) {
			options = Object.assign( {}, options, {
				path: options.path + ( options.path.indexOf( '?' ) === -1 ? '?' : '&' ) + 'lang=' + encodeURIComponent( currentLang ),
			} );
		}
		return next( options );
	} );

	function langLabel( slug ) {
		var l = cfg.languages.find( function ( x ) { return x.slug === slug; } );
		return l ? ( l.flag ? l.flag + ' ' : '' ) + l.name : slug;
	}

	function LanguagePanel() {
		var s = useState( cfg.lang );
		var lang = s[ 0 ];
		var setLang = s[ 1 ];
		var e = useState( '' );
		var error = e[ 0 ];
		var setError = e[ 1 ];
		var b = useState( false );
		var busy = b[ 0 ];
		var setBusy = b[ 1 ];
		var dirty = wp.data.useSelect( function ( select ) {
			return select( 'core/editor' ).isEditedPostDirty();
		}, [] );

		function change( v ) {
			setBusy( true );
			setError( '' );
			wp.apiFetch( { path: '/isxl/v1/post-lang', method: 'POST', data: { id: cfg.postId, lang: v } } )
				.then( function ( r ) {
					setLang( r.lang );
					currentLang = r.lang;
				} )
				.catch( function ( err ) {
					setError( ( err && err.message ) || __( 'Could not change the language.', 'insightx-lang' ) );
				} )
				.finally( function () {
					setBusy( false );
				} );
		}

		var hasGroup = Object.keys( cfg.translations ).some( function ( k ) { return cfg.translations[ k ].id; } );

		var rows = cfg.languages
			.filter( function ( l ) { return l.slug !== lang; } )
			.map( function ( l ) {
				var t = cfg.translations[ l.slug ] || {};
				var right;
				if ( t.id ) {
					right = el(
						'a',
						{ href: t.edit, title: t.title },
						t.status === 'publish' ? __( 'Edit', 'insightx-lang' ) : sprintf( __( 'Edit (%s)', 'insightx-lang' ), t.status )
					);
				} else if ( t.add && ! cfg.isNew ) {
					right = el(
						C.Button,
						{ variant: 'secondary', size: 'small', href: t.add, disabled: dirty, title: dirty ? __( 'Save first, so the copy includes your changes.', 'insightx-lang' ) : '' },
						__( '+ Add', 'insightx-lang' )
					);
				} else {
					right = el( 'span', { style: { color: '#757575' } }, __( 'Save first', 'insightx-lang' ) );
				}
				return el(
					'div',
					{ key: l.slug, style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '6px 0', borderTop: '1px solid #eee' } },
					el( 'span', null, langLabel( l.slug ) ),
					right
				);
			} );

		return el(
			Panel,
			{ name: 'isxl-language', title: __( 'Language', 'insightx-lang' ), icon: 'translation' },
			el( C.SelectControl, {
				label: __( 'This content is in', 'insightx-lang' ),
				value: lang,
				disabled: busy,
				options: cfg.languages.map( function ( l ) { return { value: l.slug, label: langLabel( l.slug ) }; } ),
				onChange: change,
				help: hasGroup ? __( 'Changing it keeps the links to the other translations.', 'insightx-lang' ) : '',
				__nextHasNoMarginBottom: true,
			} ),
			error && el( C.Notice, { status: 'error', isDismissible: false }, error ),
			el( 'p', { style: { margin: '16px 0 4px', fontWeight: 600 } }, __( 'Translations', 'insightx-lang' ) ),
			rows
		);
	}

	wp.plugins.registerPlugin( 'isxl-language', { render: LanguagePanel, icon: 'translation' } );
} )( window.wp, window.isxlPanel );
