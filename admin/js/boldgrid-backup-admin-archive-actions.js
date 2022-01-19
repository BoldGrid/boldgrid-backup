/**
 * Archive Actions
 *
 * @summary JavaScript to handle archive actions.
 *
 * @since 1.6.0
 */

/* global ajaxurl,BoldGridBackupAdminArchiveActions,jQuery */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.ACTIONS = function( $ ) {
	var $wpbody,
		self = this,
		lang = BoldGridBackupAdminArchiveActions;

	/**
	 * @summary Confirm to delete a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.6.0
	 */
	self.onClickDelete = function( e ) {
		var confirmResponse,
			$form = $( this ).closest( 'form' ),
			archiveFilename = $form.find( '[name="archive_filename"]' ).val(),
			$spinner = $form.find( '.spinner' );

		confirmResponse = confirm( lang.deleteConfirmText + ' "' + archiveFilename + '"' );

		if ( ! confirmResponse ) {
			return false;
		}

		$spinner.addClass( 'inline' );
		$wpbody.bgbuDisableActions();

		$form.submit();
		return false;
	};

	/**
	 * @summary Download a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.6.0
	 */
	self.downloadArchive = function( e ) {
		var downloadKey,
			downloadFilename,
			downloadFilepath,
			data,
			form,
			$formDom,
			$this = $( this );

		downloadKey = $this.data( 'key' );
		downloadFilename = $this.data( 'filename' );
		downloadFilepath = $this.data( 'filepath' );

		// If the wp_filesystem method is not "direct", then show a message and return.
		if ( 'direct' !== lang.accessType ) {
			alert(
				'Wordpress filesystem access method is not direct; it is set to \'' +
					lang.accessType +
					'\'.\n\nYou can download the archive file using another method, such as FTP.\n\n' +
					'The backup archive file path is: ' +
					downloadFilepath
			);

			e.preventDefault();
			return;
		}

		data = {
			action: 'download_archive_file',
			download_key: downloadKey,
			download_filename: downloadFilename,
			wpnonce: lang.archiveNonce
		};

		// Create a hidden form to request the download.
		form =
			'<form id=\'download-now-form\' class=\'hidden\' method=\'POST\' action=\'' +
			ajaxurl +
			'\' target=\'_blank\'>';
		Object.keys( data ).forEach( function( key ) {
			form += '<input type=\'hidden\' name=\'' + key + '\' value=\'' + data[key] + '\' />';
		} );
		form += '</form>';

		$formDom = $( form );

		$formDom.appendTo( 'body' ).submit();

		e.preventDefault();
	};

	/**
	 * @summary Confirm to restore a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.6.0
	 */
	self.restoreArchiveConfirm = function() {
		var $this = $( this ),
			confirmResponse,
			filename = $this.data( 'archive-filename' ),
			restoreConfirmText = lang.restoreConfirmText.replace( '%s', filename ),
			data = {
				action: 'boldgrid_backup_restore_archive',
				restore_now: $this.data( 'restore-now' ),
				archive_key: $this.data( 'archive-key' ),
				archive_filename: filename,
				archive_auth: $this.data( 'nonce' )
			},
			$spinner = $this.next( '.spinner' ),
			encryptDb = $wpbody.find( '#bgb-details-encrypt_db' ).data( 'value' ),
			tokenMatch = $wpbody.find( '#bgbp-token-match' ).data( 'value' );

		if ( 'Y' === encryptDb && 'Y' !== tokenMatch ) {
			restoreConfirmText = restoreConfirmText + '\n\n' + lang.tokenMismatchText;
		}

		confirmResponse = confirm( restoreConfirmText );

		if ( true === confirmResponse ) {
			$spinner.addClass( 'inline' );
			$wpbody.bgbuDisableActions();

			$.post( ajaxurl, data, function( response ) {
				var redirectUrl =
					response.data !== undefined && response.data.redirect_url !== undefined ?
						response.data.redirect_url :
						false;

				if ( redirectUrl ) {
					window.location.href = redirectUrl;
				} else {
					location.reload();
				}
			} ).error( function() {
				location.reload();
			} );
		}

		return false;
	};

	/**
	 * @summary Get a download link for a selected backup archive file.
	 *
	 * @since 1.7.0
	 */
	self.getDownloadLink = function( e ) {
		var $this = $( this ),
			data = {
				action: 'boldgrid_backup_generate_download_link',
				archive_filename: $this.attr( 'data-filename' ),
				archive_auth: $this.attr( 'data-nonce' )
			},
			$spinner = $this.next(),
			$downloadLink = $( '#download-link-copy' );

		e.preventDefault();

		$this.attr( 'disabled', 'disabled' );

		$spinner.addClass( 'inline' );

		$.post( ajaxurl, data, function( response ) {
			var $copyLink;

			if ( response.data !== undefined && response.data.download_url !== undefined ) {
				$downloadLink
					.removeClass( 'notice-error' )
					.addClass( 'notice-info' )
					.html( response.data.download_url + ' ' );

				$copyLink = $(
					'<button class="button" id="download-copy-button"' +
						' data-clipboard-text="' +
						response.data.download_url +
						'"> ' +
						lang.copyText +
						' <span class="dashicons dashicons-admin-links"></span></button>'
				);
				$downloadLink.append( $copyLink );

				$downloadLink.wrapInner( '<p></p>' );

				$downloadLink.append(
					'<p>' +
						lang.expiresText +
						' ' +
						response.data.expires_when +
						'</p><p>' +
						lang.linkDisclaimerText +
						'</p>'
				);

				new ClipboardJS( $copyLink[0] );
			} else if ( response.data !== undefined && response.data.error !== undefined ) {
				$downloadLink
					.removeClass( 'notice-info' )
					.addClass( 'notice-error' )
					.html( response.data.error );
			} else {
				$downloadLink
					.removeClass( 'notice-info' )
					.addClass( 'notice-error' )
					.html( lang.linkErrorText );
			}
		} )
			.error( function() {
				$downloadLink.html( lang.unknownErrorText );
			} )
			.always( function() {
				$downloadLink.show();
				$spinner.removeClass( 'inline' );
				$this.prop( 'disabled', false );
			} );
	};

	/**
	 * @summary Update the download link copy button after clicking, and then reset after 3 seconds.
	 *
	 * @since 1.7.0
	 */
	self.updateCopyText = function( e ) {
		var $this = $( this ),
			oldHtml = $this.html();

		e.preventDefault();

		$this.attr( 'disabled', 'disabled' );
		$this.html( lang.copiedText );

		setTimeout( function() {
			$this.html( oldHtml );
			$this.prop( 'disabled', false );
		}, 3000 );
	};

	$( function() {
		$wpbody = $( 'body #wpbody' );

		$wpbody.on( 'click', '.action-download', self.downloadArchive );
		$wpbody.on( 'click', '.restore-now', self.restoreArchiveConfirm );
		$wpbody.on( 'click', '#delete-action a', self.onClickDelete );
		$wpbody.on( 'click', '#download-link-button', self.getDownloadLink );
		$wpbody.on( 'click', '#download-copy-button', self.updateCopyText );
	} );
};

new BOLDGRID.BACKUP.ACTIONS( jQuery );
