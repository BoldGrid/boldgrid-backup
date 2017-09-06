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
	 * Transient time (in seconds) for disk / db size data.
	 *
	 * Default value is 300 seconds (5 minutes).
	 *
	 * @since  1.3.1
	 * @access public
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

		if( $this->is_windows() ) {
			$this->is_crontab_available = false;
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

		// Test for PHP safe mode.
		if ( $this->is_php_safemode() ) {
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
	 * @global object $wp_filesystem
	 *
	 * @return int|bool The total size for the WordPress file system in bytes, or FALSE on error.
	 */
	private function get_wp_size() {
		global $wp_filesystem;

		// Perform functionality tests.
		$is_functional = $this->run_functionality_tests();

		// If plugin is not functional, then return FALSE.
		if ( ! $is_functional ) {
			return false;
		}

		/*
		 * Include wp-includes/ms-functions.php.
		 *
		 * This method uses WordPress' recurse_dirsize function, which is loaded on multisite
		 * installations. If the recurse_dirsize function does not exist, include the necessary
		 * file.
		 */
		if ( ! function_exists( 'recurse_dirsize' ) ) {
			require_once ABSPATH . 'wp-includes/ms-functions.php';
		}

		// Save time, use transients.
		if ( false !== ( $transient = get_transient( 'boldgrid_backup_wp_size' ) ) ) {
			return $transient;
		}

		// Get the filtered file list.
		$filelist = $this->core->get_filelist_filter();

		// If nothing was found, then return 0.
		if ( empty( $filelist ) ) {
			return 0;
		}

		// Initialize total_size.
		$size = 0;

		foreach ( $filelist as $file ) {
			$file_path = ABSPATH . $file;

			if ( is_dir( $file_path ) ) {
				$size += recurse_dirsize( $file_path );
			} else {
				$size += $wp_filesystem->size( $file_path );
			}
		}

		// Save time, use transients.
		set_transient( 'boldgrid_backup_wp_size', $size, $this->transient_time );

		// Return the result.
		return $size;
	}

	/**
	 * Get an array of data concerning sizes.
	 *
	 * This data includes information about current disk and database usage and limits. It also
	 * calls $this->get_size_messages() to get text for the user explaining if / why they cannot
	 * backup their site.
	 *
	 * @since 1.3.3
	 *
	 * @return array.
	 */
	public function get_size_data() {
		$disk_space = $this->get_disk_space();
		$db_size = $this->get_database_size();
		$max_disk = $this->core->config->get_max_disk();
		$max_db = $this->core->config->get_max_db();

		$return = array(
			'disk_space' => $disk_space,
			'db_size' => $db_size,
			'disk_limit' => $max_disk,
			'db_limit' => $max_db,
		);

		/*
		 * Add additonal _hr (human readable as in 466.55MB) data to our $return variable. Done here
		 * so as not needed to be done by js.
		*/

		foreach( $disk_space as $k => $v ) {
			$return[ 'disk_space_hr' ][ $k ] = Boldgrid_Backup_Admin_Utility::bytes_to_human( $v );
		}

		$return[ 'db_size_hr' ] = Boldgrid_Backup_Admin_Utility::bytes_to_human( $db_size );
		$return[ 'disk_limit_hr' ] = Boldgrid_Backup_Admin_Utility::bytes_to_human( $max_disk );
		$return[ 'db_limit_hr' ] = Boldgrid_Backup_Admin_Utility::bytes_to_human( $max_db );

		// Add status messages about disk space and db size.
		$return['messages'] = $this->get_size_messages( $disk_space, $db_size, $max_disk, $max_db );

		return $return;
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
	 * @param  int   $max_size
	 * @param  int   $max_db
	 * @return array
	 */
	public function get_size_messages( $disk_space, $db_size, $max_disk, $max_db ) {
		// Define an array of possible messages for the user.
		$messages = array(
			// Supported but <a>Issues may Occur</a>.
			'issues_may_occur' => sprintf(
				wp_kses(
					__( 'Supported but <a href="%s" target="_blank">Issues may Occur</a>', 'boldgrid-backup' ),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				),
				esc_url( $this->core->configs['urls']['possible_issues'] )
			),
			// <a>Must Reduce before Running</a>.
			'must_reduce' => sprintf(
				'<a href="%2$s" target="_blank">%1$s</a>',
				esc_html__( 'Must Reduce before Running', 'boldgrid-backup' ),
				esc_url( $this->core->configs['urls']['reduce_size_warning'] )
			),
			// Requires Upgrade.
			'requires_upgrade' => esc_html__( 'Requires Upgrade', 'boldgrid-backup' ),
			// Supported.
			'supported' => esc_html__( 'Supported', 'boldgrid-backup' ),
		);

		// Determine if we have maxed our disk or db size.
		$exceeds_disk = $disk_space[3] > $max_disk;
		$exceeds_db = $db_size > $max_db;

		/*
		 * There are certain restrictions on free / premium version of the plugin. The IF statement
		 * adds notices for the free version of the plugin, the ELSE premium notices.
		*/
		if ( false === $this->core->config->get_is_premium() ) {
			// Check disk space.
			if ( $exceeds_disk ) {
				$return['disk'] = $messages['requires_upgrade'];
				$return['diskUsageClass'] = 'error';
			} else {
				$return['disk'] = $messages['supported'];
				$return['diskUsageClass'] = 'success';
			}

			// Check db space.
			if ( $exceeds_db ) {
				$return['db'] = $messages['requires_upgrade'];
				$return['dbUsageClass'] = 'error';
			} else {
				$return['db'] = $messages['supported'];
				$return['dbUsageClass'] = 'success';
			}
		} else {
			// Check disk space.
			if ( $exceeds_disk ) {
				$return['disk'] = $messages['must_reduce'];
				$return['diskUsageClass'] = 'error';
			} elseif ( $disk_space[3] > $this->core->config->get_max_disk_low() ) {
				$return['disk'] = $messages['issues_may_occur'];
				$return['diskUsageClass'] = 'warning';
			} else {
				$return['disk'] = $messages['supported'];
				$return['diskUsageClass'] = 'success';
			}

			// Check db space.
			if ( $exceeds_db ) {
				$return['db'] = $messages['must_reduce'];
				$return['dbUsageClass'] = 'error';
			} elseif ( $db_size > $this->core->config->get_max_db_low() ) {
				$return['db'] = $messages['issues_may_occur'];
				$return['dbUsageClass'] = 'warnings';
			} else {
				$return['db'] = $messages['supported'];
				$return['dbUsageClass'] = 'success';
			}
		}

		// If we have maxed database or disk size, return an applicable message.
		if( $exceeds_disk && $exceeds_db ) {
			$return['notSupported'] =
				__( 'Backups are currently not supported because your website exceeds both disk and database size limits.', 'boldgrid-backup' );
		} elseif( $exceeds_disk ) {
			$return['notSupported'] =
				__( 'Backups are currently not supported because your website exceeds size limits.', 'boldgrid-backup' );
		} elseif( $exceeds_db ) {
			$return['notSupported'] =
				__( 'Backups are currently not supported because your database exceeds size limits.', 'boldgrid-backup' );
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
		if ( false !== ( $transient = get_transient( 'boldgrid_backup_db_size' ) ) ) {
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
