/**
 * FTP Settings
 *
 * @summary JavaScript for handling FTP Settings page.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdminFtpSettings,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.FtpSettings = function( $ ) {
	'use strict';

	var self = this,
		lang = BoldGridBackupAdminFtpSettings,
		$action,
		$port,
		$type,
		$form,
		$saveButton,
		$deleteButton,
		$spinner;

	/**
	 * @summary Take action when the delete button is clicked.
	 *
	 * @since 1.6.0
	 */
	self.onClickDelete = function() {
		$action.val( 'delete' );
		$form.submit();
	};

	/**
	 * @summary Action to take when form has been submitted.
	 *
	 * @since 1.6.0
	 */
	self.onSubmit = function() {
		$saveButton.attr( 'disabled', true );

		$deleteButton.attr( 'disabled', true );

		$spinner.removeClass( 'hidden' );
	};

	/**
	 * @summary Action to take when type has been changed.
	 *
	 * @since 1.6.0
	 */
	self.onTypeChange = function() {
		var suggestedPort = lang.default_port[$type.val()];

		$port.val( suggestedPort ).bgbuDrawAttention();
	};

	$( function() {
		( $action = $( '[name="action"]' ) ),
			( $port = $( '[name="port"]' ) ),
			( $type = $( '[name="type"]' ) ),
			( $form = $port.closest( 'form' ) ),
			( $saveButton = $form.find( '.button-primary' ) ),
			( $deleteButton = $form.find( '.button-secondary' ) ),
			( $spinner = $form.find( '.spinner' ) );

		$type.on( 'change', self.onTypeChange );
		$form.on( 'submit', self.onSubmit );
		$deleteButton.on( 'click', self.onClickDelete );
	} );
};

BoldGrid.FtpSettings( jQuery );
