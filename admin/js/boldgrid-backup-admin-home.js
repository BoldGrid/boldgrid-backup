( function( $ ) {
	'use strict';

	// General Variables.
	var self = {};

	/*
	 * This script is passed "downloadNonce" containing a nonce for file
	 * downloads.
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
	 */
	self.downloadArchive = function() {
		// Declare variables.
		var downloadKey, downloadFilename, data, form, $formDom, $this = $( this );

		// Get the backup archive file key.
		downloadKey = $this.data( 'key' );

		// Get the backup archive filename.
		downloadFilename = $this.data( 'filename' );

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
