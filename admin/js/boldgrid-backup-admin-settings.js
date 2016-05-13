/**
 * BoldGrid Backup settings.
 *
 * @summary JavaScript for the settings page.
 *
 * @since 1.0
 */
( function( $ ) {
	'use strict';

	// General Variables.
	var self = {};
	var $scheduleDow, $noBackupDays;

	// Define a context selector for schedule-dow.
	$scheduleDow = $( '.schedule-dow' );

	// Define a context selector for no-backup-days.
	$noBackupDays = $( '#no-backup-days' );

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

	} );

} )( jQuery );
