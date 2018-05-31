<?php
/**
 * File: class-boldgrid-backup-admin-home-dir.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Home_Dir
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Home_Dir {
	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

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
	 * Get home dir used to calculate disk space.
	 *
	 * We'll assume that the user's home dir is the appropriate directory to use
	 * when calculating available disk space. If this dir has permissions that
	 * make it difficult to calculate disk space, such as
	 * C:\WINDOWS\system32\config\systemprofile then try a few other dirs.
	 *
	 * @since 1.5.1
	 */
	public function get_for_disk() {
		$possible_dirs[] = $this->core->config->get_home_directory();
		$possible_dirs[] = ABSPATH;

		foreach ( $possible_dirs as $dir ) {

			$dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $dir );

			if ( ! empty( $dir ) && $this->core->wp_filesystem->is_dir( $dir ) && $this->core->wp_filesystem->is_readable( $dir ) ) {
				return $dir;
			}
		}

		return false;
	}
}
