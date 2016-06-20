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
 * BoldGrid Backup admin core class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Core {
	/**
	 * The settings class object.
	 *
	 * @since 1.0
	 * @access public
	 * @var Boldgrid_Backup_Admin_Settings
	 */
	public $settings;

	/**
	 * The configuration class object.
	 *
	 * @since 1.0
	 * @access public
	 * @var Boldgrid_Backup_Admin_Config
	 */
	public $config;

	/**
	 * The functionality test class object.
	 *
	 * @since 1.0
	 * @access public
	 * @var Boldgrid_Backup_Admin_Test
	 */
	public $test;

	/**
	 * The admin notice class object.
	 *
	 * @since 1.0
	 * @access public
	 * @var Boldgrid_Backup_Admin_Notice
	 */
	public $notice;

	/**
	 * Available execution functions.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $available_exec_functions = null;

	/**
	 * Database backup file path.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $db_dump_filepath = '';

	/**
	 * Base directory for the get_filelist method.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $filelist_basedir = null;

	/**
	 * The filelist filter array.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $filelist_filter = array(
		'.htaccess',
		'index.php',
		'license.txt',
		'readme.html',
		'readme.txt',
		'wp-activate.php',
		'wp-admin',
		'wp-blog-header.php',
		'wp-comments-post.php',
		'wp-config.php',
		'wp-content',
		'wp-cron.php',
		'wp-includes',
		'wp-links-opml.php',
		'wp-load.php',
		'wp-login.php',
		'wp-mail.php',
		'wp-settings.php',
		'wp-signup.php',
		'wp-trackback.php',
		'xmlrpc.php',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Instantiate Boldgrid_Backup_Admin_Settings.
		$this->settings = new Boldgrid_Backup_Admin_Settings( $this );

		// Instantiate Boldgrid_Backup_Admin_Config.
		$this->config = new Boldgrid_Backup_Admin_Config( $this );

		// Instantiate Boldgrid_Backup_Admin_Test.
		$this->test = new Boldgrid_Backup_Admin_Test( $this );
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
		if ( null !== $this->available_exec_functions ) {
			return $this->available_exec_functions;
		}

		// If PHP is in safe mode, then return an empty array.
		if ( true === $this->test->is_php_safemode() ) {
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
	 *
	 * @param string $command A command string to be executed.
	 * @param array  $available_exec_functions An array of available execution functions.
	 * @param bool   $success or failure of the operation, passed back to the caller.
	 * @return string|bool Returns the command output or FALSE on error.
	 */
	public function execute_command( $command, $available_exec_functions = array(), &$success = false ) {
		// If no command was passed, then fail.
		if ( true === empty( $command ) ) {
			return false;
		}

		// If there are no supplied execution functions, then retrieve available ones.
		if ( true === empty( $available_exec_functions ) ) {
			$available_exec_functions = $this->get_execution_functions();
		}

		// Disable stderr.
		if ( false === $this->test->is_windows() && false === strpos( $command, '2>/dev/null' ) ) {
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
						// Close unused pipes[0].
						fclose( $pipes[0] );

						// Read output from pipes[1].
						$output = stream_get_contents( $pipes[1] );

						// Close pipes[1].
						fclose( $pipes[1] );

						// Close unused pipes[0].
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
			$this->settings,
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
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return bool Status of the operation.
	 */
	private function backup_database() {
		// Check if functional.
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice.
			do_action( 'boldgrid_backup_notice',
				'Functionality test has failed.  You can go to <a href="' .
				admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				'">Functionality Test</a> to view a report.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// If mysqldump is not available, then fail.
		if ( true !== $this->test->is_mysqldump_available() ) {
			do_action( 'boldgrid_backup_notice', 'mysqldump is not available.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
			do_action( 'boldgrid_backup_notice', 'The backup directory is not writable.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Create a mysql defaults file.
		$defaults_filepath = $backup_directory . '/mysqldump.cnf';

		$defaults_file_data = '[client]' . PHP_EOL . 'host=' . DB_HOST . PHP_EOL . 'user=' . DB_USER .
			 PHP_EOL . 'password=' . DB_PASSWORD . PHP_EOL;

		$status = $wp_filesystem->put_contents( $defaults_filepath, $defaults_file_data, 0600 );

		// Check if the defaults file was written.
		if ( true !== $status || false === $wp_filesystem->exists( $defaults_filepath ) ) {
			do_action( 'boldgrid_backup_notice', 'Could not create a mysql defaults file.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Limit file permissions to the mysql defaults file.
		$wp_filesystem->chmod( $defaults_filepath, 0600 );

		// Create a file path for the dump file.
		$db_dump_filepath = $backup_directory . '/' . DB_NAME . '.' . date( 'Ymd-His' ) .
			 '.sql';

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Build a mysql query to get all of the table names.
		$query = $wpdb->prepare(
			'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`=%s AND `TABLE_NAME` LIKE %s ORDER BY `TABLE_NAME`;',
			DB_NAME, $wpdb->get_blog_prefix( is_multisite() ) . '%'
		);

		// Check query.
		if ( true === empty( $query ) ) {
			do_action( 'boldgrid_backup_notice', 'Could not determine mysql tables names.',
				'notice notice-error is-dismissible'
			);

			return 0;
		}

		// Get the result.
		$tables = $wpdb->get_results( $query, ARRAY_N );

		// If there was an error or nothing returned, then fail.
		if ( empty( $tables ) ) {
			do_action( 'boldgrid_backup_notice', 'No results when getting mysql table names.',
				'notice notice-error is-dismissible'
			);

			return 0;
		}

		// Initialize $table_names.
		$table_names = null;

		// Get the table names from the query results (one row per result (index 0)).
		foreach ( $tables as $table ) {
			$table_names .= $table[0] . ' ';
		}

		// Create a file with the table names.
		$tables_filepath = $backup_directory . '/tables.' . microtime( true ) . '.tmp';

		$status = $wp_filesystem->put_contents( $tables_filepath, $table_names, 0600 );

		// Check if the temp table names file was written.
		if ( true !== $status || false === $wp_filesystem->exists( $tables_filepath ) ) {
			do_action( 'boldgrid_backup_notice', 'Could not create a table names file.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Limit file permissions to the table names file.
		$wp_filesystem->chmod( $tables_filepath, 0600 );

		// Build a command to backup the database with mysqldump.
		$command = 'mysqldump --defaults-file=' . $defaults_filepath .
			 ' --dump-date --opt --tz-utc --result-file=' . $db_dump_filepath . ' ' . DB_NAME .
		' `cat '. $tables_filepath . '`';

		// Set the PHP timeout limit to at least 300 seconds.
		set_time_limit(
			( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 )
		);

		// Execute the command.
		$output = $this->execute_command( $command, null, $status );

		// Remove the defaults file.
		$wp_filesystem->delete( $defaults_filepath, false, 'f' );

		// Remove the table names file.
		$wp_filesystem->delete( $tables_filepath, false, 'f' );

		// Check command status.
		if ( false === $output || true !== $status ) {
			do_action( 'boldgrid_backup_notice', 'mysqldump was not successful.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Check if the dump file was written.
		if ( false === $wp_filesystem->exists( $db_dump_filepath ) ) {
			do_action( 'boldgrid_backup_notice', 'A mysql dump file was not created.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Limit file permissions to the dump file.
		$wp_filesystem->chmod( $db_dump_filepath, 0600 );

		// Return success.
		return true;
	}

	/**
	 * Restore the WordPress database from a dump file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @param string $db_dump_filepath FIle path to the mysql dump file.
	 * @return bool Status of the operation.
	 */
	private function restore_database( $db_dump_filepath ) {
		// Check input.
		if ( true === empty( $db_dump_filepath ) ) {
			return false;
		}

		// Check if functional.
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice.
			do_action( 'boldgrid_backup_notice',
				'Functionality test has failed.  You can go to <a href="' .
				admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				'">Functionality Test</a> to view a report.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Create a mysql defaults file.
		$defaults_filepath = $backup_directory . '/mysqldump.cnf';

		$defaults_file_data = '[client]' . PHP_EOL . 'host=' . DB_HOST . PHP_EOL . 'user=' . DB_USER .
			 PHP_EOL . 'password=' . DB_PASSWORD . PHP_EOL;

		$status = $wp_filesystem->put_contents( $defaults_filepath, $defaults_file_data, 0600 );

		// Check if the defaults file was written.
		if ( true !== $status || false === $wp_filesystem->exists( $defaults_filepath ) ) {
			return false;
		}

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		// Build a command to restore the database with mysqldump.
		$command = 'mysql --defaults-file=' . $defaults_filepath . ' --force --one-database ' .
		DB_NAME . ' < ' . $db_dump_filepath;

		// Set the PHP timeout limit to at least 300 seconds.
		set_time_limit(
			( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 )
		);

		// Execute the command.
		$output = $this->execute_command( $command, null, $status );

		// Remove the defaults file.
		$wp_filesystem->delete( $defaults_filepath, false, 'f' );

		// Check command status.
		if ( true !== $status ) {
			return false;
		}

		// Delete the dump file.
		$wp_filesystem->delete( $db_dump_filepath, false, 'f' );

		// Return success.
		return true;
	}

	/**
	 * Get a single-dimension filelist array from a directory path.
	 *
	 * @since 1.0
	 *
	 * @param string $dirpath A directory path.
	 * @return array A single-dimension filelist array for use in this class.
	 */
	public function get_filelist( $dirpath ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Validate input.
		if ( true === empty( $dirpath ) || true !== $wp_filesystem->is_readable( $dirpath ) ) {
			return array();
		}

		// Remove any training slash in dirpath.
		$dirpath = untrailingslashit( $dirpath );

		// Mark the base directory, if not set (the first run).
		if ( true === empty( $this->filelist_basedir ) ) {
			$this->filelist_basedir = $dirpath;
		}

		// Get the non-recursive directory listing for the specified path.
		$dirlist = $wp_filesystem->dirlist( $dirpath, true, false );

		// Sort the dirlist array by filename.
		uasort( $dirlist,
			function ( $a, $b ) {
				if ( $a['name'] < $b['name'] ) {
					return - 1;
				}

				if ( $a['name'] > $b['name'] ) {
					return 1;
				}

				return 0;
			}
		);

		// Initialize $filelist.
		$filelist = array();

		// Perform conversion.
		foreach ( $dirlist as $fileinfo ) {
			// If item is a directory, then recurse, merge, and continue.
			if ( 'd' === $fileinfo['type'] ) {
				$filelist_add = $this->get_filelist( $dirpath . '/' . $fileinfo['name'] );

				$filelist = array_merge( $filelist, $filelist_add );

				continue;
			}

			// Get the file path.
			$filepath = $dirpath . '/' . $fileinfo['name'];

			// The relative path inside the ZIP file.
			$relative_path = substr( $filepath, strlen( $this->filelist_basedir ) + 1 );

			// For files, add to the filelist array.
			$filelist[] = array(
				$filepath,
				$relative_path,
				$fileinfo['size'],
			);
		}

		// Return the array.
		return $filelist;
	}

	/**
	 * Get a recursive file list of the WordPress installation root directory.
	 *
	 * This is a recursive function, which uses the class property filelist_basedir.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Core::get_filelist().
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $dirpath A directory path, defaults to ABSPATH.
	 * @return array An array of absolute file paths, relative paths, and file sizes.
	 */
	public function get_filtered_filelist( $dirpath = ABSPATH ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Validate input.
		if ( true === empty( $dirpath ) || true !== $wp_filesystem->is_readable( $dirpath ) ) {
			return array();
		}

		// Get the recursive directory listing for the specified path.
		$filelist = $this->get_filelist( $dirpath );

		// If no files were found, then return an empty array.
		if ( true === empty( $filelist ) ) {
			return array();
		}

		// Initialize $new_filelist.
		$new_filelist = array();

		// Filter the filelist array.
		foreach ( $filelist as $fileinfo ) {
			foreach ( $this->filelist_filter as $pattern ) {
				if ( 0 === strpos( $fileinfo[1], $pattern ) ) {
					$new_filelist[] = $fileinfo;

					break;
				}
			}
		}

		// Replace filelist.
		$filelist = $new_filelist;

		// Clear filelist_basedir.
		$this->filelist_basedir = null;

		// Return the filelist array.
		return $filelist;
	}

	/**
	 * Generate an new archive file path.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $extension An optional file extension.
	 * @return string|bool An archive file path, or FALSE on error.
	 */
	private function generate_archive_path( $extension = null ) {
		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Create a file path with no extension (added later).
		$filepath = $backup_directory . '/boldgrid-backup-' . $site_id . '-' .
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
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @see Boldgrid_Backup_Admin_Core::backup_database().
	 *
	 * @param bool $save A switch to save the archive file. Default is FALSE.
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	public function archive_files( $save = false, $dryrun = false ) {
		// Check if functional.
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice, if not already on the test page.
			if ( false === isset( $_GET['page'] ) || 'boldgrid-backup-test' !== $_GET['page'] ) {
				// Display an error notice.
				do_action( 'boldgrid_backup_notice',
					'Functionality test has failed.  You can go to <a href="' .
					 admin_url( 'admin.php?page=boldgrid-backup-test' ) .
					 '">Functionality Test</a> to view a report.',
					'notice notice-error is-dismissible'
				);
			}

			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		// Initialize return array and add "compressor" and "save" keys.
		$info = array(
			'mode' => 'backup',
			'dryrun' => $dryrun,
			'compressor' => null,
			'filesize' => 0,
			'save' => $save,
		);

		// Get available compressors.
		$available_compressors = $this->config->get_available_compressors();

		// Determine which compressor to use (first available).
		foreach ( $available_compressors as $available_compressor ) {
			$info['compressor'] = $available_compressor;
			break;
		}

		// If there is no available compressor, then fail.
		if ( null === $info['compressor'] ) {
			return array(
				'error' => 'No available compressor.',
			);
		}

		// Enforce retention setting.
		$this->enforce_retention();

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
		$filelist = $this->get_filtered_filelist( ABSPATH );

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

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Add the database dump file to the file list.
		$db_relative_path = substr( $this->db_dump_filepath, strlen( $backup_directory ) + 1 );

		$db_file_array = array(
			$this->db_dump_filepath,
			$db_relative_path,
			$wp_filesystem->size( $this->db_dump_filepath ),
		);

		// Prepend the $db_file_array element to the beginning of the $filelist array.
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
							'error_message' => Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ),
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
					if ( false === $wp_filesystem->exists( $info['filepath'] ) ) {
						return array(
							'error' => 'The archive file "' . $info['filepath'] .
								 '" was not written.',
						);
					}

					// Get file size.
					$info['lastmodunix'] = $wp_filesystem->mtime( $info['filepath'] );
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

		if ( true === $save && true !== $dryrun ) {
			// Modify the archive file permissions to help protect from public access.
			$wp_filesystem->chmod( $info['filepath'], 0600 );

			// Add some statistics to the return.
			$info['filesize'] = $wp_filesystem->size( $info['filepath'] );

			// Delete the temporary database dump file.
			$wp_filesystem->delete( $this->db_dump_filepath, false, 'f' );
		}

		// Stop timer.
		$time_stop = microtime( true );

		// Calculate duration.
		$info['duration'] = number_format( ( $time_stop - $time_start ), 4, '.', '' );

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for backup completed.
		if ( false === empty( $settings['notifications']['backup'] ) ) {
			// Create a site identifier.
			$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

			// Create subject.
			$subject = __( 'Backup completed for ' ) . $site_id;

			// Create message.
			$body = __( "Hello,\n\n" );

			if ( true !== $dryrun ) {
				$body .= __( 'THIS OPERATION WAS A DRY-RUN TEST.' );
			}

			$body .= __( 'A backup archive has been created for ' ) . $site_id . ".\n\n";

			$body .= __( "Backup details:\n" );

			$body .= __( 'Duration: ' . $info['duration'] . " seconds\n" );

			$body .= __( 'Total size: ' ) .
			Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['total_size'] ) . "\n";

			$body .= __( 'Archive file path: ' ) . $info['filepath'] . "\n";

			$body .= __( 'Archive file size: ' ) .
			Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] ) . "\n";

			$body .= __( 'Compressor used: ' ) . $info['compressor'] . "\n\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= __( "The backup request was made via CRON (task scheduler).\n\n" );
			}

			$body .= __(
				"You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings.\n\n"
			);

			$body .= __( "Best regards,\n\nThe BoldGrid Backup plugin\n\n" );

			// Send the notification.
			$info['mail_success'] = $this->send_notification( $subject, $body );
		}

		// If not a dry-run test, update the last backup option and enforce retention.
		if ( true !== $dryrun ) {
			// Update WP option for "boldgrid_backup_last_backup".
			if ( true === is_multisite() ) {
				update_site_option( 'boldgrid_backup_last_backup', time() );
			} else {
				update_option( 'boldgrid_backup_last_backup', time(), false );
			}

			// Enforce retention setting.
			$this->enforce_retention();
		}

		// Return the array of archive information.
		return $info;
	}

	/**
	 * Get information for the list of archive file(s).
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $download_filename A filename to match to get info.
	 * @return array An array containing file path, filename, data, and size of archive files.
	 */
	public function get_archive_list( $download_filename = null ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $archive_files array.
		$archive_files = array();

		// Get the backup directory.
		$backup_directory = $this->config->get_backup_directory();

		// If the backup directory is not configured, then return an empty array.
		if ( false === $backup_directory ) {
			return array();
		}

		// Find all backups.
		$dirlist = $wp_filesystem->dirlist( $backup_directory, false, false );

		// If no files were found, then return an empty array.
		if ( true === empty( $dirlist ) ) {
			return array();
		}

		// Sort the dirlist array by "lastmodunix".
		uasort( $dirlist,
			function ( $a, $b ) {
				if ( $a['lastmodunix'] < $b['lastmodunix'] ) {
					return - 1;
				}

				if ( $a['lastmodunix'] > $b['lastmodunix'] ) {
					return 1;
				}

				return 0;
			}
		);

		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		// Initialize $index.
		$index = -1;

		// Filter the array.
		foreach ( $dirlist as $fileinfo ) {
			if (
				1 === preg_match(
					'/^boldgrid-backup-' . $site_id . '-.*\.(zip|tar\.gz|b2z|zlib|lzf)$/',
					$fileinfo['name']
				)
			) {
				// Increment the index.
				$index++;

				// If looking for one match, skip an iteration if not the matching filename.
				if ( false === empty( $download_filename ) && $download_filename !== $fileinfo['name'] ) {
					continue;
				}

				// Create the return array.
				// @todo Should we use the data and time from the filename, or rely on lastmodunix?
				$archive_files[ $index ] = array(
					'filepath' => $backup_directory . '/' . $fileinfo['name'],
					'filename' => $fileinfo['name'],
					'filedate' => get_date_from_gmt(
						date( 'Y-m-d H:i:s', $fileinfo['lastmodunix'] ), 'n/j/Y g:i A'
					),
					'filesize' => $fileinfo['size'],
					'lastmodunix' => $fileinfo['lastmodunix'],
				);

				// If looking for info on one file and we found the match, then break the loop.
				if ( false === empty( $download_filename ) ) {
					break;
				}
			}
		}

		// Return the array.
		return $archive_files;
	}

	/**
	 * Delete an archive file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
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

			do_action( 'boldgrid_backup_notice',
				'Invalid key for the selected archive file.',
				'notice notice-error is-dismissible'
			);

			$archive_key = null;
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = $_GET['archive_filename'];
		} else {
			// Fail with a notice.
			do_action( 'boldgrid_backup_notice',
				'Invalid filename for the selected archive file.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// If there are errors, then abort.
		if ( false === $delete_ok ) {
			return false;
		}

		// Get archive list.
		$archives = $this->get_archive_list( $archive_filename );

		// If no files were found, then show a notice.
		if ( true === empty( $archives ) ) {
			// Fail with a notice.
			do_action( 'boldgrid_backup_notice',
				'No archive files were found.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Locate the filename by key number.
		$filename = ( false === empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null );

		// Verify specified filename.
		if ( $archive_filename !== $filename ) {
			// Fail with a notice.
			do_action( 'boldgrid_backup_notice',
				'The selected archive file was not found.',
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Get the file path to delete.
		$filepath = ( false === empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null );

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Delete the specified archive file.
		if ( true !== $wp_filesystem->delete( $filepath, false, 'f' ) ) {
			$delete_ok = false;
		}

		// Display notice of deletion status.
		if ( false === $delete_ok ) {
			do_action( 'boldgrid_backup_notice',
				'Error deleting the selected archive file.',
				'notice notice-error is-dismissible'
			);
		}

		// Return deletion status.
		return $delete_ok;
	}

	/**
	 * Get the newest database dump file path from a restored archive.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return string File path to the database dump file.
	 */
	private function get_dump_file() {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $db_dump_filepath.
		$db_dump_filepath = '';

		// Find all backups.
		$dirlist = $wp_filesystem->dirlist( ABSPATH, false, false );

		// If no files were found, then return an empty array.
		if ( true === empty( $dirlist ) ) {
			return '';
		}

		// Sort the dirlist array by "name" descending.
		uasort( $dirlist,
			function ( $a, $b ) {
				if ( $a['name'] < $b['name'] ) {
					return 1;
				}

				if ( $a['name'] > $b['name'] ) {
					return - 1;
				}

				return 0;
			}
		);

		// Find the first occurrence.
		foreach ( $dirlist as $fileinfo ) {
			if ( 1 === preg_match( '/' . DB_NAME . '\.[\d]+-[\d]+\.sql$/',$fileinfo['name'] ) ) {
				$db_dump_filepath = ABSPATH . $fileinfo['name'];
				break;
			}
		}

		// Return the array.
		return $db_dump_filepath;
	}

	/**
	 * Restore from a specified archive file.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	public function restore_archive_file( $dryrun = false ) {
		// Check if DOING_CRON.
		$doing_cron = ( true === defined( 'DOING_CRON' ) && DOING_CRON );

		// If a restoration was not requested, then abort.
		if ( true === empty( $_GET['restore_now'] ) ) {
			return false;
		}

		// If not DOING_CRON and not run from CLI, then verify nonce, or die.
		if ( false === $doing_cron && 'cli' !== php_sapi_name() ) {
			check_admin_referer( 'boldgrid-backup-restore', 'restore_auth' );
		}

		// Check if functional.
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action( 'boldgrid_backup_notice',
					'Functionality test has failed.  You can go to <a href="' .
					 admin_url( 'admin.php?page=boldgrid-backup-test' ) .
					 '">Functionality Test</a> to view a report.',
					'notice notice-error is-dismissible'
				);
			}

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

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action( 'boldgrid_backup_notice',
					'Invalid key for the selected archive file.',
					'notice notice-error is-dismissible'
				);
			} else {
				return array(
					'error' => 'Invalid key for the selected archive file.',
				);
			}
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = sanitize_text_field( wp_unslash( $_GET['archive_filename'] ) );
		} else {
			$restore_ok = false;

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action( 'boldgrid_backup_notice',
					'Invalid filename for the selected archive file.',
					'notice notice-error is-dismissible'
				);
			} else {
				return array(
					'error' => 'Invalid filename for the selected archive file.',
				);
			}
		}

		// Get archive list.
		if ( true === $restore_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( true === $restore_ok && true === empty( $archives ) ) {
			$restore_ok = false;

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action( 'boldgrid_backup_notice',
					'No archive files were found.',
					'notice notice-error is-dismissible'
				);
			} else {
				return array(
					'error' => 'No archive files were found.',
				);
			}
		}

		// Locate the filename by key number.
		if ( true === $restore_ok ) {
			$filename = ( false === empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null );
		}

		// Verify specified filename.
		if ( true === $restore_ok && $archive_filename !== $filename ) {
			$restore_ok = false;

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action( 'boldgrid_backup_notice',
					'The selected archive file was not found.',
					'notice notice-error is-dismissible'
				);
			} else {
				return array(
					'error' => 'The selected archive file was not found.',
				);
			}
		}

		// Get the file path to restore.
		$filepath = ( false === empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null );

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the archive file size.
		if ( false === empty( $filepath ) && true === $wp_filesystem->exists( $filepath ) ) {
			$filesize = $wp_filesystem->size( $filepath );
		} else {
			$filesize = 0;

			$restore_ok = false;
		}

		// Populate $info.
		$info = array(
			'mode' => 'restore',
			'dryrun' => $dryrun,
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

			// Set the PHP timeout limit to at least 300 seconds.
			set_time_limit( ( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 ) );

			// Check the ZIP file for consistency.
			$zip = new ZipArchive;

			$status = $zip->open( $filepath, ZipArchive::CHECKCONS );

			if ( true !== $status ) {
				$info['error'] = 'Cannot unzip archive file "' . $filepath . '".  Error code: ' .
				$status . ' (' . Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ) .
				') ';

				$restore_ok = false;
			} elseif ( true !== $dryrun ) {
				// Set the WordPress root directory path, with no trailing slash.
				$wp_root = untrailingslashit( ABSPATH );

				// Ensure that all targets are writable.
				for ( $i = 0; $i < $zip->numFiles; $i++ ) {
					// Get the entry name.
					$name = $zip->getNameIndex( $i );

					// If path exists and is not writable, then make writable.
					if ( false === Boldgrid_Backup_Admin_Utility::make_writable( ABSPATH . $name ) ) {
						$restore_ok = false;

						break;
					}

					// Extract the file.
					if ( true !== $zip->extractTo( $wp_root, array( $name ) ) ) {
						// Error extracting.
						error_log( __METHOD__ .
							': Error extracting "' . ABSPATH . $name . '" from archive file "' .
							$filepath . '".'
						);

						$restore_ok = false;

						break;
					}
				}
			}

			// Close the ZIP file.
			$zip->close();

			// Restore database.
			if ( true !== $dryrun && true === $restore_ok ) {
				// Get the database dump file path.
				$db_dump_filepath = $this->get_dump_file();

				// Restore the database.
				$restore_ok = $this->restore_database( $db_dump_filepath );
			}
		}

		// Display notice of deletion status.
		if ( false === $restore_ok && false === $doing_cron ) {
			do_action( 'boldgrid_backup_notice',
				'Error restoring the selected archive file.',
				'notice notice-error is-dismissible'
			);
		} else {
			return array(
				'error' => 'Error restoring the selected archive file.',
			);
		}

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for restoration completed.
		if ( false === empty( $settings['notifications']['restore'] ) ) {
			// Create a site identifier.
			$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

			// Create subject.
			$subject = __( 'Restoration completed for ' ) . $site_id;

			// Create message.
			$body = __( "Hello,\n\n" );

			if ( true !== $dryrun ) {
				$body .= __( 'THIS OPERATION WAS A DRY-RUN TEST.' );
			}

			if ( true === $restore_ok ) {
				$body .= __( 'A backup archive has been restored' );
			} else {
				$body .= __( 'An error occurred when attempting to restore a backup archive' );

				if ( false === empty( $wp_errors ) ) {
					$body .= $wp_errors;
				}
			}

			$body .= __( ' for ' ) . $site_id . ".\n\n";

			$body .= __( "Restoration details:\n" );

			$body .= __( 'Archive file path: ' ) . $info['filepath'] . "\n";

			$body .= __( 'Archive file size: ' ) .
			Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] ) . "\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= __( "The restoration request was made via WP-CRON.\n\n" );
			}

			$body .= __(
				"You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings.\n\n"
			);

			$body .= __( "Best regards,\n\nThe BoldGrid Backup plugin\n\n" );

			// Send the notification.
			$info['mail_success'] = $this->send_notification( $subject, $body );
		}

		// Update status.
		$info['restore_ok'] = $restore_ok;

		// Return info array.
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
		// Run the functionality tests.
		$is_functional = $this->test->get_is_functional();

		// If tests fail, then show an admin notice and abort.
		if ( false === $is_functional ) {
			do_action( 'boldgrid_backup_notice',
				'Functionality test has failed.  You can go to <a href="' .
				admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				'">Functionality Test</a> to view a report.',
				'notice notice-error is-dismissible'
			);

			return;
		}

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

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Create a nonce for file downloads via AJAX.
		$download_nonce = wp_create_nonce( 'archive_download' );

		// Create text for the restoration confirmation.
		$restore_confirm_text = __(
			'Please confirm the restoration of this WordPress installation from the archive file'
		);

		// Create text for the deletion confirmation.
		$delete_confirm_text = __( 'Please confirm the deletion the archive file' );

		// Create URL for backup now.
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'downloadNonce' => $download_nonce,
			'accessType' => $access_type,
			'restoreConfirmText' => $restore_confirm_text,
			'deleteConfirmText' => $delete_confirm_text,
			'backupUrl' => $backup_url,
		);

		// Add localize script data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-home', 'localizeScriptData', $localize_script_data );

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

		// If a restoration operation is requested, then restore from a backup archive now.
		if ( false === empty( $_GET['restore_now'] ) ) {
			$archive_info = $this->restore_archive_file();

			// Generate markup, using the restore page template.
			include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup.php';
		}

		return;
	}

	/**
	 * Callback function for creating a backup archive file now via AJAX.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Core::archive_files()
	 */
	public function boldgrid_backup_now_callback() {
		// Verify nonce.
		if ( false === isset( $_POST['backup_auth'] ) ||
			1 !== check_ajax_referer( 'boldgrid_backup_now', 'backup_auth', false ) ) {
				wp_die( '<div class="error"><p>Security violation (invalid nonce).</p></div>' );
		}

		// Perform the backup operation.
		$archive_info = $this->archive_files( true );

		// Generate markup, using the backup page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup.php';

		// If updating WordPress, then queue a restoration cron job via WP option.
		if ( true === isset( $_POST['is_updating'] ) && 'true' === $_POST['is_updating'] ) {
			if ( true === is_multisite() ) {
				update_site_option( 'boldgrid_backup_pending_rollback', $archive_info );
			} else {
				update_option( 'boldgrid_backup_pending_rollback', $archive_info );
			}
		}

		// End nicely.
		wp_die();
	}

	/**
	 * Callback function for downloading an archive file via AJAX.
	 *
	 * This callback function should only be called if the WP_Filesystem method is "direct", or
	 * a message should be displayed with the path to download using an alternate method.
	 *
	 * @since 1.0
	 */
	public function download_archive_file_callback() {
		// Verify nonce, or die.
		check_ajax_referer( 'archive_download', 'wpnonce' );

		// Validate download_key.
		if ( true === is_numeric( $_POST['download_key'] ) ) {
			$download_key = sanitize_text_field( wp_unslash( $_POST['download_key'] ) );
		} else {
			echo __( 'INVALID DOWNLOAD KEY' );
			wp_die();
		}

		// Validate download_filename.
		if ( false === empty( $_POST['download_filename'] ) ) {
			$download_filename = sanitize_text_field( wp_unslash( $_POST['download_filename'] ) );
		} else {
			echo __( 'INVALID DOWNLOAD FILENAME' );
			wp_die();
		}

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Check WP_Filesystem method; ensure it is "direct".
		if ( 'direct' !== $access_type ) {
			echo __( 'WP_Filesystem method is not "direct"' );
			wp_die();
		}

		// Get archive list.
		$archives = $this->get_archive_list( $download_filename );

		// If no files were found, then abort.
		if ( true === empty( $archives ) ) {
			echo __( 'NO BACKUP ARCHIVES FOUND' );
			wp_die();
		}

		// Locate the filename by key number.
		$filename = ( false === empty( $archives[ $download_key ]['filename'] ) ? $archives[ $download_key ]['filename'] : null );

		// Verify filename.
		if ( $download_filename !== $filename ) {
			echo __( 'FILE NOT FOUND' );
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

		// Send the file.  Not finding a replacement in $wp_filesystem.
		readfile( $filepath );

		// Exit.
		wp_die();
	}

	/**
	 * Menu callback to display the Backup functionality test page.
	 *
	 * @since 1.0
	 *
	 * @global string $wp_version The WordPress version string.
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return null
	 */
	public function page_backup_test() {
		// Perform functionality tests.
		$is_functional = $this->test->run_functionality_tests();

		if ( false === $is_functional ) {
			// Display an error notice.
			do_action( 'boldgrid_backup_notice', 'Functionality test has failed.',
				'notice notice-error is-dismissible'
			);
		}

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Get the WordPress version.
		global $wp_version;

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Get the database size.
		$db_size = $this->test->get_database_size();

		// Get the database character set.
		$db_charset = $wpdb->charset;

		// Get the database collation.
		$db_collate = $wpdb->collate;

		// Get archive info, if plugin is functional.
		if ( true === $is_functional ) {
			$archive_info = $this->archive_files( false );
		}

		// Get disk space information array.
		if ( false === empty( $archive_info['total_size'] ) ) {
			$disk_space = $this->test->get_disk_space( false );

			$disk_space[3] = $archive_info['total_size'];
		} else {
			$disk_space = $this->test->get_disk_space();
		}

		// Load template view.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-test.php';

		return;
	}

	/**
	 * Send a notification email to the admin email address.
	 *
	 * @since 1.0
	 * @access private
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
	 * Admin notice template.
	 *
	 * @since 1.0
	 *
	 * @param string $message A message to display in the admin notice.
	 * @param string $class The class string for the div.
	 * @return null
	 */
	public function notice_template( $message, $class = 'notice notice-error is-dismissible' ) {
		$message = __( $message, 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}

	/**
	 * Print cron report.
	 *
	 * @since 1.0
	 *
	 * @param array $archive_info An array of archive file information.
	 */
	public function print_cron_report( $archive_info ) {
		// Validate mode.
		if ( true === empty( $archive_info['mode'] ) ) {
			die( __( 'Error: A mode was not specified.' ) . PHP_EOL );
		}

		$valid_modes = array(
			'backup',
			'restore',
		);

		if ( false === in_array( $archive_info['mode'], $valid_modes, true ) ) {
			die( __( 'Error: Invalid mode "' . $archive_info['mode'] . '".' ) . PHP_EOL );
		}

		// Create action name.
		switch ( $archive_info['mode'] ) {
			case 'backup' :
				$action_name = 'creating';
				break;

			case 'restore' :
				$action_name = 'restoring';
				break;

			default :
				$action_name = 'handling';
				break;
		}

		// Print report.
		if ( false === empty( $archive_info['error'] ) ) {
			// Error.
			echo __( 'There was an error ' . $action_name . 'backup archive file.' ) . PHP_EOL;
			echo __( 'Error: ' . $archive_info['error'] ) . PHP_EOL;

			if ( true === isset( $archive_info['error_message'] ) ) {
				echo __( 'Error Message: ' . $archive_info['error_message'] );
			}

			if ( true === isset( $archive_info['error_code'] ) ) {
				echo ' (' . $archive_info['error_code'] . ')';
			}

			echo PHP_EOL;
		} elseif ( false === empty( $archive_info['filesize'] ) || false === empty( $archive_info['dryrun'] ) ) {
			// Dry run.
			if ( false === empty( $archive_info['filepath'] ) ) {
				echo __( 'File Path: ' ) . $archive_info['filepath'] . PHP_EOL;
			}

			if ( false === empty( $archive_info['filesize'] ) ) {
				echo __( 'File Size: ' ) .
				Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ) .
				PHP_EOL;
			}

			if ( false === empty( $archive_info['total_size'] ) ) {
				echo __( 'Total size: ' ) .
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) .
					PHP_EOL;
			}

			if ( false === empty( $archive_info['compressor'] ) ) {
				echo __( 'Compressor: ' ) . $archive_info['compressor'] . PHP_EOL;
			}

			if ( true === isset( $archive_info['duration'] ) ) {
				echo __( 'Duration: ' . $archive_info['duration'] . ' seconds' ) . PHP_EOL;
			}
		} else {
			// Unknown error.
			echo __( 'There was an unknown error ' . $action_name . ' a backup archive file.' ) .
			PHP_EOL;
		}
	}

	/**
	 * Show an admin notice on the WordPress Updates page.
	 *
	 * @since 1.0
	 */
	public function backup_notice() {
		// Get pending rollback information.
		if ( true === is_multisite() ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );
		}

		// Get the unix time for 15 minutes ago.
		$time_15_minutes_ago = strtotime( 'NOW - 15 MINUTES' );

		// If there is a pending rollback, then abort.
		if ( false === empty( $pending_rollback['lastmodunix'] ) &&
			$pending_rollback['lastmodunix'] > $time_15_minutes_ago ) {
			return;
		}

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archive count.
		$archive_count = count( $archives );

		// Initialize $listing.
		$listing = 'None';

		// Initialize $download_button.
		$download_button = '';

		// Get the most recent archive listing.
		if ( $archive_count > 0 ) {
			$key = $archive_count - 1;

			$archive = $archives[ $key ];

			$listing = $archive['filename'] . ' ' .
			Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] ) . ' ' .
			$archive['filedate'];

			$download_button = "<a id='backup-archive-download-<?php echo $key; ?>'
			class='button action-download' href='#'
			data-key='" . $key . "' data-filepath='" . $archive['filepath'] . "'
			data-filename='" . $archive['filename'] . "'>Download</a>";
		}

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

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Create a nonce for file downloads via AJAX.
		$download_nonce = wp_create_nonce( 'archive_download' );

		// Create URL for backup now.
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'downloadNonce' => $download_nonce,
			'accessType' => $access_type,
			'backupUrl' => $backup_url,
		);

		// Add localize script data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-home', 'localizeScriptData', $localize_script_data );

		// Enqueue JS for the home page.
		wp_enqueue_script( 'boldgrid-backup-admin-home' );

		// Create admin notice text.
		$notice_text = 'BoldGrid Backup last created backup archive:<p>' . $listing . ' ' .
		$download_button . "</p>
		<p>It is recommended to backup your site before performing updates.</p>
		<div id='backup-site-now-section'>
		<form action='#' id='backup-site-now-form' method='POST'>
				". wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' ) ."
				<p>
					<a id='backup-site-now' class='button' data-updating='true'>Backup Site Now</a>
					<span class='spinner'></span>
				</p>
			</form>
		</div>
		<div id='backup-site-now-results'></div>
";

		// Show admin notice.
		do_action( 'boldgrid_backup_notice', $notice_text, 'notice notice-warning is-dismissible' );
	}

	/**
	 * Show an admin notice if there is a pending rollback.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function rollback_notice() {
		// Get pending rollback information.
		if ( true === is_multisite() ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );
		}

		// If there is not pending rollback, then abort.
		if ( true === empty( $pending_rollback ) ) {
			return;
		}

		// If a backup was just made, but pending an update, then display a notice and return.
		if ( false === isset( $pending_rollback['deadline'] ) ) {
			$notice_text = 'A recent backup was made.  ' .
				'Once updates are completed, there will be a pending automatic rollback.  ' .
			'If there are no issues, then you may cancel the rollback operation.';

			do_action( 'boldgrid_backup_notice', $notice_text, 'notice notice-warning' );

			return;
		}

		// Register the JS for the rollback notice.
		wp_register_script( 'boldgrid-backup-admin-rollback',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-rollback.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Convert deadline to ISO 8601 format.
		$deadline = date( 'c', $pending_rollback['deadline'] );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'rolloutDeadline' => $deadline,
		);

		// Add localize script data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-rollback', 'localizeScriptData', $localize_script_data );

		// Enqueue JS for the rollback notice.
		wp_enqueue_script( 'boldgrid-backup-admin-rollback' );

		// Create notice text.
		$notice_text = "<div id='cancel-rollback-section'>
		There is a pending automatic rollback using the most recent backup archive." .
		"<p>If you do not want to rollback, then please cancel the action before the countdown timer elapses.</p>
		<p>Countdown: <span id='rollback-countdown-timer'></span></p>
		<form action='#' id='cancel-rollback-form' method='POST'>
		" . wp_nonce_field( 'boldgrid_rollback_notice', 'cancel_rollback_auth', true, false ) . "
		<p>
		<a id='cancel-rollback-button' class='button'>Cancel Rollback</a>
		<span class='spinner'></span>
		</p>
		</form>
		</div>
		<div id='cancel-rollback-results'></div>
";

		// Display notice.
		do_action( 'boldgrid_backup_notice', $notice_text, 'notice notice-warning' );

		return;
	}

	/**
	 * Callback function for canceling a pending rollback.
	 *
	 * @since 1.0
	 */
	public function boldgrid_cancel_rollback_callback() {
		// Verify nonce, or die with an error message.
		if ( false === isset( $_POST['cancel_rollback_auth'] ) ||
			1 !== check_ajax_referer( 'boldgrid_rollback_notice', 'cancel_rollback_auth', false ) ) {
			wp_die(
				'<div class="error"><p>Security violation (invalid nonce).</p></div>'
			);
		}

		// Remove any cron jobs for restore actions.
		$this->settings->delete_cron_entries( 'restore' );

		// Remove WP option boldgrid_backup_pending_rollback.
		if ( true === is_multisite() ) {
			delete_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			delete_option( 'boldgrid_backup_pending_rollback' );
		}

		// Echo a success message.
		echo '<p>Automatic rollback has been canceled.</p>';

		// End nicely.
		wp_die();
	}

	/**
	 * Creating a backup archive file now, before an auto-update occurs.
	 *
	 * @since 1.0
	 *
	 * @link https://developer.wordpress.org/reference/hooks/pre_auto_update/
	 * @see Boldgrid_Backup_Admin_Core::archive_files()
	 *
	 * @param string $type The type of update being checked: 'core', 'theme', 'plugin', or 'translation'.
	 * @return null
	 */
	public function boldgrid_backup_now_auto( $type ) {
		// Get backup settings.
		$settings = $this->settings->get_settings();

		// Abort if auto-backup is not enabled.
		if ( true === empty( $settings['auto_backup'] ) ) {
			return;
		}

		// Get the last backup time (unix seconds).
		$last_backup_time = get_option( 'boldgrid_backup_last_backup' );

		// If the last backup was done in the last hour, then abort.
		if ( false !== $last_backup_time && ( time() - $last_backup_time ) <= HOUR_IN_SECONDS ) {
			return;
		}

		// Perform the backup operation.
		$archive_info = $this->archive_files( true );

		return;
	}

	/**
	 * Enforce backup archive retention setting.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 *
	 * @return null
	 */
	private function enforce_retention() {
		// Get backup settings.
		$settings = $this->settings->get_settings();

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archives file count.
		$archives_count = count( $archives );

		// If the archive count is not beyond the set retention count, then return.
		if ( $archives_count <= $settings['retention_count'] ) {
			return;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $counter.
		$counter = 0;

		// Delete old backups.
		while ( $archives_count > $settings['retention_count'] ) {
			// Get the file path to delete.
			$filepath = ( false === empty( $archives[ $counter ]['filepath'] ) ? $archives[ $counter ]['filepath'] : null );

			// Delete the specified archive file.
			if ( null === $filepath || true !== $wp_filesystem->delete( $filepath, false, 'f' ) ) {
				// Something went wrong.
				break;
			}

			// Decrease the archive count.
			$archives_count --;

			// Increment the counter.
			$counter ++;
		}

		return;
	}
}
