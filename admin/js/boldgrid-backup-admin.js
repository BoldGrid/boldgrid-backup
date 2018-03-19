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
			if( id === undefined ) {
				return;
			}

			// Toggle the help text.
			$( '.help[data-id="' + id + '"]' ).slideToggle();
		});
	};

	/**
	 * @summary Remove WordPress' important notice about backups.
	 *
	 * We're already adding our own notice, no need to have 2 notices.
	 *
	 * @since 1.5.3
	 */
	self.hideBackupNotice = function() {
		if( pagenow === undefined || 'update-core' !== pagenow ) {
			return;
		}

		$( 'a[href*="WordPress_Backups"]' ).closest( '.notice' ).remove();
	};

	/**
	 * @summary Handle the clicking of a show / hide toggle.
	 *
	 * In the example below, the show / hide link has a data-toggle-target attr
	 * that helps to identify the element to toggle.
	 * # <a href="" data-toggle-target="#more_info">Show</a>
	 * # <div id="more_info" class="hidden">
	 *
	 * @since 1.5.4
	 */
	self.onClickToggle = function() {
		var $e = $( this ),
			show = 'Show',
			hide = 'Hide',
			target = $e.attr( 'data-toggle-target'),
			$target = $( target ),
			isVisible = $target.is( ':visible' );

		if( isVisible ) {
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
		var complete = false,
			$inProgressNotice = $( '.boldgrid-backup-in-progress' );

		// If we're not actually showing an "in progress" notice, abort.
		if( 1 !== $inProgressNotice.length ) {
			return;
		}

		// Increase the heartbeat so we can get an update sooner.
		wp.heartbeat.interval( 'fast' );

		/*
		 * When the heartbeat is sent, include that we're looking for an update
		 * on the in progress backup.
		 */
		$( document ).on( 'heartbeat-send', function( e, data ) {
			if( ! complete ) {
				data['boldgrid_backup_in_progress'] = true;
			}
		});

		// When the heartbeat is received, check to see if the backup has completed.
		$( document ).on( 'heartbeat-tick', function( e, data ) {
			var $notice;

			if( undefined === data.boldgrid_backup_in_progress ) {
				return;
			}

			if( ! data.boldgrid_backup_in_progress ) {
				$notice = $( data.boldgrid_backup_complete );
				$notice
					.css( 'display', 'none' )
					.insertBefore( $inProgressNotice )
					.slideDown();

				$inProgressNotice.slideUp();

				wp.heartbeat.interval( 'standard' );
				complete = true;

				$( 'body' ).trigger( 'make_notices_dismissible' );
				$( 'body' ).trigger( 'boldgrid_backup_complete' );
			}
		});
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
                $button = $( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
                btnText = typeof commonL10n !== 'undefined' ? commonL10n.dismiss : wp.customize.l10n.close;

            // Ensure plain text
            $button.find( '.screen-reader-text' ).text( btnText );
            $button.on( 'click.wp-dismiss-notice', function( event ) {
                event.preventDefault();
                $el.fadeTo( 100, 0, function() {
                    $el.slideUp( 100, function() {
                        $el.remove();
                    });
                });
            });

            $el.append( $button );
        });
    };

	$( function() {
		self.bindHelpClick();
		self.hideBackupNotice();
		self.updatePremiumLink();

		/*
		 * If and when a backup is in progress, we need to begin waiting to hear
		 * for that backup to complete.
		 */
		self.onInProgress();
		$( 'body' ).on( 'boldgrid_backup_progress_notice_added', self.onInProgress );

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
	});

	/**
	 * @summary Open submenu "Get Premium" link in a new tab.
	 *
	 * WordPress does not have a way to configure dashboard menu items to open
	 * in a new tab, so we'll handle it via js.
	 *
	 * @since 1.6.0
	 */
	self.updatePremiumLink = function() {
		$( '#adminmenu' ).find( 'a[href="' + BoldGridBackupAdmin.get_premium_url + '"]' ).attr( 'target', '_blank' );
	}
};

BoldGrid.Backup( jQuery );

/**
 * @summary Draw attention to an element.
 *
 * @since 1.5.4
 */
jQuery.fn.bgbuDrawAttention = function() {
	var currentColor,
		// In seconds, minimum time between each animation.
		animateInterval = 1 * 1000,
		d = new Date(),
		lastAnimation = this.attr( 'data-last-animation' );

	this.attr( 'data-last-animation', d.getTime() );

	// If we are currently animating this element, return.
	if( this.parent().hasClass( 'ui-effects-wrapper' ) ) {
		return;
	}


	// If enough time hasn't passed yet since the last animation, return.
	if( lastAnimation && ( d.getTime() - lastAnimation ) < animateInterval ) {
		return;
	}

	if( this.is( 'input' ) ) {
		this
			.css( 'background', '#ddd' )
			.animate( {backgroundColor: '#fff'}, 500 );
	} else if( this.is( '.dashicons-editor-help')) {
		this.effect( 'bounce', { times:2 }, 'normal' );
	} else if( this.is( 'span' ) ) {
		/*
		 * Get the original color to animate back to. This is needed because if
		 * the user clicks on an element that is in the middle of an animation,
		 * the current color will not be the original.
		 */
		if( ! this.attr( 'data-original-color' ) ) {
			this.attr( 'data-original-color', this.css( 'color' ) );
		}
		currentColor = this.attr( 'data-original-color' );

		this
			.css( 'color', '#fff' )
			.animate( { color: currentColor }, 500 );
	}
};

/**
 * @summary Disable all actions found within an element (a, buttons, etc).
 *
 * For example, if we click "restore" on a page, we want to disable all other
 * actions within the wpwrap (IE can't restore and delete at the same time).
 *
 * @since 1.5.4
 */
jQuery.fn.bgbuDisableActions = function() {
	this.find( 'a, [type="submit"]' ).attr( 'disabled', 'disabled' );
};