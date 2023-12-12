<?php
/**
 * File: class-boldgrid-backup-admin-test.php
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
 * Class: Boldgrid_Backup_Admin_Test
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Test {
	/**
	 * Base test filename.
	 *
	 * When we create test files, this is the prefix for all of them.
	 *
	 * @since  1.5.1
	 * @var    string
	 */
	public $test_prefix = 'boldgrid-backup-test-file-';

	/**
	 * The core class object.
	 *
	 * @since 1.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Is running Windows?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_windows = null;

	/**
	 * Is the home directory writable?
	 *
	 * @since 1.2
	 * @access private
	 * @var bool
	 */
	private $is_homedir_writable = null;

	/**
	 * Is the WordPress installation root directory (ABSPATH) writable?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_abspath_writable = null;

	/**
	 * Is crontab available?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_crontab_available = null;

	/**
	 * A cached value of whether or not posix_getpgid() is supported.
	 *
	 * @since 1.14.13
	 * @access private
	 * @var bool
	 */
	private static $is_getpgid_supported;

	/**
	 * Is WP-CRON enabled?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $wp_cron_enabled = null;

	/**
	 * Is PHP in safe mode?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_php_safemode = null;

	/**
	 * Is functional?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_functional = null;

	/**
	 * Transient time (in seconds) for disk / db size data.
	 *
	 * Default value is 300 seconds (5 minutes).
	 *
	 * @since  1.3.1
	 * @var    int
	 */
	public $transient_time = 300;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;
	}

	/**
	 * Find out if we can write a file with exec, and then modify it with wp_filesystem.
	 *
	 * @since 1.6.5
	 *
	 * @param  string $dir The directory to test.
	 * @return bool
	 */
	public function can_exec_write( $dir ) {
		if ( empty( $dir ) ) {
			return false;
		}

		$file = trailingslashit( $dir ) . 'safe-to-delete.txt';
		$txt  = 'This file is safe to delete.';

		// Write the file with an exec method.
		$command = sprintf( 'echo "%1$s" > %2$s', $txt, $file );
		$this->core->execute_command( $command, $success );
		if ( ! $success ) {
			return false;
		}

		// Read the file with wp_filesystem.
		if ( trim( $this->core->wp_filesystem->get_contents( $file ) ) !== $txt ) {
			return false;
		}

		// Delete the file with wp_filesystem.
		return $this->core->wp_filesystem->delete( $file );
	}

	/**
	 * Wrapper for wp_filesystem exists.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $path File or directory path.
	 * @return bool
	 */
	public function exists( $path ) {
		$exists = $this->core->wp_filesystem->exists( $path );

		/*
		 * Initial testing shows the wp_filesystem on Windows, at least when
		 * running in conjunction with IIS, does not always report accurately.
		 */
		if ( ! $exists && $this->is_windows() ) {
			$exists = file_exists( $path );
		}

		return $exists;
	}

	/**
	 * Extensive directory test.
	 *
	 * Pass in a directory, and we'll check to see if we can read / write / etc.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $dir Dircetory path.
	 * @return array
	 */
	public function extensive_dir_test( $dir ) {
		$dir             = Boldgrid_Backup_Admin_Utility::trailingslashit( $dir );
		$random_filename = $dir . $this->test_prefix . mt_rand();
		$txt_filename    = $random_filename . '.txt';
		$info_filename   = $random_filename . '.rtf';
		$str             = sprintf(
			// translators: 1: Plugin title.
			__( 'This is a test file from %1$s. You can delete this file.', 'boldgrid-backup' ),
			BOLDGRID_BACKUP_TITLE
		);

		$data['exists'] = $this->core->wp_filesystem->exists( $dir );
		$data['read']   = $this->core->wp_filesystem->is_readable( $dir );
		$data['write']  = $this->core->wp_filesystem->is_writable( $dir );

		// Can we get a directory listing?
		$dirlist         = $this->core->wp_filesystem->dirlist( $dir );
		$data['dirlist'] = is_array( $dirlist );

		// Determine if we have permission to rename a file.
		$touched = $this->core->wp_filesystem->touch( $txt_filename );
		$this->core->wp_filesystem->put_contents( $txt_filename, $str );
		$data['rename'] = $touched && $this->core->wp_filesystem->move( $txt_filename, $info_filename );

		// Delete the temp files.
		$this->core->wp_filesystem->delete( $txt_filename );
		$data['delete'] = $data['write'] && $this->core->wp_filesystem->delete( $info_filename );

		/*
		 * IIS Users, we've tried hard enough to delete old test files for you.
		 * If we can't get the dir listing below, you'll need to delete all the
		 * files in $dir starting that beging with'boldgrid-backup-test-file-'.
		 */
		$this->delete_test_files( $dir );

		return $data;
	}

	/**
	 * Recursively search for a folder.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $folder_name  Directory folder name.
	 * @param  string $starting_dir Start director path.
	 * @return bool True if folder name found.
	 */
	public function find_folder( $folder_name, $starting_dir = ABSPATH ) {
		$starting_dir = trailingslashit( $starting_dir );

		$files = $this->core->wp_filesystem->dirlist( $starting_dir );
		$files = is_array( $files ) ? $files : array();

		foreach ( $files as $file ) {

			if ( 'd' !== $file['type'] ) {
				continue;
			}

			$full_path = $starting_dir . $file['name'];

			if ( $file['name'] === $folder_name ) {
				return $full_path;
			}

			$folder_found = $this->find_folder( $folder_name, $full_path );
			if ( false !== $folder_found ) {
				return $folder_found;
			}
		}

		return false;
	}

	/**
	 * Delete test files.
	 *
	 * When given a $dir, we'll scan and delete all files that begin with
	 * $this->test_prefix.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $dir Directory path.
	 * @return bool
	 */
	public function delete_test_files( $dir ) {
		$dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $dir );

		$files = $this->core->wp_filesystem->dirlist( $dir );

		if ( ! is_array( $files ) ) {
			return false;
		}

		foreach ( $files as $file ) {
			$filename = $file['name'];

			if ( 0 === strpos( $filename, $this->test_prefix ) ) {
				$this->core->wp_filesystem->delete( $dir . $filename, true );
			}
		}

		return true;
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

		// Check if using Windows or Linux, and set as a class property.
		$this->is_windows = ( 'win' === strtolower( substr( PHP_OS, 0, 3 ) ) );

		// Return result.
		return $this->is_windows;
	}

	/**
	 * Determine if a dir is writable.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $dir Directory path.
	 * @return bool
	 */
	public function is_writable( $dir ) {
		if ( true === $this->core->wp_filesystem->is_writable( $dir ) ) {
			return true;
		}

		/*
		 * Test if a dir is writable by attempting to write a tmp file.
		 *
		 * On plesk, wp_filesystem->is_writable was returning false in a Windows
		 * environment. When attempting to actually write to the $dir though, it
		 * was successful.
		 */
		$random_filename = trailingslashit( $dir ) . mt_rand() . '.txt';
		$this->core->wp_filesystem->touch( $random_filename );
		$exists = $this->core->wp_filesystem->exists( $random_filename );

		if ( ! $exists ) {
			return false;
		}

		$this->core->wp_filesystem->delete( $random_filename );
		return true;
	}

	/**
	 * Display a warning if the user's account has a node_modules folder.
	 *
	 * This can cause a timeout when checking the size of the WordPress directory.
	 * # It is not possible to catch a "max execution time" fatal error.
	 * # It is not possible to skip certain folders when using recurse_dirsize
	 *   to calculate a directory size.
	 *
	 * @since 1.5.2
	 *
	 * @return bool True when a node_modules folder is found.
	 */
	public function node_modules_warning() {
		/*
		 * Initial test of find_folder call shows it took 0.02 seconds to run in
		 * a setup with ~15,000 files.
		 */
		$node_modules_folder = $this->find_folder( 'node_modules' );

		if ( false === $node_modules_folder ) {
			return false;
		}

		if ( $this->core->doing_ajax || $this->core->doing_cron ) {
			return true;
		}

		$folders_found   = __( 'The following node_modules folder was found in your account:', 'boldgrid-backup' );
		$possible_issues = __( 'Due to possible issues node_modules folders can cause when calculating disk space, your WordPress directory size was not calculated.', 'boldgrid-backup' );

		// translators: 1: Link.
		$ignore_warning = __(
			'To ignore this warning and try again, please <a href="%1$s">click here</a>',
			'boldgrid-backup'
		);

		$warning = sprintf(
			'<strong>%1$s</strong><br />
			<em>%2$s</em><br />
			%3$s %4$s',
			$folders_found,
			$node_modules_folder,
			$possible_issues,
			sprintf( $ignore_warning, '?page=boldgrid-backup-test&skip_node_modules=1' )
		);

		do_action( 'boldgrid_backup_notice', $warning );

		return true;
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

		if ( $this->is_windows() ) {
			$this->is_crontab_available = false;
			return $this->is_crontab_available;
		}

		$test_entry = '# ' . BOLDGRID_BACKUP_TITLE . ' Test Entry ' . time() . ' (You can delete this line).';

		/*
		 * To determine if crontab is available, we will BOTH write and remove
		 * a test entry from the crontab.
		 */
		$entry_added                = $this->core->cron->update_cron( $test_entry );
		$entry_deleted              = $this->core->cron->entry_delete( $test_entry );
		$this->is_crontab_available = $entry_added && $entry_deleted;

		return $this->is_crontab_available;
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

		if ( function_exists( '_get_cron_array' ) ) {
			$wp_cron_array = _get_cron_array();
		}

		// Check for the DISABLE_WP_CRON constant and value.
		$disable_wp_cron = false;

		if ( defined( 'DISABLE_WP_CRON' ) ) {
			$disable_wp_cron = DISABLE_WP_CRON;
		}

		$this->wp_cron_enabled = ( ! empty( $wp_cron_array ) && ! $disable_wp_cron );

		return $this->wp_cron_enabled;
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
	 * Determine whether or not php_zip is suppored.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function is_php_zip_supported() {
		$php_zip = new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this->core );

		return $php_zip->test( false );
	}

	/**
	 * Determine whether or not we can get our group process id.
	 *
	 * This is often used to determine if a backup process is still running.
	 *
	 * @since 1.14.13
	 *
	 * @link https://www.win.tue.nl/~aeb/linux/lk/lk-10.html
	 *
	 * @return bool
	 */
	public static function is_getpgid_supported() {
		if ( ! is_null( self::$is_getpgid_supported ) ) {
			return self::$is_getpgid_supported;
		}

		// Ensure we can get our process id.
		$pid = getmypid();
		if ( false === $pid ) {
			self::$is_getpgid_supported = false;
			return false;
		}

		// posix_getpgid() may not be available in all environments. Win 10 user running xampp for example.
		if ( ! function_exists( 'posix_getpgid' ) ) {
			self::$is_getpgid_supported = false;
			return false;
		}

		self::$is_getpgid_supported = false !== posix_getpgid( $pid );

		return self::$is_getpgid_supported;
	}

	/**
	 * Determine if this is a plesk environment.
	 *
	 * @since 1.5.1
	 *
	 * @return bool
	 */
	public function is_plesk() {
		foreach ( $_SERVER as $k => $v ) {
			if ( 'plesk_' === substr( $k, 0, strlen( 'plesk_' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether or not system_zip is suppored.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function is_system_zip_supported() {
		$system_zip_test = new Boldgrid_Backup_Admin_Compressor_System_Zip_Test( $this->core );

		return $system_zip_test->run();
	}

	/**
	 * Perform functionality tests.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function run_functionality_tests() {
		// If we've already run the logic in this method, we can return right now.
		if ( null !== $this->is_functional ) {
			return $this->is_functional;
		}

		$transient = 'boldgrid_backup_is_functional';

		/*
		 * If we've already run these tests:
		 * # If we are functional and our environment has not changed, assume everything is still functional.
		 * # If we are not functional, or the transient is false, we'll run the tests again. We will
		 *   assume that if false, the user is doing everything they can to get the environment functional,
		 *   and we want to be able to return true as soon as that's done. Basically, benefits of using
		 *   the transient will only come when the environment is functional, which is most cases.
		 */
		$environment = new Boldgrid_Backup_Admin_Environment();
		if ( get_transient( $transient ) && ! $environment->has_changed() ) {
			return true;
		}

		$available_compressors = $this->core->config->get_available_compressors();
		$compressor            = $this->core->compressors->get();
		$execution_functions   = Boldgrid_Backup_Admin_Cli::get_execution_functions();

		if ( empty( $execution_functions ) ) {
			/*
			 * The first test is to determine if we have any execution functions available. Some of
			 * the other tests may require them. Before this test was added, a variety of warnings would
			 * appear due to trying to run commands such as the following:
			 *
			 * # echo "This file is safe to delete." > /home/user/boldgrid_backup/safe-to-delete.txt 2>/dev/null
			 * # crontab -l 2>/dev/null
			 * # crontab /home/user/boldgrid_backup/crontab.1607956270.549.tmp 2>/dev/null
			 *
			 * Technically, we may be able to be fully functional without being able to execute commands,
			 * but for the moment, let's say we're not funtional. Only two reports of this ever.
			 */
			$this->is_functional = false;
		} elseif ( ! self::is_filesystem_supported() ) {
			$this->is_functional = false;
		} elseif ( ! $this->get_is_abspath_writable() ) {
			$this->is_functional = false;
		} elseif ( ! $this->core->backup_dir->get() ) {
			$this->is_functional = false;
		} elseif ( empty( $available_compressors ) ) {
			$this->is_functional = false;
		} elseif ( 'php_zip' === $compressor && ! $this->is_php_zip_supported() ) {
			$this->is_functional = false;
		} elseif ( 'pcl_zip' === $compressor && ! $this->is_pcl_zip_supported() ) {
			$this->is_functional = false;
		} elseif ( 'system_zip' === $compressor && ! $this->is_system_zip_supported() ) {
			$this->is_functional = false;
		} elseif ( $this->is_php_safemode() ) {
			$this->is_functional = false;
		} else {
			$this->is_functional = true;
		}

		// Transient expiration is up for debate. This is better than every admin page load.
		set_transient( $transient, $this->is_functional, DAY_IN_SECONDS );

		return $this->is_functional;
	}

	/**
	 * Disk space report.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param bool $get_wp_size Whether of not to include the size of the WordPress directory.
	 * @return array An array containing disk space (total, used, available, WordPress directory).
	 */
	public function get_disk_space( $get_wp_size = true ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the home directory.
		$home_dir = $this->core->home_dir->get_for_disk();

		// If the home directory is not defined, not a directory or not readable, then return 0.00.
		if ( empty( $home_dir ) || ! $wp_filesystem->is_dir( $home_dir ) ||
			! $wp_filesystem->is_readable( $home_dir ) ) {
			return array(
				0.00,
				0.00,
				0.00,
				false,
			);
		}

		// Get filesystem disk space information.
		$disk_total_space = disk_total_space( $home_dir );
		$disk_free_space  = disk_free_space( $home_dir );
		$disk_used_space  = $disk_total_space - $disk_free_space;

		// Initialize $wp_root_size.
		$wp_root_size = false;

		// Get the size of the filtered WordPress installation root directory (ABSPATH).
		if ( $get_wp_size ) {
			$wp_root_size = $this->get_wp_size();
		}

		// Return the disk information array.
		return array(
			$disk_total_space,
			$disk_used_space,
			$disk_free_space,
			$wp_root_size,
		);
	}

	/**
	 * Get the WordPress total file size.
	 *
	 * @since 1.0
	 *
	 * @see get_filtered_filelist
	 *
	 * @return int|bool The total size for the WordPress file system in bytes, or FALSE on error.
	 */
	public function get_wp_size() {
		// Save time, use transients.
		$transient = get_transient( 'boldgrid_backup_wp_size' );

		if ( false !== $transient ) {
			return $transient;
		}

		// Avoid timeout caused when node_modules exist. Return 0 bytes.
		if ( empty( $_GET['skip_node_modules'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$node_modules_found = $this->node_modules_warning();
			if ( true === $node_modules_found ) {
				return 0;
			}
		}

		// Perform functionality tests.
		$is_functional = $this->run_functionality_tests();

		// If plugin is not functional, then return FALSE.
		if ( ! $is_functional ) {
			return false;
		}

		$size = $this->core->filelist->get_size();

		// Save time, use transients.
		set_transient( 'boldgrid_backup_wp_size', $size, $this->transient_time );

		// Return the result.
		return $size;
	}

	/**
	 * Check for support when php is ran from the CLI.
	 *
	 * If running a PHP script from CLI, it's possible that a different php.ini file will be used than
	 * if a server (nginx or apache) runs it.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_cli_support() {
		$default = array(
			'has_curl_ssl'  => false,
			'has_url_fopen' => false,
		);

		// Set a file path for the cli-support script output.
		$filepath = wp_normalize_path( $this->core->backup_dir->get() . '/cli-support.txt' );

		// Configure an array of commands to run.
		$cmds = array(
			'php -qf ' . trailingslashit( BOLDGRID_BACKUP_PATH ) . 'cron/cli-support.php',
		);

		if ( ! $this->is_windows() && $this->core->execute_command( 'env' ) ) {
			// Some environments may run PHP in CGI mode; try to force CLI, by preferencing paths.
			$cmds[] = 'env PATH=/usr/local/bin:/usr/bin:/bin ' . $cmds[0];

			if ( $this->is_ea4_cli() ) {
				// If is a cPanel EA4 server with php-cli, then try using env first.
				sort( $cmds );
			}
		}

		// Find a command that gives us an array.
		foreach ( $cmds as $cmd ) {
			$this->core->execute_command( $cmd, $null, $null, $filepath );

			// Our command may have resulted in unexpected output. Look for a json string.
			preg_match( '/{.*}/', $this->core->wp_filesystem->get_contents( $filepath ), $matches );

			$result = is_array( $matches ) && isset( $matches[0] ) ? json_decode( $matches[0], true ) : null;

			$this->core->wp_filesystem->delete( $filepath );

			if ( ! is_array( $result ) ) {
				continue;
			}

			break;
		}

		$result = is_array( $result ) ? wp_parse_args( $result, $default ) : $default;

		$result['can_remote_get'] = $result['has_curl_ssl'] || $result['has_url_fopen'];

		return $result;
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
		// Save some time, get transient.
		$transient = get_transient( 'boldgrid_backup_db_size' );

		if ( false !== $transient ) {
			return $transient;
		}

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Build query.
		$query = sprintf(
			'SELECT SUM(`data_length` + `index_length`) FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`=' .
				"'%s'" . ' AND `TABLE_NAME` LIKE ' . "'%s'" . ' GROUP BY `TABLE_SCHEMA`;',
			DB_NAME,
			$wpdb->get_blog_prefix( is_multisite() ) . '%'
		);

		// Check query.
		if ( empty( $query ) ) {
			return 0;
		}

		// Get the result.
		$result = $wpdb->get_row( $query, ARRAY_N ); // phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared

		// If there was an error or nothing returned, then fail.
		if ( empty( $result ) ) {
			return 0;
		}

		$size = (int) $result[0];

		// Save some time, set transient.
		set_transient( 'boldgrid_backup_db_size', $size, $this->transient_time );

		// Return result.
		return $size;
	}

	/**
	 * Get and return a boolean for whether or not the ABSPATH is writable.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return bool
	 */
	public function get_is_abspath_writable() {
		if ( null !== $this->is_abspath_writable ) {
			return $this->is_abspath_writable;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Determine if ABSPATH is writable.
		$this->is_abspath_writable = $this->is_writable( ABSPATH );

		// Return the result.
		return $this->is_abspath_writable;
	}

	/**
	 * Get and return a boolean for whether or not the home directory is writable.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	public function is_homedir_writable() {
		if ( null !== $this->is_homedir_writable ) {
			return $this->is_homedir_writable;
		}

		// Get the user home directory.
		$home_dir = $this->core->config->get_home_directory();

		// Check if home directory is writable.
		$this->is_homedir_writable = $this->is_writable( $home_dir );

		// Return the result.
		return $this->is_homedir_writable;
	}

	/**
	 * Determine if we are on an IIS server.
	 *
	 * @since 1.5.1
	 *
	 * @return bool
	 */
	public function is_iis() {
		return $this->is_windows() &&
				! empty( $_SERVER['SERVER_SOFTWARE'] ) &&
				false !== strpos( $_SERVER['SERVER_SOFTWARE'], 'IIS' );
	}

	/**
	 * Determine whether pcl_zip is supported.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function is_pcl_zip_supported() {
		$pcl_zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		return $pcl_zip->test( false );
	}

	/**
	 * Determine if this server has cPanel EasyApache 4 with php-cli installed.
	 *
	 * @since 1.6.3
	 *
	 * @link https://developer.wordpress.org/reference/classes/wp_filesystem_direct/
	 *
	 * @return bool
	 */
	public function is_ea4_cli() {
		$is_ea4 = $this->core->wp_filesystem->exists( '/etc/cpanel/ea4/is_ea4' ) ||
			$this->core->wp_filesystem->is_dir( '/etc/cpanel/ea4' );

		$has_php_cli = $this->core->wp_filesystem->exists( '/usr/local/bin/php' );

		return $is_ea4 || $has_php_cli;
	}

	/**
	 * Determine whether or not the current filesystem is supported.
	 *
	 * @since 1.7.0
	 *
	 * @global object $wp_filesystem
	 *
	 * @return boolean
	 */
	public static function is_filesystem_supported() {
		global $wp_filesystem;

		$supported = true;

		// Ensure that the WP Filesystem API is loaded.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( 'direct' !== get_filesystem_method() ) {
			$supported = false;
		}

		return $supported;
	}
}
