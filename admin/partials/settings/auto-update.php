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
$autoupdateSettings = \Boldgrid\Library\Util\Option::get( 'autoupdate' );
$pluginsDefault     = ! empty( $autoupdateSettings['plugins']['default'] );
$themesDefault      = ! empty( $autoupdateSettings['themes']['default'] );

// Get backup settings.
$boldgridBackupSettings = get_site_option( 'boldgrid_backup_settings', array() );

// Get deprecated settings.
$pluginAutoupdate = (bool) \Boldgrid\Library\Util\Option::get( 'plugin_autoupdate' );
$themeAutoupdate  = (bool) \Boldgrid\Library\Util\Option::get( 'theme_autoupdate' );

// Get WordPress Core auto-update setting.
$wpcoreAutoupdates = ! empty( $autoupdateSettings['wpcore'] ) ?
	$autoupdateSettings['wpcore'] : array();
$wpcoreMajor       = ! empty( $wpcoreAutoupdates['major'] );
$wpcoreMinor       = ! isset( $wpcoreAutoupdates['minor'] ) || $wpcoreAutoupdates['minor'];
$wpcoreDev         = ! empty( $wpcoreAutoupdates['dev'] );
$wpcoreTranslation = ! empty( $wpcoreAutoupdates['translation'] );
$wpcoreAll         = ! empty( $wpcoreAutoupdates['all'] ) ||
	( $wpcoreMajor && $wpcoreMinor && $wpcoreDev && $wpcoreTranslation );
$translations      = array(
	'active'   => esc_attr__( 'Active', 'boldgrid-library' ),
	'inactive' => esc_attr__( 'Inactive', 'boldgrid-library' ),
	'parent'   => esc_attr__( 'Parent', 'boldgrid-library' ),
);

$plugins = get_plugins();

// Split into groups: active/inactive.
$pluginsActive   = array();
$pluginsInactive = array();

foreach ( $plugins as $slug => $pluginData ) {
	if ( is_plugin_inactive( $slug ) ) {
		$pluginsInactive[ $slug ] = $pluginData;
	} else {
		$pluginsActive[ $slug ] = $pluginData;
	}
}

$statuses = array(
	'Active',
	'Inactive',
);

$return            = ' ';
$helpMarkup        = sprintf(
	// translators: 1: HTML anchor open tag, 2: HTML anchor close tag.
	esc_html__(
		'Automatically perform WordPress core, plugin, and theme updates. This feature utilizes %1$sWordPress filters%2$s, which enables automatic updates as they become available.',
		'boldgrid-backup'
	),
	'<a target="_blank" href="https://codex.wordpress.org/Configuring_Automatic_Background_Updates">',
	'</a>'
);

if ( empty( $boldgridBackupSettings['auto_backup'] ) ) {
	$bbsLinkOpen  = '';
	$bbsLinkClose = '';

	if ( empty( $_GET['page'] ) || 'boldgrid-backup-settings' !== $_GET['page'] ) {
		$bbsLinkOpen = '<a href="' . admin_url( 'admin.php?page=boldgrid-backup-settings&section=section_auto_rollback' ) . '">';
		$bbsLinkClose = '</a>';
	}

	$return .= '
		<div><p>' .
		sprintf(
			// translators: 1: HTML anchor open tag, 2: HTML anchor close tag, 3: HTML em open tag, 4: HTML em close tag, 5: Plugin Title.
			esc_html__(
				'You have %3$sAuto Backup Before Update%4$s disabled in the %1$s%5$s Backup and Restore Settings%2$s.  Please consider enabling the setting.',
				'boldgrid-library'
			),
			$bbsLinkOpen,
			$bbsLinkClose,
			'<em>',
			'</em>',
			'Total Upkeep'
		) .
		'</p></div>' . PHP_EOL;
}

$return .= '
<div class="bg-box">
	<div class="bg-box-top">
		' . esc_html__( 'Configure what is Auto Updated', 'boldgrid-library' ) . '
	</div>
<div class="bg-box-bottom">
	<p class="help" data-id="update-types">' . esc_html__(
			'Major Updates typically done when there are extremely significant updates or changes to functionality.
			Minor updates are typically done when new features are added or when there are modifications to existing features.
			Sub-Minor updates are small updates that typically address bugs from previous releases. Sub-Minor updates can sometimes
			address minor security concerns, though these are usually done in Minor or Major releases.',
			'boldgrid-backup') . '</p>
<table class="form-table div-table-body">
	<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'WordPress Core', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
		<td></td>
		<td>' . esc_html__( 'All Update Types', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light toggle-group wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcoreAll ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][all]"
				value="' . ( $wpcoreAll ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Major Updates', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcoreMajor ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][major]"
				value="' . ( $wpcoreMajor ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Minor Updates', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcoreMinor ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][minor]"
				value="' . ( $wpcoreMinor ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2></td>
		<td>' . esc_html__( 'Development Updates', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcoreDev ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][dev]"
				value="' . ( $wpcoreDev ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2 />
		<td>' . esc_html__( 'Translation Updates', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light wpcore-toggle"
				data-wpcore="all"
				data-toggle-on="' . ( $wpcoreTranslation ? 'true' : 'false' ) . '">
			</div>
			<input type="hidden" name="autoupdate[wpcore][translation]"
				value="' . ( $wpcoreTranslation ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=4 class="divider"></td>
	</tr>
	</tbody>
	<tbody class="div-table-body">
	<tr>
		<th>' . esc_html__( 'Plugins', 'boldgrid-library' ) .
			'<span class="dashicons dashicons-editor-help" data-id="update-types"></span>
		</th>
		<td></td>
		<td>' . esc_html__( 'Default For New Plugins', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light" id="toggle-default-plugins"
			data-toggle-on="' . ( $pluginsDefault ? 'true' : 'false' ) . '"></div>
			<input type="hidden" name="autoupdate[plugins][default]" value="' . ( $pluginsDefault ? 1 : 0 ) . '" />
		</td>
	</tr>
	<tr>
		<td colspan=2 />
		<td>' . esc_html__( 'All Plugins', 'boldgrid-library' ) . '</td>
		<td>
			<div class="toggle toggle-light toggle-group" id="toggle-plugins"></div>
		</td>
	</tr>';

foreach ( $statuses as $status ) {
	$statusLower = strtolower( $status );

	$return .= '<tr>
	<td colspan=2 />
	<td colspan=2>
		<h3>' . $translations[ $statusLower ] . '</h3>
		<span class="dashicons dashicons-arrow-down-alt2 bglib-collapsible-' . $statusLower . '"></span>
	</td>
	</tr>';

	foreach ( ${ 'plugins' . $status } as $slug => $pluginData ) {
		// Enable if global setting is on, individual settings is on, or not set and default is on.
		$toggle = $pluginAutoupdate || ! empty( $autoupdateSettings['plugins'][ $slug ] ) ||
			( ! isset( $autoupdateSettings['plugins'][ $slug ] ) && $pluginsDefault );

		$return .= '
			<tr class="collapse ' . $status . '-collapse">
				<td colspan=2 />
				<td>' . $pluginData['Name'] . '</td>
				<td>
					<div class="toggle toggle-light plugin-toggle"
						data-plugin="' . $slug . '"
						data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
					</div>
					<input type="hidden" name="autoupdate[plugins][' . $slug . ']"
					value="' . ( $toggle ? 1 : 0 ) . '" />
				</td>
			</tr>';
	}
}

$return .= '</tbody></table>

<div class="auto-update-management div-table">
	<div class="auto-update-settings div-table-body">
		<div class="div-table-row">
			<div class="div-tableCell" style="border-bottom: 1px solid #e2e4e7"></div>
			<div class="div-tableCell" style="border-bottom: 1px solid #e2e4e7"></div>
			<div class="div-tableCell" style="border-bottom: 1px solid #e2e4e7"></div>
		</div>
		<div class="div-table-row">
			<div class="div-tableCell">
				<h3>' . esc_html__( 'Plugins', 'boldgrid-library' ) . '</h3>
			</div>
			<div class="div-tableCell"></div>
			<div class="div-tableCell">
				<div class="div-table"><div class="div-table-body">
					<div class="div-table-row">
						<div class="div-tableCell">' .
	esc_html__( 'Default for New Plugins', 'boldgrid-library' ) . '</div>
						<div class="div-tableCell">
							<div class="toggle toggle-light" id="toggle-default-plugins"
								data-toggle-on="' . ( $pluginsDefault ? 'true' : 'false' ) . '">
							</div>
						</div>
						<input type="hidden" name="autoupdate[plugins][default]"
							value="' . ( $pluginsDefault ? 1 : 0 ) . '" />
					</div>
					<div class="div-table-row"><br /></div>
					<div class="div-table-row">
						<div class="div-tableCell">' .
	esc_html__( 'All Plugins', 'boldgrid-library' ) . '</div>
						<div class="div-tableCell">
							<div class="toggle toggle-light toggle-group" id="toggle-plugins"></div>
						</div>
					</div>
';

foreach ( $statuses as $status ) {
	$statusLower = strtolower( $status );

	$return .= '<div class="div-table-contents">
	<div class="div-table-row bglib-collapsible-control' . ( 'Inactive' !== $status ?
		' bglib-collapsible-open' : '' ) . '">
		<div class="div-tableCell"><h3>' . $translations[ $statusLower ] . '</h3></div>
		<div class="div-tableCell">
			<span class="dashicons dashicons-arrow-down-alt2 bglib-collapsible-control"></span>
		</div>
	</div>
';

	foreach ( ${ 'plugins' . $status } as $slug => $pluginData ) {
		// Enable if global setting is on, individual settings is on, or not set and default is on.
		$toggle = $pluginAutoupdate || ! empty( $autoupdateSettings['plugins'][ $slug ] ) ||
			( ! isset( $autoupdateSettings['plugins'][ $slug ] ) && $pluginsDefault );

		$return .= '
			<div class="div-table-row plugin-update-setting bglib-collapsible-' . $statusLower . '">
				<div class="div-tableCell">' . $pluginData['Name'] . '</div>
				<div class="div-tableCell">
					<div class="toggle toggle-light plugin-toggle"
						data-plugin="' . $slug . '"
						data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
					</div>
				</div>
				<input type="hidden" name="autoupdate[plugins][' . $slug . ']"
					value="' . ( $toggle ? 1 : 0 ) . '" />
			</div>
		';
	}
	$return .= '</div>
';
}

$return .= '
				</div></div>
			</div>
		</div>
	</div>
</div>

	</div>
</div>

<div class="bg-box">
	<div class="bg-box-top">
		' . esc_html__( 'Themes', 'boldgrid-library' ) . '
		<span class="dashicons dashicons-editor-help" data-id="themes-autoupdate"></span>
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="themes-autoupdate">' . $helpMarkup . '</p>

<div class="auto-update-management div-table">
	<div class="auto-update-settings div-table-body">
		<div class="div-table-row">
			<div class="div-tableCell">

				<div class="div-table"><div class="div-table-body">
					<div class="div-table-row">
						<div class="div-tableCell">' .
	esc_html__( 'Default for New Themes', 'boldgrid-library' ) . '</div>
						<div class="div-tableCell">
							<div class="toggle toggle-light" id="toggle-default-themes"
								data-toggle-on="' . ( $themesDefault ? 'true' : 'false' ) . '">
							</div>
						</div>
						<input type="hidden" name="autoupdate[themes][default]"
							value="' . ( 'true' === $themesDefault ? 1 : 0 ) . '" />
					</div>
					<div class="div-table-row"><br /></div>
					<div class="div-table-row">
						<div class="div-tableCell">' .
	esc_html__( 'All Themes', 'boldgrid-library' ) . '</div>
						<div class="div-tableCell">
							<div class="toggle toggle-light toggle-group" id="toggle-themes"></div>
						</div>
					</div>
';

$activeStylesheet = get_option( 'stylesheet' );
$activeTemplate   = get_option( 'template' );
$themes           = wp_get_themes();

// Split into groups: active/inactive.
$themesActive   = array();
$themesInactive = array();

foreach ( $themes as $stylesheet => $theme ) {
	$isActive = $stylesheet === $activeStylesheet;
	$isParent = ( $activeStylesheet !== $activeTemplate && $stylesheet === $activeTemplate );

	if ( $isActive || $isParent ) {
		$themesActive[ $stylesheet ] = $theme;
	} else {
		$themesInactive[ $stylesheet ] = $theme;
	}
}

$statuses = array(
	'Active',
	'Inactive',
);

foreach ( $statuses as $status ) {
	$statusLower = strtolower( $status );

	$return .= '<div class="div-table-contents">
	<div class="div-table-row bglib-collapsible-control' . ( 'Inactive' !== $status ?
		' bglib-collapsible-open' : '' ) . '">
		<div class="div-tableCell"><h3>' . $translations[ $statusLower ] . '</h3></div>
		<div class="div-tableCell">
			<span class="dashicons dashicons-arrow-down-alt2 bglib-collapsible-' . $statusLower .'"></span>
		</div>
	</div>
';

	foreach ( ${ 'themes' . $status } as $stylesheet => $theme ) {
		$isParent = ( $activeStylesheet !== $activeTemplate && $stylesheet === $activeTemplate );

		// Enable if global setting is on, individual settings is on, or not set and default is on.
		$toggle = $themeAutoupdate || ! empty( $autoupdateSettings['themes'][ $stylesheet ] ) ||
			( ! isset( $autoupdateSettings['themes'][ $stylesheet ] ) && $themesDefault );

		$return .= '
			<div class="div-table-row theme-update-setting bglib-collapsible">
				<div class="div-tableCell">' . $theme->get( 'Name' ) .
				( $isParent ? ' (' . $translations['parent'] . ')' : '' ) . '</div>
				<div class="div-tableCell">
					<div class="toggle toggle-light theme-toggle"
						data-stylesheet="' . $stylesheet . '"
						data-toggle-on="' . ( $toggle ? 'true' : 'false' ) . '">
					</div>
				</div>
				<input type="hidden" name="autoupdate[themes][' . $stylesheet . ']"
					value="' . ( $toggle ? 1 : 0 ) . '" />
			</div>
		';
	}

	$return .= '</div>
';
}

$return .= '
				</div></div>
			</div>
		</div>
	</div>
</div>

	</div>
</div>
';

return $return;
