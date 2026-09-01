/**
 * Table include
 *
 * @summary JavaScript for handling table include settings.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global jQuery */

var BoldGrid = BoldGrid || {};

BoldGrid.TableInclude = function( $ ) {
	'use strict';

	var self = this,
		$container = $( 'div#table_inclusion' ),
		$includeTables = $container.find( '.include-tables [type="checkbox"]' ),
		$type = $container.find( '[name="table_inclusion_type"]' ),
		$configContainer = $container.find( '#table_inclusion_config' ),

		// Buttons to include / exclude all.
		$buttonAll = $container.find( '#include_all_tables, .include-all' ),
		$buttonNone = $container.find( '#exclude_all_tables' ),

		// Defaults are the status messages indicating default settings used.
		$yesDefault = $container.find( '.yes-default' ),
		$noDefault = $container.find( '.no-default' );

	/**
	 * @summary Action to take when the type (full / custom) has been changed.
	 *
	 * @since 1.6.0
	 *
	 * @param typeInput The type input element clicked in the toggle.
	 */
	self.onChangeType = function( typeInput ) {
		self.toggleConfig( typeInput );
	};

	/**
	 * @summary Toogle all database tables so they are all backed up.
	 *
	 * @since 1.6.0
	 */
	self.toggleAll = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.prop( 'checked', true );
		self.toggleStatus();

		return false;
	};

	/**
	 * @summary Toggle the area that allows you to choose which tables to backup.
	 *
	 * @since 1.6.0
	 *
	 * @param typeInput The type input element clicked in the toggle.
	 */
	self.toggleConfig = function( typeInput ) {
		var type = $( typeInput )
			.filter( ':checked' )
			.val();

		if ( 'full' === type ) {
			$configContainer.hide();
		} else if ( 'custom' === type ) {
			$configContainer.show();
		}
	};

	/**
	 * @summary Deselect all tables.
	 *
	 * @since 1.6.0
	 */
	self.toggleNone = function() {
		$includeTables.bgbuDrawAttention();
		$includeTables.prop( 'checked', false );
		self.toggleStatus();

		return false;
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
			value = $( eventTarget ).val(),
			type = $( eventTarget ).attr( 'type' );
		if ( 'radio' == type || 'checkbox' == type ) {
			$container
				.find( 'input[name="' + name + '"][value="' + value + '"]' )
				.prop( 'checked', $( eventTarget ).prop( 'checked' ) );
		} else {
			$container.find( 'input[name=' + name + ']' ).val( value );
		}
	};

	/**
	 * @summary Toogle the status that tells the user if they're backing up all tables.
	 *
	 * @since 1.6.0
	 */
	self.toggleStatus = function() {
		var allIncluded = $includeTables.length === $includeTables.filter( ':checked' ).length;

		if ( allIncluded ) {
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

		$includeTables.on( 'change', self.toggleStatus );
	} );
};

new BoldGrid.TableInclude( jQuery );
