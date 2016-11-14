<?php
/**
 * The admin-specific test functionality of the plugin
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
 * BoldGrid Backup admin test class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Test {
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
	 * Is WP-CRON enabled?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $wp_cron_enabled = null;

	/**
	 * Is mysqldump available?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $mysqldump_available = null;

	/**
	 * Is PHP in safe mode?
	 *
	 * @since 1.0
	 * @access private
	 * @var bool
	 */
	private $is_php_safemode = null;

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
	 * Transient time for disk / db size data.
	 *
	 * @since  1.3.1
	 * @access public
	 * @var    int
	 */
	public $transient_time = 0;

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

		$this->transient_time = 5 * MINUTE_IN_SECONDS;
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
		$output = $this->core->execute_command( $command, null, $success, $return_var );

		// Set class property.
		$this->is_crontab_available = ( $success || (bool) $output || 1 === $return_var );

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
		$output = $this->core->execute_command( $command, null, $success );

		// Set class property.
		$this->mysqldump_available = ( $success || (bool) $output );

		return $this->mysqldump_available;
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
	 * Perform functionality tests.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return bool
	 */
	public function run_functionality_tests() {
		// If functionality tests were already performed, then just return status.
		if ( $this->functionality_tested && null !== $this->is_functional ) {
			return $this->is_functional;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// If not writable, then mark as not functional.
		if ( ! $this->get_is_abspath_writable() ) {
			$this->is_functional = false;
		}

		// Configure the backup directory path, or mark as not functional.
		if ( ! $this->core->config->get_backup_directory() ) {
			$this->is_functional = false;
		}

		// Get available compressors.
		$available_compressors = $this->core->config->get_available_compressors();

		// Test for available compressors, and add them to the array, or mark as not functional.
		if ( empty( $available_compressors ) ) {
			$this->is_functional = false;
		}

		// Test for crontab. For now, don't check if wp-cron is enabled.
		if ( ! $this->is_crontab_available() ) {
			$this->is_functional = false;
		}

		// Test for mysqldump. For now, don't use wpbd.
		if ( ! $this->is_mysqldump_available() ) {
			$this->is_functional = false;
		}

		// Test for PHP safe mode.
		if ( $this->is_php_safemode() ) {
			$this->is_functional = false;
		}

		// Test for PHP Zip (currently the only one coded).
		if ( ! $this->core->config->is_compressor_available( 'php_zip' ) ) {
			$this->is_functional = false;
		}

		// Save result, if not previously saved.
		if ( null === $this->is_functional ) {
			$this->is_functional = true;
		}

		// Mark as completed.
		$this->functionality_tested = true;

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
		$home_dir = $this->core->config->get_home_directory();

		// If the home directory is not defined, not a directory or not writable, then return 0.00.
		if ( empty( $home_dir ) || ! $wp_filesystem->is_dir( $home_dir ) ||
			! $wp_filesystem->is_writable( $home_dir ) ) {
			return array(
				0.00,
				0.00,
				0.00,
			);
		}

		// Get filesystem disk space information.
		$disk_total_space = disk_total_space( $home_dir );
		$disk_free_space = disk_free_space( $home_dir );
		$disk_used_space = $disk_total_space - $disk_free_space;

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
	 * @access private
	 *
	 * @see get_filtered_filelist
	 *
	 * @return int|bool The total size for the WordPress file system in bytes, or FALSE on error.
	 */
	private function get_wp_size() {
		// Perform functionality tests.
		$is_functional = $this->run_functionality_tests();

		// If plugin is not functional, then return FALSE.
		if ( ! $is_functional ) {
			return false;
		}

		// Save time, use transients.
		if( false !== ( $transient = get_transient( 'boldgrid_backup_wp_size' ) ) ) {
			return $transient;
		}

		// Get the filtered file list.
		$filelist = $this->core->get_filtered_filelist( ABSPATH );

		// If nothing was found, then return 0.
		if ( empty( $filelist ) ) {
			return 0;
		}

		// Initialize total_size.
		$size = 0;

		// Add up the file sizes.
		foreach ( $filelist as $fileinfo ) {
			// Add the file size to the total.
			// get_filelist() returns fileinfo array with index 2 for filesize.
			$size += $fileinfo[2];
		}

		// Save time, use transients.
		set_transient( 'boldgrid_backup_wp_size', $size, $this->transient_time );

		// Return the result.
		return $size;
	}

	/**
	 * Get any applicable messages regarding disk / db sizes.
	 *
	 * One message is returned for disk space, and another returned for db size.
	 *
	 * For example, "Cannot backup your account, too much disk space used."
	 *
	 * @since 1.3.1
	 *
	 * @param  array $disk_space
	 * @param  int   $db_size
	 * @return array
	 */
	public function get_size_messages( $disk_space, $db_size ) {
		// Define an array of possible messages for the user.
		$messages = array(
			// Supported but <a>Issues may Occur</a>.
			'issues_may_occur' => sprintf(
				wp_kses(
					__( 'Supported but <a href="%s" target="_blank">Issues may Occur</a>', 'boldgrid-backup' ),
					array(  'a' => array( 'href' => array() ), 'target' => array() )
				),
				esc_url( 'https://www.boldgrid.com' )
			),
			// <a>Must Reduce before Running</a>.
			'must_reduce' => sprintf(
				'<a href="https://www.boldgrid.com" target="_blank">%s</a>',
				esc_html__( 'Must Reduce before Running', 'boldgrid-backup' )
			),
			// Requires Upgrade.
			'requires_upgrade' => esc_html__( 'Requires Upgrade', 'boldgrid-backup' ),
			// Supported.
			'supported' => esc_html__( 'Supported', 'boldgrid-backup' ),
		);

		/*
		 * There are certain restrictions on free / premium version of the plugin. The IF statement
		 * adds notices for the free version of the plugin, the ELSE premium notices.
		*/
		if( false === $this->core->config->get_is_premium() ) {
			// Check disk space.
			if( $disk_space[3] > $this->core->config->get_max_free_disk() ) {
				$return['disk'] = $messages['requires_upgrade'];
			} else {
				$return['disk'] = $messages['supported'];
			}

			// Check db space.
			if( $db_size > $this->core->config->get_max_free_db() ) {
				$return['db'] = $messages['requires_upgrade'];
			} else {
				$return['db'] = $messages['supported'];
			}
		} else {
			// Check disk space.
			if( $disk_space[3] > $this->core->config->get_max_disk_high() ) {
				$return['disk'] = $messages['must_reduce'];
			}elseif( $disk_space[3] > $this->core->config->get_max_disk_low() ) {
				$return['disk'] = $messages['issues_may_occur'];
			} else {
				$return['disk'] = $messages['supported'];
			}

			// Check db space.
			if( $db_size > $this->core->config->get_max_db_high() ) {
				$return['db'] = $messages['must_reduce'];
			}elseif( $db_size > $this->core->config->get_max_db_low() ) {
				$return['db'] = $messages['issues_may_occur'];
			} else {
				$return['db'] = $messages['supported'];
			}
		}

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
		// Save some time, get transient.
		if( false !== ( $transient = get_transient( 'boldgrid_backup_db_size' ) ) ) {
			return $transient;
		}

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Build query.
		$query = $wpdb->prepare(
			'SELECT SUM(`data_length` + `index_length`) FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`=%s AND `TABLE_NAME` LIKE %s GROUP BY `TABLE_SCHEMA`;',
			DB_NAME, $wpdb->get_blog_prefix( is_multisite() ) . '%'
		);

		// Check query.
		if ( empty( $query ) ) {
			return 0;
		}

		// Get the result.
		$result = $wpdb->get_row( $query, ARRAY_N );

		// If there was an error or nothing returned, then fail.
		if ( empty( $result ) ) {
			return 0;
		}

		// Save some time, set transient.
		set_transient( 'boldgrid_backup_db_size', $result[0], $this->transient_time );

		// Return result.
		return $result[0];
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
		$this->is_abspath_writable = $wp_filesystem->is_writable( ABSPATH );

		// Return the result.
		return $this->is_abspath_writable;
	}

	/**
	 * Get and return a boolean for whether or not the home directory is writable.
	 *
	 * @since 1.2
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return bool
	 */
	public function is_homedir_writable() {
		if ( null !== $this->is_homedir_writable ) {
			return $this->is_homedir_writable;
		}

		// Get the user home directory.
		$home_dir = $this->core->config->get_home_directory();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if home directory is writable.
		$this->is_homedir_writable = $wp_filesystem->is_writable( $home_dir );

		// Return the result.
		return $this->is_homedir_writable;
	}
}
