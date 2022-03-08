<?php
/**
 * File: class-boldgrid-backup-rest-case.php
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
 * Class: Boldgrid_Backup_Rest_Case
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Case extends WP_UnitTestCase {
	/**
	 * We'll create an admin. This is that user's id.
	 *
	 * @since SINCEVERSION
	 * @access protected
	 * @var int
	 */
	protected $admin_id;

	/**
	 * We'll create an editor. This is that user's id.
	 *
	 * @since SINCEVERSION
	 * @access protected
	 * @var int
	 */
	protected $editor_id;

	/**
	 * Rest server.
	 *
	 * @since SINCEVERSION
	 * @access protected
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Setup.
	 *
	 * @since SINCEVERSION
	 */
	public function set_up() {
		// Due to loading issues, these did not load correctly via bootstrap.
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-controller.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-job.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-archive.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-setting.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-test.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-siteurl.php';

		// Register REST endpoints.
		$core = new Boldgrid_Backup_Admin_Core();
		add_action( 'rest_api_init', function() use ( $core ) {
			$rest_job = new Boldgrid_Backup_Rest_Job( $core );
			$rest_job->register_routes();

			$rest_archive = new Boldgrid_Backup_Rest_Archive( $core );
			$rest_archive->register_routes();

			$rest_setting = new Boldgrid_Backup_Rest_Setting( $core );
			$rest_setting->register_routes();

			$rest_test = new Boldgrid_Backup_Rest_Test( $core );
			$rest_test->register_routes();

			$rest_siteurl = new Boldgrid_Backup_Rest_Siteurl( $core );
			$rest_siteurl->register_routes();
		} );

		// Initiate the REST API.
		global $wp_rest_server;
		$this->server   = new WP_REST_Server();
		$wp_rest_server = $this->server;

		do_action( 'rest_api_init' );

		$this->editor_id = $this->factory->user->create( array(
			'role'         => 'editor',
			'display_name' => 'test_editor',
		) );

		$this->admin_id = $this->factory->user->create( array(
			'role'         => 'administrator',
			'display_name' => 'test_admin',
		) );
	}

	/**
	 * Tear down.
	 *
	 * @since SINCEVERSION
	 */
	public function tear_down() {
		wp_delete_user( $this->editor_id );
		wp_delete_user( $this->admin_id );
	}
}
