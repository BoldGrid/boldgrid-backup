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
		$scheduleDow,
		$noBackupDays,
		$freeDowLimit,
		$useSparingly,
		$backupDir,
		tb_unload_count,
		$moveBackups;

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
	 * @summary Number of times tb_unload has been triggered.
	 *
	 * When a thickbox is closed, tb_unload is called twice. We need to keep
	 * track of how many times it's been called so that we know to only run our
	 * callback once.
	 *
	 * @since 1.5.2
	 */
	tb_unload_count = 0;

	/**
	 * Message describing resource usage.
	 *
	 * @since 1.3.1
	 */
	$useSparingly = $( '#use-sparingly' );

	/**
	 * @summary Action to take when a remote storage provider has been clicked.
	 *
	 * Primary function is to flag the clicked provider with the active class.
	 *
	 * @since 1.5.2
	 */
	self.on_click_provider = function() {
		var $a = $(this),
			$tr = $a.closest( 'tr' ),
			$table = $a.closest( 'table' );

		$table.find( 'tr' ).removeClass( 'active' );

		$tr.addClass( 'active' );
	}

	/**
	 * @summary Action to take when the thickbox is closed.
	 *
	 * @since 1.5.2
	 */
	self.on_tb_unload = function() {
		tb_unload_count++;

		// Only take action on the odd occurences of tb_unload.
		if( 0 === tb_unload_count % 2 ) {
			return;
		}

		self.refresh_storage_configuration();
	}

	/**
	 * @summary Refresh remote storage provider summary.
	 *
	 * For example, if Amazon S3 was unconfigured, an applicable message will
	 * show. After being configured, the "unconfigured" message needs to be
	 * updated.
	 *
	 * @since 1.5.2
	 */
	self.refresh_storage_configuration = function() {
		var $tr = $( '#storage_locations tr.active' ),
			$td_configure = $tr.find( 'td.configure' ),
			$nonce = $( '#_wpnonce' ),
			data = {
				'action' : 'boldgrid_backup_is_setup_' + $tr.attr( 'data-key' ),
				'security' : $nonce.val(),
			},
			$new_tr;

		$td_configure.html( '<span class="spinner inline"></span>' );

		$.post( ajaxurl, data, function( response ) {
			$new_tr = $( response.data ).addClass( 'active' );
			$tr.replaceWith( $new_tr );
		});
	}

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

		$( window ).on( 'tb_unload', self.on_tb_unload );

		$( '#storage_locations .thickbox' ).on( 'click', self.on_click_provider );
	} );

} )( jQuery );
