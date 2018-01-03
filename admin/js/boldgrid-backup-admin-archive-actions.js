/**
 * BoldGrid Backup Admin Archive Actions.
 *
 * @summary JavaScript to handle archive actions.
 *
 * @since 1.5.4
 */

/* global ajaxurl,BoldGridBackupAdminArchiveActions,jQuery */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.ACTIONS = function( $ ) {
	var self = this,
		lang = BoldGridBackupAdminArchiveActions,
		$body = $( 'body' );

	/**
	 * @summary Confirm to delete a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.5.4
	 */
	self.deleteArchiveConfirm = function( e ) {
		var confirmResponse,
			archiveFilename,
			$button = $( this );

		archiveFilename = $button.attr( 'data-filename' );

		confirmResponse = confirm( lang.deleteConfirmText + ' "' + archiveFilename + '"' );

		if( ! confirmResponse ) {
			return false;
		}

		$button.closest( 'form' ).submit();
	};

	/**
	 * @summary Download a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.5.4
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
			alert( "Wordpress filesystem access method is not direct; it is set to '" +
				lang.accessType +
				"'.\n\nYou can download the archive file using another method, such as FTP.\n\n" +
				"The backup archive file path is: " + downloadFilepath
			);

			e.preventDefault();
			return;
		}

		data = {
		    'action' : 'download_archive_file',
		    'download_key' : downloadKey,
		    'download_filename' : downloadFilename,
		    'wpnonce' : lang.archiveNonce
		};

		// Create a hidden form to request the download.
		form = "<form id='download-now-form' class='hidden' method='POST' action='" + ajaxurl + "' target='_blank'>";
		Object.keys(data).forEach(function(key) {
			form += "<input type='hidden' name='" + key + "' value='" + data[key] + "' />";
		});
		form += '</form>';

		$formDom = $( form );

		$formDom
			.appendTo( 'body' )
			.submit();

		e.preventDefault();
	};

	/**
	 * @summary Confirm to restore a selected backup archive file.
	 *
	 * This function was originally in admin-home.js as of 1.0, but moved here
	 * as of 1.5.4.
	 *
	 * @since 1.5.4
	 */
	self.restoreArchiveConfirm = function() {
		var confirmResponse,
			restoreConfirmText,
			$this = $( this ),
			filename = $this.attr( 'data-archive-filename' ),
			data = {
				'action' : 'boldgrid_backup_restore_archive',
				'restore_now' : $this.attr( 'data-restore-now' ),
				'archive_key' : $this.attr( 'data-archive-key' ),
				'archive_filename' : filename,
				'archive_auth' : $this.attr( 'data-nonce' ),
			};

		restoreConfirmText = lang.restoreConfirmText.replace( '%s', filename );
		confirmResponse = confirm( restoreConfirmText );

		if ( true === confirmResponse ) {
			$.post( ajaxurl, data, function( response ) {
				location.reload();
			}).error( function() {
				location.reload();
			});
		}

		return false;
	};

	$( function() {
		$body.on( 'click', '.action-download', self.downloadArchive );
		$body.on( 'click', '.restore-now', self.restoreArchiveConfirm );
		$body.on( 'click', '.delete', self.deleteArchiveConfirm );
	});
};

new BOLDGRID.BACKUP.ACTIONS( jQuery );
