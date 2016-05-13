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

<h2>Backup Results</h2>

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
	if ( false === empty( $archive_info['filesize'] ) ) {
		// Successful backup.
		?>
<p>File Path: <?php echo $archive_info['filepath']; ?></p>
<p>File Size: <?php echo $this->bytes_to_human( $archive_info['filesize'] ); ?></p>
<p>Total size: <?php echo $this->bytes_to_human( $archive_info['total_size'] ); ?></p>
<p>Compressor: <?php echo $archive_info['compressor']; ?></p>
<?php
if ( true === isset( $archive_info['duration'] ) ) {
	?>
<p>Duration: <?php echo $archive_info['duration'] . ' seconds'; ?></p>
<?php
}
	} elseif ( false === empty( $archive_info['dryrun'] ) ) {
		// Dry run test.
		?>
<p>This was a dry run test.</p>
<p>File Path: <?php echo $archive_info['filepath']; ?></p>
<p>Total size: <?php echo $this->bytes_to_human( $archive_info['total_size'] ); ?></p>
<p>Compressor: <?php echo $archive_info['compressor']; ?></p>
<p>Duration: <?php echo $archive_info['duration'] . ' seconds'; ?></p>
<?php
	} elseif ( false === empty( $archive_info['error'] ) ) {
		// Error creating backup.
		?>
<p>There was an error creating a backup archive file.</p>
<p>Error: <?php echo $archive_info['error']; ?></p>
<?php
if ( true === isset( $archive_info['error_code'] ) ) {
	?>
<p>Error Details: <?php
if ( true === isset( $archive_info['error_message'] ) ) {
	echo $archive_info['error_message'];
} else {
	echo 'Unknown';
}
	?> (<?php
if ( true === isset( $archive_info['error_code'] ) ) {
	echo $archive_info['error_code'];
} else {
	echo '?';
}
	?>)</p>
<?php
}
	} else {
		// Unknown error.
		?>
<p>There was an unknown error creating a backup archive file.</p>
<?php
	}
}

?>
