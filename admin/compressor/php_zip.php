<?php
/**
 * PHP Zip Compressor.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Compressor PHP Zip Class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Compressor_Php_Zip {

	/**
	 * An instance of Boldgrid_Backup_Admin_Core.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Core object
	 */
	public $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Archive files.
	 *
	 * @since 1.5.1
	 *
	 * @global $wp_filesystem
	 *
	 * @param array $filelist See Boldgrid_Backup_Admin_Filelist::get_total_size
	 * @param array $info {
	 *     An array of data about the backup archive we are generating.
	 *
	 *     @type string mode       backup
	 *     @type bool   dryrun
	 *     @type string compressor php_zip
	 *     @type ing    filesize   0
	 *     @type bool   save       1
	 *     @type int    total_size 0
	 * }
	 */
	public function archive_files( $filelist, &$info ) {
		global $wp_filesystem;

		$info['filepath'] = $this->core->generate_archive_path( 'zip' );

		if( $info['dryrun'] ) {
			$info['total_size'] += $this->core->filelist->get_total_size( $filelist );
			return true;
		}

		$zip = new ZipArchive();

		$status = $zip->open( $info['filepath'], ZipArchive::CREATE );

		if ( ! $status ) {
			return array(
				'error' => 'Cannot create ZIP archive file "' . $info['filepath'] . '".',
				'error_code' => $status,
				'error_message' => Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ),
			);
		}

		foreach ( $filelist as $fileinfo ) {
			$zip->addFile( $fileinfo[0], $fileinfo[1] );
			$info['total_size'] += $fileinfo[2];
		}

		if ( ! $zip->close() ) {
			return array(
				'error' => 'Cannot save ZIP archive file "' . $info['filepath'] . '".',
			);
		}

		if ( ! $wp_filesystem->exists( $info['filepath'] ) ) {
			return array(
				'error' => 'The archive file "' . $info['filepath'] . '" was not written.',
			);
		}

		$info['lastmodunix'] = $wp_filesystem->mtime( $info['filepath'] );

		return true;
	}
}
