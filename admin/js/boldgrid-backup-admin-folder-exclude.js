/**
 * Folder Exclude
 *
 * @summary JavaScript for handling Folder Exclude settings.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdmin,BoldGridBackupAdminFolderExclude,ajaxurl,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.FolderExclude = function( $ ) {
	'use strict';

	var self = this,
		exclusionList = null,
		filteredList = [],
		lang = BoldGridBackupAdminFolderExclude,
		$container = $( 'div#folder_exclusion' ),
		$excludeFoldersPreview = $container.find( '#exclude_folders_preview' ),
		$inputInclude = $container.find( '[name="folder_exclusion_include"]' ),
		$inputExclude = $container.find( '[name="folder_exclusion_exclude"]' ),
		$status = $container.find( '.status' ),
		$filter = $container.find( '#folder_exclusion_filter' ),
		$ul = $excludeFoldersPreview.find( 'ul' ),
		$type = $container.find( '[name="folder_exclusion_type"]' ),
		$trs = $container.find( '.form-table tbody tr' );

	/**
	 *
	 */
	self.bounceHelp = function() {
		var $icon = $container.find( '.dashicons-editor-help' );

		$icon.bgbuDrawAttention();
	};

	/**
	 *
	 */
	self.isUsingDefaults = function() {
		return (
			$inputInclude.val().trim() === lang.default_include &&
			$inputExclude.val().trim() === lang.default_exclude
		);
	};

	/**
	 *
	 */
	self.onClickConfigure = function() {
		var $a = $( this ),
			$table = $a.closest( 'table' ),
			$icon = $a.siblings( '.dashicons' ),
			status;

		status = self.isUsingDefaults() ? 'yes' : 'warning';

		$icon.toggle();

		$table
			.children( 'tbody' )
			.children( 'tr:not(:first)' )
			.toggle();

		return false;
	};

	/**
	 * @summary Handle the click of the pagination button.s
	 *
	 * @since 1.6.0
	 */
	self.onClickPagination = function() {
		var $a = $( this ),
			$links = $a.closest( '.pagination-links' ),
			currentPage = parseInt( $links.find( '.current-page' ).val() ),
			totalPages = parseInt( $links.find( '.total-pages' ).html() );

		if ( $a.hasClass( 'first' ) ) {
			self.renderList( 1 );
		} else if ( $a.hasClass( 'prev' ) ) {
			self.renderList( currentPage - 1 );
		} else if ( $a.hasClass( 'next' ) ) {
			self.renderList( currentPage + 1 );
		} else if ( $a.hasClass( 'last' ) ) {
			self.renderList( totalPages );
		}

		return false;
	};

	/**
	 * @summary Handle the click of the preview button.
	 *
	 * @since 1.6.0
	 */
	self.onClickPreview = function() {
		var data = {
			action: 'boldgrid_backup_exclude_folders_preview',
			security: $container.find( '[name="folder_exclusion_nonce"]' ).val(),
			include: $inputInclude.val(),
			exclude: $inputExclude.val()
		};

		exclusionList = [];
		$filter.val( '' );

		// Show the status area and indicate we're loading.
		$status.removeClass( 'hidden' ).html( BoldGridBackupAdmin.spinner_loading );

		// Hide the preview area.
		$excludeFoldersPreview.addClass( 'hidden' );

		$.post( ajaxurl, data, function( response ) {
			var success = null;
			if ( response.success !== undefined ) {
				success = response.success;
			}

			if ( success ) {
				$status.empty();
				exclusionList = response.data;
				self.renderList( 1 );
			} else if ( false === success ) {
				$status.html( response.data );
			} else {
				$status.html( 'Unknown error' );
			}
		} ).error( function() {
			$status.html( 'Unknown error' );
		} );

		return false;
	};

	/**
	 * @summary Handle the click of one of the samples.
	 *
	 * @since 1.6.0
	 */
	self.onClickSample = function() {
		var $button = $( this ),
			include = $button.attr( 'data-include' ),
			exclude = $button.attr( 'data-exclude' );

		$inputInclude.val( include ).bgbuDrawAttention();

		$inputExclude.val( exclude ).bgbuDrawAttention();

		self.toggleStatus();

		return false;
	};

	/**
	 * @summary Action to take when backup type has been changed.
	 *
	 * @since 1.6.0
	 *
	 * @param type The type element triggering the 'onChange' listener.
	 */
	self.onChangeType = function( type ) {
		self.toggleConfig( type );
	};

	/**
	 * @summary Process any key downs.
	 *
	 * The preview area's pagination has in input box where you can specify a
	 * page to jump to. When you enter a number and hit enter, the browser is
	 * actually clicking the preview button (which we don't want it to do). This
	 * seems like a stange approach to take, but what we're doing in this
	 * function is listening to all key downs on the page. If you're not in the
	 * .current-page input, then we do nothing. Otherwise, we prevent default
	 * action and do the pagination.
	 *
	 * @since 1.6.0
	 */
	self.onKeyDown = function( e ) {
		var isCurrentPage = $( e.target ).hasClass( 'current-page' ),
			page;

		if ( isCurrentPage && 13 === e.keyCode ) {
			page = $( e.target ).val();
			self.onSubmitPagination( page );
			e.preventDefault();
			return false;
		}

		return true;
	};

	/**
	 * @summary Handle pagination.
	 *
	 * @since 1.6.0
	 *
	 * @param int page The page of results to render.
	 */
	self.onSubmitPagination = function( page ) {
		var page = parseInt( page ),
			totalPages = parseInt( $container.find( '.total-pages' ).html() );

		page = 1 > page || page > totalPages ? 1 : page;

		self.renderList( page );

		return false;
	};

	/**
	 * @summary Render the list of files that will be backed up.
	 *
	 * Please note that there may be two lists involved. exclusionList is the
	 * main list involved, this is the list we get from the server. If the user
	 * has typed into the filter box though, filteredList will be genereated
	 * based on the filtered values.
	 *
	 * @since 1.6.0
	 *
	 * @todo Possibly move this toward a template system. For now, it works.
	 *
	 * @param int page The page of results to render.
	 *                 The way this method was initially written, you could pass in a page number or
	 *                 leave it blank to show the first page. PLEASE NOTE however that this method was
	 *                 also added onload via --- $filter.on( 'keyup', self.renderList ); --- meaning
	 *                 that "page" could also be an event. We can use this to our advantage because on
	 *                 the settings page the filelist filter is shown BOTH within the settings AND when
	 *                 the user clicks "Backup Site Now", and if this method is sent an event, then
	 *                 we can pinpoint which item specfically is being interacted with.
	 */
	self.renderList = function( page ) {
		var startKey,
			perPage = 100,
			lastRecordKey,
			lastAvailableKey = exclusionList.length - 1,
			markup = '',
			x,
			filterVal,
			filteredNoResults,
			file,

			// See docblock definition of "page" var to know more about checking if this is an event.
			isEvent = 'object' === typeof page && page.target !== undefined;

		/*
		 * The filelist preview can be filtered by typing in a search string. If this is an event, the
		 * user is typing in text to use as a filter.
		 *
		 * @todo this is still buggy on the settings page, but this will do for now. Please see the
		 * "page" var's docblock for more info.
		 */
		filterVal = isEvent ? $( page.target ).val() : $filter.val();

		page = isNaN( page ) ? 1 : page;

		// If the user has typed in a filter, then filter our list.
		filteredList = [];
		if ( '' !== filterVal ) {
			exclusionList.forEach( function( file ) {
				if ( -1 !== file.indexOf( filterVal ) ) {
					filteredList.push( file );
				}
			} );

			lastAvailableKey = filteredList.length - 1;
		}

		startKey = page * perPage - perPage;
		lastRecordKey = startKey + perPage - 1;

		/*
		 * Action to take if our last record is [99] and our last available
		 * record is [50].
		 */
		if ( lastRecordKey > lastAvailableKey ) {
			lastRecordKey = lastAvailableKey;
			startKey = lastRecordKey - perPage;
		}

		// Configure our starting record.
		if ( 0 > startKey ) {
			startKey = 0;
		}

		// Generate the markup for our list.
		for ( x = startKey; x <= lastRecordKey; x++ ) {
			file = 0 < filteredList.length ? filteredList[x] : exclusionList[x];

			markup += '<li>' + '<strong>' + ( x + 1 ).toLocaleString( 'en' ) + '</strong>. ' + file + '</li>';
		}
		filteredNoResults = '' !== filterVal && 0 === filteredList.length;
		markup = filteredNoResults ? lang.no_results : markup;

		$ul.html( markup );

		self.renderPagination( page, 100 );
	};

	/**
	 * @summary Render the pagination controls.
	 *
	 * @todo Possibly move this toward a template system. For now, it works.
	 *
	 * @since 1.6.0
	 *
	 * @param int page
	 * @param int perPage
	 */
	self.renderPagination = function( page, perPage ) {
		var markup = '',
			totalCount = '' !== $filter.val() ? filteredList.length : exclusionList.length,
			totalPages = Math.ceil( totalCount / perPage );

		page = 0 === totalCount ? 0 : page;

		markup +=
			'<span class="displaying-num">' +
			'<span>' +
			totalCount.toLocaleString( 'en' ) +
			'</span> ' +
			lang.items +
			'</span>' +
			'<span class="pagination-links">';

		if ( 1 >= page ) {
			markup += '<span class="tablenav-pages-navspan button disabled">«</span> ';
		} else {
			markup += '<a class="first button" href="#"><span>«</span></a> ';
		}

		if ( 1 >= page ) {
			markup += '<span class="tablenav-pages-navspan button disabled">‹</span> ';
		} else {
			markup += '<a class="prev button" href="#"><span>‹</span></a> ';
		}

		markup +=
			'<span class="paging-input">' +
			'<input class="current-page" type="text" value="' +
			page +
			'" size="1">' +
			'<span class="tablenav-paging-text"> ' +
			lang.of +
			' <span class="total-pages">' +
			totalPages +
			'</span></span>' +
			'</span> ';

		if ( page < totalPages ) {
			markup += '<a class="next button" href="#"><span>›</span></a> ';
		} else {
			markup += '<span class="tablenav-pages-navspan button disabled">›</span> ';
		}

		if ( page < totalPages ) {
			markup += '<a class="last button" href="#"><span>»</span></a> ';
		} else {
			markup += '<span class="tablenav-pages-navspan button disabled">»</span> ';
		}

		markup += '</span>';

		$excludeFoldersPreview
			.find( '.tablenav-pages' )
			.html( markup )
			.end()
			.removeClass( 'hidden' );
	};

	/**
	 * @summary Toggle display of everything after the "full" or "custom" options.
	 *
	 * @since 1.6.0
	 *
	 * @param typeInput The type input element clicked in the toggle.
	 */
	self.toggleConfig = function( typeInput ) {
		var type = $( typeInput )
				.filter( ':checked' )
				.val(),
			$miscInfo = $( 'div#folder_misc_info' );

		if ( 'full' === type ) {
			$trs.hide();
			$miscInfo.hide();
		} else if ( 'custom' === type ) {
			$trs.show();
			$miscInfo.show();
		}
	};

	/**
	 * Toggle Status
	 *
	 * @since 1.6.0
	 *
	 * @param eventTarget The target of the triggering event.
	 */
	self.toggleStatus = function( eventTarget ) {
		var parentContainer,
			usingDefaults,
			$yesDefault = $container.find( '.yes-default' ),
			$noDefault = $container.find( '.no-default' );

		if ( eventTarget ) {
			parentContainer = eventTarget.closest( '.form-table' );
			$inputInclude = $( parentContainer ).find( 'input[name=folder_exclusion_include]' );
			$inputExclude = $( parentContainer ).find( 'input[name=folder_exclusion_exclude]' );
		}

		usingDefaults =
			$inputInclude.val() &&
			$inputInclude.val().trim() === lang.default_include &&
			$inputExclude.val().trim() === lang.default_exclude;

		if ( usingDefaults ) {
			$yesDefault.show();
			$noDefault.hide();
		} else {
			$yesDefault.hide();
			$noDefault.show();
		}
	};

	/**
	 * Update Values
	 *
	 * @since 1.6.0
	 *
	 * @param eventTarget The target of the triggering event.
	 * @param $container The set of container divs.
	 */
	self.updateValues = function( eventTarget, $container ) {
		var name = $( eventTarget ).attr( 'name' ),
			value = $( eventTarget ).val();
		if ( 'radio' == $( eventTarget ).attr( 'type' ) ) {
			$container
				.find( 'input[name=' + name + '][value=' + value + ']' )
				.prop( 'checked', $( eventTarget ).prop( 'checked' ) );
		} else {
			$container.find( 'input[name=' + name + ']' ).val( value );
		}
	};

	// Onload event listener.
	$( function() {
		$( 'button#exclude_folders_button' ).on( 'click', self.onClickPreview );

		$( 'body' )
			.on( 'click', '#exclude_folders_preview .pagination-links a', self.onClickPagination )
			.on( 'keydown', self.onKeyDown );

		$( '.folder_exclude_sample' ).on( 'click', self.onClickSample );

		$filter.on( 'keyup', self.renderList );

		$( '#configure_folder_exclude' ).on( 'click', self.onClickConfigure );

		self.toggleStatus();
		$type.each( function() {
			self.toggleConfig( this );
		} );

		$type.on( 'change', function() {
			self.onChangeType( this );
		} );

		$container.find( 'input' ).each( function() {
			$( this ).on( 'input', function() {
				self.updateValues( this, $container );
			} );
		} );

		$inputInclude.each( function() {
			$( this )
				.on( 'input', function() {
					self.toggleStatus( this );
				} )
				.on( 'focusin', function() {
					self.bounceHelp( this );
				} );
		} );

		$inputExclude.each( function() {
			$( this )
				.on( 'input', function() {
					self.toggleStatus( this );
				} )
				.on( 'focusin', function() {
					self.bounceHelp( this );
				} );
		} );
	} );
};

new BoldGrid.FolderExclude( jQuery );
