<?php
/**
 * File: Connect.php
 *
 * @package    Boldgrid\Library
 * @subpackage Library\Views
 * @version    2.5.0
 * @author     BoldGrid <support@boldgrid.com>
 */

// Get BoldGrid settings.
\Boldgrid\Library\Util\Option::init();
$auto_update_settings = \Boldgrid\Library\Util\Option::get( 'autoupdate' );
$plugins_default      = ! empty( $auto_update_settings['plugins']['default'] );
$themes_default       = ! empty( $auto_update_settings['themes']['default'] );

// Get backup settings.
$boldgrid_backup_settings = get_site_option( 'boldgrid_backup_settings', array() );

// Get deprecated settings.
$plugin_auto_update = (bool) \Boldgrid\Library\Util\Option::get( 'plugin_autoupdate' );
$theme_auto_update  = (bool) \Boldgrid\Library\Util\Option::get( 'theme_autoupdate' );

// Get WordPress Core auto-update setting.
$wpcore_auto_updates = ! empty( $auto_update_settings['wpcore'] ) ?
	$auto_update_settings['wpcore'] : array();
$wpcore_major        = ! empty( $wpcore_auto_updates['major'] );
$wpcore_minor        = ! isset( $wpcore_auto_updates['minor'] ) || $wpcore_auto_updates['minor'];
$wpcore_dev          = ! empty( $wpcore_auto_updates['dev'] );
$wpcore_translation  = ! empty( $wpcore_auto_updates['translation'] );
$wpcore_all          = ! empty( $wpcore_auto_updates['all'] ) ||
	( $wpcore_major && $wpcore_minor && $wpcore_dev && $wpcore_translation );
$translations        = array(
	'active'   => esc_attr__( 'Active', 'boldgrid-library' ),
	'inactive' => esc_attr__( 'Inactive', 'boldgrid-library' ),
	'parent'   => esc_attr__( 'Parent', 'boldgrid-library' ),
);

// SPLIT PLUGINS INTO ACTIVE / INACTIVE

$plugins = get_plugins();

// Split into groups: active/inactive.
$plugins_active   = array();
$plugins_inactive = array();

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

// SPLIT THEMES UP INTO ACTIVE AND INACTIVE

$active_stylesheet = get_option( 'stylesheet' );
$active_template   = get_option( 'template' );
$themes            = wp_get_themes();

// Split into groups: active/inactive.
$themes_active   = array();
$themes_inactive = array();

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

// PAGE MARKUP

$return = ' ';

if ( empty( $boldgrid_backup_settings['auto_backup'] ) ) {
	$bbs_link_open  = '';
	$bbs_link_close = '';

	if ( empty( $_GET['page'] ) || 'boldgrid-backup-settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		$bbs_link_open  = '<a href="' . admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_auto_rollback' ) . '">';
		$bbs_link_close = '</a>';
	}

	$return .= '
		<div><p>' .
		sprintf(
			// translators: 1: HTML anchor open tag, 2: HTML anchor close tag, 3: HTML em open tag, 4: HTML em close tag, 5: Plugin Title.
			esc_html__(
				'You have %3$sAuto Backup Before Update%4$s disabled in the %1$s%5$s Backup and Restore Settings%2$s.  Please consider enabling the setting.',
				'boldgrid-library'
			),
			$bbs_link_open,
			$bbs_link_close,
			'<em>',
			'</em>',
			'Total Upkeep'
		) .
		'</p></div>' . PHP_EOL;
}
// CONFIGURE WHEN AUTO UPDATES OCCUR

$return .= '
<div class="bg-box">
	<div class="bg-box-top">' .
		esc_html__( 'Configure When Auto Updates Occur', 'boldgrid-library' ) . '
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="update-types">
			Its often that when new software is released, there are bugs. Users who update right away end up finding those bugs first. It\'s a good idea to delay updating until the developers have had time to work out the kinks.
		</p>
		<table class="form-table div-table-body auto-update-settings">
			<tbody class="div-table-body">
				<tr>
					<th>
						<p>' . esc_html__( 'Timely Auto Updates', 'boldgrid-backup' ) .
							'<span class="dashicons dashicons-editor-help" data-id="timely-updates"></span>
						</p>
					</th>
					<td>
						<input id="timely-updates-enabled" type="radio" name="timely_updates" value="1"';

if ( ! isset( $settings['timely_updates'] ) || 1 === $settings['timely_updates'] ) {
	$return .= ' checked';
}

$return .= '/>' . esc_html__( 'Enabled (Recommended)', 'boldgrid-backup' ) . ' &nbsp; 
						<input id="timely-updates-disabled" type="radio" name="timely_updates" value="0"';

if ( isset( $settings['auto_backup'] ) && 0 === $settings['auto_backup'] ) {
	$return .= ' checked';
}

$return .= '/>' . esc_html__( 'Disabled', 'boldgrid-backup' ) .
					'</td>
				</tr>
				<tr>
					<th>
						<p>' . esc_html__( 'When To Perform Updates', 'boldgrid-backup' ) .
							'<span class="dashicons dashicons-editor-help" data-id="timely-updates-defer"></span>
						</p>
					</th>
					<td>
						<input id="timely-updates-days" type="number" name="timely_updates_days" value="';
if ( ! isset( $settings['timely_updates_days'] ) || 1 === $settings['timely_updates_days'] ) {
	$return .= '1" />';
} else {
	$return .= $settings['timely_updates_days'] . '" />';
}

$return .= '<span>&nbsp;' . esc_html__( 'Days since update was release' ) . '</span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>';

// CONFIGURE WHAT IS AUTO UPDATED

$return .= '
<div class="bg-box">
	<div class="bg-box-top">
		' . esc_html__( 'Configure what is Auto Updated', 'boldgrid-library' ) . '
	</div>
<div class="bg-box-bottom">
	<p class="help" data-id="update-types">
			Major Updates typically done when there are extremely significant updates or changes to functionality.
			Minor updates are typically done when new features are added or when there are modifications to existing features.
			Sub-Minor updates are small updates that typically address bugs from previous releases. Sub-Minor updates can sometimes
			address minor security concerns, though these are usually done in Minor or Major releases.
	</p>
<table class="form-table div-table-body auto-update-settings">
	<tbody class="div-table-body">
	<tr>
		<th><p>' . esc_html__( 'WordPress Core', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</p></th>
		<td></td>
		<td>' . esc_html__( 'All Update Types', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light toggle-group wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcore_all ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][all]"
				value="' . ( $wpcore_all ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Major Updates', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcore_major ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][major]"
				value="' . ( $wpcore_major ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Minor Updates', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcore_minor ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][minor]"
				value="' . ( $wpcore_minor ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Development Updates', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcore_dev ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][dev]"
				value="' . ( $wpcore_dev ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2 />
		<td>' . esc_html__( 'Translation Updates', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcore_translation ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][translation]"
				value="' . ( $wpcore_translation ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=4 class="bglib-divider"></td>
	</tr>
	</tbody>';

// PLUGINS MARKUP SECTION

$return .= '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Plugins', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Plugins', 'boldgrid-library' ) .
			'<div class="toggle toggle-light right" id="toggle-default-plugins"
			data-toggle-on="' . ( $plugins_default ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="autoupdate[plugins][default]" value="' . ( $plugins_default ? 1 : 0 ) . '" />
		</td>
		<td />
		<td>' . esc_html__( 'All Plugins', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light toggle-group" id="toggle-plugins"></div>
		</td>
	</tr>';

foreach ( $statuses as $status ) {
	$status_lower = strtolower( $status );

	$return .= '<tr>
	<td colspan=2 />
	<td id="' . $status_lower . '-plugin-header">
		<h3>' . $translations[ $status_lower ] . '</h3>
	</td>
	<td>
		<span class="dashicons-arrow-down-alt2 dashicons bglib-collapsible-control" data-target=".' .
		$status_lower . '-collapsible" />
	</td>
	</tr>';

	foreach ( ${ 'plugins' . $status } as $slug => $plugin_data ) {
		// Enable if global setting is on, individual settings is on, or not set and default is on.
		$toggle = $plugin_auto_update || ! empty( $auto_update_settings['plugins'][ $slug ] ) ||
			( ! isset( $auto_update_settings['plugins'][ $slug ] ) && $plugins_default );

		$return .= '
			<tr id="' . $slug . '-row" class="' . $status_lower . '-collapsible bglib-collapsible bglib-collapsible-open">
				<td colspan=2 />
				<td><div class="td-slider">' . $plugin_data['Name'] . '</td>
				<td class="td-toggle">
					<div class="td-slider toggle toggle-light plugin-toggle"
						data-plugin="' . $slug . '"
						data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
					</div>
					<input type="hidden" name="autoupdate[plugins][' . $slug . ']"
					value="' . ( $toggle ? 1 : 0 ) . '" />
				</td>
			</tr>';
	}
}
$return .= '<tr><td colspan=4 class="bglib-divider"></td></tr></tbody>';

// THEMES MARKUP SECTION

$return .= '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Themes', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Themes', 'boldgrid-library' ) .
			'<div class="toggle toggle-light right" id="toggle-default-themes"
			data-toggle-on="' . ( $themes_default ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="autoupdate[themes][default]" value="' . ( $themes_default ? 1 : 0 ) . '" />
		</td>
		<td />
		<td>' . esc_html__( 'All Themes', 'boldgrid-library' ) . '</td>
		<td class="td-toggle">
			<div class="toggle toggle-light toggle-group" id="toggle-themes"></div>
		</td>
	</tr>';

foreach ( $theme_statuses as $status ) {
	$status_lower = strtolower( $status );

	$return .= '<tr>
	<td colspan=2 />
	<td id="' . $status_lower . '-theme-header">
		<h3>' . $translations[ $status_lower ] . '</h3>
	</td>
	<td>
		<span class="dashicons-arrow-down-alt2 dashicons bglib-collapsible-control" data-target=".' .
		$status_lower . '-collapsible" />
	</td>
	</tr>';

	foreach ( ${ 'themes' . $status } as $stylesheet => $theme ) {
		$is_parent = ( $active_stylesheet !== $active_template && $stylesheet === $active_template );

		// Enable if global setting is on, individual settings is on, or not set and default is on.
		$toggle = $theme_auto_update || ! empty( $auto_update_settings['themes'][ $stylesheet ] ) ||
			( ! isset( $auto_update_settings['themes'][ $stylesheet ] ) && $themes_default );

		$return .= '
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
					<input type="hidden" name="autoupdate[themes][' . $stylesheet . ']"
					value="' . ( $toggle ? 1 : 0 ) . '" />
				</td>
			</tr>';
	}
}
$return .= '</tbody></table>';

$return .= '</div>';

return $return;
