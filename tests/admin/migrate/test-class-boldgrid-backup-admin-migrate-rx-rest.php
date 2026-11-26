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

		$user = get_user_by( 'login', 'bgbkup_rx_user' );
		if ( $user ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user->ID );
		}

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

	public function test_authenticate_request_accepts_valid_administrator() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site capability check.' );
		}

		$this->create_auth_user( 'administrator' );

		$this->assertTrue(
			$this->authenticate(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => base64_encode( 'correct-pass' ),
				)
			)
		);
	}

	/**
	 * @dataProvider rejected_credentials
	 *
	 * @param array $params Request parameters.
	 */
	public function test_authenticate_request_rejects_missing_or_wrong_credentials( $params ) {
		$this->create_auth_user( 'administrator' );

		$this->assertFalse( $this->authenticate( $params ) );
	}

	public function test_authenticate_request_rejects_user_without_capability() {
		$this->create_auth_user( 'subscriber' );

		$this->assertFalse(
			$this->authenticate(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => base64_encode( 'correct-pass' ),
				)
			)
		);
	}

	/**
	 * Credential sets that must not authenticate.
	 *
	 * @return array
	 */
	public function rejected_credentials() {
		return array(
			'no credentials'     => array( array() ),
			'user only'          => array( array( 'user' => 'bgbkup_rx_user' ) ),
			'pass only'          => array( array( 'pass' => base64_encode( 'correct-pass' ) ) ),
			'empty user'         => array(
				array(
					'user' => '',
					'pass' => base64_encode( 'correct-pass' ),
				),
			),
			'empty pass'         => array(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => '',
				),
			),
			'both empty'         => array(
				array(
					'user' => '',
					'pass' => '',
				),
			),
			'array user'         => array(
				array(
					'user' => array( 'bgbkup_rx_user' ),
					'pass' => base64_encode( 'correct-pass' ),
				),
			),
			'array pass'         => array(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => array( base64_encode( 'correct-pass' ) ),
				),
			),
			'pass decodes empty' => array(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => '====',
				),
			),
			'wrong pass'         => array(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => base64_encode( 'wrong-pass' ),
				),
			),
			'unencoded pass'     => array(
				array(
					'user' => 'bgbkup_rx_user',
					'pass' => 'correct-pass',
				),
			),
		);
	}

	/**
	 * Run authenticate_request() as a logged-out visitor.
	 *
	 * @param array $params Request parameters.
	 * @return bool
	 */
	private function authenticate( $params ) {
		add_filter( 'send_auth_cookies', '__return_false' );
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$rx     = new Boldgrid_Backup_Admin_Migrate_Rx_Rest( $this->migrate_core );
		$result = $rx->authenticate_request( $request );

		remove_filter( 'send_auth_cookies', '__return_false' );

		return $result;
	}

	/**
	 * Create the test user.
	 *
	 * This class does not run inside a rolled-back transaction, so a user from
	 * an earlier test may still exist.
	 *
	 * @param string $role Role.
	 */
	private function create_auth_user( $role ) {
		$existing = get_user_by( 'login', 'bgbkup_rx_user' );
		if ( $existing ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $existing->ID );
		}

		self::factory()->user->create(
			array(
				'user_login' => 'bgbkup_rx_user',
				'user_pass'  => 'correct-pass',
				'role'       => $role,
			)
		);
	}
}
