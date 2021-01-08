<?php
/**
 * File: dir.php
 *
 * If many backups, list directory contents.
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$dir_browser_contents = 'Dirlist not found';
if ( $this->core->archive->is_virtual ) {
	$dir     = $this->core->backup_dir->get_path_to( $this->core->archive->basename );
	$dirlist = $this->core->wp_filesystem->dirlist( $dir );

	// Sort by filename.
	usort( $dirlist, function ( $item1, $item2 ) {
		return $item1['name'] < $item2['name'] ? -1 : 1;
	});

	$dir_browser_contents = '
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th>File</th>
					<th>Size</th>
				</tr>
			</thead>
			<tbody>';
	foreach ( $dirlist as $file ) {
		$dir_browser_contents .= '
			<tr>
				<td>' . esc_html( $file['name'] ) . '</td>
				<td>' . size_format( $file['size'], 2 ) . '</td>
			</tr>';
	}
	$dir_browser_contents .= '
			</tbody>
		</table>';
}

$dir_browser = '
	<div class="hidden" data-view-type="dir">
		' . $dir_browser_contents . '
	</div>
';

return $dir_browser;
