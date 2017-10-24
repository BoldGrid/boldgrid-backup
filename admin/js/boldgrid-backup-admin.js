/**
 * This file contains javascript to load on all admin pages of the BoldGrid Backup plugin.
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.1
 */

/* global wp,ajaxurl,BoldGridBackupAdmin */

var BoldGrid = BoldGrid || {};

BoldGrid.Backup = function( $ ) {
	var self = this,
		$includeTables;

	/**
	 * @summary Handle the click of help buttons.
	 *
	 * @since 1.3.1
	 */
	this.bindHelpClick = function() {
		$( 'body' ).on( 'click', '.dashicons-editor-help', function() {
			var id = $( this ).attr( 'data-id' );

			// If we don't have a data-id, abort.
			if( id === undefined ) {
				return;
			}

			// Toggle the help text.
			$( '.help[data-id="' + id + '"]' ).slideToggle();
		});
	};

	$( function() {
		$includeTables = $( '.include-tables [type="checkbox"]' );

		self.bindHelpClick();

		$( '#include_all_tables' ).on( 'click', function() {
			$includeTables.attr( 'checked', true );
			return false;
		} );

		$( '#exclude_all_tables' ).on( 'click', function() {
			$includeTables.attr( 'checked', false );
			return false;
		} );

		$( '#configure_include_tables' ).on( 'click', function() {
			$( '#tables_to_include .tables' ).slideToggle();
			return false;
		})
	});
};

BoldGrid.Backup( jQuery );