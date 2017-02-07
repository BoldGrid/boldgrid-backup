<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Backup_Admin_Update
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid <support@boldgrid.com>
 */

/**
 * BoldGrid Forms update class.
 */
class Boldgrid_Backup_Admin_Update {
	/**
	 * Parameters needed for this update class.
	 *
	 * @since 1.3.1
	 *
	 * @var array
	 */
	private $plugin_data = array();

	/**
	 * Constructor.
	 *
	 * Adds filters for plugin update hooks.
	 *
	 * @see self::wp_update_this_plugin()
	 *
	 * @param array $plugin_data {
	 * 	Parameters needed for this update class.
	 *
	 * 	@type string $plugin_key_code Plugin key code.
	 * 	@type string $slug            Plugin slug.
	 * 	@type string $main_file_path  Plugin main file path.
	 * 	@type array  $configs         Plugin configuration array.
	 * 	@type array  $version_data    Plugin version data from transient.
	 * 	@type string $transient       Transient name for the plugin version data.
	 * }
	 */
	public function __construct( array $plugin_data ) {
		$this->plugin_data = $plugin_data;

		$is_cron = ( defined( 'DOING_CRON' ) && DOING_CRON );
		$is_wpcli = ( defined( 'WP_CLI' ) && WP_CLI );

		if ( $is_cron || $is_wpcli || is_admin() ) {
			add_filter( 'plugins_api',
				array (
					$this,
					'custom_plugins_transient_update',
				), 11
			);

			add_filter( 'site_transient_update_plugins',
				array (
					$this,
					'site_transient_update_plugins',
				), 11
			);

			add_filter( 'pre_set_site_transient_update_plugins',
				array (
					$this,
					'custom_plugins_transient_update',
				), 11
			);
		}

		if ( $is_cron || $is_wpcli ){
			$this->wp_update_this_plugin();
		}
	}

	/**
	 * Validate input plugin data.
	 *
	 * @since 1.3.1
	 *
	 * @return bool
	 */
	private function validate_plugin_data() {
		return (
			! empty( $this->plugin_data['plugin_key_code'] ) &&
			! empty( $this->plugin_data['slug'] ) &&
			! empty( $this->plugin_data['main_file_path'] ) &&
			! empty( $this->plugin_data['configs'] ) &&
			! empty( $this->plugin_data['transient'] )
		);
	}

	/**
	 * Update the plugin transient.
	 *
	 * @see self::validate_plugin_data()
	 *
	 * @global $pagenow    The current WordPress page filename.
	 * @global $wp_version The WordPress version.
	 *
	 * @param object $transient WordPress plugin update transient.
	 * @return object $transient
	 */
	public function custom_plugins_transient_update( $transient ) {
		if ( ! $this->validate_plugin_data() ) {
			return $transient;
		}

		$version_data = $this->plugin_data['version_data'];

		$plugin_data = get_plugin_data( $this->plugin_data['main_file_path'], false );

		$have_configs = ( ! empty( $this->plugin_data['configs'] ) );

		$is_force_check = isset( $_GET['force-check'] );

		// Was the version data recently updated?
		$is_data_old = ( empty( $version_data->updated ) || $version_data->updated < time() - 5 );

		global $wp_version;

		// If we have no transient or force-check is called, and we do have configs, then get data and set transient.
		if ( $have_configs && ( ! $version_data || ( $is_force_check && $is_data_old ) ) ) {
			$options = get_site_option( 'boldgrid_settings' );

			$channel = isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable';

			$params = http_build_query( array(
				'key' => $this->plugin_data['plugin_key_code'],
				'channel' => $channel,
				'installed_' . $this->plugin_data['plugin_key_code'] . '_version' => $plugin_data['Version'],
				'installed_wp_version' => $wp_version,
				'site_hash' => get_option( 'boldgrid_site_hash' ),
			) );

			$query = $this->plugin_data['configs']['asset_server'] .
				$this->plugin_data['configs']['ajax_calls']['get_plugin_version'] . '?' . $params;

			$version_data = json_decode( wp_remote_retrieve_body( wp_remote_get( $query ) ) );

			// Set the version data transient, expire in 8 hours.
			if ( ! empty( $version_data ) && 200 === $version_data->status &&
				 ! empty( $version_data->result->data ) ) {
					// Add the current timestamp (in seconds).
					$version_data->updated = time();

					// Set version data transient, expire in 8 hours.
					delete_site_transient( $this->plugin_data['transient'] );
					set_site_transient( $this->plugin_data['transient'], $version_data,
						8 * HOUR_IN_SECONDS );
			} else {
				// Something went wrong, so just skip adding update data; return unchanged transient data.
				return $transient;
			}
		}

		global $pagenow;

		// Create a new object to be injected into transient.
		if ( 'plugin-install.php' === $pagenow && isset( $_GET['plugin'] ) &&
			 $this->plugin_data['slug'] === $_GET['plugin'] ) {
			// For version information iframe (/plugin-install.php).
			$transient = new stdClass();

			// If we have section data, then prepare it for use.
			if ( ! empty( $version_data->result->data->sections ) ) {
				// Remove new lines and double-spaces, to help prevent a broken JSON set.
				$version_data->result->data->sections = preg_replace( '/\s+/', ' ',
					trim( $version_data->result->data->sections ) );

				// Convert the JSON set into an array.
				$transient->sections = json_decode( $version_data->result->data->sections, true );

				// If we have data, format it for use, else set a default message.
				if ( ! empty( $transient->sections ) && count( $transient->sections ) ) {
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
			// $transient->downloaded = $version_data->result->data->downloads;
			$transient->last_updated = $version_data->result->data->release_date;
			$transient->download_link = $this->plugin_data['configs']['asset_server'] .
				 $this->plugin_data['configs']['ajax_calls']['get_asset'] .
				 '?id=' . $version_data->result->data->asset_id . '&installed_plugin_version=' .
				 $plugin_data['Version'] . '&installed_wp_version=' .
				 $wp_version;

			if ( ! empty( $version_data->result->data->compatibility ) &&
				( $compatibility = json_decode( $version_data->result->data->compatibility, true ) ) ) {
					$transient->compatibility = $version_data->result->data->compatibility;
			}

			/*
			 * Not currently showing ratings.
			 * if ( ! ( empty( $version_data->result->data->rating ) ||
			 * empty( $version_data->result->data->num_ratings ) ) ) {
			 * $transient->rating = ( float ) $version_data->result->data->rating;
			 * $transient->num_ratings = ( int ) $version_data->result->data->num_ratings;
			 * }
			 */

			$transient->added = '2015-03-19';

			if ( ! empty( $version_data->result->data->siteurl ) ) {
				$transient->homepage = $version_data->result->data->siteurl;
			}

			if ( ! empty( $version_data->result->data->tags ) &&
				( $tags = json_decode( $version_data->result->data->tags, true ) ) ) {
					$transient->tags = $version_data->result->data->tags;
			}

			if ( ! empty( $version_data->result->data->banners ) &&
				( $banners = json_decode( $version_data->result->data->banners, true ) ) ) {
					$transient->banners = $banners;
			}

			$transient->plugin_name = basename( $this->plugin_data['main_file_path'] );
			$transient->slug = $this->plugin_data['slug'];
			$transient->version = $version_data->result->data->version;
			$transient->new_version = $version_data->result->data->version;
			// $transient->active_installs = true;
		} else {
			// For plugins.php and update-core.php pages, and WP-CLI.
			$obj = new stdClass();
			$obj->slug = $this->plugin_data['slug'];
			$obj->plugin = $this->plugin_data['slug'] . '/' .
				basename( $this->plugin_data['main_file_path'] );
			$obj->new_version = $version_data->result->data->version;

			if ( ! empty( $version_data->result->data->siteurl ) ) {
				$obj->url = $version_data->result->data->siteurl;
			}

			$obj->package = $this->plugin_data['configs']['asset_server'] .
				$this->plugin_data['configs']['ajax_calls']['get_asset'] . '?id=' .
				$version_data->result->data->asset_id . '&installed_plugin_version=' .
				$plugin_data['Version'] . '&installed_wp_version=' . $wp_version;

			if ( $plugin_data['Version'] !== $version_data->result->data->version ) {
				$transient->response[ $obj->plugin ] = $obj;
				$transient->tested = $version_data->result->data->tested_wp_version;
			} else {
				$transient->no_update[ $obj->plugin ] = $obj;
			}
		}

		return $transient;
	}

	/**
	 * Force WP to check for updates, don't rely on cache / transients.
	 *
	 * @global $pagenow The current WordPress page filename.
	 *
	 * @param object $value WordPress plugin update transient.
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		global $pagenow;

		// Only require fresh data if user clicked "Check Again".
		if ( 'update-core.php' !== $pagenow || ! isset( $_GET['force-check'] ) ) {
			return $value;
		}

		// Set the last_checked to 1, so it will trigger the timeout and check again.
		if ( isset( $value->last_checked ) ) {
			$value->last_checked = 1;
		}

		return $value;
	}

	/**
	 * Action to add a filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.1.3
	 *
	 * @see wp_maybe_auto_update()
	 */
	public function wp_update_this_plugin () {
		add_filter( 'auto_update_plugin',
			array (
				$this,
				'auto_update_this_plugin'
			), 11, 2
		);

		add_filter( 'auto_update_plugins',
			array (
				$this,
				'auto_update_this_plugin'
			), 11, 2
		);

		// Have WordPress check for plugin updates.
		wp_maybe_auto_update();
	}

	/**
	 * Filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.1.3
	 *
	 * @param bool $update Whether or not this plugin is set to update.
	 * @param object $item The plugin transient object.
	 * @return bool Whether or not to update this plugin.
	 */
	public function auto_update_this_plugin ( $update, $item ) {
		if ( isset( $item->slug[ $this->plugin_data['slug'] ] ) && isset( $item->autoupdate ) ) {
			return true;
		} else {
			return $update;
		}
	}
}
