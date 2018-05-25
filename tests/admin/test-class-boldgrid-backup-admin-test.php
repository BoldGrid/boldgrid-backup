<?php
/**
 * BoldGrid Source Code
 *
 * @package   Test_Boldgrid_Backup_Admin_Test
 * @copyright BoldGrid.com
 * @version   $Id$
 * @since     1.6.2
 * @author    BoldGrid.com <wpb@boldgrid.com>
 */

/**
 *
 */
class Test_Boldgrid_Backup_Admin_Test extends WP_UnitTestCase {

	/**
	 * Setup.
	 *
	 * @since 1.6.2
	 */
	public function setUp() {
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
}