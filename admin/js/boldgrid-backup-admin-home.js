/**
 * BoldGrid Backup admin home page.
 *
 * @summary JavaScript for the BoldGrid Backup admin home page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

( function( $ ) {
	'use strict';

	// General Variables.
	var self = {};

	/*
	 * This script is passed "downloadNonce" and "accessType" (via wp_localize_script() in
	 * "class-boldgrid-backup-admin-core.php").
	 */

	// Onload event listener.
	$( function() {
		// Declare variables.
		var $downloadButtons, $restoreButtons, $deleteButtons;

		// Create a context selectors for the download buttons.
		$downloadButtons = $( '.action-download' );

		// Create a context selectors for the restore buttons.
		$restoreButtons = $( '.action-restore' );

		// Create a context selectors for the delete buttons.
		$deleteButtons = $( '.action-delete' );

		// On click action for download buttons.
		$downloadButtons.on( 'click', self.downloadArchive );

		// On click action for restore buttons.
		$restoreButtons.on( 'click', self.restoreArchiveConfirm );

		// On click action for delete buttons.
		$deleteButtons.on( 'click', self.deleteArchiveConfirm );
} );

	/**
	 * Download a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.downloadArchive = function() {
		// Declare variables.
		var downloadKey, downloadFilename, downloadFilepath, data, form, $formDom, $this = $( this );

		// Get the backup archive file key.
		downloadKey = $this.data( 'key' );

		// Get the backup archive filename.
		downloadFilename = $this.data( 'filename' );

		// Get the backup archive file path.
		downloadFilepath = $this.data( 'filepath' );

		// If the wp_filesystem method is not "direct", then show a message and return.
		if ( 'direct' !== accessType ) {
			alert( "Wordpress filesystem access method is not direct; it is set to '" + accessType +
				"'.\n\nYou can download the archive file using another method, such as FTP.\n\n" +
				"The backup archive file path is: " + downloadFilepath
			);

			return false;
		}

		// Generate a data array for the download request.
		data = {
		    'action' : 'download_archive_file',
		    'download_key' : downloadKey,
		    'download_filename' : downloadFilename,
		    'wpnonce' : downloadNonce
		};

		// Create a hidden form to request the download.
		form = "<form class='hidden' method='POST' action='" + ajaxurl + "' target='_blank'>";
		_.each( data, function( value, key ) {
			form += "<input type='hidden' name='" + key + "' value='" + value + "'>";
		} );
		form += '</form>';

		// Enter the form markup into the DOM.
		$formDom = $( form );

		// Add the form to the current body.
		$( 'body' ).append( $formDom );

		// Submit the form.
		$formDom.submit();

		// Return false so the page does not reload.
		return false;
	};

	/**
	 * Confirm to restore a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.restoreArchiveConfirm = function() {
		// Declare variables.
		var confirmResponse, ArchiveFilename, $this = $( this );

		// Get the backup archive filename.
		ArchiveFilename = $this.data( 'filename' );

		// Ask for confirmation.
		confirmResponse = confirm(
			'Please confirm the restoration of this WordPress installation from the archive file "'
			+ ArchiveFilename + '".' );

		// Handle response.
		if ( true === confirmResponse ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Confirm to delete a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.deleteArchiveConfirm = function() {
		// Declare variables.
		var confirmResponse, ArchiveFilename, $this = $( this );

		// Get the backup archive filename.
		ArchiveFilename = $this.data( 'filename' );

		// Ask for confirmation.
		confirmResponse = confirm(
			'Please confirm the deletion the archive file "' + ArchiveFilename + '".' );

		// Handle response.
		if ( true === confirmResponse ) {
			return true;
		} else {
			return false;
		}
	}

} )( jQuery );
