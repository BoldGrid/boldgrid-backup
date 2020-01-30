<?php
/**
 * File: class-boldgrid-backup-admin-compressor-system-zip-test.php
 *
 * System Zip Compressor Tester.
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/compressor
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Compressor_System_Zip_Test
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Compressor_System_Zip_Test {
	/**
	 * An instance of core.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An error message.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $error;

	/**
	 * An array of files that will make up the self::filelist_filepath file.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $filelist = [];

	/**
	 * A filepath to the file containing a list of files we will zip.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $filelist_filepath;

	/**
	 * An array of files and folders that we will create.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $items;

	/**
	 * A path to a directory we will be creating, filling with files, and zipping.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $test_dir;

	/**
	 * A filepath to the zip we will be creating.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $zip_filepath;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;

		$this->items = [
			[
				'type' => 'f',
				'name' => 'file.txt',
			],
			[
				'type' => 'd',
				'name' => 'folder-empty',
			],
			[
				'type' => 'd',
				'name' => 'folder-1',
			],
			[
				'type' => 'f',
				'name' => 'folder-1/file-1.txt',
			],
			[
				'type' => 'd',
				'name' => 'folder-1/folder-1a',
			],
			[
				'type' => 'd',
				'name' => 'folder-1/folder-1a/folder-1a.txt',
			],
		];

		$this->zip_filepath      = $this->core->backup_dir->get_path_to( $this->core->test->test_prefix . 'system-zip-test.zip' );
		$this->filelist_filepath = $this->core->backup_dir->get_path_to( $this->core->test->test_prefix . 'system-zip-filelist.txt' );
		$this->test_dir          = $this->core->backup_dir->get_path_to( $this->core->test->test_prefix . 'system-zip-test' );
	}

	/**
	 * Create and setup our test directory.
	 *
	 * We will be zipping up this directory.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	private function test_dir_create() {
		// Create our test directory.
		if ( ! $this->core->wp_filesystem->mkdir( $this->test_dir ) ) {
			$this->error = sprintf(
				// translators: 1 The name of a folder that could not be created.
				esc_html__( 'Unable to create test directory: %1$s', 'boldgrid-backup' ),
				$this->test_dir
			);

			return false;
		}

		// Create all the files. Abort if one could not be.
		foreach ( $this->items as $item ) {
			$created = false;

			switch ( $item['type'] ) {
				case 'd':
					$created = $this->core->wp_filesystem->mkdir( $this->test_dir . DIRECTORY_SEPARATOR . $item['name'] );
					break;
				case 'f':
					$created = $this->core->wp_filesystem->touch( $this->test_dir . DIRECTORY_SEPARATOR . $item['name'] );
					break;
			}

			// Add this item to our filelist (a .txt file containing a list of files to backup).
			$this->filelist[] = $item['name'];

			// Above if we could not create a file.
			if ( ! $created ) {
				$this->error = sprintf(
					// translators: 1 The name of a file that could not be created.
					esc_html__( 'Unable to create test file: %1$s', 'boldgrid-backup' ),
					$item['name']
				);

				return false;
			}
		}

		// Save the filelist.
		if ( ! $this->core->wp_filesystem->put_contents( $this->filelist_filepath, implode( PHP_EOL, $this->filelist ) ) ) {
			$this->error = esc_html__( 'Unable to create filelist.txt', 'boldgrid-backup' );

			return false;
		}

		return true;
	}

	/**
	 * Restore our test directory from zip and make sure everything was restored.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	private function test_dir_restore() {
		// Delete the folder we zipped and make sure it's gone.
		if ( ! $this->core->wp_filesystem->delete( $this->test_dir, true ) ) {
			$this->error = esc_html__( 'Unable to delete test directory.', 'boldgrid-backup' );
			return false;
		}

		// Create our test directory again.
		if ( ! $this->core->wp_filesystem->mkdir( $this->test_dir ) ) {
			$this->error = esc_html__( 'Unable to recreate test directory.', 'boldgrid-backup' );
			return false;
		}

		// Move our zip file to our test directory.
		if ( ! $this->core->wp_filesystem->move( $this->zip_filepath, $this->test_dir . DIRECTORY_SEPARATOR . basename( $this->zip_filepath ) ) ) {
			$this->error = esc_html__( 'Unable to move zip to test directory.', 'boldgrid-backup' );
			return false;
		}

		// Unzip.
		$this->core->execute_command( 'cd ' . $this->test_dir . '; unzip ' . basename( $this->zip_filepath ) );
		foreach ( $this->items as $item ) {
			$path = $this->test_dir . DIRECTORY_SEPARATOR . $item['name'];

			if ( ! $this->core->wp_filesystem->exists( $path ) ) {
				$this->error = esc_html__( 'Unable to restore all files and folders.', 'boldgrid-backup' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Zip up our test directory.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	private function test_dir_zip() {
		// Zip everything up and make sure it worked.
		$this->core->execute_command( 'cd ' . $this->test_dir . '; zip ' . $this->zip_filepath . ' -@ < ' . $this->filelist_filepath );
		if ( ! $this->core->wp_filesystem->exists( $this->zip_filepath ) ) {
			$this->error = esc_html__( 'Unable to create zip file.', 'boldgrid-backup' );
			return false;
		}

		return true;
	}

	/**
	 * Determine if system_zip is working as expected.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	public function run() {
		// Before and after running the test, delete test files created.
		$this->core->test->delete_test_files( $this->core->backup_dir->get() );

		// Create our test directory.
		if ( ! $this->test_dir_create() ) {
			return false;
		}

		// Zip up our test directory.
		if ( ! $this->test_dir_zip() ) {
			return false;
		}

		// Restore our test directory.
		if ( ! $this->test_dir_restore() ) {
			return false;
		}

		$this->core->test->delete_test_files( $this->core->backup_dir->get() );

		return true;
	}
}
