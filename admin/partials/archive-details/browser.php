<?php
/**
 * Display the Archive Browser section on the Archive Details page.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 */

printf( '<h2>%1$s</h2>', __( 'Archive Browser', 'boldgrid-backup' ) );

$intro_message = $this->core->lang['icon_warning'] . ' ' . __( 'Please note that most functionality for the Archive Browser, such as one click file restorations, is contained within the <a href="%2$s" target="_blank">Premium version</a>. For help with restoring a single file without this one click feature, please <a href="%1$s" target="_blank">click here</a>.', 'boldgrid-backup' );
$intro = sprintf( $intro_message, 'https://www.boldgrid.com/support', 'https://www.boldgrid.com/wordpress-backup-plugin' );

/**
 * Allow other plugins to modify this intro.
 *
 * @since 1.5.3
 *
 * @param string $intro
 */
$intro = apply_filters( 'boldgrid_backup_archive_browser_intro', $intro );

echo empty( $intro ) ? '' : sprintf( '<p>%1$s</p>', $intro );
?>

<div id="zip_browser">

	<div class="breadcrumbs" style="padding:8px 10px;">
	</div>

	<div class="listing">
		<table>
			<tbody>
			</tbody>
		</table>
	</div>

</div>
