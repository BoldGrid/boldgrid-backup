/**
 * BoldGrid Backup Admin Backup Now.
 *
 * JavaScript for handling "backup" buttons. This code was initially contained
 * within the home.js, but separated out as of 1.6.0 for reusability.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global ajaxurl,jQuery,localizeScriptData */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.BackupNow = function( $ ) {
	'use strict';

	var self = this,
		lang = localizeScriptData,
		$backupNowType = $( '[name="folder_exclusion_type"]' ),
		$tablesType = $( '[name="table_inclusion_type"]' );

	$( function() {
		$( 'body' ).on( 'click', '#backup-site-now', self.backupNow );

		$( 'body' ).on( 'boldgrid_backup_complete', self.updateProtectionEnabled );
	} );

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
		if ( 1 === $backupNowType.filter( ':checked' ).length ) {
			type = $backupNowType.filter( ':checked' ).val();
		}

		if ( 1 === $tablesType.filter( ':checked' ).length ) {
			tablesType = $tablesType.filter( ':checked' ).val();
		}

		// Assign the current jQuery object.
		$this = $( this );

        // Disable the Backup Site Now link button.
		$this
			.attr( 'disabled', 'disabled' )
			.css( 'pointer-events', 'none' );

		// Create a context selector for the Backup Site Now section.
		$backupSiteSection = $( '#backup-site-now-section' );

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

		$backupSiteSection.find( '.spinner' ).addClass( 'inline' );

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
				errorText = lang.errorText;

			/*
			 * As of 1.5.2, we are hooking into the shutdown and checking for
			 * errors. If a fatal error is found, we will return that, rather
			 * than the generic errorText defined above.
			 */
			if ( jqXHR.responseText !== undefined && '{' === jqXHR.responseText.charAt( 0 ) ) {
				data = JSON.parse( jqXHR.responseText );

				if ( data !== undefined && data.data !== undefined && data.data.errorText !== undefined ) {
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

			switch ( callback ) {
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
			'action': 'boldgrid_backup_now',
			'backup_auth': backupNonce,
			'_wp_http_referer': wpHttpReferer,
			'is_updating': isUpdating,
			'backup_now': '1',
			'folder_exclusion_type': type
		};

		/*
		 * The next few conditionals are used in the Backup Site Now modal. If we
		 * are doing a customized backup, send appropriate "include / exclude"
		 * settings for "folder / database".
		 */
		if ( 'custom' === type && 1 === $folderInclude.length ) {
			data.folder_exclusion_include = $folderInclude.val();
		}

		if ( 'custom' === type && 1 === $folderExclude.length ) {
			data.folder_exclusion_exclude = $folderExclude.val();
		}

		if ( tablesType ) {
			data.table_inclusion_type = tablesType;
		}

		if ( 'custom' === tablesType && $tableInclude.length ) {
			$tableInclude.filter( ':checked' ).each( function() {
				includeTables.push( $( this ).val() );
			} );
			data.include_tables = includeTables;
		}

		if ( undefined !== BOLDGRID.BACKUP.UpdateSelectors ) {
			BOLDGRID.BACKUP.UpdateSelectors.disable();
		}

		// Make the call.
		$.ajax( {
			url: ajaxurl,
			data: data,
			type: 'post',
			dataType: 'text',
			success: successCallback,
			error: errorCallback,
			complete: function() {

				// Hide the spinner.
				$backupSiteSection
					.find( '.spinner' )
						.removeClass( 'is-active' );

				if ( undefined !== BOLDGRID.BACKUP.UpdateSelectors ) {
					BOLDGRID.BACKUP.UpdateSelectors.enable();
				}
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

		$status.html( lang.updateProtectionActivated );

		$backupNow.html( '<p>' + lang.backupCreated + '</p>' );
	};
};

BOLDGRID.BACKUP.BackupNow( jQuery );
