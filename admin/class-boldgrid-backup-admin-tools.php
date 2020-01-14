<?php
/**
 * File: class-boldgrid-backup-admin-tools.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Tools
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Tools {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 *
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'total-upkeep_page_boldgrid-backup-tools' !== $hook ) {
			return;
		}

		// Load classes for the UI class. As of @since 1.12.5, moved from self::page to here.
		wp_enqueue_style( 'bglib-ui-css' );
		wp_enqueue_script( 'bglib-ui-js' );
		wp_enqueue_script( 'bglib-sticky' );

		// Add thickbox functionality. Initially added to support viewing log files.
		add_thickbox();
	}

	/**
	 * Render the tools page.
	 *
	 * @since 1.6.0
	 */
	public function page() {
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-tools.php';
	}
}
