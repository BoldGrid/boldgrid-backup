<?php
/**
 * File: class-boldgrid-backup-rest-test.php
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Rest_Test
 *
 * REST endpoints to access the backup preflight check results.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Test extends Boldgrid_Backup_Rest_Controller {

	/**
	 * Resource name.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    string
	 */
	protected $resource = 'test';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since SINCEVERSION
	 */
	public function register_routes() {
		$this->register_get();
	}

	/**
	 * Register the route for getting test results.
	 *
	 * @since SINCEVERSION
	 */
	public function register_get() {
		register_rest_route( $this->namespace, '/' . $this->resource, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			'schema' => [ $this, 'get_schema' ],
		] );
	}

	/**
	 * Prepare the item for the REST response.
	 *
	 * @since SINCEVERSION
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return $this->filter_schema_properties( $item );
	}

	/**
	 * Get schema for results.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array Schema Format.
	 */
	public function get_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->resource,
			'type'       => 'object',
			'properties' => [
				'passed' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Whether or not the site passed the preflight check.', 'boldgrid-backup' ),
					'type'        => 'bool',
				],
			],
		];

		return $schema;
	}

	/**
	 * Get the the preflight check results.
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Preflight check results.
	 */
	public function get_item( $request ) {
		$preflight_test     = new Boldgrid_Backup_Admin_Test( $this->core );
		$settings['passed'] = $preflight_test->run_functionality_tests();
		return $this->prepare_item_for_response( $settings, $request );
	}
}
