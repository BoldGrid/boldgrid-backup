<?php

/*  [1] => Array
(
	[filename] => .htaccess.bgb
	[stored_filename] => .htaccess.bgb
	[size] => 260
	[compressed_size] => 159
	[mtime] => 1505997198
	[comment] =>
	[folder] =>
	[index] => 1
	[status] => ok
	[crc] => 2743574654
)
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
	date( 'M j, Y h:i:s a', $file['mtime'] )
);

?>