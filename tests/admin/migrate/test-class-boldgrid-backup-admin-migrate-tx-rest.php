<?php
/**
 * File: test-class-boldgrid-backup-admin-migrate-tx-rest.php
 *
 * @link https://www.boldgrid.com
 * @since 1.17.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Migrate_Tx_Rest
 *
 * Sending-side transfer directories and request credentials.
 *
 * @since 1.17.5
 */
class Test_Boldgrid_Backup_Admin_Migrate_Tx_Rest extends WP_UnitTestCase {
	/**
	 * Migrate core.
	 *
	 * @var Boldgrid_Backup_Admin_Migrate
	 */
	private $migrate_core;

	/**
	 * Backup directory used as the transfer root.
	 *
	 * @var string
	 */
	private $backup_dir;

	/**
	 * Original backup settings.
	 *
	 * @var array
	 */
	private $original_settings;

	public function set_up() {
		parent::set_up();

		$this->migrate_core = new Boldgrid_Backup_Admin_Migrate(
			apply_filters( 'boldgrid_backup_get_core', new Boldgrid_Backup_Admin_Core() )
		);

		$this->backup_dir = rtrim( get_temp_dir(), '/' ) . '/bgbkup-tx-' . wp_generate_password( 8, false );
		mkdir( $this->backup_dir, 0700, true );

		$this->original_settings = get_option( 'boldgrid_backup_settings', array() );
		$this->set_backup_directory( $this->backup_dir );

		add_filter( 'send_auth_cookies', '__return_false' );
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		remove_filter( 'send_auth_cookies', '__return_false' );
		update_option( 'boldgrid_backup_settings', $this->original_settings );
		$this->remove_tree( $this->backup_dir );
		$this->remove_tree( dirname( $this->backup_dir ) . '/bgbkup-tx-outside' );
		parent::tear_down();
	}

	public function test_site_dir_uses_backup_directory_host_and_id() {
		$this->assertSame(
			wp_normalize_path( $this->backup_dir ) . '/example-com/xfer1234',
			$this->migrate_core->util->get_transfer_site_dir( 'https://example.com', 'xfer1234' )
		);
		$this->assertSame(
			wp_normalize_path( $this->backup_dir ) . '/temp-file-chunks/xfer1234',
			$this->migrate_core->util->get_transfer_chunk_dir( 'xfer1234' )
		);
	}

	public function test_site_dir_matches_receive_side_staging_dir() {
		$staging = $this->migrate_core->util->get_transfer_staging_dir(
			array(
				'source_site_url' => 'https://example.com',
				'transfer_id'     => 'xfer1234',
			)
		);

		$this->assertSame( $staging, $this->migrate_core->util->get_transfer_site_dir( 'https://example.com', 'xfer1234' ) );
	}

	public function test_relative_backup_directory_resolves_under_abspath() {
		$this->set_backup_directory( 'wp-content/bgbkup-relative' );
		$base = wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/wp-content/bgbkup-relative';

		$this->assertSame( $base . '/example-com/xfer1234', $this->migrate_core->util->get_transfer_site_dir( 'https://example.com', 'xfer1234' ) );
		$this->assertSame( $base . '/temp-file-chunks/xfer1234', $this->migrate_core->util->get_transfer_chunk_dir( 'xfer1234' ) );
	}

	public function test_dirs_reject_parent_segment_in_backup_directory() {
		$util = $this->migrate_core->util;

		foreach ( array( '/var/bgbkup/../outside', 'wp-content/../outside' ) as $backup_directory ) {
			$this->set_backup_directory( $backup_directory );

			$this->assertFalse( $util->get_transfer_base_dir(), $backup_directory );
			$this->assertFalse( $util->get_transfer_site_dir( 'https://example.com', 'xfer1234' ), $backup_directory );
			$this->assertFalse( $util->get_transfer_chunk_dir( 'xfer1234' ), $backup_directory );
		}
	}

	public function test_dirs_reject_unusable_segments() {
		$util = $this->migrate_core->util;

		$this->assertFalse( $util->get_transfer_site_dir( 'not a url', 'xfer1234' ) );
		$this->assertFalse( $util->get_transfer_site_dir( '', 'xfer1234' ) );
		$this->assertFalse( $util->get_transfer_site_dir( 'https://example.com', '' ) );
		$this->assertFalse( $util->get_transfer_site_dir( 'https://example.com', '..' ) );
		$this->assertFalse( $util->get_transfer_site_dir( 'https://example.com', 'a/b' ) );
		$this->assertFalse( $util->get_transfer_chunk_dir( '' ) );
		$this->assertFalse( $util->get_transfer_chunk_dir( '..' ) );
	}

	public function test_split_large_file_returns_nothing_when_backup_directory_is_unusable() {
		$source = $this->backup_dir . '/source.txt';
		file_put_contents( $source, 'hello world' );
		$this->set_backup_directory( $this->backup_dir . '/../bgbkup-tx-outside' );

		$paths = $this->migrate_core->util->split_large_file( 'xfer1234', $source, 'wp-content/source.txt', 100, 'splitting-large-files' );

		$this->assertSame( array(), $paths );
		$this->assertFalse( is_dir( dirname( $this->backup_dir ) . '/bgbkup-tx-outside' ) );
	}

	public function test_path_belongs_to_transfer_rejects_parent_segment_in_backup_directory() {
		$file = $this->backup_dir . '/example-com/xfer1234/dump.sql';
		mkdir( dirname( $file ), 0700, true );
		file_put_contents( $file, 'dump' );

		$this->assertTrue( $this->migrate_core->util->path_belongs_to_transfer( $file, 'xfer1234' ) );

		$this->set_backup_directory( $this->backup_dir . '/example-com/..' );
		$this->assertFalse( $this->migrate_core->util->path_belongs_to_transfer( $file, 'xfer1234' ) );
	}

	public function test_delete_transfer_files_removes_transfer_and_chunk_dirs() {
		$transfer_file = $this->backup_dir . '/example-com/xfer1234/dump.sql';
		$chunk_file    = $this->backup_dir . '/temp-file-chunks/xfer1234/wp-content/file.txt.part-0';
		$other_file    = $this->backup_dir . '/example-com/other/dump.sql';
		foreach ( array( $transfer_file, $chunk_file, $other_file ) as $file ) {
			mkdir( dirname( $file ), 0700, true );
			file_put_contents( $file, 'x' );
		}

		$response = $this->tx_rest()->delete_transfer_files(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'https://example.com',
				)
			)
		);

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertFalse( is_dir( $this->backup_dir . '/example-com/xfer1234' ) );
		$this->assertFalse( is_dir( $this->backup_dir . '/temp-file-chunks/xfer1234' ) );
		$this->assertTrue( is_file( $other_file ) );
	}

	public function test_delete_transfer_files_skips_unusable_directories() {
		$outside = dirname( $this->backup_dir ) . '/bgbkup-tx-outside/example-com/xfer1234/keep.txt';
		mkdir( dirname( $outside ), 0700, true );
		file_put_contents( $outside, 'keep' );

		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'delete_directory' ) )
			->getMock();
		$util->expects( $this->never() )->method( 'delete_directory' );
		$this->migrate_core->util = $util;

		$tx_rest = $this->tx_rest();

		$this->set_backup_directory( $this->backup_dir . '/../bgbkup-tx-outside' );
		$response = $tx_rest->delete_transfer_files(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'https://example.com',
				)
			)
		);
		$data     = $response->get_data();
		$this->assertFalse( $data['success'] );

		$this->set_backup_directory( $this->backup_dir );
		$response = $tx_rest->delete_transfer_files(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'not a url',
				)
			)
		);
		$data     = $response->get_data();
		$this->assertFalse( $data['success'] );

		$this->assertTrue( is_file( $outside ) );
	}

	public function test_check_dump_status_reads_status_from_transfer_dir() {
		$status_file = $this->backup_dir . '/example-com/xfer1234/db-dump-status.json';
		mkdir( dirname( $status_file ), 0700, true );
		file_put_contents(
			$status_file,
			wp_json_encode(
				array(
					'status'  => 'complete',
					'file'    => $this->backup_dir . '/example-com/xfer1234/missing.sql',
					'db_size' => 0,
				)
			)
		);

		$response = $this->tx_rest()->check_dump_status(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'https://example.com',
				)
			)
		);

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'complete', $data['db_dump_info']['status'] );
	}

	public function test_check_dump_status_fails_when_backup_directory_is_unusable() {
		$status_file = $this->backup_dir . '/example-com/xfer1234/db-dump-status.json';
		mkdir( dirname( $status_file ), 0700, true );
		file_put_contents( $status_file, wp_json_encode( array( 'status' => 'complete' ) ) );

		$this->set_backup_directory( $this->backup_dir . '/example-com/../' );
		$response = $this->tx_rest()->check_dump_status(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'https://example.com',
				)
			)
		);

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
	}

	public function test_create_dump_status_file_writes_inside_transfer_dir() {
		$this->assertTrue( $this->migrate_core->tx->create_dump_status_file( 'xfer1234', 'https://example.com' ) );

		$status_file = $this->backup_dir . '/example-com/xfer1234/db-dump-status.json';
		$this->assertTrue( is_file( $status_file ) );

		$status = json_decode( file_get_contents( $status_file ), true );
		$this->assertSame( 'pending', $status['status'] );
		$this->assertStringStartsWith(
			wp_normalize_path( $this->backup_dir ) . '/example-com/xfer1234/',
			$status['file']
		);
	}

	public function test_create_dump_status_file_writes_nothing_when_backup_directory_is_unusable() {
		$this->set_backup_directory( $this->backup_dir . '/../bgbkup-tx-outside' );

		$this->assertFalse( $this->migrate_core->tx->create_dump_status_file( 'xfer1234', 'https://example.com' ) );

		$this->assertFalse( is_dir( dirname( $this->backup_dir ) . '/bgbkup-tx-outside' ) );
		$this->assertFalse( is_dir( $this->backup_dir . '/example-com' ) );
	}

	public function test_start_db_dump_reports_failure_when_status_file_cannot_be_created() {
		$active_tx_option = $this->migrate_core->configs['option_names']['active_tx'];
		delete_option( $active_tx_option );
		$this->set_backup_directory( $this->backup_dir . '/../bgbkup-tx-outside' );

		$response = $this->tx_rest()->start_db_dump(
			$this->request(
				array(
					'transfer_id' => 'xfer1234',
					'dest_url'    => 'https://example.com',
				)
			)
		);

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertNotEmpty( $data['error'] );
		$this->assertArrayNotHasKey( 'db_dump_info', $data );
		$this->assertFalse( get_option( $active_tx_option ) );
	}

	public function test_authenticate_request_accepts_valid_administrator() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site capability check.' );
		}

		$this->create_user( 'administrator' );

		$this->assertTrue(
			$this->tx_rest()->authenticate_request(
				$this->request(
					array(
						'user' => 'bgbkup_tx_user',
						'pass' => base64_encode( 'correct-pass' ),
					)
				)
			)
		);
	}

	/**
	 * @dataProvider rejected_credentials
	 *
	 * @param array $params Request parameters.
	 */
	public function test_authenticate_request_rejects_missing_or_wrong_credentials( $params ) {
		$this->create_user( 'administrator' );

		$this->assertFalse( $this->tx_rest()->authenticate_request( $this->request( $params ) ) );
	}

	public function test_authenticate_request_rejects_user_without_capability() {
		$this->create_user( 'subscriber' );

		$this->assertFalse(
			$this->tx_rest()->authenticate_request(
				$this->request(
					array(
						'user' => 'bgbkup_tx_user',
						'pass' => base64_encode( 'correct-pass' ),
					)
				)
			)
		);
	}

	/**
	 * Credential sets that must not authenticate.
	 *
	 * @return array
	 */
	public function rejected_credentials() {
		return array(
			'no credentials'    => array( array() ),
			'user only'         => array( array( 'user' => 'bgbkup_tx_user' ) ),
			'pass only'         => array( array( 'pass' => base64_encode( 'correct-pass' ) ) ),
			'empty user'        => array(
				array(
					'user' => '',
					'pass' => base64_encode( 'correct-pass' ),
				),
			),
			'empty pass'        => array(
				array(
					'user' => 'bgbkup_tx_user',
					'pass' => '',
				),
			),
			'both empty'        => array(
				array(
					'user' => '',
					'pass' => '',
				),
			),
			'array user'        => array(
				array(
					'user' => array( 'bgbkup_tx_user' ),
					'pass' => base64_encode( 'correct-pass' ),
				),
			),
			'array pass'        => array(
				array(
					'user' => 'bgbkup_tx_user',
					'pass' => array( base64_encode( 'correct-pass' ) ),
				),
			),
			'pass decodes empty' => array(
				array(
					'user' => 'bgbkup_tx_user',
					'pass' => '====',
				),
			),
			'wrong pass'        => array(
				array(
					'user' => 'bgbkup_tx_user',
					'pass' => base64_encode( 'wrong-pass' ),
				),
			),
			'unencoded pass'    => array(
				array(
					'user' => 'bgbkup_tx_user',
					'pass' => 'correct-pass',
				),
			),
		);
	}

	/**
	 * Create the test user.
	 *
	 * @param string $role Role.
	 */
	private function create_user( $role ) {
		self::factory()->user->create(
			array(
				'user_login' => 'bgbkup_tx_user',
				'user_pass'  => 'correct-pass',
				'role'       => $role,
			)
		);
	}

	/**
	 * Sending-side REST handler.
	 *
	 * @return Boldgrid_Backup_Admin_Migrate_Tx_Rest
	 */
	private function tx_rest() {
		return new Boldgrid_Backup_Admin_Migrate_Tx_Rest( $this->migrate_core );
	}

	/**
	 * Build a REST request.
	 *
	 * @param array $params Parameters.
	 * @return WP_REST_Request
	 */
	private function request( $params ) {
		$request = new WP_REST_Request( 'POST' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Set the backup directory setting.
	 *
	 * @param string $backup_directory Backup directory.
	 */
	private function set_backup_directory( $backup_directory ) {
		$settings                     = is_array( $this->original_settings ) ? $this->original_settings : array();
		$settings['backup_directory'] = $backup_directory;
		update_option( 'boldgrid_backup_settings', $settings );
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
}
