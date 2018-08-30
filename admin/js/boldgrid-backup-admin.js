/**
 * This file contains javascript to load on all admin pages of the BoldGrid Backup plugin.
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.1
 */

/* global jQuery,pagenow */

var BoldGrid = BoldGrid || {};

BoldGrid.Backup = function( $ ) {
	var self = this;

	/**
	 * @summary Handle the click of help buttons.
	 *
	 * @since 1.3.1
	 */
	this.bindHelpClick = function() {
		$( 'body' ).on( 'click', '.dashicons-editor-help', function() {
			var id = $( this ).attr( 'data-id' );

			// If we don't have a data-id, abort.
			if ( id === undefined ) {
				return;
			}

			// Toggle the help text.
			$( '.help[data-id="' + id + '"]' ).slideToggle();
		} );
	};

	/**
	 * @summary Remove WordPress' important notice about backups.
	 *
	 * We're already adding our own notice, no need to have 2 notices.
	 *
	 * @since 1.5.3
	 */
	self.hideBackupNotice = function() {
		if ( pagenow === undefined || 'update-core' !== pagenow ) {
			return;
		}

		$( 'a[href*="WordPress_Backups"]' )
			.closest( '.notice' )
			.remove();
	};
	
	/**
	 * 
	 */
	self.onBackupInitiated = function() {
		var shown_tables = false;
		self.setupProgressHeartbeat();
		
		$( '#boldgrid-backup-in-progress-bar ')
			.show()
			.progressbar({
				value: 0
			});
		
		// When the heartbeat is received, check to see if the backup has completed.
		$( document ).on( 'heartbeat-tick', function( e, data ) {
			var timeout;

			if ( undefined === data.boldgrid_backup_in_progress ) {
				return;
			}
			
			if( data.in_progress_data.tables ) {
				
				if( ! shown_tables ) {
					
					$( '.progress-label' ).text( data.in_progress_data.status );
					
					for( var i = 0; i < data.in_progress_data.tables.length; i++ ) {	
						var table_name = data.in_progress_data.tables[i];
						
						timeout = (i+1) * 5000 / data.in_progress_data.tables.length;
						
						var show_database_tables = function( table_name ) {
							$( '#last_file_archived' ).html( '<strong>Tables included in backup</strong>: ' + table_name );
						};
						
						setTimeout( show_database_tables, timeout, table_name );
					}
					
					shown_tables = true;	
				} else {
					setTimeout( function() {
						$( '#last_file_archived' ).html( 'Completing database backup...' );	
						}, timeout + 2000 );
				}
				
			} else if ( data.in_progress_data.total_files_done && data.in_progress_data.total_files_todo ) {
				var percentage = Math.floor( data.in_progress_data.total_files_done / data.in_progress_data.total_files_todo * 100 );
					
				$( '#boldgrid-backup-in-progress-bar ').progressbar({
					value: percentage
				});
					
				if( percentage >= 50 ) {
					$( '.progress-label' ).addClass( 'over-50' );
				}
					
				if( 100 === percentage && data.in_progress_data.status ) {
					$( '.progress-label' ).text( data.in_progress_data.status );
					$( '#last_file_archived' ).empty();
				} else {
					$( '.progress-label' ).text( percentage + '%' );	
				}	
			} else {
				$( '.progress-label' ).text( data.in_progress_data.status );
			}
				
			if( data.in_progress_data.last_files ) {
				for( var i = 0; i < data.in_progress_data.last_files.length; i++ ) {
						
					var last_file_name = data.in_progress_data.last_files[i],
						timeout = i * 1000 + 1;
						
					var show_last_file = function( last_file_name ) {
						$( '#last_file_archived' ).text( last_file_name );
					};
					
					setTimeout( show_last_file, timeout, last_file_name );
				}
			}
		} );
	}

	/**
	 * @summary Handle the clicking of a show / hide toggle.
	 *
	 * In the example below, the show / hide link has a data-toggle-target attr
	 * that helps to identify the element to toggle.
	 * # <a href="" data-toggle-target="#more_info">Show</a>
	 * # <div id="more_info" class="hidden">
	 *
	 * @since 1.6.0
	 */
	self.onClickToggle = function() {
		var $e = $( this ),
			show = 'Show',
			hide = 'Hide',
			target = $e.attr( 'data-toggle-target' ),
			$target = $( target ),
			isVisible = $target.is( ':visible' );

		if ( isVisible ) {
			$target.slideUp();
			$e.html( show );
		} else {
			$target.slideDown();
			$e.html( hide );
		}

		return false;
	};

	/**
	 * @summary Action to take if we have a backup in progress.
	 *
	 * If we do have a backup in progress, we'll hook into the heartbeat and find
	 * out when that backup has been completed.
	 *
	 * @since 1.6.0
	 */
	self.onInProgress = function() {
		var $inProgressNotice = $( '.boldgrid-backup-in-progress' );

		// If we're not actually showing an "in progress" notice, abort.
		if ( 1 !== $inProgressNotice.length ) {
			return;
		}

		self.setupProgressHeartbeat();

		// When the heartbeat is received, check to see if the backup has completed.
		$( document ).on( 'heartbeat-tick', function( e, data ) {
			var $notice;

			if ( undefined === data.boldgrid_backup_in_progress ) {
				return;
			}

			if ( ! data.boldgrid_backup_in_progress ) {
				$notice = $( data.boldgrid_backup_complete );
				$notice
					.css( 'display', 'none' )
					.insertBefore( $inProgressNotice )
					.slideDown();

				$inProgressNotice.slideUp();

				$( 'body' ).trigger( 'make_notices_dismissible' );
				$( 'body' ).trigger( 'boldgrid_backup_complete' );
			}
		} );
		
		self.onBackupInitiated();
	};

	/**
	 * @summary Make an admin notice dismissible.
	 *
	 * This is a core WordPress function copied from wp-admin/js/common.js.
	 *
	 * Unfortunately this function cannot be called upon at will. If we dynamically
	 * add a notice and it includess the is-dismissible class, then we'll need
	 * to actually make it dismissible.
	 *
	 * @since 1.6.0
	 */
	self.makeNoticesDismissible = function() {
		$( '.notice.is-dismissible' ).each( function() {
			var $el = $( this ),
				$button = $(
					'<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>'
				),
				btnText = 'undefined' !== typeof commonL10n ? commonL10n.dismiss : wp.customize.l10n.close;

			// Ensure plain text
			$button.find( '.screen-reader-text' ).text( btnText );
			$button.on( 'click.wp-dismiss-notice', function( event ) {
				event.preventDefault();
				$el.fadeTo( 100, 0, function() {
					$el.slideUp( 100, function() {
						$el.remove();
					} );
				} );
			} );

			$el.append( $button );
		} );
	};
	
	/**
	 * 
	 */
	self.setupProgressHeartbeat = function() {
		// Increase the heartbeat so we can get an update sooner.
		wp.heartbeat.interval( 'fast' );

		/*
		 * When the heartbeat is sent, include that we're looking for an update
		 * on the in progress backup.
		 */
		$( document ).on( 'heartbeat-send', function( e, data ) {
			data['boldgrid_backup_in_progress'] = true;
			
			$body = $( 'body' ).removeClass( 'heartbeat-lost-focus' )
		} );
		
		var heartbeat_focus_interval = window.setInterval(
			function(){
				
				var $body = $( 'body' ),
					body_class = 'heartbeat-lost-focus';
			
				if( wp.heartbeat.hasFocus() ) {
					$body.removeClass( body_class );
				} else {
					$body.addClass( body_class );
				}
			},
			5000
		);
	}

	$( function() {
		self.bindHelpClick();
		self.hideBackupNotice();
		self.updatePremiumLink();

		/*
		 * If and when a backup is in progress, we need to begin waiting to hear
		 * for that backup to complete.
		 * 
		 * Event boldgrid_backup_progress_notice_added currently only triggered within the customizer.
		 * When a user clicks on themes, we may dynamically show them a notice that a backup is in progress.
		 */
		self.onInProgress();
		$( 'body' ).on( 'boldgrid_backup_progress_notice_added', self.onInProgress );
		
		$( 'body' ).on( 'boldgrid_backup_initiated', self.onBackupInitiated );

		$( 'body' ).on( 'click', '[data-toggle-target]', self.onClickToggle );
		$( 'body' ).on( 'make_notices_dismissible', self.makeNoticesDismissible );

		/*
		 * Remove temporary "page loading" messages.
		 *
		 * Some pages may take a few moments to render. For example, when checking
		 * ftp credentials, it may take ~3 seconds. We give the user a message,
		 * "Checking credentials", and then remove this notices afterwards by
		 * removing anything with a bgbu_remove_load class.
		 */
		$( '.bgbu-remove-load' ).remove();
	} );

	/**
	 * @summary Open submenu "Get Premium" link in a new tab.
	 *
	 * WordPress does not have a way to configure dashboard menu items to open
	 * in a new tab, so we'll handle it via js.
	 *
	 * @since 1.6.0
	 */
	self.updatePremiumLink = function() {
		$( '#adminmenu' )
			.find( 'a[href="' + BoldGridBackupAdmin.get_premium_url + '"]' )
			.attr( 'target', '_blank' );
	};
};

BoldGrid.Backup( jQuery );

/**
 * @summary Draw attention to an element.
 *
 * @since 1.6.0
 */
jQuery.fn.bgbuDrawAttention = function() {
	var currentColor,

		// In seconds, minimum time between each animation.
		animateInterval = 1 * 1000,
		d = new Date(),
		lastAnimation = this.attr( 'data-last-animation' );

	this.attr( 'data-last-animation', d.getTime() );

	// If we are currently animating this element, return.
	if ( this.parent().hasClass( 'ui-effects-wrapper' ) ) {
		return;
	}

	// If enough time hasn't passed yet since the last animation, return.
	if ( lastAnimation && d.getTime() - lastAnimation < animateInterval ) {
		return;
	}

	if ( this.is( 'input' ) ) {
		this.css( 'background', '#ddd' ).animate( { backgroundColor: '#fff' }, 500 );
	} else if ( this.is( '.dashicons-editor-help' ) ) {
		this.effect( 'bounce', { times: 2 }, 'normal' );
	} else if ( this.is( 'span' ) ) {

		/*
		 * Get the original color to animate back to. This is needed because if
		 * the user clicks on an element that is in the middle of an animation,
		 * the current color will not be the original.
		 */
		if ( ! this.attr( 'data-original-color' ) ) {
			this.attr( 'data-original-color', this.css( 'color' ) );
		}
		currentColor = this.attr( 'data-original-color' );

		this.css( 'color', '#fff' ).animate( { color: currentColor }, 500 );
	}
};

/**
 * @summary Disable all actions found within an element (a, buttons, etc).
 *
 * For example, if we click "restore" on a page, we want to disable all other
 * actions within the wpwrap (IE can't restore and delete at the same time).
 *
 * @since 1.6.0
 */
jQuery.fn.bgbuDisableActions = function() {
	this.find( 'a, [type="submit"]' ).attr( 'disabled', 'disabled' );
};
