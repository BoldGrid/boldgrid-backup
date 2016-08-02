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
	 * This script is passed "localizeScriptData" {"downloadNonce", "accessType", "restoreConfirmText",
	 * "deleteConfirmText", "backupUrl", "errorText"}
	 * (via wp_localize_script() in "class-boldgrid-backup-admin-core.php").
	 */

	// Onload event listener.
	$( function() {
		// On click action for download buttons.
		$( '.action-download' ).on( 'click', self.downloadArchive );

		// On click action for restore buttons.
		$( '.action-restore' ).off( 'click' ).on( 'click', self.restoreArchiveConfirm );

		// On click action for delete buttons.
		$( '.action-delete' ).on( 'click', self.deleteArchiveConfirm );

		// On click action for the Backup Site Now button.
		$( '#backup-site-now' ).on( 'click', self.backupNow );
	} );

	/**
	 * Download a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.downloadArchive = function() {
		// Declare variables.
		var downloadKey, downloadFilename, downloadFilepath, data, form, $formDom,
			$this = $( this );

		// Get the backup archive file key.
		downloadKey = $this.data( 'key' );

		// Get the backup archive filename.
		downloadFilename = $this.data( 'filename' );

		// Get the backup archive file path.
		downloadFilepath = $this.data( 'filepath' );

		// If the wp_filesystem method is not "direct", then show a message and return.
		if ( 'direct' !== localizeScriptData.accessType ) {
			alert( "Wordpress filesystem access method is not direct; it is set to '" +
				localizeScriptData.accessType +
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
		    'wpnonce' : localizeScriptData.downloadNonce
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
		confirmResponse = confirm( localizeScriptData.restoreConfirmText + ' "' + ArchiveFilename + '".' );

		// Handle response.
		if ( true === confirmResponse ) {
			// Disable the Backup Site Now and all Restore buttons.
			$( '#backup-site-now, .action-restore' )
				.attr( 'disabled', 'disabled' )
				.css( 'pointer-events', 'none' );

			// Proceed with restoration.
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
		confirmResponse = confirm( localizeScriptData.deleteConfirmText + ' "' + ArchiveFilename + '".' );

		// Handle response.
		if ( true === confirmResponse ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Perform a backup now.
	 *
	 * @since 1.0
	 */
	self.backupNow = function() {
		// Declare variables.
		var $this, $backupSiteSection, $backupSiteResults, backupNonce, wpHttpReferer, isUpdating,
			errorCallback, data, markup;

		// Assign the current jQuery object.
		$this = $( this );

        // Disable the Backup Site Now link button.
		$this.attr( 'disabled', 'disabled' ).css( 'pointer-events', 'none' );

		// Create a context selector for the Backup Site Now section.
		$backupSiteSection = $('#backup-site-now-section');

		// Create a context selector for the Backup Site Now results.
		$backupSiteResults = $( '#backup-site-now-results' );

		// Show the spinner.
		$backupSiteSection.find('.spinner').addClass( 'is-active' );

		// Get the wpnonce and referer values.
		backupNonce = $backupSiteSection.find( '#backup_auth' ).val();

		wpHttpReferer = $backupSiteSection.find( '[name="_wp_http_referer"]' ).val();

		// Get the backup archive file key.
		isUpdating = $this.data( 'updating' );

		// Create an error callback function.
		errorCallback = function() {
			// Show error message.
			markup = '<div class="notice notice-error"><p>' + localizeScriptData.errorText +
				'</p></div>';

			$backupSiteResults.html( markup );
		}

		// Generate the data array.
		data = {
			'action' : 'boldgrid_backup_now',
			'backup_auth' : backupNonce,
			'_wp_http_referer' : wpHttpReferer,
			'is_updating' : isUpdating,
		};

		// Make the call.
		$.ajax( {
			url : ajaxurl,
			data : data,
			type : 'post',
			dataType : 'text',
			success : function( response ) {
				// Insert markup.
				$backupSiteResults.html( response );

				// Update the archives count.
				$( '#archives-count' ).html( $( '#archives-new-count' ) );

				// Update the archives total size.
				$( '#archives-size' ).html( $( '#archives-new-size' ) );

				// Empty the current archive list.
				$( '#backup-archive-list-body' ).empty();

				// Replace the old list with the new.
				$( '#backup-archive-list-body' ).html( $( '#archive-list-new tr' ) );

				// Remove the hidden new list.
				$( '#archive-list-new' ).remove();

				// Rebind the click events, to the updated list.
				// On click action for download buttons.
				$( '.action-download' ).on( 'click', self.downloadArchive );

				// On click action for restore buttons.
				$( '.action-restore' ).on( 'click', self.restoreArchiveConfirm );

				// On click action for delete buttons.
				$( '.action-delete' ).on( 'click', self.deleteArchiveConfirm );
			},
			error : errorCallback,
			complete : function() {
				// Hide the spinner.
				$backupSiteSection.find('.spinner').removeClass( 'is-active' );
			}
		} );

		// Return false so the page does not reload.
		return false;
	}

} )( jQuery );
