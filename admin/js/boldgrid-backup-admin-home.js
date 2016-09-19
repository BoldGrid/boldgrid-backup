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
	 * This script is passed "localizeScriptData" {"archiveNonce", "accessType", "restoreConfirmText",
	 * "deleteConfirmText", "backupUrl", "errorText"}
	 * (via wp_localize_script() in "class-boldgrid-backup-admin-core.php").
	 */

	// Onload event listener.
	$( function() {
		// Declare vars.
		var $fileInput = $( 'input:file' );

		// On click action for download buttons.
		$( '.action-download' )
			.on( 'click', self.downloadArchive );

		// On form submit of restore buttons.  "document" works with buttons placed with AJAX.
		$( document.body )
			.on( 'submit', 'form.restore-now-form', self.restoreArchiveConfirm );

		// On click action for delete buttons.
		$( '.action-delete' )
			.on( 'click', self.deleteArchiveConfirm );

		// On click action for the Backup Site Now button.
		$( '#backup-site-now' )
			.on( 'click', self.backupNow );

		// On click action for the Upload button.
		$( '#upload-archive-form' )
			.find( '.button' )
				.on( 'click', self.uploadButtonClicked );

		$fileInput
			.parent()
				.find( 'input:submit' )
					.attr( 'disabled', true );

		$fileInput
			.change(
				function(){
					if ( $(this).val() ) {
						$( 'input:submit' )
							.attr( 'disabled', false );
					}
				}
		);

		// On click action for toggling a help section.
		$( '.dashicons-editor-help' ).on( 'click', self.toggleHelp );
	} );

	/**
	 * Download a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.downloadArchive = function( e ) {
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

			e.preventDefault();
			return;
		}

		// Generate a data array for the download request.
		data = {
		    'action' : 'download_archive_file',
		    'download_key' : downloadKey,
		    'download_filename' : downloadFilename,
		    'wpnonce' : localizeScriptData.archiveNonce
		};

		// Create a hidden form to request the download.
		form = "<form id='download-now-form' class='hidden' method='POST' action='" + ajaxurl + "' target='_blank'>";
		_.each( data, function( value, key ) {
			form += "<input type='hidden' name='" + key + "' value='" + value + "' />";
		} );
		form += '</form>';

		// Enter the form markup into the DOM.
		$formDom = $( form );

		// Add the form to the current body.
		$( 'body' ).append( $formDom );

		// Submit the form.
		$formDom.submit();

		// Prevent default browser action.
		e.preventDefault();
	};

	/**
	 * Confirm to restore a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.restoreArchiveConfirm = function( e ) {
		// Declare variables.
		var confirmResponse, ArchiveFilename, restoreConfirmText,
			$this = $( this );

		// Get the backup archive filename.
		ArchiveFilename = $this.find( 'input[name=archive_filename]' ).val();

		// Format the restoreConfirmText string, to add the ArchiveFilename.
		restoreConfirmText = localizeScriptData.restoreConfirmText
			.replace( '%s', ArchiveFilename );

		// Ask for confirmation.
		confirmResponse = confirm( restoreConfirmText );

		// Handle response.
		if ( true === confirmResponse ) {
			// Disable the Backup Site Now and all Restore and Delete buttons.
			$( '#backup-site-now, .action-restore, .action-delete' )
				.attr( 'disabled', 'disabled' )
				.css( 'pointer-events', 'none' );

			// Show the spinner.
			$this
				.find( '.spinner' )
					.addClass( 'is-active' )
					.css( 'display', 'inline-block' );

			// Proceed with restoration.
			return true;
		} else {
			// Prevent default browser action.
			e.preventDefault();
		}
	};

	/**
	 * Confirm to delete a selected backup archive file.
	 *
	 * @since 1.0
	 */
	self.deleteArchiveConfirm = function( e ) {
		// Declare variables.
		var confirmResponse, ArchiveFilename,
			$this = $( this );

		// Get the backup archive filename.
		ArchiveFilename = $this.data( 'filename' );

		// Ask for confirmation.
		confirmResponse = confirm( localizeScriptData.deleteConfirmText + ' "' + ArchiveFilename + '"' );

		// Handle response.
		if ( true === confirmResponse ) {
			// Show the spinner.
			$this
				.parent()
					.find( '.spinner' )
						.addClass( 'is-active' )
						.css( 'display', 'inline-block' );

			// Proceed with deletion.
			return true;
		} else {
			// Prevent default browser action.
			e.preventDefault();
		}
	};

	/**
	 * Perform a backup now.
	 *
	 * @since 1.0
	 */
	self.backupNow = function( e ) {
		// Declare variables.
		var $this, $backupSiteSection, $backupSiteResults, backupNonce, wpHttpReferer, isUpdating,
		errorCallback, data, markup;

		// Assign the current jQuery object.
		$this = $( this );

        // Disable the Backup Site Now link button.
		$this
			.attr( 'disabled', 'disabled' )
			.css( 'pointer-events', 'none' );

		// Create a context selector for the Backup Site Now section.
		$backupSiteSection = $('#backup-site-now-section');

		// Create a context selector for the Backup Site Now results.
		$backupSiteResults = $( '#backup-site-now-results' );

		// Get the wpnonce and referer values.
		backupNonce = $backupSiteSection.find( '#backup_auth' )
			.val();

		wpHttpReferer = $backupSiteSection.find( '[name="_wp_http_referer"]' )
			.val();

		// Get the backup archive file key.
		isUpdating = $this.data( 'updating' );

		// Show the spinner.
		$backupSiteSection
			.find('.spinner')
				.addClass( 'is-active' )
				.css( 'display', 'inline-block' );

		// Create an error callback function.
		errorCallback = function() {
			// Show error message.
			markup = '<div class="notice notice-error"><p>' + localizeScriptData.errorText +
				'</p></div>';

			$backupSiteResults.html( markup );
		};

		// Generate the data array.
		data = {
			'action' : 'boldgrid_backup_now',
			'backup_auth' : backupNonce,
			'_wp_http_referer' : wpHttpReferer,
			'is_updating' : isUpdating,
			'backup_now' : '1',
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
				$( '#archives-count' )
					.html( $( '#archives-new-count' ) );

				// Update the archives total size.
				$( '#archives-size' )
					.html( $( '#archives-new-size' ) );

				// Empty the current archive list.
				$( '#backup-archive-list-body' )
					.empty();

				// Replace the old list with the new.
				$( '#backup-archive-list-body' )
					.html( $( '#archive-list-new tr' ) );

				// Remove the hidden new list.
				$( '#archive-list-new' )
					.remove();

				// Rebind the click events, to the updated list.
				// On click action for download buttons.
				$( '.action-download' )
					.on( 'click', self.downloadArchive );

				// On click action for delete buttons.
				$( '.action-delete' )
					.on( 'click', self.deleteArchiveConfirm );
			},
			error : errorCallback,
			complete : function() {
				// Hide the spinner.
				$backupSiteSection
					.find('.spinner')
						.removeClass( 'is-active' );
			}
		} );

		// Prevent default browser action.
		e.preventDefault();
	};

	/**
	 * Confirm to delete a selected backup archive file.
	 *
	 * @since 1.2.2
	 */
	self.uploadButtonClicked = function() {
		// Declare variables.
		var $this = $( this );

        // Disable the Upload button.
		$this
			.css( 'pointer-events', 'none' );

		// Show the spinner.
		$this
			.parent()
				.find( '.spinner' )
					.addClass( 'is-active' )
					.css( 'display', 'inline-block' );
	};

	/**
	 * Toggle a help section.
	 *
	 * @since 1.2.2
	 */
	self.toggleHelp = function() {
		$( this ).next( '.help' ).toggle();
	};
} )( jQuery );
