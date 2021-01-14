<?php
/**
 * Restorer Process Factory class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Restorer;

/**
 * Class: Factory
 *
 * @since SINCEVERSION
 */
class Factory {
	/**
	 *
	 */
	public static function run( $backup_id, $id = null ) {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		// This is the id of the backup.
		if ( empty( $backup_id ) ) {
			error_log( 'empty backup id' );
			return false;
		}

		// Create a 16 digit id for this restoration process.
		if ( empty( $id ) ) {
			$id = substr( md5( time() ), -16 );
		}

		$backup_folder_name  = 'boldgrid-backup-' . $core->get_backup_identifier() . '-' . $backup_id;
		$backup_folder_path  = $core->backup_dir->get_path_to( $backup_folder_name );
		$restore_folder_path = $backup_folder_path . '/restore-' . $id;

		if ( ! $core->wp_filesystem->exists( $backup_folder_path ) ) {
			return false;
		}

		$restorer = new \Boldgrid\Backup\V2\Restorer\Restorer( 'restorer', false, $restore_folder_path );

		$restorer->get_data_type( 'step' )->set_key( 'backup_folder_path', $backup_folder_path );

		return $restorer;
	}
}
