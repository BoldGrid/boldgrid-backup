<?php
/**
 * File: test-class-boldgrid-backup-admin-auto-rollback.php
 *
 * @link https://www.boldgrid.com
 * @since     1.10.7
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Test
 *
 * @since 1.10.7
 */
class Test_Boldgrid_Backup_Admin_Auto_Rollback extends WP_UnitTestCase {
	/**
	 * Setup.
	 *
	 * @since 1.10.7
	 */
	public function setUp() {
		$this->core = new Boldgrid_Backup_Admin_Core();
	}

	/**
	 * Test wp_ajax_cli_cancel for success.
	 *
	 * @since 1.10.7
	 */
	public function test_wp_ajax_cli_cancel_success() {
		$_GET['backup_id'] = $this->core->get_backup_identifier();

		try {
			$this->_handleAjax( 'boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieStopException $e ) {
			echo null; // Do nothing.
		}

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response ); // Should be an object; not FALSE.
		$this->assertObjectHasAttribute( 'success', $response ); // Should have "success".
		$this->assertTrue( $response->success ); // "success" should be TRUE.
		$this->assertObjectHasAttribute( data, $response ); // Should have "data".
		$this->assertEquals( 'Rollback canceled', $response->data ); // "data" is a string; must match expected string.
	}

	/**
	 * Test wp_ajax_cli_cancel for failure.
	 *
	 * @since 1.10.7
	 */
	public function test_wp_ajax_cli_cancel_failure() {
		try {
			$this->_handleAjax( 'boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieStopException $e ) {
			echo null; // Do nothing.
		}

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response ); // Should be an object; not FALSE.
		$this->assertObjectHasAttribute( 'success', $response ); // Should have "success".
		$this->assertFalse( $response->success ); // "success" should be FALSE.
		$this->assertObjectHasAttribute( data, $response ); // Should have "data".
		$this->assertEquals( 'Invalid arguments', $response->data ); // "data" is a string; must match expected string.
	}
}
