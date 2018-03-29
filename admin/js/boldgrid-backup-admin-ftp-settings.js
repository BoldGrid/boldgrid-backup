/**
 * BoldGrid Backup FTP Settings
 *
 * @summary JavaScript for handling FTP Settings page.
 *
 * @since 1.5.4
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdminFtpSettings,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.FtpSettings = function( $ ) {
	'use strict';

	var self = this,
		lang = BoldGridBackupAdminFtpSettings,
		$action = $( '[name="action"]' ),
		$port = $( '[name="port"]' ),
		$type = $( '[name="type"]' ),
		$form = $port.closest( 'form' ),
		$saveButton = $form.find( '.button-primary' ),
		$deleteButton = $form.find( '.button-secondary' ),
		$spinner = $form.find( '.spinner' );

	/**
	 * @summary Action to take when form has been submitted.
	 *
	 * @since 1.5.4
	 */
	self.onSubmit = function() {
		var $clicked = $form.find( 'input[type=submit]:focus' ),
			action = $clicked.hasClass( 'button-primary' ) ? 'save' : 'delete';

		$action.val( action );

		$saveButton.attr( 'disabled', true );

		$deleteButton.attr( 'disabled', true );

		$spinner.removeClass( 'hidden' );
	};

	/**
	 * @summary Action to take when type has been changed.
	 *
	 * @since 1.5.4
	 */
	self.onTypeChange = function() {
		var suggestedPort = lang.default_port[ $type.val() ];

		$port
			.val( suggestedPort )
			.bgbuDrawAttention();
	};

	$( function() {
		$type.on( 'change', self.onTypeChange );
		$form.on( 'submit', self.onSubmit );
	} );
};

BoldGrid.FtpSettings( jQuery );
