<?php
/**
 * Fetcher Process Factory class.
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
 * Class: Factory
 *
 * @since SINCEVERSION
 */
class Factory {
	/**
	 *
	 */
	private static $id = 'fetcher';

	/**
	 *
	 */
	public static function run() {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		// Create a 16 digit id for this fetcher process.
		$id = substr( md5( time() ), -16 );

		/*
		 * We need to define the working directory for this fetcher, but we don't know the backup's
		 * directory yet. We'll create a temporary one now and fix it later.
		 */
		$folder_name = \Boldgrid\Backup\V2\Fetcher\Utility::get_folder_name( $id );
		$tmp_dir     = $core->backup_dir->get_path_to( $folder_name );

		$fetcher = new \Boldgrid\Backup\V2\Fetcher\Fetcher( self::$id, false, $tmp_dir );

		$fetcher->get_info()->set_key( 'fetcher_id', $id );

		return $fetcher;
	}

	/**
	 *
	 */
	public static function run_by_resumer() {
		// Get our backup id and fetcher id.
		$option     = \Boldgrid\Backup\V2\Fetcher\Utility::get_option();
		$backup_id  = $option->get_key( 'backup_id' );
		$fetcher_id = $option->get_key( 'fetcher_id' );
		if ( empty( $backup_id ) || empty( $fetcher_id ) ) {
			return false;
		}

		// Get the full path to our fetcher directory.
		$path = \Boldgrid\Backup\V2\Fetcher\Utility::path_by_id( $backup_id, $fetcher_id );
		if ( empty( $path ) ) {
			return false;
		}

		return new \Boldgrid\Backup\V2\Fetcher\Fetcher( self::$id, false, $path );
	}
}
