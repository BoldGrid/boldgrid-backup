<?php
/**
 * File: test-class-factory.php
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
 * Class: Test_Factory
 *
 * @since SINCEVERSION
 */
class Test_Factory extends WP_UnitTestCase {
	/**
	 * Test get_by_id.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_by_id() {
		// Invalid backup.
		$archive = Boldgrid\Backup\Archive\Factory::get_by_id( 1000 );
		$this->assertEquals( null, $archive->filename );

		// Create a backup if we need to.
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		if ( empty( $latest_backup ) ) {
			$core = apply_filters( 'boldgrid_backup_get_core', null );
			$core->archive_files();
			$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		}

		// This will add the backup id to the database.
		$archive = Boldgrid\Backup\Archive\Factory::get_by_filename( basename( $latest_backup['filepath'] ) );

		// We should only have 1 backup, so the id is 1.
		$archive = Boldgrid\Backup\Archive\Factory::get_by_id( 1 );
		$this->assertEquals( $archive->filepath, $latest_backup['filepath'] );
	}
}
