<?php
/**
 * The admin-specific utilities methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin utility class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Utility {
	/**
	 * Convert bytes to a human-readable measure.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @param int $bytes Number of bytes.
	 * @param int $decimals Number of decimal places.
	 * @return string
	 */
	public static function bytes_to_human( $bytes = 0, $decimals = 2 ) {
		// If $bytes is not a number, then fail.
		if ( false === is_numeric( $bytes ) ) {
			return 'INVALID';
		}

		// Ensure the $decimals is an integer.
		$decimals = (int) $decimals;

		$type = array(
			'B',
			'KB',
			'MB',
			'GB',
			'TB',
			'PB',
			'EB',
			'ZB',
			'YB',
		);

		$index = 0;

		while ( $bytes >= 1024 ) {
			$bytes /= 1024;
			$index ++;
		}

		$return = number_format( $bytes, $decimals, '.', '' ) . ' ' . $type[ $index ];

		return $return;
	}

	/**
	 * Create a site identifier.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @return string The site identifier.
	 */
	public static function create_site_id() {
		// Get the siteurl.
		if ( is_multisite() ) {
			// Use the siteurl from blog id 1.
			$siteurl = get_site_url( 1 );
		} else {
			// Get the current siteurl.
			$siteurl = get_site_url();
		}

		// Make an identifier.
		$site_id = explode( '/', $siteurl );
		unset( $site_id[0] );
		unset( $site_id[1] );
		$site_id = implode( '_', $site_id );

		return $site_id;
	}

	/**
	 * Translate a ZipArchive error code into a human-readable message.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @param int $error_code An error code from a ZipArchive constant.
	 * @return string An error message.
	 */
	public static function translate_zip_error( $error_code = null ) {
		switch ( $error_code ) {
			case ZipArchive::ER_EXISTS :
				$message = 'File already exists.';
				break;
			case ZipArchive::ER_INCONS :
				$message = 'Zip archive inconsistent.';
				break;
			case ZipArchive::ER_INVAL :
				$message = 'Invalid argument.';
				break;
			case ZipArchive::ER_MEMORY :
				$message = 'Malloc failure.';
				break;
			case ZipArchive::ER_NOENT :
				$message = 'No such file.';
				break;
			case ZipArchive::ER_NOZIP :
				$message = 'Not a zip archive.';
				break;
			case ZipArchive::ER_OPEN :
				$message = 'Cannot open file.';
				break;
			case ZipArchive::ER_READ :
				$message = 'Read error.';
				break;
			case ZipArchive::ER_SEEK :
				$message = 'Seek error.';
				break;
			default :
				$message = 'No error code was passed.';
				break;
		}

		return $message;
	}
}
