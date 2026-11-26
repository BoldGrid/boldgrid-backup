<?php
/**
 * Direct transfer staging paths and database prefix handling.
 *
 * @package Boldgrid_Backup
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Migrate_Transfer_Paths
 */
class Test_Boldgrid_Backup_Admin_Migrate_Transfer_Paths extends WP_UnitTestCase {
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

		ini_set( 'memory_limit', '512M' );

		$this->backup_dir = rtrim( get_temp_dir(), '/' ) . '/bgbkup-transfer-' . wp_generate_password( 8, false );
		mkdir( $this->backup_dir, 0700, true );

		$this->original_settings = get_option( 'boldgrid_backup_settings', array() );
		$settings                 = is_array( $this->original_settings ) ? $this->original_settings : array();
		$settings['backup_directory'] = $this->backup_dir;
		update_option( 'boldgrid_backup_settings', $settings );
	}

	public function tear_down() {
		update_option( 'boldgrid_backup_settings', $this->original_settings );
		$this->remove_tree( $this->backup_dir );
		parent::tear_down();
	}

	public function test_small_file_relative_path_is_written_inside_staging() {
		$rx       = $this->receiver();
		$contents = 'hello';
		$status   = $rx->process_small_file_rx(
			'xfer1234',
			'wp-content/uploads/photo.jpg',
			array(
				'file' => base64_encode( $contents ),
				'md5'  => md5( $contents ),
			)
		);

		$written = $this->backup_dir . '/example-com/xfer1234/wp-content/uploads/photo.jpg';
		$this->assertSame( 'transferred', $status );
		$this->assertTrue( is_file( $written ) );
		$this->assertSame( $contents, file_get_contents( $written ) );
	}

	public function test_small_file_parent_segment_is_rejected() {
		$rx     = $this->receiver();
		$status = $rx->process_small_file_rx(
			'xfer1234',
			'wp-content/../../outside.php',
			array(
				'file' => base64_encode( '<?php ' ),
				'md5'  => md5( '<?php ' ),
			)
		);

		$this->assertSame( 'failed', $status );
		$this->assertFalse( file_exists( $this->backup_dir . '/outside.php' ) );
		$this->assertFalse( file_exists( $this->backup_dir . '/example-com/outside.php' ) );
		$this->assertFalse( is_dir( $this->backup_dir . '/example-com' ) );
		$transfer = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertSame( 'failed', $transfer['status'] );
	}

	public function test_small_file_absolute_path_is_rejected() {
		$target = $this->backup_dir . '/absolute.php';
		$rx     = $this->receiver();
		$status = $rx->process_small_file_rx(
			'xfer1234',
			$target,
			array(
				'file' => base64_encode( '<?php ' ),
				'md5'  => md5( '<?php ' ),
			)
		);

		$this->assertSame( 'failed', $status );
		$this->assertFalse( file_exists( $target ) );
	}

	public function test_large_file_parent_segment_does_not_write_or_request_parts() {
		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_post' ) )
			->getMock();
		$util->expects( $this->never() )->method( 'rest_post' );

		$rx       = $this->receiver();
		$rx->util = $util;

		$rx->process_large_file_rx(
			$this->transfer_record(),
			array(
				'path'  => '../outside.php',
				'parts' => array( 'part-1' ),
				'md5'   => md5( 'nope' ),
			)
		);

		$this->assertFalse( file_exists( $this->backup_dir . '/example-com/outside.php' ) );
		$this->assertFalse( file_exists( dirname( $this->backup_dir ) . '/outside.php' ) );
		$transfer = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertSame( 'failed', $transfer['status'] );
	}

	public function test_large_file_relative_path_is_written_inside_staging() {
		$contents = 'hello-large';
		$util     = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_post' ) )
			->getMock();
		$util->expects( $this->exactly( 2 ) )
			->method( 'rest_post' )
			->willReturnCallback(
				function ( $transfer, $route ) use ( $contents ) {
					if ( 'retrieve-large-file-part' === $route ) {
						return array(
							'file_part' => base64_encode( $contents ),
						);
					}
					return array( 'success' => true );
				}
			);

		$rx       = $this->receiver();
		$rx->util = $util;

		$rx->process_large_file_rx(
			$this->transfer_record(),
			array(
				'path'  => 'wp-content/uploads/big.bin',
				'parts' => array( 'part-1' ),
				'md5'   => md5( $contents ),
			)
		);

		$written = $this->backup_dir . '/example-com/xfer1234/wp-content/uploads/big.bin';
		$this->assertTrue( is_file( $written ) );
		$this->assertSame( $contents, file_get_contents( $written ) );
	}

	public function test_prepare_transfer_file_rejects_symlink_escape() {
		$staging = $this->backup_dir . '/example-com/xfer1234';
		$outside = $this->backup_dir . '/linked-target';
		mkdir( $staging, 0700, true );
		mkdir( $outside, 0700, true );
		$this->assertTrue( symlink( $outside, $staging . '/linked' ) );

		$result = $this->migrate_core->util->prepare_transfer_file( $staging, 'linked/evil.php' );

		$this->assertFalse( $result );
		$this->assertFalse( file_exists( $outside . '/evil.php' ) );
	}

	public function test_copy_files_accepts_listing_outside_abspath() {
		$backup   = rtrim( getenv( 'HOME' ), '/' ) . '/tmp/bgbkup-xfer-outside-' . wp_generate_password( 6, false );
		$settings = get_option( 'boldgrid_backup_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = $backup;
		update_option( 'boldgrid_backup_settings', $settings );
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );

		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$this->assertNotFalse( $staging );
		$this->assertFalse( 0 === strpos( wp_normalize_path( $staging ), wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/' ) );

		$relative = 'wp-content/uploads/bgbkup-xfer-outside.txt';
		$source   = $staging . '/' . $relative;
		wp_mkdir_p( dirname( $source ) );
		file_put_contents( $source, 'outside' );

		$files = $this->migrate_core->util->get_files_in_dir( trailingslashit( $staging ) );
		$this->assertNotEmpty( $files );
		$this->assertSame( '/', substr( $files[0]['path'], 0, 1 ) );

		$restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );
		$copied  = $restore->copy_files( $files, 'xfer1234', trailingslashit( $staging ) );
		$dest    = trailingslashit( ABSPATH ) . $relative;
		$wrote   = is_file( $dest ) ? file_get_contents( $dest ) : '';

		if ( is_file( $dest ) ) {
			unlink( $dest );
		}
		$this->remove_tree( $backup );

		$this->assertTrue( $copied );
		$this->assertSame( 'outside', $wrote );
	}

	public function test_copy_files_accepts_listing_under_abspath() {
		$backup   = trailingslashit( ABSPATH ) . 'wp-content/bgbkup-xfer-copy';
		$settings = get_option( 'boldgrid_backup_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = untrailingslashit( $backup );
		update_option( 'boldgrid_backup_settings', $settings );

		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );

		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$this->assertNotFalse( $staging );

		$relative = 'wp-content/uploads/bgbkup-xfer-copied.txt';
		$source   = $staging . '/' . $relative;
		wp_mkdir_p( dirname( $source ) );
		file_put_contents( $source, 'copied' );

		$files = $this->migrate_core->util->get_files_in_dir( trailingslashit( $staging ) );
		$this->assertNotEmpty( $files );
		$this->assertSame( 0, strpos( $files[0]['path'], 'wp-content/' ) );

		$restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );
		$copied  = $restore->copy_files( $files, 'xfer1234', trailingslashit( $staging ) );
		$dest    = trailingslashit( ABSPATH ) . $relative;

		if ( is_file( $dest ) ) {
			unlink( $dest );
		}
		$this->remove_tree( $backup );

		$this->assertTrue( $copied );
	}

	public function test_staging_dir_resolves_a_relative_backup_directory() {
		$relative = 'wp-content/bgbkup-xfer-rel-' . wp_generate_password( 6, false );
		$settings = get_option( 'boldgrid_backup_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = $relative;
		update_option( 'boldgrid_backup_settings', $settings );

		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$expect  = wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/' . $relative . '/example-com/xfer1234';

		$this->assertSame( $expect, wp_normalize_path( (string) $staging ) );
	}

	public function test_staging_dir_rejects_parent_segment_in_relative_backup_directory() {
		$settings = get_option( 'boldgrid_backup_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$this->assertFalse( $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() ) );
	}

	public function test_database_receive_fails_when_staging_cannot_be_resolved() {
		$transfer                   = $this->transfer_record();
		$transfer['db_dump_info']   = array( 'split_files' => array() );
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $transfer ) );

		$settings                     = get_option( 'boldgrid_backup_settings', array() );
		$settings                     = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$rx     = new Boldgrid_Backup_Admin_Migrate_Rx( $this->migrate_core );
		$result = $rx->process_db_rx( 'xfer1234' );
		$saved  = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'failed', $saved['status'] );
		$this->assertNotEmpty( $saved['failed_message'] );
	}

	public function test_start_db_dump_failure_from_source_marks_transfer_failed() {
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );

		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_post' ) )
			->getMock();
		$util->expects( $this->once() )
			->method( 'rest_post' )
			->willReturn(
				array(
					'success' => false,
					'error'   => 'Unable to create the database dump status file',
				)
			);

		$rx       = new Boldgrid_Backup_Admin_Migrate_Rx( $this->migrate_core );
		$rx->util = $util;
		$rx->start_db_dump( $this->transfer_record() );

		$saved = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertSame( 'failed', $saved['status'] );
		$this->assertStringContainsString( 'Unable to create the database dump status file', $saved['failed_message'] );
	}

	public function test_staging_dir_rejects_parent_segment_in_absolute_backup_directory() {
		$settings = get_option( 'boldgrid_backup_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = '/var/bgbkup/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$this->assertFalse( $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() ) );
	}

	public function test_update_table_prefix_writes_a_normal_prefix() {
		$config = $this->write_config( "<?php\n\$table_prefix = 'wp_';\n" );
		$restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertTrue( $restore->update_table_prefix( 'wp_2', $config ) );
		$this->assertNotFalse( strpos( file_get_contents( $config ), "\$table_prefix = 'wp_2';" ) );
	}

	public function test_update_table_prefix_rejects_a_closed_quote() {
		$original = "<?php\n\$table_prefix = \"wp_\";\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertFalse( $restore->update_table_prefix( "wp_';phpinfo();//", $config ) );
		$this->assertFalse( $restore->update_table_prefix( 'wp-prefix', $config ) );
		$this->assertSame( $original, file_get_contents( $config ) );
	}

	public function test_update_table_prefix_updates_the_live_assignment_after_a_comment() {
		$original = "<?php\n// \$table_prefix = 'old_';\n\$table_prefix = \"wp_\";\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertTrue( $restore->update_table_prefix( 'wp_2', $config ) );

		$updated = file_get_contents( $config );
		$this->assertNotFalse( strpos( $updated, "// \$table_prefix = 'old_';" ) );
		$this->assertNotFalse( strpos( $updated, "\$table_prefix = 'wp_2';" ) );
		$this->assertFalse( strpos( $updated, "// \$table_prefix = 'wp_2';" ) );
	}

	public function test_update_table_prefix_ignores_an_assignment_inside_a_string() {
		$original = "<?php\n\$note = '\$table_prefix = \\'old_\\';';\n\$table_prefix = 'wp_';\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertTrue( $restore->update_table_prefix( 'wp_2', $config ) );

		$updated = file_get_contents( $config );
		$this->assertNotFalse( strpos( $updated, "\$note = '\$table_prefix = \\'old_\\';';" ) );
		$this->assertNotFalse( strpos( $updated, "\n\$table_prefix = 'wp_2';\n" ) );
	}

	public function test_update_table_prefix_rejects_two_live_assignments() {
		$original = "<?php\n\$table_prefix = 'wp_';\n\$table_prefix = 'wp_a';\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertFalse( $restore->update_table_prefix( 'wp_2', $config ) );
		$this->assertSame( $original, file_get_contents( $config ) );
	}

	public function test_update_table_prefix_rejects_a_computed_second_assignment() {
		$original = "<?php\n\$table_prefix = 'wp_';\n\$table_prefix = getenv('WP_TABLE_PREFIX');\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertFalse( $restore->update_table_prefix( 'wp_2', $config ) );
		$this->assertSame( $original, file_get_contents( $config ) );
	}

	public function test_update_table_prefix_rejects_a_globals_write() {
		$original = "<?php\n\$table_prefix = 'wp_';\n\$GLOBALS['table_prefix'] = getenv('WP_TABLE_PREFIX');\n";
		$config   = $this->write_config( $original );
		$restore  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );

		$this->assertFalse( $restore->update_table_prefix( 'wp_2', $config ) );
		$this->assertSame( $original, file_get_contents( $config ) );
	}

	public function test_restore_database_stops_when_the_prefix_response_is_not_a_string() {
		$this->assert_restore_skips_import( new WP_Error( 'rest_error', 'unavailable' ) );
	}

	public function test_restore_database_stops_when_the_prefix_is_rejected() {
		$this->assert_restore_skips_import( 'wp-bad' );
	}

	public function test_restore_database_stops_when_the_config_has_a_second_assignment() {
		$config    = ABSPATH . 'wp-config.php';
		$existed   = is_file( $config );
		$original  = $existed ? file_get_contents( $config ) : '';
		$rewritten = "<?php\n\$table_prefix = 'wptests_';\n\$table_prefix = getenv('WP_TABLE_PREFIX');\n";
		if ( $existed ) {
			$this->assertNotFalse( $original );
		}
		file_put_contents( $config, $rewritten );

		try {
			$this->assert_restore_skips_import( 'wp_2' );
			$this->assertSame( $rewritten, file_get_contents( $config ) );
		} finally {
			if ( $existed ) {
				file_put_contents( $config, $original );
			} elseif ( is_file( $config ) ) {
				unlink( $config );
			}
		}
	}

	public function test_unresolved_backup_directory_fails_the_large_file_transfer() {
		$settings                     = get_option( 'boldgrid_backup_settings', array() );
		$settings                     = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$record           = $this->transfer_record();
		$record['status'] = 'transferring-large-files';
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $record ) );
		update_option(
			'boldgrid_backup_xfer_file_lists',
			array(
				'xfer1234' => array(
					'small' => '[]',
					'large' => '[]',
				),
			)
		);
		$this->migrate_core->util->update_transfer_heartbeat();

		$rx = new Boldgrid_Backup_Admin_Migrate_Rx( $this->migrate_core );
		$rx->process_active_transfer( $record );

		$transfer = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertSame( 'failed', $transfer['status'] );
		$this->assertNotEmpty( $transfer['failed_message'] );
	}

	public function test_large_file_failure_does_not_download_the_next_file() {
		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_post' ) )
			->getMock();
		$util->expects( $this->never() )->method( 'rest_post' );

		$record           = $this->transfer_record();
		$record['status'] = 'transferring-large-files';
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $record ) );
		update_option(
			'boldgrid_backup_xfer_file_lists',
			array(
				'xfer1234' => array(
					'large' => wp_json_encode(
						array(
							array(
								'path'   => '../outside.php',
								'size'   => 5,
								'status' => 'ready-to-transfer',
								'parts'  => array( 'part-1' ),
								'md5'    => md5( 'nope' ),
							),
							array(
								'path'   => 'wp-content/uploads/big.bin',
								'size'   => 5,
								'status' => 'ready-to-transfer',
								'parts'  => array( 'part-2' ),
								'md5'    => md5( 'later' ),
							),
						)
					),
				),
			)
		);

		$rx       = new Boldgrid_Backup_Admin_Migrate_Rx( $this->migrate_core );
		$rx->util = $util;
		$rx->process_large_files_rx( 'xfer1234' );

		$transfer = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertSame( 'failed', $transfer['status'] );
		$this->assertFalse( is_file( $this->backup_dir . '/example-com/xfer1234/wp-content/uploads/big.bin' ) );
	}

	public function test_copy_files_failure_marks_the_transfer_failed() {
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		$staging = $this->backup_dir . '/example-com/xfer1234';
		mkdir( $staging, 0700, true );
		$outside = $this->backup_dir . '/loose.txt';
		file_put_contents( $outside, 'loose' );

		$restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );
		$copied  = $restore->copy_files(
			array(
				array( 'path' => $outside ),
			),
			'xfer1234',
			trailingslashit( $staging )
		);

		$transfer = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );
		$this->assertFalse( $copied );
		$this->assertSame( 'failed', $transfer['status'] );
		$this->assertNotEmpty( $transfer['failed_message'] );
		$this->assertFalse( is_file( trailingslashit( ABSPATH ) . 'loose.txt' ) );
	}

	public function test_unresolved_backup_directory_is_a_status_error() {
		$this->receiver();
		$settings                     = get_option( 'boldgrid_backup_settings', array() );
		$settings                     = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$body = $this->capture_status();
		$saved = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );

		$this->assertFalse( $body['success'] );
		$this->assertNotEmpty( $body['data']['message'] );
		$this->assertSame( 'failed', $saved['status'] );
	}

	public function test_unresolved_backup_directory_does_not_retry_small_files() {
		$rx = $this->receiver();
		$settings                     = get_option( 'boldgrid_backup_settings', array() );
		$settings                     = is_array( $settings ) ? $settings : array();
		$settings['backup_directory'] = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );

		$rx->retry_stalled_small_files( $this->migrate_core->util->get_transfer_from_id( 'xfer1234' ) );
		$saved = $this->migrate_core->util->get_transfer_from_id( 'xfer1234' );

		$this->assertSame( 'failed', $saved['status'] );
	}

	public function test_delete_transfer_removes_the_record_when_staging_is_absent() {
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$this->assertNotFalse( $staging );
		$this->assertFalse( is_dir( $staging ) );

		$this->capture_delete( $this->receiver_rest() );

		$transfers = get_option( 'boldgrid_backup_direct_transfers' );
		$this->assertArrayNotHasKey( 'xfer1234', $transfers );
	}

	public function test_delete_transfer_keeps_the_record_when_staging_cannot_be_resolved() {
		$settings                      = get_option( 'boldgrid_backup_settings', array() );
		$settings                      = is_array( $settings ) ? $settings : array();
		$settings['backup_directory']  = 'wp-content/../outside';
		update_option( 'boldgrid_backup_settings', $settings );
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		$this->assertFalse( $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() ) );

		$this->capture_delete( $this->receiver_rest( false ) );

		$transfers = get_option( 'boldgrid_backup_direct_transfers' );
		$this->assertArrayHasKey( 'xfer1234', $transfers );
	}

	public function test_delete_transfer_keeps_the_record_when_removal_fails() {
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$this->assertNotFalse( $staging );
		mkdir( $staging, 0700, true );

		global $wp_filesystem;
		$original      = $wp_filesystem;
		$wp_filesystem = $this->getMockBuilder( 'WP_Filesystem_Direct' )
			->disableOriginalConstructor()
			->setMethods( array( 'delete' ) )
			->getMock();
		$wp_filesystem->method( 'delete' )->willReturn( false );

		try {
			$this->capture_delete( $this->receiver_rest( false ) );
		} finally {
			$wp_filesystem = $original;
		}

		$transfers = get_option( 'boldgrid_backup_direct_transfers' );
		$this->assertArrayHasKey( 'xfer1234', $transfers );
		$this->assertTrue( is_dir( $staging ) );
	}

	/**
	 * Receiver REST handler whose source callback does not make an HTTP call.
	 *
	 * @param bool $allow_source_delete Whether the source delete call is expected.
	 * @return Boldgrid_Backup_Admin_Migrate_Rx_Rest
	 */
	private function receiver_rest( $allow_source_delete = true ) {
		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_post' ) )
			->getMock();
		if ( $allow_source_delete ) {
			$util->expects( $this->once() )->method( 'rest_post' )->willReturn( true );
		} else {
			$util->expects( $this->never() )->method( 'rest_post' );
		}

		$rx       = new Boldgrid_Backup_Admin_Migrate_Rx_Rest( $this->migrate_core );
		$rx->util = $util;

		return $rx;
	}

	/**
	 * Restore with a prefix response and assert the dump is not imported.
	 *
	 * @param mixed $prefix_response Value returned for the prefix request.
	 */
	private function assert_restore_skips_import( $prefix_response ) {
		global $wpdb;

		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		$staging = $this->migrate_core->util->get_transfer_staging_dir( $this->transfer_record() );
		$this->assertNotFalse( $staging );
		mkdir( $staging, 0700, true );

		$table = 'bgbkup_dt_probe_' . strtolower( wp_generate_password( 8, false ) );
		$dump  = $staging . '/dump.sql';
		file_put_contents( $dump, 'CREATE TABLE `' . $table . '` (id INT);' . "\n" );

		$util = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Migrate_Util' )
			->setConstructorArgs( array( $this->migrate_core ) )
			->setMethods( array( 'rest_get' ) )
			->getMock();
		$util->method( 'rest_get' )->willReturn( $prefix_response );

		$restore                  = new Boldgrid_Backup_Admin_Migrate_Restore( $this->migrate_core );
		$this->migrate_core->util = $util;

		try {
			$this->assertFalse( $restore->restore_database( $dump, $this->transfer_record() ) );
			$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		} finally {
			$wpdb->query( 'DROP TABLE IF EXISTS `' . $table . '`' );
		}
	}

	/**
	 * Run check-status and return the JSON body.
	 *
	 * @return array
	 */
	private function capture_status() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );

		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'transfer_id', 'xfer1234' );
		$rx = new Boldgrid_Backup_Admin_Migrate_Rx_Rest( $this->migrate_core );

		ob_start();
		try {
			$rx->check_status( $request );
			$this->fail( 'Expected the status request to end.' );
		} catch ( WPDieException $exception ) {
			unset( $exception );
		} finally {
			$body = ob_get_clean();
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		}

		$decoded = json_decode( $body, true );
		$this->assertTrue( is_array( $decoded ) );

		return $decoded;
	}

	/**
	 * Run delete-transfer and discard the JSON response.
	 *
	 * @param Boldgrid_Backup_Admin_Migrate_Rx_Rest $rx REST handler.
	 */
	private function capture_delete( $rx ) {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );

		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'transfer_id', 'xfer1234' );

		ob_start();
		try {
			$rx->delete_transfer( $request );
			$this->fail( 'Expected the delete request to end.' );
		} catch ( WPDieException $exception ) {
			unset( $exception );
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
			ob_end_clean();
		}
	}

	/**
	 * Seed a transfer and return the receiver.
	 *
	 * @return Boldgrid_Backup_Admin_Migrate_Rx
	 */
	private function receiver() {
		update_option( 'boldgrid_backup_direct_transfers', array( 'xfer1234' => $this->transfer_record() ) );
		update_option(
			'boldgrid_backup_xfer_file_lists',
			array(
				'xfer1234' => array(
					'small' => wp_json_encode(
						array(
							array(
								'path'   => 'wp-content/uploads/photo.jpg',
								'size'   => 5,
								'status' => 'transferring',
							),
						)
					),
					'large' => wp_json_encode(
						array(
							array(
								'path'   => 'wp-content/uploads/big.bin',
								'size'   => 5,
								'status' => 'transferring',
							),
							array(
								'path'   => '../outside.php',
								'size'   => 5,
								'status' => 'transferring',
							),
						)
					),
				),
			)
		);

		return new Boldgrid_Backup_Admin_Migrate_Rx( $this->migrate_core );
	}

	/**
	 * Transfer row used by the receive tests.
	 *
	 * @return array
	 */
	private function transfer_record() {
		return array(
			'transfer_id'     => 'xfer1234',
			'source_site_url' => 'https://example.com',
			'dest_site_url'   => 'https://example.test',
			'status'          => 'transferring-small-files',
			'time_tracking'   => array(),
			'largest_file_size' => 5,
		);
	}

	/**
	 * Write a temporary wp-config stand-in.
	 *
	 * @param string $contents File contents.
	 * @return string
	 */
	private function write_config( $contents ) {
		$path = $this->backup_dir . '/wp-config.php';
		file_put_contents( $path, $contents );
		return $path;
	}

	/**
	 * Delete a directory tree.
	 *
	 * @param string $dir Directory.
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
			if ( $item->isDir() && ! $item->isLink() ) {
				rmdir( $item->getPathname() );
			} else {
				unlink( $item->getPathname() );
			}
		}

		rmdir( $dir );
	}
}
