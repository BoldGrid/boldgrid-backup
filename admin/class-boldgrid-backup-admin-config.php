<?php
/**
 * The admin-specific configuration class for the plugin
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
 * BoldGrid Backup admin configuration class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Config {
	/**
	 * The core class object.
	 *
	 * @since 1.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

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
	 * Available compressors.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $available_compressors = array();

	/**
	 * The default retention.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $default_retention = 4;

	/**
	 * This is the premium version of the plugin.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    bool
	 */
	private $is_premium = false;

	/**
	 * Language.
	 *
	 * @since  1.3.1
	 * @access public
	 * @var    array
	 */
	public $lang = array();

	/**
	 * Max db space (high end) that can be backed up.
	 *
	 * Between $this->max_db_low and $this->max_db_high.
	 *
	 * 1GB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_db_high = 1073741824;

	/**
	 * Max db space (low end) that can be backed up.
	 *
	 * Between $this->max_db_low and $this->max_db_high.
	 *
	 * 100MB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_db_low = 104857600;

	/**
	 * Max disk space (low end) that can be backed up without running into issues.
	 *
	 * Between $this->max_disk_low and $this->max_disk_high.
	 *
	 * 1GB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_disk_low = 1073741824;

	/**
	 * Max disk space (high end) that can be backed up without running into issues.
	 *
	 * Between $this->max_disk_low and $this->max_disk_high.
	 *
	 * 10GB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_disk_high = 10737418240;

	/**
	 * Free plugin: Max days of the week backups can be scheduled for.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_dow = 2;

	/**
	 * Free plugin: Max db space that can be backed up.
	 *
	 * 100MB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_free_db = 104857600;

	/**
	 * Free plugin: Max disk space that can be backed up.
	 *
	 * 1GB.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_free_disk = 1073741824;

	/**
	 * Free plugin: Max number of archives to retain.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    int
	 */
	private $max_retention = 4;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		if( is_object( $core ) ) {
			$this->core = $core;
		}

		// Temporary, for testing only.
		if( 'true' === get_option( 'boldgrid_backup_is_premium' ) ) {
			$this->is_premium = true;
		}

		if( true === $this->is_premium ) {
			$this->default_retention = 5;
		}

		$this->set_lang();
	}

	/**
	 * Get the user home directory.
	 *
	 * @since 1.0
	 *
	 * @return string The path to the user home directory.
	 */
	public function get_home_directory() {
		// If home directory was already set, then return it.
		if ( ! empty( $this->home_dir ) ) {
			return $this->home_dir;
		}

		// For Windows and Linux.
		if ( $this->core->test->is_windows() ) {
			// Windows.
			$home_drive = ( ! empty( $_SERVER['HOMEDRIVE'] ) ? $_SERVER['HOMEDRIVE'] : null );
			$home_path = ( ! empty( $_SERVER['HOMEPATH'] ) ? $_SERVER['HOMEPATH'] : null );

			if ( ! ( empty( $home_drive ) || empty( $home_path ) ) ) {
				$home_dir = $home_drive . $home_path;
			}

			// If still unknown, then try getenv USERPROFILE.
			if ( empty( $home_dir ) ) {
				$home_dir = getenv( 'USERPROFILE' );
			}
		} else {
			// Linux.
			$home_dir = getenv( 'HOME' );

			if ( empty( $home_dir ) ) {
				$home_dir = ( ! empty( $_SERVER['HOME'] ) ? $_SERVER['HOME'] : null );
			}
		}

		// If still unknown, then try posix_getpwuid and posix_getuid.
		if ( empty( $home_dir ) && function_exists( 'posix_getuid' ) &&
			function_exists( 'posix_getpwuid' ) ) {
				$user = posix_getpwuid( posix_getuid() );

				$home_dir = ( ! empty( $user['dir'] ) ? $user['dir'] : null );
		}

		// Could not find the user home directory, so use the WordPress root directory.
		if ( empty( $home_dir ) ) {
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
	 * Get the mode (last 3 characters of the octal number) of the home directory.
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return string The mode of the home directory.
	 */
	public function get_home_mode() {
		// Get the user home directory.
		$home_dir = $this->get_home_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Get the mode of the directory.
		$home_dir_mode = $wp_filesystem->getchmod( $home_dir );

		return $home_dir_mode;
	}

	/**
	 * Get is_premium.
	 *
	 * @since 1.3.1
	 *
	 * @return bool
	 */
	public function get_is_premium() {
		return $this->is_premium;
	}

	/**
	 * Get max_db_low.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_db_low() {
		return $this->max_db_low;
	}

	/**
	 * Get max_db_high.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_db_high() {
		return $this->max_db_high;
	}

	/**
	 * Get max_disk_high.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_disk_high() {
		return $this->max_disk_high;
	}

	/**
	 * Get max_disk_low.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_disk_low() {
		return $this->max_disk_low;
	}

	/**
	 * Get max_dow.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_dow() {
		return $this->max_dow;
	}

	/**
	 * Get max_free_db.
	 *
	 * @since 1.3.1
	 *
	 * @return int.
	 */
	public function get_max_free_db() {
		return $this->max_free_db;
	}

	/**
	 * Get max_free_disk.
	 *
	 * @since 1.3.1
	 *
	 * @return int.
	 */
	public function get_max_free_disk() {
		return $this->max_free_disk;
	}

	/**
	 * Get max_retention.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_max_retention() {
		return $this->max_retention;
	}

	/**
	 * Get and return the backup directory path.
	 *
	 * @since 1.0
	 *
	 * @return string|bool The backup directory path, or FALSE on error.
	 */
	public function get_backup_directory() {
		// If home directory is not set, then set it.
		if ( empty( $this->backup_directory ) ) {
			// Initialize $backup_directory.
			$backup_directory = '';

			// Get settings.
			$settings = $this->core->settings->get_settings();

			// If the backup directory was saved in the settings, then use it.
			if ( ! empty( $settings['backup_directory'] ) ) {
				$backup_directory = $settings['backup_directory'];
			}

			$is_directory_set = $this->set_backup_directory( $backup_directory );

			// The backup directory could not be set.
			if ( ! $is_directory_set ) {
				return false;
			}
		}

		// Backup directory was set, so return the path.
		return $this->backup_directory;
	}

	/**
	 * Get default_retention.
	 *
	 * @since 1.3.1
	 *
	 * @return int
	 */
	public function get_default_retention() {
		return $this->default_retention;
	}

	/**
	 * Set backup directory.
	 *
	 * @since 1.0.1
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $backup_directory_path The backup directory path to be set/configured.
	 * @return bool
	 */
	public function set_backup_directory( $backup_directory_path = '' ) {
		// If a backup directory was not specified, then use the default.
		if ( empty( $backup_directory_path ) ) {
			// Get the user home directory.
			$home_dir = $this->get_home_directory();

			// Check if home directory is writable.
			$home_dir_writable = $this->core->test->is_homedir_writable();

			// If home directory is not writable, then abort.
			if ( ! $home_dir_writable ) {
				return false;
			}

			// Define the backup directory name, using the default.
			$backup_directory_path = $home_dir . '/boldgrid_backup';
		}

		// Initialize WP_Filesystem.
		WP_Filesystem();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory exists.
		$backup_directory_exists = $wp_filesystem->exists( $backup_directory_path );

		// If the backup directory does not exist, then attempt to create it.
		if ( ! $backup_directory_exists ) {
			$backup_directory_created = $wp_filesystem->mkdir( $backup_directory_path, 0700 );

			// If mkdir failed, then notify and abort.
			if ( ! $backup_directory_created ) {
				// Create error message.
				$errormsg = sprintf(
					esc_html__( 'Could not create directory "%s".', 'boldgrid-backup' ),
					$backup_directory_path
				);

				// Trigger an admin notice.
				do_action( 'boldgrid_backup_notice', $errormsg, 'notice notice-error is-dismissible' );

				// Abort.
				return false;
			}
		}

		// Check if the backup directory is a directory.
		$backup_directory_isdir = $wp_filesystem->is_dir( $backup_directory_path );

		// If the backup directory is not a directory, then notify and abort.
		if ( ! $backup_directory_isdir ) {
			// Create error message.
			$errormsg = sprintf(
				esc_html__( 'Backup directory "%s" is not a directory.', 'boldgrid-backup' ),
				$backup_directory_path
			);

			// Trigger an admin notice.
			do_action( 'boldgrid_backup_notice', $errormsg, 'notice notice-error is-dismissible' );

			// Abort.
			return false;
		}

		// If the backup directory is not writable, then notify and abort.
		if ( ! $wp_filesystem->is_writable( $backup_directory_path ) ) {
			// Get the mode of the directory.
			$backup_directory_mode = $wp_filesystem->getchmod( $backup_directory_path );

			// Create error message.
			$errormsg = sprintf(
				esc_html__(
					'Backup directory "%s" (mode %s) is not writable.',
					'boldgrid-backup'
				),
				$backup_directory_path,
				$backup_directory_mode
			);

			// Trigger an admin notice.
			do_action( 'boldgrid_backup_notice', $errormsg, 'notice notice-error is-dismissible' );

			// Abort.
			return false;
		}

		// Record the backup directory path.
		$this->backup_directory = $backup_directory_path;

		// Return success.
		return true;
	}

	/**
	 * Set lang.
	 *
	 * @since 1.3.1
	 */
	public function set_lang() {
		$this->lang = array(
			'website_size' => esc_html__( 'Website Size:', 'boldgrid-backup' ),
			'database_size' => esc_html__( 'Database Size:', 'boldgrid-backup' ),
		);
	}

	/**
	 * Custom upload directory callback.
	 *
	 * @since 1.2.2
	 *
	 * @see Boldgrid_Backup_Admin_Config::get_backup_directory()
	 *
	 * @param array $upload Upload data array.
	 * @return array
	 */
	public function custom_upload_dir( $upload ) {
		// Get the backup directory path.
		$backup_directory = $this->get_backup_directory();

		// Get the subdirectory name.
		$subdir = explode( '/', $backup_directory );
		$subdir = $subdir[ count( $subdir ) - 1 ];

		$upload['subdir'] = $subdir;
		$upload['path']   = $backup_directory;
		$upload['url']    = null;

		return $upload;
	}

	/**
	 * Get the WordPress admin email address.
	 *
	 * @since 1.0.1
	 *
	 * @return string|bool The admin email address, or FALSE on error.
	 */
	public function get_admin_email() {
		// Initialize $admin_email.
		$admin_email = null;

		// Get the site email address.
		// Try get_bloginfo.
		if ( function_exists( 'get_bloginfo' ) ) {
			$admin_email = get_bloginfo( 'admin_email' );
		}

		// If the email address is still needed, then try wp_get_current_user.
		if ( empty( $admin_email ) && function_exists( 'wp_get_current_user' ) ) {
			// Get the current user information.
			$current_user = wp_get_current_user();

			// Check if user information was retrieved, abort if not.
			if ( ! $current_user ) {
				return false;
			}

			// Get the current user email address.
			$admin_email = $current_user->user_email;
		}

		// If there is no email address found, then abort.
		if ( empty( $admin_email ) ) {
			return false;
		}

		// Return the admin email address.
		return $admin_email;
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
		if ( ! empty( $compressor ) &&
		! in_array( $compressor, $this->available_compressors, true ) ) {
			$this->available_compressors[] = $compressor;
		}

		return;
	}

	/**
	 * Is a specific archive compressor available?
	 *
	 * @since 1.0
	 *
	 * @param string $compressor A string to identify a compressor.
	 * @return bool
	 */
	public function is_compressor_available( $compressor = null ) {
		// If input parameter is empty, then fail.
		if ( empty( $compressor ) || empty( $this->available_compressors ) ) {
			return false;
		}

		// Check the array to see if the specified compressor is available.
		$is_available = in_array( $compressor, $this->available_compressors, true );

		return $is_available;
	}

	/**
	 * Get available compressors.
	 *
	 * Test for available archive compressors, add them to the array in a preferred order, and
	 * return the array.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_available_compressors() {
		// If at least one compressor is already configured, then return TRUE.
		if ( ! empty( $this->available_compressors ) ) {
			return $this->available_compressors;
		}

		// Initialize $this->available_compressors to an empty array.
		$this->available_compressors = array();

		// PHP zip (ZipArchive).
		if ( extension_loaded( 'zip' ) && class_exists( 'ZipArchive' ) ) {
			$this->add_compressor( 'php_zip' );
		}

		// PHP bz2 (Bzip2).
		if ( extension_loaded( 'bz2' ) && function_exists( 'bzcompress' ) ) {
			$this->add_compressor( 'php_bz2' );
		}

		// PHP zlib (Zlib).
		if ( extension_loaded( 'zlib' ) && function_exists( 'gzwrite' ) ) {
			$this->add_compressor( 'php_zlib' );
		}

		// PHP lzf (LZF).
		if ( function_exists( 'lzf_compress' ) ) {
			$this->add_compressor( 'php_lzf' );
		}

		// System tar.
		if ( file_exists( '/bin/tar' ) && is_executable( '/bin/tar' ) ) {
			$this->add_compressor( 'system_tar' );
		}

		// System zip.
		if ( file_exists( '/usr/bin/zip' ) && is_executable( '/usr/bin/zip' ) ) {
			$this->add_compressor( 'system_zip' );
		}

		return $this->available_compressors;
	}
}
