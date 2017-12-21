/**
 * BoldGrid Backup Table Include.
 *
 * @summary JavaScript for handling Table include settings.
 *
 * @since 1.5.4
 *
 * @param $ The jQuery object.
 */

/* global BoldGridBackupAdmin,BoldGridBackupAdminFolderExclude,ajaxurl,jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.TableInclude = function( $ ) {
	'use strict';

	var self = this,
		$table = $( '#table_inclusion' ),
		$includeTables = $table.find( '.include-tables [type="checkbox"]' ),
		$buttonAll = $table.find( '#include_all_tables' ),
		$buttonNone = $table.find( '#exclude_all_tables' ),
		$configure = $table.find( '#configure_include_tables' ),
		$tablesTr = $table.find( '#tables_to_include' ),
		// Defaults are the status messages indicating default settings used.
		$yesDefault = $table.find( '.yes-default' ),
		$noDefault = $table.find( '.no-default' ),
		$statusIcon = $configure.parent().children( '.dashicons' );

	/**
	 *
	 */
	self.toggleAll = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.attr( 'checked', true );
		self.toggleStatus();
		return false;
	}

	/**
	 *
	 */
	self.toggleNone = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.attr( 'checked', false );
		self.toggleStatus();
		return false;
	}

	/**
	 *
	 */
	self.toggleStatus = function() {

		console.log( 'here in talbe include toggle status');

		var allIncluded = $includeTables.length === $includeTables.filter( ':checked' ).length;

		$statusIcon.removeClass( 'dashicons-warning dashicons-yes green yellow' );

		if( allIncluded ) {
			$yesDefault.show();
			$noDefault.hide();

			$statusIcon.bgbuSetStatus( 'yes' );
		} else {
			$yesDefault.hide();
			$noDefault.show();

			$statusIcon.bgbuSetStatus( 'warning' );
		}
	}

	$( function() {
		$buttonAll.on( 'click', self.toggleAll );

		$( '.include-all' ).on( 'click', self.toggleAll );

		$buttonNone.on( 'click', self.toggleNone );

		$configure.on( 'click', function() {
			$configure.siblings( '.dashicons' ).toggle();
			$tablesTr.toggle();
			return false;
		});

		self.toggleStatus();

		$includeTables.on( 'change', self.toggleStatus );
	} );
};

new BoldGrid.TableInclude( jQuery );