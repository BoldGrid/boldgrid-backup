<?php
/**
 * File: class-boldgrid-backup-admin-auto-updates-logger.php
 *
 * @since      1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Auto_Updates_Logger
 *
 * @since 1.8.0
 */
class Boldgrid_Backup_Admin_Auto_Updates_Logger {
	public $core;
	public $log;

	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
		$this->log  = new Boldgrid_Backup_Admin_Log( $this->core );
		$this->log->init( 'auto-update.log' );

		add_action( 'upgrader_process_complete', array( $this, 'log_update' ), 10, 2 );
	}

	/**
	 * Log update information.
	 *
	 * @param WP_Upgrader $upgrader_object
	 * @param array       $options
	 */
	public function log_update( $upgrader_object, $options ) {
		if ( isset( $options['action'] ) && 'update' === $options['action'] ) {
			$type    = isset( $options['type'] ) ? $options['type'] : '';
			$plugins = isset( $options['plugins'] ) ? $options['plugins'] : array();
			$themes  = isset( $options['themes'] ) ? $options['themes'] : array();

			// Check if the update was successful.
			if ( 'success' !== $options['result'] ) {
				$this->log->add(
					'Auto update failed for' . $type
					. ":\nUpgraderObject: " . wp_json_encode( $upgrader_object )
					. "\nResults: " . wp_json_encode( $options )
				);
				return;
			}

			// Check if the update was for a plugin.
			if ( 'plugin' === $type ) {
				$plugin         = $upgrader_object->skin->plugin_info();
				$plugin_name    = $plugin['Name'];
				$plugin_version = $plugin['Version'];
				$this->log->add(
					'Auto update for plugin ' . $plugin_name
					. ' to version ' . $plugin_version . ' was successful.'
				);
			}

			// Check if the update was for a theme.
			if ( 'theme' === $type ) {
				$theme         = $upgrader_object->skin->theme_info();
				$theme_name    = $theme['Name'];
				$theme_version = $theme['Version'];
				$this->log->add(
					'Auto update for theme ' . $theme_name
					. ' to version ' . $theme_version . ' was successful.'
				);
			}

			// Check if the update was for core.
			if ( 'core' === $type ) {
				// check if is it is minor, major, translation, or dev update
				$core_version = $upgrader_object->skin->result['new_version'];
				$this->log->add(
					'Auto update for core to version '
					. $core_version . ' was successful.'
				);
			}
		}
	}
}

new Boldgrid_Backup_Admin_Auto_Updates_Logger();
