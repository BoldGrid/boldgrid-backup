<?php
/**
 * File: class-boldgrid-backup-activator.php
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
 * Class: Boldgrid_Backup_Activator
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Activator {
	/**
	 * Plugin activation.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @see Boldgrid_Backup_Admin_Core()
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 * @see Boldgrid_Backup_Admin_Cron::add_all_crons()
	 */
	public static function activate() {
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-support.php';
		$support = new Boldgrid_Backup_Admin_Support();

		if ( $support->is_filesystem_supported() ) {
			$core      = new Boldgrid_Backup_Admin_Core();
			$settings  = $core->settings->get_settings();
			$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;

			/*
			 * Add all previous crons.
			 *
			 * The add_all_crons methods called include proper checks to ensure
			 * scheduler is available and $settings include a schedule.
			 */
			if ( 'cron' === $scheduler ) {
				$core->cron->add_all_crons( $settings );
			} elseif ( 'wp-cron' === $scheduler ) {
				$core->wp_cron->add_all_crons( $settings );
			}
		}
	}
}
