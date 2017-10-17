/**
 * Browser.
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.5.2
 */

/* global ajaxurl,jQuery,boldgrid_backup_zip_browser */

var BoldGrid = BoldGrid || {};

BoldGrid.ZipBrowser = function( $ ) {

	var self = this,
		$listing = $( '#zip_browser .listing' ),
		loading = '<span class="spinner inline"></span> ' + boldgrid_backup_zip_browser.loading + '...';

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
		var $a = $(this),
			$tr = $a.closest( 'tr' ),
			expanded = '1' === $tr.attr( 'data-expanded' ),
			colspan = $tr.find( 'td' ).length,
			$newTr = $( '<tr class="file-actions"><td colspan="' + colspan + '">' + loading + '</td></tr>' ),
			$dummyTr = $( '<tr></tr>' ),
			data = {
				'action': 'boldgrid_backup_browse_archive_file_actions',
				'security' : $( '#_wpnonce' ).val(),
				'filepath' : $tr.attr( 'data-dir' ),
			};

		if( ! expanded ) {
			$newTr
				.css( 'background-color', $tr.css( 'background-color' ) )
				.insertAfter( $tr );
			$dummyTr.insertAfter( $newTr );

			$.post(ajaxurl, data, function(response) {
				$newTr.find( 'td' ).html( response.data );
				$tr.attr( 'data-expanded', '1' );
			});
		} else {
			$tr
				.next( 'tr' ).remove().end()
				.next( 'tr' ).remove().end()
				.attr( 'data-expanded', '0' );
		}
	};

	/**
	 * @summary Handle the click of a folder.
	 *
	 * @since 1.5.3
	 */
	self.onClickFolder = function() {
		var $a = $(this),
			$tr = $a.closest( 'tr' ),
			dir = $tr.attr( 'data-dir' );

		self.renderBrowser( dir );
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
			$container = $( '#zip_browser .breadcrumbs' ),
			html = '<span class="dashicons dashicons-admin-home"></span> <a data-dir-".">' + boldgrid_backup_zip_browser.home + '</a> ',
			dataDir = '';

		dir = typeof dir !== 'undefined' ? dir.trim() : '/';
		split = dir.split( '/' );

		split.forEach( function( element ) {
			if( '' === element || '.' === element ) {
				return;
			}

			dataDir += element + '/';

			html += ' / <a data-dir="' + dataDir + '">' + element + '</a>';
		});

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

		dir = typeof dir !== 'undefined' ? dir : '.';

		data = {
			'action': 'boldgrid_backup_browse_archive',
			'security' : $( '#_wpnonce' ).val(),
			'filepath' : $( '#filepath' ).val(),
			'dir' : dir,
		};

		self.renderBreadcrumbs( dir );

		$listing.find( 'tbody' ).html( '<tr><td colspan="' + colspan + '">' + loading + '</td></tr>'  );

		$.post(ajaxurl, data, function(response) {
			$listing.html( response.data );
		});
	};

	/**
	 * @summary Init.
	 *
	 * @since 1.5.3
	 */
	$( function() {
		self.renderBrowser();

		$( 'body' ).on( 'click', '.listing .folder', self.onClickFolder );
		$( 'body' ).on( 'click', '.listing .file', self.onClickFile );
		$( 'body' ).on( 'click', '.breadcrumbs a', self.onClickBreadcrumb );
	});
};

BoldGrid.ZipBrowser( jQuery );