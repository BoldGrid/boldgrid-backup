/**
 * Admin Customizer
 *
 * Javascript for the Custoimzer. Used for backup protection before updating themes.
 *
 * @since 1.6.0
 */

/* global ajaxurl,boldgridBackupCustomizer,jQuery,wp */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.CUSTOMIZER = function( $ ) {
	var self = this;

	self.protectNoticeShow = false;
	self.progressNoticeShow = false;

	/**
	 * @summary If applicable, show a "Backup in progress" notice when the user
	 * clicks on themes.
	 *
	 * @since 1.6.0
	 */
	self.showProgressNotice = function() {
		wp.customize.section( 'installed_themes', function( section ) {

			// Actions to take when installed_themes section is opened.
			section.expanded.bind( function() {
				if ( ! self.progressNoticeShow ) {
					var data = {
							action: 'boldgrid_backup_get_progress_notice',
							nonce: boldgridBackupCustomizer.nonce
						},
						successCallback;

					/**
					 * @summary Success callback.
					 *
					 * @since 1.6.0
					 */
					successCallback = function( response ) {
						if (
							response.success !== undefined &&
							true === response.success &&
							false !== response.data
						) {
							$( '.customize-themes-notifications' ).prepend( response.data );
							$( 'body' ).trigger( 'boldgrid_backup_progress_notice_added' );

							self.protectNoticeShow = true;
						}
					};

					// Make a call to see if we have an "in progress" notice to show.
					$.ajax( {
						url: ajaxurl,
						data: data,
						type: 'post',
						dataType: 'json',
						success: successCallback
					} );
				}
			} );
		} );
	};

	/**
	 * Show the "protect" notice.
	 *
	 * @since 1.6.0
	 */
	self.showProtectNotice = function() {
		wp.customize.section( 'installed_themes', function( section ) {

			// Actions to take when installed_themes section is opened.
			section.expanded.bind( function() {

				// Show the "protect now" notice.
				if ( ! self.protectNoticeShow ) {
					var data = {
						action: 'boldgrid_backup_get_protect_notice',
						update_protection: true
					};

					$.post( ajaxurl, data, function( response ) {
						if ( response.success !== undefined && true === response.success ) {
							$( '.customize-themes-notifications' ).append( response.data );
							self.protectNoticeShow = true;
						}
					} );
				}
			} );
		} );
	};

	$( function() {
		var deadline;

		if ( boldgridBackupCustomizer.is_rollback_enabled ) {
			deadline = BOLDGRID.BACKUP.RollbackTimer.getUpdatedDeadline();
		}

		self.showProgressNotice();

		// Wait until we have the deadline.
		$( 'body' ).on( 'boldgrid-backup-have-deadline', function() {
			var haveDeadline =
					deadline !== undefined &&
					deadline.responseText !== undefined &&
					'' !== deadline.responseText,
				haveUpdatesAvailable = 0 < boldgridBackupCustomizer.update_data.counts.themes;

			if ( haveDeadline ) {
				BOLDGRID.BACKUP.RollbackTimer.show();
			} else if ( haveUpdatesAvailable ) {
				self.showProtectNotice();
			}
		} );
	} );
};

BOLDGRID.BACKUP.CUSTOMIZER( jQuery );
