/* InsightX Lang — settings page. */
( function ( $ ) {
	'use strict';

	/* Toast after a save redirect. */
	var toast = document.getElementById( 'isxl-toast' );
	if ( toast && toast.dataset.msg ) {
		toast.textContent = toast.dataset.msg;
		toast.classList.add( 'is-visible' );
		setTimeout( function () { toast.classList.remove( 'is-visible' ); }, 2600 );
		if ( window.history.replaceState ) {
			var u = new URL( window.location.href );
			u.searchParams.delete( 'msg' );
			window.history.replaceState( null, '', u.toString() );
		}
	}

	/* ---------- Languages table ---------- */
	var $rows = $( '#isxl-lang-rows' );
	if ( $rows.length ) {
		$rows.sortable( { handle: '.isxl-drag', axis: 'y' } );

		var flag = function ( code ) {
			code = ( code || '' ).toUpperCase();
			if ( ! /^[A-Z]{2}$/.test( code ) ) return '';
			return String.fromCodePoint( 0x1f1e6 + code.charCodeAt( 0 ) - 65, 0x1f1e6 + code.charCodeAt( 1 ) - 65 );
		};

		// The default radio carries the row's slug.
		$rows.on( 'input', '.isxl-slug', function () {
			$( this ).closest( 'tr' ).find( '.isxl-default-radio' ).val( this.value );
		} );
		$rows.on( 'input', '[data-f="flag"]', function () {
			$( this ).siblings( '.isxl-flag-preview' ).text( flag( this.value ) );
		} );

		// The default language cannot be deleted; its trash button follows the radio.
		var syncDelete = function () {
			$rows.find( 'tr' ).each( function () {
				$( this ).find( '.isxl-del' ).prop( 'disabled', $( this ).find( '.isxl-default-radio' ).is( ':checked' ) );
			} );
		};
		$rows.on( 'change', '.isxl-default-radio', syncDelete );

		// Trash: a row added on this page just goes away; a saved language is deleted and saved right away.
		$rows.on( 'click', '.isxl-del', function () {
			var $tr = $( this ).closest( 'tr' );
			if ( $tr.is( '[data-new]' ) ) {
				$tr.remove();
				return;
			}
			var name = $tr.find( '[data-f="name"]' ).val() || $tr.find( '[data-f="slug"]' ).val();
			var msg = ( window.isxlAdmin && window.isxlAdmin.confirmDelete ) || 'Delete "%s"?';
			if ( ! window.confirm( msg.replace( '%s', name ) ) ) return;
			var form = $tr.closest( 'form' )[ 0 ];
			$tr.remove();
			if ( form.reportValidity() ) form.submit();
		} );

		$( '#isxl-add-lang' ).on( 'click', function () {
			var v = $( '#isxl-preset' ).val();
			if ( ! v ) return;
			var p = v === 'custom' ? [ '', '', '', '' ] : JSON.parse( v );
			var i = 'n' + Date.now();
			var html = document.getElementById( 'isxl-lang-row-tpl' ).innerHTML.replace( /__i__/g, i );
			var $tr = $( html );
			$tr.find( '[data-f="slug"]' ).val( p[ 0 ] );
			$tr.find( '[data-f="name"]' ).val( p[ 1 ] );
			$tr.find( '[data-f="locale"]' ).val( p[ 2 ] );
			$tr.find( '[data-f="flag"]' ).val( p[ 3 ] );
			$tr.find( '.isxl-flag-preview' ).text( flag( p[ 3 ] ) );
			$tr.find( '.isxl-default-radio' ).val( p[ 0 ] );
			if ( ! $rows.children().length ) $tr.find( '.isxl-default-radio' ).prop( 'checked', true );
			$rows.append( $tr );
			syncDelete();
			$( '#isxl-preset option:selected' ).not( '[value="custom"]' ).remove();
			$( '#isxl-preset' ).val( '' );
			if ( v === 'custom' ) $tr.find( '[data-f="name"]' ).trigger( 'focus' );
		} );
	}

	/* ---------- Strings filter ---------- */
	var $search = $( '#isxl-str-search' );
	var $group = $( '#isxl-str-group' );
	var filter = function () {
		var q = ( $search.val() || '' ).toLowerCase().trim();
		var g = $group.val();
		$( '.isxl-str-table tbody tr' ).each( function () {
			var ok = ( ! g || this.dataset.group === g ) && ( ! q || this.dataset.search.indexOf( q ) !== -1 );
			this.hidden = ! ok;
		} );
	};
	$search.on( 'input', filter );
	$group.on( 'change', filter );
} )( jQuery );
