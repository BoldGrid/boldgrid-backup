/**
 * Summary
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.3
 */

/* global ajaxurl,BoldGridBackupAdmin,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.ArchiveDetails = function( $ ) {

	var self = this,
		$body = $( 'body' ),
		adminLang = BoldGridBackupAdmin.lang,
		lang = boldgrid_backup_archive_details;

	/**
	 * @summary Handle the click of the Upload button.
	 */
	self.onClickUpload = function() {

		var $a = $(this),
			$td = $a.closest( 'td' ),
			$tr = $a.closest( 'tr' ),
			provider = $tr.attr( 'data-remote-provider' ),
			data = {
				'action' : 'boldgrid_backup_remote_storage_upload_' + provider,
				'filename' : $( '#filename' ).val(),
				'security' : $( '#_wpnonce' ).val(),
			},
			failUpload;

		/*
		 * @summary Action to take when an upload fails.
		 *
		 * @param {Object} response Our ajax response.
		 */
		failUpload = function( response ) {
			var defaultMessage = adminLang.xmark + ' ' + lang.failUpload,
				dataNotEmpty = response !== undefined && response.data !== undefined && response.data !== '',
				message = dataNotEmpty ? response.data : defaultMessage;

			$td.html( message );
		};

		$a
			.attr( 'disabled', 'disabled' )
			.text( lang.uploading + '...' )
			.after( ' <span class="spinner inline"></span>' );

		$.post( ajaxurl, data, function( response ) {
			$td.find( '.spinner' ).remove();

			if( response.success !== undefined && true === response.success ) {
				$a
					.after( '&#10003; ' + lang.uploaded )
					.remove();
			}

			if( response.success === undefined || true !== response.success ) {
				failUpload( response );
			}
		}).error( function( response ) {
			failUpload();
		});

		return false;
	};

	/**
	 * @summary Action to take when a user clicks download.
	 *
	 * @since 1.5.4
	 */
	self.onClickDownload = function() {
		var $button = $( this ),
			$tr = $button.closest( 'tr' ),
			provider = $tr.attr( 'data-remote-provider' ),
			data = {
				'action' : 'boldgrid_backup_remote_storage_download_' + provider,
				'filename' : $( '#filename' ).val(),
				'security' : $( '#_wpnonce' ).val(),
			},
			$spinner = $tr.find( '.spinner' ),
			$wpbody = $tr.closest( '#wpbody' );

		$spinner.addClass( 'inline' );

		$wpbody.bgbuDisableActions();

		$.post( ajaxurl, data, function( response ) {
			location.reload();
		}).error( function() {
			location.reload();
		});
	};

	/**
	 * Init.
	 */
	$( function() {
		$body.on( 'click', '.remote-storage a.upload', self.onClickUpload );
		$body.on( 'click', '.remote-storage .download-to-server', self.onClickDownload );
	});
};

BoldGrid.ArchiveDetails( jQuery );