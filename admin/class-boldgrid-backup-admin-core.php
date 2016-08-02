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
	 * A unique identifier for backups of this WordPress installation.
	 *
	 * @since 1.0.1
	 * @access private
	 * @var string
	 */
	private $backup_identifier = null;

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

		// Instantiate Boldgrid_Backup_Admin_Notice.
		$this->notice = new Boldgrid_Backup_Admin_Notice( $this );

		// Ensure there is a backup identifier.
		$this->get_backup_identifier();
	}

	/**
	 * Get the unique identifier for backups of this WordPress installation.
	 *
	 * @since 1.0.1
	 *
	 * @return string A unique identifier for backups.
	 */
	public function get_backup_identifier() {
		// If the id was already stored, then return it.
		if ( false === empty( $this->backup_identifier ) ) {
			return $this->backup_identifier;
		}

		// Determine if multisite.
		$is_multisite = is_multisite();

		// Check wp_options for the id.
		if ( true === $is_multisite ) {
			$backup_identifier = get_site_option( 'boldgrid_backup_id' );
		} else {
			$backup_identifier = get_option( 'boldgrid_backup_id' );
		}

		// If the id was already stored in WP options, then save and return it.
		if ( false === empty( $backup_identifier ) ) {
			$this->backup_identifier = $backup_identifier;

			return $backup_identifier;
		}

		// Generate a new backup id.
		$admin_email = $this->config->get_admin_email();

		$unique_string = site_url() . ' <' . $admin_email . '>';

		$backup_identifier = hash( 'crc32', hash( 'sha512', $unique_string ) );

		// If something went wrong with hashing, then just use a random string to make the id.
		if ( true === empty( $backup_identifier ) ) {
			$random_string = '';

			for ( $i = 0; $i <= 32; $i ++ ) {
				$random_string .= chr( mt_rand( 40, 126 ) );
			}

			$backup_identifier = hash( 'crc32', $random_string );
		}

		// Save and return the id.
		$this->backup_identifier = $backup_identifier;

		if ( true === $is_multisite ) {
			update_site_option( 'boldgrid_backup_id', $backup_identifier );
		} else {
			update_option( 'boldgrid_backup_id', $backup_identifier );
		}

		return $backup_identifier;
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
	 * @param int    $return_var If present, the return_var, passed back to the caller.
	 * @return string|bool Returns the command output or FALSE on error.
	 */
	public function execute_command( $command, $available_exec_functions = array(), &$success = false, &$return_var = 0 ) {
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
			$message = sprintf(
				esc_html__(
					'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
					'boldgrid-backup'
				),
				admin_url( 'admin.php?page=boldgrid-backup-test' )
			);

			do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );

			return false;
		}

		// If mysqldump is not available, then fail.
		if ( true !== $this->test->is_mysqldump_available() ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'mysqldump is not available.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The backup directory is not writable.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Could not create a mysql defaults file.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Could not determine mysql tables names.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return 0;
		}

		// Get the result.
		$tables = $wpdb->get_results( $query, ARRAY_N );

		// If there was an error or nothing returned, then fail.
		if ( empty( $tables ) ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'No results when getting mysql table names.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Could not create a table names file.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'mysqldump was not successful.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Check if the dump file was written.
		if ( false === $wp_filesystem->exists( $db_dump_filepath ) ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'A mysql dump file was not created.', 'boldgrid-backup' ),
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
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The database dump file path was not specified.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Check if functional.
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice.
			$message = sprintf(
				esc_html__(
					'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
					'boldgrid-backup'
				),
				admin_url( 'admin.php?page=boldgrid-backup-test' )
			);

			do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );

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
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__(
					'Error restoring database; Could not create a temporary mysql defaults file.',
					'boldgrid-backup'
				),
				'notice notice-error is-dismissible'
			);

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
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The mysql command was not successful.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

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
		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		// Create a file path with no extension (added later).
		$filepath = $backup_directory . '/boldgrid-backup-' . $site_id . '-' . $backup_identifier .
			'-' . date( 'Ymd-His' );

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
				$message = sprintf(
					esc_html(
						'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
						'boldgrid-backup'
					),
					admin_url( 'admin.php?page=boldgrid-backup-test' )
				);

				do_action(
					'boldgrid_backup_notice',
					$message,
					'notice notice-error is-dismissible'
				);
			}

			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		// Close any PHP session, so that another session can open during the backup operation.
		session_write_close();

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

					// Get file modification time.
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
		$info['duration'] = number_format( ( $time_stop - $time_start ), 2, '.', '' );

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for backup completed.
		if ( false === empty( $settings['notifications']['backup'] ) ) {
			// Create a site identifier.
			$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

			// Create subject.
			$subject = sprintf(
				__( 'Backup completed for %s', 'boldgrid-backup' ),
				$site_id
			);

			// Create message.
			$body = esc_html__( 'Hello', 'boldgrid-backup' ) . ",\n\n";

			if ( true === $dryrun ) {
				$body .= esc_html__( 'THIS OPERATION WAS A DRY-RUN TEST', 'boldgrid-backup' ) . ".\n\n";
			}

			$body .= sprintf(
				esc_html__( 'A backup archive has been created for %s', 'boldgrid-backup' ),
				$site_id
			) . ".\n\n";

			$body .= esc_html__( 'Backup details', 'boldgrid-backup' ) . ":\n";

			$body .= sprintf(
				esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
				$info['duration']
			) . "\n";

			$body .= sprintf(
				esc_html__( 'Total size: %s', 'boldgrid-backup' ),
				Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['total_size'] )
			) . "\n";

			$body .= sprintf(
				esc_html__( 'Archive file path: %s', 'boldgrid-backup' ),
				$info['filepath']
			) . "\n";

			$body .= sprintf(
				esc_html__( 'Archive file size: %s', 'boldgrid-backup' ),
				Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] )
			) . "\n";

			$body .= sprintf(
				esc_html__( 'Compressor used: %s', 'boldgrid-backup' ),
				$info['compressor']
			) . "\n\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= esc_html__(
					'The backup request was made via CRON (task scheduler)', 'boldgrid-backup'
				) . ".\n\n";
			}

			$body .= esc_html__(
				'You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings',
				'boldgrid-backup'
			) . ".\n\n";

			$body .= esc_html__( 'Best regards', 'boldgrid-backup' ) . ",\n\n";

			$body .= esc_html__( 'The BoldGrid Backup plugin', 'boldgrid-backup' ) . "\n\n";

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
	 * Get information for the list of archive file(s) (in descending order by date modified).
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

		// Sort the dirlist array by "lastmodunix" descending.
		uasort( $dirlist,
			function ( $a, $b ) {
				if ( $a['lastmodunix'] < $b['lastmodunix'] ) {
					return 1;
				}

				if ( $a['lastmodunix'] > $b['lastmodunix'] ) {
					return - 1;
				}

				return 0;
			}
		);

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		// Initialize $index.
		$index = -1;

		// Filter the array.
		foreach ( $dirlist as $fileinfo ) {
			if (
				1 === preg_match(
					'/^boldgrid-backup-(' . $site_id . '|.*?-' . $backup_identifier .
					')-.*\.(zip|tar\.gz|b2z|zlib|lzf)$/',
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

			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			$archive_key = null;
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = $_GET['archive_filename'];
		} else {
			// Fail with a notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'No archive files were found.', 'boldgrid-backup' ),
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
				esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' ),
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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Error deleting the selected archive file.', 'boldgrid-backup' ),
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
		// Initialize $error.
		$error = array();

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
				$message = sprintf(
					esc_html__(
						'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
						'boldgrid-backup'
					),
					admin_url( 'admin.php?page=boldgrid-backup-test' )
				);

				do_action(
					'boldgrid_backup_notice',
					$message,
					'notice notice-error is-dismissible'
				);
			}

			return array(
				'error' => esc_html__( 'Functionality tests fail.', 'boldgrid-backup' ),
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
				$error[] = esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' );
			} else {
				return array(
					'error' => esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' ),
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
				$error[] = esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' );
			} else {
				return array(
					'error' => esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' ),
				);
			}
		}

		// Close any PHP session, so that another session can open during this restore operation.
		session_write_close();

		// Clear rollback information and restoration cron jobs that may be present.
		$this->cancel_rollback();

		// Get archive list.
		if ( true === $restore_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( true === $restore_ok && true === empty( $archives ) ) {
			$restore_ok = false;

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				$error[] = esc_html__( 'No archive files were found.', 'boldgrid-backup' );
			} else {
				return array(
					'error' => esc_html__( 'No archive files were found.', 'boldgrid-backup' ),
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
				$error[] = esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' );
			} else {
				return array(
					'error' => esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' ),
				);
			}
		}

		// If there are errors, then create a message from the combined errors found.
		if ( true !== $restore_ok || count( $error ) > 0 ) {
			// Initialize $errors string.
			$errors = '';

			foreach ( $error as $err ) {
				$errors .= $err . '<br />' . PHP_EOL;
			}

			$message = esc_html__( 'The requested restoration failed', 'boldgrid-backup' ) . '.<br />' .
			PHP_EOL . $errors;

			return array(
				'error' => $message,

			);
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

			// Display an error notice, if not DOING_CRON.
			if ( false === $doing_cron ) {
				do_action(
					'boldgrid_backup_notice',
					esc_html__( 'The selected archive file is empty.', 'boldgrid-backup' ),
					'notice notice-error is-dismissible'
				);
			}

			// Abort.
			return array(
				'error' => esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' ),
			);
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
				$info['error'] = sprintf(
					esc_html__(
						'Cannot unzip archive file "%s". Error code: %s (%s)', 'boldgrid-backup'
					),
					$filepath,
					$status,
					Boldgrid_Backup_Admin_Utility::translate_zip_error( $status )
				);

				$restore_ok = false;
				// Display an error notice, if not DOING_CRON.
				if ( false === $doing_cron ) {
					do_action( 'boldgrid_backup_notice',
						$info['error'],
						'notice notice-error is-dismissible'
					);
				} else {
					return array(
						'error' => $info['error'],
					);
				}
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

						// Display an error notice, if not DOING_CRON.
						if ( false === $doing_cron ) {
							do_action(
								'boldgrid_backup_notice',
								sprintf(
									esc_html__(
										'The file "%s" is not writable.',
										'boldgrid-backup'
									),
									$name
								),
								'notice notice-error is-dismissible'
							);
						} else {
							return array(
								'error' => sprintf(
									__( 'The file "%s" is not writable.', 'boldgrid-backup' ),
									$name
								),
							);
						}

						break;
					}

					// Extract the file.
					if ( true !== $zip->extractTo( $wp_root, array( $name ) ) ) {
						// Error extracting.
						$info['error'] = sprintf(
							esc_html__(
								'Error extracting "%s%s" from archive file "%s".',
								'boldgrid-backup'
							),
							ABSPATH,
							$name,
							$filepath
						);

						error_log( __METHOD__ . ': ' . $info['error'] );

						$restore_ok = false;

						// Display an error notice, if not DOING_CRON.
						if ( false === $doing_cron ) {
							do_action( 'boldgrid_backup_notice',
								$info['error'],
								'notice notice-error is-dismissible'
							);
						} else {
							return array(
								'error' => $info['error'],
							);
						}

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
		if ( false === $restore_ok ) {
			if ( false === $doing_cron ) {
				do_action(
					'boldgrid_backup_notice',
					esc_html__( 'Could not restore database.', 'boldgrid-backup' ),
					'notice notice-error is-dismissible'
				);
			} else {
				return array(
					'error' => esc_html__( 'Could not restore database.', 'boldgrid-backup' ),
				);
			}
		}

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for restoration completed.
		if ( false === empty( $settings['notifications']['restore'] ) ) {
			// Create a site identifier.
			$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

			// Create subject.
			$subject = sprintf(
				esc_html__( 'Restoration completed for %s', 'boldgrid-backup' ),
				$site_id
			);

			// Create message.
			$body = esc_html__( 'Hello', 'boldgrid-backup' ) . ",\n\n";

			if ( true === $dryrun ) {
				$body .= esc_html__( 'THIS OPERATION WAS A DRY-RUN TEST', 'boldgrid-backup' ) . ".\n\n";
			}

			if ( true === $restore_ok ) {
				$body .= esc_html__( 'A backup archive has been restored', 'boldgrid-backup' );
			} else {
				$body .= esc_html__(
					'An error occurred when attempting to restore a backup archive',
					'boldgrid-backup'
				);
			}

			$body .= sprintf(
				__( ' for %s', 'boldgrid-backup' ),
				$site_id
			) . ".\n\n";

			$body .= esc_html__( 'Restoration details', 'boldgrid-backup' ) . ":\n";

			$body .= sprintf(
				esc_html__( 'Archive file path: %s', 'boldgrid-backup' ),
				$info['filepath']
			) . "\n";

			$body .= sprintf(
				esc_html__( 'Archive file size: %s', 'boldgrid-backup' ),
				Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] )
			) . "\n";

			if ( defined( 'DOING_CRON' ) ) {
				$body .= esc_html__(
					'The restoration request was made via CRON (task scheduler)',
					'boldgrid-backup'
				) . ".\n\n";
			}

			$body .= esc_html__(
				'You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings',
				'boldgrid-backup'
			) . ".\n\n";

			$body .= esc_html__( 'Best regards', 'boldgrid-backup' ) . ",\n\n";

			$body .= esc_html__( 'The BoldGrid Backup plugin', 'boldgrid-backup' ) . "\n\n";

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
			$message = sprintf(
				esc_html__(
					'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
					'boldgrid-backup'
				),
				admin_url( 'admin.php?page=boldgrid-backup-test' )
			);

			do_action( 'boldgrid_backup_notice', $message, 'notice notice-error is-dismissible' );

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
		$restore_confirm_text = esc_html__(
			'Please confirm the restoration of this WordPress installation from the archive file',
			'boldgrid-backup'
		);

		// Create text for the deletion confirmation.
		$delete_confirm_text = esc_html__(
			'Please confirm the deletion the archive file',
			'boldgrid-backup'
		);

		// Create URL for backup now.
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'downloadNonce' => $download_nonce,
			'accessType' => $access_type,
			'restoreConfirmText' => $restore_confirm_text,
			'deleteConfirmText' => $delete_confirm_text,
			'backupUrl' => $backup_url,
			'errorText' => esc_html__(
				'There was an error processing your request.  Please reload the page and try again.',
				'boldgrid-backup'
			),
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
				wp_die(
					'<div class="error"><p>' .
					esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) .
					'</p></div>'
				);
		}

		// Perform the backup operation.
		$archive_info = $this->archive_files( true );

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archives file count.
		$archives_count = count( $archives );

		// Get the total size for all archives.
		$archives_size = 0;

		foreach ( $archives as $archive ) {
			$archives_size += $archive['filesize'];
		}

		// Make the archives total size human-readable.
		$archives_size = Boldgrid_Backup_Admin_Utility::bytes_to_human( $archives_size );

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
			esc_html_e( 'INVALID DOWNLOAD KEY', 'boldgrid-backup' );
			wp_die();
		}

		// Validate download_filename.
		if ( false === empty( $_POST['download_filename'] ) ) {
			$download_filename = sanitize_text_field( wp_unslash( $_POST['download_filename'] ) );
		} else {
			esc_html_e( 'INVALID DOWNLOAD FILENAME', 'boldgrid-backup' );
			wp_die();
		}

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Check WP_Filesystem method; ensure it is "direct".
		if ( 'direct' !== $access_type ) {
			esc_html_e( 'WP_Filesystem method is not "direct"', 'boldgrid-backup' );
			wp_die();
		}

		// Get archive list.
		$archives = $this->get_archive_list( $download_filename );

		// If no files were found, then abort.
		if ( true === empty( $archives ) ) {
			esc_html_e( 'NO BACKUP ARCHIVES FOUND', 'boldgrid-backup' );
			wp_die();
		}

		// Locate the filename by key number.
		$filename = ( false === empty( $archives[ $download_key ]['filename'] ) ? $archives[ $download_key ]['filename'] : null );

		// Verify filename.
		if ( $download_filename !== $filename ) {
			esc_html_e( 'FILE NOT FOUND', 'boldgrid-backup' );
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

		// Close any PHP session, so another session can open during the download.
		session_write_close();

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
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Functionality test has failed.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);
		}

		// Get the user home directory.
		$home_dir = $this->config->get_home_directory();

		// Get the mode of the directory.
		$home_dir_mode = $this->config->get_home_mode();

		// Check if home directory is writable.
		$home_dir_writable = $this->test->is_homedir_writable();

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

		// Enqueue CSS for the test page.
		wp_enqueue_style( 'boldgrid-backup-admin-test',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-test.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

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

		// Get settings, for the notification email address.
		$settings = $this->settings->get_settings();

		$admin_email = $settings['notification_email'];

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
	 * Print cron report.
	 *
	 * @since 1.0
	 *
	 * @param array $archive_info An array of archive file information.
	 */
	public function print_cron_report( $archive_info ) {
		// Validate mode.
		if ( true === empty( $archive_info['mode'] ) ) {
			esc_html_e( 'Error: A mode was not specified.', 'boldgrid-backup' );
			wp_die();
		}

		$valid_modes = array(
			'backup',
			'restore',
		);

		if ( false === in_array( $archive_info['mode'], $valid_modes, true ) ) {
			printf(
				esc_html__( 'Error: Invalid mode "%s".', 'boldgrid-backup' ),
				$archive_info['mode']
			);
			wp_die();
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
			printf(
				esc_html__( 'There was an error $s backup archive file.', 'boldgrid-backup' ),
				$action_name
			);

			echo PHP_EOL;

			printf(
				esc_html__( 'Error: %s', 'boldgrid-backup' ),
				$archive_info['error']
			);

			echo PHP_EOL;

			if ( true === isset( $archive_info['error_message'] ) ) {
				printf(
					esc_html__( 'Error Message: %s', 'boldgrid-backup' ),
					$archive_info['error_message']
				);
			}

			if ( true === isset( $archive_info['error_code'] ) ) {
				printf(
					' (%s)',
					$archive_info['error_code']
				);
			}

			echo PHP_EOL;
		} elseif ( false === empty( $archive_info['filesize'] ) || false === empty( $archive_info['dryrun'] ) ) {
			// Dry run.
			if ( false === empty( $archive_info['filepath'] ) ) {
				printf(
					esc_html__( 'File Path: %s', 'boldgrid-backup' ),
					$archive_info['filepath']
				);

				echo PHP_EOL;
			}

			if ( false === empty( $archive_info['filesize'] ) ) {
				printf(
					esc_html__( 'File Size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] )
				);

				echo PHP_EOL;
			}

			if ( false === empty( $archive_info['total_size'] ) ) {
				printf(
					esc_html__( 'Total size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] )
				);

				echo PHP_EOL;
			}

			if ( false === empty( $archive_info['compressor'] ) ) {
				printf(
					esc_html__( 'Compressor: %s', 'boldgrid-backup' ),
					$archive_info['compressor']
				);

				echo PHP_EOL;
			}

			if ( true === isset( $archive_info['duration'] ) ) {
				printf(
					esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
					$archive_info['duration']
				);

				echo PHP_EOL;
			}
		} else {
			// Unknown error.
			printf(
				esc_html__(
					'There was an unknown error %s a backup archive file.',
					'boldgrid-backup'
				),
				$action_name
			);

			echo PHP_EOL;
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

		// If there is a pending rollback, then abort.
		if ( false === empty( $pending_rollback['lastmodunix'] ) ) {
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
			$key = 0;

			$archive = $archives[ $key ];

			$listing = $archive['filename'] . ' ' .
			Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] ) . ' ' .
			$archive['filedate'];

			$download_button = "<a id='backup-archive-download-<?php echo $key; ?>'
			class='button action-download' href='#'
			data-key='" . $key . "' data-filepath='" . $archive['filepath'] . "'
			data-filename='" . $archive['filename'] . "'>Download</a>";
		}

		// Enqueue CSS.
		wp_enqueue_style( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS.
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

		// Enqueue JS.
		wp_enqueue_script( 'boldgrid-backup-admin-home' );

		// Create admin notice text.
		$notice_text = sprintf(
			__(
				"BoldGrid Backup last created backup archive:<p>%s %s</p>
		<p>It is recommended to backup your site before performing updates.
		If you perform a backup here, before performing updates, then an automatic rollback is possible.</p>
		<div id='backup-site-now-section'>
		<form action='#' id='backup-site-now-form' method='POST'>
				%s
				<p>
					<a id='backup-site-now' class='button button-primary' data-updating='true'>Backup Site Now</a>
					<span class='spinner'></span>
				</p>
			</form>
		</div>
		<div id='backup-site-now-results'></div>",
				'boldgrid-backup'
			),
			$listing,
			$download_button,
			wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' )
		) . PHP_EOL;

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
		// Get pending rollback deadline.
		$deadline = $this->get_rollback_deadline();

		// If there is not a pending rollback, then abort.
		if ( true === empty( $deadline ) ) {
			return;
		}

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archive count.
		$archive_count = count( $archives );

		// If the deadline has passed or no backup archives to restore, then remove the pending
		// rollback information and cron.
		if ( $deadline <= time() || 0 === $archive_count ) {
			// Clear rollback information.
			$this->cancel_rollback();

			return;
		}

		// Enqueue CSS.
		wp_enqueue_style( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS for the rollback notice.
		wp_register_script( 'boldgrid-backup-admin-rollback',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-rollback.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Create text for the restoration confirmation.
		$restore_confirm_text = esc_html__(
			'Please confirm the restoration of this WordPress installation from the archive file',
			'boldgrid-backup'
		);

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'restoreConfirmText' => $restore_confirm_text,
		);

		// Include the time (in ISO 8601 format).
		$localize_script_data['rolloutDeadline'] = date( 'c', $deadline );

		// Add localize script data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-rollback', 'localizeScriptData', $localize_script_data );

		// Enqueue JS for the rollback notice.
		wp_enqueue_script( 'boldgrid-backup-admin-rollback' );

		// Get the most recent archive listing.
		$key = 0;

		$archive = $archives[ $key ];

		// Create URL for restoring from an archive file.
		$restore_url = get_admin_url( null,
			'admin.php?page=boldgrid-backup&restore_now=1&archive_key=' . $key .
			'&archive_filename=' . $archive['filename']
		);

		// Create an array of arguments for the notice template.
		$args['restore_url'] = wp_nonce_url(
			$restore_url, 'boldgrid-backup-restore', 'restore_auth'
		);

		$args['restore_filename'] = $archive['filename'];

		$args['cancel_nonce_field'] = wp_nonce_field(
			'boldgrid_rollback_notice', 'cancel_rollback_auth', true, false
		);

		// Create notice markup.
		$notice_markup = $this->get_rollback_markup( $args );

		// Display notice.
		do_action( 'boldgrid_backup_notice', $notice_markup, 'notice notice-warning' );

		return;
	}

	/**
	 * Generate markup for the rollback notice.
	 *
	 * @since 1.2
	 * @access private
	 *
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 * 		@type string $cancel_nonce_field A WordPress nonce for the Cancel Rollback button form.
	 * 		@type string $restore_url URL used to perform the restoration.
	 * 		@type string $restore_filename Filename of the backup archive to be restored.
	 * }
	 * @return string The resulting markup.
	 */
	private function get_rollback_markup( $args ) {
		$notice_markup = "<div id='cancel-rollback-section'>" . PHP_EOL .
		esc_html__(
			'There is a pending automatic rollback using the most recent backup archive.',
			'boldgrid-backup'
		) . PHP_EOL . '<p>' .
		esc_html__(
			'If you do not want to rollback, then you must cancel the action before the countdown timer elapses.',
			'boldgrid-backup'
		) . '</p>' . PHP_EOL .
		'<p>' .
		esc_html__( 'Countdown', 'boldgrid-backup' ) .
		": <span id='rollback-countdown-timer'></span></p>
		<form action='#' id='cancel-rollback-form' method='POST'>
		" . $args['cancel_nonce_field'] . "
		<p>
		<a id='cancel-rollback-button' class='button'>" .
		esc_html__( 'Cancel Rollback', 'boldgrid-backup' ) .
		"</a>
		<span class='spinner'></span>
		</p>
		</form>
		</div>
		<div id='restore-now-section'>
		<p>" .
		esc_html__(
			'You can click the button below to rollback your site now.',
			'boldgrid-backup'
		) .
		"</p>
		<p>
		<a class='button action-restore' href='" . $args['restore_url'] . "' data-filename='" .
		$args['restore_filename'] . "'>" .
		esc_html__( 'Rollback Site Now' , 'boldgrid-backup' ) .
		"</a>
		<span class='spinner'></span>
		</p>
		</div>
		<div id='cancel-rollback-results'></div>
";

		return $notice_markup;
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

		// Clear rollback information.
		$this->cancel_rollback();

		// Echo a success message.
		echo '<p>Automatic rollback has been canceled.</p>';

		// End nicely.
		wp_die();
	}

	/**
	 * Cancel rollback.
	 *
	 * @since 1.0.1
	 */
	public function cancel_rollback() {
		// Remove any cron jobs for restore actions.
		$this->settings->delete_cron_entries( 'restore' );

		// Remove WP option boldgrid_backup_pending_rollback.
		$this->settings->delete_rollback_option();
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
		if ( true === empty( $settings['retention_count'] ) || $archives_count <= $settings['retention_count'] ) {
			return;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $counter.
		$counter = $archives_count - 1;

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
			$counter --;
		}

		return;
	}

	/**
	 * Get the pending rollback deadline (in unix seconds).
	 *
	 * @since 1.2
	 *
	 * @return int The pending rollback deadline in unix seconds, or zero if not present.
	 */
	public function get_rollback_deadline() {
		// Get pending rollback information.
		if ( true === is_multisite() ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );
		}

		// Return pending rollback deadline, or 0 if not present.
		if ( true === empty( $pending_rollback['deadline'] ) ) {
			return 0;
		} else {
			return $pending_rollback['deadline'];
		}
	}

	/**
	 * Callback function for the hook "upgrader_process_complete".
	 *
	 * @since 1.2
	 *
	 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 *
	 * @return null
	 */
	public function upgrader_process_complete() {
		// Add/update restoration cron job.
		$this->settings->add_restore_cron();

		// If not on an admin page, then abort.
		if ( false === is_admin() ) {
			return;
		}

		// Get pending rollback deadline.
		$deadline = $this->get_rollback_deadline();

		// If there is not a pending rollback, then abort.
		if ( true === empty( $deadline ) ) {
			return;
		}

		// Get the ISO time (in ISO 8601 format).
		$iso_time = date( 'c', $deadline );

		// Print a hidden div with the time, so that JavaScript can read it.
?>
<div class='hidden' id='rollback-deadline'><?php echo $iso_time; ?></div>
<?php
	}
}
