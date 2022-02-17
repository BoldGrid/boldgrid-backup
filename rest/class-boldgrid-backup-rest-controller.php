<?php
/**
 * File: class-boldgrid-backup-rest-controller.php
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
 * Class: Boldgrid_Backup_Rest_Controller
 *
 * A base class for the wp rest controller.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Controller extends WP_REST_Controller {

	/**
	 * The core class object.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	protected $core;

	/**
	 * Namespace of the class.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    string
	 */
	protected $namespace = 'bgbkup/v1';

	/**
	 * Setup the core backup class.
	 *
	 * @since SINCEVERSION
	 * @param Boldgrid_Backup_Admin_Core $core Core Backup class.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Ensure user has access to any of the archive endpoints.
	 *
	 * @since SINCEVERSION
	 *
	 * @return boolean Has Access?
	 */
	public function permission_check() {
		return current_user_can( 'activate_plugins' );
	}

	/**
	 * Make sure that an item only has the items present in the schema.
	 *
	 * @since SINCEVERSION
	 *
	 * @param array $item Resource Item.
	 * @return array      Updated resource item.
	 */
	protected function filter_schema_properties( $item ) {
		$resource = [];
		$schema   = $this->get_schema();
		foreach ( $schema['properties'] as $name => $property ) {
			if ( isset( $item[ $name ] ) ) {
				$resource[ $name ] = $item[ $name ];
			} else {
				$resource[ $name ] = 'array' === $property['type'] ? [] : null;
			}
		}

		return $resource;
	}

}
