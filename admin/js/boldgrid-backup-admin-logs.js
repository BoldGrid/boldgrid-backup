/**
 * Backup Logs.
 *
 * @summary This file handles the displaying of log files.
 *
 * @since 1.12.5
 */

/* global jQuery */

var BOLDGRID = BOLDGRID || {};

BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

( function( $ ) {
	'use strict';

	var self;

	/**
	 * Logs.
	 *
	 * @since 1.12.5
	 */
	BOLDGRID.BACKUP.Logs = {

		/**
		 * i18n.
		 *
		 * @since 1.7.0
		 *
		 * @type object
		 */
		i18n: window.BoldGridBackupAdminLogs || {},

		/**
		 * Init.
		 *
		 * @since 1.12.5
		 */
		init: function() {
			self._onReady();
		},

		/**
		 * Steps to take when a log file is clicked on.
		 *
		 * @since 1.12.5
		 */
		onClickLog: function() {
			var data = {
				action: 'boldgrid_backup_view_log',
				filename: $( this ).attr( 'data-filename' ),
				nonce: $( '#bgbup_log_nonce' ).val()
			};

			/*
			 * Show a loading message in the thickbox modal.
			 *
			 * Thickbox has a few events, but "on open" is not one of them. The timeout is required
			 * to allow some time for the modal to open before we take action. In testing, a 1ms timeout
			 * worked. To be on the safe side, we're using 10ms.
			 */
			setTimeout( function() {
				$( '#TB_window' ).addClass( 'bg-full-screen' );

				$( '#TB_ajaxContent' ).html(
					'<p id="bgbu_thickbox_loading">' +
						self.i18n.loading +
						' <span class="spinner inline"></span></p>'
				);
			}, 10 );

			$.post( ajaxurl, data, function( response ) {
				$( '#TB_ajaxContent' ).html( response.data );
			} ).fail( function( jqXHR ) {

				/*
				 * @todo This error message could use some work. For 500 errors, WordPress will return
				 * "There has been a critical error on this website. Learn more about debugging in WordPress."
				 * Show an "unknown error" and have the user contact BoldGrid for help rather than send
				 * the user off learning about debugging.
				 */
				var error = jqXHR.status + ' ' + jqXHR.statusText + ': ' + self.i18n.unknownError;

				$( '#TB_ajaxContent' ).html( '<div class="notice notice-error"><p>' + error + '</p></div>' );
			} );
		},

		/**
		 * On ready.
		 *
		 * @since 1.7.0
		 */
		_onReady: function() {
			$( function() {
				$( '#section_logs a[data-filename]' ).on( 'click', self.onClickLog );
			} );
		}
	};

	self = BOLDGRID.BACKUP.Logs;
} )( jQuery );

BOLDGRID.BACKUP.Logs.init();
