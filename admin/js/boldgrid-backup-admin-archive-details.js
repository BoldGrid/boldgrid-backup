/**
 * Archive Details
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.3.3
 */

/* global ajaxurl,BoldGridBackupAdmin,jQuery,boldgrid_backup_archive_details */

var BoldGrid = BoldGrid || {};

BoldGrid.ArchiveDetails = function( $ ) {
	var self = this,
		$body = $( 'body' ),
		$contentWrap = $( '#wp-content-wrap' ),
		$downloadFirst = $body.find( '#download_first' ),
		$editorTabs = $body.find( '.wp-editor-tabs button' ),
		adminLang = BoldGridBackupAdmin.lang,
		lang = boldgrid_backup_archive_details;

	/**
	 * @summary Handle the click of the Upload button.
	 */
	self.onClickUpload = function() {
		var $a = $( this ),
			provider = $a.attr( 'data-provider-id' ),
			data = {
				action: 'boldgrid_backup_remote_storage_upload_' + provider,
				filename: $( '#filename' ).val(),
				security: $( '#bgbkup_remote_upload_nonce' ).val()
			},
			failUpload;

		/*
		 * @summary Action to take when an upload fails.
		 *
		 * @param {Object} response Our ajax response.
		 */
		failUpload = function( response ) {
			var defaultMessage = adminLang.xmark + ' ' + lang.failUpload,
				dataNotEmpty =
					response !== undefined && response.data !== undefined && '' !== response.data,
				message = dataNotEmpty ? defaultMessage + ' ' + response.data : defaultMessage;

			$a.parent().html( message );
		};

		$a.attr( 'disabled', 'disabled' )
			.text( lang.uploading + '...' )
			.after( ' <span class="spinner inline"></span>' );

		$.post( ajaxurl, data, function( response ) {
			$a.next( '.spinner' ).remove();

			if ( response.success !== undefined && true === response.success ) {
				$a.after( '&#10003; ' + lang.uploaded ).remove();
			}

			if ( response.success === undefined || true !== response.success ) {
				failUpload( response );
			}
		} ).error( function( response ) {
			failUpload();
		} );

		return false;
	};

	/**
	 * @summary Action to take when a user clicks download.
	 *
	 * @since 1.6.0
	 */
	self.onClickDownload = function() {
		var $button = $( this ),
			provider = $button.attr( 'data-provider-id' ),
			data = {
				action: 'boldgrid_backup_remote_storage_download_' + provider,
				filename: $( '#filename' ).val(),
				security: $( '#bgbkup_archive_details_nonce' ).val()
			},
			$spinner = $button.next( '.spinner' ),
			$wpbody = $body.find( '#wpbody' );

		$spinner.addClass( 'inline' );

		$wpbody.bgbuDisableActions();

		$.post( ajaxurl, data, function( response ) {
			location.reload();
		} ).error( function() {
			location.reload();
		} );
	};

	/**
	 * @summary Action to take when the user clicks the "download remote" button.
	 *
	 * This method downloads the first remote archive it finds.
	 *
	 * @since 1.6.0
	 */
	self.onClickDownloadFirst = function() {
		var $downloadToServer = $body.find( '.download-to-server' ),
			$spinner = $( this ).next( '.spinner' );

		$spinner.addClass( 'inline' );

		$downloadToServer
			.first()
			.click()

			// Remvoe the spinner so we don't have two spinners going at same time.
			.next( '.spinner' )
			.remove();
	};

	/**
	 * @summary Action to take when a tab is clicked on.
	 *
	 * These are the "Files & Folders" and "Database" tabs.
	 *
	 * @since 1.6.0
	 */
	self.onClickTab = function() {
		var $dbElements = $( '[data-view-type="db"]' ),
			$fileElements = $( '[data-view-type="file"]' ),
			view;

		$contentWrap.toggleClass( 'html-active tmce-active' );

		view = $contentWrap.hasClass( 'html-active' ) ? 'db' : 'file';

		switch ( view ) {
			case 'file':
				$dbElements.hide();
				$fileElements.show();

				break;
			case 'db':
				BoldGrid.ZipBrowser.onClickViewDb();

				$dbElements.show();
				$fileElements.hide();

				break;
		}
	};

	/**
	 * @summary Action to take when the user clicks "Update".
	 *
	 * What are they updating? Not really the backup itself, but things like the title and
	 * description of the backup.
	 *
	 * @since 1.7.0
	 */
	self.onClickUpdate = function() {
		var request,
			data = {
				action: 'boldgrid_backup_update_archive_details',
				filename: $( '#filename' ).val(),
				security: $( '#bgbkup_archive_details_nonce' ).val(),
				attributes: {
					title: $( 'input#title' ).val(),
					description: $( 'textarea#backup-description' ).val(),
					protect: $( 'select#backup-protect' ).val(),
					encrypt_db: $( 'select#encrypt-db' ).val()
				}
			},
			onFail,
			onSuccess,
			$actions = $( '#major-publishing-actions' ),
			$button = $( this ),
			$spinner = $button.siblings( '.spinner' );

		onSuccess = function( response ) {
			if ( false === response.success ) {
				onFail( response.data );
			} else {

				// Animate things.
				$spinner.removeClass( 'inline' );
				$button.text( adminLang.updated );
				setTimeout( function() {
					$button.text( adminLang.update ).prop( 'disabled', false );
				}, 1000 );
			}
		};

		onFail = function( msg ) {

			// Configure our error message.
			if ( msg === undefined ) {
				msg = adminLang.unknown_error;
			} else if ( 'object' === typeof msg && msg.statusText ) {
				msg = msg.statusText;
			}
			msg = adminLang.failed_to_update + msg;

			// Animate things.
			$spinner.removeClass( 'inline' );
			$button.text( adminLang.update ).prop( 'disabled', false );

			// Add an error message and make it dissmissible.
			$actions.prepend( '<div class="notice notice-error is-dismissible"><p>' + msg + '</p></div>' );
			$( 'body' ).trigger( 'make_notices_dismissible' );
		};

		// Animate things.
		$spinner.addClass( 'inline' );
		$button.text( adminLang.updating ).prop( 'disabled', true );
		$actions.find( '.notice' ).slideUp();

		request = $.post( ajaxurl, data )
			.done( onSuccess )
			.fail( onFail );
	};

	/**
	 * Init.
	 */
	$( function() {
		$body.on( 'click', '.remote-storage a.upload', self.onClickUpload );
		$body.on( 'click', '.remote-storage .download-to-server', self.onClickDownload );
		$body.on( 'click', '#publishing-action button', self.onClickUpdate );
		$editorTabs.on( 'click', self.onClickTab );
		$downloadFirst.on( 'click', self.onClickDownloadFirst );
	} );
};

BoldGrid.ArchiveDetails( jQuery );
