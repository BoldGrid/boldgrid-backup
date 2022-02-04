<?php
/**
 * File: test-class-boldgrid-backup-rest-settings.php
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
 * Class: Test_Boldgrid_Backup_Rest_Settings
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Rest_Settings extends Boldgrid_Backup_Rest_Case {
	/**
	 * Test get_item.
	 *
	 * @since SINCEVERISON
	 */
	public function test_get_item() {
		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/settings' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/settings' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we have expected data.
		$keys = array( 'schedule', 'autoupdate', 'notification_email', 'auto_backup', 'folder_exclusion_include', 'folder_exclusion_exclude' );
		foreach ( $keys as $key ) {
			$this->assertTrue( array_key_exists( $key, $data ) );
		}
	}

	/**
	 * Test update item.
	 *
	 * @since SINCEVERSION
	 */
	public function test_update_item() {
		$example_email = wp_rand( 1, 5000 ) . '@example.org';

		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/settings' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/settings' );
		$request->set_body_params( array(
			'settings' => array(
				'notification_email' => $example_email,
			),
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure the email address we set is the email address get got back.
		$this->assertTrue( $data['notification_email'] === $example_email );

		$settings = get_option( 'boldgrid_backup_settings' );

		// Look at the raw settings and ensure the email address was updated.
		$this->assertTrue( $settings['notification_email'] === $example_email );
	}
}
