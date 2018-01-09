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

	$( function() {
		self.bindHelpClick();
		self.hideBackupNotice();
	});
};

BoldGrid.Backup( jQuery );

/**
 * @summary Draw attention to an element.
 *
 * @since 1.5.4
 */
jQuery.fn.bgbuDrawAttention = function() {
	var currentColor;

	if( this.is( 'input' ) ) {
		this
			.css( 'background', '#ddd' )
			.animate( {backgroundColor: '#fff'}, 500 );
		return;
	}

	if( this.is( '.dashicons-editor-help')) {
		this.effect( 'bounce', { times:2 }, 'normal' );
	}

	if( this.is( 'span' ) ) {
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