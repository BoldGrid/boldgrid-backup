<?php
/**
 * Archive Process Factory class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver;

/**
 * Class: Factory
 *
 * @since SINCEVERSION
 */
class Factory {
	/**
	 *
	 */
	public static function run( $id = null ) {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		// Create a 16 digit id.
		if ( empty( $id ) ) {
			$id = substr( md5( time() ), -16 );
		}

		$name = 'boldgrid-backup-' . $core->get_backup_identifier() . '-' . $id;

		$backup_folder_path = $core->backup_dir->get_path_to( $name );

		// Create the zip placeholder.
		$zip_filepath = $core->backup_dir->get_path_to( $name . '.zip' );
		if ( ! $core->wp_filesystem->exists( $zip_filepath ) ) {
			$core->wp_filesystem->touch( $zip_filepath );
		}

		$archiver = new \Boldgrid\Backup\V2\Archiver\Archiver( 'archiver', false, $backup_folder_path );
		$archiver->get_info()->set_key( 'filepath', $zip_filepath );

		return $archiver;
	}
}