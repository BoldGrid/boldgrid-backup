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
		var $downloadButtons;

		// Create a context selectors for the download buttons.
		$downloadButtons = $( '.backup-archive-list-download-button' );

		// On click action for download buttons.
		$downloadButtons.on( 'click', self.downloadArchive );
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

} )( jQuery );
