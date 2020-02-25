<?php
/**
 * File: storage-location.php
 *
 * Display a single provider on the settings page.
 * This page returns the html markup needed for the <tr> #storage_locations table.
 *
 * @link https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @param  array $location {
 *     A remote storage location / provider.
 *
 *     @type string $title     Amazon S3
 *     @type string $key       amazon_s3
 *     @type string $configure A url to configure the provider, such as
 *                             admin.php?page=boldgrid-backup-amazon-s3
 *     @type bool   $is_setup  Whether or not this provider is properly
 *                             configured / setup.
 *     @type bool   $enabled   Whether or not this provider is enabled.
 * }
 * @return string
 */

defined( 'WPINC' ) || die;

$configure = '';

// Some storage locations need to be authorized, such as Google Drive.
if ( ! empty( $location['authorized'] ) ) {
	$configure = sprintf( '&#10003; %1$s | ', __( 'Authorized', 'boldgrid-backup' ) );
} elseif ( ! empty( $location['authorize'] ) ) {
	$configure .= '<a href="' . esc_url( $location['authorize'] ) . '">' . __( 'Authorize', 'boldgrid-backup' ) . '</a> | ';
}

$configure_link = '<a href="%1$s&TB_iframe=true&width=600&height=550" class="thickbox">%2$s</a>';

if ( $location['is_setup'] && ! empty( $location['configure'] ) ) {
	$configure .= sprintf( '&#10003; %1$s', __( 'Configured', 'boldgrid-backup' ) );
	$configure .= ' (' . sprintf( $configure_link, $location['configure'], __( 'update', 'boldgrid-backup' ) ) . ')';
} elseif ( ! empty( $location['configure'] ) ) {
	$configure .= sprintf( $configure_link, $location['configure'], __( 'Configure', 'boldgrid-backup' ) );
}

$disabled = $location['is_setup'] ? '' : 'disabled';

$checked = isset( $location['enabled'] ) && true === $location['enabled'] ? 'checked' : '';

return sprintf(
	'
	<tr data-key="%4$s">
		<td style="vertical-align:top; min-width:120px;">
			<input type="checkbox" name="storage_location[%4$s]" value="1" %3$s %5$s> <strong>%1$s</strong>
		</td>
		<td class="configure">
			%2$s
			%6$s
		</td>
	</tr>
	',
	$location['title'],
	$configure,
	$disabled,
	$location['key'],
	$checked,
	( empty( $location['error'] ) ? '' : '<div class="notice notice-error inline" style="margin-bottom:0;">' . esc_html( $location['error'] ) . '</div>' )
);
