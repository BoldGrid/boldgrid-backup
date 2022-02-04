<?php
/**
 * File: test-class-boldgrid-backup-rest-archive.php
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
 * Class: Test_Boldgrid_Backup_Rest_Archive
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Rest_Archive extends Boldgrid_Backup_Rest_Case {
	/**
	 * Setup.
	 *
	 * @since SINCEVERSION
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * Tear down.
	 *
	 * @since SINCEVERSION
	 */
	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test creating a backup.
	 *
	 * @since SINCEVERSION
	 */
	public function test_create_item() {
		/*
		 * Test an unprivileged user. Make sure they get a 403.
		 *
		 * Our API call return will be similar to the following:
		 * Array (
		 * 		[code] => rest_forbidden
		 * 		[message] => Sorry, you are not allowed to do that.
		 * 		[data] => Array (
		 * 			[status] => 403
		 * 	) )
		 */
		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/archives' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( 403 === $data['data']['status'] );

		/**
		 * Ensure an admin can get a backup started.
		 *
		 * Our API call return will be similar to the following:
		 * Array (
		 *      [id] => 1643907103-e7c738
		 *      [type] => backup
		 *      [created_at] => 2022-02-03T16:51:43+00:00
		 *      [started_at] =>
		 *      [completed_at] =>
		 *      [status] => pending
		 *      [data] => Array ( )
		 * )
		 */
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/archives' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( 'backup' === $data['type'] );

		// Ensure we can get our task based upon the task id we got back.
		$task = new Boldgrid_Backup_Admin_Task();
		$task->init_by_id( $data['id'] );

		$this->assertTrue( $data['id'] === $task->get_id() );

		// @todo Find a way to ensure our backup finishes.

		// While we're here, let's test the jobs REST calls.
		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/jobs/' . $data['id'] );
		$request->set_param( 'id', $data['id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// A basic test to ensure the jobs REST call is working. @todo expand upon.
		$this->assertTrue( 'backup' === $data['type'] );
		$this->assertTrue( 'pending' === $data['status'] );
		$this->assertTrue( ! empty( $data['id'] ) );
	}

	/**
	 * Test getting a listing of backups.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_items() {
		// Test permissions.
		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/archives' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( 403 === $data['data']['status'] );

		// Ensure we have at least one backup so we get something back in the list.
		$archiver = new Boldgrid_Backup_Archiver();
		$archiver->run();
		$info = $archiver->get_info();
		$this->assertTrue( ! empty( $info['filepath'] ) );

		// Ensure we get a list.
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/archives' );
    	$response = $this->server->dispatch( $request );
    	$data     = $response->get_data();

		// Ensure we have a filename in the first backup. @todo A very basic test, should be expanded.
		$this->assertTrue( ! empty( $data[0]['filename'] ) );
	}

	/**
	 * Test restoring a backup.
	 *
	 * @since SINCEVERSION
	 */
	public function test_restore() {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'PUT', '/bgbkup/v1/archives' );
    	$response = $this->server->dispatch( $request );
    	$data     = $response->get_data();

		// We should get an error: Unable to restore. Missing required parameters.
		$this->assertTrue( 'bgbkup_rest_missing_param' === $data['code'] );

		$backup_id = 1;

		$request = new WP_REST_Request( 'PUT', '/bgbkup/v1/archives' );
		$request->set_param( 'id', $backup_id );
    	$response = $this->server->dispatch( $request );
    	$data     = $response->get_data();

		$this->assertTrue( $data['data']['backup_id'] === $backup_id );

		// Ensure we can get our task based upon the task id we got back.
		$task = new Boldgrid_Backup_Admin_Task();
		$task->init_by_id( $data['id'] );

		$this->assertTrue( $data['id'] === $task->get_id() );

		// @todo Find a way to ensure our restore finishes.
	}
}
