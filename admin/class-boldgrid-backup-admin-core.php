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
			// Display an error notice, if not already on the test page.
			if ( false === isset( $_GET['page'] ) || 'boldgrid-backup-test' !== $_GET['page'] ) {
				do_action( 'boldgrid_backup_notice',
					'Functionality test has failed.  You can go to <a href="' .
					admin_url( 'admin.php?page=boldgrid-backup-test' ) .
					'">Functionality Test</a> to view a report.',
					'notice notice-error is-dismissible'
	 			);
			}

			return false;
		}

		// If mysqldump is not available, then fail.
		if ( true !== $this->test->is_mysqldump_available() ) {
			return false;
		}

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}
		// Create a mysql defaults file.
		$defaults_filepath = $backup_directory . '/mysqldump.cnf';

		$defaults_file_data = '[client]' . PHP_EOL . 'host=' . DB_HOST . PHP_EOL . 'user=' . DB_USER .
			 PHP_EOL . 'password=' . DB_PASSWORD . PHP_EOL;

		$status = $wp_filesystem->put_contents( $defaults_filepath, $defaults_file_data, 0600 );

		// Check if the defaults file was written.
		if ( true !== $status || false === $wp_filesystem->exists( $defaults_filepath ) ) {
			return false;
		}

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
			return 0;
		}

		// Get the result.
		$tables = $wpdb->get_results( $query, ARRAY_N );

		// If there was an error or nothing returned, then fail.
		if ( empty( $tables ) ) {
			return 0;
		}

		// Build a command to backup the database with mysqldump.
		$command = 'mysqldump --defaults-file=' . $defaults_filepath .
			 ' --dump-date --opt --tz-utc --result-file=' . $db_dump_filepath . ' ' . DB_NAME;

		// Insert the table names from the query results (one row per result (index 0)).
		foreach ( $tables as $table ) {
			$command .= ' ' . $table[0];
		}

		// Set the PHP timeout limit to at least 300 seconds.
		set_time_limit(
		( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > 300 ? $max_execution_time : 300 ) );

		// Execute the command.
		$output = $this->execute_command( $command, null, $status );

		// Check command status.
		if ( false === $output || true !== $status ) {
			return false;
		}

		// Remove the defaults file.
		$wp_filesystem->delete( $defaults_filepath, false, 'f' );

		// Check if the dump file was written.
		if ( false === $wp_filesystem->exists( $db_dump_filepath ) ) {
			return false;
		}

		// Limit file permissions to the dump file.
		$wp_filesystem->chmod( $db_dump_filepath, 0600 );

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
	 * @see get_filelist
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
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param bool $save A switch to save the archive file. Default is FALSE.
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	private function archive_files( $save = false, $dryrun = false ) {
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
			$wp_filesystem->chmod( $info['filepath'], 0600 );

			// Add some statistics to the return.
			$info['filesize'] = $wp_filesystem->size( $info['filepath'] );
		}

		// Delete the temporary database dump file.
		$wp_filesystem->delete( $this->db_dump_filepath, false, 'f' );

		// Stop timer.
		$time_stop = microtime( true );

		// Calculate duration.
		$info['duration'] = number_format( ( $time_stop - $time_start ), 4, '.', '' );

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for backup completed.
		if ( false === empty( $settings['notifications']['backup'] ) && true !== $dryrun ) {
			// Create a site identifier.
			$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

			// Create subject.
			$subject = __( 'Backup completed for ' ) . $site_id;

			// Create message.
			$body = __( "Hello,\n\n" );

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
				$body .= __( "The backup request was made via WP-CRON (WordPress task scheduler).\n\n" );
			}

			$body .= __(
				"You can manage notifications in your WordPress admin panel, under BoldGrid Backup Settings.\n\n"
			);

			$body .= __( "Best regards,\n\nThe BoldGrid Backup plugin\n\n" );

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
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $download_filename A filename to match to get info.
	 * @return array An array containing file path, filename, data, and size of archive files.
	 */
	private function get_archive_list( $download_filename = null ) {
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

		// Initialize $index.
		$index = -1;

		// Filter the array.
		foreach ( $dirlist as $fileinfo ) {
			if ( 1 === preg_match( '/^boldgrid-backup-.*\.(zip|tar\.gz|b2z|zlib|lzf)$/', $fileinfo['name'] )
			) {
				// Increment the index.
				$index++;

				// If looking for one match, skip an iteration if not the matching filename.
				if ( false === empty( $download_filename ) && $download_filename !== $fileinfo['name'] ) {
					continue;
				}

				$archive_files[ $index ] = array(
					'filepath' => $backup_directory . '/' . $fileinfo['name'],
					'filename' => $fileinfo['name'],
					'filedate' => get_date_from_gmt(
						date( 'Y-m-d H:i:s', $fileinfo['lastmodunix'] ), 'n/j/Y g:i A'
					),
					'filesize' => $fileinfo['size'],
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
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
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
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice.
			do_action( 'boldgrid_backup_notice',
				'Functionality test has failed.  You can go to <a href="' .
				 admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				 '">Functionality Test</a> to view a report.',
				'notice notice-error is-dismissible'
			);

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

			do_action( 'boldgrid_backup_notice',
				'Invalid key for the selected archive file.',
				'notice notice-error is-dismissible'
			);
		}

		// Validate archive_filename.
		if ( false === empty( $_GET['archive_filename'] ) ) {
			$archive_filename = sanitize_text_field( wp_unslash( $_GET['archive_filename'] ) );
		} else {
			$restore_ok = false;

			do_action( 'boldgrid_backup_notice',
				'Invalid filename for the selected archive file.',
				'notice notice-error is-dismissible'
			);
		}

		// Get archive list.
		if ( true === $restore_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( true === $restore_ok && true === empty( $archives ) ) {
			$restore_ok = false;

			do_action( 'boldgrid_backup_notice',
				'No archive files were found.',
				'notice notice-error is-dismissible'
			);
		}

		// Locate the filename by key number.
		if ( true === $restore_ok ) {
			$filename = ( false === empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null );
		}

		// Verify specified filename.
		if ( true === $restore_ok && $archive_filename !== $filename ) {
			$restore_ok = false;

			do_action( 'boldgrid_backup_notice',
				'The selected archive file was not found.',
				'notice notice-error is-dismissible'
			);
		}

		// Get the file path to delete.
		$filepath = ( false === empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null );

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the archive file size.
		if ( false === empty( $filepath ) && true === $wp_filesystem->exists( $filepath ) ) {
			$filesize = $wp_filesystem->size( $filepath );
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

			// Unzip the backup archive file to ABSPATH.
			// @todo Finish restoration code below.
			//$result = unzip_file( $filepath, ABSPATH );
			$result = true;

			// Check for error.
			if ( true !== $result ) {
				error_log( __METHOD__ . ': Could not extract "' . $filepath . '" into "' . ABSPATH . '".' );

				$restore_ok = false;
			}

			// Restore database.
			if ( true === $restore_ok ) {
				// Restore the database.
			}
		}

		// Display notice of deletion status.
		if ( false === $restore_ok ) {
			do_action( 'boldgrid_backup_notice',
				'Error restoring the selected archive file.',
				'notice notice-error is-dismissible'
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

			if ( true === $restore_ok ) {
				$body .= __( 'A backup archive has been restored' );
			} else {
				$body .= __( 'An error occurred when attempting to restore a backup archive' );
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

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'downloadNonce' => $download_nonce,
			'accessType' => $access_type,
			'restoreConfirmText' => $restore_confirm_text,
			'deleteConfirmText' => $delete_confirm_text,
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

		// If a backup operation is requested, then make a backup now.
		if ( false === empty( $_GET['backup_now'] ) ) {
			$this->backup_now();
		} else {
			// If a restoration operation is requested, then restore from a backup archive now.
			if ( false === empty( $_GET['restore_now'] ) ) {
				$this->restore_archive_file();
			}
		}

		return;
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
		if ( true !== $this->test->run_functionality_tests() ) {
			// Display an error notice.
			do_action( 'boldgrid_backup_notice', 'Functionality test has failed.',
				'notice notice-error is-dismissible'
			);
		}

		// Get the WordPress version.
		global $wp_version;

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Get disk space information array.
		$disk_space = $this->test->get_disk_space();

		// Get the database size.
		$db_size = $this->test->get_database_size();

		// Get the database character set.
		$db_charset = $wpdb->charset;

		// Get the database collation.
		$db_collate = $wpdb->collate;

		// Get archive info.
		$archive_info = $this->archive_files( false );

		// Get the backup directory path.
		$backup_directory = $this->config->get_backup_directory();

		// Load template view.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-test.php';

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
	 * Admin notice template.
	 *
	 * @since 1.0
	 *
	 * @param string $message A message to display in the admin notice.
	 * @return null
	 */
	public function notice_template( $message, $class = 'notice notice-error is-dismissible' ) {
		$message = __( $message, 'boldgrid-backup' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		return;
	}
}
