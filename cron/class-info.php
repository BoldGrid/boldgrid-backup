<?php
/**
 * File: class-info.php
 *
 * Get information needed for cron processes.
 *
 * @link https://www.boldgrid.com
 * @since 1.9.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.WP.AlternativeFunctions,WordPress.XSS.EscapeOutput
 */

namespace Boldgrid\Backup\Cron;

/**
 * Class: Info.
 *
 * @since 1.9.0
 */
class Info {
	/**
	 * Archive and environment information.
	 *
	 * @since 1.9.0
	 * @access private
	 *
	 * @var array
	 * @staticvar
	 */
	private static $info = [];

	/**
	 * Backup result information JSON file path.
	 *
	 * @since.1.8.0
	 * @access private
	 * @staticvar
	 *
	 * @var string
	 */
	private static $results_file_path = __DIR__ . '/restore-info.json';

	/**
	 * Get the results file path.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @return string
	 */
	public static function get_results_filepath() {
		return self::$results_file_path;
	}

	/**
	 * Get information.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::is_cli()
	 * @see self::get_mode()
	 * @see self::get_zip_arg()
	 * @see self::have_execution_functions()
	 * @see self::get_restore_info()
	 *
	 * @return array
	 */
	public static function get_info() {
		if ( empty( self::$info['checked'] ) ) {
			self::is_cli();
			self::get_mode();
			self::get_zip_arg();
			self::have_execution_functions();
			self::get_restore_info();
		}

		return self::$info;
	}

	/**
	 * Check for errors.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_info()
	 *
	 * @return bool
	 */
	public static function has_errors() {
		self::get_info();

		return ! empty( self::$info['errors'] );
	}

	/**
	 * Print errors.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::has_errors()
	 */
	public static function print_errors() {
		if ( self::has_errors() ) {
			echo implode( PHP_EOL, self::$info['errors'] ) . PHP_EOL;
		}
	}

	/**
	 * Is this process running from the command line.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid_Backup_Cron_Helper::is_cli()
	 *
	 * @return bool
	 */
	public static function is_cli() {
		require_once __DIR__ . '/class-boldgrid-backup-cron-helper.php';

		if ( ! \Boldgrid_Backup_Cron_Helper::is_cli() ) {
			self::$info['errors']['cli'] = 'Error: This process must run from the CLI.';
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Get the operational mode.
	 *
	 * A specific mode is required.
	 * "help" will print usage and exit.  This is the default option.
	 * "check" will check issues and restore if needed.
	 * "restore" will force a restoration.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::has_arg_flag()
	 *
	 * @return string|false
	 */
	public static function get_mode() {
		if ( ! isset( self::$info['operation'] ) ) {
			switch ( true ) {
				case self::has_arg_flag( 'help' ):
					self::$info['operation'] = 'help';
					break;
				case self::has_arg_flag( 'restore' ):
					self::$info['operation'] = 'restore';
					break;
				case self::has_arg_flag( 'check' ):
					self::$info['operation'] = 'check';
					break;
				default:
					self::$info['operation'] = false;
					break;
			}

			if ( 'help' === self::$info['operation'] ) {
				self::$info['errors']['help'] =
					'Usage: php bgbkup-cli.php <check|restore> [zip=<path/to/backup.zip>]';
			} elseif ( ! self::$info['operation'] ) {
				self::$info['errors']['mode'] =
					'Error: An operational mode (check/restore) is required.';
				self::$info['errors']['help'] =
					'Usage: php bgbkup-cli.php <check|restore> [zip=<path/to/backup.zip>]';
			}
		}

		return self::$info['operation'];
	}

	/**
	 * Are there available execution functions.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid_Backup_Admin_Cli::get_execution_functions()
	 *
	 * @return bool
	 */
	public static function have_execution_functions() {
		require_once dirname( __DIR__ ) . '/admin/class-boldgrid-backup-admin-cli.php';

		$exec_functions = \Boldgrid_Backup_Admin_Cli::get_execution_functions();

		if ( empty( $exec_functions ) ) {
			self::$info['errors']['no_exec'] = 'Error: No available PHP executable functions.';
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Get CLI arguments, save the array in $info['cli_args'], and return the array of CLI args.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @return array;
	 */
	public static function get_cli_args() {
		if ( empty( self::$info['cli_args'] ) ) {
			if ( ! empty( $_SERVER['argv'] ) ) {
				parse_str(
					implode( '&', array_slice( $_SERVER['argv'], 1 ) ),
					self::$info['cli_args']
				);
			} else {
				self::$info['cli_args'] = [];
			}
		}

		return self::$info['cli_args'];
	}

	/**
	 * Do CLI arguments include specified flag.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_cli_args()
	 *
	 * @param  string $flag Flag argument.
	 * @return bool
	 */
	public static function has_arg_flag( $flag ) {
		return isset( self::get_cli_args()[ $flag ] );
	}

	/**
	 * Get the value of a spcified CLI arguments.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_cli_args()
	 *
	 * @param  string $name Argument name.
	 * @return string|null
	 */
	public static function get_arg_value( $name ) {
		$args  = self::get_cli_args();
		$value = isset( $args[ $name ] ) ? $args[ $name ] : null;

		return $value;
	}

	/**
	 * Get the ZIP path spcified in CLI arguments.
	 *
	 * ZIP archive value specified is formatted into a canonicalized absolute pathname.
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_cli_args()
	 *
	 * @return string|false
	 */
	public static function get_zip_arg() {
		if ( ! isset( self::$info['zip'] ) ) {
			$zip_arg  = self::get_arg_value( 'zip' );
			$zip_path = realpath( $zip_arg );

			if ( self::has_arg_flag( 'zip' ) && empty( $zip_arg ) ) {
				self::$info['errors']['zip_path_empty'] = 'Error: Empty ZIP archive path specified.';
			} elseif ( $zip_arg && ! file_exists( $zip_path ) ) {
				self::$info['errors']['zip_path_bad'] = 'Error: Specified ZIP archive path "' .
				( $zip_path ? $zip_path : $zip_arg ) . '" does not exist.';
			}

			self::$info['zip'] = ( $zip_arg && $zip_path && file_exists( $zip_path ) ) ?
				$zip_path : false;
		}

		return self::$info['zip'];
	}

	/**
	 * Get environment info, save the array in $info['env'], and return the array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see Boldgrid_Backup_Url_Helper::call_url()
	 *
	 * @return array;
	 */
	public static function get_env_info() {
		if ( empty( self::$info['env'] ) ) {
			require_once __DIR__ . '/class-boldgrid-backup-url-helper.php';
			$url_helper = new \Boldgrid_Backup_Url_Helper();

			self::$info['env'] = json_decode(
				$url_helper->call_url(
					self::$info['siteurl'] . '/wp-content/plugins/boldgrid-backup/cron/env-info.php'
				),
				true
			);
		}

		return self::$info['env'];
	}

	/**
	 * Read a JSON file, and return the contents in an array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @param  string $file_path JSON file path.
	 * @return array
	 */
	public static function read_json_file( $file_path ) {
		$result = file_exists( $file_path ) ?
			json_decode( file_get_contents( $file_path ), true ) : false;

		return $result ? $result : [];
	}

	/**
	 * Read an archive log JSON file from inside of a backup archive ZIP file, and return the
	 * contents in an array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @param  string $filepath Backup Archive ZIP file path.
	 * @return array
	 */
	public static function read_zip_log( $filepath ) {
		if ( ! preg_match( '/\.zip$/', $filepath ) ) {
			self::$info['errors']['zip_path_invalid'] =
				'Error: Invalid ZIP file path specified; must end with ".zip".';
			return [];
		}

		// Determine the log filename.
		$archive_log_file = basename( preg_replace( '/\.zip$/', '.log', $filepath ) );

		$extract_dir      = dirname( $filepath );
		$extract_filepath = $extract_dir . '/' . $archive_log_file;

		if ( ! file_exists( $extract_filepath ) ) {
			// Extract the log file.
			switch ( true ) {
				case class_exists( 'ZipArchive' ):
					$archive = new \ZipArchive();
					if ( true === $archive->open( self::$info['filepath'] ) ) {
						$success = $archive->extractTo( $extract_dir, $archive_log_file );
						$archive->close();
					}
					break;
				// @todo: Add PCLZip and unzip (CLI).
				default:
					self::$info['errors'][] = 'Error: Could not extract files; ZipArchive unavailable.';
					return [];
			}

			if ( ! file_exists( $extract_filepath ) ) {
				self::$info['errors'][] = 'Error: Backup archive log file "' . $archive_log_file .
					'" does not exist in the specified ZIP file path "' . $filepath . '".';
				return [];
			}

			$log_info = self::read_json_file( $extract_filepath );
			unlink( $extract_filepath );
		} else {
			// Log file already existed, so just read it.
			$log_info = self::read_json_file( $extract_filepath );
		}

		return $log_info;
	}

	/**
	 * Set an item in the info array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @param string $key   Index/key name.
	 * @param mixed  $value Value.
	 */
	public static function set_info_item( $key, $value ) {
		if ( isset( $key, $value ) && is_string( $key ) ) {
			self::$info[ $key ] = $value;
		}
	}

	/**
	 * Retrieve validated restoration information.
	 *
	 * If validation failes, then errors are saved in the $info['errors'] class property.
	 *
	 * @since 1.9.0
	 * @access private
	 * @static
	 *
	 * @see self::get_zip_arg()
	 * @see self::read_json_file()
	 * @see self::read_zip_log()
	 */
	private static function get_restore_info() {
		self::$info['checked'] = time();

		// Initialize restore attempts counter, which is a default until merge with $results.
		self::$info['restore_attempts'] = 0;

		// Check for PHP safe mode.
		if ( ini_get( 'safe_mode' ) ) {
			self::$info['errors'][] = 'Error: Cannot continue in PHP safe mode.';
			return;
		}

		if ( self::has_arg_flag( 'zip' ) ) {
			// Use the specified ZIP archive file path from the CLI arguments.
			self::$info['filepath'] = self::get_zip_arg();

			if ( empty( self::$info['filepath'] ) ) {
				// Error already added by self::get_zip_arg().
				return;
			}

			self::$info['archive_key'] = 0;
			self::$info['restore_cmd'] = null;

			$results = self::read_json_file( self::$results_file_path );

			self::$info['cron_secret'] = isset( $results['cron_secret'] ) ?
				$results['cron_secret'] : null;

			// Retrieve information from the log file in the ZIP archive.
			$log_info = self::read_zip_log( self::$info['filepath'] );

			if ( empty( $log_info ) ) {
				// Error already added by self::read_zip_log().
				return;
			}

			self::$info = array_merge( self::$info, $log_info );

			if ( self::$info['siteurl'] && self::$info['backup_id'] && self::$info['cron_secret'] ) {
				// Build the restore command.
				self::$info['restore_cmd'] = 'php -d register_argc_argv=1 -qf "' . dirname( __DIR__ ) .
					'/boldgrid-backup-cron.php" mode=restore siteurl=' . self::$info['siteurl'] .
					' id=' . self::$info['backup_id'] . ' secret=' . self::$info['cron_secret'] .
					' archive_key=' . self::$info['archive_key'] . ' archive_filename=' .
					basename( self::$info['filepath'] );
			}

				self::$info['timestamp'] = time();
		} else {
			// Use the latest backup archive created by the plugin.  Get the backup results file.
			if ( ! file_exists( self::$results_file_path ) ) {
				self::$info['errors'][] = 'Error: Missing backup results file ("' .
					self::$results_file_path . '").';
				return;
			}

			$results = self::read_json_file( self::$results_file_path );

			// Validate results file content.
			if ( empty( $results ) ) {
				self::$info['errors'][] = 'Error: No backup results found.';
				return;
			}

			if ( empty( $results['filepath'] ) ) {
				self::$info['errors'][] = 'Error: Unknown backup archive file path.';
				return;
			}

			if ( empty( $results['file_md5'] ) ) {
				self::$info['errors'][] = 'Error: Missing archive file checksum.';
				return;
			}

			// Check if archive exists, and matches checksum.
			if ( ! file_exists( $results['filepath'] ) ) {
				self::$info['errors'][] = 'Error: Backup archive file "' .
					$results['filepath'] . '" does not exist.';
				return;
			}

			if ( md5_file( $results['filepath'] ) !== $results['file_md5'] ) {
				self::$info['errors'][] = 'Error: Failed archive file checksum.';
				return;
			}

			// Get the archive log file and merge info.
			$archive_log_filepath = preg_replace( '/\.zip$/', '.log', $results['filepath'] );

			if ( ! file_exists( $archive_log_filepath ) ) {
				self::$info['errors'][] = 'Error: Backup archive log file "' . $archive_log_filepath .
					'" does not exist.';
				return;
			}

			$log_info = self::read_json_file( $archive_log_filepath );

			// Validate results file content.
			if ( empty( $log_info ) ) {
				self::$info['errors'][] = 'Error: No backup information found in the log file "' .
					$archive_log_filepath . '".';
				return;
			}

			// Merge info and results arrays.
			self::$info = array_merge( self::$info, $log_info, $results );

			if ( empty( self::$info['cron_secret'] ) ) {
				self::$info['errors'][] = 'Error: Unknown cron_secret.';
			}
		}

		if ( empty( self::$info['ABSPATH'] ) ) {
			self::$info['errors'][] = 'Error: Unknown ABSPATH.';
			return;
		}

		if ( ! is_dir( self::$info['ABSPATH'] ) ) {
			self::$info['errors'][] = 'Error: ABSPATH directory "' . self::$info['ABSPATH'] .
				'" does not exist or is not a directory.';
		}

		if ( ! is_writable( self::$info['ABSPATH'] ) ) {
			self::$info['errors'][] = 'Error: ABSPATH directory "' . self::$info['ABSPATH'] .
				'" is not writable.';
		}

		if ( empty( self::$info['siteurl'] ) ) {
			self::$info['errors'][] = 'Error: Unknown siteurl.';
		}

		if ( empty( self::$info['db_filename'] ) ) {
			self::$info['errors'][] = 'Error: Unknown database dump filename.';
		} else {
			self::$info['db_filepath'] = self::$info['ABSPATH'] . self::$info['db_filename'];
		}

		self::get_env_info();
	}
}
