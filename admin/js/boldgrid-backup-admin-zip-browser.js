/**
 * Browser.
 *
 * @summary JS for all admin backup pages.
 *
 * @since 1.5.2
 */

/* global ajaxurl */

var BoldGrid = BoldGrid || {};

BoldGrid.ZipBrowser = function( $ ) {

	var self = this,
		$listing = $( '#zip_browser .listing' );

	/**
	 *
	 */
	self.onClickBreadcrumb = function() {
		var dir = $( this ).attr( 'data-dir' );

		self.renderBrowser( dir );
	}

	/**
	 *
	 */
	self.onClickFile = function() {
		var $a = $(this),
			$tr = $a.closest( 'tr' ),
			expanded = '1' === $tr.attr( 'data-expanded' ),
			colspan = $tr.find( 'td' ).length,
			$newTr = $( '<tr class="file-actions"><td colspan="' + colspan + '">With BoldGrid Backup Premium, you can view and restore files from here.</td></tr>' ),
			$dummyTr = $( '<tr></tr>' );

		if( ! expanded ) {
			$newTr.insertAfter( $tr );
			$dummyTr.insertAfter( $newTr );
			$newTr.css( 'background-color', $tr.css( 'background-color' ) );
			$tr.attr( 'data-expanded', '1' );
		} else {
			$tr
				.next( 'tr' ).remove().end()
				.next( 'tr' ).remove().end()
				.attr( 'data-expanded', '0' );
		}


	}

	/**
	 *
	 */
	self.onClickFolder = function() {
		var $a = $(this),
			$tr = $a.closest( 'tr' ),
			dir = $tr.attr( 'data-dir' );

		self.renderBrowser( dir );
	}

	/**
	 *
	 */
	self.renderBreadcrumbs = function( dir ) {
		var dir = typeof dir !== 'undefined' ? dir.trim() : '/',
			split = dir.split( '/' ),
			$container = $( '#zip_browser .breadcrumbs' ),
			html = '<span class="dashicons dashicons-admin-home"></span> <a data-dir-".">' + boldgrid_backup_zip_browser.home + '</a> ',
			dataDir = '';

		split.forEach( function( element ) {
			if( '' === element || '.' === element ) {
				return;
			}

			dataDir += element + '/';

			html += ' / <a data-dir="' + dataDir + '">' + element + '</a>';
		});

		$container.html( html );
	}

	/**
	 * Render the archive browser.
	 */
	self.renderBrowser = function( dir ) {
		var dir = typeof dir !== 'undefined' ? dir : '.',
			data = {
				'action': 'boldgrid_backup_browse_archive',
				'security' : $( '#_wpnonce' ).val(),
				'filepath' : $( '#filepath' ).val(),
				'dir' : dir,
			},
			colspan = $listing.find( 'thead th' ).length,
			loading = '<span class="spinner inline"></span> ' + boldgrid_backup_zip_browser.loading + '...';

		self.renderBreadcrumbs( dir );

		$listing.find( 'tbody' ).html( '<tr><td colspan="' + colspan + '">' + loading + '</td></tr>'  );

		jQuery.post(ajaxurl, data, function(response) {
			$listing.html( response.data );
		});
	}

	/**
	 * Init.
	 */
	$( function() {
		self.renderBrowser();

		$( 'body' ).on( 'click', '.listing .folder', self.onClickFolder );
		$( 'body' ).on( 'click', '.listing .file', self.onClickFile );
		$( 'body' ).on( 'click', '.breadcrumbs a', self.onClickBreadcrumb );
	});
};

BoldGrid.ZipBrowser( jQuery );