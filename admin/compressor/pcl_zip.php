<?php
/**
 * Pcl Zip Compressor.
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
 * BoldGrid Backup Admin Compressor Pclzip Class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Compressor_Pcl_Zip extends Boldgrid_Backup_Admin_Compressor {

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		if( ! class_exists( 'PclZip' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/class-pclzip.php' );
		}

		parent::__construct( $core );
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
		$cwd = $this->wp_filesystem->cwd();

		$info['filepath'] = $this->core->generate_archive_path( 'zip' );

		$dump_file = $filelist[0][0];

		if( $info['dryrun'] ) {
			$info['total_size'] += $this->core->filelist->get_total_size( $filelist );
			return true;
		}

		$archive = new PclZip( $info['filepath'] );
		if ( 0 === $archive ) {
			return array(
				'error' => sprintf( 'Cannot create ZIP archive file %1$s. %2$s.', $info['filepath'], $archive->errorInfo() ),
			);
		}

		/**
		 * Filter to run before adding a file to the archive.
		 *
		 * While we only pass a small array to the add method, every single file
		 * added to the archive is passed through this method.
		 *
		 * @since 1.5.1
		 *
		 * @link http://www.phpconcept.net/pclzip/user-guide/50
		 *
		 * @param  string $p_event  The identity of the call-back argument
		 * @param  array  $p_header The description of the file that will be added.
		 * @return int    Return 0 to skip adding the file to the archive.
		 */
		function pre_add( $p_event, &$p_header) {
			if( false !== strpos( $p_header['stored_filename'], '/node_modules/') ) {
				return 0;
			}

			return 1;
		}

		/*
		 * Add files to the archive.
		 *
		 * ZipArchive takes the approach of looping through each file and
		 * individually adding it to the archive. When Pcl Zip takes this
		 * approach, the archiving takes too long and never completes. When
		 * adding instead only folders and top level files to the archive, the
		 * archiving completes much faster.
		 */
		$filelist = $this->core->get_filelist_filter();
		$this->wp_filesystem->chdir( ABSPATH );

		$status = $archive->add( $filelist,
			PCLZIP_CB_PRE_ADD, 'pre_add',
			PCLZIP_OPT_REMOVE_PATH, ABSPATH
		);
		if( 0 === $status ) {
			return array(
				'error' => sprintf( 'Cannot add files to ZIP archive file: %1$s', $archive->errorInfo() ),
			);
		}

		$status = $archive->add( $dump_file, PCLZIP_OPT_REMOVE_ALL_PATH );
		if( 0 === $status ) {
			return array(
					'error' => sprintf( 'Cannot add database dump to ZIP archive file: %1$s', $archive->errorInfo() ),
			);
		}

		$this->wp_filesystem->chdir( $cwd );

		return true;
	}
}
