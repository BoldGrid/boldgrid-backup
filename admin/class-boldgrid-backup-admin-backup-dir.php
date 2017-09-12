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
	 * Create our backup directory and necessary files.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $backup_dir
	 * @return string
	 */
	public function create( $backup_dir ) {
		$check_permissions = __( 'Please ensure your backup directory exists and has the proper read, write, and modify permissions.', 'boldgrid-backup' );

		$cannot_create = __( 'Unable to create necessary file: %1$s<br />%2$s', 'boldgrid-backup' );
		$cannot_write = __( 'Unable to write to necessary file: %1$s<br />%2$s', 'boldgrid-backup' );

		if( ! $backup_dir ) {
			$backup_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'boldgrid-backup' . DIRECTORY_SEPARATOR;
		} else {
			$backup_dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_dir );
		}

		$htaccess_path = $backup_dir . '.htaccess';
		$index_html_path = $backup_dir . 'index.html';
		$index_php_path = $backup_dir . 'index.php';

		$files = array(
			array(
				'type' => 'dir',
				'path' => $backup_dir,
			),
			array(
				'type' => 'file',
				'path' => $htaccess_path,
				'contents' => "<IfModule mod_access_compat.c>\nOrder Allow,Deny\nDeny from all\n</IfModule>\nOptions -Indexes\n",
			),
			array(
				'type' => 'file',
				'path' => $index_html_path,
			),
			array(
				'type' => 'file',
				'path' => $index_php_path,
			),
		);

		foreach( $files as $file ) {
			switch( $file['type'] ) {
				case 'dir':
					if( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$created = $this->core->wp_filesystem->mkdir( $file['path'] );
						if( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}
					}
					break;
				case 'file':
					if( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$created = $this->core->wp_filesystem->touch( $file['path'] );
						if( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}

						if( ! empty( $file['contents'] ) ) {
							$written = $this->core->wp_filesystem->put_contents( $file['path'], $file['contents'] );
							if( ! $written ) {
								$this->errors[] = sprintf( $cannot_write, $file['path'], $check_permissions );
								return false;
							}
						}
					}
					break;
			}
		}

		return $backup_dir;
	}

	/**
	 * Get and return the backup directory path.
	 *
	 * @since 1.0
	 *
	 * @return string|bool The backup directory path, or FALSE on error.
	 */
	public function get() {
		if( ! empty( $this->backup_directory ) ) {
			return $this->backup_directory;
		}

		/*
		 * If there is not a valid backup directory stored in the settings, then
		 * we'll need to add / overwrite the value in the settings with a dir
		 * that we create.
		 */
		$overwrite_settings = false;

		$settings = $this->core->settings->get_settings();
		if( ! empty( $settings['backup_directory'] ) ) {
			$backup_directory = $settings['backup_directory'];
		}

		/*
		 * Prior to version 1.5.1, the backup directory was outside of the doc
		 * root. As of 1.5.1, if there is no backup directory set, or the one
		 * set is empty, we'll create a new backup dir within the wp-content dir.
		 */
		if( empty( $backup_directory ) || empty( $this->core->wp_filesystem->dirlist( $backup_directory ) ) ) {
			$dir_created = $this->create();

			if( $dir_created ) {
				$overwrite_settings = true;
				$backup_directory = $dir_created;
			}
		}

		$backup_directory = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_directory );

		if( ! $this->is_valid( $backup_directory ) ) {
			return false;
		}

		if( $overwrite_settings ) {
			$settings['backup_directory'] = $backup_directory;
			update_site_option( 'boldgrid_backup_settings', $settings );
		}

		$this->backup_directory = $backup_directory;
		return $this->backup_directory;
	}

	/**
	 * Validate backup directory.
	 *
	 * Make sure it exists, it's writable, etc.
	 */
	public function is_valid( $backup_dir ) {
		$perms = $this->core->test->extensive_dir_test( $backup_dir );

		if( ! $perms['exists'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not exists: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['read'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not have read permission: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['rename'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not permission to rename files: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['delete'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not permission to delete files: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['dirlist'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not permission to retrieve directory listing: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		return $perms['exists'] && $perms['read'] && $perms['write'] && $perms['rename'] && $perms['delete'] && $perms['dirlist'];
	}
}
