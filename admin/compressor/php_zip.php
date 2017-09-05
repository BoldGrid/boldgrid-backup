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
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressor_Php_Zip extends Boldgrid_Backup_Admin_Compressor {

	/**
	 * An instance of ZipArchive.
	 *
	 * @since 1.5.1
	 * @var   ZipArchive
	 */
	public $zip;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		parent::__construct( $core );
	}

	/**
	 * Add all directories to our zip archive.
	 *
	 * Empty directories are not naturally added to our archive, this method
	 * adds them.
	 *
	 * @since 1.5.1
	 *
	 * @param string $dir The directory to scan for folders.
	 */
	public function add_dirs( $dir ) {
		$dir_list = $this->wp_filesystem->dirlist( $dir );

		foreach( $dir_list as $name => $data ) {
			if( 'd' !== $data['type'] ) {
				continue;
			}

			$relative_dir = str_replace( ABSPATH, '', $dir );

			$dir_to_add = empty( $relative_dir ) ? $name : $relative_dir . '/' . $name;

			// Do not add node_modules. @todo Allow for more sophisitcated exclusions.
			if( false !== strpos( $dir_to_add, '/node_modules/' ) ) {
				continue;
			}

			$this->zip->addEmptyDir( $dir_to_add );
			$this->add_dirs( trailingslashit( $dir ) . $name );
		}
	}

	/**
	 * Archive files.
	 *
	 * @since 1.5.1
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
		$info['filepath'] = $this->core->generate_archive_path( 'zip' );

		if( $info['dryrun'] ) {
			$info['total_size'] += $this->core->filelist->get_total_size( $filelist );
			return true;
		}

		$this->zip = new ZipArchive();

		$status = $this->zip->open( $info['filepath'], ZipArchive::CREATE );

		if ( ! $status ) {
			return array(
				'error' => 'Cannot create ZIP archive file "' . $info['filepath'] . '".',
				'error_code' => $status,
				'error_message' => Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ),
			);
		}

		$this->add_dirs( ABSPATH );

		foreach ( $filelist as $fileinfo ) {
			$this->zip->addFile( $fileinfo[0], $fileinfo[1] );
			$info['total_size'] += $fileinfo[2];
		}

		if ( ! $this->zip->close() ) {
			return array(
				'error' => 'Cannot save ZIP archive file "' . $info['filepath'] . '".',
			);
		}

		return true;
	}
}
