/**
 * This file contains javascript that helps display disk and db size data for the user.
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.3
 */

/* global wp,ajaxurl,BoldGridBackupAdmin */

var BoldGrid = BoldGrid || {};

BoldGrid.BackupNow = function( $ ) {
	var self = this;

	/**
	 * Show disk and db sizes.
	 *
	 * @since 1.3.3
	 */
	self.getSizeData = function() {
		var sizes,
			data = {
				'action': 'boldgrid_backup_sizes',
				'sizes_auth' : $( '#sizes_auth' ).val()
			},
			template = wp.template( 'boldgrid-backup-sizes' ),
			$sizeData = $( '#size-data' ),
			$backupButton = $( '#backup-site-now' ),
			$backupNote = $( '#note-pre-backup' );

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

			$sizeData.html( template( sizes ) );

			if( sizes.messages.notSupported === undefined ) {
				$backupButton.removeAttr( 'disabled', 'disabled' );
				$backupNote.removeClass( 'hidden' );
			} else {
				$backupButton.attr( 'disabled', 'disabled' );
			}
		};

		$.post( ajaxurl, data, successAction );
	};

	$( function() {
		self.getSizeData();
	});
};

BoldGrid.BackupNow( jQuery );