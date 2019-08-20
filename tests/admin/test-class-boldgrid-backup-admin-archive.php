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
	 * Test init_by_key.
	 *
	 * @since 1.11.0
	 */
	public function test_init_by_key() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// If we don't have a backup, create one.
		// @todo This is freezing phpunit.
		/*
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		if ( empty( $latest_backup ) ) {
			$info = $core->archive_files( true );
		}
		*/

		$archive = $core->archive->init_by_key( 0 );
		$this->assertTrue( $archive );

		$archive = $core->archive->init_by_key( 100 );
		$this->assertFalse( $archive );
	}
}
