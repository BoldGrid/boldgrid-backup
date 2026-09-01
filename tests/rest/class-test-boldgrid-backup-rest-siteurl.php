<?php
/**
 * File: test-class-boldgrid-backup-rest-siteurl.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/rest
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Rest_Siteurl
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Rest_Siteurl extends Boldgrid_Backup_Rest_Case {
	/**
	 * Test get_item.
	 *
	 * @since SINCEVERISON
	 */
	public function test_get_item() {
		wp_set_current_user( $this->editor_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/siteurl' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/bgbkup/v1/siteurl' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Validate the data we got back from the rest call.
		$this->assertTrue( 'http://example.org' === $data['home'] );
		$this->assertTrue( 'http://example.org' === $data['siteurl'] );
		$this->assertTrue( empty( $data['old_home'] ) );
		$this->assertTrue( empty( $data['old_siteurl'] ) );
	}

	/**
	 * Test update item.
	 *
	 * @since SINCEVERSION
	 */
	public function test_update_item() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/bgbkup/v1/siteurl' );
		$request->set_body_params( array(
			'siteurl' => 'http://example.com',
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Ensure we don't have permission as an editor.
		$this->assertTrue( 403 === $data['data']['status'] );

		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'POST', '/bgbkup/v1/siteurl' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// We forgot to pass in our siteurl and should get an error.
		$this->assertTrue( 400 === $data['data']['status'] );
		$this->assertTrue( 'rest_missing_callback_param' === $data['code'] );

		// Before we change the site url, let's create a post with a link in it.
		$post_id = wp_insert_post( array(
			'post_title'   => 'Test post',
			'post_content' => 'A link to <a href="http://example.org/about-us">about us</a>.',
		) );

		$request = new WP_REST_Request( 'POST', '/bgbkup/v1/siteurl' );
		$request->set_body_params( array(
			'siteurl' => 'http://example.com',
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Validate the data we got back after changing the site url.
		$this->assertTrue( 'http://example.com' === $data['home'] );
		$this->assertTrue( 'http://example.com' === $data['siteurl'] );
		$this->assertTrue( 'http://example.org' === $data['old_home'] );
		$this->assertTrue( 'http://example.org' === $data['old_siteurl'] );

		// Validate some options.
		$this->assertTrue( 'http://example.com' === get_option( 'home' ) );
		$this->assertTrue( 'http://example.com' === get_option( 'siteurl' ) );

		// Ensure the link in our post was updated.
		clean_post_cache( $post_id );
		$post = get_post( $post_id );
		$this->assertTrue( false !== strpos( $post->post_content, 'http://example.com/about-us' ) );

		// Test and make sure we don't get weird find / replace issues.
		$request = new WP_REST_Request( 'POST', '/bgbkup/v1/siteurl' );
		$request->set_body_params( array(
			'siteurl' => 'http://example.com/514/514',
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Validate the data we got back after changing the site url.
		$this->assertTrue( 'http://example.com/514/514' === $data['home'] );
		$this->assertTrue( 'http://example.com/514/514' === $data['siteurl'] );
		$this->assertTrue( 'http://example.com' === $data['old_home'] );
		$this->assertTrue( 'http://example.com' === $data['old_siteurl'] );

		// Validate some options.
		$this->assertTrue( 'http://example.com/514/514' === get_option( 'home' ) );
		$this->assertTrue( 'http://example.com/514/514' === get_option( 'siteurl' ) );

		// Ensure the link in our post was updated.
		clean_post_cache( $post_id );
		$post = get_post( $post_id );
		$this->assertTrue( false !== strpos( $post->post_content, 'http://example.com/514/514/about-us' ) );
	}
}
