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
		$core = new Boldgrid_Backup_Admin_Core();

		$core->cron->delete_cron_entries( true );
		$core->wp_cron->clear_schedules();
	}
}
