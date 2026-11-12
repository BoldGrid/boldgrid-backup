<?php
/**
 * boldgrid_reseller is read as an array by BoldGrid Library.
 *
 * @package Boldgrid_Backup
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * Return reseller settings as an array.
 *
 * get_option() returns false when the option has never been saved. Library
 * menu code indexes that value, and PHP 8 warns.
 *
 * @since 1.17.5
 *
 * @param mixed $value Option value.
 * @return array
 */
function boldgrid_backup_reseller_option( $value ) {
	return is_array( $value ) ? $value : array();
}

add_filter( 'option_boldgrid_reseller', 'boldgrid_backup_reseller_option' );
add_filter( 'default_option_boldgrid_reseller', 'boldgrid_backup_reseller_option' );
