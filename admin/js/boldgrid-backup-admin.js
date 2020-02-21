/**
 * This file contains javascript to load on all admin pages of the plugin
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

	$( function() {
		self.bindHelpClick();
		self.hideBackupNotice();
		self.updatePremiumLink();
		self.installPremiumLink();

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

	self.installPremiumLink = function() {
		$( '.install-premium' ).one( "click", function( event ) {
			event.preventDefault();
			downloadUrl = $(this).attr( 'href' );
			noticeP = $(this).parent();
			noticeDiv = $(noticeP).parent();
			$( noticeP ).html('<span class="spinner inline"></span><span class="in-progress">&nbsp;Installing Premium Plugin.</span>');

			adminAjaxUrl = $( this ).attr( 'href' );
			nonce = $( this ).attr( 'data-nonce' );

			$.post( adminAjaxUrl, {action: 'boldgrid_backup_install_premium', nonce: nonce, data: 'install'}, function( response ) {
				console.log( response.data.installed );
				if ( true === response.data.installed ) {
					$(noticeDiv).find( '.spinner' ).remove();
					$(noticeDiv).find( '.in-progress' ).html('&#x2714;&nbsp;Premium Plugin Installed' );
					$(noticeDiv).find( '.in-progress' ).attr( 'class', 'complete' );
					$(noticeDiv).append( '<p><span class="spinner inline"></span><span class="in-progress">&nbsp;Activating Premium Plugin.</span></p>' );
					self.activatePremium(adminAjaxUrl, nonce, noticeDiv);
				} else {
					$(noticeDiv).find( '.spinner').remove();
					$(noticeDiv).find( '.in-progress').html( '&#x2718;&nbsp;Premium plugin installation Failed. <a href="' + downloadUrl + '">Click Here</a> to download and install manually.');
					$(noticeDiv).find( '.in-progress' ).attr( 'class', 'failed' );
				}
			} );
		} );
	};

	self.activatePremium = function(adminAjaxUrl, nonce, noticeDiv) {
		$.post( adminAjaxUrl, {action: 'boldgrid_backup_install_premium', nonce: nonce, data: 'activate'}, function( response ) {
			console.log( response.data.activated );
			if ( true === response.data.activated ) {
				$(noticeDiv).find( '.spinner' ).remove();
				$(noticeDiv).find( '.in-progress' ).html('&#x2714;&nbsp;Premium Plugin Activated' );
				$(noticeDiv).find( '.in-progress' ).attr( 'class', 'complete' );
			} else {
				$(noticeDiv).find( '.spinner').remove();
				$(noticeDiv).find( '.in-progress').html( '&#x2718;&nbsp;Premium plugin activation Failed.');
				$(noticeDiv).find( '.in-progress' ).attr( 'class', 'failed' ); 
			}
		});
	}
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
