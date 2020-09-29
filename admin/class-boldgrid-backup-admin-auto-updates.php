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

		add_filter( 'automatic_updater_disabled', '__return_false' );
	}

	/**
	 * Updates the WordPress Options table.
	 *
	 * This is fired by the "update_option_{$option}" hook when the option is
	 * either auto_update_plugins or auto_update_themes.
	 *
	 * Prior to WordPress 5.5, Total Upkeep managed auto updates per plugin / theme and saved the
	 * settings in $settings['auto_update']. As of WordPress 5.5, WordPress handles auto updates
	 * per plugin / theme and saves this info to auto_update_plugins and auto_update_themes options.
	 * This method is here to ensure the two record systems stay in sync. Whenever the official options
	 * are updated, this method will apply the same settings to the Total Upkeep settings.
	 *
	 * @see Boldgrid_Backup::define_admin_hooks() add_action definition.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/update_option_option/ WP Hook Documentation
	 *
	 * @since 1.14.3
	 *
	 * @param array  $old_value Value of the option before updating.
	 * @param array  $new_value Value of the option after updating.
	 * @param string $option Name of option being updated.
	 */
	public function wordpress_option_updated( $old_value, $new_value, $option ) {
		// Determines if this is being fired for a theme or plugin update
		$update_type = 'auto_update_plugins' === $option ? 'plugins' : 'themes';
		$core        = apply_filters( 'boldgrid_backup_get_core', null );
		$settings    = $core->settings->get_settings();

		// The plugins / themes listed in $new_value will only be those that have auto updates enabled.
		$enabled_offers = $new_value;
		foreach ( $settings['auto_update'][ $update_type ] as $offer => $enabled ) {
			// Do not modify the 'default' setting. This is used to define the default for new themes / plugins.
			if ( 'default' === $offer ) {
				continue;
			}
			// If the theme / plugin is found in the $enabled_offers array, enable in our settings, otherwise disable.
			$settings['auto_update'][ $update_type ][ $offer ] = in_array( $offer, $enabled_offers, true ) ? '1' : '0';
		}

		// Save the settings.
		$core->settings->save( $settings );
	}

	/**
	 * Set Settings.
	 *
	 * @since 1.14.0
	 */
	public function set_settings() {
		$core           = apply_filters( 'boldgrid_backup_get_core', null );
		$this->settings = $core->settings->get_setting( 'auto_update' );
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
		$this->init_plugins();

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
		$this->init_themes();

		$days_to_wait = $this->get_days();
		$theme        = $this->themes->getFromStylesheet( $stylesheet );
		$theme->setUpdateData();
		$days_since_release   = $theme->updateData->days; //phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$theme_update_enabled = isset( $this->settings['themes'][ $stylesheet ] ) ? (bool) $this->settings['themes'][ $stylesheet ] : (bool) $this->settings['themes']['default'];
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
	 * @param  mixed    $update Whether or not to update.
	 * @param  stdClass $item The item class passed to callback.
	 *
	 * @return bool
	 */
	public function auto_update_plugins( $update, $item ) {
		/*
		 * In WP5.5 , WordPress uses the auto_update_${type} hook to
		 * determine if auto updates is forced or not, in order to disable the
		 * Enable / Disable action links on the plugin / theme pages. When it checks the filter in those cases
		 * it provides null as the $update parameter. If we return $null in those cases
		 * WP will not think that the auto updates are 'forced'.
		 */
		if ( is_null( $update ) ) {
			return null;
		}

		$this->init_plugins();

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
	 * @param  mixed    $update Whether or not to update.
	 * @param  stdClass $item The item class passed to callback.
	 *
	 * @return bool
	 */
	public function auto_update_themes( $update, $item ) {
		/*
		 * In WP5.5 , WordPress uses the auto_update_${type} hook to
		 * determine if auto updates is forced or not, in order to disable the
		 * Enable / Disable action links on the plugin / theme pages. When it checks the filter in those cases
		 * it provides null as the $update parameter. If we return $null in those cases
		 * WP will not think that the auto updates are 'forced'.
		 */
		if ( is_null( $update ) ) {
			return null;
		}

		$this->init_themes();

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

	/**
	 * Initialize our plugins.
	 *
	 * Initially, self::plugins was initialized in the constructor, which was ran on every admin page.
	 * Some xhprof investigation showed the constructor was adding 0.19s  / 282kb memory to each page.
	 * During optimization, it was noticed that only two methods utilzed self::plugins. To save a few
	 * resources, self::plugins is now initialized within this method, which much be called prior to
	 * using self::plugins.
	 *
	 * @since 1.14.5
	 */
	private function init_plugins() {
		if ( ! empty( $this->plugins ) ) {
			return;
		}

		$plugins       = new \Boldgrid\Library\Library\Plugin\Plugins();
		$this->plugins = $plugins->getAllPlugins();
	}

	/**
	 * Initialize our themes.
	 *
	 * @since 1.14.5
	 *
	 * @see self::init_plugins() for additional info on this method.
	 */
	private function init_themes() {
		if ( ! empty( $this->themes ) ) {
			return;
		}

		$this->themes = new \Boldgrid\Library\Library\Theme\Themes();
	}
}
