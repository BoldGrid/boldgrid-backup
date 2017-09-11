<?php
/**
 * Boldgrid Backup Admin Backup Dir.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Boldgrid Backup Admin Backup Dir class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Backup_Dir {

	/**
	 * Backup directory.
	 *
	 * @since 1.5.1
	 * @var   string
	 */
	public $backup_directory;

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of errors.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    array
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get and return the backup directory path.
	 *
	 * @since 1.0
	 *
	 * @return string|bool The backup directory path, or FALSE on error.
	 */
	public function get( $display_errors = true ) {
		$backup_directory = ! empty( $this->backup_directory ) ? $this->backup_directory : $this->get_from_settings();

		$is_directory_set = $this->set( $backup_directory, $display_errors );

		if ( ! $is_directory_set ) {
			return false;
		}

		return $this->backup_directory;
	}

	/**
	 * Get our backup dir from the settings.
	 *
	 * @since 1.5.1
	 *
	 * @return string|bool
	 */
	public function get_from_settings() {
		$backup_directory = false;
		$settings = $this->core->settings->get_settings();

		if ( ! empty( $settings['backup_directory'] ) ) {
			$backup_directory = $settings['backup_directory'];
			$backup_directory = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_directory );
		}

		return $backup_directory;
	}

	/**
	 * Validate backup directory.
	 *
	 * Make sure it exists, it's writable, etc.
	 */
	public function is_valid() {
		$backup_dir = $this->get_from_settings();
		$perms = $this->core->test->extensive_dir_test( $backup_dir );

		return $perms['exists'] && $perms['read'] && $perms['write'] && $perms['rename'] && $perms['delete'] && $perms['dirlist'];
	}

	/**
	 * Get an array of possible backup directories.
	 *
	 * @since  1.5.1
	 * @return array
	 */
	public function get_backup_directories() {
		$dirs = array();

		$dirs[] = $this->get_home_directory();

		if( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {

			/*
			 * App_Data (Windows / Plesk).
			 *
			 * The App_Data folder is used as a data storage for the web
			 * application. It can store files such as .mdf, .mdb, and XML. It
			 * manages all of your application's data centrally. It is
			 * accessible from anywhere in your web application. The real
			 * advantage of the App_Data folder is that, any file you place
			 * there won't be downloadable.
			 */
			$app_data = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'App_Data';
			if( $this->core->wp_filesystem->exists( $app_data ) ) {
				$dirs[] = $app_data;
			}
		}

		return $dirs;
	}

	/**
	 * Set backup directory.
	 *
	 * @since 1.5.1
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param  string $backup_directory_path The backup directory path to be set/configured.
	 * @param  bool   $display_notices       Whether or not to display admin notices.  Default: true.
	 * @return bool
	 */
	public function set( $backup_directory_path = '', $display_notices = true ) {
		$iis_modify = __( 'Users on IIS servers may also need to have modify permissions added to the directory.' );

		$cannot_create_dir = __( 'We were unable to create the following backup directory:<br />
			%1$s<br />
			If you are sure this is the directory you wish to use, please manually create the directory and resave your settings.
			If this directory already exists, please ensure it has read / write permissions.
			%2$s',
			'boldgrid-backup'
		);

		$dir_not_writable = __( 'Backup directory "%1$s" (mode %2$s) is not writable.<br />%3$s', 'boldgrid-backup' );

		if ( empty( $backup_directory_path ) ) {
			$dirs = $this->get_backup_directories();

			foreach( $dirs as $dir ) {
				$dir = trailingslashit( $dir );

				$dir_writable = $this->core->test->is_writable( $dir );

				if( $dir_writable ) {
					$backup_directory_path = $dir . DIRECTORY_SEPARATOR . 'boldgrid_backup';
					break;
				}
			}
		}

		if ( empty( $backup_directory_path ) ) {
			return false;
		}

		// Check if the backup directory exists.
		$backup_directory_exists = $this->core->wp_filesystem->exists( $backup_directory_path );

		// If the backup directory does not exist, then attempt to create it.
		if ( ! $backup_directory_exists ) {
			$backup_directory_created = $this->core->wp_filesystem->mkdir( $backup_directory_path, 0700 );

			// If mkdir failed, then notify and abort.
			if ( ! $backup_directory_created ) {
				if( $display_notices ) {
					$this->errors[] = sprintf( $cannot_create_dir, $backup_directory_path, $iis_modify );
				}
				return false;
			}
		}

		// Check if the backup directory is a directory.
		$backup_directory_isdir = $this->core->wp_filesystem->is_dir( $backup_directory_path );

		// If the backup directory is not a directory, then notify and abort.
		if ( ! $backup_directory_isdir ) {
			if( $display_notices ) {
				$this->errors[] = sprintf( esc_html__( 'Backup directory "%s" is not a directory.', 'boldgrid-backup' ), $backup_directory_path );
			}
			return false;
		}

		// If the backup directory is not writable, then notify and abort.
		if ( ! $this->core->wp_filesystem->is_writable( $backup_directory_path ) ) {
			// Get the mode of the directory.
			$backup_directory_mode = $this->core->wp_filesystem->getchmod( $backup_directory_path );
			if( $display_errors ) {
				$this->errors[] = sprintf( $dir_not_writable, $backup_directory_path, $backup_directory_mode, $iis_modify );
			}
			return false;
		}

		// Record the backup directory path.
		$this->backup_directory = $backup_directory_path;

		// Return success.
		return true;
	}
}
