<?php
/**
 * File: class-boldgrid-backup-rest-job.php
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
 * Class: Boldgrid_Backup_Rest_Job
 *
 * REST endpoints to perform get jobs.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Job extends Boldgrid_Backup_Rest_Controller {

	/**
	 * Resource name.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    string
	 */
	protected $resource = 'jobs';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since SINCEVERSION
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->resource . '/(?P<id>[\w-]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
				'args'                => [
					'id' => [
						'required'    => true,
						'context'     => [ 'view' ],
						'description' => esc_html__( 'Unique identifier for the object.', 'boldgrid-backup' ),
						'type'        => 'string',
					],
				],
			],
			'schema' => [ $this, 'get_schema' ],
		] );
	}

	/**
	 * Get our sample schema for comments.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array Schema Format.
	 */
	public function get_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'job',
			'type'       => 'object',
			'properties' => [
				'id'           => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Unique identifier for the object.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'type'         => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Type of job.', 'boldgrid-backup' ),
					'type'        => 'string',
					'enum'        => [
						'backup',
						'restoration',
					],
				],
				'status'       => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Currently status of the job.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'started_at'   => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Information attached to the start of the prcoess.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'completed_at' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Results of the process.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
			],
		];

		return $schema;
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
	 * Get one item from the collection.
	 *
	 * Example call:
	 * jQuery.get( 'https://domain/wp-json/bgbkup/v1/jobs/<JOB-ID>' );
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id = $request->get_param( 'id' );

		$task  = new Boldgrid_Backup_Admin_Task();
		$found = $task->init_by_id( $id );

		$task->date_format = 'c';

		$item = $found ? $task->get() : null;
		if ( ! empty( $item ) ) {
			$data = $this->prepare_item_for_response( $item, $request );
			return new WP_REST_Response( $data, 200 );
		} else {
			return new WP_Error( 'no_job', esc_html__( 'Job Not found', 'boldgrid-backup' ), [
				'status' => 404,
			] );
		}
	}
}
