/**
 * BoldGrid Backup Folder Exclude.
 *
 * @summary JavaScript for handling Folder Exclude settings..
 *
 * @since 1.5.4
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdmin,BoldGridBackupAdminFolderExclude,ajaxurl,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.FolderExclude = function( $ ) {
	'use strict';

	var self = this,
		exclusionList = null,
		lang = BoldGridBackupAdminFolderExclude,
		$container = $( '#folder_exclusion' ),
		$excludeFoldersPreview = $container.find( '#exclude_folders_preview' ),
		$inputInclude = $container.find( '[name="folder_exclusion_include"]' ),
		$inputExclude = $container.find( '[name="folder_exclusion_exclude"]' );

	/**
	 * @summary Handle the click of the pagination button.s
	 *
	 * @since 1.5.4
	 */
	self.onClickPagination = function() {
		var $a = $( this ),
			$links = $a.closest( '.pagination-links' ),
			currentPage = parseInt( $links.find( '.current-page' ).val() ),
			totalPages = parseInt( $links.find( '.total-pages' ).html() );

		if( $a.hasClass( 'first' ) ) {
			self.renderList( 1 );
		} else if( $a.hasClass( 'prev' ) ) {
			self.renderList( currentPage - 1 );
		} else if( $a.hasClass( 'next' ) ) {
			self.renderList( currentPage + 1 );
		} else if( $a.hasClass( 'last' ) ) {
			self.renderList( totalPages );
		}

		return false;
	};

	/**
	 * @summary Handle the click of the preview button.
	 *
	 * @since 1.5.4
	 */
	self.onClickPreview = function() {
		var data = {
				'action': 'boldgrid_backup_exclude_folders_preview',
				'security' : $container.find( '[name="folder_exclusion_nonce"]' ).val(),
				'include' : $inputInclude.val(),
				'exclude' : $inputExclude.val(),
			};

		$excludeFoldersPreview
			.removeClass( 'hidden' )
			// Show the status.
			.find( '.status' ).removeClass( 'hidden' ).html( BoldGridBackupAdmin.spinner_loading ).end()
			.find( 'ul' ).empty().addClass( 'hidden' ).end()
			.find( '.tablenav-pages' ).empty();

		$.post( ajaxurl, data, function( response ) {
			var success = null;
			if( response.success !== undefined ) {
				success = response.success;
			}

			if( success ) {
				$excludeFoldersPreview.find( '.status' ).empty();
				exclusionList = response.data;
				self.renderList( 1 );
			} else if( false === success ) {
				$excludeFoldersPreview.find( '.status' ).html( response.data );
			} else {
				$excludeFoldersPreview.find( '.status' ).html( 'Unknown error' );
			}
		} ).error( function() {
			$excludeFoldersPreview.find( '.status' ).html( 'Unknown error' );
		} );

		return false;
	};

	/**
	 * @summary Handle the click of one of the samples.
	 *
	 * @since 1.5.4
	 */
	self.onClickSample = function() {
		var $button = $( this ),
			include = $button.attr( 'data-include' ),
			exclude = $button.attr( 'data-exclude' );

		$inputInclude.val( include );
		$inputExclude.val( exclude );

		return false;
	};

	/**
	 * @summary Process any key downs.
	 *
	 * The preview area's pagination has in input box where you can specify a
	 * page to jump to. When you enter a number and hit enter, it the browser is
	 * actually clicking the preview button (which we don't want it to do). This
	 * seems like a stange approach to take, but what we're doing in this
	 * function is listening to all key downs on the page. If you're not in the
	 * .current-page input, then we do nothing. Otherwise, we prevent default
	 * action and do the pagination.
	 *
	 * @since 1.5.4
	 */
	self.onKeyDown = function( e ) {
		var isCurrentPage = $( e.target ).hasClass( 'current-page' );

		if( isCurrentPage && 13 === e.keyCode ) {
			self.onSubmitPagination();
			e.preventDefault();
			return false;
		}

		return true;
	};

	/**
	 * @summary Handle pagination.
	 *
	 * @since 1.5.4
	 */
	self.onSubmitPagination = function() {
		var page = parseInt( $excludeFoldersPreview.find( '.current-page' ).val() );

		page = page < 1 ? 1 : page;

		self.renderList( page );

		return false;
	};

	/**
	 * @summary Render the list of files that will be backed up.
	 *
	 * @since 1.5.4
	 *
	 * @todo Possibly move this toward a template system. For now, it works.
	 *
	 * @param int page The page of results to render.
	 */
	self.renderList = function( page ) {
		var startKey,
			// The number of results per page.
			perPage = 100,
			lastRecordKey,
			lastAvailableKey = exclusionList.length - 1,
			markup = '',
			x;

		startKey = page * perPage - perPage;
		lastRecordKey = startKey + perPage - 1;

		/*
		 * Action to take if our last record is [99] and our last available
		 * record is [50].
		 */
		if( lastRecordKey > lastAvailableKey ) {
			lastRecordKey = lastAvailableKey;
			startKey = lastRecordKey - perPage;
		}

		// Configure our starting record.
		if( startKey < 0 ) {
			startKey = 0;
		}

		$excludeFoldersPreview.find( '.tablenav' ).removeClass( 'hidden' );

		for( x = startKey; x <= lastRecordKey; x++ ) {
			markup += '<li>' +
				'<strong>' + ( x + 1 ).toLocaleString('en') + '</strong>. ' +
				exclusionList[x] + '</li>';
		}
		$excludeFoldersPreview.find( 'ul' ).html( markup ).removeClass( 'hidden' );

		self.renderPagination( page, 100 );
	};

	/**
	 * @summary Render the pagination controls.
	 *
	 * @todo Possibly move this toward a template system. For now, it works.
	 *
	 * @since 1.5.4
	 *
	 * @param int page
	 * @param int perPage
	 */
	self.renderPagination = function( page, perPage ) {
		var markup = '',
			totalCount = exclusionList.length,
			totalPages = Math.ceil( totalCount / perPage );

		markup += '<span class="displaying-num">' +
				'<span>' + totalCount.toLocaleString( 'en' ) + '</span> ' + lang.items +
			'</span>' +
			'<span class="pagination-links">';

		if( 1 === page ) {
			markup += '<span class="tablenav-pages-navspan">«</span> ';
		} else {
			markup += '<a class="first" href="#"><span>«</span></a> ';
		}

		if( 1 >= page ) {
			markup += '<span class="tablenav-pages-navspan">‹</span> ';
		} else {
			markup += '<a class="prev" href="#"><span>‹</span></a> ';
		}

		markup +=
			'<span class="paging-input">' +
				'<input class="current-page" type="text" value="' + page + '" size="1">' +
				'<span class="tablenav-paging-text"> ' + lang.of + ' <span class="total-pages">' + totalPages + '</span></span>' +
			'</span> ';

		if( page < totalPages ) {
			markup += '<a class="next" href="#"><span>›</span></a> ';
		} else {
			markup += '<span class="tablenav-pages-navspan">›</span> ';
		}

		if( page < totalPages ) {
			markup += '<a class="last" href="#"><span>»</span></a> ';
		} else {
			markup += '<span class="tablenav-pages-navspan">»</span> ';
		}

		markup += '</span>';

		$excludeFoldersPreview.find( '.tablenav-pages' ).html( markup );
	};

	// Onload event listener.
	$( function() {
		$( '#exclude_folders_button' ).on( 'click', self.onClickPreview );

		$( 'body' ).on( 'click', '#exclude_folders_preview .pagination-links a', self.onClickPagination );

		$( 'body').keydown( self.onKeyDown );

		$( '.folder_exclude_sample' ).on( 'click', self.onClickSample );
	} );
};

BoldGrid.FolderExclude( jQuery );