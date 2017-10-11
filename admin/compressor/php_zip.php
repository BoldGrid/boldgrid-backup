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
			return true;
		}

		$this->zip = new ZipArchive();

		$status = $this->zip->open( $info['filepath'], ZipArchive::CREATE );

		if ( ! $status ) {
			return array(
				'error' => 'Cannot open ZIP archive file "' . $info['filepath'] . '".',
				'error_code' => $status,
				'error_message' => Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ),
			);
		}

		$this->add_dirs( ABSPATH );

		foreach ( $filelist as $fileinfo ) {
			$this->zip->addFile( $fileinfo[0], $fileinfo[1] );
		}

		if ( ! $this->zip->close() ) {
			return array(
				'error' => 'Cannot close ZIP archive file "' . $info['filepath'] . '".',
			);
		}

		return true;
	}

	/**
	 * Determine if ZipArchive is available.
	 *
	 * @since 1.5.2
	 */
	public static function is_available() {
		return extension_loaded( 'zip' ) && class_exists( 'ZipArchive' );
	}

	/**
	 * Test the functionality of php_zip.
	 *
	 * @since 1.5
	 *
	 * @return bool
	 */
	public function test() {
		if( null !== self::$test_result ) {
			return self::$test_result;
		}

		$backup_dir = $this->core->backup_dir->get();

		$test_file_contents = $str = __( 'This is a test file from BoldGrid Backup. You can delete this file.', 'boldgrid-backup' );
		$cannot_open_zip = __( 'Unable to create zip file: %1$s', 'boldgrid-backup' );
		$cannot_close_zip = __( 'When testing ZipArchive functionality, we are able to create a zip file and add files to it, but we were unable to close the zip file.<br /><strong>Please be sure the following backup directory has modify permissions</strong>:<br />%1$s', 'boldgrid-backup' );
		$safe_to_delete = __( 'safe-to-delete', 'boldgrid-backup' );
		$test_zip_file = $test_zip_file = $this->core->test->test_prefix . '-zip';
		$test_filename = sprintf( '%1$s%2$s-%3$s-%4$s', $backup_dir, $test_zip_file, mt_rand(), $safe_to_delete );

		$zip_filepath = $test_filename . '.zip';
		$random_filename = $test_filename . '.txt';

		$zip = new ZipArchive();
		$status = $zip->open( $zip_filepath, ZipArchive::CREATE );
		if( ! $status ) {
			$this->test_errors[] = sprintf( $cannot_open_zip, $zip_filepath );
			self::$test_result = false;
			return false;
		}

		$this->core->wp_filesystem->touch( $random_filename );
		$this->core->wp_filesystem->put_contents( $random_filename, $test_file_contents );

		$zip->addFile( $random_filename, 'test.txt' );

		$zip_closed = @$zip->close();

		$this->core->test->delete_test_files( $backup_dir );

		if( ! $zip_closed ) {
			$this->test_errors[] = sprintf( $cannot_close_zip, $backup_dir );
			self::$test_result = false;
			return false;
		}

		self::$test_result = true;
		return true;
	}
}
