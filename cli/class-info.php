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
	 * Per-install CLI/file secret.
	 *
	 * @since 1.17.3
	 * @access private
	 * @staticvar
	 *
	 * @var string|null
	 */
	private static $secret;

	/**
	 * Filename of the restore locator (points CLI at the backup directory).
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const RESTORE_LOCATOR_FILE = 'restore-locator.php';

	/**
	 * Filename used to persist the per-install secret in the backup directory.
	 *
	 * @since 1.17.3
	 * @var string
	 */
	const SECRET_FILENAME = '.boldgrid-cli-secret';

	/**
	 * Get the results file path.
	 *
	 * Stored in the backup directory (typically outside the web root) when possible.
	 * Falls back to a legacy plugin-relative cron/ path only when no secure storage
	 * directory is available yet.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see self::get_secret()
	 * @see self::get_secure_storage_dir()
	 *
	 * @return string
	 */
	public static function get_results_filepath() {
		if ( null === self::$results_file_path ) {
			$secret      = self::get_secret();
			$storage_dir = self::get_secure_storage_dir();

			if ( $storage_dir ) {
				self::$results_file_path = self::trailingslashit_path( $storage_dir ) . 'restore-info-' . $secret . '.json';
			} else {
				// Legacy fallback; callers with WordPress should migrate via ensure_secure_storage().
				self::$results_file_path = dirname( __DIR__ ) . '/cron/restore-info-' . $secret . '.json';
			}
		}

		return self::$results_file_path;
	}

	/**
	 * Get the path to the restore locator file.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return string
	 */
	public static function get_restore_locator_filepath() {
		return __DIR__ . '/' . self::RESTORE_LOCATOR_FILE;
	}

	/**
	 * Read the backup/storage directory from the restore locator.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return string|null Absolute directory path, or null if unavailable.
	 */
	public static function get_dir_from_locator() {
		$locator = self::get_restore_locator_filepath();

		if ( ! file_exists( $locator ) || ! is_readable( $locator ) ) {
			return null;
		}

		$dir = include $locator;

		if ( ! is_string( $dir ) || '' === $dir ) {
			return null;
		}

		$dir = self::untrailingslashit_path( $dir );

		return is_dir( $dir ) ? $dir : null;
	}

	/**
	 * Trim trailing directory separators (WP-independent).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function untrailingslashit_path( $path ) {
		return rtrim( (string) $path, '/\\' );
	}

	/**
	 * Add a trailing directory separator (WP-independent).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function trailingslashit_path( $path ) {
		return self::untrailingslashit_path( $path ) . '/';
	}

	/**
	 * Get a directory suitable for storing restore-info and the CLI secret.
	 *
	 * Prefer the backup directory via the restore locator. WordPress callers that
	 * already know the backup directory should pass it to ensure_secure_storage()
	 * / write_restore_locator() instead of relying on this method to bootstrap Core.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return string|null
	 */
	public static function get_secure_storage_dir() {
		return self::get_dir_from_locator();
	}

	/**
	 * Write the restore locator file used by CLI (and env-info) to find storage.
	 *
	 * The locator refuses direct HTTP execution; it only returns a path when included.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string $storage_dir Absolute backup/storage directory.
	 * @return bool
	 */
	public static function write_restore_locator( $storage_dir ) {
		$storage_dir = self::untrailingslashit_path( (string) $storage_dir );
		if ( '' === $storage_dir || ! is_dir( $storage_dir ) ) {
			return false;
		}

		/*
		 * Refuse direct HTTP/CLI execution of the locator. Prefer SCRIPT_FILENAME vs
		 * __FILE__ so auto_prepend_file (and similar pre-includes) cannot bypass the
		 * older get_included_files() <= 1 check; keep that check as a fallback.
		 */
		$contents = '<?php' . "\n" .
			'// phpcs:disable' . "\n" .
			'$__bg_script = isset( $_SERVER[\'SCRIPT_FILENAME\'] ) ? (string) $_SERVER[\'SCRIPT_FILENAME\'] : \'\';' . "\n" .
			'$__bg_direct = ( \'\' !== $__bg_script && @realpath( $__bg_script ) === @realpath( __FILE__ ) );' . "\n" .
			'if ( $__bg_direct || count( get_included_files() ) <= 1 ) {' . "\n" .
			"\t" . 'header( \'HTTP/1.1 403 Forbidden\' );' . "\n" .
			"\t" . 'exit;' . "\n" .
			'}' . "\n" .
			'return ' . var_export( $storage_dir, true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		$written = ( false !== file_put_contents( self::get_restore_locator_filepath(), $contents ) );

		if ( $written ) {
			// Reset cached path so the next get_results_filepath() uses the locator.
			self::$results_file_path = null;
		}

		return $written;
	}

	/**
	 * Persist the per-install secret into the secure storage directory.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string $secret      Secret string.
	 * @param string $storage_dir Absolute storage directory.
	 * @return bool
	 */
	public static function persist_secret( $secret, $storage_dir ) {
		$storage_dir = self::untrailingslashit_path( (string) $storage_dir );
		if ( '' === $storage_dir || ! is_dir( $storage_dir ) || ! self::is_valid_secret_format( $secret ) ) {
			return false;
		}

		$filepath = $storage_dir . '/' . self::SECRET_FILENAME;
		$written  = ( false !== file_put_contents( $filepath, $secret ) );

		if ( $written ) {
			@chmod( $filepath, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			self::$secret            = $secret;
			self::$results_file_path = null;
		}

		return $written;
	}

	/**
	 * Whether a secret string matches the expected format.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string $secret Secret.
	 * @return bool
	 */
	public static function is_valid_secret_format( $secret ) {
		return is_string( $secret ) && (bool) preg_match( '/^[0-9a-f]{32}$/', $secret );
	}

	/**
	 * Generate a per-install 32-hex secret via a CSPRNG.
	 *
	 * Fail closed (null) when no cryptographically strong source is available —
	 * never fall back to a deterministic value such as md5(false).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return string|null
	 */
	public static function generate_secret() {
		if ( ! function_exists( 'random_bytes' ) ) {
			$compat = dirname( __DIR__ ) . '/vendor/paragonie/random_compat/lib/random.php';
			if ( is_readable( $compat ) ) {
				require_once $compat; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}

		if ( function_exists( 'random_bytes' ) ) {
			try {
				return bin2hex( random_bytes( 16 ) );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fall through to openssl.
			}
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$strong = false;
			$bytes  = openssl_random_pseudo_bytes( 16, $strong );
			if ( false !== $bytes && $strong && 16 === strlen( $bytes ) ) {
				return bin2hex( $bytes );
			}
		}

		return null;
	}

	/**
	 * Copy a legacy cron/restore-info-*.json into secure storage when needed.
	 *
	 * Migrates using the legacy verify secret, the current secret, or any remaining
	 * restore-info file under cron/ (verify may already be gone).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string      $storage_dir   Absolute storage directory.
	 * @param string      $secret        Current secret (destination filename).
	 * @param string|null $legacy_secret Optional legacy verify secret.
	 * @return bool True when secure restore-info exists after this call.
	 */
	public static function migrate_legacy_restore_info( $storage_dir, $secret, $legacy_secret = null ) {
		$storage_dir    = self::untrailingslashit_path( (string) $storage_dir );
		$secure_results = self::trailingslashit_path( $storage_dir ) . 'restore-info-' . $secret . '.json';

		if ( file_exists( $secure_results ) ) {
			return true;
		}

		$cron_dir   = dirname( __DIR__ ) . '/cron';
		$candidates = [];

		if ( self::is_valid_secret_format( (string) $legacy_secret ) ) {
			$candidates[] = $cron_dir . '/restore-info-' . $legacy_secret . '.json';
		}
		if ( self::is_valid_secret_format( $secret ) ) {
			$candidates[] = $cron_dir . '/restore-info-' . $secret . '.json';
		}

		$files = @scandir( $cron_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! empty( $files ) ) {
			foreach ( preg_grep( '/^restore-info-.*\.json$/', $files ) as $file ) {
				$candidates[] = $cron_dir . '/' . $file;
			}
		}

		foreach ( array_unique( $candidates ) as $legacy_results ) {
			if ( ! file_exists( $legacy_results ) || ! is_readable( $legacy_results ) ) {
				continue;
			}

			$contents = file_get_contents( $legacy_results );
			if ( false === $contents ) {
				continue;
			}

			if ( false !== file_put_contents( $secure_results, $contents ) ) {
				@chmod( $secure_results, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return true;
			}
		}

		return false;
	}

	/**
	 * Read secret from secure storage (backup directory).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string|null $storage_dir Optional storage directory.
	 * @return string|null
	 */
	public static function read_secret_from_storage( $storage_dir = null ) {
		$storage_dir = $storage_dir ? self::untrailingslashit_path( $storage_dir ) : self::get_secure_storage_dir();
		if ( ! $storage_dir ) {
			return null;
		}

		$filepath = $storage_dir . '/' . self::SECRET_FILENAME;
		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			return null;
		}

		$secret = trim( (string) file_get_contents( $filepath ) );

		return self::is_valid_secret_format( $secret ) ? $secret : null;
	}

	/**
	 * Find a legacy webroot verify-*.php secret (pre-1.17.3).
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return string|null
	 */
	public static function get_legacy_verify_secret() {
		$files = @scandir( __DIR__ ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( empty( $files ) ) {
			return null;
		}

		$matches = preg_grep( '/^verify-[0-9a-f]{32}\.php$/', $files );
		if ( empty( $matches ) ) {
			return null;
		}

		$matches = array_values( $matches );
		if ( ! preg_match( '/^verify-([0-9a-f]{32})\.php$/', $matches[0], $match ) ) {
			return null;
		}

		return $match[1];
	}

	/**
	 * Delete legacy cli/verify-*.php files from the plugin directory.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return int Number of files deleted.
	 */
	public static function delete_legacy_verify_files() {
		$files   = @scandir( __DIR__ ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$deleted = 0;

		if ( empty( $files ) ) {
			return 0;
		}

		foreach ( preg_grep( '/^verify-[0-9a-f]{32}\.php$/', $files ) as $file ) {
			$path = __DIR__ . '/' . $file;
			if ( is_file( $path ) && @unlink( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete legacy cron/restore-info-*.json files from the plugin directory.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @return int Number of files deleted.
	 */
	public static function delete_legacy_cron_restore_info_files() {
		$cron_dir = dirname( __DIR__ ) . '/cron';
		$files    = @scandir( $cron_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$deleted  = 0;

		if ( empty( $files ) ) {
			return 0;
		}

		foreach ( preg_grep( '/^restore-info-.*\.json$/', $files ) as $file ) {
			$path = $cron_dir . '/' . $file;
			if ( is_file( $path ) && @unlink( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Ensure per-install secret and restore-info live outside the web-served plugin tree.
	 *
	 * Always generates a fresh secret when only a legacy webroot verify file exists, because
	 * release packaging has historically shipped a static verify file for all installs.
	 *
	 * @since 1.17.3
	 * @static
	 *
	 * @param string|null $storage_dir Optional backup directory override.
	 * @return string|null The storage directory used, or null on failure.
	 */
	public static function ensure_secure_storage( $storage_dir = null ) {
		$storage_dir = $storage_dir ? self::untrailingslashit_path( (string) $storage_dir ) : self::get_secure_storage_dir();

		if ( ! $storage_dir || ! is_dir( $storage_dir ) || ! is_writable( $storage_dir ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			return null;
		}

		self::write_restore_locator( $storage_dir );

		$secret        = self::read_secret_from_storage( $storage_dir );
		$legacy_secret = self::get_legacy_verify_secret();

		/*
		 * If we only have a legacy webroot verify file, do not reuse it — it may be the
		 * publicly distributed release copy. Generate a new per-install secret instead.
		 */
		if ( empty( $secret ) ) {
			$secret = self::generate_secret();
			if ( empty( $secret ) ) {
				// Fail closed; leave legacy files in place until a CSPRNG is available.
				return null;
			}
			self::persist_secret( $secret, $storage_dir );
		}

		self::$secret            = $secret;
		self::$results_file_path = null;

		// Migrate restore-info from cron/ before deleting legacy plugin-tree copies.
		self::migrate_legacy_restore_info( $storage_dir, $secret, $legacy_secret );

		self::delete_legacy_verify_files();
		self::delete_legacy_cron_restore_info_files();

		return $storage_dir;
	}

	/**
	 * Get secret.
	 *
	 * Used to secure scripts used outside of WordPress and to name restore-info files.
	 * Stored per-install in the backup directory (via locator), not as a webroot verify file.
	 *
	 * @since 1.14.10
	 *
	 * @return string
	 */
	public static function get_secret() {
		if ( self::is_valid_secret_format( (string) self::$secret ) ) {
			return self::$secret;
		}

		$secret = self::read_secret_from_storage();
		if ( $secret ) {
			self::$secret = $secret;
			return $secret;
		}

		/*
		 * Legacy webroot verify-*.php: readable for one request so emergency CLI can still
		 * locate an old restore-info file, but callers with a backup directory should run
		 * ensure_secure_storage() to rotate away from any shipped/static secret.
		 */
		$secret = self::get_legacy_verify_secret();
		if ( $secret ) {
			self::$secret = $secret;
			return $secret;
		}

		// Generate a new secret. Prefer secure storage; avoid writing verify files into cli/.
		$secret = self::generate_secret();
		if ( empty( $secret ) ) {
			// Fail closed: do not invent a weak/deterministic secret.
			return '';
		}

		$storage_dir = self::get_secure_storage_dir();

		if ( $storage_dir ) {
			self::persist_secret( $secret, $storage_dir );
			self::write_restore_locator( $storage_dir );
		} else {
			// Last resort before first backup directory exists (should be rare).
			self::$secret = $secret;
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
