/**
 * Backup In Progress Bar
 *
 * @summary This file handles the "In progress" bar for when a backup is in progress.
 *
 * @since 1.7.0
 */

/* global jQuery,wp */

var BOLDGRID = BOLDGRID || {};

BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

( function( $ ) {
	'use strict';

	var self;

	/**
	 * Suggest starter content.
	 *
	 * @since 1.7.0
	 */
	BOLDGRID.BACKUP.InProgress = {

		/**
		 * Whether or not there's an "In progress" notice on the page.
		 *
		 * @since 1.11.2
		 *
		 * @type bool
		 */
		hasProgressNotice: false,

		/**
		 * Whether or not there's an "Update Protection" notice on the page.
		 *
		 * @since 1.11.2
		 *
		 * @type bool
		 */
		hasProtectionNotice: false,

		/**
		 * Label.
		 *
		 * @since 1.7.0
		 *
		 * @type string
		 */
		$label: null,

		/**
		 * In progress notice.
		 *
		 * @since 1.7.0
		 *
		 * @type string
		 */
		$inProgressNotice: null,

		/**
		 * i18n.
		 *
		 * @since 1.7.0
		 *
		 * @type object
		 */
		i18n: window.BoldGridBackupAdminInProgress || {},

		/**
		 * The Update Protection notice.
		 *
		 * @since 1.11.2
		 *
		 * @type string
		 */
		$protectionNotice: null,

		/**
		 * Init.
		 *
		 * @since 1.7.0
		 */
		init: function() {
			self._onReady();
		},

		/**
		 * On ready.
		 *
		 * @since 1.7.0
		 */
		_onReady: function() {
			$( function() {
				if ( 'undefined' !== typeof wp.heartbeat ) {

					/*
					 * Check for a backup in progress.
					 *
					 * If there is, we need to begin listenting to the heartbeat to find out when it
					 * completes (so we can adjust the message).
					 */
					setTimeout( self.onInProgress, 1000 );

					/*
					 * Event "boldgrid_backup_progress_notice_added" currently only triggered within the
					 * customizer. When a user clicks on themes, we may dynamically show them a notice that
					 * a backup is in progress.
					 */
					$( document ).on( 'boldgrid_backup_progress_notice_added', 'body', self.onInProgress );

					/*
					 * Take action when a backup is started.
					 *
					 * The only script triggering this event is backup-now.js.
					 */
					$( document ).on( 'boldgrid_backup_initiated', 'body', self.onBackupInitiated );

					$( document ).on( 'boldgrid_backup_complete', 'body', self.onComplete );

					// Configure our "Update Protection" values.
					self.$protectionNotice = $( '.boldgrid-backup-protect-now' );
					self.hasProtectionNotice = 1 === self.$protectionNotice.length;
				} else {

					// Something's gone wrong.
					console.log( 'Error: Progress bar needs heartbeat enqueued.' );
				}
			} );
		},

		/**
		 * Determine whether or not a step is active.
		 *
		 * @since 1.7.0
		 *
		 * @param  string step The id of the container.
		 * @return bool
		 */
		isStepActive: function( step ) {
			return $( '#boldgrid_backup_in_progress_steps' )
				.find( '[data-step="' + step + '"]' )
				.hasClass( 'active' );
		},

		/**
		 * Action to take when a backup is initiated.
		 *
		 * We configure the heartbeat and the progress bar.
		 *
		 * @since 1.7.0
		 */
		onBackupInitiated: function() {
			self.heartbeatStart();

			// Show and initialize our progress bar.
			$( '#boldgrid_backup_in_progress_container' ).show();
			self.$label = $( '.progress-label' );
			self.setPercentage( 0 );
		},

		/**
		 * Action to take when a backup is completed.
		 *
		 * This function is called within this file's onHeartbeatTick listener.
		 *
		 * @since 1.7.0
		 *
		 * @param object data The data object received from the WordPress Heartbeat.
		 */
		onComplete: function( data ) {
			var success;

			// If we don't have any error messages, backup was a success.
			success = '' === data.boldgrid_backup_error;

			// Bail out of the heartbeat.
			$( document ).off( 'heartbeat-tick', self.onHeartbeatTick );
			$( document ).off( 'heartbeat-send', self.heartbeatModify );

			/*
			 * Enable buttons again.
			 *
			 * We disabled certain buttons during the backup, like "Update now" and "Backup site now".
			 * Enable those buttons now.
			 */
			if ( undefined !== BOLDGRID.BACKUP.UpdateSelectors ) {
				BOLDGRID.BACKUP.UpdateSelectors.enable();
			} else {
				console.log( 'Error: BOLDGRID.BACKUP.UpdateSelectors class not available.' );
			}

			$( 'body' ).trigger( 'make_notices_dismissible' );

			/*
			 * Hide "in progress" notices.
			 *
			 * There's no longer a backup in progress, so hide the progress bar.
			 *
			 * The notice is either:
			 * 1: Inside of its own .notice container, represented by self.$inProgressNotice. This
			 *    is the admin notice added on page load when a backup is in progress.
			 * 2: Inside the "Update protection" notice, represented by #boldgrid_backup_in_progress_container.
			 *    This is hidden on page load, and shown dynamically when a backup is initiated.
			 */
			self.$inProgressNotice.slideUp();
			$( '#boldgrid_backup_in_progress_container' ).slideUp();

			success ? self.onSuccess( data ) : self.onError( data );
		},

		/**
		 * Steps to take when an error has occurred.
		 *
		 * @since 1.11.2
		 *
		 * @param object Heartbeat data.
		 */
		onError: function( data ) {
			var $notice;

			if ( self.hasProtectionNotice ) {
				self.$protectionNotice

					// Change the notice from a warning to an error.
					.removeClass( 'notice-warning' )
					.addClass( 'notice-error' )

					// Clean up the existing markup of the notice.
					.find( '#protection_enabled' )
					.nextAll()
					.remove()
					.end()
					.remove()
					.end()

					// Break the news and tell the user an error occurred.
					.append( '<p>' + self.i18n.backup_error + '</p>' )
					.append(
						'<div class="notice"><p><strong>' +
							self.i18n.error +
							'</strong><br /><em>' +
							data.in_progress_data.error +
							'</em></p></div>'
					)
					.append( '<p>' + self.i18n.get_support + '</p>' );
			} else {
				$notice = $( data.boldgrid_backup_error );
				$notice

					// Hide the notice before inserting it so that we can display it using slide down.
					.css( 'display', 'none' )
					.insertBefore( self.$inProgressNotice )
					.slideDown();
			}
		},

		/**
		 * Steps to take when our backup was successful.
		 *
		 * @since 1.11.2
		 *
		 * @param object Heartbeat data.
		 */
		onSuccess: function( data ) {
			var $notice;

			if ( self.hasProgressNotice ) {

				/*
				 * Display our notice.
				 *
				 * The backup is complete, and we either have a success notice or an error notice. Figure
				 * out which it is, and then display it.
				 *
				 * The markup for the actual notice is given to us via the heartbeat call.
				 */
				$notice = $( data.boldgrid_backup_complete );
				$notice

					// Hide the notice before inserting it so that we can display it using slide down.
					.css( 'display', 'none' )
					.insertBefore( self.$inProgressNotice )
					.slideDown();
			}

			/*
			 * Show a notice that upgrade protection is now enabled. This updates the current notice
			 * rather than generate a new one.
			 *
			 * This logic was originally introduced in 1.5.3 within backup-now.js. As of 1.11.2 it
			 * has been moved here so that backup-now.js can focus soley on triggering the ajax call
			 * to generate the backup and nothing else.
			 */
			$( '#backup-site-now-results' )
				.closest( '.notice' )

				// Change it from warning to success.
				.removeClass( 'notice-warning' )
				.addClass( 'notice-success' )

				// Find the protection enabled and change the html.
				.find( '#protection_enabled' )
				.html( self.i18n.update_protection_activated );

			/*
			 * When a backup is completed, replace the "Backup Site Now" button with a "Backup Created
			 * Successfully" message.
			 *
			 * The .backup-site-now-section is the container for the "Backup Site Now" <form>.
			 *
			 * We're targeting the "visible" section so that the non-visible section, the one in the
			 * modal, does not get overwritten.
			 */
			$( '#backup-site-now-section:visible' ).html( '<p>' + self.i18n.backup_created + '</p>' );
		},

		/**
		 * Steps to take when the heartbeat ticket is received.
		 *
		 * @since 1.7.0
		 */
		onHeartbeatTick: function( e, data ) {

			/*
			 * This class deals with backups in progress. If our in progress class didn't give us
			 * any information, abort.
			 */
			if ( undefined === data.boldgrid_backup_in_progress ) {
				return;
			}

			/*
			 * Ensure the fast heartbeat.
			 *
			 * At this point in the script, we've already set the heartbeat to fast. The problem
			 * is that this elevated heartbeat only lasts for at most 2.5 minutes. If you've got
			 * a really big site, once that heartbeat goes back to 60, it's going to look like
			 * this froze.
			 */
			if ( 5 !== wp.heartbeat.interval ) {
				wp.heartbeat.interval( 'fast' );
			}

			if ( data.in_progress_data.percentage ) {
				self.setPercentage( data.in_progress_data.percentage );
			}

			// Update our progress bar.
			if ( 1 === data.in_progress_data.step ) {
				self.onStepDatabase( data.in_progress_data );
			} else if ( 2 === data.in_progress_data.step ) {
				self.onStepAddingFiles( data.in_progress_data );
			} else if ( 3 === data.in_progress_data.step && data.in_progress_data.tmp ) {
				self.onStepSaving( data.in_progress_data );
			} else {
				self.setStepActive();
				self.setLabel( data.in_progress_data.status );
				self.setSubText();
			}

			/*
			 * Steps to take when we no longer have a backup in progress.
			 *
			 * @todo This logic to determine when a backup has been completed needs to be improved.
			 */
			if ( null === data.boldgrid_backup_in_progress ) {
				self.onComplete( data );
			}
		},

		/**
		 * Action to take if we have a backup in progress.
		 *
		 * This function is ran when the page is ready. It checks to see if we're showing a "Backup
		 * In Progress" notice. If we are, then we need to hook into the heartbeat and find out when
		 * that backup has been completed.
		 *
		 * @since 1.7.0
		 */
		onInProgress: function() {
			self.$inProgressNotice = $( '.boldgrid-backup-in-progress' );

			self.hasProgressNotice = 1 === self.$inProgressNotice.length;

			if ( self.hasProgressNotice ) {
				self.onBackupInitiated();
			}
		},

		/**
		 * Steps to take with the progress bar when we're adding files to the archive.
		 *
		 * @since 1.7.0
		 *
		 * @param object In progress data received from ajax call.
		 */
		onStepAddingFiles: function( data ) {
			var percentage = Math.floor( ( data.total_files_done / data.total_files_todo ) * 100 );

			self.setStepActive( 2 );

			self.setPercentage( percentage );

			self.setLabel( percentage + '%' );

			/*
			 * Different styles are needed as the progress bar reaches 50% and begins to overlap
			 * the status text.
			 */
			if ( 50 <= percentage ) {
				self.$label.addClass( 'over-50' );
			}

			if ( 100 === percentage && data.status ) {
				self.setSubText();
			}

			/*
			 * If we have "last files" data within our "in progress data", loop through and use a
			 * setTimeout to display each one.
			 */
			if ( data.last_files ) {
				for ( var i = 0; i < data.last_files.length; i++ ) {
					setTimeout( self.setSubText, i * 1000 + 1, data.last_files[i] );
				}
			}
		},

		/**
		 * Steps to take with the progress bar when we're backing up the database.
		 *
		 * @since 1.7.0
		 *
		 * @param object In progress data received from ajax call.
		 */
		onStepDatabase: function( data ) {
			var stepIsActive = self.isStepActive( 1 ),
				timeout;

			self.setStepActive( 1 );

			/*
			 * Show tables being backed up.
			 *
			 * We only run this step once, the first time we know that database tables are being
			 * backed up.
			 */
			if ( ! stepIsActive ) {
				self.setSubText( self.i18n.adding_tables );

				for ( var i = 0; i < data.tables.length; i++ ) {
					timeout = ( ( i + 1 ) * 5000 ) / data.tables.length;
					setTimeout( self.setLabel, timeout, data.tables[i] );
				}

				/*
				 * We really don't know how long the database backup will take. In the above loop,
				 * we show all the tables that are being backed up within a 5 second period. After
				 * that time, we just finish it up with a "Completing database backup" message,
				 * which for more users shouldn't show for too long.
				 */
				setTimeout( function() {
					self.setLabel( self.i18n.completing_database );
					self.setSubText();
				}, timeout + 2000 );
			}
		},

		/**
		 * Steps to take with the progress bar when we're saving the archive.
		 *
		 * @since 1.7.0
		 *
		 * @param object In progress data received from ajax call.
		 */
		onStepSaving: function( data ) {
			var percentage;

			self.setStepActive( 3 );

			if ( data.percent_closed ) {
				percentage = Math.floor( data.percent_closed * 100 );
				self.setLabel( ' ' + percentage + '% Complete' );
			} else {
				percentage = Math.floor( ( data.tmp.size / data.total_size_archived ) * 100 );
				self.setLabel( ' ' + self.i18n.archive_file_size + data.tmp.size_format );
			}

			self.setPercentage( percentage );

			self.setSubText( self.i18n.size_before_compression + data.total_size_archived_size_format );
		},

		/**
		 * Set the text of the progress bar label.
		 *
		 * For example, if we're at 50% and the progress bar actually says "50%", it's the label
		 * that we're seeing.
		 *
		 * @since 1.7.0
		 */
		setLabel: function( string ) {
			self.$label.text( string );
		},

		/**
		 * Set the percentage of the progress bar.
		 *
		 * @since 1.7.0
		 *
		 * @param int percentage The percentage complete, 0 - 100.
		 */
		setPercentage: function( percentage ) {
			$( '#boldgrid-backup-in-progress-bar' )
				.show()
				.progressbar( {
					value: percentage
				} );

			if ( 50 <= percentage ) {
				self.$label.addClass( 'over-50' );
			} else {
				self.$label.removeClass( 'over-50' );
			}
		},

		/**
		 * Set a step as being active.
		 *
		 * @since 2.0.0
		 *
		 * @param string step The id of the container.
		 */
		setStepActive: function( step ) {
			var $container = $( '#boldgrid_backup_in_progress_steps' ),
				$steps = $container.find( '[data-step]' );

			if ( step && self.isStepActive( step ) ) {
				return;
			}

			$steps.removeClass( 'active' );

			if ( step ) {
				$container.find( '[data-step="' + step + '"]' ).addClass( 'active' );
			}
		},

		/**
		 * Set our sub text.
		 *
		 * For example, if we're adding files and we want to show each file that is being added
		 * (in smaller text below the progress bar), that is sub text.
		 *
		 * @since 2.0.0
		 *
		 * @param string text
		 */
		setSubText: function( text ) {
			var $lastFileArchived = $( '#last_file_archived' );

			if ( text ) {
				$lastFileArchived.text( text );
			} else {
				$lastFileArchived.empty();
			}
		},

		/**
		 * Modify the heartbeat and tell it we want "boldgrid_backup_in_progress" details.
		 *
		 * @since 1.7.0
		 */
		heartbeatModify: function( e, data ) {
			data.boldgrid_backup_in_progress = true;
			$( 'body' ).removeClass( 'heartbeat-lost-focus' );
		},

		/**
		 * Start "In progress" Heartbeat.
		 *
		 * @since 1.7.0
		 */
		heartbeatStart: function() {

			// Increase the heartbeat so we can get an update sooner.
			wp.heartbeat.interval( 'fast' );

			// Modify the heartbeat and ask for an update on our in progress backup.
			$( document ).on( 'heartbeat-send', self.heartbeatModify );

			// Now that we've modified the heartbeat, we need to listen for its tick.
			$( document ).on( 'heartbeat-tick', self.onHeartbeatTick );

			/*
			 * When your window loses focus, the heartbeat may slow down and you may not get updates
			 * as quickly. If you have two windows open and you're not actively looking at the
			 * backup in progress, you'll think it froze because of the slower heartbeat.
			 *
			 * This method adds a class to the body to indicate that the heartbeat has lost focus.
			 * The class will lighten the progress bar, to show that it's not currently active.
			 */
			window.setInterval( function() {
				var $body = $( 'body' ),
					body_class = 'heartbeat-lost-focus';

				if ( wp.heartbeat.hasFocus() ) {
					$body.removeClass( body_class );
				} else {
					$body.addClass( body_class );
				}
			}, 5000 );
		}
	};

	self = BOLDGRID.BACKUP.InProgress;
} )( jQuery );

BOLDGRID.BACKUP.InProgress.init();
