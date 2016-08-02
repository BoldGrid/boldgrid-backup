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
				$message = esc_html__( 'File already exists' );
				break;
			case ZipArchive::ER_INCONS :
				$message = esc_html__( 'Zip archive inconsistent' );
				break;
			case ZipArchive::ER_INVAL :
				$message = esc_html__( 'Invalid argument' );
				break;
			case ZipArchive::ER_MEMORY :
				$message = esc_html__( 'Malloc failure' );
				break;
			case ZipArchive::ER_NOENT :
				$message = esc_html__( 'No such file' );
				break;
			case ZipArchive::ER_NOZIP :
				$message = esc_html__( 'Not a zip archive' );
				break;
			case ZipArchive::ER_OPEN :
				$message = esc_html__( 'Cannot open file' );
				break;
			case ZipArchive::ER_READ :
				$message = esc_html__( 'Read error' );
				break;
			case ZipArchive::ER_SEEK :
				$message = esc_html__( 'Seek error' );
				break;
			default :
				$message = esc_html__( 'No error code was passed' );
				break;
		}

		return $message;
	}

	/**
	 * Make a directory or file writable, if exists.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @static
	 *
	 * @param string $filepath A path to a directory or file.
	 * @return bool Success.
	 */
	public static function make_writable( $filepath ) {
		// Validate file path string.
		$filepath = realpath( $filepath );

		if ( true === empty( $filepath ) ) {
			return true;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// If path exists and is not writable, then make writable.
		if ( true === $wp_filesystem->exists( $filepath ) ) {
			if ( false === $wp_filesystem->is_writable( $filepath ) ) {
				if ( true === $wp_filesystem->is_dir( $filepath ) ) {
					// Is a directory.
					if ( false === $wp_filesystem->chmod( $filepath, 0755 ) ) {
						// Error chmod 755 a directory.
						error_log(
							__METHOD__ . ': Error using chmod 0755 on directory "' . $filepath . '".'
						);

						return false;
					}
				} else {
					// Is a file.
					if ( false === $wp_filesystem->chmod( $filepath, 0644 ) ) {
						// Error chmod 644 a file.
						error_log(
							__METHOD__ . ': Error using chmod 0644 on file "' . $filepath . '".'
						);

						return false;
					}
				}
			}
		}

		return true;
	}
}
