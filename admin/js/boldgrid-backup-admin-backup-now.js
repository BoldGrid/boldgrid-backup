/**
 * Backup Now
 *
 * JavaScript for handling "backup" buttons. This code was initially contained
 * within the home.js, but separated out as of 1.6.0 for reusability.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global ajaxurl,jQuery,localizeScriptData,pagenow */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.BackupNow = function( $ ) {
	'use strict';

	var self = this,
		$backupNowType = $( '[name="folder_exclusion_type"]' ),
		$tablesType = $( '[name="table_inclusion_type"]' );

	$( function() {
		$( 'body' ).on( 'click', '#backup-site-now', self.backupNow );
	} );

	/**
	 * Perform a backup now.
	 *
	 * @since 1.0
	 */
	self.backupNow = function( e ) {

		// Declare variables.
		var $this,
			$backupSiteSection,
			backupNonce,
			wpHttpReferer,
			isUpdating,
			data,
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
		$this.attr( 'disabled', 'disabled' ).css( 'pointer-events', 'none' );

		// Create a context selector for the Backup Site Now section.
		$backupSiteSection = $( '#backup-site-now-section' );

		$( '#TB_ajaxContent' )
			.find( 'input' )
			.attr( 'disabled', true )
			.end()
			.find( 'button' )
			.attr( 'disabled', true )
			.end();

		$( '#you_may_leave' ).fadeIn();

		// Get the wpnonce and referer values.
		backupNonce = $backupSiteSection.find( '#backup_auth' ).val();

		wpHttpReferer = $backupSiteSection.find( '[name="_wp_http_referer"]' ).val();

		// Get the backup archive file key.
		isUpdating = $this.data( 'updating' );

		$backupSiteSection.find( '.spinner' ).addClass( 'inline' );

		// Generate the data array.
		data = {
			action: 'boldgrid_backup_now',
			backup_auth: backupNonce,
			_wp_http_referer: wpHttpReferer,
			is_updating: isUpdating,
			backup_now: '1',
			folder_exclusion_type: type,
			backup_title: $( '[name="backup_title"]' ).val(),
			backup_description: $( '[name="backup_description"]' ).val()
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

		/*
		 * Make the ajax call to "Backup Site Now".
		 *
		 * No success, error, or complete callback is passed to the ajax call. Successes will be
		 * handled by "in progress".
		 */
		$.ajax( {
			url: ajaxurl,
			data: data,
			type: 'post'
		} );

		/*
		 * Take action now that the ajax call to create a backup has been triggered.
		 *
		 * If we're on the Backup Archive's page page, wait 6 seconds and reload the page. Within the
		 * "Backup Site Now" modal, the user will be given a notice that their backup has started, and
		 * that the page will refresh and display a progress bar.
		 *
		 * Else, trigger 'boldgrid_backup_initiated'. The only listener is in-progress.js. When a
		 * backup has been initiated, it starts the WordPress Heartbeat and shows the in progress container.
		 *
		 * @todo Below, we wait 6 seconds because we are assuming that in that time the ajax call will
		 * trigger the backup and the flag for "a backup in progress" will be set. If the flag is not
		 * set by the time the page refreshes, the in progress notice will not show. The page should
		 * not refresh until we know a backup is in progress so that we know the in progress bar will
		 * show when the page refreshes.
		 */
		if ( true === pagenow.includes( 'boldgrid-backup' ) ) {
			setTimeout( function() {
				location.reload();
			}, 6000 );
		} else {
			$( 'body' ).trigger( 'boldgrid_backup_initiated' );
		}

		// Prevent default browser action.
		e.preventDefault();
	};
};

BOLDGRID.BACKUP.BackupNow( jQuery );
