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

/**
 * Class: Boldgrid_Backup_Admin_Auto_Updates
 *
 * @since 1.2
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
		public $themes;

		public $core;

	/**
	 * Constructor
	 *
	 * @since SINCEVERSION
	 *
	 */
	public function __construct() {

		$this->set_settings();

		$this->plugins = \Boldgrid\Library\Library\Plugin\Plugins::getAllActivePlugins();
		$this->themes  = new \Boldgrid\Library\Library\Theme\Themes();

		add_filter( 'automatic_updater_disabled', '__return_false' );
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_plugins' ), 10, 2 );
		add_filter( 'auto_update_themes', array( $this, 'auto_update_themes' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'auto_update_core' ) );
	}

	/**
	 * Constructor
	 *
	 * @since SINCEVERSION
	 *
	 * @param WP_Theme
	 */
	public function is_premium_done() {
		$license        = new \Boldgrid\Library\Library\License();
		$is_premium     = $license->isPremium( 'boldgrid-backup' );
		$premium_plugin = 'boldgrid-backup-premium/boldgrid-backup-premium.php';
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$premium_active = in_array( $premium_plugin, $active_plugins, true ) || is_plugin_active_for_network( $premium_plugin );
		return ( $is_premium && $premium_active );
	}

	/**
	 * Set Settings
	 *
	 * @since SINCEVERSION
	 */
	public function set_settings() {
		$boldgrid_backup_settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( isset( $boldgrid_backup_settings['auto_update'] ) ) {
			$this->settings = $boldgrid_backup_settings['auto_update'];
		} else {
			$this->settings = [];
		}
	}

	/**
	 * Maybe Update Plugin
	 *
	 * @since SINCEVERSION
	 *
	 * @param $string $slug
	 * @return bool
	 */
	public function maybe_update_plugin( $slug ) {
		$days_to_wait          = $this->settings['days'];
		$plugin                = \Boldgrid\Library\Library\Plugin\Plugins::getActivePluginBySlug( $this->plugins, $slug );
		$days_since_release    = $plugin->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$plugin_update_enabled = (bool) $this->settings['plugins'][ $plugin->getFile() ];

		//if premium, check the days since it was released, if not premium then this is true.
		if ( $this->is_premium_done() ) {
			$is_update_time = ( $days_since_release >= $days_to_wait );
		} else {
			$is_update_time = true;
		}

		if ( $is_update_time && true === $plugin_update_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Maybe Update Theme
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $stylesheet
	 * @return bool
	 */
	public function maybe_update_theme( $stylesheet ) {
		$days_to_wait         = $this->settings['days'];
		$theme                = $this->themes->getFromStylesheet( $stylesheet );
		$days_since_release   = $theme->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$theme_update_enabled = (bool) $this->settings['themes'][ $stylesheet ];
		//if premium, check the days since it was released, if not premium then this is true.
		if ( $this->is_premium_done() ) {
			$is_update_time = ( $days_since_release >= $days_to_wait );
		} else {
			$is_update_time = true;
		}

		if ( $is_update_time && true === $theme_update_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Auto Update Plugins
	 *
	 * This method is the callback for the 'auto_update_plugin' action hook
	 *
	 * @since SINCEVERSION
	 *
	 * @param bool $update
	 * @param stdClass $item
	 * @return bool
	 */
	public function auto_update_plugins( $update, stdClass $item ) {
		// Array of plugin slugs to always auto-update
		$plugins = [];
		foreach ( $this->plugins as $plugin ) {
			if ( $this->maybe_update_plugin( $plugin->getSlug() ) ) {
				$plugins[] = $plugin->getSlug();
			}
		}
		if ( in_array( $item->slug, $plugins, true ) ) {
			// Always update plugins in this array
			return true;
		} else {
			// Else, Do Not Update Plugin
			return false;
		}
	}

	/**
	 * Auto Update Themes
	 *
	 * This method is the callback for the 'auto_update_theme' action hook
	 *
	 * @since SINCEVERSION
	 *
	 * @param bool $update
	 * @param stdClass $item
	 * @return bool
	 */
	public function auto_update_themes( $update, stdClass $item ) {
		// Array of theme stylesheets to always auto-update
		$themes = [];
		foreach ( $this->themes->list() as $theme ) {
			if ( $this->maybe_update_plugin( $theme->stylesheet ) ) {
				$themes[] = $theme->stylesheet;
			}
		}
		if ( in_array( $item->theme, $themes, true ) ) {
			// Always update themes in this array
			return true;
		} else {
			// Else, Do Not Update theme
			return false;
		}
	}

	/**
	 * Auto Update Core
	 *
	 * Sets the type of updates to perform for wpcore.
	 *
	 * @since SINCEVERSION
	 */
	public function auto_update_core() {
		$wpcs = $this->settings['wpcore'];
		if ( $wpcs['all'] ) {
			add_filter( 'auto_update_core', '__return_true' );
		}

		$dev         = ( $wpcs['dev'] ) ? 'true' : 'false';
		$major       = ( $wpcs['major'] ) ? 'true' : 'false';
		$minor       = ( $wpcs['minor'] ) ? 'true' : 'false';
		$translation = ( $wpcs['translation'] ) ? 'true' : 'false';

		add_filter( 'allow_dev_auto_core_updates', '__return_' . $dev );
		add_filter( 'allow_major_auto_core_updates', '__return_' . $major );
		add_filter( 'allow_minor_auto_core_updates', '__return_' . $minor );
		add_filter( 'auto_update_translation', '__return_' . $translation );
	}
}
