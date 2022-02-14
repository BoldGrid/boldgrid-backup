<?php
/**
 * File: test-class-boldgrid-backup-rest-job.php
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
 * Class: Test_Boldgrid_Backup_Rest_Job
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Rest_Job extends Boldgrid_Backup_Rest_Case {
	/**
	 * Test get_item.
	 *
	 * The actual test to see if it works as expected can be found within
	 * Test_Boldgrid_Backup_Rest_Archive::test_create_item
	 *
	 * This method is simple here to tell you that! So that when you go looking for this test class,
	 * you see it's there and take comfort in knowing it's tested.
	 *
	 * @since SINCEVERISON
	 */
	public function test_get_item() {
		$fake_job_id = 12345678;

		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/jobs/' . $fake_job_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/jobs/' . $fake_job_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure the fake job was not found.
		$this->assertTrue( 'no_job' === $data['code'] );
	}
}
