<?php
/**
 * File: test-class-boldgrid-backup-rest-test.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/rest
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 */

/**
 * Class: Test_Boldgrid_Backup_Rest_Test
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Rest_Test extends Boldgrid_Backup_Rest_Case {
	/**
	 * Test get_item.
	 *
	 * @since SINCEVERISON
	 */
	public function test_get_item() {
		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/test' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/test' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure our response has the "passed" key.
		$this->assertTrue( array_key_exists( 'passed', $data ) );
	}
}
