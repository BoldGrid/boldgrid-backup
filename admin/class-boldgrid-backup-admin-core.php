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
	 * Auto Rollback class.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Auto_Rollback
	 */
	public $auto_rollback;

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
	 * Plugin configs.
	 *
	 * @since  1.3.4
	 * @access plublic
	 * @var    array
	 */
	public $configs;

	/**
	 * Whether or not we're in ajax.
	 *
	 * When we're on the archives page and click "Backup Site Now", when
	 * this->archive_files runs, it reports doing_ajax as true.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    bool
	 */
	public $doing_ajax;

	/**
	 * Whether or not we're doing cron.
	 *
	 * When we're generating a backup, both via cron and wpcron, doing_cron is
	 * true.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    bool
	 */
	public $doing_cron;

	/**
	 * Email.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Email
	 */
	public $email;

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
	 * The admin cron class object.
	 *
	 * @since 1.0
	 * @access public
	 * @var Boldgrid_Backup_Admin_Cron
	 */
	public $cron;

	/**
	 * The admin xhprof class object.
	 *
	 * @since 1.2
	 * @access public
	 * @var Boldgrid_Backup_Admin_Xhprof
	 */
	public $xhprof;

	/**
	 * WP Cron class.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    Boldgrid_Backup_Admin_WP_Cron
	 */
	public $wp_cron;

	/**
	 * An instance of the filesystem.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    WP_Filesystem
	 */
	public $wp_filesystem;

	/**
	 * An instance of the Archive Log class.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Archive_Log
	 */
	public $archive_log;

	/**
	 * An instance of the Archive Details class.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Archive_Details
	 */
	public $archive_details;

	/**
	 * An instance of the Archive Fail class.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Archive_Fail
	 */
	public $archive_fail;

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
	 * @access public
	 * @var string
	 */
	public $db_dump_filepath = '';

	/**
	 * An instance of the Boldgrid Backup Admin Filelist Class.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Filelist object
	 */
	public $filelist;

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
		'.htaccess.bgb',
		'index.php',
		'license.txt',
		'readme.html',
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
	 * An instance of the Boldgrid Backup Admin Backup Dir class.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Backup_Dir object.
	 */
	public $backup_dir;

	/**
	 * A unique identifier for backups of this WordPress installation.
	 *
	 * @since 1.0.1
	 * @access private
	 * @var string
	 */
	private $backup_identifier = null;

	/**
	 * An instance of the Boldgrid Backup Admin Home Dir class.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Home_Dir object.
	 */
	public $home_dir;

	/**
	 * Local storage.
	 *
	 * @since  1.5.2
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Storage_Local
	 */
	public $local;

	/**
	 * The scheduler class object.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    Boldgrid_Backup_Admin_Scheduler
	 */
	public $scheduler;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @global $wp_filesystem.
	 */
	public function __construct() {
		global $wp_filesystem;

		$this->doing_cron = ( defined( 'DOING_CRON' ) && DOING_CRON );
		$this->doing_ajax = is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX;

		$this->wp_filesystem = $wp_filesystem;

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

		// Instantiate Boldgrid_Backup_Admin_Settings.
		$this->settings = new Boldgrid_Backup_Admin_Settings( $this );

		// Instantiate Boldgrid_Backup_Admin_Config.
		$this->config = new Boldgrid_Backup_Admin_Config( $this );

		// Instantiate Boldgrid_Backup_Admin_Test.
		$this->test = new Boldgrid_Backup_Admin_Test( $this );

		// Instantiate Boldgrid_Backup_Admin_Notice.
		$this->notice = new Boldgrid_Backup_Admin_Notice();

		// Instantiate Boldgrid_Backup_Admin_Cron.
		$this->cron = new Boldgrid_Backup_Admin_Cron( $this );

		// Instantiate Boldgrid_Backup_Admin_Upload.
		$this->upload = new Boldgrid_Backup_Admin_Upload( $this );

		// Instantiate Boldgrid_Backup_Admin_Xhprof.
		// Starts profiling and saves a report, if enabled in the "config.local.php" file.
		$this->xhprof = new Boldgrid_Backup_Admin_Xhprof();

		$this->restore_helper = new Boldgrid_Backup_Admin_Restore_Helper();

		$this->restore_git = new Boldgrid_Backup_Admin_Restore_Git();

		$this->filelist = new Boldgrid_Backup_Admin_Filelist();

		$this->backup_dir = new Boldgrid_Backup_Admin_Backup_Dir( $this );

		$this->home_dir = new Boldgrid_Backup_Admin_Home_Dir( $this );

		$this->compressors = new Boldgrid_Backup_Admin_Compressors( $this );

		$this->archive_log = new Boldgrid_Backup_Admin_Archive_Log( $this );

		$this->archive_details = new Boldgrid_Backup_Admin_Archive_Details( $this );

		$this->archive_fail = new Boldgrid_Backup_Admin_Archive_Fail( $this );

		$this->wp_cron = new Boldgrid_Backup_Admin_WP_Cron( $this );

		$this->scheduler = new Boldgrid_Backup_Admin_Scheduler( $this );

		$this->auto_rollback = new Boldgrid_Backup_Admin_Auto_Rollback( $this );

		$this->remote = new Boldgrid_Backup_Admin_Remote( $this );

		$this->jobs = new Boldgrid_Backup_Admin_Jobs( $this );

		$this->local = new Boldgrid_Backup_Admin_Storage_Local( $this );

		$this->email = new Boldgrid_Backup_Admin_Email( $this );

		// Ensure there is a backup identifier.
		$this->get_backup_identifier();

		$this->configs = Boldgrid_Backup_Admin::get_configs();
	}

	/**
	 * Get the class property $this->filelist_filter.
	 *
	 * @since 1.2.2
	 *
	 * @param  bool  $exclude_not_exists Remove files that don't exist.
	 * @return array
	 */
	public function get_filelist_filter( $exclude_not_exists = false ) {
		$filelist_filter = $this->filelist_filter;

		if( ! $exclude_not_exists ) {
			return $filelist_filter;
		}

		foreach( $filelist_filter as $key => $filename ) {
			if( ! $this->wp_filesystem->exists( ABSPATH . $filename ) ) {
				unset( $filelist_filter[$key] );
			}
		}

		return $filelist_filter;
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
		if ( ! empty( $this->backup_identifier ) ) {
			return $this->backup_identifier;
		}

		// Check wp_options for the id.
		$backup_identifier = get_site_option( 'boldgrid_backup_id' );

		// If the id was already stored in WP options, then save and return it.
		if ( ! empty( $backup_identifier ) ) {
			$this->backup_identifier = $backup_identifier;

			return $backup_identifier;
		}

		// Generate a new backup id.
		$admin_email = $this->config->get_admin_email();

		$unique_string = site_url() . ' <' . $admin_email . '>';

		$backup_identifier = hash( 'crc32', hash( 'sha512', $unique_string ) );

		// If something went wrong with hashing, then just use a random string to make the id.
		if ( empty( $backup_identifier ) ) {
			$random_string = '';

			for ( $i = 0; $i <= 32; $i ++ ) {
				$random_string .= chr( mt_rand( 40, 126 ) );
			}

			$backup_identifier = hash( 'crc32', $random_string );
		}

		// Save and return the id.
		$this->backup_identifier = $backup_identifier;

		update_site_option( 'boldgrid_backup_id', $backup_identifier );

		return $backup_identifier;
	}

	/**
	 * Initialize the premium version of the plugin.
	 *
	 * @since 1.5.2
	 */
	public function init_premium() {
		$premium_class = 'Boldgrid_Backup_Premium';

		if( ! class_exists( $premium_class) ) {
			return;
		}

		$this->premium = new $premium_class( $this );
		$this->premium->run();
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
		if ( $this->available_exec_functions ) {
			return $this->available_exec_functions;
		}

		// If PHP is in safe mode, then return an empty array.
		if ( $this->test->is_php_safemode() ) {
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
			if ( in_array( $exec_function, $disabled, true ) ) {
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
		if ( empty( $command ) ) {
			return false;
		}

		// If there are no supplied execution functions, then retrieve available ones.
		if ( empty( $available_exec_functions ) ) {
			$available_exec_functions = $this->get_execution_functions();
		}

		// Disable stderr.
		if ( ! $this->test->is_windows() && false === strpos( $command, '2>/dev/null' ) ) {
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

					if ( is_resource( $handle ) ) {
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
		if ( ! empty( $output ) ) {
			$output = trim( $output );
		}

		// If the command was not successful, then return FALSE.
		if ( ! $success ) {
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
		$lang = array(
			'backup_archive' =>  __( 'Backup Archive', 'boldgrid-backup' ),
			'boldgrid_backup' => __( 'BoldGrid Backup', 'boldgrid-backup' ),
			'preflight_check' => __( 'Preflight Check', 'boldgrid-backup' ),
			'settings' =>        __( 'Settings', 'boldgrid-backup' ),
		);

		// The main slug all sub menu items are children of.
		$main_slug = 'boldgrid-backup-settings';

		// The callable function for the settings page.
		$settings_page = array(
			$this->settings,
			'page_backup_settings',
		);

		// The capability required for these menu items to be displayed to the user.
		$capability = 'administrator';

		add_menu_page(
			$lang['boldgrid_backup'],
			$lang['boldgrid_backup'],
			$capability,
			$main_slug,
			$settings_page,
			'none'
		);

		/*
		 * Add "Settings", formally known as "Backup Settings".
		 *
		 * @link http://wordpress.stackexchange.com/questions/66498/add-menu-page-with-different-name-for-first-submenu-item
		 */
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['settings'],
			$lang['settings'],
			$capability,
			$main_slug,
			$settings_page
		);

		// Add "Backup Archive", formally known as "BoldGrid Backup".
		add_submenu_page(
			$main_slug,
			'BoldGrid ' . $lang['backup_archive'],
			$lang['backup_archive'],
			$capability,
			'boldgrid-backup',
			array(
				$this,
				'page_archives',
			)
		);

		// Add "Preflight Check" page, formally know as "Functionality Test".
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['preflight_check'],
			$lang['preflight_check'],
			$capability,
			'boldgrid-backup-test',
			array(
				$this,
				'page_backup_test',
			)
		);

		add_submenu_page(
			null,
			'BoldGrid ' . $lang['backup_archive'],
			$lang['backup_archive'],
			$capability,
			'boldgrid-backup-archive-details',
			array(
				$this->archive_details,
				'render_archive',
			)
		);

		return;
	}

	/**
	 * Register / enqueue scripts.
	 *
	 * This method is being called during the "admin_enqueue_scripts" hook.
	 *
	 * @since 1.5.2
	 */
	public function admin_enqueue_scripts() {
		wp_register_style(
			'boldgrid-backup-admin-new-thickbox-style',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-new-thickbox-style.css',
			array(),
			BOLDGRID_BACKUP_VERSION
		);

		wp_register_style(
			'boldgrid-backup-admin-hide-all',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-hide-all.css',
			array(),
			BOLDGRID_BACKUP_VERSION
		);
	}

	/**
	 * Backup the WordPress database.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return bool Status of the operation.
	 */
	private function backup_database() {
		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice.
			$this->notice->functionality_fail_notice();
			return array( 'error' => __( 'Unable to create backup, functionality test failed.', 'boldgrid_backup' ) );
		}

		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
			return array( 'error' => sprintf( __( 'The backup directory is not writable: %1$s.', 'boldgrid-backup' ), $backup_directory ) );
		}

		// Create a file path for the dump file.
		$db_dump_filepath = $backup_directory . DIRECTORY_SEPARATOR . DB_NAME . '.' . date( 'Ymd-His' ) . '.sql';

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		$this->set_time_limit();

		// Create a dump of our database.
		$dumper = new Boldgrid_Backup_Admin_Db_Dump();
		$status = $dumper->dump( $db_dump_filepath );
		if( ! empty( $status['error'] ) ) {
			do_action( 'boldgrid_backup_notice', $status['error'], 'notice notice-error is-dismissible' );
			return false;
		}

		// Ensure file is written and is over 100 bytes.
		$exists = $this->test->exists( $db_dump_filepath );
		if( ! $exists ) {
			return array( 'error' => sprintf( __( 'mysqldump file does not exist: %1$s', 'boldgrid-backup' ), $db_dump_filepath ) );
		}
		$dump_file_size = $this->wp_filesystem->size( $db_dump_filepath );
		if( 100 > $dump_file_size ) {
			return array( 'error' => sprintf( __( 'mysqldump file was not written to: %1$s', 'boldgrid-backup' ), $db_dump_filepath ) );
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
	 * @see Boldgrid_Backup_Admin_Test::run_functionality_tests()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 * @see Boldgrid_Backup_Admin_Core::execute_command()
	 * @see Boldgrid_Backup_Admin_Utility::update_siteurl()
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @param string $db_dump_filepath File path to the mysql dump file.
	 * @param string $db_prefix The database prefix to use, if restoring and it changed.
	 * @return bool Status of the operation.
	 */
	private function restore_database( $db_dump_filepath, $db_prefix = null ) {
		// Check input.
		if ( empty( $db_dump_filepath ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The database dump file was not found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice.
			$this->notice->functionality_fail_notice();

			return false;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		// Get the WP Options for "siteurl" and "home", to restore later.
		$wp_siteurl = get_option( 'siteurl' );
		$wp_home = get_option( 'home' );


		$this->set_time_limit();

		$importer = new Boldgrid_Backup_Admin_Db_Import();
		$status = $importer->import( $db_dump_filepath );

		if( ! empty( $status['error'] ) ) {
			do_action( 'boldgrid_backup_notice', $status['error'], 'notice notice-error is-dismissible' );
			return false;
		}

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $db_prefix ) ) {
			// Connect to the WordPress database via $wpdb.
			global $wpdb;

			// Set the database table prefix.
			$wpdb->set_prefix( $db_prefix );
		}

		// Clear the WordPress cache.
		wp_cache_flush();

		// Get the restored "siteurl" and "home".
		$restored_wp_siteurl = get_option( 'siteurl' );
		$restored_wp_home = get_option( 'home' );

		// If changed, then update the siteurl in the database.
		if ( $restored_wp_siteurl !== $wp_siteurl ) {
			$update_siteurl_success =
				Boldgrid_Backup_Admin_Utility::update_siteurl( $restored_wp_siteurl, $wp_siteurl );

			if ( ! $update_siteurl_success ) {
				// Display an error notice.
				do_action(
					'boldgrid_backup_notice',
					esc_html__(
						'The WordPress siteurl has changed.  There was an issue changing it back.  You will have to fix the siteurl manually in the database, or use an override in your wp-config.php file.',
						'boldgrid-backup'
					),
					'notice notice-error is-dismissible'
				);
			}
		}

		// If changed, then restore the WP Option for "home".
		if ( $restored_wp_home !== $wp_home ) {
			// Ensure there are no trailing slashes in siteurl.
			$wp_home = untrailingslashit( $wp_home );

			update_option( 'home', $wp_home );
		}

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

		// If this is a node_modules folder, do not iterate through it.
		if( false !== strpos( $dirpath, '/node_modules' ) ) {
			return array();
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Validate input.
		if ( empty( $dirpath ) || ! $wp_filesystem->is_readable( $dirpath ) ) {
			return array();
		}

		// Remove any training slash in dirpath.
		$dirpath = untrailingslashit( $dirpath );

		// Mark the base directory, if not set (the first run).
		if ( empty( $this->filelist_basedir ) ) {
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
		if ( empty( $dirpath ) || ! $wp_filesystem->is_readable( $dirpath ) ) {
			return array();
		}

		// Get the recursive directory listing for the specified path.
		$filelist = $this->get_filelist( $dirpath );

		// If no files were found, then return an empty array.
		if ( empty( $filelist ) ) {
			return array();
		}

		// Initialize $new_filelist.
		$new_filelist = array();

		// Filter the filelist array.
		foreach ( $filelist as $fileinfo ) {

			// @todo The user needs a way to specifiy what to skip in the backups.
			$is_node_modules = false !== strpos( $fileinfo[1], '/node_modules/' );
			$is_backup_directory = $this->backup_dir->file_in_dir( $fileinfo[1] );

			if( $is_node_modules || $is_backup_directory ) {
				continue;
			}

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
	public function generate_archive_path( $extension = null ) {
		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		$filename = sprintf( 'boldgrid-backup-%1$s-%2$s-%3$s',
			$site_id,
			$backup_identifier,
			date( 'Ymd-His' )
		);
		$filename = sanitize_file_name( $filename );

		// Create a file path with no extension (added later).
		$filepath = $backup_directory . DIRECTORY_SEPARATOR . $filename;

		// If specified, add an extension.
		if ( ! empty( $extension ) ) {
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

		/**
		 * Actions to take before any archiving begins.
		 *
		 * @since 1.5.2
		 */
		do_action( 'boldgrid_backup_archive_files_init' );

		$email_templates = include BOLDGRID_BACKUP_PATH . '/includes/config/email-templates.php';

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice, if not already on the test page.
			if ( ! isset( $_GET['page'] ) || 'boldgrid-backup-test' !== $_GET['page'] ) {
				// Display an error notice.
				$this->notice->functionality_fail_notice();
			}

			return array(
				'error' => 'Functionality tests fail.',
			);
		}

		/*
		 * Check if disk and db are within limits of backup.
		 *
		 * For example, are they trying to backup 20G of disk when the plugin only supports 10? If
		 * check fails, abort with appropriate message.
		 */
		$size_data = $this->test->get_size_data();
		if( ! empty( $size_data['messages']['notSupported'] ) ) {
			// If we're backing up via cron, send email.
			if( DOING_CRON ) {
				$body = sprintf( $email_templates['fail_size']['body'], $size_data['messages']['notSupported'] );
				$subject = sprintf( $email_templates['fail_size']['subject'], get_site_url() );
				$this->email->send( $subject, $body );
			}

			return array(
				'error' => $size_data['messages']['notSupported'],
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
			'total_size' => 0,
		);

		// Determine how this backup was triggered.
		$sapi_type = php_sapi_name();
		if( $this->doing_ajax ) {
			$current_user = wp_get_current_user();
			$info['trigger'] = $current_user->user_login . ' (' . $current_user->user_email . ')';
		} elseif( $this->doing_cron && substr( $sapi_type, 0, 3 ) === 'cli' ) {
			$info['trigger'] = 'Cron';
		} elseif( $this->doing_cron ) {
			$info['trigger'] = 'WP cron';
		} else {
			$info['trigger'] = __( 'Unknown', 'boldgrid-backup' );
		}

		$info['compressor'] = $this->compressors->get();

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
		if ( $save && ! $dryrun ) {
			$status = $this->backup_database();

			if ( ! empty( $status['error'] ) ) {
				return array(
					'error' => $status['error'],
				);
			}
		}

		// Keep track of how long the site was paused for / the time to backup the database.
		$db_time_stop = microtime( true );

		// Get the file list.
		$filelist = $this->get_filtered_filelist( ABSPATH );

		// Initialize total_size.
		$info['total_size'] = 0;

		// If not saving, then just return info.
		if ( ! $save ) {
			foreach ( $filelist as $fileinfo ) {
				// Add the file size to the total.
				$info['total_size'] += $fileinfo[2];
			}

			return $info;
		}

		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
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

		$this->set_time_limit();

		/**
		 * Allow the filtering of our $info before generating a backup.
		 *
		 * @since 1.5.1
		 *
		 * @param array $info See Boldgrid_Backup_Admin_Compressor_Php_Zip::archive_files.
		 */
		$info = apply_filters( 'boldgrid_backup_pre_archive_info', $info );

		/*
		 * Use the chosen compressor to build an archive.
		 * If the is no available compressor, then return an error.
		 */
		switch ( $info['compressor'] ) {
			case 'php_zip' :
				$compressor = new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this );
				$status = $compressor->archive_files( $filelist, $info );
				break;
			case 'pcl_zip' :
				$compressor = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this );
				$status = $compressor->archive_files( $filelist, $info );
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
				$status = array(
					'error' => 'No available compressor',
				);
				break;
		}

		$info['total_size'] += $this->filelist->get_total_size( $filelist );

		if ( true === $status && ! $wp_filesystem->exists( $info['filepath'] ) ) {
			$status = array(
				'error' => 'The archive file "' . $info['filepath'] . '" was not written.',
			);
		}

		if( ! empty( $status['error'] ) ) {
			return $status;
		}

		$info['lastmodunix'] = $wp_filesystem->mtime( $info['filepath'] );

		if ( $save && ! $dryrun ) {
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
		$info['db_duration'] = number_format( ( $db_time_stop - $time_start ), 2, '.', '' );

		/**
		 * Actions to take after a backup has been created.
		 *
		 * At priority 100, we delete the local backup file if the user does
		 * not want to keep it.
		 *
		 * At priority 200, we send an email to the user with a summary of the
		 * backup and the jobs.
		 *
		 * @since 1.5.2
		 *
		 * @param array $info{
		 *     An array of info about the backup just created.
		 *
		 *     @type string $mode         backup
		 *     @type bool   $dryrun
		 *     @type string $compressor   pcl_zip
		 *     @type int    $filesize     30992482
		 *     @type bool   $save
		 *     @type int    $total_size
		 *     @type string $filepath     C:\file.zip
		 *     @type int    $lastmodunix  1506602959
		 *     @type int    $duration     57.08
		 *     @type int    $db_duration  0.35
		 *     @type bool   $mail_success
		 * }
		 */
		do_action( 'boldgrid_backup_post_archive_files', $info );

		// Send an email.
		if( $this->email->user_wants_notification( 'backup' ) && $this->doing_ajax ) {
			$email_parts = $this->email->post_archive_parts( $info );
			$email_body = $email_parts['body']['main'] . $email_parts['body']['signature'];
			$info['mail_success'] = $this->email->send( $email_parts['subject'], $email_body );
		}

		// If not a dry-run test, update the last backup option and enforce retention.
		if ( ! $dryrun ) {
			// Update WP option for "boldgrid_backup_last_backup".
			update_site_option( 'boldgrid_backup_last_backup', time() );

			$this->archive_log->write( $info );

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
	 * @param string $backup_directory  Specify a directory to look within for backups.
	 * @return array {
	 * 	A numbered array of arrays containing the following indexes.
	 * 	@type string $filepath Archive file path.
	 * 	@type string $filename Archive filename.
	 * 	@type string $filedate Localized file modification date.
	 * 	@type int $filesize The archive file size in bytes.
	 * 	@type int $lastmodunix The archive file modification time in unix seconds.
	 * }
	 */
	public function get_archive_list( $download_filename = null, $backup_directory = null ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $archive_files array.
		$archive_files = array();

		// Get the backup directory.
		if( is_null( $backup_directory ) ) {
			$backup_directory = $this->backup_dir->get();
		}

		// If the backup directory is not configured, then return an empty array.
		if ( ! $backup_directory ) {
			return array();
		}

		// Find all backups.
		$dirlist = $wp_filesystem->dirlist( $backup_directory, false, false );

		// If no files were found, then return an empty array.
		if ( empty( $dirlist ) ) {
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
					'/^boldgrid-backup-(' . $site_id . '|.*?-?' . $backup_identifier .
					')-.*\.(zip|tar\.gz|b2z|zlib|lzf)$/',
					$fileinfo['name']
				)
			) {
				// Increment the index.
				$index++;

				// If looking for one match, skip an iteration if not the matching filename.
				if ( ! empty( $download_filename ) && $download_filename !== $fileinfo['name'] ) {
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
				if ( ! empty( $download_filename ) ) {
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
		if ( empty( $_POST['delete_now'] ) ) {
			return false;
		}

		// Initialize $delete_ok.
		$delete_ok = true;

		// Verify nonce, or die.
		check_admin_referer( 'archive_auth', 'archive_auth' );

		// Validate archive_key.
		if ( isset( $_POST['archive_key'] ) && is_numeric( $_POST['archive_key'] ) ) {
			$archive_key = sanitize_text_field( $_POST['archive_key'] );
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
		if ( ! empty( $_POST['archive_filename'] ) ) {
			$archive_filename = $_POST['archive_filename'];
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
		if ( ! $delete_ok ) {
			return false;
		}

		// Get archive list.
		$archives = $this->get_archive_list( $archive_filename );

		// If no files were found, then show a notice.
		if ( empty( $archives ) ) {
			// Fail with a notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'No archive files were found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Locate the filename by key number.
		$filename = (
			! empty( $archives[ $archive_key ]['filename'] ) ?
			$archives[ $archive_key ]['filename'] : null
		);

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
		$filepath = (
			! empty( $archives[ $archive_key ]['filepath'] ) ?
			$archives[ $archive_key ]['filepath'] : null
		);

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Delete the specified archive file.
		if ( ! $wp_filesystem->delete( $filepath, false, 'f' ) ) {
			$delete_ok = false;
		}

		// Display notice of deletion status.
		if ( ! $delete_ok ) {
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
	 * @param  string $filepath The full filepath to the file .zip archive.
	 * @return string File path to the database dump file.
	 */
	private function get_dump_file( $filepath ) {

		if( empty( $filepath ) || ! $this->wp_filesystem->exists( $filepath ) ) {
			return '';
		}

		/*
		 * Get the sql file to restore.
		 *
		 * These few lines below are new to the get_dump_file method.
		 * Historically we searched the ABSPATH for a filename matching
		 * ########-######.sql and returned the first one we found. Well, what
		 * if there were more than one matching file, which .sql file do we
		 * restore?
		 *
		 * $pcl_zip->get_sqls Searches the zip file and finds a list of all
		 * .sql files that match the pattern we're looking for and returns them.
		 * We should only get 1 back, and that is the appropriate dump file.
		 *
		 * These few lines should do the trick. If not, the original code is
		 * still in place to take the initial approach and find a dump file to
		 * restore (the first applicable one it can find).
		 */
		$pcl_zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this );
		$sqls = $pcl_zip->get_sqls( $filepath );
		if( 1 === count( $sqls ) ) {
			return ABSPATH . $sqls[0];
		}

		// Initialize $db_dump_filepath.
		$db_dump_filepath = '';

		// Find all backups.
		$dirlist = $this->core->wp_filesystem->dirlist( ABSPATH, false, false );

		// If no files were found, then return an empty array.
		if ( empty( $dirlist ) ) {
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

		// Find the first occurrence of a MySQL dump file.
		// Format: *.########-######.sql
		// An example filename: joec_wrdp2.20160919-162431.sql
		foreach ( $dirlist as $fileinfo ) {
			if ( 1 === preg_match( '/\.[\d]+-[\d]+\.sql$/',$fileinfo['name'] ) ) {
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
	 * @see https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	public function restore_archive_file( $dryrun = false ) {
		// Initialize $error.
		$error = array();

		// Check if DOING_CRON.
		$doing_cron = ( defined( 'DOING_CRON' ) && DOING_CRON );

		// If a restoration was not requested, then abort.
		if ( empty( $_POST['restore_now'] ) ) {
			return false;
		}

		// If not DOING_CRON, then verify nonce, or die.
		if ( ! $doing_cron ) {
			check_admin_referer( 'archive_auth', 'archive_auth' );
		}

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				$this->notice->functionality_fail_notice();
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
		if ( isset( $_POST['archive_key'] ) && is_numeric( $_POST['archive_key'] ) ) {
			$archive_key = sanitize_text_field( wp_unslash( $_POST['archive_key'] ) );
		} else {
			$restore_ok = false;

			$message = esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' );

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				$error[] = $message;
			} else {
				return array(
					'error' => $message,
				);
			}
		}

		// Validate archive_filename.
		if ( ! empty( $_POST['archive_filename'] ) ) {
			$archive_filename = sanitize_text_field( wp_unslash( $_POST['archive_filename'] ) );
		} else {
			$restore_ok = false;

			$message = esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' );

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				$error[] = $message;
			} else {
				return array(
					'error' => $message,
				);
			}
		}

		// Close any PHP session, so that another session can open during this restore operation.
		session_write_close();

		// Get archive list.
		if ( $restore_ok ) {
			$archives = $this->get_archive_list( $archive_filename );
		}

		// If no files were found, then show a notice.
		if ( $restore_ok && empty( $archives ) ) {
			$restore_ok = false;

			$message = esc_html__( 'No archive files were found.', 'boldgrid-backup' );

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				$error[] = $message;
			} else {
				return array(
					'error' => $message,
				);
			}
		}

		// Locate the filename by key number.
		if ( $restore_ok ) {
			$filename = (
				! empty( $archives[ $archive_key ]['filename'] ) ?
				$archives[ $archive_key ]['filename'] : null
			);
		}

		// Verify specified filename.
		if ( $restore_ok && $archive_filename !== $filename ) {
			$restore_ok = false;

			$message = esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' );

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				$error[] = $message;
			} else {
				return array(
					'error' => $message,
				);
			}
		}

		// If there are errors, then create a message from the combined errors found.
		if ( ! $restore_ok || count( $error ) > 0 ) {
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
		$filepath = (
			! empty( $archives[ $archive_key ]['filepath'] ) ?
			$archives[ $archive_key ]['filepath'] : null
		);

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the archive file size.
		if ( ! empty( $filepath ) && $wp_filesystem->exists( $filepath ) ) {
			$filesize = $wp_filesystem->size( $filepath );
		} else {
			$filesize = 0;

			$restore_ok = false;

			$message = esc_html__( 'The selected archive file is empty.', 'boldgrid-backup' );

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				do_action(
					'boldgrid_backup_notice',
					$message,
					'notice notice-error is-dismissible'
				);
			}

			// Abort.
			return array(
				'error' => $message,
			);
		}

		// Check ZIP file for required file patterns.
		$zip_patterns_exist = Boldgrid_Backup_Admin_Utility::zip_patterns_exist(
			$filepath,
			$this->filelist_filter
		);

		// If the ZIP file does not have the required patterns, then fail.
		if ( ! $zip_patterns_exist ) {
			$restore_ok = false;

			$message = esc_html__(
				'The required directories and files were not found in the ZIP archive file.',
				'boldgrid-backup'
			);

			// Display an error notice, if not DOING_CRON.
			if ( ! $doing_cron ) {
				do_action(
					'boldgrid_backup_notice',
					$message,
					'notice notice-error is-dismissible'
				);
			}

			// Abort.
			return array(
				'error' => $message,
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
		if ( $restore_ok ) {
			// Prevent this script from dying.
			ignore_user_abort( true );

			$this->set_time_limit();

			/**
			 * Action to take before restoring an archive.
			 *
			 * @since 1.5.1
			 *
			 * @param array $info
			 */
			do_action( 'boldgrid_backup_pre_restore', $info );

			$unzip_status = ! $dryrun ? unzip_file( $info['filepath'], ABSPATH ) : null;

			if( is_wp_error( $unzip_status ) ) {
				$error = false;

				/**
				 * Take action when a restoration fails.
				 *
				 * Those actions may return a custom error message, such as:
				 * "Your restoration failed, but we did XYZ. Please try again".
				 *
				 * @param WP_Error $unzip_status
				 */
				$error = apply_filters( 'boldgrid_backup_restore_fail', $unzip_status );

				if( empty( $error ) ) {
					$message = $unzip_status->get_error_message();
					$data = $unzip_status->get_error_data();
					$error = sprintf( '%1$s%2$s', $message, is_string( $data ) && ! empty( $data ) ? ': ' . $data : '' );
				}

				if( $doing_cron ) {
					return array( 'error' => $error );
				} else {
					$restore_ok = false;
					$info['error'] = $error;
				}
			}

			/**
			 * Action to take after restoring an archive.
			 *
			 * @since 1.5.1
			 *
			 * @param array $info
			 */
			do_action( 'boldgrid_backup_post_restore', $info );

			// Restore database.
			if ( ! $dryrun && $restore_ok ) {
				// Get the database dump file path.
				$db_dump_filepath = $this->get_dump_file( $filepath );

				// Initialize database table prefix.
				$db_prefix = null;

				// Get the database table prefix from the new "wp-config.php" file, if exists.
				if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
					$wpcfg_contents = $wp_filesystem->get_contents( ABSPATH . 'wp-config.php' );
				}

				if ( ! empty( $wpcfg_contents ) ) {
					preg_match(
						'#\$table_prefix.*?=.*?' . "'" . '(.*?)' . "'" . ';#',
						$wpcfg_contents,
						$matches
					);

					// If a prefix was found, then use it.
					if ( ! empty( $matches[1] ) ) {
						$db_prefix = $matches[1];
					}
				}

				// Restore the database and then delete the dump.
				$restore_ok = $this->restore_database( $db_dump_filepath, $db_prefix );
				$this->wp_filesystem->delete( $db_dump_filepath, false, 'f' );

				// Display notice of deletion status.
				if ( ! $restore_ok ) {
					$info['error'] = esc_html__( 'Could not restore database.', 'boldgrid-backup' );

					if ( ! $doing_cron ) {
						do_action( 'boldgrid_backup_notice', $info['error'], 'notice notice-error is-dismissible' );
					} else {
						return array(
							'error' => $info['error'],
						);
					}
				}
			}
		}

		// Clear rollback information and restoration cron jobs that may be present.
		$this->cancel_rollback();

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for restoration completed.
		if ( ! empty( $settings['notifications']['restore'] ) ) {
			// Include the mail template.
			include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-mail-restore.php';

			// Send the notification.
			// Parameters come from the included mail template file.
			$info['mail_success'] = $this->email->send( $subject, $body );
		}

		// Update status.
		$info['restore_ok'] = $restore_ok;

		// Check backup directory.
		$info['backup_directory_set'] = $this->backup_dir->get();

		// Return info array.
		return $info;
	}

	/**
	 * Menu callback to display the Backup home page.
	 *
	 * @since 1.0
	 *
	 * @see    Boldgrid_Backup_Admin_Upload::upload_archive_file().
	 * @global string $pagenow
	 *
	 * @return null
	 */
	public function page_archives() {
		global $pagenow;

		add_thickbox();
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );

		// Run the functionality tests.
		$is_functional = $this->test->run_functionality_tests();

		// If tests fail, then show an admin notice and abort.
		if ( ! $is_functional ) {
			$this->notice->functionality_fail_notice();

			return;
		}

		// Register the JS for the home page.
		wp_register_script( 'boldgrid-backup-admin-home',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-home.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION
		);

		wp_enqueue_script( 'boldgrid-backup-now' );

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Create a nonce for archive operations.
		$archive_nonce = wp_create_nonce( 'archive_auth' );

		// Create text for the restoration confirmation.
		// This variable is passed to JavaScript that will use string replacement for "%s".
		$restore_confirm_text = esc_html__(
			'Please confirm the restoration of this WordPress installation from the archive file:' .
			PHP_EOL . '"%s"' . PHP_EOL . PHP_EOL .
			'Please be aware that you may get logged-out if your session token does not exist in the database restored.',
			'boldgrid-backup'
		);

		// Create text for the deletion confirmation.
		$delete_confirm_text = esc_html__(
			'Please confirm the deletion of the archive file:' . PHP_EOL,
			'boldgrid-backup'
		);

		// Create URL for backup now.
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup' );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'archiveNonce' => $archive_nonce,
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
		if ( ! empty( $_POST['delete_now'] ) ) {
			$this->delete_archive_file();
		}

		// If uploading an archive file.
		if ( ! empty( $_FILES['file'] ) ) {
			$this->upload->upload_archive_file();
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

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		$settings = $this->settings->get_settings();

		// If the directory path is not in the settings, then add it for the form.
		if ( empty( $settings['backup_directory'] ) ) {
			$settings['backup_directory'] = $this->backup_dir->get();
		}

		// Include the home page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-home.php';

		// Include our js templates.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-js-templates.php';

		// If a restoration operation is requested, then restore from a backup archive now.
		if ( ! empty( $_POST['restore_now'] ) ) {

			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				'notice notice-info restoration-in-progress',
				esc_html__( 'Restoration in progress', 'boldgrid-backup' )
			);

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
		if ( ! isset( $_POST['backup_auth'] ) ||
			1 !== check_ajax_referer( 'boldgrid_backup_now', 'backup_auth', false ) ) {
				wp_die(
					'<div class="error"><p>' .
					esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) .
					'</p></div>'
				);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die(
				'<div class="error"><p>' .
				esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ) .
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

		// Create a nonce for archive operations.
		$archive_nonce = wp_create_nonce( 'archive_auth' );

		// Generate markup, using the backup page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup.php';

		// If updating WordPress, then queue a restoration cron job via WP option.
		if ( isset( $_POST['is_updating'] ) && 'true' === $_POST['is_updating'] ) {
			update_site_option( 'boldgrid_backup_pending_rollback', $archive_info );
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
		check_ajax_referer( 'archive_auth', 'wpnonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			esc_html_e( 'Security violation (not authorized).', 'boldgrid-backup' );
			wp_die();
		}

		// Validate download_key.
		if ( is_numeric( $_POST['download_key'] ) ) {
			$download_key = sanitize_text_field( wp_unslash( $_POST['download_key'] ) );
		} else {
			esc_html_e( 'INVALID DOWNLOAD KEY', 'boldgrid-backup' );
			wp_die();
		}

		// Validate download_filename.
		if ( ! empty( $_POST['download_filename'] ) ) {
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
		if ( empty( $archives ) ) {
			esc_html_e( 'NO BACKUP ARCHIVES FOUND', 'boldgrid-backup' );
			wp_die();
		}

		// Locate the filename by key number.
		$filename = (
			! empty( $archives[ $download_key ]['filename'] ) ?
			$archives[ $download_key ]['filename'] : null
		);

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

		// Get the user home directory.
		$home_dir = $this->config->get_home_directory();

		// Get the mode of the directory.
		$home_dir_mode = $this->config->get_home_mode();

		// Check if home directory is writable.
		$home_dir_writable = $this->test->is_homedir_writable();

		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		$possible_backup_dirs = $this->backup_dir->get_possible_dirs();

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

		$disk_space = $this->test->get_disk_space();

		// Get our crons and ready them for display.
		$our_crons = $this->cron->get_our_crons();
		foreach( $our_crons as &$cron ) {
			$cron = esc_html( $cron );
		}
		$our_wp_crons = $this->wp_cron->get_our_crons();
		foreach( $our_wp_crons as &$cron ) {
			$cron = esc_html( $cron );
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
	 * Register scripts.
	 *
	 * @since 1.3.3.
	 */
	public function register_scripts() {
		wp_register_script( 'boldgrid-backup-now',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-now.js',
			array( 'jquery', 'wp-util' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
	}

	/**
	 * Set the PHP timeout limit to at least 15 minutes.
	 *
	 * Various places within this class use to set the timeout limit to 300 seconds. This timeout
	 * limit has been increased to 900 seconds and moved into its own method.
	 *
	 * @since 1.3.5
	 *
	 * @param int $time_limit Limit in seconds.
	 */
	public function set_time_limit( $time_limit = 900 ) {
		set_time_limit( ( ( $max_execution_time = ini_get( 'max_execution_time' ) ) > $time_limit ?
			$max_execution_time :
			$time_limit
		) );
	}

	/**
	 * Show an admin notice on the WordPress Updates page.
	 *
	 * @since 1.0
	 *
	 * @global string $pagenow
	 */
	public function backup_notice() {
		global $pagenow;

		// Get pending rollback information.
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// If there is a pending rollback, then abort.
		if ( ! empty( $pending_rollback['lastmodunix'] ) ) {
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
			data-filename='" . $archive['filename'] . "'>" .
			esc_html__( 'Download ', 'boldgrid-backup' ) . '</a>';
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

		// Create a nonce for file downloads.
		$archive_nonce = wp_create_nonce( 'archive_auth' );

		// Create URL for backup now.
		$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );

		// Create an array of data to pass to JS.
		$localize_script_data = array(
			'archiveNonce' => $archive_nonce,
			'accessType' => $access_type,
			'backupUrl' => $backup_url,
		);

		// Add localize script data to the JS script.
		wp_localize_script( 'boldgrid-backup-admin-home', 'localizeScriptData', $localize_script_data );

		// Enqueue JS.
		wp_enqueue_script( 'boldgrid-backup-admin-home' );
		wp_enqueue_script( 'boldgrid-backup-now' );

		// Create admin notice text.
		$notice_text = sprintf(
			__(	'BoldGrid Backup last created backup archive:<p>%1$s %2$s</p>
				<p>It is recommended to backup your site before performing updates.
				If you perform a backup here, before performing updates, then an automatic rollback is possible.
				More information is available in the <a href="%3$s">Backup Archive</a> page.</p>',
				'boldgrid-backup'
			),
			$listing,
			$download_button,
			admin_url( 'admin.php?page=boldgrid-backup' )
		) . PHP_EOL;

		$backup_button = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php';

		$size_data = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php';

		// Show admin notice.
		do_action( 'boldgrid_backup_notice', $notice_text . $size_data . $backup_button, 'notice notice-warning is-dismissible' );

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-js-templates.php';
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
		if ( empty( $deadline ) ) {
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

		// Create an array of arguments for the notice template.
		$args['restore_key'] = $key;

		$args['restore_filename'] = $archive['filename'];

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
	 * 		@type int $restore_key Key index used for restoration.
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
		" . wp_nonce_field( 'boldgrid_rollback_notice', 'cancel_rollback_auth', true, false ) . "
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
		<form action='" . get_admin_url( null, 'admin.php?page=boldgrid-backup' ) . "'
		class='restore-now-form' method='POST'>
			<input type='hidden' name='restore_now' value='1' />
			<input type='hidden' name='archive_key' value='" . $args['restore_key'] . "' />
			<input type='hidden' name='archive_filename' value='" . $args['restore_filename'] . "' />
			" . wp_nonce_field( 'archive_auth', 'archive_auth', true, false ) . "
			<input type='submit' class='button action-restore' data-key='" . $args['restore_key'] . "'
			data-filename='" . $args['restore_filename'] . "'
			value='" . esc_html__( 'Rollback Site Now', 'boldgrid-backup' ) . "' />
			<span class='spinner'></span>
		</form>
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
		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die(
				'<div class="error"><p>' .
				esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ) .
				'</p></div>'
			);
		}

		// Verify nonce, or die with an error message.
		if ( ! isset( $_POST['cancel_rollback_auth'] ) ||
		1 !== check_ajax_referer( 'boldgrid_rollback_notice', 'cancel_rollback_auth', false ) ) {
			wp_die(
				'<div class="error"><p>' .
				esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) .
				'</p></div>'
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
	 *
	 * @see Boldgrid_Backup_Admin_Cron::delete_cron_entries().
	 * @see Boldgrid_Backup_Admin_Settings::delete_rollback_option().
	 */
	public function cancel_rollback() {
		// Remove any cron jobs for restore actions.
		$this->cron->delete_cron_entries( 'restore' );

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
		if ( empty( $settings['auto_backup'] ) ) {
			return;
		}

		// Get the last backup time (unix seconds).
		$last_backup_time = get_option( 'boldgrid_backup_last_backup' );

		// If the last backup was done in the last hour, then abort.
		if ( $last_backup_time && ( time() - $last_backup_time ) <= HOUR_IN_SECONDS ) {
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
	 *
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 *
	 * @return null
	 */
	public function enforce_retention() {
		// Get backup settings.
		$settings = $this->settings->get_settings();

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archives file count.
		$archives_count = count( $archives );

		// If the archive count is not beyond the set retention count, then return.
		if ( empty( $settings['retention_count'] ) || $archives_count <= $settings['retention_count'] ) {
			return;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $counter.
		$counter = $archives_count - 1;

		// Delete old backups.
		while ( $archives_count > $settings['retention_count'] ) {
			// Get the file path to delete.
			$filepath = (
				! empty( $archives[ $counter ]['filepath'] ) ?
				$archives[ $counter ]['filepath'] : null
			);

			// Delete the specified archive file.
			if ( ! $filepath || ! $wp_filesystem->delete( $filepath, false, 'f' ) ) {
				// Something went wrong.
				break;
			}

			$this->archive_log->delete_by_zip( $filepath );

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
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// Return pending rollback deadline, or 0 if not present.
		if ( empty( $pending_rollback['deadline'] ) ) {
			return 0;
		} else {
			return $pending_rollback['deadline'];
		}
	}

	/**
	 * Callback for getting the rollback deadline.
	 *
	 * @since 1.2.1
	 *
	 * @see Boldgrid_Backup_Admin_Core::get_rollback_deadline().
	 */
	public function boldgrid_backup_deadline_callback() {
		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die();
		}

		// Get the rollback deadline.
		$deadline = $this->get_rollback_deadline();

		// If there is no deadline, then die.
		if ( empty( $deadline ) ) {
			wp_die();
		}

		// Convert the deadline to ISO time (in ISO 8601 format).
		$iso_time = date( 'c', $deadline );

		wp_die( $iso_time );
	}

	/**
	 * Callback for getting size data.
	 *
	 * @since 1.3.1
	 */
	public function boldgrid_backup_sizes_callback() {
		session_write_close();

		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'unauthorized' );
		}

		// Check nonce.
		if( ! check_ajax_referer( 'boldgrid_backup_sizes', 'sizes_auth', false ) ) {
			wp_die( 'unauthorized' );
		}

		$return = $this->test->get_size_data();

		echo json_encode( $return );

		wp_die();
	}

	/**
	 * Callback function for the hook "upgrader_process_complete".
	 *
	 * @since 1.2
	 *
	 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 * @see Boldgrid_Backup_Admin_Cron::add_restore_cron().
	 * @see Boldgrid_Backup_Admin_Core::get_rollback_deadline().
	 */
	public function upgrader_process_complete() {
		// Add/update restoration cron job.
		$this->auto_rollback->add_cron();

		// If not on an admin page, then abort.
		if ( ! is_admin() ) {
			return;
		}

		// Get pending rollback deadline.
		$deadline = $this->get_rollback_deadline();

		// If there is not a pending rollback, then abort.
		if ( empty( $deadline ) ) {
			return;
		}

		// Get the ISO time (in ISO 8601 format).
		$iso_time = date( 'c', $deadline );

		// Print a hidden div with the time, so that JavaScript can read it.
		printf( '<div class="hidden" id="rollback-deadline">%1$s</div>', $iso_time );
	}
}
