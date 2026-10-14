<?php
/**
 * File: test-class-boldgrid-backup-admin-test.php
 *
 * @link https://www.boldgrid.com
 * @since     1.6.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Test
 *
 * @since 1.6.2
 */
class Test_Boldgrid_Backup_Admin_Test extends WP_UnitTestCase {
	/**
	 * Core instance.
	 *
	 * @since 1.6.2
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * Setup.
	 *
	 * @since 1.6.2
	 */
	public function set_up() {
		$this->core = new Boldgrid_Backup_Admin_Core();
	}

	/**
	 * Test get_cli_support.
	 *
	 * @since 1.6.2
	 */
	public function test_get_cli_support() {
		$cli_support = $this->core->test->get_cli_support();

		// Make sure essential values are there.
		$this->assertTrue( isset( $cli_support['has_curl_ssl'] ) );
		$this->assertTrue( isset( $cli_support['has_url_fopen'] ) );
		$this->assertTrue( isset( $cli_support['can_remote_get'] ) );

		// We're assuming EVERYONE can 'can_remote_get'. If they can't, let us know.
		$this->assertTrue( $cli_support['can_remote_get'] );
	}

	/**
	 * Crontab availability must be a read, not a rewrite.
	 *
	 * @since 1.17.5
	 */
	public function test_is_crontab_available_does_not_write() {
		$original = $this->core->cron;
		$cron     = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'get_all', 'update_cron', 'entry_delete' ) )
			->getMock();

		$cron->expects( $this->once() )->method( 'get_all' )->with( true )->willReturn( "* * * * * date >> heartbeat.log\n" );
		$cron->expects( $this->never() )->method( 'update_cron' );
		$cron->expects( $this->never() )->method( 'entry_delete' );

		$this->core->cron = $cron;

		try {
			$test = new Boldgrid_Backup_Admin_Test( $this->core );
			$this->assertTrue( $test->is_crontab_available() );
		} finally {
			$this->core->cron = $original;
		}
	}
}
