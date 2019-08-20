<?php
/**
 * File: test-class-boldgrid-backup-admin-auto-rollback.php
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
 * Class: Test_Boldgrid_Backup_Auto_Rollback
 *
 * @since 1.11.0
 */
class Test_Boldgrid_Backup_Auto_Rollback extends WP_UnitTestCase {
	/**
	 * Test get_time_data.
	 *
	 * @since 1.11.0
	 */
	public function test_get_time_data() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$time = $core->auto_rollback->get_time_data();

		$this->assertTrue( ! empty( $time['hour'] ) );
		$this->assertTrue( ! empty( $time['minute'] ) );
		$this->assertTrue( ! empty( $time['second'] ) );
		$this->assertTrue( ! empty( $time['deadline'] ) );

		// If the time data is already set, it should return it (rather than generate fresh).
		sleep( 2 );
		$new_time = $core->auto_rollback->get_time_data();
		$this->assertEquals( $time, $new_time );
	}
}
