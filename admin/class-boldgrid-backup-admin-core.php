<?php
/**
 * The admin-specific core functionality of the plugin
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
 * BoldGrid Backup Admin Core class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Core {
	/**
	 * Is the WordPress installation root directory (ABSPATH) writable?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_abspath_writable = null;

	/**
	 * User home directory.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $home_dir;

	/**
	 * Backup directory.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $backup_directory;

	/**
	 * Is running Windows?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_windows = null;

	/**
	 * Available compressors.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $available_compressors = array();

	/**
	 * Is PHP in safe mode?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_php_safemode = null;

	/**
	 * Is crontab available?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_crontab_available = null;

	/**
	 * Is mysqldump available?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $mysqldump_available = null;

	/**
	 * Is gzip available?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $gzip_available = null;

	/**
	 * Available execution functions.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $available_exec_functions = array();

	/**
	 * The WordPress version, if running.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $wp_version = '';

	/**
	 * Is WP-CRON enabled?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $wp_cron_enabled = null;

	/**
	 * Functionality tests completed?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $functionality_tested = false;

	/**
	 * Is functional?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_functional = null;

	/**
	 * Database backup file path.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $db_dump_filepath = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Add nav menu items.
		add_action( 'admin_menu', array(
			$this,
			'add_menu_items',
		), 1002 );

		// Handle callback for archive file download buttons.
		add_action( 'wp_ajax_download_archive_file',
			array(
				$this,
				'download_archive_file_callback',
			)
		);
	}

	/**
	 * Check if using Windows.
	 *
	 * @since 1.0
	 *
	 * @return bool TRUE is using Windows.
	 */
	public function is_windows() {
		// If was already checked, then return result from the class property.
		if ( null !== $this->is_windows ) {
			return $this->is_windows;
		}

		// Check if using Windows or Linux.
		$is_windows = ( 'win' === strtolower( substr( PHP_OS, 0, 3 ) ) );

		// Set as a class property.
		$this->is_windows = $is_windows;

		// Return result.
		return $is_windows;
	}

	/**
	 * Perform functionality tests.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function run_functionality_tests() {
		// If functionality tests were already performed, then just return status.
		if ( true === $this->functionality_tested && null !== $this->is_functional ) {
			return $this->is_functional;
		}

		// Configure a constant "EOL" for reporting.
		$this->configure_eol();

		// Is the WordPress installation root directory writable?
		$this->is_abspath_writable = is_writable( ABSPATH );

		// If not writable, then mark as not functional.
		if ( true !== $this->is_abspath_writable ) {
			$this->is_functional = false;
		}

		// Configure the backup directory path, or mark as not functional.
		if ( true !== $this->configure_backup_directory() ) {
			$this->is_functional = false;
		}

		// Test for available compressors, and add them to the array, or mark as not functional.
		if ( true !== $this->configure_compressors() ) {
			$this->is_functional = false;
		}

		// Test for crontab and wp-cron.
		if ( true !== $this->is_crontab_available() && true !== $this->wp_cron_enabled() ) {
			$this->is_functional = false;
		}

		// Test for mysqldump.
		if ( true !== $this->is_mysqldump_available() ) {
			$this->is_functional = false;
		}

		// Test for gzip.
		$this->is_gzip_available();

		// Test for PHP safe mode.
		$this->is_php_safemode();

		// If WordPress is running, then set the version.
		$this->set_wp_version();

		// Save result, if not previously saved.
		if ( null === $this->is_functional ) {
			$this->is_functional = true;
		}

		// Mark as completed.
		$this->functionality_tested = true;

		// If test failed, then display a notice.
		if ( true !== $this->is_functional ) {
			// Display an error notice.
			add_action( 'admin_footer', array(
				$this,
				'notice_functionality_fail',
			) );
		}

		return $this->is_functional;
	}

	/**
	 * Configure EOL.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return null
	 */
	private function configure_eol() {
		// If constant is already defined, then abort.
		if ( true === defined( 'EOL' ) ) {
			return;
		}

		// Determine value.
		if ( 'cli' !== php_sapi_name() ) {
			$eol = '<br />' . PHP_EOL;
		} else {
			$eol = PHP_EOL;
		}

		// Define the constant.
		define( 'EOL', $eol );

		return;
	}

	/**
	 * Get the user home directory.
	 *
	 * @since 1.0
	 *
	 * @return string The path to the user home directory.
	 */
	public function get_home_directory() {
		// If gome directory was already set, then return it.
		if ( false === empty( $this->home_dir ) ) {
			return $this->home_dir;
		}

		// For Windows and Linux.
		if ( true === $this->is_windows() ) {
			// Windows.
			$home_drive = ( false === empty( $_SERVER['HOMEDRIVE'] ) ? $_SERVER['HOMEDRIVE'] : null );
			$home_path = ( false === empty( $_SERVER['HOMEPATH'] ) ? $_SERVER['HOMEPATH'] : null );

			if ( false === ( empty( $home_drive ) || empty( $home_path ) ) ) {
				$home_dir = $home_drive . $home_path;
			}

			// If still unknown, then try getenv USERPROFILE.
			if ( true === empty( $home_dir ) ) {
				$home_dir = getenv( 'USERPROFILE' );
			}
		} else {
			// Linux.
			$home_dir = getenv( 'HOME' );

			if ( true === empty( $home_dir ) ) {
				$home_dir = ( false === empty( $_SERVER['HOME'] ) ? $_SERVER['HOME'] : null );
			}
		}

		// If still unknown, then try posix_getpwuid and posix_getuid.
		if ( true === empty( $home_dir ) && function_exists( 'posix_getuid' ) &&
			 function_exists( 'posix_getpwuid' ) ) {
			$user = posix_getpwuid( posix_getuid() );

			$home_dir = ( false === empty( $user['dir'] ) ? $user['dir'] : null );
		}

		// Could not find the user home directory, so use the WordPress root directory.
		if ( true === empty( $home_dir ) ) {
			$home_dir = ABSPATH;
		}

		// Use rtrim the $home_dir to strip any trailing slashes.
		$home_dir = rtrim( $home_dir, '\\/' );

		// Record the home directory.
		$this->home_dir = $home_dir;

		// Return the directory path.
		return $home_dir;
	}

	/**
	 * Configure backup directory path.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool
	 */
	private function configure_backup_directory() {
		// Get the user home directory.
		$home_dir = $this->get_home_directory();

		// Define the backup directory name.
		$backup_directory_path = $home_dir . '/boldgrid_backup';

		// Check if the backup directory exists.
		$backup_directory_exists = is_dir( $backup_directory_path );

		// If the backup directory does not exist, then attempt to create it.
		if ( false === $backup_directory_exists ) {
			$backup_directory_created = mkdir( $backup_directory_path, 0700 );

			// If mkdir failed, then abort.
			if ( false === $backup_directory_created ) {
				return false;
			}
		}

		// Check if the backup directory is writable, abort if not.
		if ( false === is_writable( $backup_directory_path ) ) {
			return false;
		}

		// Record the backup directory path.
		$this->backup_directory = $backup_directory_path;

		return true;
	}

	/**
	 * Add an archive compressor to the available list.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $compressor A name of a compressor.
	 * @return null
	 */
	private function add_compressor( $compressor = null ) {
		if ( false === empty( $compressor ) ) {
			$this->available_compressors[] = $compressor;
		}

		return;
	}

	/**
	 * Is a specific archive compressor available?
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $compressor A string to identify a compressor.
	 * @return bool
	 */
	private function is_compressor_available( $compressor = null ) {
		// If input parameter is empty, then fail.
		if ( true === empty( $compressor ) ) {
			return false;
		}

		// Check the array to see if the specified compressor is available.
		$is_available = in_array( $compressor, $this->available_compressors, true );

		return $is_available;
	}

	/**
	 * Test for available archive compressors, and add them to the array in a preferred order.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool
	 */
	private function configure_compressors() {
		// Initialize $is_available.
		$is_available = false;

		// PHP zip (ZipArchive).
		if ( extension_loaded( 'zip' ) && class_exists( 'ZipArchive' ) ) {
			$this->add_compressor( 'php_zip' );
			$is_available = true;
		}

		// PHP bz2 (Bzip2).
		if ( extension_loaded( 'bz2' ) && function_exists( 'bzcompress' ) ) {
			$this->add_compressor( 'php_bz2' );
			$is_available = true;
		}

		// PHP zlib (Zlib).
		if ( extension_loaded( 'zlib' ) && function_exists( 'gzwrite' ) ) {
			$this->add_compressor( 'php_zlib' );
			$is_available = true;
		}

		// PHP lzf (LZF).
		if ( function_exists( 'lzf_compress' ) ) {
			$this->add_compressor( 'php_lzf' );
			$is_available = true;
		}

		// System tar.
		if ( file_exists( '/bin/tar' ) && is_executable( '/bin/tar' ) ) {
			$this->add_compressor( 'system_tar' );
			$is_available = true;
		}

		// System zip.
		if ( file_exists( '/usr/bin/zip' ) && is_executable( '/usr/bin/zip' ) ) {
			$this->add_compressor( 'system_zip' );
			$is_available = true;
		}

		return $is_available;
	}

	/**
	 * Get the available execution functions.
	 *
	 * @since 1.0
	 *
	 * @return array An array of function names.
	 */
	public function get_execution_functions() {
		// If the array already has elements, then return the array.
		if ( false === empty( $this->available_exec_functions ) ) {
			return $this->available_exec_functions;
		}

		// If PHP is in safe mode, then return an empty array.
		$safe_mode = ini_get( 'safe_mode' );

		if ( false === empty( $safe_mode ) ) {
			return array();
		}

		// Get the PHP disable_functions list.
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		// Make an array of execution functions.
		$exec_functions = array(
			'popen',
			'proc_open',
			'exec',
			'shell_exec',
			'passthru',
			'system',
		);

		// Iterate through the array and remove disabled functions.
		foreach ( $exec_functions as $exec_function ) {
			if ( true === in_array( $exec_function, $disabled, true ) ) {
				unset( $exec_functions[ $exec_function ] );
			}
		}

		// Save the array of execution functions.
		$this->available_exec_functions = $exec_functions;

		return $exec_functions;
	}

	/**
	 * Execute a system command using an array of execution functions.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $command A command string to be executed.
	 * @param array  $available_exec_functions An array of available execution functions.
	 * @param bool   $success or failure of the operation, passed back to the caller.
	 * @return string|bool Returns the command output or FALSE on error.
	 */
	private function execute_command( $command, $available_exec_functions = array(), &$success = false ) {
		// If no command was passed, then fail.
		if ( true === empty( $command ) ) {
			return false;
		}

		// If there are no supplied execution functions, then retrieve available ones.
		if ( true === empty( $available_exec_functions ) ) {
			$available_exec_functions = $this->get_execution_functions();
		}

		// Disable stderr.
		if ( false === $this->is_windows() && false === strpos( $command, '2>/dev/null' ) ) {
			$command .= ' 2>/dev/null';
		}

		// Initialize $success.
		$success = false;

		// Test getting output using available execution functions, until one is successful.
		foreach ( $available_exec_functions as $exec_function ) {
			switch ( $exec_function ) {
				case 'exec' :
					exec( $command, $out, $return_var );

					// If the exit status is int(0), then it was successful.
					if ( 0 === $return_var ) {
						$output = implode( PHP_EOL, $out );

						$success = true;

						break 2;
					} else {
						$output = false;
					}

					break 2;

				case 'passthru' :
					// If output buffering is enabled, then use passthru.
					if ( ob_start() ) {
						passthru( $command, $return_var );

						// Get current buffer contents and delete current output buffer.
						$output = ob_get_clean();

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				case 'popen' :
					$handle = popen( $command, 'r' );

					$output = fread( $handle, 4096 );

					/*
					 * If handle is a valid resource, then check for success.
					 */
					if ( false !== $handle ) {
						// Close the process handle and get the return status.
						$return_var = pclose( $handle );

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							// Bad exit status code (non-zero).
							$output = false;
						}
					} else {
						// Failed to create a process handle.
						$output = false;
					}

					break 2;

				case 'proc_open' :
					// Create the descriptor spec array.
					$descriptorspec = array(
						0 => array(
							'pipe',
							'r',
						),
						1 => array(
							'pipe',
							'w',
						),
						2 => array(
							'pipe',
							'w',
						),
					);

					// Open a process handle.
					$handle = proc_open( $command, $descriptorspec, $pipes );

					if ( false !== is_resource( $handle ) ) {
						// Close unused pipe[0].
						fclose( $pipes[0] );

						// Read output from pipe[1].
						$output = stream_get_contents( $pipes[1] );

						// Close pipe[1].
						fclose( $pipes[1] );

						// Close unused pipe[0].
						fclose( $pipes[2] );

						// Close the process handle and get the return status.
						$return_var = proc_close( $handle );

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				case 'shell_exec' :
					$output = shell_exec( $command );

					if ( false === strpos( $output, 'command not found' ) ) {
						$success = true;

						break 2;
					} else {
						$output = false;
					}

					break 2;

				case 'system' :
					// If output buffering is enabled, then use system.
					if ( ob_start() ) {
						system( $command, $return_var );

						// Get current buffer contents and delete current output buffer.
						$output = ob_get_clean();

						// If the exit status is int(0), then it was successful.
						if ( 0 === $return_var ) {
							$success = true;

							break 2;
						} else {
							$output = false;
						}
					}

					break 2;

				default :
					break;
			}
		}

		// If there is output, then trim it.
		if ( false === empty( $output ) ) {
			$output = trim( $output );
		}

		// If the command was not successful, then return FALSE.
		if ( true !== $success ) {
			return false;
		}

		// Success.
		return $output;
	}

	/**
	 * Is mysqldump available?
	 *
	 * Once the success is determined, the result is stored in a class property.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_mysqldump_available() {
		// If this test was already completed, then just return the result.
		if ( null !== $this->mysqldump_available ) {
			return $this->mysqldump_available;
		}

		// Create the test command.
		$command = 'mysqldump -V';

		// Test to see if the mysqldump command is available.
		$output = $this->execute_command( $command, null, $success );

		// Set class property.
		$this->mysqldump_available = ( $success || (bool) $output );

		return $this->mysqldump_available;
	}
	/**
	 * Is gzip available?
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_gzip_available() {
		// If this test was already completed, then just return the result.
		if ( null !== $this->gzip_available ) {
			return $this->gzip_available;
		}

		// Create the test command.
		$command = 'gzip -V';

		// Test to see if the gzip command is available.
		$output = $this->execute_command( $command, null, $success );

		// Set class property.
		$this->gzip_available = ( $success || (bool) $output );

		return $this->gzip_available;
	}

	/**
	 * Is crontab available?
	 *
	 * Once the success is determined, the result is stored in a class property.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_crontab_available() {
		// If this test was already completed, then just return the result.
		if ( null !== $this->is_crontab_available ) {
			return $this->is_crontab_available;
		}

		// Create the test command.
		$command = 'crontab -l';

		// Test to see if the crontab command is available.
		$output = $this->execute_command( $command, null, $success );

		// Set class property.
		$this->is_crontab_available = ( $success || (bool) $output );

		return $this->is_crontab_available;
	}

	/**
	 * Is PHP running in safe mode?
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_php_safemode() {
		// If this test was already completed, then just return the result.
		if ( null !== $this->is_php_safemode ) {
			return $this->is_php_safemode;
		}

		// Check if PHP is in safe mode.
		$this->is_php_safemode = (bool) ini_get( 'safe_mode' );

		// Return result.
		return $this->is_php_safemode;
	}

	/**
	 * Set the wp_verison, if WordPress is running.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global string $wp_version The WordPress version string.
	 *
	 * @return bool
	 */
	private function set_wp_version() {
		// If WordPress is not running, then fail.
		if ( false === defined( 'WPINC' ) ) {
			return false;
		}

		// Get the WordPress version.
		global $wp_version;

		// If the WordPress version is in the global, then record it as WordPress is running.
		if ( false === empty( $wp_version ) ) {
			$this->wp_version = $wp_version;

			return true;
		}

		return false;
	}

	/**
	 * Is WP-CRON enabled?
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function wp_cron_enabled() {
		// If this test was already completed, then just return the result.
		if ( null !== $this->wp_cron_enabled ) {
			return $this->wp_cron_enabled;
		}

		// Get the WP-CRON array.
		$wp_cron_array = array();

		if ( true === function_exists( '_get_cron_array' ) ) {
			$wp_cron_array = _get_cron_array();
		}

		// Check for the DISABLE_WP_CRON constant and value.
		$disable_wp_cron = false;

		if ( true === defined( 'DISABLE_WP_CRON' ) ) {
			$disable_wp_cron = DISABLE_WP_CRON;
		}

		$this->wp_cron_enabled = ( false === empty( $wp_cron_array ) && false === $disable_wp_cron );

		return $this->wp_cron_enabled;
	}

	/**
	 * Disk space report.
	 *
	 * @since 1.0
	 *
	 * @return array An array containing disk space (total, used, available, WordPress directory).
	 */
	public function get_disk_space() {
		// Get the HOME environment variable.
		$env_home = getenv( 'HOME' );

		// Locate the home directory by environment variable or use parent of ABSPATH.
		$home_dir = ( false === empty( $env_home ) ? $env_home : dirname( ABSPATH ) );

		// Trim any trailing slash (or backslash in Windows).
		$home_dir = rtrim( $home_dir, DIRECTORY_SEPARATOR );

		// If the home directory is not defined, not a directory or not writable, then return 0.00.
		if ( true === empty( $home_dir ) || false === is_dir( $home_dir ) ||
			 false === is_writable( $home_dir ) ) {
			$return = array(
				0.00,
				0.00,
				0.00,
			);

			return $return;
		}

		// Get filesystem disk space information.
		$disk_total_space = disk_total_space( $home_dir );
		$disk_free_space = disk_free_space( $home_dir );
		$disk_used_space = $disk_total_space - $disk_free_space;

		// Initialize $wp_root_size.
		$wp_root_size = 0;

		// Get the size of the WordPress installation root directory (ABSPATH).
		if ( true === $this->is_windows() ) {
			/*
			 * Windows.
			 * @link https://msdn.microsoft.com/en-us/library/z9ty6h50%28v=vs.84%29.aspx
			 * @link https://msdn.microsoft.com/en-us/library/f1xtf7ta%28v=vs.84%29.aspx
			 * @link https://msdn.microsoft.com/en-us/library/aa243182%28v=vs.60%29.aspx
			 */
			$obj = new COM( 'scripting.filesystemobject' );
			if ( is_object( $obj ) ) {
				$wp_root_size = $obj->getfolder( ABSPATH )->size;
				unset( $obj );
			}
		} else {
			// Linux.
			$command = 'du -sb ' . ABSPATH . ' | cut -f1';
			$wp_root_size = trim( $this->execute_command( $command ) );
		}

		// Create the return array.
		$return = array(
			$disk_total_space,
			$disk_used_space,
			$disk_free_space,
			$wp_root_size,
		);

		// Return the disk information array.
		return $return;
	}

	/**
	 * Get database size.
	 *
	 * @since 1.0
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return int The total size of the database (in bytes).
	 */
	public function get_database_size() {
		// If the database name constant is not defined, then fail.
		if ( false === defined( 'DB_NAME' ) ) {
			return 0;
		}

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Build query.
		$query = $wpdb->prepare( "SELECT SUM(`data_length` + `index_length`) FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`='%s';", DB_NAME );

		// Check query.
		if ( true === empty( $query ) ) {
			return 0;
		}

		// Get the result.
		$result = $wpdb->get_row( $query, ARRAY_N );

		// If there was an error or nothing returned, then fail.
		if ( empty( $result ) ) {
			return 0;
		}

		// Return result.
		return $result[0];
	}

	/**
	 * Convert bytes to a human-readable measure.
	 *
	 * @since 1.0
	 *
	 * @param int $bytes Number of bytes.
	 * @param int $decimals Number of decimal places.
	 * @return string
	 */
	public function bytes_to_human( $bytes = 0, $decimals = 2 ) {
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
	 * Functionality test report.
	 *
	 * Perform tests/checks for backup functionality, and provide a report.
	 *
	 * @since 1.0
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return null
	 */
	public function functionality_report() {
		// Perform functionality tests.
		$this->run_functionality_tests();

		// Set column size.
		$column_size = 30;

		// Create report.
		$report = 'BoldGrid Backup - Functionality test report:' . EOL;

		// Is the WordPress installation root directory writable?
		$report .= str_pad( 'WordPress directory writable? ', $column_size ) .
			 ( true === $this->is_abspath_writable ? 'Yes' : 'No' ) . EOL;

		// Does the backup directory exist and writable?
		$report .= str_pad( 'Backup directory exists? ', $column_size ) .
			 ( true === empty( $this->backup_directory ) ? 'No' : 'Yes' ) . EOL;

		// Check for available compressors.
		$report .= 'Available compressors:' . EOL;

		$report .= str_pad( 'PHP ZipArchive? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'php_zip' ) ? 'Yes' : 'No' ) . EOL;

		$report .= str_pad( 'PHP Bzip2? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'php_bz2' ) ? 'Yes' : 'No' ) . EOL;

		$report .= str_pad( 'PHP Zlib? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'php_zlib' ) ? 'Yes' : 'No' ) . EOL;

		$report .= str_pad( 'PHP LZF? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'php_lzf' ) ? 'Yes' : 'No' ) . EOL;

		$report .= str_pad( 'System TAR? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'system_tar' ) ? 'Yes' : 'No' ) . EOL;

		$report .= str_pad( 'System ZIP? ', $column_size ) .
			 ( true === $this->is_compressor_available( 'system_zip' ) ? 'Yes' : 'No' ) . EOL;

		// Is PHP in safe mode?
		$report .= str_pad( 'PHP in safe mode? ', $column_size ) .
			 ( true === $this->is_php_safemode ? 'Yes' : 'No' ) . EOL;

		// Is the system mysqldump available?
		$report .= str_pad( 'System mysqldump available? ', $column_size ) .
			 ( true === $this->mysqldump_available ? 'Yes' : 'No' ) . EOL;

		// Is the system crontab available?
		$report .= str_pad( 'System crontab available? ', $column_size ) .
			 ( true === $this->is_crontab_available ? 'Yes' : 'No' ) . EOL;

		// Get the WordPress version.
		$report .= str_pad( 'WordPress version: ', $column_size ) . $this->wp_version . EOL;

		// Is WP-CRON enabled?
		$report .= str_pad( 'Is WP-CRON enabled? ', $column_size ) .
			 ( $this->wp_cron_enabled ? 'Yes' : 'No' ) . EOL;

		// Report if functionality test passed or not.
		$report .= str_pad( 'Functionality test status: ', $column_size ) .
			 ( $this->is_functional ? 'PASS' : 'FAIL' ) . EOL;

		// Get disk space information array.
		$disk_space = $this->get_disk_space();

		$report .= str_pad( 'Disk total space: ', $column_size ) .
			 $this->bytes_to_human( $disk_space[0] ) . EOL;

		$report .= str_pad( 'Disk used space: ', $column_size ) .
			 $this->bytes_to_human( $disk_space[1] ) . EOL;

		$report .= str_pad( 'Disk free space: ', $column_size ) .
			 $this->bytes_to_human( $disk_space[2] ) . EOL;

		$report .= str_pad( 'WordPress directory size: ', $column_size ) .
			 $this->bytes_to_human( $disk_space[3] ) . EOL;

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Get the database size.
		$db_size = $this->get_database_size();

		$report .= str_pad( 'Database size: ', $column_size ) . $this->bytes_to_human( $db_size ) .
			 EOL;

		// Get the WordPress database charset.
		$report .= str_pad( 'WordPress database charset: ', $column_size ) . $wpdb->charset . EOL;

		// Get the WordPress database collate.
		$report .= str_pad( 'WordPress database collate: ', $column_size ) . $wpdb->collate . EOL;

		// Get archive info.
		$archive_info = $this->archive_files( false );

		if ( false === empty( $archive_info['error'] ) ) {
			$report .= str_pad( 'Compressor error: ', $column_size ) . $archive_info['error'] . EOL;

			if ( false === empty( $archive_info['error_code'] ) ) {
				$report .= str_pad( 'Compressor error code: ', $column_size ) .
					 $archive_info['error_code'] . EOL;

				if ( false === empty( $archive_info['error_message'] ) ) {
					$report .= str_pad( 'Compressor error message: ', $column_size ) .
						 $archive_info['error_message'] . EOL;
				}
			}
		}

		// Include archive statistics.
		if ( false === empty( $archive_info['total_size'] ) ) {
			$report .= str_pad( 'Backup archive size: ', $column_size ) .
				$this->bytes_to_human( $archive_info['total_size'] ) . EOL;

			// Calculate possible disk free space after a backup, using the entire WP directory size.
			$disk_free_post = $disk_space[2] - $archive_info['total_size'] - $db_size;
		} else {
			// Calculate possible disk free space after a backup, using the entire WP directory size.
			$disk_free_post = $disk_space[2] - $disk_space[3] - $db_size;
		}

		if ( $disk_free_post > 0 ) {
			$report .= str_pad( 'Estimated free space after backup: ', $column_size )
			. $this->bytes_to_human( $disk_free_post ) . EOL;
		} else {
			$report .= 'THERE IS NOT ENOUGH SPACE TO PERFORM A BACKUP!' . EOL;
		}

		// End of testing.
		$report .= 'End of functionality test report.' . EOL;

		// Print report.
		echo  __( $report );

		return;
	}

	/**
	 * Add menu items.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function add_menu_items() {
		add_menu_page( 'BoldGrid Backup', 'BoldGrid Backup', 'administrator', 'boldgrid-backup',
			array(
				$this,
				'page_backup_home',
			), 'none'
		);

		add_submenu_page( 'boldgrid-backup', 'Backup Settings', 'Backup Settings', 'administrator',
			'boldgrid-backup-settings', array(
			$this,
			'page_backup_settings',
			)
		);

		add_submenu_page( 'boldgrid-backup', 'Functionality Test', 'Functionality Test',
			'administrator', 'boldgrid-backup-test', array(
			$this,
			'page_backup_test',
			)
		);

		return;
	}

	/**
	 * Backup the WordPress database.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool
	 */
	private function backup_database() {
		// Check if functional.
		if ( true !== $this->run_functionality_tests() ) {
			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		// If mysqldump is not available, then fail.
		if ( true !== $this->mysqldump_available ) {
			return false;
		}

		// Create a mysql defaults file.
		$defaults_filepath = $this->backup_directory . '/mysqldump.cnf';

		$defaults_file_data = '[client]' . PHP_EOL . 'host=' . DB_HOST . PHP_EOL . 'user=' . DB_USER .
			 PHP_EOL . 'password=' . DB_PASSWORD . PHP_EOL;

		file_put_contents( $defaults_filepath, $defaults_file_data );

		// Check if the defaults file was written.
		if ( false === file_exists( $defaults_filepath ) ) {
			return false;
		}

		chmod( $defaults_filepath, 0600 );

		// Create a file path for the dump file.
		$db_dump_filepath = $this->backup_directory . '/' . DB_NAME . '.' . date( 'Ymd-His' ) .
			 '.sql';

		// If gzip is available, then add ".gz" to the file extension.
		if ( true === $this->gzip_available ) {
			$db_dump_filepath .= '.gz';
		}

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		// Backup the database with mysqldump.
		$command = 'mysqldump --defaults-file=' . $defaults_filepath .
			 ' --dump-date --tz-utc --databases ' . DB_NAME;

		if ( true === $this->gzip_available ) {
			$command .= ' | gzip -c';
		}

		$command .= ' > ' . $db_dump_filepath;

		// Set the PHP timeout limit to at least 300 seconds.
		set_time_limit(
		( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 ) );

		// Execute the command.
		$status = $this->execute_command( $command );

		// Remove the defaults file.
		unlink( $defaults_filepath );

		// Check if the dump file was written.
		if ( false === file_exists( $db_dump_filepath ) ) {
			return false;
		}

		// Limit file permissions to the dump file.
		chmod( $db_dump_filepath, 0600 );

		// Return the status.
		if ( false !== $status ) {
			$status = true;
		}

		return $status;
	}
	/**
	 * Translate a ZipArchive error code into a human-readable message.
	 *
	 * @since 1.0
	 *
	 * @param int $error_code An error code from a ZipArchive constant.
	 * @return string An error message.
	 */
	public function translate_zip_error( $error_code = null ) {
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

	/**
	 * Get a recursive file list of the WordPress installation root directory.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $dirpath A directory path, defaults to ABSPATH.
	 * @return array An array of absolute file paths, relative paths, and file sizes.
	 */
	private function get_filelist( $dirpath = ABSPATH ) {
		// Initialize $files.
		$files = array();

		// Initialize $filelist.
		$filelist = array();

		// Identify the plugin files to add.
		// Include the custom RecursiveFilterIterator class file.
		require_once dirname( __FILE__ ) . '/class-boldgrid-backup-admin-recursivefilteriterator.php';

		// Create a RecursiveDirectoryIterator object.
		$iterator = new RecursiveDirectoryIterator( $dirpath, FilesystemIterator::SKIP_DOTS );

		// Create a RecursiveFilterIterator object; uses an array mask.
		$iterator_filter = new Boldgrid_Backup_Admin_RecursiveFilterIterator( $iterator );

		$files = new RecursiveIteratorIterator( $iterator_filter );

		// Build an array of filenames and relative paths.
		foreach ( $files as $file ) {
			// Only add files.
			if ( true === $file->isDir() ) {
				continue;
			}

			// The real path.
			$filepath = $file->getRealPath();

			// The relative path inside the ZIP file.
			$relative_path = substr( $filepath, strlen( $dirpath ) );

			// Get the file size.
			$filesize = $file->getSize();

			// Add current file to archive.
			$filelist[] = array(
				$filepath,
				$relative_path,
				$filesize,
			);
		}

		return $filelist;
	}

	/**
	 * Create a site identifier.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return string The site identifier.
	 */
	private function create_site_id() {
		// Get the siteurl.
		if ( is_multisite() ) {
			$siteurl = get_site_url( 1 );
		} else {
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
	 * Generate an new archive file path.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $extension An optional file extension.
	 * @return string An archive file path.
	 */
	private function generate_archive_path( $extension = null ) {
		// Create a site identifier.
		$site_id = $this->create_site_id();

		// Create a file path with no extension (added later).
		$filepath = $this->backup_directory . '/boldgrid-backup-' . $site_id . '-' .
			 date( 'Ymd-His' );

		// If specified, add an extension.
		if ( false === empty( $extension ) ) {
			// Trim the input extension.
			$extension = trim( $extension, ' .' );

			$filepath .= '.' . $extension;
		}

		return $filepath;
	}

	/**
	 * Create an archive file containing the WordPress files.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param bool $save A switch to save the archive file. Default is FALSE.
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	private function archive_files( $save = false, $dryrun = false ) {
		// Check if functional.
		if ( true !== $this->run_functionality_tests() ) {
			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		// Initialize return array and add "compressor" and "save" keys.
		$info = array(
			'dryrun' => $dryrun,
			'compressor' => null,
			'filesize' => 0,
			'save' => $save,
		);

		// Determine which compressor to use (first available).
		foreach ( $this->available_compressors as $available_compressor ) {
			$info['compressor'] = $available_compressor;
			break;
		}

		// If there is no available compressor, then fail.
		if ( null === $info['compressor'] ) {
			return array(
				'error' => 'No available compressor.',
			);
		}

		// Prevent this script from dying.
		ignore_user_abort( true );

		// Start timer.
		$time_start = microtime( true );

		// Backup the database, if saving an archive file and not a dry run.
		if ( true === $save && true !== $dryrun ) {
			$status = $this->backup_database();
		}

		// Check database backup status, if saving, and not a dry run.
		if ( true === $save && true !== $dryrun && true !== $status ) {
			return array(
				'error' => 'Error making a database backup.',
			);
		}

		// Get the file list.
		$filelist = $this->get_filelist( ABSPATH );

		// Initialize total_size.
		$info['total_size'] = 0;

		// If not saving, then just return info.
		if ( true !== $save ) {
			foreach ( $filelist as $fileinfo ) {
				// Add the file size to the total.
				$info['total_size'] += $fileinfo[2];
			}

			return $info;
		}

		// Add the database dump file to the file list.
		$db_relative_path = substr( $this->db_dump_filepath, strlen( $this->backup_directory ) + 1 );

		$db_file_array = array(
			$this->db_dump_filepath,
			$db_relative_path,
			filesize( $this->db_dump_filepath ),
		);

		array_unshift( $filelist, $db_file_array );

		// Set the PHP timeout limit to at least 300 seconds.
		set_time_limit( ( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 ) );

		/*
		 * Use the chosen compressor to build an archive.
		 * If the is no available compressor, then return an error.
		 */
		switch ( $info['compressor'] ) {
			case 'php_zip' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'zip' );

				if ( true !== $dryrun ) {
					$zip = new ZipArchive();

					// Create the archive file.
					$status = $zip->open( $info['filepath'], ZipArchive::CREATE );

					if ( true !== $status ) {
						return array(
							'error' => 'Cannot create ZIP archive file "' . $info['filepath'] . '".',
							'error_code' => $status,
							'error_message' => $this->translate_zip_error( $status ),
						);
					}
				}

				// Add files to the archive.
				foreach ( $filelist as $fileinfo ) {

					// Add current file to archive, if not a dry run.
					if ( true !== $dryrun ) {
						$zip->addFile( $fileinfo[0], $fileinfo[1] );
					}

					// Add the file size to the total.
					$info['total_size'] += $fileinfo[2];
				}

				// If a dry run, then break out of the switch.
				if ( true === $dryrun ) {
					break;
				}

				// Close (save) the ZIP file.
				if ( false === $zip->close() ) {
					return array(
						'error' => 'Cannot save ZIP archive file "' . $info['filepath'] . '".',
					);
				} else {
					if ( false === file_exists( $info['filepath'] ) ) {
						return array(
							'error' => 'The archive file "' . $info['filepath'] .
								 '" was not written.',
						);
					}
				}

				break;
			case 'php_bz2' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'b2z' );
				break;
			case 'php_zlib' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'zlib' );
				break;
			case 'php_lzf' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'lzf' );
				break;
			case 'system_tar' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'tar.gz' );
				break;
			case 'system_zip' :
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'zip' );
				break;
			default :
				return array(
					'error' => 'No available compressor',
				);
				break;
		}

		if ( true !== $dryrun ) {
			// Modify the archive file permissions to help protect from public access.
			chmod( $info['filepath'], 0600 );

			// Add some statistics to the return.
			$info['filesize'] = filesize( $info['filepath'] );
		}

		// Stop timer.
		$time_stop = microtime( true );

		// Calculate duration.
		$info['duration'] = number_format( ( $time_stop - $time_start ), 4, '.', '' );

		// Get settings.
		$settings = $this->get_settings();

		// If enabled, send email notification for backup completed.
		if ( false === empty( $settings['notifications']['backup'] ) && true !== $dryrun ) {
			// Create a site identifier.
			$site_id = $this->create_site_id();

			// Create subject.
			$subject = 'Backup completed for ' . $site_id;

			// Create message.
			$body = "Hello,\n\n";

			$body .= 'A backup archive has been created for ' . $site_id . ".\n\n";

			$body .= "Backup details:\n";

			$body .= 'Duration: ' . $info['duration'] . " seconds\n";

			$body .= 'Total size: ' . $this->bytes_to_human( $info['total_size'] ) . "\n";

			$body .= 'Archive file path: ' . $info['filepath'] . "\n";

			$body .= 'Archive file size: ' . $this->bytes_to_human( $info['filesize'] ) . "\n";

			$body .= 'Compressor used: ' . $info['compressor'] . "\n\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= "The backup request was made via WP-CRON (WordPress task scheduler).\n\n";
			}

			$body .= "You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings.\n\n";

			$body .= "Best regards,\n\nThe BoldGrid Backup plugin\n\n";

			// Send the notification.
			$info['mail_success'] = $this->send_notification( $subject, $body );
		}

		// Return the array of archive information.
		return $info;
	}

	/**
	 * Get information for the list of archive file(s).
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $download_filename A filename to match to get info.
	 * @return array An array containing file path, filename, data, and size of archive files.
	 */
	private function get_archive_list( $download_filename = null ) {
		// Initialize $archives array.
		$archives = array();

		// Initialize $archive_files array.
		$archive_files = array();

		// Ensure the backup directory is configured.
		$backup_dir_configured = $this->configure_backup_directory();

		// If the backup directory is configured, then find archives.
		if ( true === $backup_dir_configured ) {
			// Find all backups.
			$archive_files = glob( $this->backup_directory . '/boldgrid-backup-*.*' );
		}

		// Build an array of archive information.
		foreach ( $archive_files as $key => $filepath ) {
			// Get the filename.
			$filename = substr( $filepath, strlen( $this->backup_directory ) + 1 );

			// Skip if not the matching filename.
			if ( false === empty( $download_filename ) && $download_filename !== $filename ) {
				continue;
			}

			// Get file modification time.
			$mtime = filemtime( $filepath );

			if ( false !== $mtime ) {
				$mtime = date( 'n/j/Y h:i A', $mtime );
			} else {
				$mtime = 'UNKNOWN';
			}

			// Add to array.
			$archives[ $key ] = array(
				'filepath' => $filepath,
				'filename' => $filename,
				'filedate' => $mtime,
				'filesize' => filesize( $filepath ),
			);

			// If looking for info on one file and we found the match, then break the loop.
			if ( false === empty( $download_filename ) ) {
				break;
			}
		}

		// Return the array.
		return $archives;
	}

	/**
	 * Delete an archive file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Whether or not the archive file was deleted.
	 */
	private function delete_archive_file() {
		// If a deletion was not requested, then abort.
		if ( true === empty( $_GET['delete_now'] ) ) {
			return false;
		}

		// Initialize $delete_ok.
		$delete_ok = true;

		// Verify nonce, or die.
		check_admin_referer( 'boldgrid-backup-delete', 'delete_auth' );

		// Validate archive_key.
		if ( isset( $_GET['archive_key'] ) && true === is_numeric( $_GET['archive_key'] ) ) {
			$archive_key = sanitize_text_field( $_GET['archive_key'] );
		} else {
			$delete_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_archive_key',
			) );

			$archive_key = null;
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = $_GET['archive_filename'];
		} else {
			$delete_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_invalid_filename',
			) );
		}

		// Get archive list.
		if ( true === $delete_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( true === $delete_ok && true === empty( $archives ) ) {
			$delete_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_no_archives',
			) );
		}

		// Locate the filename by key number.
		if ( true === $delete_ok ) {
			$filename = ( false === empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null );
		}

		// Verify specified filename.
		if ( true === $delete_ok && $archive_filename !== $filename ) {
			$delete_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_not_found',
			) );
		}

		// Get the file path to delete.
		$filepath = ( false === empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null );

		// Delete the specified archive file.
		if ( true === $delete_ok && true !== unlink( $filepath ) ) {
			$delete_ok = false;
		}

		// Display notice of deletion status.
		if ( false === $delete_ok ) {
			add_action( 'admin_footer', array(
				$this,
				'notice_delete_error',
			) );
		}

		// Return deletion status.
		return $delete_ok;
	}

	/**
	 * Backup and create an archive file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Whether or not the backup process was successful.
	 */
	private function backup_now() {
		// If a restoration was not requested, then abort.
		if ( true === empty( $_GET['backup_now'] ) ) {
			return false;
		}

		// Verify nonce, or die.
		check_admin_referer( 'boldgrid-backup-backup', 'backup_auth' );

		// Perform the backup operation.
		$archive_info = $this->archive_files( true );

		// Display results, using the backup page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup.php';

		// Return status.
		$status = ( false === empty( $archive_info['error'] ) );

		return $status;
	}

	/**
	 * Restore from a specified archive file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return array An array of archive file information.
	 */
	private function restore_archive_file() {
		// If a restoration was not requested, then abort.
		if ( true === empty( $_GET['restore_now'] ) ) {
			return false;
		}

		// Verify nonce, or die.
		check_admin_referer( 'boldgrid-backup-restore', 'restore_auth' );

		// Check if functional.
		if ( true !== $this->run_functionality_tests() ) {
			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		// Initialize variables.
		$restore_ok = true;
		$archive_key = null;
		$archive_filename = null;

		// Validate archive_key.
		if ( isset( $_GET['archive_key'] ) && true === is_numeric( $_GET['archive_key'] ) ) {
			$archive_key = sanitize_text_field( wp_unslash( $_GET['archive_key'] ) );
		} else {
			$restore_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_archive_key',
			) );
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = sanitize_text_field( wp_unslash( $_GET['archive_filename'] ) );
		} else {
			$restore_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_invalid_filename',
			) );
		}

		// Get archive list.
		if ( true === $restore_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( true === $restore_ok && true === empty( $archives ) ) {
			$restore_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_no_archives',
			) );
		}

		// Locate the filename by key number.
		if ( true === $restore_ok ) {
			$filename = ( false === empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null );
		}

		// Verify specified filename.
		if ( true === $restore_ok && $archive_filename !== $filename ) {
			$restore_ok = false;

			add_action( 'admin_footer', array(
				$this,
				'notice_not_found',
			) );
		}

		// Get the file path to delete.
		$filepath = ( false === empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null );

		// Get the archive file size.
		if ( false === empty( $filepath ) && true === file_exists( $filepath ) ) {
			$filesize = filesize( $filepath );
		} else {
			$filesize = 0;
		}

		// Populate $info.
		$info = array(
			'filename' => $archive_filename,
			'filepath' => $filepath,
			'filesize' => $filesize,
			'archive_key' => $archive_key,
			'restore_ok' => $restore_ok,
		);

		// Perform the restoration, if all is ok.
		if ( true === $restore_ok ) {
			// Prevent this script from dying.
			ignore_user_abort( true );

			// @todo: Write restoration code here.
		}

		// Display notice of deletion status.
		if ( false === $restore_ok ) {
			add_action( 'admin_footer', array(
				$this,
				'notice_restore_error',
			) );
		}

		// Get settings.
		$settings = $this->get_settings();

		// If enabled, send email notification for restoration completed.
		if ( false === empty( $settings['notifications']['restore'] ) ) {
			// Create a site identifier.
			$site_id = $this->create_site_id();

			// Create subject.
			$subject = 'Restoration completed for ' . $site_id;

			// Create message.
			$body = "Hello,\n\n";

			if ( true === $restore_ok ) {
				$body .= 'A backup archive has been restored';
			} else {
				$body .= 'An error occurred when attempting to restore a backup archive';
			}

			$body .= ' for ' . $site_id . ".\n\n";

			$body .= "Restoration details:\n";

			$body .= 'Archive file path: ' . $info['filepath'] . "\n";

			$body .= 'Archive file size: ' . $this->bytes_to_human( $info['filesize'] ) . "\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= "The restoration request was made via WP-CRON.\n\n";
			}

			$body .= "You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings.\n\n";

			$body .= "Best regards,\n\nThe BoldGrid Backup plugin\n\n";

			// Send the notification.
			$info['mail_success'] = $this->send_notification( $subject, $body );
		}

		return $info;
	}

	/**
	 * Menu callback to display the Backup home page.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function page_backup_home() {
		// Enqueue CSS for the home page.
		wp_enqueue_style( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS for the home page.
		wp_register_script( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-home.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Create a nonce for file downloads via AJAX.
		$download_nonce = wp_create_nonce( 'archive_download' );

		// Add localized data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-home', 'downloadNonce', $download_nonce );

		// Enqueue JS for the home page.
		wp_enqueue_script( 'boldgrid-backup-admin-home' );

		// If a delete operation is requested, then delete the selected backup now.
		if ( false === empty( $_GET['delete_now'] ) ) {
			$this->delete_archive_file();
		}

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archives file count.
		$archives_count = count( $archives );

		// Get the total size for all archives.
		$archives_size = 0;

		foreach ( $archives as $archive ) {
			$archives_size += $archive['filesize'];
		}

		// Include the home page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-home.php';

		// If a backup operation is requested, then make a backup now.
		if ( false === empty( $_GET['backup_now'] ) ) {
			$this->backup_now();
		}

		// If a restoration operation is requested, then restore from a backup archive now.
		if ( false === empty( $_GET['restore_now'] ) ) {
			$this->restore_archive_file();
		}

		return;
	}

	/**
	 * Callback function for downloading an archive file via AJAX.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function download_archive_file_callback() {
		// Verify nonce, or die.
		check_ajax_referer( 'archive_download', 'wpnonce' );

		// Validate download_key.
		if ( true === is_numeric( $_POST['download_key'] ) ) {
			$download_key = sanitize_text_field( wp_unslash( $_POST['download_key'] ) );
		} else {
			echo 'INVALID DOWNLOAD KEY';
			wp_die();
		}

		// Validate download_filename.
		if ( false === empty( $_POST['download_filename'] ) ) {
			$download_filename = sanitize_text_field( wp_unslash( $_POST['download_filename'] ) );
		} else {
			echo 'INVALID DOWNLOAD FILENAME';
			wp_die();
		}

		// Get archive list.
		$archives = $this->get_archive_list( $download_filename );

		// If no files were found, then abort.
		if ( true === empty( $archives ) ) {
			echo 'NO BACKUP ARCHIVES FOUND';
			wp_die();
		}

		// Locate the filename by key number.
		$filename = ( false === empty( $archives[ $download_key ]['filename'] ) ? $archives[ $download_key ]['filename'] : null );

		// Verify filename.
		if ( $download_filename !== $filename ) {
			echo 'FILE NOT FOUND';
			wp_die();
		}

		$filepath = $archives[ $download_key ]['filepath'];

		$filesize = $archives[ $download_key ]['filesize'];

		// Send header.
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Type: binary/octet-stream' );
		header( 'Content-Length: ' . $filesize );

		// Check and flush output buffer if needed.
		if ( 0 !== ob_get_level() ) {
			ob_end_flush();
		}

		// Send the file.
		readfile( $filepath );

		// Exit.
		wp_die();

		// Return added to satisfy phpcs.
		return;
	}

	/**
	 * Menu callback to display the Backup functionality test page.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function page_backup_test() {
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-test.php';

		return;
	}

	/**
	 * Get settings using defaults.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return array An array of settings.
	 */
	private function get_settings() {
		// Get settings.
		$settings = get_option( 'boldgrid_backup_settings' );

		// Parse settings.
		if ( false === empty( $settings['schedule'] ) ) {
			// Update schedule format.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = ( false ===
				 empty( $settings['schedule']['dow_sunday'] ) ? 1 : 0 );
			$settings['schedule']['dow_monday'] = ( false ===
				 empty( $settings['schedule']['dow_monday'] ) ? 1 : 0 );
			$settings['schedule']['dow_tuesday'] = ( false ===
				 empty( $settings['schedule']['dow_tuesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_wednesday'] = ( false ===
				 empty( $settings['schedule']['dow_wednesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_thursday'] = ( false ===
				 empty( $settings['schedule']['dow_thursday'] ) ? 1 : 0 );
			$settings['schedule']['dow_friday'] = ( false ===
				 empty( $settings['schedule']['dow_friday'] ) ? 1 : 0 );
			$settings['schedule']['dow_saturday'] = ( false ===
				 empty( $settings['schedule']['dow_saturday'] ) ? 1 : 0 );

			// Time of day.
			$settings['schedule']['tod_h'] = ( false === empty( $settings['schedule']['tod_h'] ) ? $settings['schedule']['tod_h'] : mt_rand( 1, 5 ) );
			$settings['schedule']['tod_m'] = ( false === empty( $settings['schedule']['tod_m'] ) ? $settings['schedule']['tod_m'] : mt_rand( 1, 59 ) );
			$settings['schedule']['tod_a'] = ( false === empty( $settings['schedule']['tod_a'] ) ? $settings['schedule']['tod_a'] : 'AM' );

			// Other settings.
			$settings['notifications']['backup'] = ( false ===
				 isset( $settings['notifications']['backup'] ) || false ===
				 empty( $settings['notifications']['backup'] ) ? 1 : 0 );
			$settings['notifications']['restore'] = ( false ===
				 isset( $settings['notifications']['restore'] ) || false ===
				 empty( $settings['notifications']['restore'] ) ? 1 : 0 );
			$settings['auto_rollback'] = ( false === isset( $settings['auto_rollback'] ) ||
				 false === empty( $settings['auto_rollback'] ) ? 1 : 0 );
		} else {
			// Define defaults.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = 0;
			$settings['schedule']['dow_monday'] = 0;
			$settings['schedule']['dow_tuesday'] = 0;
			$settings['schedule']['dow_wednesday'] = 0;
			$settings['schedule']['dow_thursday'] = 0;
			$settings['schedule']['dow_friday'] = 0;
			$settings['schedule']['dow_saturday'] = 0;

			// Time of day.
			$settings['schedule']['tod_h'] = mt_rand( 1, 5 );
			$settings['schedule']['tod_m'] = mt_rand( 1, 59 );
			$settings['schedule']['tod_a'] = 'AM';

			// Other settings.
			$settings['notifications']['backup'] = 1;
			$settings['notifications']['restore'] = 1;
			$settings['auto_rollback'] = 1;
		}

		// Return the settings array.
		return $settings;
	}

	/**
	 * Update or add an entry to the system user crontab or wp-cron.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $entry A cron entry.
	 * @return bool Success.
	 */
	private function update_cron( $entry ) {
		// If no entry was passed, then abort.
		if ( true === empty( $entry ) ) {
			return false;
		}

		// Check if crontab is available.
		$is_crontab_available = $this->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( true !== $is_crontab_available && true !== $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( true !== $this->configure_backup_directory() ) {
			return false;
		}

		// Use either crontab or wp-cron.
		if ( true === $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->execute_command( $command );

			// Check for failure.
			if ( false === $crontab ) {
				return false;
			}

			// Add entry to crontab to the end, if it does not already exist.
			if ( false === strpos( $crontab, $entry ) ) {
				$crontab .= "\n" . $entry . "\n";
			}

			// Strip extra line breaks.
			$crontab = str_replace( "\n\n", "\n", $crontab );

			// Trim the crontab.
			$crontab = trim( $crontab );

			// Add a line break at the end of the file.
			$crontab .= "\n";

			// Save the temp crontab to file.
			$temp_crontab_path = $this->backup_directory . '/crontab.' . microtime( true ) . '.tmp';

			file_put_contents( $temp_crontab_path, $crontab );

			// Check if the defaults file was written.
			if ( false === file_exists( $temp_crontab_path ) ) {
				return false;
			}

			chmod( $temp_crontab_path, 0600 );

			// Write crontab.
			$command = 'crontab ' . $temp_crontab_path;

			$crontab = $this->execute_command( $command );

			// Remove temp crontab file.
			unlink( $temp_crontab_path );

			// Check for failure.
			if ( false === $crontab ) {
				return false;
			}
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return true;
	}

	/**
	 * Delete boldgrid-backup cron entries from the system user crontab or wp-cron.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Success.
	 */
	private function delete_cron_entries() {
		// Check if crontab is available.
		$is_crontab_available = $this->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( true !== $is_crontab_available && true !== $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( true !== $this->configure_backup_directory() ) {
			return false;
		}

		// Set a search pattern to match for our cron jobs.
		$pattern = 'boldgrid-backup-cron.php';

		// Use either crontab or wp-cron.
		if ( true === $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->execute_command( $command );

			// If no entries exist, then return success.
			if ( false === strpos( $crontab, $pattern ) ) {
				return true;
			}

			// Remove lines matching the pattern.
			$crontab_exploded = explode( "\n", $crontab );

			$crontab = '';

			foreach ( $crontab_exploded as $line ) {
				if ( false === strpos( $line, $pattern ) ) {
					$line = trim( $line );
					$crontab .= $line . "\n";
				}
			}

			// Save the temp crontab to file.
			$temp_crontab_path = $this->backup_directory . '/crontab.' . microtime( true ) . '.tmp';

			file_put_contents( $temp_crontab_path, $crontab );

			// Check if the defaults file was written.
			if ( false === file_exists( $temp_crontab_path ) ) {
				return false;
			}

			chmod( $temp_crontab_path, 0600 );

			// Write crontab.
			$command = 'crontab ' . $temp_crontab_path;

			$crontab = $this->execute_command( $command );

			// Remove temp crontab file.
			unlink( $temp_crontab_path );
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return true;
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Update success.
	 */
	private function update_settings() {
		// Verify nonce.
		check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

		// Check for settings update.
		if ( false === empty( $_POST['save_time'] ) ) {
			// Get settings.
			$settings = $this->get_settings();

			// Initialize $update_error.
			$update_error = false;

			// Initialize $days_scheduled.
			$days_scheduled = array();

			// Validate input for schedule.
			$indices = array(
				'dow_sunday',
				'dow_monday',
				'dow_tuesday',
				'dow_wednesday',
				'dow_thursday',
				'dow_friday',
				'dow_saturday',
				'tod_h',
				'tod_m',
				'tod_a',
			);

			foreach ( $indices as $index ) {
				// Determine input type.
				if ( 0 === strpos( $index, 'dow_' ) ) {
					$type = 'day';
				} elseif ( 'tod_h' === $index ) {
					$type = 'h';
				} elseif ( 'tod_m' === $index ) {
					$type = 'm';
				} elseif ( 'tod_a' === $index ) {
					$type = 'a';
				} else {
					// Unknown type.
					$type = '?';
				}

				if ( false === empty( $_POST[ $index ] ) ) {
					// Validate by type.
					switch ( $type ) {
						case 'day' :
							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// If day was scheduled, then track it.
							if ( 1 === $_POST[ $index ] ) {
								$days_scheduled[] = date( 'w', strtotime( str_replace( 'dow_', '', $index ) ) );
							}

							break;
						case 'h' :
							if ( $_POST[ $index ] < 1 || $_POST[ $index ] > 12 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							break;
						case 'm' :
							if ( $_POST[ $index ] < 0 || $_POST[ $index ] > 59 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// Pad left with 0.
							$_POST[ $index ] = str_pad( $_POST[ $index ], 2, '0', STR_PAD_LEFT );

							break;
						case 'a' :
							if ( 'AM' !== $_POST[ $index ] && 'PM' !== $_POST[ $index ] ) {
								// Error in input; unknown type.
								$update_error = true;
								break 2;
							}

							break;
						default :
							// Error in input; unknown type.
							$update_error = true;
							break 2;
					}

					// Update the setting value provided.
					$settings['schedule'][ $index ] = $_POST[ $index ];
				} elseif ( 'day' === $type ) {
					// Unassigned days.
					$settings['schedule'][ $index ] = 0;
				} else {
					// Error in input.
					$update_error = true;

					break;
				}
			}

			// Validate input for other settings.
			$settings['notifications']['backup'] = ( ( true === isset( $_POST['notify_backup'] ) &&
				 '1' === $_POST['notify_backup'] ) ? 1 : 0 );

			$settings['notifications']['restore'] = ( ( true === isset( $_POST['notify_restore'] ) &&
				 '1' === $_POST['notify_restore'] ) ? 1 : 0 );

			$settings['auto_rollback'] = ( ( false === isset( $_POST['auto_rollback'] ) ||
				 '1' === $_POST['auto_rollback'] ) ? 1 : 0 );

			// If no errors, then save the settings.
			if ( false === $update_error ) {
				// Record the update time.
				$settings['updated'] = time();

				// Attempt to update WP option.
				if ( true !== update_option( 'boldgrid_backup_settings', $settings ) ) {
					// Failure.
					$update_error = true;

					add_action( 'admin_footer',
						array(
							$this,
							'notice_settings_error',
						)
					);
				} else {
					// Update cron jobs.
				}
			} else {
				// Interrupted by a previous error.
				add_action( 'admin_footer',
					array(
						$this,
						'notice_settings_error',
					)
				);
			}
		}

		// Delete existing backup cron jobs.
		$cron_status = $this->delete_cron_entries();

		// If delete cron failed, then show a notice.
		if ( true !== $cron_status ) {
			$update_error = true;

			add_action( 'admin_footer', array(
				$this,
				'notice_cron_error',
			) );
		}

		// Update cron, if there are days selected.
		if ( false === empty( $days_scheduled ) ) {
			// Build cron job line in crontab format.
			$entry = date( 'i H',
				strtotime(
					$settings['schedule']['tod_h'] . ':' . $settings['schedule']['tod_m'] . ' ' .
						 $settings['schedule']['tod_a']
				)
			) . ' * * ';

			$days_scheduled_list = '';

			foreach ( $days_scheduled as $day ) {
				$days_scheduled_list .= $day . ',';
			}

			$days_scheduled_list = rtrim( $days_scheduled_list, ',' );

			$entry .= $days_scheduled_list . ' curl -sk ' . plugin_dir_url( __FILE__ ) .
				 'boldgrid-backup-cron.php';

			if ( false === $this->is_windows() ) {
				$entry .= ' > /dev/null 2>&1';
			}

			// Update cron.
			$cron_status = $this->update_cron( $entry );

			// If update cron failed, then show a notice.
			if ( true !== $cron_status ) {
				$update_error = true;

				add_action( 'admin_footer', array(
					$this,
					'notice_cron_error',
				) );
			}
		}

		// If there was no error, then show success notice.
		if ( false === $update_error ) {
			// Success.
			add_action( 'admin_footer', array(
				$this,
				'notice_settings_saved',
			) );
		}

		// Return success.
		return ! $update_error;
	}

	/**
	 * Menu callback to display the Backup schedule page.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function page_backup_settings() {
		// Display warning on resource usage and backups.
		add_action( 'admin_footer', array(
			$this,
			'notice_settings_warning',
		) );

		// Get BoldGrid reseller settings.
		$boldgrid_reseller = get_option( 'boldgrid_reseller' );

		// If not part of a reseller, then show the unofficial host notice.
		if ( true === empty( $boldgrid_reseller ) ) {
			add_action( 'admin_footer', array(
				$this,
				'notice_backup_warning',
			) );
		}

		// Check for settings update.
		if ( false === empty( $_POST['save_time'] ) ) {
			// Verify nonce.
			check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

			$this->update_settings();
		}

		// Enqueue CSS for the settings page.
		wp_enqueue_style( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-settings.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS for the settings page.
		wp_register_script( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-settings.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Enqueue JS for the settings page.
		wp_enqueue_script( 'boldgrid-backup-admin-settings' );

		// Get settings.
		$settings = $this->get_settings();

		// Include the page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-settings.php';

		return;
	}

	/**
	 * Send a notification email to the admin email address.
	 *
	 * @param string $subject The email subject.
	 * @param string $body The email body.
	 *
	 * @return bool Whether or not the notification email was sent.
	 */
	private function send_notification( $subject, $body ) {
		// Abort if subject or body is empty.
		if ( true === empty( $subject ) || true === empty( $body ) ) {
			return false;
		}

		// Initialize $admin_email.
		$admin_email = null;

		// Get the site email address.
		// Try get_bloginfo.
		if ( true === function_exists( 'get_bloginfo' ) ) {
			$admin_email = get_bloginfo( 'admin_email' );
		}

		// If the email address is still needed, then try wp_get_current_user.
		if ( true === empty( $admin_email ) && true === function_exists( 'wp_get_current_user' ) ) {
			// Get the current user information.
			$current_user = wp_get_current_user();

			// Check if user information was retrieved, abort if not.
			if ( false === $current_user ) {
				return false;
			}

			// Get the current user email address.
			$admin_email = $current_user->user_email;
		}

		// If there is no email address found, then abort.
		if ( true === empty( $admin_email ) ) {
			return false;
		}

		// Get the site title.
		$site_title = get_bloginfo( 'name' );

		// Configure mail headers.
		$headers = 'From: ' . $site_title . ' <' . $admin_email . '>' . "\r\n" . 'X-Mailer: PHP/' .
			 phpversion() . "\r\n";

		// Send mail.
		$status = mail( $admin_email, $subject, $body, $headers );

		// Return status.
		return $status;
	}

	/**
	 * Admin notice for functionality test failure.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_functionality_fail() {
		$class = 'notice notice-error is-dismissible';
		$message = __(
			'Functionality test has failed.  You can go to <a href="' .
				 admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				 '">Functionality Test</a> to view a report.', 'boldgrid-backup'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for invalid archive key.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_archive_key() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Invalid key for the selected archive file.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for invalid archive filename.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_invalid_filename() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Invalid filename for the selected archive file.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for no archive files found.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_no_archives() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'No archive files were found.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for archive file not found.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_not_found() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'The selected archive file was not found.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for deletion errors.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_delete_error() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Error deleting the selected archive file.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for restoration errors.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_restore_error() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Error restoring the selected archive file.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for settings warning.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_settings_warning() {
		$class = 'notice notice-warning is-dismissible';
		$message = __(
			'Warning: Making backups uses resources. When the system is backing up, it will slow down your site for visitors. Furthermore, when the database itself is being copied, your site must “pause” temporarily to preserve data integrity. For most sites, the pause is typically a few seconds and is not noticed by visitors. Large sites take longer though. Please keep the number of backups you have stored and how often you make those backups to a minimum.',
			'boldgrid-backup'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for backup warning for unofficial hosts.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_backup_warning() {
		$class = 'notice notice-warning is-dismissible';
		$message = __(
			'Please note that your web hosting provider may have a policy against these types of backups. Please verify with your provider or choose a BoldGrid Official Host.'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for failed to get settings.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_settings_retrieval() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Failed to get settings.  Please try again.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for successful saved settings.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_settings_saved() {
		$class = 'updated settings-error notice is-dismissible';
		$message = __( 'Settings saved.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for failed saved settings.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_settings_error() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'Invalid settings submitted.  Please try again.', 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Admin notice for failed cron modification.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function notice_cron_error() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'An error occurred when modifying cron jobs.  Please try again.',
			'boldgrid-backup'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}
}
