<?php
/**
 * File: browser-entry.php
 *
 * Create the <tr> for each file in the archilve.
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$class = $file['folder'] ? 'folder' : 'file';

$icon = 'folder' === $class ? 'dashicons dashicons-portfolio' : 'dashicons dashicons-media-default';

$size = empty( $file['size'] ) ? '' : Boldgrid_Backup_Admin_Utility::bytes_to_human( $file['size'] );

/*
 * Get the last modified time for this file.
 *
 * ZipArchive uses the server's local time as a timestamp, while PclZip uses UTC.
 */
$mtime = ! empty( $file['mtime'] ) ? $file['mtime'] : null;
$this->core->time->init( $mtime, $this->core->archive->compressor );

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
	$this->core->time->get_span()
);
