/**
 * BoldGrid Backup admin rollback notice.
 *
 * @summary JavaScript for the BoldGrid Backup admin rollback notice.
 *
 * @since 1.0
 */

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

	/*
	 * This script is passed "localizeScriptData" {"rolloutDeadline"}
	 * (via wp_localize_script() in "class-boldgrid-backup-admin-core.php").
	 */

	// Onload event listener.
	$( function() {
		// On click action for the Cancel Rollback button.
		$( '#cancel-rollback-button' )
			.on( 'click', self.cancelRollback );

		// On click action for restore buttons.
		$( '.action-restore' )
			.off( 'click' )
			.on( 'click', self.restoreArchiveConfirm );
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
			markup = '<div class="notice notice-error"><p>There was an error processing your request.  Please reload the page and try again.</p></div>';

			$cancelRollbackResults.html( markup );
		}

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
	 * Confirm to restore a selected backup archive file.
	 *
	 * @since 1.0.1
	 */
	self.restoreArchiveConfirm = function() {
		// Declare variables.
		var confirmResponse, archiveFilename, $restoreSpinner,
			$this = $( this );

		// Get the backup archive filename.
		archiveFilename = $this
			.data( 'filename' );

		// Ask for confirmation.
		confirmResponse = confirm( localizeScriptData.restoreConfirmText + ' "' + archiveFilename + '".' );

		// Handle response.
		if ( true === confirmResponse ) {
	        // Disable the restore Site Now link button.
			$this
				.attr( 'disabled', 'disabled' )
				.css( 'pointer-events', 'none' );

			// Create a context selector for the restore spinner.
			$restoreSpinner = $('#restore-now-section')
				.find( '.spinner' );

			// Show the spinner.
			$restoreSpinner
				.addClass( 'is-active' );

			$restoreSpinner
				.css( 'display', 'inline-block' );

			return true;
		} else {
			return false;
		}
	}

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
			var totalSeconds, seconds, minutes, hours, days;

			// Parse data into seconds from now.
			totalSeconds = Date.parse( endTime ) - Date.parse( new Date() );

			// If totalSeconds is less than or equal to zero, then return zero array.
			if ( totalSeconds <= 0 ) {
				return {
					'total': 0,
					'minutes': '0',
					'seconds': '00',
				}
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
		 * @param string id A DOM id.
		 * @param string endTime A data/time parsed with Date.parse().
		 */
		initializeClock : function( id ) {
			// Define variables.
			var clock, interval, totalSeconds,
				self = this;

			// Get the element for the clock display.
			clock = document.getElementById( id );

			// Use an interval of 1 second to update the clock.
			interval = setInterval( function() {
				totalSeconds = self.getTimeRemaining( BOLDGRID.BACKUP.RollbackTimer.deadline );

				// Update the clock display.
				clock.innerHTML = totalSeconds.minutes + ':' + totalSeconds.seconds;

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

			if ( $RollbackDeadline.length ) {
				BOLDGRID.BACKUP.RollbackTimer.deadline = $RollbackDeadline.text();
			}
		},

		/**
		 * If the rollback countdown timer is needed, then initialize the clock.
		 *
		 * @since 1.0
		 *
		 * @see initializeClock().
		 */
		init : function() {
			if ( localizeScriptData.rolloutDeadline ) {
				// Set the end time/deadline.
				BOLDGRID.BACKUP.RollbackTimer.deadline = localizeScriptData.rolloutDeadline;

				// Initialize the clock.
				this.initializeClock( 'rollback-countdown-timer' );
			}

			// When the update progress iframe loads, check for a new deadline.
			$( 'iframe' )
				.on( 'load', this.updateDeadline );
		}
	};

	// Initialize the deadline.
	BOLDGRID.BACKUP.RollbackTimer.deadline = '';

	// Initialize the rollback timer.
	BOLDGRID.BACKUP.RollbackTimer.init();

} )( jQuery );
