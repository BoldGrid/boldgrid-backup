<?php
/**
 * File: class-info.php
 *
 * Get information needed for CLI processes.
 *
 * @link       https://www.boldgrid.com
 * @since      1.9.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.WP.AlternativeFunctions,WordPress.XSS.EscapeOutput
 */

namespace Boldgrid\Backup\Cli;

/**
 * Class: Info.
 *
 * @since 1.9.0
 */
class Info {
	/**
	 * Archive and environment information.
	 *
	 * @since  1.9.0
	 * @access private
	 *
	 * @var array
	 * @staticvar
	 */
	private static $info = [];

	/**
	 * Backup result information JSON file path.
	 *
	 * @since  1.8.0
	 * @access private
	 * @staticvar
	 *
	 * @var string
	 */
	private static $results_file_path;

	/**
	 * Get the results file path.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @return string
	 */
	public static function get_results_filepath() {
		if ( null === self::$results_file_path ) {
			self::$results_file_path = dirname( __DIR__ ) . '/cron/restore-info-' . self::get_secret() . '.json';
		}

		return self::$results_file_path;
	}

	/**
	 * Get secret.
	 *
	 * Used to secure scripts used outside of WordPress.
	 *
	 * @since 1.14.10
	 *
	 * @return string
	 */
	public static function get_secret() {
		$secret = null;

		// First, attempt to get our secret.
		$files   = scandir( __DIR__ );
		$pattern = '/^verify-[0-9a-f]{32}\.php/';
		$matches = preg_grep( $pattern, $files );
		if ( ! empty( $matches ) ) {
			$matches = array_values( $matches );
			preg_match( '/^verify-(.*).php/', $matches[0], $match );

			if ( ! empty( $match[1] ) ) {
				$secret = $match[1];
			}
		}

		// If we don't have a secret, make one.
		if ( empty( $secret ) ) {
			$secret   = md5( openssl_random_pseudo_bytes( 32 ) );
			$filepath = __DIR__ . '/verify-' . $secret . '.php';
			file_put_contents( $filepath, '<?php // phpcs:disable' );
		}

		return $secret;
	}

	/**
	 * Get information.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_results_filepath()
	 * @see self::is_cli()
	 * @see self::get_mode()
	 * @see self::get_notify_flag()
	 * @see self::get_email_arg()
	 * @see self::get_zip_arg()
	 * @see self::have_execution_functions()
	 * @see self::get_restore_info()
	 * @see self::choose_method()
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 *
	 * @return array
	 */
	public static function get_info() {
		if ( empty( self::$info['checked'] ) ) {
			self::get_results_filepath();
			self::is_cli();
			self::get_mode();
			self::get_log_flag();
			self::get_log_level();
			self::get_notify_flag();
			self::get_email_arg();
			self::get_zip_arg();
			self::have_execution_functions();
			self::get_restore_info();
			self::choose_method(); // Requires data from self::get_restore_info().
			Log::write( 'Gathered information.', LOG_DEBUG );
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
	 * Print errors (to STDERR / FD2) and log (if enabled).
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::has_errors()
	 */
	public static function print_errors() {
		if ( self::has_errors() ) {
			if ( ! defined( 'STDERR' ) ) {
				define( 'STDERR', fopen( 'php://stderr', 'w' ) );
			}

			fwrite( STDERR, implode( PHP_EOL, self::$info['errors'] ) . PHP_EOL );
		}
	}

	/**
	 * Is this process running from the command line.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid_Backup_Cron_Helper::is_cli()
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 *
	 * @return bool
	 */
	public static function is_cli() {
		require_once dirname( __DIR__ ) . '/cron/class-boldgrid-backup-cron-helper.php';

		if ( ! \Boldgrid_Backup_Cron_Helper::is_cli() ) {
			self::$info['errors']['cli'] = 'Error: This process must run from the CLI.';
			Log::write( self::$info['errors']['cli'], LOG_ERR );
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
	 * @see \Boldgrid\Backup\Cli\Log::write()
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

			$usage = 'Usage: php bgbkup-cli.php <check|restore> [log=<0|1>] [notify=<0|1>] [email=<email_address>] [method=<ajax|cli|pclzip|ziparchive>] [zip=<path/to/backup.zip>] [log_level=<(LOG_EMERG|LOG_ALERT|LOG_CRIT|LOG_ERR|LOG_WARNING|LOG_NOTICE|LOG_INFO|LOG_DEBUG)|(0-7)>]';

			if ( 'help' === self::$info['operation'] ) {
				self::$info['errors']['help'] = $usage;
			} elseif ( ! self::$info['operation'] ) {
				self::$info['errors']['mode'] =
					'Error: An operational mode (check/restore) is required.';
				self::$info['errors']['help'] = $usage;
			} else {
				Log::write( 'Operational mode set to "' . self::$info['operation'] . '".', LOG_INFO );
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
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 *
	 * @return bool
	 */
	public static function have_execution_functions() {
		require_once dirname( __DIR__ ) . '/admin/class-boldgrid-backup-admin-cli.php';

		$exec_functions = \Boldgrid_Backup_Admin_Cli::get_execution_functions();

		if ( empty( $exec_functions ) ) {
			self::$info['errors']['no_exec'] = 'Error: No available PHP executable functions.';
			Log::write( self::$info['errors']['no_exec'], LOG_ERR );
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
		$value = isset( $args[ $name ] ) ? urldecode( $args[ $name ] ) : null;

		return $value;
	}

	/**
	 * Get the log flag spcified in CLI arguments.
	 *
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see self::has_arg_flag()
	 *
	 * @return bool
	 */
	public static function get_log_flag() {
		if ( ! isset( self::$info['log'] ) ) {
			self::$info['log'] = (bool) self::get_arg_value( 'log' );
		}

		return self::$info['log'];
	}

	/**
	 * Get the log level argument spcified in CLI arguments.
	 *
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see self::get_arg_value()
	 *
	 * @return int
	 */
	public static function get_log_level() {
		$allowed = [
			'LOG_EMERG', // 0.
			'LOG_ALERT', // 1.
			'LOG_CRIT', // 2.
			'LOG_ERR', // 3.
			'LOG_WARNING', // 4.
			'LOG_NOTICE', // 5 -- Our default.
			'LOG_INFO', // 6.
			'LOG_DEBUG', // 7.
		];

		if ( ! isset( self::$info['log_level'] ) ) {
			self::$info['log_level'] = self::get_arg_value( 'log_level' );

			// Validate the log level.
			if ( in_array( self::$info['log_level'], $allowed, true ) ) {
				// Convert string to the constant integer value.
				self::$info['log_level'] = constant( self::$info['log_level'] );
			} elseif ( ! is_numeric( self::$info['log_level'] ) ||
				self::$info['log_level'] < LOG_EMERG || self::$info['log_level'] > LOG_DEBUG ) {
					// Invalid or no input value; set to the default.
					self::$info['log_level'] = LOG_NOTICE;
			}
		}

		return (int) self::$info['log_level'];
	}

	/**
	 * Get the notification flag spcified in CLI arguments.
	 *
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see self::has_arg_flag()
	 *
	 * @return bool
	 */
	public static function get_notify_flag() {
		if ( ! isset( self::$info['notify'] ) ) {
			self::$info['notify'] = (bool) self::get_arg_value( 'notify' );
		}

		return self::$info['notify'];
	}

	/**
	 * Get the notification email address spcified in CLI arguments.
	 *
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see self::get_arg_value()
	 *
	 * @return string|false
	 */
	public static function get_email_arg() {
		if ( ! isset( self::$info['email'] ) ) {
			self::$info['email'] = self::get_arg_value( 'email' );
		}

		return self::$info['email'];
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
	 * @see self::get_arg_value()
	 * @see self::has_arg_flag()
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
	 * Determine the restoration method.
	 *
	 * This method either validates and uses the specified method, or determines one to use.
	 * Valid values are: "ajax", "ziparchive", "pclzip", "cli".
	 * This method triggers reading the CLI arguments and appends to the self:$info array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_arg_value()
	 * @see Site_Check::is_siteurl_reachable()
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 *
	 * @return string|false
	 */
	public static function choose_method() {
		if ( ! isset( self::$info['method'] ) ) {
			self::$info['method'] = false;
			$method_arg           = self::get_arg_value( 'method' );
			$unavail_msg          = 'Error: The method "' . $method_arg . '" is not available.';

			switch ( $method_arg ) {
				case 'ajax':
					if ( ! ( Site_Check::is_siteurl_reachable() && ! empty( self::$info['restore_cmd'] ) ) ) {
						self::$info['errors']['method_unavailable'] = $unavail_msg;
						break;
					}
					self::$info['method'] = $method_arg;
					break;
				case 'cli':
					if ( ! ( \Boldgrid_Backup_Admin_Cli::call_command( 'unzip', $success, $return_var ) || $success || 0 === $return_var ) ) {
						self::$info['errors']['method_unavailable'] = $unavail_msg;
						break;
					}
					self::$info['method'] = $method_arg;
					break;
				case 'pclzip':
					if ( empty( self::$info['ABSPATH'] ) || ! file_exists( self::$info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php' ) ) {
						self::$info['errors']['method_unavailable'] = $unavail_msg;
						break;
					}
					self::$info['method'] = $method_arg;
					break;
				case 'ziparchive':
					if ( ! class_exists( 'ZipArchive' ) ) {
						self::$info['errors']['method_unavailable'] = $unavail_msg;
						break;
					}
					self::$info['method'] = $method_arg;
					break;
				case '':
					// Determine method.
					switch ( true ) {
						case class_exists( 'ZipArchive' ):
							self::$info['method'] = 'ziparchive';
							break;
						case file_exists( $info['ABSPATH'] . 'wp-admin/includes/class-pclzip.php' ):
							self::$info['method'] = 'pclzip';
							break;
						case \Boldgrid_Backup_Admin_Cli::call_command( 'unzip', $success, $return_var ) || $success || 0 === $return_var:
							self::$info['method'] = 'cli';
							break;
						default:
							self::$info['method'] = 'ajax';
							break;
					}
					break;
				default:
					self::$info['errors']['method_invalid'] =
						'Error: The specified restoration method is invalid; must be one of the following: ajax, cli, pclzip, ziparchive.';
					break;
			}
		}

		Log::write( 'Chosen restoration method: "' . self::$info['method'] . '".', LOG_INFO );

		return self::$info['method'];
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
			require_once dirname( __DIR__ ) . '/cron/class-boldgrid-backup-url-helper.php';
			$url_helper        = new \Boldgrid_Backup_Url_Helper();
			$url               = self::$info['siteurl'] . '/wp-content/plugins/boldgrid-backup/cli/env-info.php?secret=' . self::get_secret();
			self::$info['env'] = json_decode( $url_helper->call_url( $url ), true );
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
	 * Extarct a file from a backup archive ZIP file.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 *
	 * @param  string $extract_dir Extraction directory.
	 * @param  string $file        File to be extracted.
	 * @return bool
	 */
	public static function extract_file( $extract_dir, $file ) {
		$success = false;

		Log::write( 'Extracting "' . $file . '" into "' . $extract_dir . '".', LOG_DEBUG );

		switch ( true ) {
			case class_exists( 'ZipArchive' ):
				$zip = new \ZipArchive();
				if ( true === $zip->open( self::$info['filepath'] ) ) {
					$success = $zip->extractTo( $extract_dir, $file );
					$zip->close();
				} else {
					$message                = 'Error: Could not open the specified ZIP file path "' .
						self::$info['filepath'] . '".';
					self::$info['errors'][] = $message;
					Log::write( $message, LOG_ERR );
				}
				break;
			// @todo: Add PCLZip and unzip (CLI).
			default:
				$message                = 'Error: Could not extract files; ZipArchive unavailable.';
				self::$info['errors'][] = $message;
				Log::write( $message, LOG_ERR );
		}

		if ( ! $success || ! file_exists( $extract_dir . '/' . $file ) ) {
			$success                = false;
			$message                = 'Error: The file "' . $file .
				'" does not exist in the specified ZIP file path "' . self::$info['filepath'] .
				'".';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		}

		return $success;
	}

	/**
	 * Read an archive log JSON file from inside of a backup archive ZIP file, and return the
	 * contents in an array.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 * @see self::extract_file()
	 * @see self::read_json_file()
	 *
	 * @return array
	 */
	public static function read_zip_log() {
		if ( ! preg_match( '/\.zip$/', self::$info['filepath'] ) ) {
			self::$info['errors']['zip_path_invalid'] =
				'Error: Invalid ZIP file path specified; must end with ".zip".';
			Log::write( self::$info['errors']['zip_path_invalid'], LOG_ERR );
			return [];
		}

		$log_info         = [];
		$extract_dir      = dirname( self::$info['filepath'] );
		$archive_log_file = basename( preg_replace( '/\.zip$/', '.log', self::$info['filepath'] ) );
		$extract_filepath = $extract_dir . '/' . $archive_log_file;

		if ( ! file_exists( $extract_filepath ) ) {
			// Extract the log file.
			$success = self::extract_file( $extract_dir, $archive_log_file );

			if ( $success && file_exists( $extract_filepath ) ) {
				$log_info = self::read_json_file( $extract_filepath );
				unlink( $extract_filepath );
			}
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
	 * If validation fails, then errors are saved in the $info['errors'] class property.
	 *
	 * @since  1.9.0
	 * @access private
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 * @see self::get_zip_info()
	 * @see self::get_latest_info()
	 * @see self::read_json_file()
	 * @see self::read_zip_log()
	 *
	 * @return bool
	 */
	private static function get_restore_info() {
		self::$info['checked'] = time();

		// Initialize restore attempts counter, which is a default until merge with $results.
		self::$info['restore_attempts'] = 0;

		// Check for PHP safe mode.
		if ( ini_get( 'safe_mode' ) ) {
			$message                = 'Error: Cannot continue in PHP safe mode.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		if ( self::has_arg_flag( 'zip' ) ) {
			// Use the specified ZIP archive file path from the CLI arguments.
			if ( ! self::get_zip_info() ) {
				// Error already added by self::get_zip_info().
				return false;
			}
		} else {
			// Use the latest backup archive created by the plugin.  Get the backup results file.
			if ( ! self::get_latest_info() ) {
				// Error already added by self::get_latest_info().
				return false;
			}
		}

		if ( empty( self::$info['ABSPATH'] ) ) {
			$message                = 'Error: Unknown ABSPATH.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		if ( ! is_dir( self::$info['ABSPATH'] ) ) {
			$message                = 'Error: ABSPATH directory "' . self::$info['ABSPATH'] .
				'" does not exist or is not a directory.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		}

		if ( ! is_writable( self::$info['ABSPATH'] ) ) {
			$message                = 'Error: ABSPATH directory "' . self::$info['ABSPATH'] .
				'" is not writable.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		}

		if ( empty( self::$info['siteurl'] ) ) {
			$message                = 'Error: Unknown siteurl.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		}

		if ( empty( self::$info['db_filename'] ) ) {
			$message                = 'Error: Unknown database dump filename.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		} else {
			self::$info['db_filepath'] = self::$info['ABSPATH'] . self::$info['db_filename'];
		}

		self::get_env_info();

		return true;
	}

	/**
	 * Retrieve validated restoration information from the specified ZIP file path.
	 *
	 * If validation fails, then errors are saved in the $info['errors'] class property.
	 *
	 * @since  1.9.0
	 * @access private
	 * @static
	 *
	 * @see self::get_zip_arg()
	 * @see self::read_json_file()
	 * @see self::read_zip_log()
	 *
	 * @return bool
	 */
	private static function get_zip_info() {
		$zip_filepath = self::get_zip_arg();

		if ( empty( $zip_filepath ) ) {
			// Error already added by self::get_zip_arg().
			return false;
		}

		self::$info['filepath']    = $zip_filepath;
		self::$info['archive_key'] = 0;
		self::$info['restore_cmd'] = null;

		// Attempt to read information from the last backup's result file.
		$results                   = self::read_json_file( self::$results_file_path );
		self::$info['cron_secret'] = isset( $results['cron_secret'] ) ? $results['cron_secret'] : null;
		self::$info['site_title']  = isset( $results['site_title'] ) ? $results['site_title'] : null;

		// Retrieve information from the log file in the ZIP archive.
		$log_info = self::read_zip_log();

		if ( empty( $log_info ) ) {
			// Error already added by self::read_zip_log().
			return false;
		}

		self::$info = array_merge( self::$info, $log_info );

		if ( self::$info['siteurl'] && self::$info['backup_id'] && self::$info['cron_secret'] ) {
			// Build the restore command.
			self::$info['restore_cmd'] = 'php -d register_argc_argv=1 -qf "' . dirname( __DIR__ ) .
				'/boldgrid-backup-cron.php" ' .
				http_build_query(
					[
						'mode'             => 'restore',
						'siteurl'          => self::$info['siteurl'],
						'id'               => self::$info['backup_id'],
						'secret'           => self::$info['cron_secret'],
						'archive_key'      => self::$info['archive_key'],
						'archive_filename' => basename( self::$info['filepath'] ),
					],
					'',
					' '
				);
		}

			self::$info['timestamp'] = time();

			return true;
	}

	/**
	 * Retrieve validated restoration information from the latest backup archive.
	 *
	 * If validation fails, then errors are saved in the $info['errors'] class property.
	 *
	 * @since  1.9.0
	 * @access private
	 * @static
	 *
	 * @see self::has_arg_flag()
	 * @see \Boldgrid\Backup\Cli\Log::write()
	 * @see self::read_json_file()
	 * @see self::read_zip_log()
	 *
	 * @return bool
	 */
	private static function get_latest_info() {
		// If mode is not check or restore, then the restore info is not needed.
		if ( ! self::has_arg_flag( 'check' ) && ! self::has_arg_flag( 'restore' ) ) {
			return false;
		}

		// We require the results info file from the last full backup.
		if ( ! file_exists( self::$results_file_path ) ) {
			$message                = 'Error: Missing backup results file ("' .
				self::$results_file_path . '").';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		$results = self::read_json_file( self::$results_file_path );

		// Validate results file content.
		if ( empty( $results ) ) {
			$message                = 'Error: No backup results found.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		if ( empty( $results['filepath'] ) ) {
			$message                = 'Error: Unknown backup archive file path.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		// Check if archive exists.
		if ( ! file_exists( $results['filepath'] ) ) {
			$message                = 'Error: Backup archive file "' .
				$results['filepath'] . '" does not exist.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		// Get the archive log file and merge info.
		$archive_log_filepath = preg_replace( '/\.zip$/', '.log', $results['filepath'] );

		if ( ! file_exists( $archive_log_filepath ) ) {
			$message                = 'Error: Backup archive log file "' . $archive_log_filepath .
				'" does not exist.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		$log_info = self::read_json_file( $archive_log_filepath );

		// Validate results file content.
		if ( empty( $log_info ) ) {
			$message                = 'Error: No backup information found in the log file "' .
				$archive_log_filepath . '".';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
			return false;
		}

		// Merge info and results arrays.
		self::$info = array_merge( self::$info, $log_info, $results );

		if ( empty( self::$info['cron_secret'] ) ) {
			$message                = 'Error: Unknown cron_secret.';
			self::$info['errors'][] = $message;
			Log::write( $message, LOG_ERR );
		}

		// Ensure that there is a site title.
		if ( empty( self::$info['site_title'] ) ) {
			self::$info['site_title'] = 'WordPress';
		}

		return true;
	}
}
