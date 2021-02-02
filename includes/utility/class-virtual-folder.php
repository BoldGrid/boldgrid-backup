<?php
/**
 * Utility Remove class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Utility;

/**
 * Class: Remote
 *
 * @since SINCEVERSION
 */
class Virtual_Folder {
	public static $base = 'boldgrid-backup';

	/**
	 *
	 */
	public static function get_by_id( $id ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		return self::$base . '-' . $core->get_backup_identifier() . '-' . $id;
	}

	/**
	 *
	 */
	public static function id_by_folder( $folder ) {
		preg_match( '/boldgrid-backup-(.+)-(.{16})/', $folder, $matches );

		return empty( $matches[2] ) ? false : $matches[2];
	}

	/**
	 *
	 */
	public static function filename_by_folder( $folder ) {
		return $folder . '.zip';
	}

	/**
	 * Pass in a zip filename, either:
	 * # /home/user/boldgrid_backup/boldgrid-backup-1234-abcd.zip
	 * # boldgrid-backup-1234-abcd.zip
	 *
	 * And get the path to the virtual folder:
	 * # /home/user/boldgrid_backup/boldgrid-backup-1234-abcd
	 */
	public static function folder_by_zip( $zip_filename ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$folder_name = wp_basename( $zip_filename, '.zip' );

		return $core->backup_dir->get_path_to( $folder_name );
	}

	/**
	 * Pass a backup id, such as:
	 * # 12345678
	 *
	 * Get
	 * # /home/user/boldgrid_backup/boldgrid-backup-1234-12345678/
	 */
	public static function path_by_id( $id ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$folder_name = self::get_by_id( $id );

		return trailingslashit( $core->backup_dir->get_path_to( $folder_name ) );
	}

	/**
	 * Create empty zip file based on folder.
	 *
	 * If you pass in:
	 * boldgrid-backup-b8ad717e-908dcb169c3c35cb
	 *
	 * This method will create:
	 * /home/user/boldgrid_backup/boldgrid-backup-b8ad717e-908dcb169c3c35cb.zip
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public static function zip_by_folder( $folder ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$filepath = $core->backup_dir->get_path_to( $folder . '.zip' );

		return $core->wp_filesystem->touch( $filepath );
	}
}
