<?php
/**
 * File: class-boldgrid-backup-deactivator.php
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Deactivator
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

		$core->cron->delete_cron_entries( 'all' );
		$core->wp_cron->clear_schedules();
	}
}
