/**
 * BoldGrid Backup admin home page.
 *
 * @summary JavaScript for the BoldGrid Backup admin home page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

/* global ajaxurl,jQuery,localizeScriptData */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.HOME = function( $ ) {
	'use strict';

	// General Variables.
	var self = this,
		$fileInput = $( 'input:file' ),
		$backupNowType = $( '[name="folder_exclusion_type"]' ),
		lang = localizeScriptData,
		$mineCount = $( '.mine' ),
		$tablesType = $( '[name="table_inclusion_type"]' );

	// Onload event listener.
	$( function() {
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

		$mineCount.on( 'click', self.onClickCount );
	} );

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
	};

	/**
	 * @summary Action to take when a user clicks on a mine count.
	 *
	 * @since 1.5.4
	 */
	self.onClickCount = function() {
		var $anchor = $(this),
			$p = $anchor.closest( 'p' ),
			$trs = $( '#backup-archive-list-body tr' ),
			type = $anchor.html().trim();

		$p.find( '.mine' ).removeClass( 'current' );
		$anchor.addClass( 'current' );

		if( lang.All === type ) {
			$trs.show();
			return false;
		}

		$trs.each( function( index ) {
			var $tr = $( this ),
				$matches = $tr.find( '[data-location="' + type + '"]' ),
				matchCount = $matches.length;

			if( 0 === matchCount ) {
				$tr.hide();
			} else {
				$tr.show();
				$matches.bgbuDrawAttention();
			}
		});

		return false;
	};

	/**
	 * Perform a backup now.
	 *
	 * @since 1.0
	 */
	self.backupNow = function( e ) {
		// Declare variables.
		var $this, $backupSiteSection, $backupSiteResults, backupNonce, wpHttpReferer, isUpdating,
		errorCallback, successCallback, data, markup,
		$folderExclude = $( '[name="folder_exclusion_exclude"]' ),
		$folderInclude = $( '[name="folder_exclusion_include"]' ),
		$tableInclude = $( '[name="include_tables[]"]' ),
		includeTables = [],
		type = 'full',
		tablesType = null;

		/*
		 * If we are in a Backup Site Now modal and there is a "type" value set,
		 * grab it.
		 */
		if( 1 === $backupNowType.filter( ':checked' ).length ) {
			type = $backupNowType.filter( ':checked' ).val();
		}

		if( 1 === $tablesType.filter( ':checked' ).length ) {
			tablesType = $tablesType.filter( ':checked' ).val();
		}

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

		$( '#TB_ajaxContent' )
			.find( 'input' ).attr( 'disabled', true ).end()
			.find( 'button' ).attr( 'disabled', true ).end();

		$( '#you_may_leave' ).fadeIn();

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
				callback = success && data.data !== undefined && data.data.callback !== undefined ? data.data.callback : null;

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
			'folder_exclusion_type' : type,
		};

		/*
		 * The next few conditionals are used in the Backup Site Now modal. If we
		 * are doing a customized backup, send appropriate "include / exclude"
		 * settings for "folder / database".
		 */
		if( 'custom' === type && 1 === $folderInclude.length ) {
			data.folder_exclusion_include = $folderInclude.val();
		}

		if( 'custom' === type && 1 === $folderExclude.length ) {
			data.folder_exclusion_exclude = $folderExclude.val();
		}

		if( tablesType ) {
			data.table_inclusion_type = tablesType;
		}

		if( 'custom' === tablesType && $tableInclude.length ) {
			$tableInclude.filter( ':checked' ).each( function(){
				includeTables.push( $(this).val() );
			});
			data.include_tables = includeTables;
		}


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
};

BOLDGRID.BACKUP.HOME( jQuery );
