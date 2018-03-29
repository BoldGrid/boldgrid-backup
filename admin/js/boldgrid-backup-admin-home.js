/**
 * BoldGrid Backup admin home page.
 *
 * @summary JavaScript for the BoldGrid Backup admin home page.
 *
 * @since 1.0
 *
 * @param $ The jQuery object.
 */

/* global jQuery */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

BOLDGRID.BACKUP.HOME = function( $ ) {
	'use strict';

	// General Variables.
	var self = this,
		$fileInput = $( 'input:file' ),
		$mineCount = $( '.mine' ),
		$mineCountHelp = $( '.subsubsub' ).find( '.dashicons' );

	// Onload event listener.
	$( function() {

		// On click action for the Upload button.
		$( '#upload-archive-form' )
			.find( '.button' )
				.on( 'click', self.uploadButtonClicked );

		$( '.page-title-action.add-new' ).on( 'click', function() {
			$( '#add_new' ).toggle();
		} );

		$fileInput
			.parent()
				.find( 'input:submit' )
					.attr( 'disabled', true );

		// On click action for toggling a help section.
		$( '.dashicons-editor-help' ).on( 'click', self.toggleHelp );

		// Remove restoration notice.
		self.hideRestoreNotice();

		$fileInput.on( 'change', self.onChangeInput );

		$mineCount
			.on( 'click', self.onClickCount )
			.on( 'mouseover', function() {
				$mineCountHelp.bgbuDrawAttention();
			} );
	} );

	/**
	 * Hide the restore archive notice and enable action buttons.
	 *
	 * @since 1.2.3
	 */
	self.hideRestoreNotice = function() {

		// Enable the Backup Site Now and all Restore and Delete buttons.
		$( '#backup-site-now, .action-restore, .action-delete' )
			.prop( 'disabled', false )
			.css( 'pointer-events', '' );

		// Hide the restore notice.
		$( '.restoration-in-progress' ).hide();
	};

	/**
	 * @summary Take action when a backup file is selected for upload.
	 *
	 * This includes checking the filesize and showing applicable warnings.
	 *
	 * @since 1.5.2
	 */
	self.onChangeInput = function() {
		var $badExtension = $( '#bad_extension' ),
			$fileSizeWarning = $( '[data-id="upload-backup"]:not(span)' ),
			$fileTooLarge = $( '#file_too_large' ),
			$submit = $( 'input:submit' ),
			extension,
			isBadExtension,
			isTooBig,
			maxSize = parseInt( $( '[name="MAX_FILE_SIZE"]' ).val() ),
			name,
			size;

		if ( ! $fileInput.val() ) {
			$fileSizeWarning.slideUp();
			$fileTooLarge.slideUp();
			$badExtension.slideUp();
			$submit.attr( 'disabled', true );
			return;
		}

		name = $fileInput[0].files[0].name;
		size = $fileInput[0].files[0].size;
		extension = name.substr( ( name.lastIndexOf( '.' ) + 1 ) );

		isTooBig = 0 > maxSize - size;
		isBadExtension = 'zip' !== extension;

		if ( isBadExtension ) {
			$badExtension.slideDown();
		} else {
			$badExtension.slideUp();
		}

		if ( isTooBig ) {
			$fileSizeWarning.slideDown();
			$fileTooLarge.slideDown();
		} else {
			$fileSizeWarning.slideUp();
			$fileTooLarge.slideUp();
		}

		if ( isTooBig || isBadExtension ) {
			$submit.attr( 'disabled', true );
		} else {
			$submit.attr( 'disabled', false );
		}
	};

	/**
	 * @summary Action to take when a user clicks on a mine count.
	 *
	 * @since 1.5.4
	 */
	self.onClickCount = function() {
		var $anchor = $( this ),
			$p = $anchor.closest( 'p' ),
			$trs = $( '#backup-archive-list-body tr' ),

			// Type is either on_web_server or on_remote_server
			type = $anchor.attr( 'data-count-type' );

		// Highlight the count we just clicked on.
		$p.find( '.mine' ).removeClass( 'current' );
		$anchor.addClass( 'current' );

		if ( 'all' === type ) {
			$trs.show();
			return false;
		}

		$trs.each( function( index ) {
			var $tr = $( this ),
				$matches = $tr.find( '[data-' + type + '="true"]' );

			if ( 0 === $matches.length ) {
				$tr.hide();
			} else {
				$tr.show();
				$matches.bgbuDrawAttention();
			}
		} );

		return false;
	};

	/**
	 * Confirm to delete a selected backup archive file.
	 *
	 * @since 1.2.2
	 */
	self.uploadButtonClicked = function() {

		// Declare variables.
		var $this = $( this );

        // Disable the Upload button.
		$this
			.css( 'pointer-events', 'none' );

		// Show the spinner.
		$this
			.parent()
				.find( '.spinner' )
					.addClass( 'is-active' )
					.css( 'display', 'inline-block' );
	};

	/**
	 * Toggle a help section.
	 *
	 * @since 1.2.2
	 */
	self.toggleHelp = function() {
		$( this ).next( '.help' ).toggle();
	};
};

BOLDGRID.BACKUP.HOME( jQuery );
