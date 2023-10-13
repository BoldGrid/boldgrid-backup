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
	/**
	 * Core object.
	 *
	 * @var Boldgrid_Backup_Core
	 * @since 1.8.0
	 */
	public $core;

	/**
	 * Log object.
	 *
	 * @var Boldgrid_Backup_Admin_Log
	 * @since 1.8.0
	 */
	public $auto_update_log;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {
		$this->core     = apply_filters( 'boldgrid_backup_get_core', null );
		$this->settings = $this->core->settings->get_setting( 'auto_update' );

		// Hook into WordPress initialization to detect auto-updates.
		add_action( 'init', array( $this, 'detect_auto_update' ) );

		// Hook into WordPress update process to log update information.
		add_action( 'upgrader_process_complete', array( $this, 'upgrade_complete' ), 10, 2 );
	}

	/**
	 * Get Days.
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
	 * Callback function to detect when WordPress auto-update process starts.
	 */
	public function detect_auto_update() {
		if ( defined( 'DOING_CRON' ) && DOING_CRON && false !== strpos( $_SERVER['REQUEST_URI'], '/wp-cron.php' ) ) {
			// Timely auto updates enabled.
			$timely_auto_updates = empty( $this->settings['timely-updates-enabled'] ) ? 'No' : 'Yes';
			// Today's date.
			$today = date( 'Y-m-d' );
			// Days to delay the update.
			$days_delay = $this->get_days();

			$this->auto_update_log = new Boldgrid_Backup_Admin_Log( $this->core );
			$this->auto_update_log->init( 'auto-update.log' );
			$this->auto_update_log->add( '--- Running Auto Updates ---' );
			$this->auto_update_log->add( 'Timely Updates Enabled: ' . $timely_auto_updates );

			$core_enabled = false;

			foreach ( $this->settings['wpcore'] as $core ) {
				if ( ! empty( $core ) ) {
					$core_enabled = true;
				}
			}

			// Check for core updates.
			if ( $core_enabled ) {
				$core_update = get_site_transient( 'update_core' );

				if ( isset( $core_update->updates ) && is_array( $core_update->updates ) ) {
					foreach ( $core_update->updates as $update ) {
						if ( $update->current !== $update->version ) {
							$core_msg = sprintf(
								'WordPress Core Update Available from version %s to %s.',
								$update->current,
								$update->version,
							);

							$this->auto_update_log->add( $core_msg );
						} else {
							$this->auto_update_log->add( 'WordPress Core is up to date.' );
						}
					}
				}
			}

			// Check for plugin updates.
			$plugin_updates = get_site_transient( 'update_plugins' );

			if ( isset( $plugin_updates->response ) && is_array( $plugin_updates->response ) ) {
				foreach ( $plugin_updates->response as $plugin_file => $update ) {
					if ( ! empty( $this->settings['plugins'][ $plugin_file ] ) ) {
						$plugin_data            = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
						$plugin_slug            = dirname( plugin_basename( WP_PLUGIN_DIR . '/' . $plugin_file ) );
						$plugin_name            = $plugin_data['Name'];
						$plugin_current_version = $plugin_data['Version'];
						$remote_plugin_data     = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json' );

						if ( ! is_wp_error( $remote_plugin_data ) ) {
							$plugin_json               = json_decode( $remote_plugin_data['body'] );
							$plugin_new_version        = $plugin_json->version;
							$plugin_release_date       = date( 'Y-m-d', strtotime( $plugin_json->last_updated ) );
							$days_since_plugin_release = abs( strtotime( $today ) - strtotime( $plugin_release_date ) ) / 86400;
							$allow_update_msg          = 'Yes' === $timely_auto_updates && $days_since_plugin_release < $days_delay ? 'This plugin will be updated.' : 'This plugin will not be updated.';

							$plugin_msg = sprintf(
								'Plugin Update Available: %s from version %s to %s released on %s. Days since release: %s. Timely auto updates set to %s days after release. %s',
								$plugin_name,
								$plugin_current_version,
								$plugin_new_version,
								$plugin_release_date,
								$days_since_plugin_release,
								$days_delay,
								$allow_update_msg
							);

							$this->auto_update_log->add( $plugin_msg );
						}
					}
				}
			} else {
				$this->auto_update_log->add( 'All plugins are up to date.' );
			}

			// Check for theme updates.
			$theme_updates = get_site_transient( 'update_themes' );

			if ( isset( $theme_updates->response ) && is_array( $theme_updates->response ) ) {
				foreach ( $theme_updates->response as $theme_slug => $update ) {
					if ( ! empty( $this->settings['themes'][ $theme_slug ] ) ) {
						$theme_data            = wp_get_theme( $theme_slug );
						$theme_name            = $theme_data->get( 'Name' );
						$theme_current_version = $theme_data->get( 'Version' );
						$remote_theme_data     = wp_remote_get( 'https://api.wordpress.org/themes/info/1.2/?action=theme_information&slug=' . $theme_slug );

						if ( ! is_wp_error( $remote_theme_data ) ) {
							$theme_json               = json_decode( $remote_theme_data['body'] );
							$theme_new_version        = $theme_json->version;
							$theme_release_date       = date( 'Y-m-d', strtotime( $theme_json->last_updated ) );
							$days_since_theme_release = abs( strtotime( $today ) - strtotime( $theme_release_date ) ) / 86400;
							$allow_update_msg         = 'Yes' === $timely_auto_updates && $days_since_theme_release < $days_delay ? 'This theme will be updated.' : 'This theme will not be updated.';
							$theme_msg                = sprintf(
								'Theme Update Available: %s from version %s to %s released on %s. Days since release: %s. Timely auto updates set to %s days after release. %s',
								$theme_name,
								$theme_current_version,
								$theme_new_version,
								$theme_release_date,
								$days_since_theme_release,
								$days_delay,
								$allow_update_msg
							);

							$this->auto_update_log->add( $theme_msg );
						}
					}
				}
			} else {
				$this->auto_update_log->add( 'All themes are up to date.' );
			}
		}
    }

	/**
	 * Log update information.
	 *
	 * @param WP_Upgrader $upgrader_object
	 * @param array       $options
	 */
	public function upgrade_complete( $upgrader_object, $options ) {
		$this->auto_update_log = new Boldgrid_Backup_Admin_Log( $this->core );
		$this->auto_update_log->init( 'auto-update.log' );

		if ( isset( $options['action'] ) && 'update' === $options['action'] ) {
			$type        = isset( $options['type'] ) ? $options['type'] : 'WP Core';
			$update_item = ! empty( $options['temp_backup'] ) ? implode( ', ', $options['temp_backup'] ) : '';

			$log_message = sprintf(
				'Automatic %s update for %s complete.',
				$type,
				$update_item
			);

			$this->auto_update_log->add( $log_message );
		}
	}
}
