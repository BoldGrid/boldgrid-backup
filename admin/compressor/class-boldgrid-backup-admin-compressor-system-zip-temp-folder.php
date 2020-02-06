<?php
/**
 * File: class-boldgrid-backup-admin-compressor-system-zip-temp-folder.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.13.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/compressor
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Compressor_System_Zip_Temp_Folder
 *
 * @since 1.13.0
 */
class Boldgrid_Backup_Admin_Compressor_System_Zip_Temp_Folder {
	/**
	 * An instance of core.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The name of the temporary folder where the compression will take place.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var string
	 */
	private static $name = 'system-zip-temp';

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
	}

	/**
	 * Create the temp folder.
	 *
	 * @since 1.13.0
	 *
	 * @return bool True on success.
	 */
	public function create() {
		return $this->core->wp_filesystem->mkdir( self::get_path() );
	}

	/**
	 * Delete the temp folder.
	 *
	 * @since 1.13.0
	 *
	 * @return bool True on success.
	 */
	public function delete() {
		return $this->core->wp_filesystem->rmdir( self::get_path() );
	}

	/**
	 * Get the path to our temp folder.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public static function get_path() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return $core->backup_dir->get_path_to( self::$name );
	}
}
