<?php
/**
 * File: auto-update.php
 *
 * Show "Auto Update" section on settings page.
 *
 * @since      SINCEVERSION
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

// Get BoldGrid settings.
\Boldgrid\Library\Util\Option::init();
$boldgrid_backup_settings = get_site_option( 'boldgrid_backup_settings', array() );
$auto_update_settings     = $boldgrid_backup_settings['auto_update'];
$translations             = array(
	'active'   => esc_attr__( 'Active', 'boldgrid-library' ),
	'inactive' => esc_attr__( 'Inactive', 'boldgrid-library' ),
	'parent'   => esc_attr__( 'Parent', 'boldgrid-library' ),
);


// Get Heading Markup
function get_heading_markup( $auto_update_settings ) {
	if ( empty( $auto_update_settings ) ) {
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
}

// GET PREMIUM MARKUP
function get_premium_markup ( $auto_update_settings ) {
	$core = apply_filters( 'boldgrid_backup_get_core', null );
	$premium_url = $core->go_pro->get_premium_url( 'bgbkup-settings-auto-update' );
	$premium_box = sprintf(
		'
		<div class="bg-box-bottom premium">
			%1$s

			<p>
				%2$s
				%3$s
			</p>
		</div>',
		/* 1 */ $premium_inputs,
		/* 2 */ $core->go_pro->get_premium_button( $premium_url ),
		/* 3 */ __( 'Upgrade to premium for the option to configure a delay on updates!', 'boldgrid-backup' )
	);

	$premium_markup = '<div class="bg-box">
		<div class="bg-box-top">' .
			esc_html__( 'Configure When Auto Updates Occur', 'boldgrid-library' ) . '
		</div>' . $premium_box . '</div>';
		
	return $premium_markup;
}

// CONFIGURE WHAT IS AUTO UPDATED

// WPCORE MARKUP SECTION
function get_wpcore_update_markup( $auto_update_settings, $translations ) {
	$wpcore_auto_updates = ! empty( $auto_update_settings['wpcore'] ) ?
		$auto_update_settings['wpcore'] : array();
	$wpcore_major        = ! empty( $wpcore_auto_updates['major'] );
	$wpcore_minor        = ! isset( $wpcore_auto_updates['minor'] ) || $wpcore_auto_updates['minor'];
	$wpcore_dev          = ! empty( $wpcore_auto_updates['dev'] );
	$wpcore_translation  = ! empty( $wpcore_auto_updates['translation'] );
	$wpcore_all          = ! empty( $wpcore_auto_updates['all'] ) ||
		( $wpcore_major && $wpcore_minor && $wpcore_dev && $wpcore_translation );

	$wpcore_update_markup .= '
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
				<input type="hidden" name="auto_update[wpcore][all]"
					value="' . ( $wpcore_all ? 1 : 0 ) . '" />
			</td>
		</tr>
		<tr>
			<td colspan=2></td>
			<td>' . esc_html__( 'Major Updates', 'boldgrid-library' ) . '</td>
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
			<td>' . esc_html__( 'Minor Updates', 'boldgrid-library' ) . '</td>
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
			<td>' . esc_html__( 'Development Updates', 'boldgrid-library' ) . '</td>
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
			<td>' . esc_html__( 'Translation Updates', 'boldgrid-library' ) . '</td>
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

// PLUGINS MARKUP SECTION
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
	
	$plugins_update_markup .= '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Plugins', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Plugins', 'boldgrid-library' ) .
			'<div class="toggle toggle-light right" id="toggle-default-plugins"
			data-toggle-on="' . ( $plugins_default ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="auto_update[plugins][default]" value="' . ( $plugins_default ? 1 : 0 ) . '" />
		</td>
		<td />
		<td>' . esc_html__( 'All Plugins', 'boldgrid-library' ) . '</td>
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

			$plugins_update_markup .= '
				<tr id="' . $slug . '-row" class="' . $status_lower . '-collapsible bglib-collapsible bglib-collapsible-open">
					<td colspan=2 />
					<td><div class="td-slider">' . $plugin_data['Name'] . '</td>
					<td class="td-toggle">
						<div class="td-slider toggle toggle-light plugin-toggle"
							data-plugin="' . $slug . '"
							data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
						</div>
						<input type="hidden" name="auto_update[plugins][' . $slug . ']"
						value="' . ( $toggle ? 1 : 0 ) . '" />
					</td>
				</tr>';
		}
	}

	$plugins_update_markup .= '<tr><td colspan=4 class="bglib-divider"></td></tr></tbody>';
	return $plugins_update_markup;
}

// THEMES MARKUP SECTION
function get_themes_update_markup( $auto_update_settings, $translations ) {
	$themes_default    = ! empty( $auto_update_settings['themes']['default'] );
	$active_stylesheet = get_option( 'stylesheet' );
	$active_template   = get_option( 'template' );
	$themes            = wp_get_themes();


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

	$themes_update_markup .= '<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Themes', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
	</tr>
	<tr>
		<td>' . esc_html__( 'Default For New Themes', 'boldgrid-library' ) .
			'<div class="toggle toggle-light right" id="toggle-default-themes"
			data-toggle-on="' . ( $themes_default ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="auto_update[themes][default]" value="' . ( $themes_default ? 1 : 0 ) . '" />
		</td>
		<td />
		<td>' . esc_html__( 'All Themes', 'boldgrid-library' ) . '</td>
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
			$toggle = $theme_auto_update || ! empty( $auto_update_settings['themes'][ $stylesheet ] ) ||
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
$auto_update_markup = ' ' . get_heading_markup( $auto_update_settings );
if ( $this->core->config->is_premium_done ) {
	include_once BOLDGRID_BACKUP_PREMIUM_PATH . '/admin/partials/settings/timely-auto-updates.php';
	$auto_update_markup .= get_when_update_markup( $auto_update_settings );
} else {
	$auto_update_markup .= get_premium_markup( $auto_update_settings );
}
$auto_update_markup .= get_wpcore_update_markup($auto_update_settings, $translations) .
	get_plugins_update_markup($auto_update_settings, $translations) .
	get_themes_update_markup($auto_update_settings, $translations) .
	'</table></div>';

return $auto_update_markup;