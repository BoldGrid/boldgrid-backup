<?php
/**
 * File: class-boldgrid-backup-rest-setting.php
 *
 * @link       https://www.boldgrid.com
 * @since      X.X.X
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Rest_Setting
 *
 * REST endpoints to access the backup settings.
 *
 * @since X.X.X
 */
class Boldgrid_Backup_Rest_Setting extends Boldgrid_Backup_Rest_Controller {

	/**
	 * Resource name.
	 *
	 * @since  X.X.X
	 * @access private
	 * @var    string
	 */
	protected $resource = 'settings';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since X.X.X
	 */
	public function register_routes() {
		$this->register_get();
	}

	/**
	 * Register the route for creating a backup.
	 *
	 * @since X.X.X
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
	 * @since X.X.X
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
	 * @since X.X.X
	 *
	 * @return array Schema Format.
	 */
	public function get_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->resource,
			'type'       => 'object',
			'properties' => [
				'schedule'                 => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Date the backup was created.', 'boldgrid-backup' ),
					'type'        => 'array',
				],
				'notification_email'       => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Email to notify got backups.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'auto_backup'              => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Does a site have auto backups enabled?', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'auto_backup'              => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Does a site have auto rollback enabled?', 'boldgrid-backup' ),
					'type'        => 'integer',
				],
				'folder_exclusion_include' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Directories and files to include.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
				'folder_exclusion_exclude' => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Directories and files to exclude.', 'boldgrid-backup' ),
					'type'        => 'string',
				],
			],
		];

		return $schema;
	}

	/**
	 * Get the users plugin settings.
	 *
	 * @since X.X.X
	 *
	 * @return array Plugin settings.
	 */
	public function get_item( $request ) {
		$settings = get_option( 'boldgrid_backup_settings', [] );
		return $this->prepare_item_for_response( $settings, $request );
	}
}
