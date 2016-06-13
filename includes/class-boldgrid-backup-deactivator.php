<?php
/**
 * Fired during plugin deactivation
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @author BoldGrid.com <wpb@boldgrid.com>
 */
class Boldgrid_Backup_Deactivator {

	/**
	 * Plugin deactivation.
	 *
	 * @since 1.0
	 */
	public static function deactivate() {
		// Instantiate the admin core.
		$plugin_admin_core = new Boldgrid_Backup_Admin_Core();

		// Delete cron jobs for backup tasks.
		$plugin_admin_core->settings->delete_cron_entries();
	}
}
