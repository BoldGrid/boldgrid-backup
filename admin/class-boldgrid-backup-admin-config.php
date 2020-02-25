<?php
/**
 * File: class-boldgrid-backup-admin-config.php
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
 * Class: Boldgrid_Backup_Admin_Config
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
	private $default_retention = 5;

	/**
	 * This is the premium version of the plugin.
	 *
	 * @since  1.3.1
	 * @access private
	 * @var    bool
	 */
	private $is_premium = false;

	/**
	 * Whether or not the premium plugin is activated.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    bool
	 */
	public $is_premium_active = false;

	/**
	 * Whether or not we have a premium license and the premium extension is
	 * installed.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    bool
	 */
	public $is_premium_done = false;

	/**
	 * Whether or not the premium extension is installed (we didn't say activated,
	 * just installed, the files exist on the server).
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    bool
	 */
	public $is_premium_installed = false;

	/**
	 * Language.
	 *
	 * @since  1.3.1
	 * @access public
	 * @var    array
	 */
	public $lang = array();

	/**
	 * License, an instance of \Boldgrid\Library\Library\License.
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    \Boldgrid\Library\Library\License
	 */
	protected $license;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		if ( is_object( $core ) ) {
			$this->core = $core;
		}

		if ( class_exists( '\Boldgrid\Library\Library\License' ) ) {
			$this->license    = new \Boldgrid\Library\Library\License();
			$this->is_premium = $this->license->isPremium( 'boldgrid-backup' );
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

		if ( $this->core->test->is_windows() && $this->core->test->is_plesk() ) {
			/*
			 * Plesk's File Manager labels C:\Inetpub\vhosts\domain.com as the
			 * "Home directory". If we find we cannot read that directory, then
			 * we'll use the document root as the home directory.
			 */
			$home_dir = dirname( $_SERVER['DOCUMENT_ROOT'] );
			if ( ! $this->core->wp_filesystem->is_readable( $home_dir ) ) {
				$home_dir = $_SERVER['DOCUMENT_ROOT'];
			}
		} elseif ( $this->core->test->is_windows() ) {
			// Windows.
			$home_drive = ( ! empty( $_SERVER['HOMEDRIVE'] ) ? $_SERVER['HOMEDRIVE'] : null );
			$home_path  = ( ! empty( $_SERVER['HOMEPATH'] ) ? $_SERVER['HOMEPATH'] : null );

			if ( ! ( empty( $home_drive ) || empty( $home_path ) ) ) {
				$home_dir = $home_drive . $home_path;
			}

			// If still unknown, then try getenv USERPROFILE.
			if ( empty( $home_dir ) ) {
				$home_dir = getenv( 'USERPROFILE' );
			}
		} else {
			// Linux.  Try posix_getpwuid and posix_getuid.
			if ( function_exists( 'posix_getuid' ) && function_exists( 'posix_getpwuid' ) ) {
				$user     = posix_getpwuid( posix_getuid() );
				$home_dir = ( ! empty( $user['dir'] ) ? $user['dir'] : null );
			}

			if ( empty( $home_dir ) ) {
				$home_dir = ( ! empty( $_SERVER['HOME'] ) ? $_SERVER['HOME'] : null );
			}

			// If still unknown, then try environmental variables.
			if ( empty( $home_dir ) ) {
				$home_dir = getenv( 'HOME' );
			}
		}

		// Could not find the user home directory, so use the WordPress root directory.
		if ( empty( $home_dir ) ) {
			$home_dir = ABSPATH;
		}

		// Use rtrim the $home_dir to strip any trailing slashes.
		$home_dir = rtrim( $home_dir, '\\/' );
		$home_dir = str_replace( '\\\\', '\\', $home_dir );

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
	 * The is_premium class property is set within the constructor.
	 *
	 * @since 1.3.1
	 *
	 * @return bool
	 */
	public function get_is_premium() {
		return $this->is_premium;
	}

	/**
	 * Get our license string, such as "Free" or "Premium".
	 *
	 * @since 1.6.0
	 */
	public function get_license_string() {
		if ( ! isset( $this->license ) ) {
			return __( 'Unknown', 'boldgrid-backup' );
		} else {
			return $this->license->getLicenseString();
		}
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
	 * Set lang.
	 *
	 * @since 1.3.1
	 */
	public function set_lang() {
		$this->lang = array(
			'website_size'     => esc_html__( 'Website Size:', 'boldgrid-backup' ),
			'database_size'    => esc_html__( 'Database Size:', 'boldgrid-backup' ),
			'of'               => esc_html__( 'of', 'boldgrid-backup' ),
			'xmark'            => '&#10007;',
			'update'           => esc_html__( 'Update', 'boldgrid-backup' ),
			'updating'         => esc_html__( 'Updating...', 'boldgrid-backup' ),
			'updated'          => esc_html__( 'Updated!', 'boldgrid-backup' ),
			'failed_to_update' => esc_html__( 'Failed to update: ', 'boldgrid-backup' ),
			'unknown_error'    => esc_html__( 'Unknown error.', 'boldgrid-backup' ),
		);
	}

	/**
	 * Custom upload directory callback.
	 *
	 * @since 1.2.2
	 *
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 *
	 * @param array $upload Upload data array.
	 * @return array
	 */
	public function custom_upload_dir( $upload ) {
		// Get the backup directory path.
		$backup_directory = $this->core->backup_dir->get();

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
	 */
	private function add_compressor( $compressor = null ) {
		if ( ! empty( $compressor ) &&
		! in_array( $compressor, $this->available_compressors, true ) ) {
			$this->available_compressors[] = $compressor;
		}
	}

	/**
	 * Actions to take during the admin_init hook.
	 *
	 * @since 1.6.0
	 */
	public function admin_init() {
		$relative_path = 'boldgrid-backup-premium/boldgrid-backup-premium.php';
		$abs_path      = dirname( BOLDGRID_BACKUP_PATH ) . '/' . $relative_path;

		// Function is_plugin_active only available in and after admin_init.
		$this->is_premium_active = is_plugin_active( $relative_path );

		$this->is_premium_installed = $this->core->wp_filesystem->exists( $abs_path );

		$this->is_premium_done = $this->is_premium && $this->is_premium_active;
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
	 * @see Boldgrid_Backup_Admin_Core::execute_command()
	 *
	 * @return array
	 */
	public function get_available_compressors() {
		// If at least one compressor is already configured, then return TRUE.
		if ( ! empty( $this->available_compressors ) ) {
			return $this->available_compressors;
		}

		if ( ! class_exists( 'PclZip' ) ) {
			require_once ABSPATH . '/wp-admin/includes/class-pclzip.php';
		}

		// Initialize $this->available_compressors to an empty array.
		$this->available_compressors = array();

		// PHP zip (ZipArchive).
		if ( Boldgrid_Backup_Admin_Compressor_Php_Zip::is_extension_available() ) {
			$this->add_compressor( 'php_zip' );
		}

		// PclZip.
		if ( class_exists( 'PclZip' ) ) {
			$this->add_compressor( 'pcl_zip' );
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

		if ( $this->core->test->is_windows() ) {
			return $this->available_compressors;
		}

		// System tar.
		if ( $this->core->execute_command( '/bin/tar --version' ) ) {
			$this->add_compressor( 'system_tar' );
		}

		// System zip.
		$system_zip_test = new Boldgrid_Backup_Admin_Compressor_System_Zip_Test( $this->core );
		if ( $system_zip_test->run() ) {
			$this->add_compressor( 'system_zip' );
		}

		return $this->available_compressors;
	}
}
