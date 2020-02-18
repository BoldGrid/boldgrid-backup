<?php
/**
 * File: class-boldgrid-backup-admin-auto-updates.php
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    SINCEVERSION
 * @author     BoldGrid <support@boldgrid.com>
 */

class Boldgrid_Backup_Admin_Auto_Updates {
	/**
	 * Settings
	 *
	 * @since SINCEVERSION
	 * var array
	*/
	public $settings;

	/**
	 * Active Plugins
	 */
	public $plugins = [];

	public function __construct() {

		$this->set_settings();

		$this->set_plugins();

		add_filter( 'automatic_updater_disabled', '__return_false' );
		// // add_filter( 'auto_update_plugin', array( $this, 'set_update_plugins' ), 10, 2 );

		// $this->set_update_plugins();
	}

	public function set_settings() {
		$boldgrid_backup_settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( isset( $boldgrid_backup_settings['auto_update'] ) ) {
			$this->settings = $boldgrid_backup_settings['auto_update'];
		} else {
			$this->settings = [];
		}
	}

	public function set_plugins() {
		$this->plugins = \Boldgrid\Library\Library\Plugin\Plugins::getAllActivePlugins();
	}

	public function maybe_update_plugin( $slug ) {
		$core = appy_filters('boldgrid_backup_get_core', null);
		$days_to_wait = $this->settings['days'];
		$plugin = \Boldgrid\Library\Library\Plugin\Plugins::getActivePluginBySlug( $this->plugins, $slug );
		$days_since_release = $plugin->updateData->days;
		$plugin_update_enabled = (bool) $this->settings['plugins'][ $plugin->getFile() ];
		
		//if premium, check the days since it was released, if not premium then this is true.
		if ( $core->config->is_premium_done ) {
			$is_update_time = ( $days_since_release >= $days_to_wait );
		} else {
			$is_update_time = true;
		}
		
		if ( $days_since_release >= $days_to_wait && true === $plugin_update_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	public function set_update_plugins() {
		foreach ( $this->plugins as $plugin ) {
			error_log( serialize( $will_update ) );
			$will_update = $this->maybe_update_plugin( $plugin->getSlug() );
			if ( true === $will_update ) {
				$will_update = apply_filters( 'auto_update_plugin', true, $plugin->getSlug() );
				error_log( $plugin->getSlug() . ' Will Update: ' . serialize( $will_update ) );
			} else {
				$will_update = apply_filters( 'auto_update_plugin', false, $plugin->getSlug() );
				error_log( $plugin->getSlug() . ' Will Update: ' . serialize( $will_update ) );
			}
		}
		
		return $will_update;
	}

	function auto_update_plugins ( $update, $item ) {
		// Array of plugin slugs to always auto-update
		$plugins = [];
		foreach ( $this->plugins as $plugin ) {
			if ( $this->maybe_update_plugin( $plugin->getSlug() ) ) {
				$plugins[] = $plugin->getSlug();
			}
		}
		if ( in_array( $item->slug, $plugins ) ) {
			// Always update plugins in this array
			error_log( $item->slug . ' Will Update');
			return true;
		} else {
			// Else, Do Not Update Plugin
			error_log( $item->slug . ' Will Not Update');
			return false;
		}
	}
}