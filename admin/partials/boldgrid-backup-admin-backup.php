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
if ( false === empty( $archive_info['filepath'] ) ) {
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
?>
	<p>Total size: <?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ); ?></p>
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
<p>Error: <?php echo $archive_info['error']; ?></p>
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
