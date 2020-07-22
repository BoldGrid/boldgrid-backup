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
	 * Filters auto update markup on plugin page.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $html Original HTML Markup.
	 * @param string $plugin_file Path to main plugin file.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public function auto_update_markup( $html, $plugin_file, $plugin_data ) {
		$doc = new DOMDocument();

		// Loads the original html markup into a DOMDocument object.
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOEMPTYTAG );

		// URL to use in href.
		$enable_link = 'plugins.php';
		$link        = $doc->getElementsByTagName( 'a' );

		// This sets the attributes for the <a> within the markup to work with our own ajax call instead of the default one.
		$link->item( 0 )->setAttribute( 'class', 'boldgrid-backup-enable-auto-update' );
		$link->item( 0 )->setAttribute( 'data-update_type', 'plugin' );
		$link->item( 0 )->setAttribute( 'data-update_name', $plugin_file );

		// If the auto update is currently enabled, display markup to disable auto updates.
		if ( '1' === $this->settings['plugins'][ $plugin_file ] ) {
			$divs = $doc->getElementsByTagName( 'div' );
			$div  = $divs->item( 0 );

			/*
			 * DOMDocument doesn't put the <div> element in the right place.
			 * So we have to remove it here, and re-add it below.
			 */
			$div->parentNode->removeChild( $div ); //phpcs:ignore WordPress.NamingConventions.ValidVariableName

			$link->item( 0 )->setAttribute( 'data-update_enable', false );
			$doc->normalizeDocument();

			// Re-Add the <div> element.
			$doc->appendChild( $div );

			// Export the modified HTML markup as a string.
			return $doc->saveHTML();
		} else {

			// If the auto update is currently disabled, display markup to enable auto updates.
			$link->item( 0 )->setAttribute( 'data-update_enable', true );

			// Export the modified HTML markup as a string.
			return $doc->saveHTML();
		}
	}

	/**
	 * Ajax Callback to enable auto updates for a given plugin or theme.
	 *
	 * @since SINCEVERSION
	 */
	public function wp_ajax_boldgrid_backup_auto_update() {
		// he nonce for this is created in Boldgrid_Backup_Admin::enqueue_styles.
		if ( ! check_admin_referer( 'boldgrid_backup_auto_update' ) ) {
			echo wp_json_encode(
				array(
					'settings_updated' => false,
					'error'            => 'Nonce Verification Failed',
				)
			);

			wp_die();
		}

		// Get Update Data from $_POST array.
		$update_data = isset( $_POST['data'] ) ? $_POST['data'] : array();

		// If this is called without the update_data, die.
		if ( empty( $update_data ) ) {
			echo wp_json_encode(
				array(
					'settings_updated' => false,
					'error'            => 'Invalid Request',
				)
			);
			wp_die();
		}
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		// If the update type is 'plugin' update the auto_update settings for plugins.
		if ( isset( $update_data['update_type'] ) && 'plugin' === $update_data['update_type'] ) {
			$settings = $core->settings->get_settings();
			$settings['auto_update']['plugins'][ $update_data['update_name'] ] = $update_data['update_enable'] ? '1' : '0';
			$settings_updated = $core->settings->save( $settings );

			// Even though we have updated the option in our settings, it must be updated in the WordPress options table.
			$core->settings->set_autoupdate_options( $settings['auto_update'] );

			echo wp_json_encode(
				array(
					'settings_updated' => $settings_updated,
					'error'            => $settings_updated ? '' : 'Settings Failed to Save',
				)
			);

			wp_die();
		}
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
