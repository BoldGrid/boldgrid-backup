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
		$core = new Boldgrid_Backup_Admin_Core();
		$settings = $core->settings->get_settings();
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
