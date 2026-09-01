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
		 * The number of times our ajax call has said yes, is done.
		 *
		 * This is used to help prevent a race condition. Before showing results, we let the is done
		 * count reach 2 so that all processes can finish and we ensure we have the appropriate error
		 * message, if any.
		 *
		 * @since 1.14.13
		 *
		 * @type int
		 */
		isDoneCount: 0,

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
		 * Init our protection notice variables.
		 *
		 * Prior to @1.14.13, this code lived within the _onReady. It has since been moved here
		 * so that it may be called within the customizer. The Protection notice is added via ajax in
		 * the Customizer, and we cannot set these values accurately within the _onReady.
		 */
		initProtectionNotice: function() {
			self.$protectionNotice = $( '.boldgrid-backup-protect-now' );
			self.hasProtectionNotice = 1 === self.$protectionNotice.length;
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
					 *
					 * If there is a quick fail, we still need to load the in progress system so we
					 * can show the user the error.
					 */
					if ( ! BoldGridBackupAdmin.is_done || BoldGridBackupAdmin.is_quick_fail ) {
						setTimeout( self.start, 1000 );
					}

					/*
					 * Event "boldgrid_backup_progress_notice_added" currently only triggered within the
					 * customizer. When a user clicks on themes, we may dynamically show them a notice that
					 * a backup is in progress.
					 */
					$( document ).on( 'boldgrid_backup_progress_notice_added', 'body', self.start );

					/*
					 * Take action when a backup is started.
					 *
					 * The only script triggering this event is backup-now.js.
					 */
					$( document ).on( 'boldgrid_backup_initiated', 'body', self.start );

					$( document ).on( 'boldgrid_backup_complete', 'body', self.onComplete );

					// Configure our "Update Protection" values.
					self.$protectionNotice = $( '.boldgrid-backup-protect-now' );
					self.hasProtectionNotice = 1 === self.$protectionNotice.length;

					// Take action when a tab within the in progress notice is clicked.
					$( document ).on(
						'click',
						'.notice .bgbkup-nav-tab-wrapper-in-progress .nav-tab',
						self.onNavClick
					);

					// Take action when the user clicks cancel backup.
					$( document ).on( 'click', '#bgbkup_cancel_backup', self.onClickCancel );
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
		 * Action to take when a user clicks to cancel a backup.
		 *
		 * @since 1.14.13
		 */
		onClickCancel: function() {

			/*
			 * Make the ajax call to cancel the backup.
			 *
			 * No success, error, or complete callback is passed to the ajax call. Status updates will
			 * be handled naturally by the in progress system.
			 */
			$.ajax( {
				url: ajaxurl,
				data: {
					action: 'boldgrid_backup_cancel',
					cancel_auth: $( '#bgbkup-cancel' ).val()
				},
				type: 'post'
			} );

			$( '#bgbkup_progress_actions' ).html(
				wp.i18n.__( 'Canceling backup', 'boldgrid-backup' ) + ' <span class="spinner inline"></span>'
			);
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

			data.is_success ? self.onSuccess( data ) : self.onError( data );
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

			// Init "protection notice" values if in the customizer. Please see that init method.
			if ( typeof pagenow !== undefined && 'customize' === pagenow ) {
				self.initProtectionNotice();
			}

			if ( self.hasProtectionNotice ) {
				self.$protectionNotice

					// Change the notice from a warning to an error.
					.removeClass( 'notice-warning' )
					.addClass( 'notice-error' )

					// Clean up the existing markup of the notice.
					.find( '#protection_enabled' )
					.nextUntil( '#boldgrid_backup_in_progress_container' )
					.remove()
					.end()
					.remove()
					.end();

				$( '<p>' + self.i18n.backup_error + '</p><p>' + self.i18n.get_support + '</p>' ).insertBefore(
					'#boldgrid_backup_in_progress_container'
				);

				self.updateStatus( { message: data.boldgrid_backup_error.message } );
			} else {
				self.updateStatus( data.boldgrid_backup_error );
			}
		},

		/**
		 * Steps to take when a user clicks on a nav element.
		 *
		 * @since 1.14.13
		 */
		onNavClick: function() {
			var $clickedNav = $( this ),
				$activeNav = $clickedNav.siblings( '.nav-tab-active' ).eq( 0 );

			// If clicking on the active tab, do nothing.
			if ( $clickedNav.is( $activeNav ) ) {
				return;
			}

			// Toggle which tab is active.
			$clickedNav.addClass( 'nav-tab-active' );
			$activeNav.removeClass( 'nav-tab-active' );

			// Toggle the container that's actually shown.
			$( '#' + $clickedNav.attr( 'data-container' ) ).show();
			$( '#' + $activeNav.attr( 'data-container' ) ).hide();
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
				self.updateStatus( data.boldgrid_backup_complete );
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

				/*
				 * Set the percentage to 100 so that the text doesn't look funny with a half colored
				 * background. This was done because we would show, "Complete" yet the progress bar
				 * was not yet complete.
				 */
				self.setPercentage( 100 );
			}

			// Update our log.
			$( '#bgbkup_progress_log .bgbkup-log' ).html( data.log.trim() );

			// Steps to take when we no longer have a backup in progress.
			if ( data.is_done ) {
				self.isDoneCount++;

				// Race condition. Give it one more heartbeat (5 more seconds).
				if ( data.is_success || 1 < self.isDoneCount ) {
					self.onComplete( data );
				}
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
		start: function() {
			if ( 1 !== $( '#boldgrid_backup_in_progress_container' ).length ) {
				return;
			}

			self.$inProgressNotice = $( '.boldgrid-backup-in-progress' );
			self.hasProgressNotice = 1 === self.$inProgressNotice.length;

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

			// Show and initialize our progress bar.
			$( '#boldgrid_backup_in_progress_container' ).slideDown();
			self.$label = $( '.progress-label' );
			self.setPercentage( 0 );

			/*
			 * Additional UX changes.
			 *
			 * IE, disable "backup now" buttons if they're shown (IE within protection notices).
			 *
			 * @todo This set of code is somewhat copied from backup-now.js, within the backupNow method.
			 * If need be one day, combine into a reusable method.
			 */
			$( '#backup-site-now' )
				.attr( 'disabled', 'disabled' )
				.css( 'pointer-events', 'none' );
			$( '#you_may_leave' ).fadeIn();
			$( '#backup-site-now-form' )
				.find( '.spinner' )
				.addClass( 'inline' );
		},

		/**
		 * Update the status of an in progress notice.
		 *
		 * This was introduced as of @1.14.13 when tabs were introduced. When the status of a backup
		 * in progress changes, this method will update the display (rather than the legacy method of
		 * hiding the existing status notice and showing a new one).
		 *
		 * @since 1.14.13
		 *
		 * @param array data An array of data about the notice.
		 */
		updateStatus: function( data ) {
			var $status = $( '#bgbkup_progress_status' );

			if ( undefined !== data.class ) {
				self.$inProgressNotice.attr( 'class', data.class );
			}

			if ( undefined !== data.header ) {
				self.$inProgressNotice.find( '.header-notice' ).html( data.header );
			}

			if ( undefined !== data.message ) {
				$status.slideUp( {
					complete: function() {
						$status.html( data.message ).slideDown();
					}
				} );
			}
		}
	};

	self = BOLDGRID.BACKUP.InProgress;
} )( jQuery );

BOLDGRID.BACKUP.InProgress.init();
