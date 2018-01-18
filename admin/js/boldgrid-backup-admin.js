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

	$( function() {
		self.bindHelpClick();
		self.hideBackupNotice();

		$( 'body' ).on( 'click', '[data-toggle-target]', self.onClickToggle );
	});
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