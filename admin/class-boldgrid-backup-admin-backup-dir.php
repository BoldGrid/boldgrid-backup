<?php
/**
 * File: class-boldgrid-backup-admin-backup-dir.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @author     BoldGrid.com <wpb@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

/**
 * Class: Boldgrid_Backup_Admin_Backup_Dir
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
	 * The backup directory with the absolute path removed.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    string
	 */
	public $without_abspath;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Determine if exec can write to our backup directory.
	 *
	 * @since 1.6.5
	 *
	 * @return bool
	 */
	public function can_exec_write() {
		$backup_dir = $this->get();

		return $this->core->test->can_exec_write( $backup_dir );
	}

	/**
	 * Create our backup directory and necessary files.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $backup_dir Backup directory path.
	 * @return mixed False on failure, trailingslashed $backup_dir on success.
	 */
	public function create( $backup_dir ) {
		$check_permissions = __( 'Please ensure your backup directory exists and has the proper read, write, and modify permissions.', 'boldgrid-backup' );

		// translators: 1: File path, 2: Informational message above ($check_permissions).
		$cannot_create = __( 'Unable to create necessary file: %1$s<br />%2$s', 'boldgrid-backup' );

		// translators: 1: File path, 2: Informational message above ($check_permissions).
		$cannot_write = __( 'Unable to write to necessary file: %1$s<br />%2$s', 'boldgrid-backup' );

		$backup_dir = untrailingslashit( $backup_dir );

		$logs_dir = $this->get_logs_dir( $backup_dir );

		$htaccess_path   = $backup_dir . DIRECTORY_SEPARATOR . '.htaccess';
		$index_html_path = $backup_dir . DIRECTORY_SEPARATOR . 'index.html';
		$index_php_path  = $backup_dir . DIRECTORY_SEPARATOR . 'index.php';

		$files = array(
			array(
				'type'  => 'dir',
				'path'  => $backup_dir,
				'chmod' => 0700,
			),
			array(
				'type'  => 'dir',
				'path'  => $logs_dir,
				'chmod' => 0700,
			),
			array(
				'type'     => 'file',
				'path'     => $htaccess_path,
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

		/**
		 * Allow other plugins to modify our config.
		 *
		 * @since 1.5.3
		 *
		 * @param array  $files
		 * @param string $backup_dir
		 */
		$files = apply_filters( 'boldgrid_backup_create_dir_config', $files, $backup_dir );

		foreach ( $files as $file ) {
			switch ( $file['type'] ) {
				case 'dir':
					if ( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$chmod   = ! empty( $file['chmod'] ) ? $file['chmod'] : false;
						$created = $this->core->wp_filesystem->mkdir( $file['path'], $chmod );
						if ( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}
					}
					break;
				case 'file':
					if ( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$created = $this->core->wp_filesystem->touch( $file['path'] );
						if ( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}

						if ( ! empty( $file['contents'] ) ) {
							$written = $this->core->wp_filesystem->put_contents( $file['path'], $file['contents'] );
							if ( ! $written ) {
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
	 * Get a directory listing of our backup directory.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public function dirlist() {
		$this->get();

		$files = array();

		if ( $this->backup_directory ) {
			$files = $this->core->wp_filesystem->dirlist( $this->backup_directory );
		}

		return $files;
	}

	/**
	 * Find files in backup directory where filename contains search string.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $search      The search string / needle.
	 * @param  string $search_type The type of search to perform. As you can see based on this method's
	 *                             name, originally we only found files containing a string. As of 1.13.8,
	 *                             you can pass a $search_type of 'end' to find files ending with a
	 *                             string.
	 * @return array
	 */
	public function dirlist_containing( $search, $search_type = 'contain' ) {
		$matches = array();
		$dirlist = $this->dirlist();

		// Find all the files including $search in their filename.
		foreach ( $dirlist as $filename => $filedata ) {
			switch ( $search_type ) {
				case 'end':
					// As of 1.13.8, find files ending with string.
					if ( substr( $filename, -strlen( $search ) ) === $search ) {
						$matches[ $filename ] = $filedata;
					}
					break;
				default:
					// Default behaviour, find files containing.
					if ( false !== strpos( $filename, $search ) ) {
						$matches[ $filename ] = $filedata;
					}
					break;
			}
		}

		return $matches;
	}

	/**
	 * Get and return the backup directory path.
	 *
	 * @since 1.0
	 *
	 * @return string|bool The backup directory path, or FALSE on error.
	 */
	public function get() {

		// If we've already set the backup directory, return it.
		if ( ! empty( $this->backup_directory ) ) {
			return $this->backup_directory;
		}

		// If we have it in the settings, then use it.
		$settings = $this->core->settings->get_settings();
		if ( ! empty( $settings['backup_directory'] ) &&
			$this->core->wp_filesystem->is_writable( $settings['backup_directory'] ) ) {
				$this->set( $settings['backup_directory'] );

			return $this->backup_directory;
		}

		return $this->guess_and_set();
	}

	/**
	 * Get the directory to our logs folder.
	 *
	 * @since 1.12.5
	 *
	 * @param  string $backup_dir Backup directory path.
	 * @return string             The filepath to the logs directory.
	 */
	public function get_logs_dir( $backup_dir = null ) {
		$backup_dir = ! empty( $backup_dir ) ? $backup_dir : $this->get();

		$logs_dir = '';

		// When looking for an existing logs dir, we'll be looking for a dir beginning with this prefix.
		$prefix = 'logs-' . $this->core->get_backup_identifier() . '-';

		/*
		 * Check to see if we already have a logs directory created.
		 *
		 * We'll do this by getting a dir list of the backup directory, and then looking for a folder
		 * named in this format: logs-[BACKUP IDENTIFIER]-[16 CHARS]
		 */

		// Get directory listing of backup directory.
		$dirlist = $this->core->wp_filesystem->dirlist( $backup_dir );
		$dirlist = is_array( $dirlist ) ? $dirlist : array();

		// Look for a logs directory.
		foreach ( $dirlist as $file ) {
			if ( 'd' !== $file['type'] ) {
				continue;
			}

			preg_match( '/^' . $prefix . '[a-zA-Z0-9]{16}$/', $file['name'], $matches );

			if ( ! empty( $matches ) ) {
				$logs_dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_dir ) . $matches[0];
				break;
			}
		}

		/*
		 * If we need to, create our log directory.
		 *
		 * We're using the 16 random characters at the end of the folder name to stay consistent with
		 * Total Upkeep Premium.
		 *
		 * @link https://github.com/BoldGrid/boldgrid-backup-premium/blob/ea33c7fc1b9a184d17ee50b2e61e665967595e85/admin/class-boldgrid-backup-premium-admin-historical.php#L165-L175
		 */
		if ( empty( $logs_dir ) ) {
			$logs_dir = $backup_dir . DIRECTORY_SEPARATOR . $prefix . substr( md5( time() ), -16 );
		}

		return $logs_dir;
	}

	/**
	 * Get an array of possible backup directories.
	 *
	 * @since  1.5.1
	 * @return array
	 */
	public function get_possible_dirs() {
		$dirs = array();

		// Standard value, the user's home directory.
		$dirs[] = $this->core->config->get_home_directory();

		if ( $this->core->test->is_windows() ) {
			// Example: "C:\Users\user\AppData\Local".
			$dirs[] = $this->core->config->get_home_directory() . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local';

			if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
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
				$dirs[]   = str_replace( '\\\\', '\\', $app_data );
			}
		}

		// As a last resort, we will store backups in the /wp-content folder.
		$dirs[] = WP_CONTENT_DIR;

		return $dirs;
	}

	/**
	 * Get the full path to a file in the backup dir.
	 *
	 * Returns backup_dir/$file.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $file Filename.
	 * @return string
	 */
	public function get_path_to( $file ) {
		$backup_dir = $this->get();
		return Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_dir ) . $file;
	}

	/**
	 * Generate a random string of characters for a directory suffix.
	 *
	 * @since 1.11.7
	 *
	 * @return string
	 */
	public function generate_suffix() {
		$suffix = '';
		$chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		for ( $i = 0; $i < 12; $i++ ) {
			$suffix .= $chars[ mt_rand( 0, strlen( $chars ) - 1 ) ];
		}

		return $suffix;
	}

	/**
	 * Set our backup directory.
	 *
	 * Based on the environment, determine where best backup dir should be and
	 * then create it.
	 *
	 * @since 1.5.2
	 *
	 * @see self::generate_suffix()
	 *
	 * @return mixed Backup directory on success, false on failure.
	 */
	public function guess_and_set() {
		$possible_dirs = $this->get_possible_dirs();

		foreach ( $possible_dirs as $possible_dir ) {

			$possible_dir = untrailingslashit( $possible_dir );

			// Ensure /parent_directory exists.
			if ( ! $this->core->wp_filesystem->exists( $possible_dir ) ) {
				continue;
			}

			/*
			 * Create the directory and all applicable files needed within for security,
			 * such as .htaccess / etc.
			 *
			 * If directory is the wp-content directory, add additional random characters so that
			 * the backup directory is not simply wp-content/boldgrid_backup.
			 */
			$append           = WP_CONTENT_DIR !== $possible_dir ? '' : '_' . $this->generate_suffix();
			$possible_dir    .= DIRECTORY_SEPARATOR . 'boldgrid_backup' . $append;
			$backup_directory = $this->create( $possible_dir );

			if ( ! $backup_directory ) {
				continue;
			}

			// Validate read/write/modify/ect. permissions of directory.
			$valid = $this->is_valid( $backup_directory );
			if ( ! $valid ) {
				continue;
			}

			// If we've gotten this far, we've got our new backup directory.
			break;
		}

		if ( empty( $backup_directory ) ) {
			return false;
		}

		$this->set( $backup_directory );

		$settings = $this->core->settings->get_settings();

		$settings['backup_directory'] = $backup_directory;

		$this->core->settings->save( $settings );

		return $this->backup_directory;
	}

	/**
	 * Determine if a file is in the backup directory.
	 *
	 * Pass in a filepath (relative to ABSPATH) and this method will determine
	 * if it's within the backup directory.
	 *
	 * Example $this->without_abspath:
	 * * wp-content/boldgrid-backup/
	 *
	 * Example $file(s):
	 * * (no)  .htaccess
	 * * (no)  wp-admin/admin.php
	 * * (yes) wp-content/boldgrid-backup/boldgrid-backup-domain-000-000-000.zip
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file Filename.
	 * @param  bool   $use_abspath Bool determining whether or not to use the
	 *                             backup directory in its absolute path.
	 * @return bool
	 */
	public function file_in_dir( $file, $use_abspath = false ) {
		if ( ! $use_abspath ) {
			return false !== strpos( $file, $this->without_abspath );
		} else {
			return false !== strpos( $file, $this->backup_directory );
		}
	}

	/**
	 * Validate backup directory.
	 *
	 * Make sure it exists, it's writable, etc.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $backup_dir Backup directory path.
	 * @return bool
	 */
	public function is_valid( $backup_dir ) {

		if ( empty( $backup_dir ) ) {
			return false;
		}

		$perms = $this->core->test->extensive_dir_test( $backup_dir );

		if ( ! $perms['exists'] ) {
			$this->errors[] = sprintf(
				// translators: 1: Backup directory path.
				__( 'Backup Directory does not exists: %1$s', 'boldgrid-backup' ),
				$backup_dir
			);
		}

		if ( ! $perms['read'] ) {
			$this->errors[] = sprintf(
				// translators: 1: Backup directory path.
				__( 'Backup Directory does not have read permission: %1$s', 'boldgrid-backup' ),
				$backup_dir
			);
		}

		if ( ! $perms['rename'] ) {
			$this->errors[] = sprintf(
				// translators: 1: Backup directory path.
				__(
					'Backup Directory does not have permission to rename files: %1$s',
					'boldgrid-backup'
				),
				$backup_dir
			);
		}

		if ( ! $perms['delete'] ) {
			$this->errors[] = sprintf(
				// translators: 1: Backup directory path.
				__(
					'Backup Directory does not have permission to delete files: %1$s',
					'boldgrid-backup'
				),
				$backup_dir
			);
		}

		if ( ! $perms['dirlist'] ) {
			$this->errors[] = sprintf(
				// translators: 1: Backup directory path.
				__(
					'Backup Directory does not have permission to retrieve directory listing: %1$s',
					'boldgrid-backup'
				),
				$backup_dir
			);
		}

		/*
		 * Do not allow the ABSPATH (/home/user/wordpress) to be within the
		 * backup directory (/home/user).
		 *
		 * In the above example, we will create /home/user/.htaccess to prevent
		 * browsing of backups, and this would prevent all traffic to the ABSPATH.
		 */
		$backup_dir     = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_dir );
		$abspath_in_dir = 0 === strpos( ABSPATH, $backup_dir );
		if ( $abspath_in_dir ) {
			$this->errors[] = sprintf(
				// translators: 1: WordPress installation directory path, 2: Backup directory path.
				__(
					'Your <strong>WordPress directory</strong> <em>%1$s</em> cannot be within your <strong>backup directory</strong> %2$s.',
					'boldgrid-backup'
				),
				ABSPATH,
				$backup_dir
			);
		}

		return $perms['exists'] && $perms['read'] && $perms['write'] && $perms['rename'] && $perms['delete'] && $perms['dirlist'] && ! $abspath_in_dir;
	}

	/**
	 * Even in a Windows environment, wp_filesystem->dirlist retrieves paths
	 * with a / instead of \. Fix $without_abspath so we can properly check if
	 * files are in the backup directory.
	 *
	 * @since 1.5.2
	 *
	 * @param string $backup_directory Backup directory.
	 */
	public function set( $backup_directory ) {

		if ( empty( $backup_directory ) ) {
			return false;
		}

		$created = $this->create( $backup_directory );
		if ( ! $created ) {
			return false;
		}

		$this->backup_directory = $backup_directory;

		$this->without_abspath = str_replace( ABSPATH, '', $this->backup_directory );
		$this->without_abspath = str_replace( '\\', '/', $this->without_abspath );
	}
}
