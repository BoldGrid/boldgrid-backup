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

namespace Boldgrid\Backup\Archive;

/**
 * Class: Factory
 *
 * A factory for getting an archive of type Boldgrid_Backup_Admin_Archive.
 *
 * @since SINCEVERSION
 */
class Factory {
	/**
	 * Get an archive by filename.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string                        $filename The filename of a backup.
	 * @return Boldgrid_Backup_Admin_Archive
	 */
	public static function get_by_filename( $filename ) {
		$archive = new \Boldgrid_Backup_Admin_Archive();

		$archive->init_by_filename( $filename );

		$archive = self::set_id( $archive );

		return $archive;
	}

	/**
	 * Get a backup by id.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string                        $id The backup id.
	 * @return Boldgrid_Backup_Admin_Archive
	 */
	public static function get_by_id( $id ) {
		$archive = new \Boldgrid_Backup_Admin_Archive();

		// Get the filename of our backup based on id.
		$option     = new Option();
		$option_row = $option->get_by_key( 'id', (int) $id );
		$filename   = ! empty( $option_row['filename'] ) ? $option_row['filename'] : null;

		if ( ! empty( $filename ) ) {
			$archive->init_by_filename( $filename );
		}

		return $archive;
	}

	/**
	 * Give a backup an id.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  Boldgrid_Backup_Admin_Archive $archive An archive.
	 * @return Boldgrid_Backup_Admin_Archive
	 */
	private static function set_id( $archive ) {
		$option = new Option();

		$option_row = $option->get_by_key( 'filename', $archive->filename );

		if ( empty( $option_row ) ) {
			$option_row = [ 'filename' => $archive->filename ];
		}

		if ( isset( $option_row['id'] ) ) {
			$archive->set_id( $option_row['id'] );
		} else {
			$archive->set_id( $option->get_next_id() );

			$option->update_by_filename( $archive->filename, 'id', $archive->get_id() );
		}

		return $archive;
	}
}
