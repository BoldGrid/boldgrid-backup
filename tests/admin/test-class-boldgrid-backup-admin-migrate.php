<?php
/**
 * File: test-class-boldgrid-backup-admin-migrate.php
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
 * Class: Test_Boldgrid_Backup_Admin_Migrate
 *
 * @since 1.11.0
 */
class Test_Boldgrid_Backup_Admin_Migrate extends WP_UnitTestCase {
	public $core;

	public function set_up() {
		$this->core = apply_filters(
			'boldgrid_backup_get_core',
			new \Boldgrid_Backup_Admin_Core()
		);
	}

	public function test_constructor() {
		$admin_migrate = new Boldgrid_Backup_Admin_Migrate( $this->core );
		$this->assertInstanceOf( 'Boldgrid_Backup_Admin_Migrate', $admin_migrate );
	}
	public function test_init_logging() {
		$admin_migrate = new Boldgrid_Backup_Admin_Migrate( $this->core );
		$admin_migrate->init_logging();
		$this->assertInstanceOf( 'Boldgrid_Backup_Admin_Log', $admin_migrate->log );
	}
}