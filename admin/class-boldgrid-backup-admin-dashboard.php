<?php
/**
 * File: class-boldgrid-backup-admin-dashboard.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
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
 * @since 1.11.0
 */
class Boldgrid_Backup_Admin_Dashboard {
	/**
	 * The core class object.
	 *
	 * @since  1.11.0
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
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.11.0
	 *
	 * @param string $hook Hook name.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'toplevel_page_boldgrid-backup-dashboard' === $hook ) {
			wp_enqueue_style(
				'boldgrid-backup-admin-dashboard',
				plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-dashboard.css',
				array(),
				BOLDGRID_BACKUP_VERSION
			);
		}
	}

	/**
	 * Get cards needed for the dashboard.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_cards() {
		$cards = [
			new \Boldgrid\Backup\Admin\Card\Premium(),
			new \Boldgrid\Backup\Admin\Card\Backups(),
			new \Boldgrid\Backup\Admin\Card\Updates(),
		];

		return $cards;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.11.0
	 */
	public function page() {
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-dashboard.php';
	}
}
