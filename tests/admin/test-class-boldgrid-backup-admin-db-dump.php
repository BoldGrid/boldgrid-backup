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
	 * Test get_connection_string().
	 *
	 * @since 1.13.3
	 */
	public function test_get_connection_string() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// localhost.
		$this->assertEquals(
			'mysql:host=localhost;dbname=db_catfish',
			$core->utility->get_pdo_connection_string( 'localhost', 'db_catfish' )
		);

		// localhost:3306.
		$this->assertEquals(
			'mysql:host=localhost;port=3306;dbname=db_catfish',
			$core->utility->get_pdo_connection_string( 'localhost:3306', 'db_catfish' )
		);

		// localhost:/var/lib/mysql/mysql.sock.
		$this->assertEquals(
			'mysql:host=localhost;unix_socket=/var/lib/mysql/mysql.sock;dbname=db_catfish',
			$core->utility->get_pdo_connection_string( 'localhost:/var/lib/mysql/mysql.sock', 'db_catfish' )
		);

		// /var/lib/mysql/mysql.sock.
		$this->assertEquals(
			'mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=db_catfish',
			$core->utility->get_pdo_connection_string( '/var/lib/mysql/mysql.sock', 'db_catfish' )
		);
	}
}
