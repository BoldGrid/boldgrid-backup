/**
 * BoldGrid Backup admin home page.
 *
 * @summary JavaScript for the BoldGrid Backup admin home page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.HOME = function( $ ) {
	'use strict';

	// General Variables.
	var self = this,
		$fileInput = $( 'input:file' );

	/*
	 * This script is passed "localizeScriptData" {"archiveNonce", "accessType", "restoreConfirmText",
	 * "deleteConfirmText", "backupUrl", "errorText"}
	 * (via wp_localize_script() in "class-boldgrid-backup-admin-core.php").
	 */

	// Onload event listener.
	$( function() {
		// On click action for download buttons.
		$( 'body' ).on( 'click', '.action-download', self.downloadArchive );

		// On form submit of restore buttons.  "document" works with buttons placed with AJAX.
		$( document.body )
			.on( 'submit', 'form.restore-now-form', self.restoreArchiveConfirm );

		// On click action for delete buttons.
		$( 'body' ).on( 'click', '.action-delete', self.deleteArchiveConfirm );

		// On click action for the Backup Site Now button.
		$( '#backup-site-now' )
			.on( 'click', self.backupNow );

		// On click action for the Upload button.
		$( '#upload-archive-form' )
			.find( '.button' )
				.on( 'click', self.uploadButtonClicked );

		$( '.page-title-action.add-new' ).on( 'click', function() {
			$( '#add_new' ).toggle();
		});

		$fileInput
			.parent()
				.find( 'input:submit' )
					.attr( 'disabled', true );

		// On click action for toggling a help section.
		$( '.dashicons-editor-help' ).on( 'click', self.toggleHelp );

		// Remove restoration notice.
		self.hideRestoreNotice();

		$fileInput.on( 'change', self.onChangeInput );
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
	 * Show the restore archive spinner and disable action buttons.
	 *
	 * @since 1.2.3
	 */
	self.showRestoreSpinner = function( $this ) {
		// Disable the Backup Site Now and all Restore and Delete buttons.
		$( '#backup-site-now, .action-restore, .action-delete' )
			.prop( 'disabled', true )
			.css( 'pointer-events', 'none' );

		// Show the spinner.
		$this.find( '.spinner' ).addClass( 'is-active' ).css( 'display', 'inline-block' );
	};

	/**
	 * Hide the restore archive notice and enable action buttons.
	 *
	 * @since 1.2.3
	 */
	self.hideRestoreNotice = function() {
		// Enable the Backup Site Now and all Restore and Delete buttons.
		$( '#backup-site-now, .action-restore, .action-delete' )
			.prop( 'disabled', false )
			.css( 'pointer-events', '' );

		// Hide the restore notice.
		$( '.restoration-in-progress' ).hide();
	};

	/**
	 * @summary Take action when a backup file is selected for upload.
	 *
	 * This includes checking the filesize and showing applicable warnings.
	 *
	 * @since 1.5.2
	 */
	self.onChangeInput = function() {
		var $badExtension = $( '#bad_extension' ),
			$fileSizeWarning = $( '[data-id="upload-backup"]:not(span)' ),
			$fileTooLarge = $( '#file_too_large' ),
			$submit = $( 'input:submit' ),
			extension,
			isBadExtension,
			isTooBig,
			maxSize = parseInt( $( '[name="MAX_FILE_SIZE"]' ).val() ),
			name,
			size;

		if( ! $fileInput.val() ) {
			$fileSizeWarning.slideUp();
			$fileTooLarge.slideUp();
			$badExtension.slideUp();
			$submit.attr( 'disabled', true );
			return;
		}

		name = $fileInput[0].files[0].name;
		size = $fileInput[0].files[0].size;
		extension = name.substr( ( name.lastIndexOf( '.' ) +1 ) );

		isTooBig = 0 > maxSize - size;
		isBadExtension = 'zip' !== extension;

		if( isBadExtension ) {
			$badExtension.slideDown();
		} else {
			$badExtension.slideUp();
		}

		if( isTooBig ) {
			$fileSizeWarning.slideDown();
			$fileTooLarge.slideDown();
		} else {
			$fileSizeWarning.slideUp();
			$fileTooLarge.slideUp();
		}

		if( isTooBig || isBadExtension ) {
			$submit.attr( 'disabled', true );
		} else {
			$submit.attr( 'disabled', false );
		}
	}

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
			self.showRestoreSpinner( $this );

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

		if( ! confirmResponse ) {
			return;
		}

		$this.closest( 'td' )
			.find( '.row-actions' )
				.removeClass( 'row-actions' )
				.end()
			.find( '.spinner' )
				.addClass( 'is-active' )
				.css( 'display', 'inline-block' )
				.end()
			.find( 'form' )
				.submit();
	};

	/**
	 * Perform a backup now.
	 *
	 * @since 1.0
	 */
	self.backupNow = function( e ) {
		// Declare variables.
		var $this, $backupSiteSection, $backupSiteResults, backupNonce, wpHttpReferer, isUpdating,
		errorCallback, successCallback, data, markup;

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

		/**
		 * @summary backupNow error callback.
		 *
		 * @since 1.0
		 *
		 * @param object jqXHR
		 * @param string textStatus
		 * @param string errorThrown
		 */
		errorCallback = function( jqXHR, textStatus, errorThrown ) {
			var data,
				errorText = localizeScriptData.errorText;

			/*
			 * As of 1.5.2, we are hooking into the shutdown and checking for
			 * errors. If a fatal error is found, we will return that, rather
			 * than the generic errorText defined above.
			 */
			if( jqXHR.responseText !== undefined && '{' === jqXHR.responseText.charAt( 0 ) ) {
				data = JSON.parse( jqXHR.responseText );

				if( data !== undefined && data.data !== undefined && data.data.errorText !== undefined ) {
					errorText = data.data.errorText;
				}
			}

			// Show error message.
			markup = '<div class="notice notice-error"><p>' + errorText + '</p></div>';

			$backupSiteResults.html( markup );
		};

		/**
		 * @summary backupNow success callback.
		 *
		 * @since 1.5.3
		 */
		successCallback = function( response ) {
			var data = JSON.parse( response ),
				success = data.success !== undefined && true === data.success,
				callback = success && data.data !== undefined && data.data.callback !== undefined ? data.data.callback : null,
				message = callback && data.data.message !== undefined ? data.data.message : null;

			switch( callback ) {
				case 'updateProtectionEnabled':
					self.updateProtectionEnabled();
					break;
				case 'reload':
					location.reload();
					break;
			}
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
			success : successCallback,
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
	 * @summary Show notice after backup and upgrade protection now enabled.
	 *
	 * This updates the current notice rather than generates a new one.
	 *
	 * @since 1.5.3
	 */
	self.updateProtectionEnabled = function() {
		var $notice = $( '#backup-site-now-results' ).closest( '.notice' ),
			$status = $notice.find( '#protection_enabled' ),
			$backupNow = $( '#backup-site-now-section' );

		$notice.removeClass( 'notice-warning' ).addClass( 'notice-success' );

		$status.html( localizeScriptData.updateProtectionActivated );

		$backupNow.html( '<p>' + localizeScriptData.backupCreated + '</p>' );
	}

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
}

BOLDGRID.BACKUP.HOME( jQuery );
