<?php
/**
 * File: test-class-option.php
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Option
 *
 * @since SINCEVERSION
 */
class Test_Option extends WP_UnitTestCase {
	/**
	 * Get our latest backup, creating a backup if need be.
	 *
	 * @since SINCEVERSION
	 */
	public function get_latest_backup() {
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );

		if ( empty( $latest_backup ) ) {
			$core = apply_filters( 'boldgrid_backup_get_core', null );
			$core->archive_files( true );

			$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		}

		return $latest_backup;
	}

	/**
	 * Test get_next_id.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_next_id() {
		$option = new Boldgrid\Backup\Archive\Option();

		delete_option( 'boldgrid_backup_backups' );

		$this->assertEquals( 0, $option->get_next_id() );

		// Make sure we have a backup and it's been added to the boldgrid_backup_backups option.
		$latest_backup = $this->get_latest_backup();
		$archive       = Boldgrid\Backup\Archive\Factory::get_by_filename( basename( $latest_backup['filepath'] ) );

		$this->assertEquals( 1, $option->get_next_id() );
	}

	/**
	 * Test update_by_filename.
	 *
	 * @since SINCEVERSION
	 */
	public function test_update_by_filename() {
		// Make sure we have a backup and it's been added to the boldgrid_backup_backups option.
		$latest_backup = $this->get_latest_backup();
		$filename      = basename( $latest_backup['filepath'] );
		$archive       = Boldgrid\Backup\Archive\Factory::get_by_filename( $filename );

		// Update by filename.
		$option = new Boldgrid\Backup\Archive\Option();
		$option->update_by_filename( $filename, 'next_to_me', 'JamesRos' );

		// Verify it worked.
		$all_backups = $option->get_all();
		$this->assertEquals( $all_backups[0]['next_to_me'], 'JamesRos' );
	}
}
