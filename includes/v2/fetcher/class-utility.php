<?php
/**
 * Utility class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Fetcher;

/**
 * Class: Utility
 *
 * @since SINCEVERSION
 */
class Utility {
	/**
	 *
	 */
	public static function get_folder_name( $id ) {
		return 'fetcher_' . $id;
	}

	/**
	 *
	 */
	public static function get_option() {
		return new \Boldgrid\Backup\Option\Option( 'boldgrid_backup_fetcher_data' );
	}

	/**
	 *
	 */
	public static function path_by_id( $backup_id, $fetcher_id ) {
		// Get our fetcher folder name, such as "fetcher_1234".
		$folder_name = self::get_folder_name( $fetcher_id );

		// Get the full path to our backup folder, such as /boldgrid_backup/boldgrid-backup-1234-12345678
		$backup_path = \Boldgrid\Backup\Utility\Virtual_Folder::path_by_id( $backup_id );

		// Return the full path to our fetcher, such as /boldgrid_backup/boldgrid-backup-1234-12345678/fetcher_1234
		return trailingslashit( $backup_path ) . $folder_name;
	}
}
