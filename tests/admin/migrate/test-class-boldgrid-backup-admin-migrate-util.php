<?php
/**
 * File: test-class-boldgrid-backup-admin-migrate-util.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Migrate_Util
 *
 * @since 1.11.0
 */
class Test_Boldgrid_Backup_Admin_Migrate_Util extends WP_UnitTestCase {
	public $core;

	public $migrate_core;

	private $test_file_path;

	private $chunk_paths;

	public function set_up() {
		$this->core = apply_filters(
			'boldgrid_backup_get_core',
			new \Boldgrid_Backup_Admin_Core()
		);

		$this->migrate_core = new Boldgrid_Backup_Admin_Migrate( $this->core );
	}

	public function test_construct() {
		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertInstanceOf( 'Boldgrid_Backup_Admin_Migrate_Util', $util );
	}

	public function test_get_transfer_dir() {
		$settings = get_option( 'boldgrid_backup_settings' );
		$util     = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertEquals( $settings['backup_directory'], $util->get_transfer_dir() );
	}

	public function test_url_to_safe_directory_name() {
		$sample_url        = 'https://test.boldgrid.com';
		$expected_dir_name = 'test-boldgrid-com';

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertEquals( $expected_dir_name, $util->url_to_safe_directory_name( $sample_url ) );
	}

	public function test_create_dir_path() {
		$sample_path  = ABSPATH . 'test_create_dir_path/sub_directory/file.php';
		$util         = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$created_path = $util->create_dirpath( $sample_path );
		$this->assertTrue( is_dir( dirname( $sample_path ) ) );
		rmdir( dirname( $sample_path ) );
	}

	public function test_split_large_file() {
		// Arrange
		$transfer_id          = '12345';
		$this->test_file_path =  ABSPATH . '/wp-content/uploads/temp-file.txt'; // Create a temporary file in /tmp
		$relative_path        = 'wp-content/uploads/temp-file.txt';

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );

		// Create the large file to be split
		$large_file_size = 1024 * 1024 * 5; // 5MB
		$file_handle     = fopen( $this->test_file_path, 'wb' );
		fwrite( $file_handle, str_repeat( 'Hello World!', $large_file_size / 100 ) );
		fclose( $file_handle );

		// Act
		$this->chunk_paths = $util->split_large_file( $transfer_id, $this->test_file_path, $relative_path, 1024 * 1024 ); // Test with a valid max upload size

		// Assert
		$this->assertCount( 6, $this->chunk_paths ); // This should be split into 6 chunks. 5 full and 1 partial.
	}

	protected function tearDown(): void {
		// Remove the original test file if it exists
		if ( $this->test_file_path && file_exists( $this->test_file_path ) ) {
			@unlink( $this->test_file_path );
		}

		// Remove each chunk if they exist
		if ( ! empty( $this->chunk_paths ) && is_array( $this->chunk_paths ) ) {
			foreach ( $this->chunk_paths as $path ) {
				if ( file_exists( $path ) ) {
					@unlink( $path );
				}
			}
		}

		// Call parent tearDown to respect the PHPUnit lifecycle
		parent::tearDown();
	}
}