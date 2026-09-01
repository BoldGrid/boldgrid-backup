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

	var self = this;

	$( function() {
		$( 'body' ).on( 'click', '#backup-site-now', self.backupNow );
	} );

	/**
	 * Perform a backup now.
	 *
	 * The "backup site now" button could be the button in the header of our admin pages. However, it
	 * could also be the "backup site now" button on any dashboard page where you can create a backup
	 * before performing updates.
	 *
	 * @since 1.0
	 */
	self.backupNow = function( e ) {
		var $this = $( this ),
			data,

			/*
			 * Define our "Backup Site Now" parent.
			 *
			 * The parent is needed because on some pages, the backup settings will appear twice, and
			 * we need to be specific to which ones we are targeting. For example, on the settings page
			 * there are two identical settings for files and database settings - one set of settings
			 * for scheduled backups, and one set for the one time "backup site now" backup.
			 */
			$parent = $this.closest( '#TB_ajaxContent' ),

			/*
			 * Configure all of our file and table include/exclude settings.
			 *
			 * Again, these are only available within the "backup site now" modal.
			 */
			// A radio input for which files to include, either a "full" or "custom" backup.
			$radioFolderType = $parent.find( '[name="folder_exclusion_type"]' ).filter( ':checked' ),

			// A radio input for which tables to include, either a "full" or "custom" backup.
			$radioTableType = $parent.find( '[name="table_inclusion_type"]' ).filter( ':checked' ),

			// The input for files to exclude, such as, ".git,node_modules".
			$folderExclude = $parent.find( '[name="folder_exclusion_exclude"]' ),

			// The input for files to include, such as, "WPCORE,/wp-content".
			$folderInclude = $parent.find( '[name="folder_exclusion_include"]' ),

			// The individual checkboxes for each table to include / exclude.
			$tableInclude = $parent.find( '[name="include_tables[]"]' ),

			// An array of tables to include. If the user chose "custom", will be populated below.
			includeTables = [],

			/*
			 * By default, we will create a "full" backup of all files and database tables.
			 *
			 * # If the user is within a backup modal, they will have the choice to configure "custom"
			 *   settings so they can include / exclude specific files and folders.
			 * # If outside of a modal, such as a "backup site now" before plugin upgrades, it will
			 *   be a "full" backup.
			 */
			type = 1 === $radioFolderType.length ? $radioFolderType.val() : 'full',
			tablesType = 1 === $radioTableType.length ? $radioTableType.val() : 'full',

			/*
			 * Configure our "backup site now" section and the values found within.
			 *
			 * Within the modal, this is at the bottom of the modal where the "backup site now" button
			 * is. This is where the auth / nonce info is, and it's also where we'll add the spinner
			 * once clicked.
			 */
			$backupSiteSection = $( '#backup-site-now-section' );

		/*
		 * Generate ajax settings for our "backup site now" call.
		 *
		 * Custom file and table settings will be added after this declaration.
		 */
		data = {
			action: 'boldgrid_backup_now',
			backup_auth: $backupSiteSection.find( '#backup_auth' ).val(),
			_wp_http_referer: $backupSiteSection.find( '[name="_wp_http_referer"]' ).val(),

			/*
			 * Determine whether or not we are backing up before an update.
			 *
			 * On pages where we are creating a backup before an update, such as on the Dashboard >
			 * Updates page, the "backup site now" button will have an data-updating="true" attribute.
			 */
			is_updating: $this.data( 'updating' ),
			backup_now: '1',
			folder_exclusion_type: type,
			table_inclusion_type: tablesType,
			backup_title: $( '[name="backup_title"]' ).val(),
			backup_description: $( '[name="backup_description"]' ).val()
		};

		// Configure our custom file and folder include / exclude rules.
		if ( 'custom' === type ) {
			if ( 1 === $folderInclude.length ) {
				data.folder_exclusion_include = $folderInclude.val();
			}
			if ( 1 === $folderExclude.length ) {
				data.folder_exclusion_exclude = $folderExclude.val();
			}
		}

		// Configure our custom database tables include / exclude rules.
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
		 * UI/UX changes.
		 *
		 * The next few lines handle disabling buttons and showing notices.
		 *
		 * @todo This set of code is somewhat duplicated in in-progress.js, within the start method.
		 * If need be one day, combine into a reusable method.
		 */
		$this.attr( 'disabled', 'disabled' ).css( 'pointer-events', 'none' );

		$parent
			.find( 'input' )
			.attr( 'disabled', true )
			.end()
			.find( 'button' )
			.attr( 'disabled', true )
			.end();

		$( '#you_may_leave' ).fadeIn();

		$backupSiteSection.find( '.spinner' ).addClass( 'inline' );

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
