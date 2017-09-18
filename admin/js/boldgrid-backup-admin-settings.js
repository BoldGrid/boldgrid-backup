/**
 * BoldGrid Backup settings.
 *
 * @summary JavaScript for the settings page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdmin */

( function( $ ) {
	'use strict';

	// General Variables.
	var self = {},
		$scheduleDow, $noBackupDays, $freeDowLimit, $useSparingly, $backupDir, $moveBackups;

	/**
	 * Directory to store backups.
	 *
	 * @since 1.3.6
	 */
	$backupDir = $( '#backup-directory-path' );

	// Define a context selector for schedule-dow.
	$scheduleDow = $( '.schedule-dow' );

	// Define a context selector for no-backup-days.
	$noBackupDays = $( '#no-backup-days' );

	/**
	 * Message describing dow limitations.
	 *
	 * @since 1.3.1
	 */
	$freeDowLimit = $( '#free-dow-limit' );

	/**
	 * Message asking user if we should move their backups.
	 *
	 * @since 1.3.6
	 */
	$moveBackups = $( '#move-backups' );

	/**
	 * Message describing resource usage.
	 *
	 * @since 1.3.1
	 */
	$useSparingly = $( '#use-sparingly' );

	/**
	 * @summary Check if any days of the week selected.
	 *
	 * @since 1.0
	 */
	self.scheduleDowChecked = function() {
		// Define vars.
		var isDowChecked = false;

		if ( $scheduleDow.find( 'input' ).is( ':checked' ) ) {
			isDowChecked = true;
		}

		return isDowChecked;
	};

	/**
	 * @summary Toogle the move backups message.
	 *
	 * @since 1.3.6
	 */
	self.toggleMoveBackups = function() {
		if( $backupDir.val() === $backupDir.prop( 'defaultValue' ) ) {
			$moveBackups.hide();
		} else {
			$moveBackups.show();
		}
	};

	/**
	 * Toggle notice for no backup days selected.
	 *
	 * @since 1.0
	 */
	self.toggleNoBackupDays = function() {
		// How many days of the week are checked?
		var daysCount = $scheduleDow.find( ':checked' ).length;

		/*
		 * If this is not the premium version of the plugin, enforce a limit to the days of the week
		 * selection.
		 *
		 * @since 1.3.1
		 */
		if( 'false' === BoldGridBackupAdmin.is_premium ) {
			if( daysCount >= BoldGridBackupAdmin.max_dow ) {
				// Disable all checkboxes not currently selected.
				$scheduleDow.find( ':checkbox:not(:checked)' ).prop( 'disabled', true );

				// Show a message.
				$freeDowLimit.show();
			} else {
				// Enable all checkboxes.
				$scheduleDow.find( ':checkbox' ).prop( 'disabled', false );

				// Hide the message.
				$freeDowLimit.hide();
			}
		}

		/*
		 * If the user has selected more than 1 day under "Days of the Week", show a message about
		 * resource usage.
		 *
		 * @since 1.3.1
		 */
		if( daysCount > 1 ) {
			$useSparingly.show();
		} else {
			$useSparingly.hide();
		}

		if ( true === self.scheduleDowChecked() ) {
			$noBackupDays.hide();
		} else {
			$noBackupDays.show();
		}
	};

	/**
	 * @summary Toggle timezone.
	 *
	 * @since 1.5.1
	 */
	self.toggleTimezone = function() {
		var $scheduler = $( '#scheduler' ),
			$wp_cron_timezone = $( '#wp_cron_timezone' );

		switch( $scheduler.val() ) {
			case 'cron':
				$wp_cron_timezone.hide();
				break;
			case 'wp-cron':
				$wp_cron_timezone.show();
				break;
		}
	}

	// Onload event listener.
	$( function() {
		// Check if any days or the week are checked, toggle notice.
		self.toggleNoBackupDays();

		$backupDir.on( 'input', self.toggleMoveBackups );

		// On click action for days, check if any days or the week are checked,
		// toggle notice.
		$scheduleDow.on( 'click', self.toggleNoBackupDays );

		/*
		 * When the page loads AND when the user changes the scheduler, toggle
		 * the timezone settings.
		 */
		self.toggleTimezone();
		$( '#scheduler' ).on( 'change', self.toggleTimezone );
	} );

} )( jQuery );
