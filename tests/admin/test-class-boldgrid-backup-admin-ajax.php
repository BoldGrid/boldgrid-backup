<?php
/**
 * File: test-class-boldgrid-backup-admin-ajax.php
 *
 * @link https://www.boldgrid.com
 * @since     1.10.7
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * // phpcs:disable Generic.PHP.NoSilencedErrors,WordPress.VIP
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Ajax
 *
 * @since 1.10.7
 *
 * @group ajax
 * @runTestsInSeparateProcesses
 */
class Test_Boldgrid_Backup_Admin_Ajax extends WP_Ajax_UnitTestCase {
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
		global $_GET; // phpcs:ignore
		$_GET['backup_id'] = $this->core->get_backup_identifier();

		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			@$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieStopException $e ) {
			echo null; // Do nothing.
		}

		// This option was given a value above, and the cancel rollback method should have deleted it.
		$this->assertEmpty( get_option( 'boldgrid_backup_pending_rollback' ) );

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response ); // Should be an object.
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
		global $_GET; // phpcs:ignore
		unset( $_GET['backup_id'] );

		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			@$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieStopException $e ) {
			echo null; // Do nothing.
		}

		/*
		 * This option was given a value above, and because the cancel rollback method failed, the
		 * value should still be the same.
		 */
		$this->assertEquals( 'test', get_option( 'boldgrid_backup_pending_rollback' ) );

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response ); // Should be an object.
		$this->assertObjectHasAttribute( 'success', $response ); // Should have "success".
		$this->assertFalse( $response->success ); // "success" should be FALSE.
		$this->assertObjectHasAttribute( data, $response ); // Should have "data".
		$this->assertEquals( 'Invalid arguments', $response->data ); // "data" is a string; must match expected string.
	}
}
