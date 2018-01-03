<?php
/**
 * Create a <tr> for a single local backup.
 *
 * When the Backups page is generated, we loop through each local archive and
 * include this file, which returns the <tr>.
 *
 * @since 1.5.4
 *
 * @param array $archive {
 *     An array of details about an archive.
 *
 *     @type string $filepath    /home/user/boldgrid_backup/file.zip
 *     @type string $filename    file.zip
 *     @type string $filedate    1/2/2018 10:16 AM
 *     @type int    $filesize    209814211
 *     @type int    $lastmodunix 1514906188
 * }
 */

defined( 'WPINC' ) ? : die;

$md5_id = md5( $archive['filepath'] );

$locations = array( __( 'Local backup', 'boldgrid-backup' ) );

/**
 * Allow other plugins to modify text showing where this backup is located.
 *
 * @since 1.5.4
 *
 * @param string $filename
 * @param array  $locations
 */
$locations = apply_filters( 'boldgrid_backup_backup_locations', $locations, $archive['filename'] );

return sprintf( '
	<tr data-timestamp="%1$s">
		<td>
			%2$s
		</td>
		<td>
			<strong>%3$s</strong>: %4$s
		</td>
		<td>
			%6$s
		</td>
		<td class="auto-width">
			%5$s
		</td>
	</tr>',
	/* 1 */ $archive['lastmodunix'],
	/* 2 */ implode( '<br />', $locations ),
	/* 3 */ __( 'Backup', 'boldgrid-backup' ),
	/* 4 */ $archive['filedate'],
	/* 5 */ sprintf( '<a class="button" href="admin.php?page=boldgrid-backup-archive-details&md5=%1$s">%2$s</a>', $md5_id, __( 'View details', 'boldgrid-backup' ) ),
	/* 6 */ Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] )
);

?>
