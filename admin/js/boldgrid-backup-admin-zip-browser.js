/**
 * Browser
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.5.2
 */

/* global ajaxurl,jQuery,boldgrid_backup_zip_browser */

var BoldGrid = BoldGrid || {};

BoldGrid.ZipBrowser = function( $ ) {
	var self = this,
		$zipBrowser = $( '#zip_browser' ),
		$listing = $zipBrowser.find( '.listing' ),
		spinner = '<span class="spinner inline"></span> ',
		loading = spinner + boldgrid_backup_zip_browser.loading + '...';

	/**
	 * @summary Handle the click of a breadcrumb.
	 *
	 * @since 1.5.3
	 */
	self.onClickBreadcrumb = function() {
		var dir = $( this ).attr( 'data-dir' );

		self.renderBrowser( dir );
	};

	/**
	 * @summary Handle the click of a file.
	 *
	 * @since 1.5.3
	 */
	self.onClickFile = function() {
		var $a = $( this ),
			$tr = $a.closest( 'tr' ),
			expanded = '1' === $tr.attr( 'data-expanded' ),
			colspan = $tr.find( 'td' ).length,
			$newTr = $(
				'<tr class="file-actions"><td colspan="' + colspan + '">' + loading + '</td></tr>'
			),
			$dummyTr = $( '<tr></tr>' ),
			data = {
				action: 'boldgrid_backup_browse_archive_file_actions',
				security: $( '#bgbkup_archive_details_nonce' ).val(),
				filename: $( '#filename' ).val(),
				file: $tr.attr( 'data-dir' )
			};

		if ( ! expanded ) {
			$newTr.css( 'background-color', $tr.css( 'background-color' ) ).insertAfter( $tr );
			$dummyTr.insertAfter( $newTr );

			$tr.attr( 'data-expanded', '1' );

			$.post( ajaxurl, data, function( response ) {
				if ( response.success !== undefined ) {
					$newTr.find( 'td' ).html( response.data );
				} else {
					$newTr.find( 'td' ).html( boldgrid_backup_zip_browser.unknownError );
				}
			} ).error( function() {
				$newTr.find( 'td' ).html( boldgrid_backup_zip_browser.unknownError );
			} );
		} else {
			$tr
				.next( 'tr' )
				.remove()
				.end()
				.next( 'tr' )
				.remove()
				.end()
				.attr( 'data-expanded', '0' );
		}
	};

	/**
	 * @summary Handle the click of a folder.
	 *
	 * @since 1.5.3
	 */
	self.onClickFolder = function() {
		var $a = $( this ),
			$tr = $a.closest( 'tr' ),
			dir = $tr.attr( 'data-dir' );

		self.renderBrowser( dir );
	};

	/**
	 * @summary Handle the click of the "load archive browser" button.
	 *
	 * @since 1.6.0
	 */
	self.onClickLoadBrowser = function() {
		$( this ).attr( 'disabled', 'disabled' );

		self.renderBrowser( '.' );
	};

	/**
	 * @summary Handle the click of the "restore this database" button.
	 *
	 * @since 1.6.0
	 */
	self.onClickRestoreDb = function() {
		var $a = $( this ),
			$p = $a.closest( 'p' ),
			$spinner = $a.next(),
			data = {
				action: 'boldgrid_backup_browse_archive_restore_db',
				security: $( '#bgbkup_archive_details_nonce' ).val(),
				filename: $( '#filename' ).val(),
				file: $a.attr( 'data-file' )
			},
			confirmation,
			status = '<span class="spinner inline"></span> Restoring';

		confirmation = confirm( boldgrid_backup_zip_browser.confirmDbRestore );

		if ( ! confirmation ) {
			return false;
		}

		$p.empty().html( status );
		$a.attr( 'disabled', 'disabled' );
		$spinner.addClass( 'inline middle' );

		$.post( ajaxurl, data, function( response ) {
			location.reload();
		} ).error( function() {
			location.reload();
		} );

		return false;
	};

	/**
	 * @summary Handle the postbox-like toggle on thead th's that hide a table.
	 *
	 * @since 1.6.0
	 */
	self.onClickToggle = function() {
		var $toggle = $( this ),
			$tbody = $toggle.closest( 'table' ).find( 'tbody' );

		$toggle.toggleClass( 'closed' );

		if ( $toggle.hasClass( 'closed' ) ) {
			$tbody.hide();
		} else {
			$tbody.show();
		}
	};

	/**
	 * @summary Handle the click of the "View details" button for a database.
	 *
	 * @since 1.6.0
	 */
	self.onClickViewDb = function() {
		var $a = $( this ),
			data = {
				action: 'boldgrid_backup_browse_archive_view_db',
				security: $( '#bgbkup_archive_details_nonce' ).val(),
				filename: $( '#filename' ).val(),
				file: $( '#dump_filename' ).val()
			},
			$details = $( '#db_details' ),
			errorCallback;

		// Only render the view once.
		if ( 'true' === $details.attr( 'data-rendered' ) ) {
			return;
		}
		$details.attr( 'data-rendered', 'true' );

		$a.attr( 'disabled', 'disabled' );

		$details.html( loading );

		errorCallback = function() {
			$details.html( boldgrid_backup_zip_browser.unknownErrorNotice );
		};

		$.post( ajaxurl, data, function( response ) {
			var success = response.success !== undefined && true === response.success,
				fail = response.success !== undefined && false === response.success;

			if ( success || fail ) {
				$details.html( response.data );
			} else {
				errorCallback();
			}
		} ).error( errorCallback );
	};

	/**
	 * @summary Render breadcrumbs.
	 *
	 * @since 1.5.3
	 *
	 * @param string dir
	 */
	self.renderBreadcrumbs = function( dir ) {
		var split,
			$container = $zipBrowser.find( '.breadcrumbs' ),
			html =
				'<span class="dashicons dashicons-admin-home"></span> <a data-dir-".">' +
				boldgrid_backup_zip_browser.home +
				'</a> ',
			dataDir = '';

		dir = 'undefined' !== typeof dir ? dir.trim() : '/';
		split = dir.split( '/' );

		split.forEach( function( element ) {
			if ( '' === element || '.' === element ) {
				return;
			}

			dataDir += element + '/';

			html += ' / <a data-dir="' + dataDir + '">' + element + '</a>';
		} );

		$container.html( html );
	};

	/**
	 * @summary Render the archive browser.
	 *
	 * @since 1.5.3
	 */
	self.renderBrowser = function( dir ) {
		var data,
			colspan = $listing.find( 'thead th' ).length;

		dir = 'undefined' !== typeof dir ? dir : '.';

		$zipBrowser.show();

		data = {
			action: 'boldgrid_backup_browse_archive',
			security: $( '#bgbkup_archive_details_nonce' ).val(),
			filename: $( '#filename' ).val(),
			dir: dir
		};

		self.renderBreadcrumbs( dir );

		$listing.find( 'tbody' ).html( '<tr><td colspan="' + colspan + '">' + loading + '</td></tr>' );

		$.post( ajaxurl, data, function( response ) {
			if ( response.success !== undefined ) {
				$listing.html( response.data );
			} else {
				$listing.html( boldgrid_backup_zip_browser.unknownBrowseError );
			}
		} ).error( function() {
			$listing.html( boldgrid_backup_zip_browser.unknownBrowseError );
		} );
	};

	/**
	 * @summary Init.
	 *
	 * @since 1.5.3
	 */
	$( function() {
		$( 'body' ).on( 'click', '.listing .folder', self.onClickFolder );
		$( 'body' ).on( 'click', '.listing .file', self.onClickFile );
		$( 'body' ).on( 'click', '.breadcrumbs a', self.onClickBreadcrumb );
		$( 'body' ).on( 'click', '.restore-db', self.onClickRestoreDb );
		$( 'body' ).on( 'click', '.view-db', self.onClickViewDb );
		$( 'body' ).on( 'click', 'th .toggle-indicator', self.onClickToggle );
		$( 'body' ).on( 'click', '.load-browser', self.onClickLoadBrowser );

		self.renderBrowser( '.' );
	} );
};

BoldGrid.ZipBrowser = new BoldGrid.ZipBrowser( jQuery );
