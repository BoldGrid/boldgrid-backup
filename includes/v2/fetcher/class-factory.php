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
	public static function run( $id = null ) {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		// Create a 16 digit id for this fetcher process.
		if ( empty( $id ) ) {
			$id = substr( md5( time() ), -16 );
		}

		/*
		 * We need to define the working directory for this fetcher, but we don't know the backup's
		 * directory yet. We'll create a temporary one now and fix it later.
		 */
		$tmp_dir = $core->backup_dir->get_path_to( 'fetcher_' . $id );

		$fetcher = new \Boldgrid\Backup\V2\Fetcher\Fetcher( 'fetcher', false, $tmp_dir );

		return $fetcher;
	}
}
