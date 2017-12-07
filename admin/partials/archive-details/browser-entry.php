<?php
/**
 * Create the <tr> for each file in the archilve.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 */

$class = $file['folder'] ? 'folder' : 'file';

$icon = 'folder' === $class ? 'dashicons dashicons-portfolio' : 'dashicons dashicons-media-default';

$size = empty( $file['size'] ) ? '' : Boldgrid_Backup_Admin_Utility::bytes_to_human( $file['size'] );

return sprintf(
	'<tr data-dir="%1$s">
		<td>
			<span class="%4$s"></span>
			<a class="%2$s">%3$s</a>
		</td>
		<td>
			%5$s
		</td>
		<td>
			%6$s
		</td>
	</tr>',
	$file['filename'],
	$class,
	basename( $file['filename'] ),
	$icon,
	$size,
	isset( $file['mtime'] ) ? date( 'M j, Y h:i:s a', $file['mtime'] ) : ''
);

?>