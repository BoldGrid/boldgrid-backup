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
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressor_Pcl_Zip extends Boldgrid_Backup_Admin_Compressor {

	/**
	 * The status of our test result.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    mixed|bool|null
	 */
	public static $test_result = null;

	/**
	 * An array of any errors received while testing.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    array
	 */
	public $test_errors = array();

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
		 * @param  array  $p_header The description of the file that will be
		 *                          added. https://pastebin.com/MTMGwaZ2
		 * @return int    Return 0 to skip adding the file to the archive.
		 */
		function pre_add( $p_event, &$p_header) {

			$in_node_modules = false !== strpos( $p_header['stored_filename'], '/node_modules/');
			$in_backup_directory = apply_filters( 'boldgrid_backup_file_in_dir', $p_header['stored_filename'] );

			if( $in_node_modules || $in_backup_directory ) {
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

	/**
	 * Test the functionality of php_zip.
	 *
	 * @since 1.5
	 *
	 * @param  bool $display_errors
	 * @return bool
	 */
	public function test( $display_errors = true ) {
		if( null !== self::$test_result ) {
			return self::$test_result;
		}

		$backup_dir = $this->core->backup_dir->get();

		// Strings to help with creating test files.
		$test_file_contents = $str = __( 'This is a test file from BoldGrid Backup. You can delete this file.', 'boldgrid-backup' );
		$safe_to_delete = __( 'safe-to-delete', 'boldgrid-backup' );
		$test_zip_file = $this->core->test->test_prefix . '-zip';
		$test_filename = sprintf( '%1$s%2$s-%3$s-%4$s', $backup_dir, $test_zip_file, mt_rand(), $safe_to_delete );
		$zip_filepath = $test_filename . '.zip';
		$random_filename = $test_filename . '.txt';

		$cannot_touch_file = __( 'PclZip test failed. We were unable to create the following test file:<br />
			%1$s.<br />
			Please ensure your backup directory has read, write, and modify permissions.',
			'boldgrid-backup'
		);

		$cannot_put_contents = __( 'PclZip test failed. We were able to create the following test file, but we were unable to modify it. were unable to modify it:<br />
			%1$s<br />
			Please ensure your backup directory has read, write, and modify permissions.',
			'boldgrid-backup'
		);

		$touched = $this->core->wp_filesystem->touch( $random_filename );
		if( ! $touched ) {
			$this->test_errors[] = sprintf( $cannot_touch_file, $random_filename );
			self::$test_result = false;
			return false;
		}

		$contents_put = $this->core->wp_filesystem->put_contents( $random_filename, $test_file_contents );
		if( ! $contents_put ) {
			$this->test_errors[] = sprintf( $cannot_put_contents, $random_filename );
			self::$test_result = false;
			return false;
		}

		$archive = new PclZip( $zip_filepath );
		if ( 0 === $archive ) {
			$this->test_errors[] = sprintf( 'Cannot create ZIP archive file %1$s. %2$s.', $info['filepath'], $archive->errorInfo() );
		}

		$status = $archive->add( $random_filename );
		if( 0 === $status ) {
			$this->test_errors[] = sprintf( 'Cannot add files to PclZip archive file: %1$s', $archive->errorInfo() );
		}

		$this->core->test->delete_test_files( $backup_dir );

		self::$test_result = true;
		return true;
	}
}
