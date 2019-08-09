<?php
/**
 * File: test-class-boldgrid-backup-admin-core.php
 *
 * @link  https://www.boldgrid.com
 * @since xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Core
 *
 * @since xxx
 */
class Test_Boldgrid_Backup_Admin_Core extends WP_UnitTestCase {
	/**
	 * Assert that a given dir in an archive has files and folders.
	 *
	 * This is a very generic test, doesn't need to be exact, as the WordPress core files will change
	 * over time.
	 *
	 * For example, we may run this and say:
	 * Make sure wp-admin folder has over 10 files totalling over 10000 bytes, and there's at least
	 * 3 folders.
	 *
	 * @since xxx
	 *
	 * @param string $filepath Path to the zip file.
	 * @param string $dir      The dir within the zip to check.
	 * @param int    $min_file_count The minimum number of files that need to be in the directory.
	 * @param int    $file_file_size The minimum file size of all files in the directory.
	 * @param int    $min_dir_count  The minimum number of folders that need to be in the directory.
	 */
	public function assertDirNotEmpty( $filepath, $dir = '.', $min_file_count, $min_file_size, $min_dir_count ) {
		$abspath    = $this->zip->browse( $filepath, $dir );
		$file_count = 0;
		$file_size  = 0;
		$dir_count  = 0;

		foreach ( $abspath as $file ) {
			if ( $file['folder'] ) {
				$dir_count++;
			} else {
				$file_count++;
				$file_size += $file['size'];
			}
		}

		// Debug. This is how you can see the actual counts / sizes in question.

		phpunit_error_log( array(
			'$dir'        => $dir,
			'$file_count' => $file_count,
			'$file_size'  => $file_size,
			'$dir_count'  => $dir_count,
		) );


		$this->assertTrue( $file_count >= $min_file_count && $file_size >= $min_file_size && $dir_count >= $min_dir_count );
	}

	/**
	 * An instance core.
	 *
	 * @since xxx
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * An instance of pcl_zip.
	 *
	 * @since xxx
	 * @var Boldgrid_Backup_Admin_Compressor_Pcl_Zip
	 */
	public $zip;

	/**
	 * Setup.
	 *
	 * @since xxx
	 */
	public function setUp() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
	}

	/**
	 * Test archive_files.
	 *
	 * @since xxx
	 */
	public function test_archive_files() {
		// Delete our latest_backup variable.
		delete_option( 'boldgrid_backup_latest_backup' );
		$this->assertFalse( get_option( 'boldgrid_backup_latest_backup' ) );

		/*
		 * Basic test.
		 *
		 * This is a generic backup test (IE backup all files and folders and tables).
		 */
		$info = $this->core->archive_files( true );

		// Ensure a backup was made and we have a filepath.
		$this->assertTrue( ! empty( $info['filepath'] ) );

		// Ensure the $info returned matches the data stored in the boldgrid_backup_latest_backup option.
		$this->assertTrue( get_option( 'boldgrid_backup_latest_backup' ) === $info );

		// Ensure we have files and folders (IE they're not empty and the backup process recursed).
		$this->assertDirNotEmpty( $info['filepath'], '.', 10, 10000, 3 );
		$this->assertDirNotEmpty( $info['filepath'], 'wp-includes', 10, 10000, 3 );
		$this->assertDirNotEmpty( $info['filepath'], 'wp-includes/rest-api', 3, 10000, 3 );
		$this->assertDirNotEmpty( $info['filepath'], 'wp-admin', 10, 10000, 3 );

		// Ensure there is exactly 1 .sql in the backup.
		$sqls = $this->zip->get_sqls( $info['filepath'] );
		$this->assertTrue( 1 === count( $sqls ) );

		//phpunit_error_log( $info );
	}
}
