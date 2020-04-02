<?php
/**
 * File: test-class-boldgrid-backup-admin-db-dump.php
 *
 * @link https://www.boldgrid.com
 * @since 1.13.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Db_Dump
 *
 * @since 1.13.3
 */
class Test_Boldgrid_Backup_Admin_Db_Dump extends WP_UnitTestCase {
	/**
	 * Test get_db_host().
	 *
	 * @since 1.13.3
	 */
	public function test_get_db_host() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->assertEquals( 'localhost', $core->db_dump->get_db_host( 'localhost' ) );

		$this->assertEquals( 'localhost:3306', $core->db_dump->get_db_host( 'localhost:3306' ) );

		$this->assertEquals( 'localhost', $core->db_dump->get_db_host( 'localhost:/var/lib/mysql/mysql.sock' ) );
	}
}
