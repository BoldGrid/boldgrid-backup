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
	 * Get update schedule strings.
	 *
	 * Converts the unix timestamp of each theme update.
	 * into a 'xx {days|months|years}' string.
	 *
	 * @since 1.14.3
	 *
	 * @return array An associative array of stylesheet => string.
	 */
	public function update_schedule_strings() {
		$update_schedule_strings = array();
		foreach ( $this->themes->get() as $theme ) {
			$theme->setUpdateData();
			$days_till_update = apply_filters( 'boldgrid_backup_premium_days_till_update', $theme );
			if ( is_int( $days_till_update ) && 1 <= $days_till_update ) {
				$update_schedule_strings[ $theme->stylesheet ] = human_time_diff( $days_till_update );
			}
		}
		return $update_schedule_strings;
	}

	/**
	 * Filters auto update markup on themes page.
	 *
	 * @since 1.14.3
	 *
	 * @param string $template The unfiltered javascript template for themes page.
	 *
	 * @return string
	 */
	public function theme_update_markup( $template ) {

		$patterns = array(
			'/class="toggle-auto-update/',
			'/class="auto-update-time/',
		);
		// add onclick event and prepend the classes with boldgrid-backup.
		$replacements = array(
			'onclick="BoldGrid.autoUpdateThemes(this)" class="boldgrid-backup-toggle-auto-update',
			'class="boldgrid-backup-auto-update-time',
		);

		$filtered_template = '<# data.autoupdate.forced = "" #>' . preg_replace( $patterns, $replacements, $template );

		$time_pattern = '/(Automatic update scheduled in )(\d+)(\s\S+)(<\/span>)/';
		$time_replace = '<# if ( BoldGridBackupAdmin.theme_update_strings[data.id] ) { #>\1{{ BoldGridBackupAdmin.theme_update_strings[data.id] }}\4<# } else { #>\1\2\3\4<# } #>';

		$filtered_template = preg_replace( $time_pattern, $time_replace, $filtered_template );
		return $filtered_template;
	}

	/**
	 * Filters auto update markup on plugin page.
	 *
	 * @since 1.14.3
	 *
	 * @param string $html Original HTML Markup.
	 * @param string $plugin_file Path to main plugin file.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public function auto_update_markup( $html, $plugin_file, $plugin_data ) {

		// This sets the attributes for the <a> within the markup to work with our own ajax call instead of the default one.
		$plugins_with_updates  = array_keys( get_site_transient( 'update_plugins' )->response );
		$core                  = apply_filters( 'boldgrid_backup_get_core', null );
		$auto_update_settings  = $core->settings->get_setting( 'auto_update' );
		$auto_updates_disabled = $auto_update_settings['plugins'][ $plugin_file ] ? '0' : '1';

		$auto_update_status = sprintf(
			// Translators: 1. Whether auto-updates are currently enabled or disabled.
			esc_html__( 'Auto-updates %s', 'boldgrid-backup' ),
			$auto_updates_disabled ? esc_html__( 'disabled', 'boldgrid-backup' ) : esc_html__( 'enabled', 'boldgrid-backup' )
		);
		$link_text = sprintf(
			// Translators: 1. Whether or not to enable or disable auto-updates.
			esc_html__( '%s Auto-updates', 'boldgrid-backup' ),
			$auto_updates_disabled ? esc_html__( 'Enable ', 'boldgrid-backup' ) : esc_html__( 'Disable ', 'boldgrid-backup' )
		);

		$html = '
			<span class="boldgrid-backup-auto-update-status">' . $auto_update_status . '</span>
			<br />
			<a class="boldgrid-backup-enable-auto-update" data-update_type="plugin"
			data-update_name="' . $plugin_file . '" data-update_enable="' . $auto_updates_disabled . '">' . $link_text . '</a>';

		// If the auto update are not currently disabled, display markup to disable auto updates.
		if ( ! $auto_updates_disabled ) {
			$plugin           = \Boldgrid\Library\Library\Plugin\Factory::create( $plugin_file );
			$days_till_update = apply_filters( 'boldgrid_backup_premium_days_till_update', $plugin );

			$update_schedule_string = '';
			/*
			 * If the plugin is listed in the auto_update_plugins option then auto updates are enabled for that plugin.
			 * We have to test that $days_till_update is an integer because the 'boldgrid_backup_premium_days_till_update' filter
			 * will return a Plugin object if that filter does not exist. If the $days_till_update is greater than 0, then display the
			 * human readable time difference, otherwise display the standard wp_get_auto_update_message().
			*/
			if ( in_array( $plugin_file, $plugins_with_updates, true ) && is_int( $days_till_update ) && 0 < $days_till_update ) {
				$update_schedule_string = sprintf(
					'<div class="boldgrid-backup-auto-update-time">%s %s.</div>',
					esc_html__( 'Automatic update scheduled in', 'boldgrid-backup' ),
					human_time_diff( $days_till_update )
				);
			} elseif ( in_array( $plugin_file, $plugins_with_updates, true ) ) {
				$update_schedule_string = sprintf(
					'<div class="boldgrid-backup-auto-update-time">%s</div>',
					wp_get_auto_update_message()
				);
			}
			$html .= $update_schedule_string;
		}

		return $html;
	}

	/**
	 * Updates the WordPress Options table.
	 *
	 * This is fired by the "update_option_{$option}" hook when the option is
	 * either auto_update_plugins or auto_update_themes.
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
			if ( in_array( $offer, $enabled_offers, true ) ) {
				$settings['auto_update'][ $update_type ][ $offer ] = '1';
			} else {
				$settings['auto_update'][ $update_type ][ $offer ] = '0';
			}
		}

		// Save the settings.
		$core->settings->save( $settings );
	}

	/**
	 * Ajax Callback to enable auto updates for a given plugin or theme.
	 *
	 * This is fired whenever someone clicks on 'Enable(Disable) Auto Updates'
	 * to be sure that our method is called instead of WordPress' method.
	 *
	 * @since 1.14.3
	 */
	public function wp_ajax_boldgrid_backup_auto_update() {
		// the nonce for this is created in Boldgrid_Backup_Admin::enqueue_styles.
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
		} elseif ( isset( $update_data['slug'] ) ) {
			// if data-slug is set, this is a theme being updated.
			$settings = $core->settings->get_settings();
			$settings['auto_update']['themes'][ $update_data['slug'] ] = 'enable' === $update_data['wpAction'] ? '1' : '0';
			$settings_updated = $core->settings->save( $settings );

			// Even though we have updated the option in our settings, it must be updated in the WordPress options table.
			$core->settings->set_autoupdate_options( $settings['auto_update'], true );

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
