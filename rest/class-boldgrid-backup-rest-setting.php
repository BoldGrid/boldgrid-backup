<?php
/**
 * File: class-boldgrid-backup-rest-setting.php
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
 * Class: Boldgrid_Backup_Rest_Setting
 *
 * REST endpoints to access the backup settings.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Setting extends Boldgrid_Backup_Rest_Controller {

	/**
	 * Resource name.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    string
	 */
	protected $resource = 'settings';

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
				'schedule'                 => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Date the backup was created.', 'boldgrid-backup' ),
					'type'        => 'array',
				],
				'autoupdate'               => [
					'context'     => [ 'view' ],
					'description' => esc_html__( 'Automatic Update.', 'boldgrid-backup' ),
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
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Plugin settings.
	 */
	public function get_item( $request ) {
		$settings          = get_option( 'boldgrid_backup_settings', [] );
		$boldgrid_settings = get_option( 'boldgrid_settings' );

		$settings['autoupdate'] = isset( $boldgrid_settings['autoupdate'] ) ? $boldgrid_settings['autoupdate'] : null;

		return $this->prepare_item_for_response( $settings, $request );
	}

	/**
	 * Update settings through API.
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Updated Settings.
	 */
	public function update_item( $request ) {
		$schema             = $this->get_schema();
		$settings           = get_option( 'boldgrid_backup_settings', [] );
		$requested_settings = $request->get_param( 'settings' );

		foreach ( $schema['properties'] as $name => $value ) {
			if ( isset( $requested_settings[ $name ] ) ) {
				$settings[ $name ] = $requested_settings[ $name ];
			}
		}

		$scheduler             = new Boldgrid_Backup_Admin_Scheduler( $this->core );
		$settings['scheduler'] = $scheduler->get();

		$admin_settings = new Boldgrid_Backup_Admin_Settings( $this->core );
		$settings       = $admin_settings->update_cron( $settings );

		// Update Settings.
		update_option( 'boldgrid_backup_settings', $settings );

		// Update the auto update setting.
		if ( ! empty( $requested_settings['autoupdate'] ) ) {
			$boldgrid_settings               = get_option( 'boldgrid_settings' );
			$boldgrid_settings['autoupdate'] = $requested_settings['autoupdate'];
			update_option( 'boldgrid_settings', $boldgrid_settings );
		}

		return $this->get_item( $request );
	}
}
