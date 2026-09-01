<?php
/**
 * File: test-class-boldgrid-backup-admin-migrate-rx-rest.php
 *
 * @link https://www.boldgrid.com
 * @since 1.17.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Migrate_Rx_Rest
 *
 * @since 1.17.0
 */
class Test_Boldgrid_Backup_Admin_Migrate_Rx_Rest extends WP_UnitTestCase {
	public $core;

	public $migrate_core;

	public function set_up() {
		$this->core = apply_filters(
			'boldgrid_backup_get_core',
			new \Boldgrid_Backup_Admin_Core()
		);

		$this->migrate_core = new Boldgrid_Backup_Admin_Migrate( $this->core );
	}

	protected function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	/**
	 * Stub wp_remote_get responses for validate_url.
	 *
	 * @param array $header_map Headers from getAll().
	 */
	private function mock_validate_url_headers( $header_map ) {
		$headers = new class( $header_map ) {
			private $all;

			public function __construct( $all ) {
				$this->all = $all;
			}

			public function getAll() {
				return $this->all;
			}
		};

		add_filter(
			'pre_http_request',
			function () use ( $headers ) {
				return array(
					'headers'  => $headers,
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);
	}

	public function test_validate_url_malformed_api_link_returns_rest_error() {
		$rx = new Boldgrid_Backup_Admin_Migrate_Rx_Rest( $this->migrate_core );
		$this->mock_validate_url_headers(
			array(
				'link' => 'rel="https://api.w.org/"',
			)
		);

		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'url', 'https://example.com' );

		$result = $rx->validate_url( $request );
		$data   = $result->get_data();

		$this->assertTrue( $data['error'] );
	}
}
