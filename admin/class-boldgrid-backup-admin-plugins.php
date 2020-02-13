<?php
/**
 * File: class-boldgrid-backup-admin-plugins.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.10.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Plugins
 *
 * This is a generic class designed to help manage how this plugin behaves within the scope of
 * "WordPress Dashboard > Plugins > *".
 *
 * @since 1.10.1
 */
class Boldgrid_Backup_Admin_Plugins {
	/**
	 * Active Plugins.
	 *
	 * @since SINCEVERSION
	 * @var array
	 */
	public $active_plugins;

	/**
	 * Filter this plugin's links within Plugins > Installed Plugins.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $actions     An array of plugin action links. By default this can include 'activate',
	 *                            'deactivate', and 'delete'. With Multisite active this can also include
	 *                            'network_active' and 'network_only' items.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                            'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 */
	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$row_actions = [
			'settings' => '<a href="' . esc_url( $core->settings->get_settings_url() ) . '">' .
			esc_html__( 'Settings', 'boldgrid-backup' ) . '</a>',
		];

		if ( ! $core->config->get_is_premium() ) {
			$row_actions[] = '<a href="' . esc_url( $core->go_pro->get_premium_url( 'bgbkup-plugin-actions' ) ) .
				'" target="_blank">' . esc_html__( 'Get Premium', 'boldgrid-backup' ) . '</a>';
		}

		$actions = array_merge( $row_actions, $actions );

		return $actions;
	}

	/**
	 * Add Auto Update Message
	 *
	 * @since SINCEVERSION
	 */
	public function add_auto_update_message() {
		$this->active_plugins = apply_filters( 'boldgrid_backup_active_plugins', null );
		foreach ( $this->active_plugins as $plugin ) {
			add_action( 'in_plugin_update_message-' . $plugin->getFile(), array( $this, 'print_update_message' ), 10, 2 );
		}
	}
	/**
	 * Prints Update Message
	 *
	 * @since SINCEVERSION
	 *
	 * @param array $data
	 * @param array $data
	 */
	public function print_update_message( $data, $response ) {
		$core   = apply_filters( 'boldgrid_backup_get_core', null );
		$plugin = apply_filters( 'boldgrid_backup_get_plugin', $this->active_plugins, $data['slug'] );
		printf(
			'<br/>Version <strong>%s</strong> was released <strong>%s</strong> days ago.<br/>
			Total Upkeep will Automatically update this plugin after <strong>XX</strong> days.
			<a href="%s">View Update Settings</a>',
			esc_html( $plugin->updateData->version ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			esc_html( $plugin->updateData->days ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			esc_url( $core->settings->get_settings_url( 'section_auto_updates' ) )
		);
	}
}
