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
	 * The name of the option that signifies this plugin was just activated.
	 *
	 * The option is meant to be read immediately following plugin activation, since that is when
	 * this plugin can actually take action.
	 *
	 * @since xxx
	 * @var string
	 */
	public static $option = 'boldgrid_backup_activate';

	/**
	 * Whether or not the plugin was just activated.
	 *
	 * This property is meant to track if we're within the process of activating the plugin right
	 * this second, as in we're within the "register_activation_hook". It's a little different than
	 * our static $option value.
	 *
	 * @since xxx
	 * @var bool
	 */
	public static $just_activated = false;

	/**
	 * Plugin activation.
	 *
	 * This method is ran via register_activation_hook.
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
		// Flag that the plugin has just been activated.
		update_option( self::$option, 1 );
		self::$just_activated = true;

		if ( Boldgrid_Backup_Admin_Test::is_filesystem_supported() ) {
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

	/**
	 * Determine whether or not we just activated the plugin.
	 *
	 * For example, this should return true when on wp-admin/plugins.php and it says "Plugin activated".
	 *
	 * @since xxx
	 *
	 * @return bool
	 */
	public function on_post_activate() {
		return ! self::$just_activated && '1' === get_option( self::$option );
	}

	/**
	 * Display admin notices immediately after activating the plugin.
	 *
	 * @since xxx
	 */
	public function post_activate_notice() {
		if ( $this->on_post_activate() ) {
			$core = apply_filters( 'boldgrid_backup_get_core', null );

			echo '<div class="notice notice-success">
					<h2>' . esc_html__( 'Thank you for installing BoldGrid Backup!', 'boldgrid-backup' ) . '</h2>
					<p>' . esc_html__( 'To get started, we recommend going to your BoldGrid Backup Settings page and configuring scheduled backups.', 'boldgrid-backup' ) . '</p>
					<p><a href="' . esc_url( $core->settings->get_settings_url() ) . '" class="button button-primary">' . esc_html__( 'Configure BoldGrid Backup Now', 'boldgrid-backup' ) . '</a></p>
				</div>';
		}
	}

	/**
	 * Shutdown action.
	 *
	 * @since xxx
	 */
	public function shutdown() {
		// Delete the option that signifies we just activated BoldGrid Backup.
		if ( ! self::$just_activated ) {
			delete_option( self::$option );
		}
	}
}
