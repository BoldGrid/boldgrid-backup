/**
 * BoldGrid Backup admin rollback notice.
 *
 * @summary JavaScript for the BoldGrid Backup admin rollback notice.
 *
 * @since 1.0
 */

/* global ajaxurl,boldgrid_backup_admin_rollback,jQuery */

// Declare namespace.
var BOLDGRID = BOLDGRID || {};

// Define sub-namespace.
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

/**
 * BoldGrid Backup admin rollback.
 *
 * @summary JavaScript for the BoldGrid Backup admin rollback notice.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */
( function( $ ) {
	'use strict';

	// General Variables.
	var self = {};

	// Onload event listener.
	$( function() {
		$( 'body' ).on( 'click', '#cancel-rollback-button', self.cancelRollback );
	} );

	/**
	 * Cancel pending rollback.
	 *
	 * @since 1.0
	 */
	self.cancelRollback = function() {
		// Declare variables.
		var data, cancelNonce, wpHttpReferer, errorCallback, $cancelRollbackSection,
			$cancelRollbackResults, $rollbackSpinner,
			$this = $( this );

        // Disable the Cancel Rollback button.
        $this.attr( 'disabled', 'disabled' )
        	.css( 'pointer-events', 'none' );

		// Create a context selector for the cancel rollback section.
		$cancelRollbackSection = $( '#cancel-rollback-section' );

		// Create a context selector for the cancel rollback results.
		$cancelRollbackResults = $( '#cancel-rollback-results' );

		// Create a context selector for the cancel rollback spinner.
		$rollbackSpinner = $cancelRollbackSection
			.find( '.spinner' );

		// Show the spinner.
		$rollbackSpinner
			.addClass( 'is-active' );

		$rollbackSpinner
			.css( 'display', 'inline-block' );

		// Get the wpnonce and referer values.
		cancelNonce = $cancelRollbackSection.find( '#cancel_rollback_auth' )
			.val();

		wpHttpReferer = $cancelRollbackSection.find( '[name="_wp_http_referer"]' )
			.val();

		// Create an error callback function.
		errorCallback = function() {
			// Show error message.
			var markup = '<div class="notice notice-error"><p>There was an error processing your request.  Please reload the page and try again.</p></div>';

			$cancelRollbackResults.html( markup );
		};

		// Generate a data array for the download request.
		data = {
		    'action' : 'boldgrid_cancel_rollback',
		    'cancel_rollback_auth' : cancelNonce,
		    '_wp_http_referer' : wpHttpReferer,
		};

		// Make the call.
		$.ajax( {
			url : ajaxurl,
			data : data,
			type : 'post',
			dataType : 'text',
			success : function( response ) {
				// Remove the restore now section.
				$( '#restore-now-section' )
					.empty();

				// Insert markup in the results section.
				$cancelRollbackResults
					.html( response );

				// Hide the cancel rollback section.
				$cancelRollbackSection
					.hide();
			},
			error : errorCallback,
			complete : function() {
				// Hide the spinner.
				$cancelRollbackSection
					.find( '.spinner' )
					.removeClass( 'is-active' );
			}
		} );

		// Return false so the page does not reload.
		return false;
	};

	/**
	 * Namespace BOLDGRID.BACKUP.RollbackTimer.
	 *
	 * @since 1.0
	 */
	BOLDGRID.BACKUP.RollbackTimer = {
		/**
		 * Get the time remaining to an end time.
		 *
		 * @since 1.0
		 *
		 * @param string endTime A data/time parsed with Date.parse().
		 * @return array
		 */
		getTimeRemaining : function( endTime ) {
			// Declare variables.
			var totalSeconds, seconds, minutes;

			// Parse data into seconds from now.
			totalSeconds = Date.parse( endTime ) - Date.parse( new Date() );

			// If totalSeconds is less than or equal to zero, then return zero array.
			if ( totalSeconds <= 0 ) {
				return {
					'total': 0,
					'minutes': '0',
					'seconds': '00',
				};
			}

			// Calculate seconds, minutes, hours, days.
			seconds = Math.floor( ( totalSeconds / 1000 ) % 60 );
			minutes = Math.floor( totalSeconds / 1000 / 60 );

			// Return the data in an array.
			return {
				'total': totalSeconds,
				'minutes': minutes,
				'seconds': ( '0' + seconds ).slice( -2 ),
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
		initializeClock : function( deadline ) {
			// Define variables.
			var $clock, interval, totalSeconds,
				self = this;

			if( deadline !== undefined ) {
				BOLDGRID.BACKUP.RollbackTimer.deadline = deadline;
			}

			// Get the element for the clock display.
			$clock = $( '#rollback-countdown-timer' );

			// Use an interval of 1 second to update the clock.
			interval = setInterval( function() {
				totalSeconds = self.getTimeRemaining( BOLDGRID.BACKUP.RollbackTimer.deadline );

				// Update the clock display.
				$clock.html( totalSeconds.minutes + ':' + totalSeconds.seconds );

				// When the timer reaches zero, stop the countdown and disable the cancel button.
				if( totalSeconds.total <= 0 ){
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
		updateDeadline : function() {
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
		getUpdatedDeadline : function() {
			// Declare variables.
			var $bulkActionForm, wpnonce, wpHttpReferer, data;

			// Create a context selector for bulk-action-form.
			$bulkActionForm = $( '#bulk-action-form' );

			// Get the bulk-action-form wpnonce.
			wpnonce = $bulkActionForm
				.find( '#_wpnonce' ).val();

			// Get the bulk-action-form wpnonce.
			wpHttpReferer = $bulkActionForm
				.find( '[name="_wp_http_referer"]' ).val();

			// Use adminajax to get the updated deadline.
			// Generate the data array.
			data = {
				'action' : 'boldgrid_backup_deadline',
				'_wpnonce' : wpnonce,
				'_wp_http_referer' : wpHttpReferer,
			};

			// Make the call.
			$.ajax( {
				url : ajaxurl,
				data : data,
				type : 'post',
				dataType : 'text',
				success : function( response ) {
					// Update the rollback timer.
					if ( response.length ) {
						BOLDGRID.BACKUP.RollbackTimer.deadline = response;
					}
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
		show : function() {
			var data = {
					'action' : 'boldgrid_backup_get_countdown_notice',
				};

			$.post( ajaxurl, data, function( response ) {
				var deadline,
					$headerEnd = $( '.wp-header-end' );
					$notice,
					$wrap = $( '.wrap' ).first();

				if( response.success !== undefined && true === response.success ) {
					$( '.boldgrid-backup-protect-now' ).slideUp();

					$notice = $( response.data );
					$notice.addClass( 'hidden' );

					// Determine where to add the notice.
					if( 1 === $headerEnd.length ) {
						$notice.insertAfter( $headerEnd );
					} else {
						$notice.prependTo( $wrap );
					}

					$notice.slideDown();

					deadline = $('#rollback-deadline').val();
					BOLDGRID.BACKUP.RollbackTimer.initializeClock( deadline );
				}
			});
		},

		/**
		 * If the rollback countdown timer is needed, then initialize the clock.
		 *
		 * @since 1.0
		 *
		 * @see initializeClock().
		 */
		init : function() {
			// Declare vars.
			var $document = $( document ),
				haveDeadline;

			// Determine whether or not we have a valid deadline.
			haveDeadline = typeof boldgrid_backup_admin_rollback === 'object' &&
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
			$( 'iframe' )
				.on( 'load', this.updateDeadline );

			// When a plugin is updated via adminajax, then get the new deadline and update the timer.
			$document
				.on( 'wp-plugin-update-success', this.getUpdatedDeadline );

			// When a theme is updated via adminajax, then get the new deadline and update the timer.
			$document
				.on( 'wp-theme-update-success', this.getUpdatedDeadline );

			$document.on( 'wp-plugin-update-success wp-theme-update-success', this.show );
		}
	};

	// Initialize the deadline.
	BOLDGRID.BACKUP.RollbackTimer.deadline = '';

	// Initialize the rollback timer.
	BOLDGRID.BACKUP.RollbackTimer.init();

} )( jQuery );
