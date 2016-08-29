<?php
/**
 * The admin-specific utility methods for the plugin
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
		if ( ! is_numeric( $bytes ) ) {
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
				$message = esc_html__( 'File already exists', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_INCONS :
				$message = esc_html__( 'Zip archive inconsistent', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_INVAL :
				$message = esc_html__( 'Invalid argument', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_MEMORY :
				$message = esc_html__( 'Malloc failure', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_NOENT :
				$message = esc_html__( 'No such file', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_NOZIP :
				$message = esc_html__( 'Not a zip archive', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_OPEN :
				$message = esc_html__( 'Cannot open file', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_READ :
				$message = esc_html__( 'Read error', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_SEEK :
				$message = esc_html__( 'Seek error', 'boldgrid-backup' );
				break;
			default :
				$message = esc_html__( 'No error code was passed', 'boldgrid-backup' );
				break;
		}

		return $message;
	}

	/**
	 * Translate a file upload error code into a human-readable message.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 *
	 * @param int $error_code An error code from a file upload error constant.
	 * @return string An error message.
	 */
	public static function translate_upload_error( $error_code ) {
		switch ( $error_code ) {
			case UPLOAD_ERR_INI_SIZE:
				$message = esc_html__(
					'The uploaded file exceeds the upload_max_filesize directive in php.ini',
					'boldgrid-backup'
				);
				break;

			case UPLOAD_ERR_FORM_SIZE:
				$message = esc_html__(
					'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
					'boldgrid-backup'
				);
				break;

			case UPLOAD_ERR_PARTIAL:
				$message = esc_html__(
					'The uploaded file was only partially uploaded',
					'boldgrid-backup'
				);
				break;

			case UPLOAD_ERR_NO_FILE:
				$message = esc_html__(
					'No file was uploaded',
					'boldgrid-backup'
				);

				break;

			case UPLOAD_ERR_NO_TMP_DIR:
				$message = esc_html__(
					'Missing a temporary folder',
					'boldgrid-backup'
				);
				break;

			case UPLOAD_ERR_CANT_WRITE:
				$message = esc_html__(
					'Failed to write file to disk',
					'boldgrid-backup'
				);
				break;

			case UPLOAD_ERR_EXTENSION:
				$message = esc_html__(
					'File upload stopped by extension',
					'boldgrid-backup'
				);
				break;

			default:
				$message = esc_html(
					'Unknown upload error',
					'boldgrid-backup'
				);
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

		if ( empty( $filepath ) ) {
			return true;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// If path exists and is not writable, then make writable.
		if ( $wp_filesystem->exists( $filepath ) ) {
			if ( ! $wp_filesystem->is_writable( $filepath ) ) {
				if ( $wp_filesystem->is_dir( $filepath ) ) {
					// Is a directory.
					if ( ! $wp_filesystem->chmod( $filepath, 0755 ) ) {
						// Error chmod 755 a directory.
						error_log(
							__METHOD__ . ': Error using chmod 0755 on directory "' . $filepath . '".'
						);

						return false;
					}
				} else {
					// Is a file.
					if ( ! $wp_filesystem->chmod( $filepath, 0644 ) ) {
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

	/**
	 * Increase the PHP max execution time.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @link http://php.net/manual/en/info.configuration.php#ini.max-execution-time
	 *
	 * @param string $max_execution_time A php.ini style max_execution_time.
	 * @return bool Success of the operation.
	 */
	public static function bump_max_execution( $max_execution_time ) {
		// Abort if in safe mode or max_execution_time is not changable.
		if ( ini_get( 'safe_mode' ) || ! wp_is_ini_value_changeable( 'max_execution_time' ) ) {
			return false;
		}

		// Validate input max_execution_time.
		if ( ! is_numeric( $max_execution_time ) || $max_execution_time < 0 ) {
			return false;
		}

		// Get the current max execution time set for PHP.
		$current_max = ini_get( 'max_execution_time' );

		// If the current max execution time is less than specified, then try to increase it.
		// PHP default is "30".
		if ( $current_max < $max_execution_time ) {
			set_time_limit( $max_execution_time );

			if ( false === ini_set( 'max_execution_time', $max_execution_time ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the file upload limit.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @see wp_convert_hr_to_bytes() in wp-includes/load.php
	 * @link http://php.net/manual/en/ini.core.php#ini.post-max-size
	 * @link http://php.net/manual/en/ini.core.php#ini.upload-max-filesize
	 *
	 * @return int The upload/post limit in bytes.
	 */
	public static function get_upload_limit() {
		// Get PHP setting value for post_max_size.
		// PHP default is "8M".
		$post_max_size = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );

		// Get PHP setting value for upload_max_filesize.
		// PHP default is "2M".
		$upload_max_filesize = wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );

		// Determine the minimum value.
		$min = min( $post_max_size, $upload_max_filesize );

		// Return the resulting minimum value (int in bytes).
		return $min;
	}

	/**
	 * Increase the PHP memory limit.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @see wp_is_ini_value_changeable() in wp-includes/default-constants.php
	 * @see wp_convert_hr_to_bytes() in wp-includes/load.php
	 * @link http://php.net/manual/en/ini.core.php#ini.memory-limit
	 *
	 * @param string $memory_limit A php.ini style memory_limit string.
	 * @return bool Success of the operation.
	 */
	public static function bump_memory_limit( $memory_limit ) {
		// Abort if in safe mode or memory_limit is not changable.
		if ( ini_get( 'safe_mode' ) || ! wp_is_ini_value_changeable( 'memory_limit' ) ) {
			return false;
		}

		// Convert memory limit string to an integer in bytes.
		$memory_limit_int = wp_convert_hr_to_bytes( $memory_limit );

		// Get the current upload max filesize set for PHP.
		$current_limit_int = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );

		// Apply a WordPress filter to help ensure the setting.
		apply_filters( 'admin_memory_limit', $memory_limit_int );

		// If the current memory limit is less than specified, then try to increase it.
		// PHP default is "128M".
		if ( $current_limit_int < $memory_limit_int ) {
			if ( false === ini_set( 'memory_limit', $memory_limit_int ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Attempt to increase the PHP max upload size.
	 *
	 * The upload_max_filesize is set as "PHP_INI_PERDIR";
	 * The entry can be set in "php.ini", ".htaccess", "httpd.conf" or ".user.ini".
	 * We can attempt to set it to a higher limit via a filter, as WordPress may have previously
	 * reduced it.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @see wp_convert_hr_to_bytes() in wp-includes/load.php
	 * @link http://php.net/manual/en/ini.sect.safe-mode.php#ini.safe-mode
	 * @link http://php.net/manual/en/ini.core.php#ini.file-uploads
	 * @link http://php.net/manual/en/ini.core.php#ini.max-file-uploads
	 *
	 * @param string $max_filesize A php.ini style upload_max_filesize string.
	 * @return bool Success of the operation.
	 */
	public static function bump_upload_limit( $max_filesize ) {
		// Abort if in safe mode.
		if ( ini_get( 'safe_mode' ) ) {
			return false;
		}

			// Abort if file_uploads is "0" (disabled).
		// PHP default is "1" (enabled).
		if ( ! ini_get( 'file_uploads' ) ) {
			return false;
		}

		// Abort if max_file_uploads is "0" (disabled).
		// PHP default is "20".
		if ( ! ini_get( 'max_file_uploads' ) ) {
			return false;
		}

		// Convert upload max filesize string to an integer in bytes.
		$max_filesize_int = wp_convert_hr_to_bytes( $max_filesize );

		// Apply a WordPress filter to help ensure the setting.
		apply_filters( 'upload_size_limit', $max_filesize_int, $max_filesize_int, $max_filesize_int );

		return true;
	}

	/**
	 * Check if a file is a ZIP archive file.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @see get_filesystem_method() in wp-admin/includes/file.php
	 *
	 * @param string $file A file path to be checked.
	 * @return bool
	 */
	public static function is_zip_file( $file ) {
		// Validate input filename.
		if ( empty( $file ) ) {
			return false;
		}

		// Create a ZipArchive object.
		$zip = new ZipArchive;

		// Check the ZIP file for consistency.
		$status = $zip->open( $file, ZipArchive::CHECKCONS );

		// Close the ZIP file.
		$zip->close();

		// Check the result.
		$result = ( true === $status );

		// Return the result.
		return $result;
	}

	/**
	 * Check if a specific file exists in a ZIP archive.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @link http://php.net/manual/en/class.ziparchive.php
	 *
	 * @param string $zip_file Path to a ZIP file.
	 * @param string $locate_file A filename or path to be located.
	 * @param bool   $is_path Is the input file a path.
	 * @return bool
	 */
	public static function zip_file_exists( $zip_file, $locate_file, $is_path = false ) {
		// Validate input parameters.
		if ( empty( $zip_file ) || empty( $locate_file ) ) {
			return false;
		}

		// Create a ZipArchive object.
		$zip = new ZipArchive;

		// Check the ZIP file for consistency.
		$status = $zip->open( $zip_file, ZipArchive::CHECKCONS );

		if ( true !== $status ) {
			// Invalid ZIP file.
			return false;
		}

		// Locate the filename or path.
		if ( $is_path ) {
			$index = $zip->locateName( $locate_file );
		} else {
			$index = $zip->locateName( $$locate_file, ZipArchive::FL_NODIR );
		}

		// Close the ZIP file.
		$zip->close();

		// Return the result.
		return (bool) $index;
	}

	/**
	 * Check if specific entry patterns exists in a ZIP archive.
	 *
	 * @since 1.2.2
	 *
	 * @static
	 *
	 * @link http://php.net/manual/en/class.ziparchive.php
	 *
	 * @param string $zip_file Path to a ZIP file.
	 * @param array  $locate_files An array of filenames and/or paths to be located.
	 * @return bool
	 */
	public static function zip_patterns_exist( $zip_file, $locate_files ) {
		// Validate input parameters.
		if ( empty( $zip_file ) || empty( $locate_files ) || ! is_array( $locate_files ) ) {
			return false;
		}

		// Create a ZipArchive object.
		$zip = new ZipArchive;

		// Check the ZIP file for consistency.
		$status = $zip->open( $zip_file, ZipArchive::CHECKCONS );

		if ( true !== $status ) {
			// Invalid ZIP file.
			return false;
		}

		// Define an array of paterns to skip.
		$skip = array(
			'.htaccess',
		);

		// Check for each string pattern in the $locate_files array.
		// This is a loose search, so we can also search for directory names.
		foreach ( $locate_files as $locate_entry ) {
			// Skip certain patterns.
			if ( in_array( $locate_entry, $skip, true ) ) {
				continue;
			}

			// Initialize $found.
			$found = false;

			// Iterate through the ZIP file list.
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				// Get the list entry name.
				$entry = $zip->getNameIndex( $i );

				if ( false !== strpos( $entry, $locate_entry ) ) {
					// Pattern was found; skip to the next iteration.
					$found = true;

					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		// Close the ZIP file.
		$zip->close();

		// Return success.
		return true;
	}
}
