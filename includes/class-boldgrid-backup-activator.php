<?php
/**
 * Fired during plugin activation
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @author BoldGrid.com <wpb@boldgrid.com>
 */
class Boldgrid_Backup_Activator {

	/**
	 * Plugin activation.
	 *
	 * @since 1.0
	 */
	public static function activate() {
		// Instantiate the admin core.
		$plugin_admin_core = new Boldgrid_Backup_Admin_Core();

		// Add cron job if needed.
		$plugin_admin_core->settings->add_cron_entry();
	}
}
