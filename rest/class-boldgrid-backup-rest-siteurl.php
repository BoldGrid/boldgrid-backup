<?php
/**
 * File: class-boldgrid-backup-rest-siteurl.php
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
 * Class: Boldgrid_Backup_Rest_Siteurl
 *
 * REST endpoint for the siteurl.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Siteurl extends Boldgrid_Backup_Rest_Controller {
	/**
	 * Resource name.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    string
	 */
	protected $resource = 'siteurl';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since SINCEVERSION
	 */
	public function register_routes() {
		$this->register_get();
		$this->register_update();
	}

	/**
	 * Register the route for creating a backup.
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
	 * Register router for updating settings.
	 *
	 * @since SINCEVERSION
	 */
	public function register_update() {
		register_rest_route( $this->namespace, '/' . $this->resource, [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
				'args'                => array(
					'siteurl' => array(
						'required'            => true,
						'description'         => esc_html__( 'New site url.', 'boldgrid-backup' ),
						'type'                => 'string',
						'sanitation_callback' => function ( $field ) {
							return esc_url_raw( $field );
						},
					),
				),
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
	 * Get schema for settings.
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
				'home'        => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Home.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'siteurl'     => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Siteurl.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'old_home'    => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Old home (before changing).', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'old_siteurl' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Old siteurl (before changing).', 'boldgrid-backup' ),
					'type'        => 'string',
				],
			],
		];

		return $schema;
	}

	/**
	 * Get the users plugin settings.
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Plugin settings.
	 */
	public function get_item( $request ) {
		return $this->prepare_item_for_response( array(
			'home'    => get_option( 'home' ),
			'siteurl' => get_option( 'siteurl' ),
		), $request );
	}

	/**
	 * Update a site url via a REST call.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return array                    Updated Settings.
	 */
	public function update_item( $request ) {
		$old_home    = get_option( 'home' );
		$old_siteurl = get_option( 'siteurl' );

		// Get the new site url.
		$siteurl = $request->get_param( 'siteurl' );

		Boldgrid_Backup_Admin_Utility::update_siteurl( array(
			'old_siteurl' => $old_siteurl,
			'siteurl'     => $siteurl,
			'flush'       => true,
		) );

		return $this->prepare_item_for_response( array(
			'old_home'    => $old_home,
			'old_siteurl' => $old_siteurl,
			'home'        => get_option( 'home' ),
			'siteurl'     => get_option( 'siteurl' ),
		), $request );
	}
}
