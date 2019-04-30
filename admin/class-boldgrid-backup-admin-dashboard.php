<?php
/**
 * File: class-boldgrid-backup-admin-dashboard.php
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Dashboard
 *
 * @since xxx
 */
class Boldgrid_Backup_Admin_Dashboard {
	/**
	 * The core class object.
	 *
	 * @since  xxx
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since xxx
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 *
	 */
	public function page() {
		wp_enqueue_style( 'bglib-ui-css' );
		// wp_enqueue_script( 'bglib-ui-js' );
		// wp_enqueue_script( 'bglib-sticky' );

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-dashboard.php';
	}
}
