<?php
/**
 * File: class-boldgrid-backup-admin-transfers.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Transfers
 *
 * @since 1.11.0
 */
class Boldgrid_Backup_Admin_Transfers {
	/**
	 * The core class object.
	 *
	 * @since 1.11.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Render the transfers page.
	 *
	 * @since 1.11.0
	 */
	public function page() {
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-transfers.php';
	}
}
