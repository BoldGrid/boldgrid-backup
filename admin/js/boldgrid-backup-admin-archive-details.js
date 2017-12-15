/**
 * Summary
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.3
 */

/* global ajaxurl */

var BoldGrid = BoldGrid || {};

BoldGrid.ArchiveDetails = function( $ ) {

	var self = this;

	/**
	 *
	 */
	self.onClickUpload = function() {

		var $a = $(this),
			$td = $a.closest( 'td' ),
			$tr = $a.closest( 'tr' ),
			provider = $tr.attr( 'data-remote-provider' ),
			data = {
				'action' : 'boldgrid_backup_remote_storage_upload_' + provider,
				'filepath' : $( '#filepath' ).val(),
				'security' : $( '#_wpnonce' ).val(),
			},
			failUpload = function() {
				$td
					.empty()
					.append( '&#10007; ' + boldgrid_backup_archive_details.failUpload )
			};

		$a
			.attr( 'disabled', 'disabled' )
			.text( boldgrid_backup_archive_details.uploading + '...' )
			.after( ' <span class="spinner inline"></span>' );

		$.post( ajaxurl, data, function( response ) {
			$td.find( '.spinner' ).remove();

			if( response.success !== undefined && true === response.success ) {
				$a
					.after( '&#10003; ' + boldgrid_backup_archive_details.uploaded )
					.remove();
			}

			if( response.success === undefined || true !== response.success ) {
				failUpload();
			}
		}).error( function( response ) {
			failUpload();
		});

		return false;
	}

	/**
	 * Init.
	 */
	$( function() {
		$( 'body' ).on( 'click', '.remote-storage a.upload', self.onClickUpload );
	});
};

BoldGrid.ArchiveDetails( jQuery );