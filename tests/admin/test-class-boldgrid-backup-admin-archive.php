<?php
/**
 * File: test-class-boldgrid-backup-admin-archive.php
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
 * Class: Test_Boldgrid_Backup_Admin_Archive
 *
 * @since 1.11.0
 */
class Test_Boldgrid_Backup_Admin_Archive extends WP_UnitTestCase {
	/**
	 * Ensure we have a backup.
	 *
	 * @since SINCEVERSION
	 */
	public function maybe_create_backup() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		if ( empty( $latest_backup ) ) {
			$core->archive_files( true );
		}
	}

	/**
	 * Test init_by_key.
	 *
	 * @since 1.11.0
	 */
	public function test_init_by_key() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->maybe_create_backup();

		$archive = $core->archive->init_by_key( 0 );
		$this->assertTrue( $archive );

		$archive = $core->archive->init_by_key( 100 );
		$this->assertFalse( $archive );
	}

	/**
	 *  Test init_by_latest.
	 *
	 *  @since SINCEVERSION
	 */
	public function test_init_by_latest() {
		$archive = new Boldgrid_Backup_Admin_Archive();

		// An archive class that hasn't been initialized should have a 0 timestamp.
		$this->assertEquals( 0, $archive->timestamp );

		$this->maybe_create_backup();

		// Once we initialize, the timestamp should be set.
		$archive->init_by_latest();
		$this->assertTrue( 0 !== $archive->timestamp );
	}

	/**
	 * Test is_archive.
	 *
	 * @since SINCEVERSION
	 */
	public function test_is_archive() {
		$archive = new Boldgrid_Backup_Admin_Archive();

		$this->assertFalse( $archive->is_archive( 'catfish.zip' ) );

		// Create a backup and get the path to it.
		$this->maybe_create_backup();
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		$this->assertTrue( $archive->is_archive( $latest_backup['filepath'] ) );
	}

	/**
	 * Test is_stored_locally.
	 *
	 * @since SINCEVERSION
	 */
	public function test_is_stored_locally() {
		$this->maybe_create_backup();

		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );

		$archive = new Boldgrid_Backup_Admin_Archive();
		$archive->init( $latest_backup['filepath'] );

		$this->assertTrue( $archive->is_stored_locally() );
		$this->assertFalse( $archive->is_stored_remotely() );
	}

	/**
	 * Test set_id.
	 *
	 * @since SINCEVERSION
	 */
	public function test_set_id() {
		// An uninitialized archive will have a null id.
		$archive = new Boldgrid_Backup_Admin_Archive();
		$this->assertEquals( null, $archive->get_id() );
		$this->assertEquals( null, $archive->get_key() );

		// Get a backup.
		$this->maybe_create_backup();
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		// The factory will call set_id().
		$archive = Boldgrid\Backup\Archive\Factory::get_by_filename( basename( $latest_backup['filepath'] ) );

		// We should only have 1 backup. Its id should be 1.
		$this->assertEquals( 1, $archive->get_id() );
		$this->assertEquals( 0, $archive->get_key() );
	}
}
