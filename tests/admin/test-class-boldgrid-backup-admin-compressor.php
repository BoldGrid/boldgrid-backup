<?php
/**
 * File: test-class-boldgrid-backup-admin-compressor.php
 *
 * @link https://www.boldgrid.com
 * @since 1.13.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Compressor
 *
 * @since 1.6.0
 */
class Test_Boldgrid_Backup_Admin_Compressor extends WP_UnitTestCase {
	private
		$core,
		$pcl_zip,
		$php_zip,
		$system_zip;

	/**
	 * Setup.
	 *
	 * @since 1.13.0
	 */
	public function setUp() {
		if ( ! defined( 'BOLDGRID_BACKUP_VERSION' ) ) {
			define( 'BOLDGRID_BACKUP_VERSION', '1.12.7' );
		}

		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->pcl_zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$this->php_zip = new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this->core );

		$this->system_zip = new Boldgrid_Backup_Admin_Compressor_System_Zip( $this->core );
	}

	/**
	 * Test archive_files.
	 *
	 * @since 1.13.0
	 */
	public function test_archive_files() {
		$compressors = [
			'pcl_zip'    => [],
			'php_zip'    => [],
			'system_zip' => [],
		];

		// First, create a backup using each compressor.
		foreach ( $compressors as $compressor => $compressor_data ) {
			update_option( 'boldgrid_backup_settings', [ 'compressor' => $compressor ] );

			$compressors[ $compressor ] = $this->core->archive_files( true );

			// Ensure the proper compressor was used.
			$this->assertTrue( $compressors[ $compressor ]['compressor'] === $compressor );
		}

		// Create a directory where we'll do our testing.
		$testing_dir = $this->core->backup_dir->get() . '/test_archive_files';
		$this->core->execute_command( 'rm -rf ' . $testing_dir . '; mkdir ' . $testing_dir );

		/*
		 * Extract each backup into its own directory.
		 *
		 * For example:
		 * * backup_dir/test_archive_files/pcl_zip
		 * * backup_dir/test_archive_files/php_zip
		 * * backup_dir/test_archive_files/system_zip
		 */
		foreach ( $compressors as $compressor => $info ) {
			$compressor_dir                             = $testing_dir . '/' . $compressor;
			$compressors[ $compressor ]['extracted_to'] = $compressor_dir;

			// Make a directory for this compressor.
			$this->core->execute_command( 'mkdir ' . $compressor_dir );

			// Copy our zip there.
			$this->core->execute_command( 'cp ' . $info['filepath'] . ' ' . $compressor_dir . '/' );

			// Extract the backup.
			$this->core->execute_command( 'cd ' . $compressor_dir . '; unzip ' . basename( $info['filepath'] ) );

			// Delete the backup.
			$this->core->execute_command( 'rm -f ' . $compressor_dir . '/' . basename( $info['filepath'] ) );
		}

		/*
		 * Run a diff and make sure each backup contains the same files.
		 *
		 * By default, the diff should only have 4 items:
		 * [0] => Only in pcl_zip: boldgrid-backup-example.org-213c1637-20200204-163543.log
		 * [1] => Only in php_zip: boldgrid-backup-example.org-213c1637-20200204-163545.log
		 * [2] => Only in pcl_zip: bradm_wp_test.20200204-163542.sql
		 * [3] => Only in php_zip: bradm_wp_test.20200204-163545.sql
		 */
		// Compare pcl_zip folder and php_zip folder.
		$output = shell_exec( 'diff -rq ' . $compressors['pcl_zip']['extracted_to'] . ' ' . $compressors['php_zip']['extracted_to'] ); // phpcs:ignore
		$pclzip_phpzip_diff = explode( PHP_EOL, trim( $output ) );
		$this->assertTrue( 4 === count( $pclzip_phpzip_diff ) );

		// Compare pcl_zip folder and system_zip folder.
		$output = shell_exec( 'diff -rq ' . $compressors['pcl_zip']['extracted_to'] . ' ' . $compressors['system_zip']['extracted_to'] ); // phpcs:ignore
		$pclzip_systemzip_diff = explode( PHP_EOL, trim( $output ) );
		$this->assertTrue( 4 === count( $pclzip_systemzip_diff ) );

		/*
		 * To make sure everything above worked as expected, (1) edit a file, (2) add a blank
		 * directory, and (3) make sure these differences are caught by diff.
		 */
		// (1) Append to a file.
		$file = fopen( $compressors['system_zip']['extracted_to'] . '/wp-admin/about.php', 'a') or die( 'Unable to open file!' ); // phpcs:ignore
		fwrite( $file, "\n" . 'This is a test' ); // phpcs:ignore
		fclose( $file ); // phpcs:ignore
		// (2) Create a dummy folder.
		mkdir( $compressors['system_zip']['extracted_to'] . '/test-folder' );
		// (3) Review the diff.
		$output = shell_exec( 'diff -rq ' . $compressors['pcl_zip']['extracted_to'] . ' ' . $compressors['system_zip']['extracted_to'] ); // phpcs:ignore
		$pclzip_systemzip_diff = explode( PHP_EOL, trim( $output ) );
		$this->assertTrue( 6 === count( $pclzip_systemzip_diff ) );
	}

	/**
	 * Test is_available.
	 *
	 * @since 1.13.0
	 */
	public function test_is_available() {
		$this->assertTrue( $this->pcl_zip->is_available() );

		$this->assertTrue( $this->php_zip->is_available() );

		$this->assertTrue( $this->system_zip->is_available() );
	}

	/**
	 * Test is_default.
	 *
	 * @since 1.13.0
	 */
	public function test_is_default() {
		// Either pcl_zip or php_zip will be default.
		$this->assertTrue( $this->pcl_zip->is_default() || $this->php_zip->is_default() );

		// system_zip is not default.
		$this->assertFalse( $this->system_zip->is_default() );
	}

	/**
	 * Test is_saved_compressor.
	 *
	 * @since 1.13.0
	 */
	public function test_is_saved_compressor() {
		// When there is no saved compressor, all compressors should return false.
		update_option( 'boldgrid_backup_settings', [] );
		$this->assertFalse( $this->pcl_zip->is_saved_compressor() );
		$this->assertFalse( $this->php_zip->is_saved_compressor() );
		$this->assertFalse( $this->system_zip->is_saved_compressor() );

		// When pcl_zip is the saved compressor.
		$settings = [ 'compressor' => 'pcl_zip' ];
		update_option( 'boldgrid_backup_settings', $settings );
		$this->assertTrue( $this->pcl_zip->is_saved_compressor() );
		$this->assertFalse( $this->php_zip->is_saved_compressor() );
		$this->assertFalse( $this->system_zip->is_saved_compressor() );
	}
}
