<?php
/**
 * The admin-specific update functionality of the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Update class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Update {
	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $plugin_name
	 */
	private $plugin_name = '';

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $version
	 */
	private $version = '';

	/**
	 * Configuration settings.
	 *
	 * @since 1.0
	 * @access public
	 * @staticvar array
	 * @var array
	 */
	public static $configs = array();

	/**
	 * Constructor.
	 *
	 * Add hooks for plugin update functions.
	 *
	 * @global $pagenow The current WordPress page filename.
	 *
	 * @param string $plugin_name The plugin folder name.
	 * @param string $version The plugin version number.
	 * @return null
	 */
	public function __construct( $plugin_name = '', $version = '' ) {
		// Set the plugin name.
		$this->plugin_name = $plugin_name;

		// Set the plugin version.
		$this->version = $version;

		// Only for wp-admin.
		if ( is_admin() ) {
			// Get the current WordPress page filename.
			global $pagenow;

			// Make an array of plugin update pages.
			$plugin_update_pages = array(
				'plugins.php',
				'update-core.php',
			);

			// Is page for plugin information?
			$is_plugin_information = ( ( true === empty( $pagenow ) || 'plugin-install.php' === $pagenow )
				&& isset( $_GET['plugin'] ) && $this->plugin_name === $_GET['plugin'] &&
				isset( $_GET['tab'] ) && 'plugin-information' === $_GET['tab'] );

			// Is this a plugin update action?
			$is_plugin_update = ( isset( $_REQUEST['action'] ) &&
				 'update-plugin' === $_REQUEST['action'] && 'admin-ajax.php' === $pagenow );

			// Add filters to modify plugin update transient information.
			if ( in_array( $pagenow, $plugin_update_pages, true ) || $is_plugin_information ||
				 $is_plugin_update ) {
				// Add filters.
				add_filter( 'pre_set_site_transient_update_plugins',
					array(
						$this,
						'custom_plugins_transient_update',
					), 10
				);

				add_filter( 'plugins_api',
					array(
						$this,
						'custom_plugins_transient_update',
					), 10
				);

				// Force WP to check for updates, don't rely on cache / transients.
				add_filter( 'site_transient_update_plugins',
					array(
						$this,
						'site_transient_update_plugins',
					), 10
				);
			}
		}
	}

	/**
	 * Update the plugin transient.
	 *
	 * @global $pagenow The current WordPress page filename.
	 * @global $wp_version The WordPress version.
	 *
	 * @param object $transient WordPress plugin update transient.
	 * @return object $transient
	 */
	public function custom_plugins_transient_update( $transient ) {
		// Check if multisite.
		$is_multisite = is_multisite();

		// Get version data transient.
		if ( true === $is_multisite ) {
			$version_data = get_site_transient( 'boldgrid_backup_version_data' );
		} else {
			$version_data = get_transient( 'boldgrid_backup_version_data' );
		}

		// Get configs.
		$configs = self::get_configs();

		// Get the plugin version.
		$plugin_version = $this->version;

		// Get the WordPress version.
		global $wp_version;

		// Do we have $configs?
		$have_configs = ( false === empty( $configs ) );

		// Is force-check present?
		$is_force_check = isset( $_GET['force-check'] );

		// Was the version data recently updated?
		$is_data_old = ( empty( $version_data->updated ) || $version_data->updated < time() - 60 );

		/*
		 * If we have no transient or force-check is called, and we do have configs,
		 * then get data and set transient.
		 */
		if ( $have_configs && ( false === $version_data || ( $is_force_check && $is_data_old ) ) ) {
			// Determine the plugin update release channel.
			if ( true === $is_multisite ) {
				( $options = get_site_option( 'boldgrid_settings' ) ) ||
					 ( $options = get_option( 'boldgrid_settings' ) );
			} else {
				$options = get_option( 'boldgrid_settings' );
			}

			// Set the release channel.
			$channel = isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable';

			// Get the latest version information.
			// Build the http query.
			$params_array['key'] = 'backup';
			$params_array['channel'] = $channel;
			$params_array['installed_backup_version'] = $plugin_version;
			$params_array['installed_wp_version'] = $wp_version;
			$params_array['site_hash'] = get_option( 'boldgrid_site_hash' );

			$params = http_build_query( $params_array );

			$query = $configs['asset_server'] . $configs['ajax_calls']['get_plugin_version'] . '?' .
				 $params;

			// Make the call.
			$version_data = json_decode( wp_remote_retrieve_body( wp_remote_get( $query ) ) );

			// Set the version data transient, expire in 8 hours.
			if ( false === empty( $version_data ) && 200 === $version_data->status &&
				 false === empty( $version_data->result->data ) ) {
				// Add the current timestamp (in seconds).
				$version_data->updated = time();

				// Save the update data in a transient.
				if ( true === $is_multisite ) {
					delete_site_transient( 'boldgrid_backup_version_data' );
					set_site_transient( 'boldgrid_backup_version_data', $version_data,
					8 * HOUR_IN_SECONDS );
				} else {
					delete_transient( 'boldgrid_backup_version_data' );
					set_transient( 'boldgrid_backup_version_data', $version_data,
					8 * HOUR_IN_SECONDS );
				}
			} else {
				// Something went wrong, so just skip adding update data; return unchanged transient data.
				return $transient;
			}
		}

		// Get the current WordPress page filename.
		global $pagenow;

		// Create a new object to be injected into transient.
		if ( 'plugin-install.php' === $pagenow && isset( $_GET['plugin'] ) &&
			 $this->plugin_name === $_GET['plugin'] ) {
			// For version information iframe (/plugin-install.php).
			$transient = new stdClass();

			// If we have section data, then prepare it for use.
			if ( false === empty( $version_data->result->data->sections ) ) {
				// Remove new lines and double-spaces, to help prevent a broken JSON set.
				$version_data->result->data->sections = preg_replace( '/\s+/', ' ',
				trim( $version_data->result->data->sections ) );

				// Convert the JSON set into an array.
				$transient->sections = json_decode( $version_data->result->data->sections, true );

				// If we have data, format it for use, else set a default message.
				if ( false === empty( $transient->sections ) && count( $transient->sections ) > 0 ) {
					foreach ( $transient->sections as $section => $section_data ) {
						$transient->sections[ $section ] = html_entity_decode( $section_data,
						ENT_QUOTES );
					}
				} else {
					$transient->sections['description'] = 'Data not available';
				}
			} else {
				$transient->sections['description'] = 'Data not available';
			}

			// Set the other elements.
			$transient->name = $version_data->result->data->title;
			$transient->requires = $version_data->result->data->requires_wp_version;
			$transient->tested = $version_data->result->data->tested_wp_version;
			$transient->last_updated = $version_data->result->data->release_date;
			$transient->download_link = $configs['asset_server'] .
				 $configs['ajax_calls']['get_asset'] . '?id=' . $version_data->result->data->asset_id .
				 '&installed_backup_version=' . $plugin_version . '&installed_wp_version=' .
				 $wp_version;

			if ( false === empty( $version_data->result->data->compatibility ) && null !== ( $compatibility = json_decode(
			$version_data->result->data->compatibility, true ) ) ) {
				$transient->compatibility = $version_data->result->data->compatibility;
			}

			$transient->added = '2016-05-05';

			if ( false === empty( $version_data->result->data->siteurl ) ) {
				$transient->homepage = $version_data->result->data->siteurl;
			}

			if ( false === empty( $version_data->result->data->tags ) && null !== ( $tags = json_decode(
			$version_data->result->data->tags, true ) ) ) {
				$transient->tags = $version_data->result->data->tags;
			}

			if ( false === empty( $version_data->result->data->banners ) && null !== ( $banners = json_decode(
			$version_data->result->data->banners, true ) ) ) {
				$transient->banners = $banners;
			}

			$transient->plugin_name = $this->plugin_name . '.php';
			$transient->slug = $this->plugin_name;
			$transient->version = $version_data->result->data->version;
			$transient->new_version = $version_data->result->data->version;
		} else {
			// For plugins.php and update-core.php pages.
			$obj = new stdClass();
			$obj->slug = $this->plugin_name;
			$obj->plugin = $this->plugin_name . '/' . $this->plugin_name . '.php';
			$obj->new_version = $version_data->result->data->version;

			if ( false === empty( $version_data->result->data->siteurl ) ) {
				$obj->url = $version_data->result->data->siteurl;
			}

			$obj->package = $configs['asset_server'] . $configs['ajax_calls']['get_asset'] . '?id=' .
				 $version_data->result->data->asset_id . '&installed_backup_version=' .
				 $plugin_version . '&installed_wp_version=' . $wp_version;

			if ( $plugin_version !== $version_data->result->data->version ) {
				$transient->response[ $obj->plugin ] = $obj;
				$transient->tested = $version_data->result->data->tested_wp_version;
			} else {
				$transient->no_update[ $obj->plugin ] = $obj;
			}
		}

		return $transient;
	}

	/**
	 * Force WordPress to check for updates, and do not rely on cache / transients.
	 *
	 * @global $pagenow The current WordPress page filename.
	 *
	 * @param object $value WordPress plugin update transient.
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		// Get the current WordPress page filename.
		global $pagenow;

		// Only require fresh data if user clicked "Check Again".
		if ( 'update-core.php' !== $pagenow || false === isset( $_GET['force-check'] ) ) {
			return $value;
		}

		// Set the last_checked to 1, so it will trigger the timeout and check again.
		if ( true === isset( $value->last_checked ) ) {
			$value->last_checked = 1;
		}

		return $value;
	}

	/**
	 * Get configuration settings.
	 *
	 * @since 1.0
	 *
	 * @static
	 *
	 * @return array An array of configuration settings.
	 */
	public static function get_configs() {
		// If the configuration array was already created, then return it.
		if ( false === empty( self::$configs ) ) {
			return self::$configs;
		}

		// Set the config directory.
		$config_dir = BOLDGRID_BACKUP_PATH . '/includes/config';

		// Set the config file paths.
		$global_config_path = $config_dir . '/config.plugin.php';
		$local_config_path = $config_dir . '/config.local.php';

		// Initialize $global_configs array.
		$global_configs = array();

		// If a global config file exists, read the global configuration settings.
		if ( true === file_exists( $global_config_path ) ) {
			$global_configs = require $global_config_path;
		}

		// Initialize $local_configs array.
		$local_configs = array();

		// If a local configuration file exists, then read the settings.
		if ( true === file_exists( $local_config_path ) ) {
			$local_configs = require $local_config_path;
		}

		// If an api key hash stored in the database, then set it as the global api_key.
		$api_key_from_database = get_option( 'boldgrid_api_key' );

		if ( false === empty( $api_key_from_database ) ) {
			$global_configs['api_key'] = $api_key_from_database;
		}

		// Get the WordPress site url and set it in the global configs array.
		$global_configs['site_url'] = get_site_url();

		// Merge global and local configuration settings.
		if ( false === empty( $local_configs ) ) {
			$configs = array_merge( $global_configs, $local_configs );
		} else {
			$configs = $global_configs;
		}

		// Set the configuration array in the class property.
		self::$configs = $configs;

		// Return the configuration array.
		return $configs;
	}

	/**
	 * Action to add a filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.0.1
	 */
	public function wp_update_this_plugin () {
		// Add filters to modify plugin update transient information.
		add_filter( 'pre_set_site_transient_update_plugins',
			array (
				$this,
				'custom_plugins_transient_update'
			)
		);

		add_filter( 'plugins_api',
			array (
				$this,
				'custom_plugins_transient_update'
			)
		);

		add_filter( 'site_transient_update_plugins',
			array (
				$this,
				'site_transient_update_plugins'
			)
		);

		add_filter( 'auto_update_plugin',
			array (
				$this,
				'auto_update_this_plugin'
			), 10, 2
		);

		add_filter( 'auto_update_plugins',
			array (
				$this,
				'auto_update_this_plugin'
			), 10, 2
		);

		// Have WordPress check for plugin updates.
		wp_maybe_auto_update();
	}

	/**
	 * Filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.0.1
	 *
	 * @param bool $update Whether or not this plugin is set to update.
	 * @param object $item The plugin transient object.
	 * @return bool Whether or not to update this plugin.
	 */
	public function auto_update_this_plugin ( $update, $item ) {
		if ( isset( $item->slug['boldgrid-backup'] ) && isset( $item->autoupdate ) ) {
			return true;
		} else {
			return $update;
		}
	}
}
