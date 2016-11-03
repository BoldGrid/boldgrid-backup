/**
 * BoldGrid Backup settings.
 *
 * @summary JavaScript for the settings page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */
( function( $ ) {
	'use strict';

	// General Variables.
	var self = {},
		$scheduleDow, $noBackupDays;

	// Define a context selector for schedule-dow.
	$scheduleDow = $( '.schedule-dow' );

	// Define a context selector for no-backup-days.
	$noBackupDays = $( '#no-backup-days' );

	/**
	 * Show disk and db sizes.
	 *
	 * @since 1.3.1
	 */
	self.getSizeData = function() {
		var sizes,
			data = {
				'action': 'boldgrid_backup_sizes',
				'sizes_auth' : $( '#sizes_auth' ).val()
			},
			template = wp.template( 'boldgrid-backup-sizes' );

		var successAction = function( msg ) {
			if( 'unauthorized' === msg ) {
				return;
			}

			sizes = JSON.parse( msg );

			// Add our translation settings.
			sizes.lang = BoldGridBackupAdminSettings;

			$( '#size-data' ).html( template( sizes ) );
		}

		$.post( ajaxurl, data, successAction );
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
	 * Toggle notice for no backup days selected.
	 *
	 * @since 1.0
	 */
	self.toggleNoBackupDays = function() {
		if ( true === self.scheduleDowChecked() ) {
			$noBackupDays.hide();
		} else {
			$noBackupDays.show();
		}
	};

	// Onload event listener.
	$( function() {
		// Check if any days or the week are checked, toggle notice.
		self.toggleNoBackupDays();

		// On click action for days, check if any days or the week are checked,
		// toggle notice.
		$scheduleDow.on( 'click', self.toggleNoBackupDays );

		self.getSizeData();
	} );

} )( jQuery );
