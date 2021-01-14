<?php
/**
 * Factory class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archive;

/**
 * Class: Factory
 *
 * @since SINCEVERSION
 */
class Factory {
	/**
	 *
	 */
	public static function run_by_filename( $filename ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// Make sure the .zip exists.
		if ( ! $core->backup_dir->exists( $filename ) ) {
			error_log( 'filename not exist' );
			return false;
		}

		// Make sure the folder exists.
		$dir = $core->backup_dir->get_path_to( wp_basename( $filename, '.zip' ) );
		if ( ! $core->wp_filesystem->exists( $dir ) ) {
			error_log( 'dirname not exist = ' . $dir );
			return false;
		}

		$archive = new \Boldgrid\Backup\V2\Archive\Archive();
		$archive->set_dir( $dir );
		$archive->set_filename( $filename );

		return $archive;
	}
}
