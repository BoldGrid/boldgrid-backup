/**
 * BoldGrid Backup Table Include.
 *
 * @summary JavaScript for handling Table include settings.
 *
 * @since 1.5.4
 *
 * @param $ The jQuery object.
 */

/* global jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.TableInclude = function( $ ) {
	'use strict';

	var self = this,
		$table = $( '#table_inclusion' ),
		$includeTables   = $table.find( '.include-tables [type="checkbox"]' ),
		$type            = $table.find( '[name="table_inclusion_type"]' ),
		$configContainer = $table.find( '#table_inclusion_config' ),
		// Buttons to include / exclude all.
		$buttonAll       = $table.find( '#include_all_tables, .include-all' ),
		$buttonNone      = $table.find( '#exclude_all_tables' ),
		// Defaults are the status messages indicating default settings used.
		$yesDefault      = $table.find( '.yes-default' ),
		$noDefault       = $table.find( '.no-default' );

	/**
	 * @summary Action to take when the type (full / custom) has been changed.
	 *
	 * @since 1.5.4
	 */
	self.onChangeType = function() {
		self.toggleConfig();
	};

	/**
	 * @summary Toogle all database tables so they are all backed up.
	 *
	 * @since 1.5.4
	 */
	self.toggleAll = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.attr( 'checked', true );
		self.toggleStatus();

		return false;
	};

	/**
	 * @summary Toggle the area that allows you to choose which tables to backup.
	 *
	 * @since 1.5.4
	 */
	self.toggleConfig = function() {
		var type = $type.filter( ':checked' ).val();

		if( 'full' === type ) {
			$configContainer.hide();
		} else {
			$configContainer.show();
		}
	};

	/**
	 * @summary Deselect all tables.
	 *
	 * @since 1.5.4
	 */
	self.toggleNone = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.attr( 'checked', false );
		self.toggleStatus();

		return false;
	};

	/**
	 * @summary Toogle the status that tells the user if they're backing up all tables.
	 *
	 * @since 1.5.4
	 */
	self.toggleStatus = function() {
		var allIncluded = $includeTables.length === $includeTables.filter( ':checked' ).length;

		if( allIncluded ) {
			$yesDefault.show();
			$noDefault.hide();
		} else {
			$yesDefault.hide();
			$noDefault.show();
		}
	};

	$( function() {
		$buttonAll.on( 'click', self.toggleAll );
		$buttonNone.on( 'click', self.toggleNone );

		self.toggleStatus();
		self.toggleConfig();

		$type.on( 'change', self.onChangeType );

		$includeTables.on( 'change', self.toggleStatus );
	} );
};

new BoldGrid.TableInclude( jQuery );