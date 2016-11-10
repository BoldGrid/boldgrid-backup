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
	var self = this;

	/**
	 * @summary Handle the click of help buttons.
	 *
	 * @since 1.3.1
	 */
	this.bindHelpClick = function() {
		$( '.dashicons-editor-help' ).on( 'click', function() {
			var id = $( this ).attr( 'data-id' );

			// If we don't have a data-id, abort.
			if( id === undefined ) {
				return;
			}

			// Toggle the help text.
			$( '.help[data-id="' + id + '"]' ).slideToggle();
		});
	};

	/**
	 * Show disk and db sizes.
	 *
	 * @since 1.3.1
	 */
	self.getSizeData = function() {
		var sizes,
			data = {
				'action': 'boldgrid_backup_sizes',
				'sizes_auth' : $( '#sizes_auth' ).val()
			},
			template = wp.template( 'boldgrid-backup-sizes' ),
			$sizeData = $( '#size-data' );

		// If #size-data is not on this page, abort.
		if( 0 === $sizeData.length ) {
			return;
		}

		var successAction = function( msg ) {
			if( 'unauthorized' === msg ) {
				return;
			}

			sizes = JSON.parse( msg );

			// Add our translation settings.
			sizes.lang = BoldGridBackupAdmin.lang;

			$( '#size-data' ).html( template( sizes ) );
		};

		$.post( ajaxurl, data, successAction );
	};

	/**
	 * @summary Init.
	 *
	 * @since 1.3.1
	 */
	this.init = function() {
		self.bindHelpClick();
		self.getSizeData();
	};

	$( function() {
		self.init();
	});
};

BoldGrid.Backup( jQuery );