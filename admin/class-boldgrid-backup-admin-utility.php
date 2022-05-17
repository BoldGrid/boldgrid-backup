<?php
/**
 * File: class-boldgrid-backup-admin-utility.php
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Utility
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
	 * @param int $bytes    Number of bytes.
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

		$return = str_replace( '.00', '', $return );

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
	 * Database find and replace.
	 *
	 * Take note we also have self::option_find_replace that does a find and replace specific to option
	 * values because they are serialized. It uses self::str_replace_recursive.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $find
	 * @param string $replace
	 */
	public static function db_find_replace( $table, $column, $find, $replace ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE	`' . $wpdb->prefix . '%1$s`
				SET		`%2$s` = REPLACE( `%3$s`, "%4$s", "%5$s" )
				WHERE	`%6$s` LIKE "%%%7$s%%";',
				$table,
				$column,
				$column,
				$find,
				$replace,
				$column,
				$wpdb->esc_like( $find )
			)
		);
		// phpcs:enable
	}

	/**
	 * Custom error handler.
	 *
	 * Catches everything (including warnings) and throws an excpetion.
	 *
	 * Can be used in this manner:
	 * set_error_handler( array( 'Boldgrid_Backup_Admin_Utility', 'handle_error' ) );
	 * try{
	 *      // Try something
	 * } catch( Exception $e ) {
			$e->getMessage();
	 * }
	 * restore_error_handler();
	 *
	 * @since 1.6.0
	 *
	 * @static
	 *
	 * @param  int    $errno   Error number. (can be a PHP Error level constant).
	 * @param  string $errstr  Error description.
	 * @param  string $errfile File in which the error occurs.
	 * @param  int    $errline Line number where the error is situated.
	 * @throws ErrorException  Error exception object.
	 */
	public static function handle_error( $errno, $errstr, $errfile = false, $errline = false ) {
		// A set of errors to ignore.
		$skips = array(

			/*
			 * Ignore mcrypt errors (DEPRECATED as of PHP 7.1.0).
			 *
			 * When using phpseclib for sftp, we're catching these warnings even
			 * though the author used @suppression with their mcrypt calls. There's
			 * a lot of information online about these errors within phpseclib,
			 * but I'll reference the following:
			 *
			 * https://github.com/phpseclib/phpseclib/issues/1028
			 * # mcrypt is only used if it's available. If mcrypt is not available
			 *   either a pure-PHP implementation is used or OpenSSL is used. The
			 *   prioritization is as follows: OpenSSL > mcrypt > pure-PHP. mcrypt
			 *   and OpenSSL are loads faster than the pure-PHP implementation.
			 * # So mcrypt offers a 45x speedup over the internal mode. OpenSSL
			 *   offers a 6.5x speedup over mcrypt.
			 *
			 * https://github.com/phpseclib/phpseclib/issues/1229
			 * # phpseclib (all branches) are unit tested on PHP 7.2:
			 *   https://travis-ci.org/phpseclib/phpseclib
			 *   They all pass in spite of using mcrypt. idk if you've ever used
			 *   Travis CI / phpunit but an E_DEPRECATED notice will result in a
			 *   failing unit test and yet the unit tests are all passing.
			 */
			'Function mcrypt_list_algorithms() is deprecated',
			'Function mcrypt_module_open() is deprecated',
			'Function mcrypt_generic_init() is deprecated',
			'Function mcrypt_generic() is deprecated',
			'Function mdecrypt_generic() is deprecated',
		);

		if ( in_array( $errstr, $skips, true ) ) {
			return;
		}

		throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
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
			case ZipArchive::ER_EXISTS:
				$message = esc_html__( 'File already exists', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_INCONS:
				$message = esc_html__( 'Zip archive inconsistent', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_INVAL:
				$message = esc_html__( 'Invalid argument', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_MEMORY:
				$message = esc_html__( 'Malloc failure', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_NOENT:
				$message = esc_html__( 'No such file', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_NOZIP:
				$message = esc_html__( 'Not a zip archive', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_OPEN:
				$message = esc_html__( 'Cannot open file', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_READ:
				$message = esc_html__( 'Read error', 'boldgrid-backup' );
				break;
			case ZipArchive::ER_SEEK:
				$message = esc_html__( 'Seek error', 'boldgrid-backup' );
				break;
			default:
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
						return false;
					}
				} else {
					// Is a file.
					if ( ! $wp_filesystem->chmod( $filepath, 0644 ) ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Find and replace for option values.
	 *
	 * Similar to self::db_find_replace. This one is special however because it ends up using
	 * self::str_replace_recursive for the replacement mechanism.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $find
	 * @param string $replace
	 */
	public static function option_find_replace( $find, $replace ) {
		global $wpdb;

		$matched_options = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT	`option_name`
				FROM	`' . $wpdb->prefix . 'options`
				WHERE	`option_value` LIKE %s;',
				'%' . $wpdb->esc_like( $find ) . '%'
			),
			ARRAY_N
		);

		if ( empty( $matched_options ) ) {
			return;
		}

		foreach ( $matched_options as $option_name ) {
			$option_value = get_option( $option_name[0] );
			$option_value = self::str_replace_recursive( $find, $replace, $option_value );

			update_option( $option_name[0], $option_value );
		}
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

			if ( false === ini_set( 'max_execution_time', $max_execution_time ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
				return false;
			}
		}

		return true;
	}

	/**
	 * Get plugin data.
	 *
	 * This is a wrapper function for WordPress' get_plugin_data function,
	 * which requires the full path to a plugin. This method only requires
	 * folder/file.php
	 *
	 * @since 1.5.3
	 *
	 * @param  string $plugin Plugin slug ("boldgrid-backup/boldgrid-backup.php").
	 * @return array
	 */
	public function get_plugin_data( $plugin ) {
		$path = dirname( BOLDGRID_BACKUP_PATH ) . DIRECTORY_SEPARATOR . $plugin;
		$data = get_plugin_data( $path );
		return $data;
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
			if ( false === ini_set( 'memory_limit', $memory_limit_int ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
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
	 * Determine whether or not Total Upkeep is active.
	 *
	 * The fact that Total Upkeep is calling this method shows that it is installed and activated.
	 * However, it we are not listed in the "active_plugins" option, then we are in the middle of
	 * activation.
	 *
	 * Because the library may not be available until activation, this method can help us determine
	 * whether or not we should instantiate library classes at a certain time.
	 *
	 * @since 1.13.5
	 *
	 * @return bool
	 */
	public static function is_active() {
		$active_plugins = get_option( 'active_plugins', array() );

		return in_array( 'boldgrid-backup/boldgrid-backup.php', $active_plugins, true );
	}

	/**
	 * Determine whether or not the current user is an administrator.
	 *
	 * @since 1.14.14
	 *
	 * @return bool
	 */
	public static function is_user_admin() {
		return current_user_can( 'update_plugins' );
	}

	/**
	 * Determine whether or not the given $page is the current.
	 *
	 * @since 1.7.0
	 *
	 * @global string $pagenow
	 *
	 * @param  string $page The page to check for in $_GET.
	 * @return boolean
	 */
	public static function is_admin_page( $page ) {
		global $pagenow;

		return 'admin.php' === $pagenow && ! empty( $_GET['page'] ) && $page === $_GET['page']; // phpcs:ignore WordPress.CSRF.NonceVerification
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
		$zip = new ZipArchive();

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
	 * @param string $zip_file    Path to a ZIP file.
	 * @param string $locate_file A filename or path to be located.
	 * @param bool   $is_path     Is the input file a path.
	 * @return bool
	 */
	public static function zip_file_exists( $zip_file, $locate_file, $is_path = false ) {
		// Validate input parameters.
		if ( empty( $zip_file ) || empty( $locate_file ) ) {
			return false;
		}

		// Create a ZipArchive object.
		$zip = new ZipArchive();

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
			$index = $zip->locateName( $locate_file, ZipArchive::FL_NODIR );
		}

		// Close the ZIP file.
		$zip->close();

		// Return the result.
		return (bool) $index;
	}

	/**
	 * Chmod a directory or file.
	 *
	 * @since 1.2.2
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @static
	 *
	 * @param string $file Path to a directory or file.
	 * @param int    $mode (Optional) The permissions as octal number, usually 0644 for files,
	 *                     0755 for dirs.
	 * @return bool
	 */
	public static function chmod( $file, $mode = false ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Modify the file permissions.
		$result = $wp_filesystem->chmod( $file, $mode );

		// Return the result.
		return $result;
	}

	/**
	 * Fix wp-config.php file.
	 *
	 * If restoring "wp-config.php", then ensure that the credentials remain intact.
	 *
	 * @since 1.2.2
	 *
	 * @see http://us1.php.net/manual/en/function.preg-replace.php#103985
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @static
	 *
	 * @return bool
	 */
	public static function fix_wpconfig() {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Set the file path.
		$file = ABSPATH . 'wp-config.php';

		// Abort if the file does not exist.
		if ( ! $wp_filesystem->exists( $file ) ) {
			return false;
		}

		// Get the file contents.
		$file_contents = $wp_filesystem->get_contents( $file );

		// Create an array containing the definition names to replace.
		$definitions = array(
			'DB_NAME',
			'DB_USER',
			'DB_PASSWORD',
			'DB_HOST',
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		);

		// Replace the definitions.
		foreach ( $definitions as $definition ) {
			// If the definition does not exist, then skip it.
			if ( ! defined( $definition ) ) {
				continue;
			}

			// Replace $n ($0-$99) backreferences before preg_replace.
			// @see http://us1.php.net/manual/en/function.preg-replace.php#103985 .
			$value = preg_replace( '/\$(\d)/', '\\\$$1', constant( $definition ) );

			// Replace definition.
			$file_contents = preg_replace(
				'#define.*?' . $definition . '.*;#',
				"define('" . $definition . "', '" . $value . "');",
				$file_contents,
				1
			);

			// If there was a failure, then abort.
			if ( null === $file_contents ) {
				return false;
			}
		}

		// Write the changes to file.
		$wp_filesystem->put_contents( $file, $file_contents, 0600 );

		return true;
	}

	/**
	 * A wrapper for WordPress' flush_rewrite_rules.
	 *
	 * Wrapper function is necessary because rewriting the .htaccess only works if the
	 * save_mod_rewrite_rules() function exists, which only does in admin. This method ensures it's
	 * there.
	 *
	 * @link https://core.trac.wordpress.org/ticket/51805
	 *
	 * @param bool $hard Whether to update .htaccess (hard flush) or just update rewrite_rules option
	 *                   (soft flush).
	 */
	public static function flush_rewrite_rules( $hard = true ) {
		if ( $hard && ! function_exists( 'save_mod_rewrite_rules' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		flush_rewrite_rules( $hard );
	}

	/**
	 * Replace the siteurl in the WordPress database.
	 *
	 * @since 1.2.3
	 *
	 * @see Boldgrid_Backup_Admin_Utility::str_replace_recursive()
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @static
	 *
	 * @param array $args {
	 *      An array of arguments.
	 *
	 *      @type string $old_siteurl The old/restored siteurl to find and be replaced.
	 *      @type string $siteurl     The siteurl to replace the old siteurl.
	 *      @type bool   $flush       Whether or not to flush the rewrite rules.
	 * }
	 * @return bool
	 */
	public static function update_siteurl( $args = array() ) {
		wp_parse_args( $args, array(
			'flush' => false,
		) );

		$old_siteurl = $args['old_siteurl'];
		$new_siteurl = $args['siteurl'];

		// Validate.
		if ( false === filter_var( $old_siteurl, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		if ( false === filter_var( $new_siteurl, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		/*
		 * Ensure the site url does not end in a trailing slash.
		 *
		 * This is best practice. IE when you manually edit your home / siteurl in the dashboard on
		 * the Settings > General page, WordPress will automatically untrailingslahsit.
		 */
		$old_siteurl = untrailingslashit( $old_siteurl );
		$new_siteurl = untrailingslashit( $new_siteurl );

		/*
		 * Find and replace option values.
		 *
		 * Do this before updating the "siteurl" and "home" via the update_option calls below. Otherwise,
		 * we'll runing into:
		 * # Old url:           domain.com
		 * # Requested new url: domain.com/514/514
		 * # Resulting url:     domain.com/514/514/514/514
		 */
		self::option_find_replace( $old_siteurl, $new_siteurl );

		remove_all_filters( 'pre_update_option_siteurl' );

		update_option( 'siteurl', $new_siteurl );
		update_option( 'home', $new_siteurl );

		$replacers = array(
			// Post content.
			array(
				'table'   => 'posts',
				'column'  => 'post_content',
				'find'    => $old_siteurl,
				'replace' => $new_siteurl,
			),
			// Custom urls in menus.
			array(
				'table'   => 'postmeta',
				'column'  => 'meta_value',
				'find'    => $old_siteurl,
				'replace' => $new_siteurl,
			),
		);

		foreach ( $replacers as $replacer ) {
			self::db_find_replace( $replacer['table'], $replacer['column'], $replacer['find'], $replacer['replace'] );
		}

		// Check if the upload_url_path needs to be updated.
		$upload_url_path = get_option( 'upload_url_path' );
		if ( ! empty( $upload_url_path ) ) {
			$upload_url_path = str_replace( $old_siteurl, $new_siteurl, $upload_url_path );
			update_option( 'upload_url_path', $upload_url_path );
		}

		/*
		 * If requested (false by default), flush the rewrite rules.
		 *
		 * Why not make this a standard operation? We would except for the fact that the
		 * Boldgrid_Backup_Admin_Restore_Helper class adds the "flush_rewrite_rules" function to the
		 * "shutdown" hook. Does it need to be done at shutdown? Not entirely sure.
		 *
		 * This optional action is helpful when updating the site url outside of a restoration process,
		 * IE a stand alone REST call to update the site url.
		 */
		if ( ! empty( $args['flush'] ) ) {
			self::flush_rewrite_rules();
		}

		return true;
	}

	/**
	 * Replace string(s) in a string or recurively in an array or object.
	 *
	 * @since 1.2.3
	 *
	 * @static
	 *
	 * @param string $search  Search string.
	 * @param string $replace Replace string.
	 * @param mixed  $subject Input subject (array|object|string).
	 * @return mixed The input subject with recursive string replacements.
	 */
	public static function str_replace_recursive( $search, $replace, $subject ) {
		if ( is_string( $subject ) ) {
			$subject = str_replace( $search, $replace, $subject );
		} elseif ( is_array( $subject ) ) {
			foreach ( $subject as $index => $element ) {
				// Recurse.
				$subject[ $index ] = self::str_replace_recursive(
					$search,
					$replace,
					$element
				);
			}
		} elseif ( is_object( $subject ) ) {
			foreach ( $subject as $index => $element ) {
				// Recurse.
				$subject->$index = self::str_replace_recursive(
					$search,
					$replace,
					$element
				);
			}
		}

		return $subject;
	}

	/**
	 * Convert a unix time to user's local time.
	 *
	 * @since 1.5.3
	 *
	 * @param  int $time Time in UNIX seconds.
	 * @return int
	 */
	public function time( $time ) {
		$gmt_offset = get_option( 'gmt_offset' );

		if ( empty( $gmt_offset ) || ! is_numeric( $gmt_offset ) ) {
			return $time;
		}

		return $time + ( $gmt_offset * HOUR_IN_SECONDS );
	}

	/**
	 * Alternative to WordPress' trailingslashit function.
	 *
	 * WordPress' native function does not take into account Windows / the
	 * DIRECTORY_SEPARATOR.
	 *
	 * @since 1.5.1
	 *
	 * @static
	 *
	 * @param  string $string A string.
	 * @return string
	 */
	public static function trailingslashit( $string ) {
		switch ( DIRECTORY_SEPARATOR ) {
			case '/':
				$string = str_replace( '\\', '/', $string );
				break;
			case '\\':
				$string = str_replace( '/', '\\', $string );
				break;
		}

		return untrailingslashit( $string ) . DIRECTORY_SEPARATOR;
	}
}
