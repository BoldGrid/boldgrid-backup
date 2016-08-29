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
		// Require dependent files.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-config.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-core.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cron.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-notice.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-settings.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-test.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-update.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-xhprof.php';

		// Instantiate the admin core.
		$plugin_admin_core = new Boldgrid_Backup_Admin_Core();

		// Delete cron jobs for backup tasks.
		$plugin_admin_core->cron->delete_cron_entries();
	}
}
