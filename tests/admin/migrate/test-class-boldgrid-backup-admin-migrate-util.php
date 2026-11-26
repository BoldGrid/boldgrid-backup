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

	/**
	 * Stub wp_remote_get responses for get_site_rest_url.
	 *
	 * @param array $header_map Headers from getAll().
	 */
	private function mock_site_rest_url_headers( $header_map ) {
		$headers = new class( $header_map ) {
			private $all;

			public function __construct( $all ) {
				$this->all = $all;
			}

			public function getAll() {
				return $this->all;
			}
		};

		add_filter(
			'pre_http_request',
			function () use ( $headers ) {
				return array(
					'headers'  => $headers,
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);
	}

	public function test_get_site_rest_url_from_link_header() {
		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->mock_site_rest_url_headers(
			array(
				'link' => '<https://example.com/wp-json/>; rel="https://api.w.org/"',
			)
		);

		$result = $util->get_site_rest_url( 'https://example.com' );

		$this->assertSame( 'https://example.com/wp-json/', $result );
	}

	public function test_get_site_rest_url_missing_api_link_returns_wp_error() {
		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->mock_site_rest_url_headers(
			array(
				'link' => '<https://example.com/>; rel="preconnect"',
			)
		);

		$result = $util->get_site_rest_url( 'https://example.com' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'rest_error', $result->get_error_code() );
	}

	public function test_get_site_rest_url_malformed_api_link_returns_wp_error() {
		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->mock_site_rest_url_headers(
			array(
				'link' => 'rel="https://api.w.org/"',
			)
		);

		$result = $util->get_site_rest_url( 'https://example.com' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'rest_error', $result->get_error_code() );
	}

	public function test_direct_transfer_rejects_subscriber() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertFalse( $util->user_can_direct_transfer() );
	}

	public function test_direct_transfer_multisite_requires_super_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite capability check.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertFalse( $util->user_can_direct_transfer() );

		grant_super_admin( $user_id );
		$this->assertTrue( $util->user_can_direct_transfer() );
		revoke_super_admin( $user_id );
	}

	public function test_direct_transfer_allows_site_administrator() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site capability check.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->assertTrue( $util->user_can_direct_transfer() );
	}

	public function test_path_belongs_to_transfer() {
		$transfer_id    = 'abc123';
		$saved_settings = get_option( 'boldgrid_backup_settings' );
		$base           = wp_tempnam( 'bgbkup-transfer' );
		unlink( $base );
		mkdir( $base, 0700, true );

		$owned = $base . '/example-com/' . $transfer_id . '/dump.sql';
		mkdir( dirname( $owned ), 0700, true );
		file_put_contents( $owned, 'dump' );

		$chunk = $base . '/temp-file-chunks/' . $transfer_id . '/wp-content/file.txt.part-0';
		mkdir( dirname( $chunk ), 0700, true );
		file_put_contents( $chunk, 'chunk' );

		$other = $base . '/example-com/other/dump.sql';
		mkdir( dirname( $other ), 0700, true );
		file_put_contents( $other, 'other' );

		$shallow = $base . '/' . $transfer_id . '/dump.sql';
		mkdir( dirname( $shallow ), 0700, true );
		file_put_contents( $shallow, 'shallow' );

		$deep = $base . '/example-com/nested/' . $transfer_id . '/dump.sql';
		mkdir( dirname( $deep ), 0700, true );
		file_put_contents( $deep, 'deep' );

		$named_file = $base . '/other-dest/' . $transfer_id;
		mkdir( dirname( $named_file ), 0700, true );
		file_put_contents( $named_file, 'name' );

		update_option(
			'boldgrid_backup_settings',
			array(
				'backup_directory' => $base,
			)
		);

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );

		$outside = dirname( $base ) . '/outside.sql';
		file_put_contents( $outside, 'nope' );

		$this->assertTrue( $util->path_belongs_to_transfer( $owned, $transfer_id ) );
		$this->assertTrue( $util->path_belongs_to_transfer( $chunk, $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $other, $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $owned, '../' ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $outside, $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( dirname( $owned ), $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $shallow, $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $deep, $transfer_id ) );
		$this->assertFalse( $util->path_belongs_to_transfer( $named_file, $transfer_id ) );

		unlink( $outside );
		$this->remove_tree( $base );
		update_option( 'boldgrid_backup_settings', $saved_settings );
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
		$util                 = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$this->test_file_path =  ABSPATH . 'temp-file.txt'; // Create a temporary file in /tmp
		$util->create_dirpath( $this->test_file_path );

		$relative_path = 'wp-content/uploads/temp-file.txt';

		$util = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );

		// Create the large file to be split
		$large_file_size = 1024 * 1024 * 5; // 5MB
		$file_handle     = fopen( $this->test_file_path, 'wb' );
		fwrite( $file_handle, str_repeat( 'Hello World!', (int) ( $large_file_size / 100 ) ) );
		fclose( $file_handle );

		// Act
		$this->chunk_paths = $util->split_large_file( $transfer_id, $this->test_file_path, $relative_path, 1024 * 1024, 'transferring-large-files' ); // Test with a valid max upload size

		// Assert
		$this->assertCount( 6, $this->chunk_paths ); // This should be split into 6 chunks. 5 full and 1 partial.
	}

	public function test_split_large_file_keeps_chunks_under_sanitized_id() {
		$saved_settings = get_option( 'boldgrid_backup_settings' );
		$base           = wp_tempnam( 'bgbkup-chunks' );
		unlink( $base );
		mkdir( $base, 0700, true );

		update_option(
			'boldgrid_backup_settings',
			array(
				'backup_directory' => $base,
			)
		);

		$source = $base . '/source.txt';
		file_put_contents( $source, 'hello world' );

		$util   = new Boldgrid_Backup_Admin_Migrate_Util( $this->migrate_core );
		$raw_id = 'abc123/../../outside';
		$paths  = $util->split_large_file( $raw_id, $source, 'wp-content/source.txt', 100, 'splitting-large-files' );

		$expected_dir = wp_normalize_path( $base . '/temp-file-chunks/abc123outside' );
		$this->assertNotEmpty( $paths );
		foreach ( $paths as $path ) {
			$real = realpath( $path );
			$this->assertNotFalse( $real );
			$this->assertStringStartsWith( trailingslashit( $expected_dir ), wp_normalize_path( $real ) );
		}
		$this->assertFalse( is_dir( $base . '/outside' ) );
		$this->assertFalse( is_dir( $base . '/temp-file-chunks/outside' ) );
		$this->assertFalse( is_dir( dirname( $base ) . '/outside' ) );

		$empty = $util->split_large_file( '../', $source, 'wp-content/source.txt', 100, 'splitting-large-files' );
		$this->assertSame( array(), $empty );

		$this->remove_tree( $base );
		update_option( 'boldgrid_backup_settings', $saved_settings );
	}

	/**
	 * Remove a temporary directory tree created by a test.
	 *
	 * @param string $dir Directory to remove.
	 */
	private function remove_tree( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( $item->isDir() ) {
				rmdir( $item->getPathname() );
			} else {
				unlink( $item->getPathname() );
			}
		}

		rmdir( $dir );
	}

	protected function tearDown(): void {
		remove_all_filters( 'pre_http_request' );

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