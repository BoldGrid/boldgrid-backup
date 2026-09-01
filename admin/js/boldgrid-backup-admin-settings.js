/**
 * Settings page
 *
 * @summary JavaScript for the settings page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

/* global ajaxurl,bglibLicense,BOLDGRID,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.Settings = function( $ ) {
	'use strict';

	// General Variables.
	var self = this,
		$scheduleDow,
		$noBackupDays,
		$useSparingly,
		$backupDir,
		$body = $( 'body' ),
		tb_unload_count,
		$moveBackups,
		$siteCheck;

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
	 * @summary Take action when the user clicks Check again.
	 *
	 * The user is checking their license status again.
	 *
	 * @since 1.6.0
	 */
	self.onClickCheckAgain = function() {
		var $button = $( this ),
			$parent = $button.parent(),
			$licenseString = $parent.find( '#license_string' ),
			$reloadMessage = $( '#license_reload_page' ),
			$spinner = $parent.find( '.spinner' ),
			successFunction,
			errorFunction;

		$spinner.show();
		$licenseString.empty();

		errorFunction = function( response ) {
			var error =
				response !== undefined && response.data !== undefined && response.data.string !== undefined ?
					response.data.string :
					bglibLicense.unknownError;

			$spinner.hide();
			$licenseString.html( error );
		};

		successFunction = function( response ) {
			if (
				true !== response.success ||
				response.data === undefined ||
				response.data.string === undefined
			) {
				errorFunction( response );
				return;
			}

			$spinner.hide();
			$licenseString.html( response.data.string );

			if ( response.data.isPremium ) {
				$reloadMessage.removeClass( 'hidden' );
			}
		};

		BOLDGRID.LIBRARY.License.clear( 'boldgrid-backup', successFunction, errorFunction );

		return false;
	};

	/**
	 * @summary Action to take when a remote storage provider has been clicked.
	 *
	 * Primary function is to flag the clicked provider with the active class.
	 *
	 * @since 1.5.2
	 */
	self.on_click_provider = function() {
		var $a = $( this ),
			$tr = $a.closest( 'tr' ),
			$table = $a.closest( 'table' );

		$table.find( 'tr' ).removeClass( 'active' );

		/*
		 * We add the active class so that we can identify the provider that is
		 * being updated.
		 */
		$tr.addClass( 'active' );
	};

	/**
	 * @summary Action to take when the thickbox is closed.
	 *
	 * @since 1.5.2
	 */
	self.on_tb_unload = function() {
		tb_unload_count++;

		// Only take action on the odd occurences of tb_unload.
		if ( 0 === tb_unload_count % 2 ) {
			return;
		}

		self.refresh_storage_configuration();
	};

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
		var $tr = $( '#storage_locations tr.active:not(.refreshing)' ),
			$td_configure = $tr.find( 'td.configure' ),
			data = {
				action: 'boldgrid_backup_is_setup_' + $tr.attr( 'data-key' ),
				security: $( '#bgbkup_settings_nonce' ).val()
			},
			$new_tr;

		$tr.addClass( 'refreshing' );

		$td_configure.html( '<span class="spinner inline"></span>' );

		$.post( ajaxurl, data, function( response ) {
			$new_tr = $( response.data );
			$tr.replaceWith( $new_tr );

			self.toggleNoStorage();
		} );
	};

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
		if ( $backupDir.val() === $backupDir.prop( 'defaultValue' ) ) {
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
		 * If the user has selected more than 1 day under "Days of the Week", show a message about
		 * resource usage.
		 *
		 * @since 1.3.1
		 */
		if ( 1 < daysCount ) {
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
	 * @summary Toggle the warning about no backups if no storage selected.
	 *
	 * @since 1.5.2
	 */
	self.toggleNoStorage = function() {
		var count_checked = $( '#storage_locations input[type="checkbox"]:checked' ).length,
			$noStorage = $( '#no_storage' );

		if ( 0 === count_checked ) {
			$noStorage.show();
		} else {
			$noStorage.hide();
		}
	};

	/**
	 * @summary Toggle site check options.
	 *
	 * @since 1.10.0
	 */
	self.toggleSiteCheck = function() {
		if ( '1' === $siteCheck.filter( ':checked' ).val() ) {

			// Site Check is enabled.
			$( '#site-check-interval' ).prop( 'disabled', false );
			$( 'input[name="site_check_logger"]' ).prop( 'disabled', false );
			$( 'input[name="auto_recovery"]' ).prop( 'disabled', false );
			$( '#notification-site-check' ).prop( 'disabled', false );
		} else {

			// Site Check is disabled.
			$( '#site-check-interval' ).prop( 'disabled', true );
			$( 'input[name="site_check_logger"]' ).prop( 'disabled', true );
			$( 'input[name="auto_recovery"]' ).prop( 'disabled', true );
			$( '#notification-site-check' ).prop( 'disabled', true );
		}
	};

	self.toggleCompressionInfo = function() {
		var isSystemZip = false,
			compressorSelector = $( 'select[name="compressor"]' );

		isSystemZip = 'system_zip' === $( compressorSelector ).val();

		if ( isSystemZip ) {
			$( '.compression-level' ).show();
		} else {
			$( '.compression-level' ).hide();
		}
	};

	/**
	 * Toggle Cron Interval
	 *
	 * @summary Toggle the cron interval select element.
	 *
	 * @since 1.16.0
	 */
	self.toggleCronInterval = function() {
		var $schedulerSelect = $( 'select[name="scheduler"]' ),
			toggleInterval = function( val ) {
				if ( 'cron' === val ) {
					$( '#cron_interval' ).show();
				} else {
					$( '#cron_interval' ).hide();
				}
			};

		toggleInterval( $schedulerSelect.find( 'option:selected' ).val() );

		$schedulerSelect.on( 'change', function() {
			toggleInterval(
				$( this )
					.find( 'option:selected' )
					.val()
			);
		} );
	};

	// Onload event listener.
	$( function() {

		// Check if any days or the week are checked, toggle notice.
		self.toggleNoBackupDays();

		self.toggleNoStorage();

		self.toggleCompressionInfo();

		self.toggleCronInterval();

		$body.on( 'click', '#storage_locations input[type="checkbox"]', self.toggleNoStorage );

		$backupDir.on( 'input', self.toggleMoveBackups );

		// On click action for days, check if any days or the week are checked,
		// toggle notice.
		$scheduleDow.on( 'click', self.toggleNoBackupDays );

		$( window ).on( 'tb_unload', self.on_tb_unload );

		$body.on( 'click', '#storage_locations .thickbox', self.on_click_provider );

		$body.on( 'click', '#license_check_again', self.onClickCheckAgain );

		$siteCheck = $( 'input[name="site_check"]' );
		self.toggleSiteCheck();
		$body.on( 'click', $siteCheck, self.toggleSiteCheck );

		$( 'select[name="compressor"]' ).on( 'change', self.toggleCompressionInfo );
	} );
};

BoldGrid.Settings( jQuery );
