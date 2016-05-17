<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

?>
<div class='wrap'>
<h1>BoldGrid Backup</h1>
<h2>Backup Archives</h2>
<?php

// Create URL for backup now.
$backup_url = get_admin_url( null, 'admin.php?page=boldgrid-backup&backup_now=1' );

$backup_url = wp_nonce_url( $backup_url, 'boldgrid-backup-backup', 'backup_auth' );

// Print the list of archive files.
if ( false === empty( $archives ) ) {
	?>
<table id='backup-archive-list-table'>
	<thead id='backup-archive-list-header'>
		<tr>
			<th class='backup-archive-list-path'>Filename</th>
			<th class='backup-archive-list-size'>Size</th>
			<th class='backup-archive-list-date'>Date</th>
			<th class='backup-archive-list-download'></th>
			<th class='backup-archive-list-restore'></th>
		</tr>
	</thead>
	<tbody id='backup-archive-list-body'>
<?php
foreach ( $archives as $key => $archive ) {
	// Create URL for restoring from an archive file.
	$restore_url = get_admin_url( null,
		'admin.php?page=boldgrid-backup&restore_now=1&archive_key=' . $key . '&archive_filename=' .
	$archive['filename'] );

	$restore_url = wp_nonce_url( $restore_url, 'boldgrid-backup-restore', 'restore_auth' );

	// Create URL for deleting an archive file.
	$delete_url = get_admin_url( null,
		'admin.php?page=boldgrid-backup&delete_now=1&archive_key=' . $key . '&archive_filename=' .
	$archive['filename'] );

	$delete_url = wp_nonce_url( $delete_url, 'boldgrid-backup-delete', 'delete_auth' );

	?>
	<tr>
		<td class='backup-archive-list-path'><?php echo $archive['filename']; ?></td>
		<td class='backup-archive-list-size'><?php echo $this->bytes_to_human( $archive['filesize'] ); ?></td>
		<td class='backup-archive-list-date'><?php echo $archive['filedate']; ?></td>
		<td class='backup-archive-list-download'><a
			id='backup-archive-download-<?php echo $key; ?>'
			class='button backup-archive-list-download-button' href='#'
			data-key='<?php echo $key ?>' data-filepath='<?php echo $archive['filepath']; ?>'
			data-filename='<?php echo $archive['filename']; ?>'>Download</a></td>
		<td class='backup-archive-list-restore'><a class='button'
			href='<?php echo $restore_url; ?>'> Restore</a></td>
		<td class='backup-archive-list-restore'><a class='button'
			href='<?php echo $delete_url; ?>'> Delete</a></td>
		</tr>
<?php
}
	?>
	</tbody>
</table>
<h2>Backup Archive Summary</h2>
<table id='backup-archive-summary-table'>
	<tbody id='backup-archive-list-header'>
		<tr>
			<td class='backup-archive-summary-metric'>Archive Count:</td>
			<td class='backup-archive-summary-value'><?php echo $archives_count; ?></td>
		</tr>
		<tr>
			<td class='backup-archive-summary-metric'>Total Size:</td>
			<td class='backup-archive-summary-value'><?php echo $this->bytes_to_human( $archives_size );?></td>
		</tr>
	</tbody>
</table>
<?php
} else {
	?>
<p>There are no files created.</p>
<?php
}
?>
<p>
	<a class='button' href='<?php echo $backup_url; ?>'>Backup Site Now</a>
</p>
</div>
