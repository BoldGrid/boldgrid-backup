<?php
/**
 * File: class-boldgrid-backup-cron-helper.php
 *
 * @link https://www.boldgrid.com
 * @since 1.6.5
 *
 * @package Boldgrid_Backup
 * @copyright BoldGrid
 * @version $Id$
 * @author BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Cron_Helper
 *
 * @since 1.6.5
 */
class Boldgrid_Backup_Cron_Helper {

	/**
	 * Determine if we are in the cli.
	 *
	 * @since 1.6.5
	 *
	 * @return bool
	 */
	public function is_cli() {
		return isset( $_SERVER['argv'], $_SERVER['argc'] ) || $_SERVER['argc']; // phpcs:ignore
	}
}
