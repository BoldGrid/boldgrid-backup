<?php
/**
 * File: class-boldgrid-backup-admin-auto-updates.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.14.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    1.14.0
 * @author     BoldGrid <support@boldgrid.com>
 */

use Boldgrid\Library\Library\Plugin\Plugins;

/**
 * Class: Boldgrid_Backup_Admin_Auto_Updates.
 *
 * @since 1.14.0
 */
class Boldgrid_Backup_Admin_Auto_Updates {
	/**
	 * Settings.
	 *
	 * @since 1.14.0
	 * @var array
	 */
	public $settings;

	/**
	 * Active Plugins.
	 *
	 * @since 1.14.0
	 * @var array
	 */
	public $plugins = array();

	/**
	 * Themes.
	 *
	 * @since 1.14.0
	 * @var array
	 */
	public $themes = array();

	/**
	 * Active Plugins.
	 *
	 * @since 1.14.0
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {

		$this->set_settings();
		$plugins       = new \Boldgrid\Library\Library\Plugin\Plugins();
		$this->plugins = $plugins->getAllPlugins();
		$this->themes  = new \Boldgrid\Library\Library\Theme\Themes();
		add_filter( 'automatic_updater_disabled', '__return_false' );
	}

	/**
	 * Set Settings.
	 *
	 * @since 1.14.0
	 */
	public function set_settings() {
		$boldgrid_backup_settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( isset( $boldgrid_backup_settings['auto_update'] ) ) {
			$this->settings = $boldgrid_backup_settings['auto_update'];
		} else {
			$this->settings = array(
				'days' => 0,
			);
		}
	}

	/**
	 * Get Days.
	 *
	 * @since 1.14.0
	 *
	 * @return int
	 */
	public function get_days() {
		if ( empty( $this->settings['timely-updates-enabled'] ) || empty( $this->settings['days'] ) ) {
			return 0;
		} else {
			return $this->settings['days'];
		}
	}

	/**
	 * Maybe Update Plugin.
	 *
	 * @since 1.14.0
	 *
	 * @param string $slug Plugin Slug.
	 * @return bool
	 */
	public function maybe_update_plugin( $slug ) {
		$days_to_wait = $this->get_days();
		$plugin       = \Boldgrid\Library\Library\Plugin\Plugins::getBySlug( $this->plugins, $slug );
		$plugin->setUpdateData();

		$days_since_release    = $plugin->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$plugin_update_enabled = array_key_exists( $plugin->getFile(), $this->settings['plugins'] ) ? (bool) $this->settings['plugins'][ $plugin->getFile() ] : (bool) $this->settings['plugins']['default'];
		$is_update_time        = ( $days_since_release >= $days_to_wait );
		if ( $is_update_time && true === $plugin_update_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Maybe Update Theme.
	 *
	 * @since 1.14.0
	 *
	 * @param string $stylesheet Theme's Stylesheet.
	 * @return bool
	 */
	public function maybe_update_theme( $stylesheet ) {
		$days_to_wait = $this->get_days();
		$theme        = $this->themes->getFromStylesheet( $stylesheet );
		$theme->setUpdateData();
		$days_since_release   = $theme->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$theme_update_enabled = isset( $this->settings['themes'][ $stylesheet ] ) ? (bool) $this->settings['themes'][ $stylesheet ] : (bool) $this->settings['plugins']['default'];
		$is_update_time       = ( $days_since_release >= $days_to_wait );

		if ( $is_update_time && true === $theme_update_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Auto Update Plugins.
	 *
	 * This method is the callback for the 'auto_update_plugin' action hook.
	 *
	 * @since 1.14.0
	 *
	 * @param  bool     $update Whether or not to update.
	 * @param  stdClass $item The item class passed to callback.
	 * @return bool
	 */
	public function auto_update_plugins( $update, stdClass $item ) {
		// Array of plugin slugs to always auto-update.
		$plugins = array();
		foreach ( $this->plugins as $plugin ) {
			if ( $this->maybe_update_plugin( $plugin->getSlug() ) ) {
				$plugins[] = $plugin->getSlug();
			}
		}
		if ( in_array( $item->slug, $plugins, true ) ) {
			// Always update plugins in this array.
			return true;
		} else {
			// Else, Do Not Update Plugin.
			return false;
		}
	}

	/**
	 * Auto Update Themes.
	 *
	 * This method is the callback for the 'auto_update_theme' action hook.
	 *
	 * @since 1.14.0
	 *
	 * @param bool     $update Whether or not to update.
	 * @param stdClass $item The item class passed to callback.
	 * @return bool
	 */
	public function auto_update_themes( $update, stdClass $item ) {
		// Array of theme stylesheets to always auto-update.
		$themes = array();
		foreach ( $this->themes->get() as $theme ) {
			if ( $this->maybe_update_theme( $theme->stylesheet ) ) {
				$themes[] = $theme->stylesheet;
			}
		}
		if ( in_array( $item->theme, $themes, true ) ) {
			// Always update themes in this array.
			return true;
		} else {
			// Else, Do Not Update theme.
			return false;
		}
	}

	/**
	 * Auto Update Core.
	 *
	 * Sets the type of updates to perform for wpcore.
	 *
	 * @since 1.14.0
	 */
	public function auto_update_core() {
		$wpcs_default = array(
			'all'         => false,
			'minor'       => true,
			'major'       => false,
			'translation' => false,
			'dev'         => false,
		);

		$wpcs = isset( $this->settings['wpcore'] ) ? $this->settings['wpcore'] : $wpcs_default;
		if ( $wpcs['all'] ) {
			add_filter( 'auto_update_core', '__return_true' );
		}

		$dev         = ( $wpcs['dev'] ) ? 'true' : 'false';
		$major       = ( $wpcs['major'] ) ? 'true' : 'false';
		$minor       = ( $wpcs['minor'] ) ? 'true' : 'false';
		$translation = ( $wpcs['translation'] ) ? 'true' : 'false';

		add_filter( 'allow_major_auto_core_updates', '__return_' . $major );
		add_filter( 'allow_minor_auto_core_updates', '__return_' . $minor );
		add_filter( 'auto_update_translation', '__return_' . $translation );
		add_filter( 'allow_dev_auto_core_updates', '__return_' . $dev );
	}
}
