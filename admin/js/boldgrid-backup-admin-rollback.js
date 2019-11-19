/**
 * Rollback notice
 *
 * @summary JavaScript for the rollback notice.
 *
 * @since 1.0
 */

/* global ajaxurl,boldgrid_backup_admin_rollback,pagenow,jQuery,wp */

// Declare namespace.
var BOLDGRID = BOLDGRID || {};

// Define sub-namespace.
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

/**
 * Rollback notice.
 *
 * @summary JavaScript for the rollback notice.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */
( function( $ ) {
	'use strict';

	// Onload event listener.
	$( function() {
		$( 'body' ).on( 'click', '#cancel-rollback-button', BOLDGRID.BACKUP.RollbackTimer.cancelRollback );

		BOLDGRID.BACKUP.RollbackTimer.adjustOnAbout();
	} );

	/**
	 * Namespace BOLDGRID.BACKUP.RollbackTimer.
	 *
	 * @since 1.0
	 */
	BOLDGRID.BACKUP.RollbackTimer = {

		// When we show a global notice in the customizer, it is identified by this code.
		countdownCode: 'boldgrid-backup-countdown',

		/**
		 * Cancel pending rollback.
		 *
		 * @since 1.0
		 */
		cancelRollback: function() {

			// Declare variables.
			var data,
				cancelNonce,
				wpHttpReferer,
				errorCallback,
				$cancelRollbackSection,
				$cancelRollbackResults,
				$rollbackSpinner,
				$this = $( this ),
				successCallBack;

			// Disable the Cancel Rollback button.
			$this.attr( 'disabled', 'disabled' ).css( 'pointer-events', 'none' );

			// Create a context selector for the cancel rollback section.
			$cancelRollbackSection = $( '#cancel-rollback-section' );

			// Create a context selector for the cancel rollback results.
			$cancelRollbackResults = $( '#cancel-rollback-results' );

			// Create a context selector for the cancel rollback spinner.
			$rollbackSpinner = $cancelRollbackSection.find( '.spinner' );

			// Show the spinner.
			$rollbackSpinner.addClass( 'is-active' );

			$rollbackSpinner.css( 'display', 'inline-block' );

			// Get the wpnonce and referer values.
			cancelNonce = $cancelRollbackSection.find( '#cancel_rollback_auth' ).val();

			wpHttpReferer = $cancelRollbackSection.find( '[name="_wp_http_referer"]' ).val();

			// Create an error callback function.
			errorCallback = function() {

				// Show error message.
				var markup =
					'<div class="notice notice-error"><p>There was an error processing your request.  Please reload the page and try again.</p></div>';

				$cancelRollbackResults.html( markup );
			};

			// Generate a data array for the download request.
			data = {
				action: 'boldgrid_cancel_rollback',
				cancel_rollback_auth: cancelNonce,
				_wp_http_referer: wpHttpReferer
			};

			/**
			 * Action to take when we successfully canceled the rollback.
			 *
			 * @since 1.6.0
			 */
			successCallBack = function( response ) {

				// Remove the restore now section.
				$( '[data-restore-now]' )
					.parent()
					.slideUp();

				// Insert markup in the results section.
				$cancelRollbackResults.html( response );

				// Hide the cancel rollback section.
				$cancelRollbackSection.slideUp();

				if ( 'customize' === pagenow ) {
					wp.customize.notifications.remove( BOLDGRID.BACKUP.RollbackTimer.countdownCode );
				}
			};

			// Make the call.
			$.ajax( {
				url: ajaxurl,
				data: data,
				type: 'post',
				dataType: 'text',
				success: successCallBack,
				error: errorCallback,
				complete: function() {

					// Hide the spinner.
					$cancelRollbackSection.find( '.spinner' ).removeClass( 'is-active' );
				}
			} );

			// Return false so the page does not reload.
			return false;
		},

		/**
		 * Get the time remaining to an end time.
		 *
		 * @since 1.0
		 *
		 * @param string endTime A data/time parsed with Date.parse().
		 * @return array
		 */
		getTimeRemaining: function( endTime ) {

			// Declare variables.
			var totalSeconds, seconds, minutes;

			// Parse data into seconds from now.
			totalSeconds = Date.parse( endTime ) - Date.parse( new Date() );

			// If totalSeconds is less than or equal to zero, then return zero array.
			if ( 0 >= totalSeconds ) {
				return {
					total: 0,
					minutes: '0',
					seconds: '00'
				};
			}

			// Calculate seconds, minutes, hours, days.
			seconds = Math.floor( ( totalSeconds / 1000 ) % 60 );
			minutes = Math.floor( totalSeconds / 1000 / 60 );

			// Return the data in an array.
			return {
				total: totalSeconds,
				minutes: minutes,
				seconds: ( '0' + seconds ).slice( -2 )
			};
		},

		/**
		 * Initialize a countdown timer, updating a DOM id.
		 *
		 * Uses BOLDGRID.BACKUP.RollbackTimer.deadline for the end time/deadline.
		 *
		 * @since 1.0
		 *
		 * @see getTimeRemaining().
		 * @see updateDeadline().
		 *
		 * @param string deadline
		 */
		initializeClock: function() {

			// Define variables.
			var $clock,
				interval,
				totalSeconds,
				self = this;

			// Get the element for the clock display.
			$clock = $( '#rollback-countdown-timer' );

			// Use an interval of 1 second to update the clock.
			interval = setInterval( function() {
				totalSeconds = self.getTimeRemaining( BOLDGRID.BACKUP.RollbackTimer.deadline );

				// Update the clock display.
				$clock.html( totalSeconds.minutes + ':' + totalSeconds.seconds );

				// When the timer reaches zero, stop the countdown and disable the cancel button.
				if ( 0 >= totalSeconds.total ) {
					clearInterval( interval );

					// Disable the Cancel Rollback button.
					$( '#cancel-rollback-button' )
						.attr( 'disabled', 'disabled' )
						.css( 'pointer-events', 'none' );
				}
			}, 1000 );
		},

		/**
		 * If updating something, then update the timer deadline.
		 *
		 * @since 1.2
		 */
		updateDeadline: function() {

			// Declare variables.
			var $RollbackDeadline;

			// Check for the deadline in the source (when completing updates in the admin section).
			$RollbackDeadline = $( 'iframe' )
				.contents()
				.find( '#rollback-deadline' );

			// Update the rollback timer.
			if ( $RollbackDeadline.length ) {
				BOLDGRID.BACKUP.RollbackTimer.deadline = $RollbackDeadline.text();
				BOLDGRID.BACKUP.RollbackTimer.initializeClock();
			}
		},

		/**
		 * If updating something, then update the timer deadline from the retrieved ISO time.
		 *
		 * @since 1.2.1
		 */
		getUpdatedDeadline: function() {

			// Declare variables.
			var $bulkActionForm, wpnonce, wpHttpReferer, data;

			// Create a context selector for bulk-action-form.
			$bulkActionForm = $( '#bulk-action-form' );

			// Get the bulk-action-form wpnonce.
			wpnonce = $bulkActionForm.find( '#_wpnonce' ).val();

			// Get the bulk-action-form wpnonce.
			wpHttpReferer = $bulkActionForm.find( '[name="_wp_http_referer"]' ).val();

			// Use adminajax to get the updated deadline.
			// Generate the data array.
			data = {
				action: 'boldgrid_backup_deadline',
				_wpnonce: wpnonce,
				_wp_http_referer: wpHttpReferer
			};

			// Make the call.
			return $.ajax( {
				url: ajaxurl,
				data: data,
				type: 'post',
				dataType: 'text',
				success: function( response ) {

					// Update the rollback timer.
					if ( response.length ) {
						BOLDGRID.BACKUP.RollbackTimer.deadline = response;
					}

					/*
					 * Someone may be waiting to see if we have a deadline, let
					 * them know we're done.
					 */
					$( 'body' ).trigger( 'boldgrid-backup-have-deadline' );
				}
			} );
		},

		/**
		 * @summary Show the countdown notice.
		 *
		 * This method makes an ajax request to get the countdown notice. Useful
		 * when plugins / themes are updated via ajaxy.
		 *
		 * @since 1.6.0
		 */
		show: function() {

			/*
			 * Show only one countdown.
			 *
			 * If there is already a countdown showing, abort. The user may be
			 * on the themes page updating themes, and if they update two themes,
			 * we don't want to show two countdown notices.
			 */
			if ( 0 < $( '.boldgrid-backup-countdown:visible' ).length ) {
				return;
			}

			var data = {
					action: 'boldgrid_backup_get_countdown_notice'
				},
				successCallback;

			/**
			 * Action to take after getting the countdown notice.
			 *
			 * @since 1.6.0
			 */
			successCallback = function( response ) {
				var $notice,
					notification,
					$headerEnd = $( '.wp-header-end' ),
					$wrap = $( '.wrap' ).first();

				if ( response.success !== undefined && true === response.success ) {
					$( '.boldgrid-backup-protect-now, .boldgrid-backup-protected' ).slideUp();

					$notice = $( response.data );

					// Determine where and how to add the notice.
					if ( 'customize' === pagenow ) {
						notification = new wp.customize.Notification(
							BOLDGRID.BACKUP.RollbackTimer.countdownCode,
							{ message: $notice.removeClass( 'notice notice-warning' ).html(), type: 'warning' }
						);
						wp.customize.notifications.add( notification );
					} else {
						$notice.addClass( 'hidden' );

						if ( 1 === $headerEnd.length ) {
							$notice.insertAfter( $headerEnd );
						} else {
							$notice.prependTo( $wrap );
						}

						$notice.slideDown();
					}

					/*
					 * Allow the countdown to render (especially in the
					 * customizer) before initializing the clock.
					 */
					setTimeout( function() {
						BOLDGRID.BACKUP.RollbackTimer.initializeClock();
					}, 500 );
				}
			};

			$.ajax( {
				url: ajaxurl,
				data: data,
				type: 'post',
				dataType: 'json',
				success: successCallback
			} );
		},

		/**
		 * @summary Show the countdown notice on the about page.
		 *
		 * WordPress hides all admin notices on the wp-admin/about.php page. The
		 * countdown notice is important enough to break this mold.
		 *
		 * @since 1.6.0
		 */
		adjustOnAbout: function() {
			if ( 'about' !== pagenow ) {
				return;
			}

			var $notice = $( '.notice.boldgrid-backup-countdown' );

			$notice.css( 'display', 'block!important' ).insertBefore( '.wrap' );
		},

		/**
		 * If the rollback countdown timer is needed, then initialize the clock.
		 *
		 * @since 1.0
		 *
		 * @see initializeClock().
		 */
		init: function() {

			// Declare vars.
			var $document = $( document ),
				haveDeadline;

			// Determine whether or not we have a valid deadline.
			haveDeadline =
				'object' === typeof boldgrid_backup_admin_rollback &&
				boldgrid_backup_admin_rollback.rolloutDeadline !== undefined &&
				'1970' !== boldgrid_backup_admin_rollback.rolloutDeadline.slice( 0, 4 );

			// If there is a defined rollout deadline, then initialize the timer.
			if ( haveDeadline ) {

				// Set the end time/deadline.
				BOLDGRID.BACKUP.RollbackTimer.deadline = boldgrid_backup_admin_rollback.rolloutDeadline;

				// Initialize the clock/timer.
				this.initializeClock();
			}

			// When the update progress iframe loads, check for a new deadline.
			$( 'iframe' ).on( 'load', this.updateDeadline );

			// When a plugin is updated via adminajax, then get the new deadline and update the timer.
			$document.on( 'wp-plugin-update-success', this.getUpdatedDeadline );

			// When a theme is updated via adminajax, then get the new deadline and update the timer.
			$document.on( 'wp-theme-update-success', this.getUpdatedDeadline );

			$document.on( 'wp-plugin-update-success wp-theme-update-success', this.show );
		}
	};

	// Initialize the deadline.
	BOLDGRID.BACKUP.RollbackTimer.deadline = '';

	// Initialize the rollback timer.
	BOLDGRID.BACKUP.RollbackTimer.init();
} )( jQuery );
