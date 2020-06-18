<?php //phpcs:ignore WordPress.Files.FileName
/**
 * File: auto-update.php
 *
 * Show "Auto Update" section on settings page.
 *
 * @since      1.14.0
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

// Get BoldGrid settings.
\Boldgrid\Library\Util\Option::init();
$boldgrid_backup_settings     = get_site_option( 'boldgrid_backup_settings', array() );
$default_auto_update_settings = array(
	'plugins' => array(),
	'themes'  => array(),
	'wpcore'  => array(),
);
$auto_update_settings         = isset( $boldgrid_backup_settings['auto_update'] ) ? $boldgrid_backup_settings['auto_update'] : $default_auto_update_settings;
$translations                 = array(
	'active'   => esc_attr__( 'Active', 'boldgrid-backup' ),
	'inactive' => esc_attr__( 'Inactive', 'boldgrid-backup' ),
	'parent'   => esc_attr__( 'Parent', 'boldgrid-backup' ),
);

/**
 * Get Heading markup.
 *
 * @since 1.14.0
 *
 * @param array $boldgrid_backup_settings Boldgrid Backup Settings.
 * @param array $auto_update_settings Auto Update Settings from DB.
 * @return string
 */
function get_heading_markup( $boldgrid_backup_settings, $auto_update_settings ) {
	if ( empty( $auto_update_settings ) || 0 === $boldgrid_backup_settings['auto_backup'] ) {
		$bbs_link_open  = '';
		$bbs_link_close = '';

		if ( empty( $_GET['page'] ) || 'boldgrid-backup-settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$bbs_link_open  = '<a href="' . admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_auto_rollback' ) . '">';
			$bbs_link_close = '</a>';
		}

		return '
			<div><p>' .
			sprintf(
				// translators: 1: HTML anchor open tag, 2: HTML anchor close tag, 3: HTML em open tag, 4: HTML em close tag, 5: Plugin Title.
				esc_html__(
					'You have %3$sAuto Backup Before Update%4$s disabled in the %1$s%5$s Backup and Restore Settings%2$s.  Please consider enabling the setting.',
					'boldgrid-backup'
				),
				$bbs_link_open,
				$bbs_link_close,
				'<em>',
				'</em>',
				'Total Upkeep'
			) .
			'</p></div>' . PHP_EOL;
	}
}

/**
 * Get Premium markup.
 *
 * @since 1.14.0
 *
 * @return string
 */
function get_premium_markup() {
	$core        = apply_filters( 'boldgrid_backup_get_core', null );
	$premium_url = $core->go_pro->get_premium_url( 'bgbkup-settings-auto-update' );

	$premium_box = sprintf(
		'
		<div class="bg-box-bottom premium">
			<p>
				%1$s
				%2$s
			</p>
		</div>',
		/* 1 */ $core->go_pro->get_premium_button( $premium_url ),
		/* 2 */ __( 'Upgrade to Premium for the option to configure a delay on updates!', 'boldgrid-backup' )
	);

	return $premium_box;
}

/**
 * Get WP Core Update markup.
 *
 * @since 1.14.0
 *
 * @param array $auto_update_settings Auto Update Settings from DB.
 * @return string
 */
function get_wpcore_update_markup( $auto_update_settings ) {
	$wpcore_auto_updates = ! empty( $auto_update_settings['wpcore'] ) ?
		$auto_update_settings['wpcore'] : array();
	$wpcore_major        = ! empty( $wpcore_auto_updates['major'] );
	$wpcore_minor        = ! isset( $wpcore_auto_updates['minor'] ) || $wpcore_auto_updates['minor'];
	$wpcore_dev          = ! empty( $wpcore_auto_updates['dev'] );
	$wpcore_translation  = ! empty( $wpcore_auto_updates['translation'] );
	$wpcore_all          = ! empty( $wpcore_auto_updates['all'] ) ||
		( $wpcore_major && $wpcore_minor && $wpcore_dev && $wpcore_translation );

	$wpcore_update_markup = '
	<div class="bg-box">
		<div class="bg-box-top">
			' . esc_html__( 'Configure what is Auto Updated', 'boldgrid-backup' ) . '
		</div>
	<div class="bg-box-bottom">
	<table class="form-table div-table-body auto-update-settings">
		<tbody class="div-table-body">
		<tr>
			<th>' . esc_html__( 'WordPress Core', 'boldgrid-backup' ) .
				'<span class="dashicons dashicons-editor-help" data-id="wpcore-updates"></span>
			</th>
			<td></td>
			<td>' . esc_html__( 'All Update Types', 'boldgrid-backup' ) . '</td>
			<td class="td-toggle">
				<div class="toggle toggle-light toggle-group wpcore-toggle"
					data-wpcore="all"
					data-toggle-on="' . ( $wpcore_all ? 'true' : 'false' ) . '">
				</div>
				<input type="hidden" name="auto_update[wpcore][all]"
					value="' . ( $wpcore_all ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr class="table-help hide-help" data-id="wpcore-updates">
			<td colspan=4>
				<p>
				' . esc_html__( 'Select Which WordPress Core updates you wish to have automatically updated', 'boldgrid-backup' ) . '.
				</p>
			</td>
		</tr>
		<tr>
			<td colspan=2></td>
			<td>' . esc_html__( 'Major Updates', 'boldgrid-backup' ) . '</td>
			<td class="td-toggle">
				<div class="toggle toggle-light wpcore-toggle"
					data-wpcore="major"
					data-toggle-on="' . ( $wpcore_major ? 'true' : 'false' ) . '">
				</div>
				<input type="hidden" name="auto_update[wpcore][major]"
					value="' . ( $wpcore_major ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr>
			<td colspan=2></td>
			<td>' . esc_html__( 'Minor Updates', 'boldgrid-backup' ) . '</td>
			<td class="td-toggle">
				<div class="toggle toggle-light wpcore-toggle"
					data-wpcore="minor"
					data-toggle-on="' . ( $wpcore_minor ? 'true' : 'false' ) . '">
				</div>
				<input type="hidden" name="auto_update[wpcore][minor]"
					value="' . ( $wpcore_minor ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr>
			<td colspan=2></td>
			<td>' . esc_html__( 'Development Updates', 'boldgrid-backup' ) . '</td>
			<td class="td-toggle">
				<div class="toggle toggle-light wpcore-toggle"
					data-wpcore="dev"
					data-toggle-on="' . ( $wpcore_dev ? 'true' : 'false' ) . '">
				</div>
				<input type="hidden" name="auto_update[wpcore][dev]"
					value="' . ( $wpcore_dev ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr>
			<td colspan=2 />
			<td>' . esc_html__( 'Translation Updates', 'boldgrid-backup' ) . '</td>
			<td class="td-toggle">
				<div class="toggle toggle-light wpcore-toggle"
					data-wpcore="translation"
					data-toggle-on="' . ( $wpcore_translation ? 'true' : 'false' ) . '">
				</div>
				<input type="hidden" name="auto_update[wpcore][translation]"
					value="' . ( $wpcore_translation ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr>
			<td colspan=4 class="bglib-divider"></td>
		</tr>
		</tbody>';
	return $wpcore_update_markup;
}

/**
 * Get Plugins Update Markup.
 *
 * @since 1.14.0
 *
 * @param array $auto_update_settings Auto Update Settings from DB.
 * @param array $translations Translations.
 * @return string
 */
function get_plugins_update_markup( $auto_update_settings, $translations ) {
	$plugins_default    = ! empty( $auto_update_settings['plugins']['default'] );
	$plugin_auto_update = (bool) \Boldgrid\Library\Util\Option::get( 'plugin_autoupdate' );
	$plugins            = get_plugins();
	$plugins_active     = array();
	$plugins_inactive   = array();

	foreach ( $plugins as $slug => $plugin_data ) {
		if ( is_plugin_inactive( $slug ) ) {
			$plugins_inactive[ $slug ] = $plugin_data;
		} else {
			$plugins_active[ $slug ] = $plugin_data;
		}
	}

	$statuses = array(
		'Active',
		'Inactive',
	);

	$plugins_update_markup = '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Plugins', 'boldgrid-backup' ) .
			'<span class="dashicons dashicons-editor-help" data-id="plugin-updates"></span>
		</th>
	</tr>
	<tr class="table-help hide-help" data-id="plugin-updates">
		<td colspan=4>
			<p>' . esc_html__( 'Choose which Plugins you wish to update automatically', 'boldgrid-backup' ) . '</p>
		</td>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Plugins', 'boldgrid-backup' ) . '</td>
		<td>
			<div class="toggle toggle-light right" id="toggle-default-plugins"
			data-toggle-on="' . ( $plugins_default ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="auto_update[plugins][default]" value="' . ( $plugins_default ? 1 : 0 ) . '" />
		</td>
		<td>' . esc_html__( 'All Plugins', 'boldgrid-backup' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light toggle-group" id="toggle-plugins"></div>
		</td>
	</tr>';

	foreach ( $statuses as $status ) {
		$status_lower = strtolower( $status );

		$plugins_update_markup .= '<tr>
		<td colspan=2 />
		<td id="' . $status_lower . '-plugin-header">
			<h3>' . $translations[ $status_lower ] . '</h3>
		</td>
		<td>
			<span class="dashicons-arrow-down-alt2 dashicons bglib-collapsible-control" data-target=".' .
			$status_lower . '-collapsible" />
		</td>
		</tr>';

		foreach ( ${ 'plugins_' . $status_lower } as $slug => $plugin_data ) {
			// Enable if global setting is on, individual settings is on, or not set and default is on.
			$toggle = $plugin_auto_update || ! empty( $auto_update_settings['plugins'][ $slug ] ) ||
				( ! isset( $auto_update_settings['plugins'][ $slug ] ) && $plugins_default );
			$plugin = \Boldgrid\Library\Library\Plugin\Factory::create( $slug );

			$plugin->setUpdateData();

			$third_party = $plugin->updateData->thirdParty; //phpcs:ignore WordPress.NamingConventions.ValidVariableName

			if ( true === $third_party ) {
				$extra_info_icon = '<span class="help-icon dashicons dashicons-warning yellow" data-id="' . $slug . '-extra-info"></span>';
			} else {
				$extra_info_icon = '';
			}
			$plugins_update_markup .= '
				<tr id="' . $slug . '-row" class="' . $status_lower . '-collapsible bglib-collapsible bglib-collapsible-open">
					<td colspan=2 />
					<td>' . $plugin_data['Name'] . $extra_info_icon . '</td>
					<td class="td-toggle">
						<div class="td-slider toggle toggle-light plugin-toggle"
							data-plugin="' . $slug . '"
							data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
						</div>
						<input type="hidden" name="auto_update[plugins][' . $slug . ']"
						value="' . ( $toggle ? 1 : 0 ) . '" />
					</td>
				</tr>';

			if ( true === $third_party ) {
				$plugins_update_markup .= '
					<tr class="table-help hide-help" data-id="' . $slug . '-extra-info">
						<td colspan=2></td>
						<td colspan=1>
							<p style="position:relative;z-index=-1">' . esc_html__(
						'This plugin was not installed through the WordPress Plugins Repository. If auto updates are enabled, they will take place immediately.',
						'boldrid-backup'
					) . ' </p>
						</td>
						<td></td>
					</tr>';
			}
		}
	}

	$plugins_update_markup .= '<tr><td colspan=4 class="bglib-divider"></td></tr></tbody>';
	return $plugins_update_markup;
}

/**
 * Get Themes Update Markup.
 *
 * @since 1.14.0
 *
 * @param array $auto_update_settings Auto Update Settings from DB.
 * @param array $translations Translations.
 * @return string
 */
function get_themes_update_markup( $auto_update_settings, $translations ) {
	$themes_default    = ! empty( $auto_update_settings['themes']['default'] );
	$active_stylesheet = get_option( 'stylesheet' );
	$active_template   = get_option( 'template' );
	$themes            = wp_get_themes();
	$themes_active     = array();
	$themes_inactive   = array();

	foreach ( $themes as $stylesheet => $theme ) {
		$is_active = $stylesheet === $active_stylesheet;
		$is_parent = ( $active_stylesheet !== $active_template && $stylesheet === $active_template );

		if ( $is_active || $is_parent ) {
			$themes_active[ $stylesheet ] = $theme;
		} else {
			$themes_inactive[ $stylesheet ] = $theme;
		}
	}

	$theme_statuses = array(
		'Active',
		'Inactive',
	);

	$themes_update_markup = '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Themes', 'boldgrid-backup' ) .
			'<span class="dashicons dashicons-editor-help" data-id="theme-updates"></span>
		</th>
	</tr>
	<tr class="table-help hide-help" data-id="theme-updates">
	<td colspan=4>
		<p>' . esc_html__( 'Choose which Themes you wish to update automatically ', 'boldgrid-backup' ) . '</p>
	</td>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Themes', 'boldgrid-backup' ) . '</td>
		<td>
			<div class="toggle toggle-light right" id="toggle-default-themes"
				data-toggle-on="' . ( $themes_default ? 'true' : 'false' ) . '"></div>
				<input type="hidden" name="auto_update[themes][default]" value="' . ( $themes_default ? 1 : 0 ) . '" />
			</div>
		</td>
		<td>' . esc_html__( 'All Themes', 'boldgrid-backup' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light toggle-group" id="toggle-themes"></div>
		</td>
	</tr>';

	foreach ( $theme_statuses as $status ) {
		$status_lower = strtolower( $status );

		$themes_update_markup .= '<tr>
		<td colspan=2 />
		<td id="' . $status_lower . '-theme-header">
			<h3>' . $translations[ $status_lower ] . '</h3>
		</td>
		<td>
			<span class="dashicons-arrow-down-alt2 dashicons bglib-collapsible-control" data-target=".' .
			$status_lower . '-collapsible" />
		</td>
		</tr>';

		foreach ( ${ 'themes_' . $status_lower } as $stylesheet => $theme ) {
			$is_parent = ( $active_stylesheet !== $active_template && $stylesheet === $active_template );

			// Enable if global setting is on, individual settings is on, or not set and default is on.
			$toggle = ! empty( $auto_update_settings['themes'][ $stylesheet ] ) ||
				( ! isset( $auto_update_settings['themes'][ $stylesheet ] ) && $themes_default );

			$themes_update_markup .= '
				<tr id="' . $stylesheet . '-row" class="' . $status_lower . '-collapsible bglib-collapsible bglib-collapsible-open">
					<td colspan=2 />
					<td><div class="td-slider">' .
						$theme->get( 'Name' ) .
						( $is_parent ? ' (' . $translations['parent'] . ')' : '' ) .
					'</td>
					<td class="td-toggle">
						<div class="td-slider toggle toggle-light theme-toggle"
							data-stylesheet="' . $stylesheet . '"
							data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
						</div>
						<input type="hidden" name="auto_update[themes][' . $stylesheet . ']"
						value="' . ( $toggle ? 1 : 0 ) . '" />
					</td>
				</tr>';
		}
	}

	$themes_update_markup .= '</tbody>';

	return $themes_update_markup;
}

$auto_update_markup = ' ' . get_heading_markup( $boldgrid_backup_settings, $auto_update_settings );

$auto_update_markup .= '
	<div class="bg-box">
		<div class="bg-box-top">' .
			esc_html__( 'Configure When Auto Updates Occur', 'boldgrid-backup' ) . '
		</div>
	<div class="bg-box-bottom">';

// If the 'boldgrid_backup_premium_timely_auto_updates' filter does not exist, then the $auto_update_settings array will be returned.
$timely_update_markup = apply_filters( 'boldgrid_backup_premium_timely_auto_updates', $auto_update_settings );

/**
 * This was changed to be sure that there are no errors / issues if Total Upkeep is updated, but Total Upkeep Premium is not.
 * If Premium IS active and the above filter returns the $auto_update_settings array instead of the markup, then the user will need
 * to update to the newest version. If the Premium Plugin is active and the markup is returned above, then the timely update markup is
 * displayed. Lastly, if the premium plugin is not active at all, then the premium upsell is displayed.
 */
if ( $this->core->config->is_premium_done && $timely_update_markup === $auto_update_settings ) {
	$auto_update_markup .= sprintf(
		'<div class="bg-box-bottom premium">
			<p>
				<a class="button" href="%1$s">%2$s</a>
				%3$s
			</p>
		</div></div></div>',
		/* 1 */ admin_url( 'update-core.php' ),
		/* 2 */ __( 'View Updates', 'boldgrid-backup' ),
		/* 3 */ __( 'Upgrade to the newest version of Premium for the option to configure a delay on updates!', 'boldgrid-backup' )
	);
} elseif ( $this->core->config->is_premium_done ) {
	$auto_update_markup .= $timely_update_markup;
} else {
	$auto_update_markup .= get_premium_markup( $auto_update_settings );
	$auto_update_markup .= '</div></div>';
}

$auto_update_markup .= get_wpcore_update_markup( $auto_update_settings, $translations ) .
	get_plugins_update_markup( $auto_update_settings, $translations ) .
	get_themes_update_markup( $auto_update_settings, $translations ) .
	'</table></div></div>';

return $auto_update_markup;
