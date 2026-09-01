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
				'passed'            => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Whether or not the site passed the preflight check.', 'boldgrid-backup' ),
					'type'        => 'bool',
				],
				'php_version'       => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'PHP Version', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'wordpress_version' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'WordPress Version', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'disk_database'     => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Size of the database', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'abspath'           => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'ABSPATH Version', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'disk_abspath'      => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Size of ABSPATH', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'disk_total'        => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Home dir total disk.', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'disk_used'         => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Home dir total disk used.', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'disk_free'         => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Home dir total disk free (total - used).', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'disk_post_backup'  => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Estimated disk sapce after backup (free - abspath - db).', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
			],
		];

		return $schema;
	}

	/**
	 * Get the the preflight check results.
	 *
	 * Originally built to show just the pass / fail status, but has since been updated to show
	 * more details (such as php version, disk sizes, etc).
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Preflight check results.
	 */
	public function get_item( $request ) {
		global $wp_version;

		$preflight_test = new Boldgrid_Backup_Admin_Test( $this->core );

		$disk_space = $preflight_test->get_disk_space( false );
		$disk_wp    = $preflight_test->get_wp_size();
		$disk_db    = $preflight_test->get_database_size();

		$settings = array(
			'passed'            => $preflight_test->run_functionality_tests(),
			'php_version'       => phpversion(),
			'wordpress_version' => $wp_version,
			'abspath'           => ABSPATH,
			'disk_abspath'      => $disk_wp,
			'disk_database'     => $disk_db,
			'disk_total'        => $disk_space[0],
			'disk_used'         => $disk_space[1],
			'disk_free'         => $disk_space[2],
			'disk_post_backup'  => $disk_space[2] - $disk_wp - $disk_db,
		);

		return $this->prepare_item_for_response( $settings, $request );
	}
}
