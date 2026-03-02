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
 * @preserveGlobalState disabled
 */
class Test_Boldgrid_Backup_Admin_Ajax extends WP_Ajax_UnitTestCase {
	/**
	 * Setup.
	 *
	 * @since 1.10.7
	 */
	public function set_up() {
		parent::setup();

		require_once dirname( dirname( __DIR__ ) ) . '/includes/class-boldgrid-backup.php';
		( new Boldgrid_Backup() )->run();

		$this->core = new Boldgrid_Backup_Admin_Core();
	}

	/**
	 * Simulate the server-side state created when a restore cron job is scheduled.
	 *
	 * Stores a known secret in the site option and returns it so tests can pass it
	 * as the cli_cancel_secret GET parameter.
	 *
	 * @since 1.17.2
	 *
	 * @param string $secret Optional secret value to store. Defaults to a fixed test value.
	 * @return string The secret that was stored.
	 */
	private function set_cli_cancel_secret( $secret = 'test_cli_cancel_secret_abc123' ) {
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $secret );
		return $secret;
	}

	/**
	 * Assert that an AJAX response represents a failure with "Invalid arguments".
	 *
	 * @since 1.17.2
	 */
	private function assertInvalidArgumentsResponse() {
		// The pending rollback option should not have been cleared.
		$this->assertEquals( 'test', get_option( 'boldgrid_backup_pending_rollback' ) );

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertEquals( 'Invalid arguments', $response->data );
	}

	/**
	 * Test wp_ajax_cli_cancel for success.
	 *
	 * Both a valid backup_id and a valid cli_cancel_secret (matching the stored
	 * site option) are required for the cancel to succeed.
	 *
	 * @since 1.10.7
	 */
	public function test_wp_ajax_cli_cancel_success() {
		global $_GET; // phpcs:ignore
		$_GET['backup_id']         = $this->core->get_backup_identifier();
		$_GET['cli_cancel_secret'] = $this->set_cli_cancel_secret();

		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Do nothing, this is expected.
			echo null;
		}

		// The rollback option should have been cleared by cancel().
		$this->assertEmpty( get_option( 'boldgrid_backup_pending_rollback' ) );

		// The one-time secret should have been deleted by cancel().
		$this->assertFalse( get_site_option( 'boldgrid_backup_cli_cancel_secret', false ) );

		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response ); // Should be an object.
		$this->assertObjectHasAttribute( 'success', $response ); // Should have "success".
		$this->assertTrue( $response->success ); // "success" should be TRUE.
		$this->assertObjectHasAttribute( 'data', $response ); // Should have "data".
		$this->assertEquals( 'Rollback canceled', $response->data ); // "data" is a string; must match expected string.
	}

	/**
	 * Test wp_ajax_cli_cancel fails when backup_id is missing.
	 *
	 * @since 1.10.7
	 */
	public function test_wp_ajax_cli_cancel_failure() {
		global $_GET; // phpcs:ignore
		unset( $_GET['backup_id'] );
		$_GET['cli_cancel_secret'] = $this->set_cli_cancel_secret();

		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Do nothing, this is expected.
			echo null;
		}

		$this->assertInvalidArgumentsResponse();
	}

	/**
	 * Test wp_ajax_cli_cancel fails when cli_cancel_secret is missing.
	 *
	 * This covers the reported vulnerability: an attacker who can compute or guess
	 * the backup_id must not be able to cancel a rollback without also knowing the
	 * one-time random secret stored server-side.
	 *
	 * @since 1.17.2
	 */
	public function test_wp_ajax_cli_cancel_no_secret() {
		global $_GET; // phpcs:ignore
		$_GET['backup_id'] = $this->core->get_backup_identifier();
		unset( $_GET['cli_cancel_secret'] );

		$this->set_cli_cancel_secret(); // Secret is stored but not provided in the request.
		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieContinueException $e ) {
			echo null;
		}

		$this->assertInvalidArgumentsResponse();
	}

	/**
	 * Test wp_ajax_cli_cancel fails when an incorrect cli_cancel_secret is provided.
	 *
	 * Even with a valid backup_id, supplying the wrong secret must be rejected.
	 *
	 * @since 1.17.2
	 */
	public function test_wp_ajax_cli_cancel_wrong_secret() {
		global $_GET; // phpcs:ignore
		$_GET['backup_id']         = $this->core->get_backup_identifier();
		$_GET['cli_cancel_secret'] = 'this_is_not_the_correct_secret';

		$this->set_cli_cancel_secret( 'correct_secret_stored_server_side' ); // Stored secret differs.
		update_option( 'boldgrid_backup_pending_rollback', 'test' );

		try {
			$this->_handleAjax( 'nopriv_boldgrid_cli_cancel_rollback' );
		} catch ( WPAjaxDieContinueException $e ) {
			echo null;
		}

		$this->assertInvalidArgumentsResponse();
	}
}
