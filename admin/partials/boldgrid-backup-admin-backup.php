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

<?php
if ( true === empty( $_GET['restore_now'] ) ) {
?>
<h2>Backup Results</h2>
<?php
}
?>

<?php

/**
 * If data exists in the $archive_info array, then print results, else show an error message.
 *
 * @param array $archive_info {
 *        @type string compressor The code-name for the compressor used to create the archive.
 *        @type string $filepath The absolute file path.
 *        @type int $filesize The archive file size.
 *        @type int $total_size The total size of the uncompressed files.
 *        @type string $error A friendly error message.
 *        @type int $error_code An integer from a compressor constant.
 *        @type string $error_message A human-readable interpretation or the error code.
 *        }
 */
if ( false === empty( $archive_info ) ) {
	if ( false === empty( $archive_info['dryrun'] ) ) {
?>
<div class="notice notice-info"><p>This was a dry run test.</p></div>
<?php
	}

	if ( true === empty( $archive_info['error'] ) ) {
		// Successful backup.
?>
<div class="notice notice-success">
	<p><?php
	if ( false === empty( $_GET['restore_now'] ) ) {
		echo 'The selected archive file has been successfully restored.';
	} else {
		echo 'A backup archive file has been created successfully.';
	}
?></p>
<?php
$filename = '';

if ( false === empty( $archive_info['filepath'] ) ) {
	$filename = basename( $archive_info['filepath'] );
?>
	<p>File Path: <?php echo $archive_info['filepath']; ?></p>
<?php
}

if ( false === empty( $archive_info['filesize'] ) ) {
?>
	<p>File Size: <?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ); ?></p>
<?php
}

if ( false === empty( $archive_info['total_size'] ) ) {
	$size = Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] );
?>
	<p>Total size: <?php echo $size; ?></p>
<?php
}

if ( false === empty( $archive_info['compressor'] ) ) {
?>
	<p>Compressor: <?php echo $archive_info['compressor']; ?></p>
<?php
}

if ( true === isset( $archive_info['duration'] ) ) {
?>
	<p>Duration: <?php echo $archive_info['duration'] . ' seconds'; ?></p>
<?php
}
?>
</div>
<div class='hidden'>
<table>
	<tbody id='archive-list-new'>
<?php

// Make the new archive list.
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
		<td class='backup-archive-list-size'><?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] ); ?></td>
		<td class='backup-archive-list-date'><?php echo $archive['filedate']; ?></td>
		<td class='backup-archive-list-download'><a
			id='backup-archive-download-<?php echo $key; ?>'
			class='button action-download' href='#'
			data-key='<?php echo $key ?>' data-filepath='<?php echo $archive['filepath']; ?>'
			data-filename='<?php echo $archive['filename']; ?>'>Download</a></td>
		<td class='backup-archive-list-restore'><a class='button action-restore'
			href='<?php echo $restore_url; ?>' data-filename='<?php echo $archive['filename']; ?>'>
			Restore</a></td>
		<td class='backup-archive-list-delete'><a class='button action-delete'
			href='<?php echo $delete_url; ?>' data-filename='<?php echo $archive['filename']; ?>'>
			Delete</a></td>
	</tr>
<?php
}
?>
	</tbody>
</table>
</div>
<div class='hidden'>
<span id='archives-new-count'><?php echo $archives_count; ?></span>
<span id='archives-new-size'><?php echo $archives_size; ?></span>
</div>
<?php
	} else {
		// Error creating backup.
		?>
<div class="notice notice-error"><p><?php
if ( false === empty( $_GET['restore_now'] ) ) {
	echo __( 'There was an error restoring the selected backup archive file.' );
} else {
	echo __( 'There was an error creating a backup archive file.' );
}
?></p>
<?php
if ( false === empty( $archive_info['filepath'] ) ) {
?>
	<p>File Path: <?php echo $archive_info['filepath']; ?></p>
<?php
}
?>
<p><?php echo $archive_info['error']; ?></p>
<?php
if ( true === isset( $archive_info['error_message'] ) ) {
	echo '<p>Error Details: ' . $archive_info['error_message'];

	if ( true === isset( $archive_info['error_code'] ) ) {
		echo ' (' . $archive_info['error_code'] . ')';
	}

	echo '</p>';
}
?>
</div>
<?php
	}
}

?>
<div id='end-backup'></div>
