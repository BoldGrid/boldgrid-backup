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

if ( empty( $_POST['restore_now'] ) ) {
?>
<h2><?php esc_html_e( 'Backup Results', 'boldgrid-backup' ); ?></h2>
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
if ( ! empty( $archive_info ) ) {
	if ( ! empty( $archive_info['dryrun'] ) ) {
?>
<div class="notice notice-info">
	<p><?php esc_html_e( 'This was a dry run test', 'boldgrid-backup' ); ?>.</p>
</div>
<?php
	}

	if ( empty( $archive_info['error'] ) ) {
		// Successful backup.
?>
<div class="notice notice-success">
	<p><?php
	if ( ! empty( $_POST['restore_now'] ) ) {
		esc_html_e( 'The selected archive file has been successfully restored', 'boldgrid-backup' );
	} else {
		esc_html_e( 'A backup archive file has been created successfully', 'boldgrid-backup' );
	}
?>.</p>
<?php
$filename = '';

if ( ! empty( $archive_info['filepath'] ) ) {
	$filename = basename( $archive_info['filepath'] );
?>
	<p><?php
	printf(
		esc_html__( 'File Path: %s', 'boldgrid-backup' ),
		$archive_info['filepath']
	);
?></p>
<?php
}

if ( ! empty( $archive_info['filesize'] ) ) {
?>
	<p><?php
	printf(
		esc_html__( 'File Size: %s', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] )
	);
?></p>
<?php
}

if ( ! empty( $archive_info['total_size'] ) ) {
?>
	<p><?php
	printf(
		esc_html__( 'Total size: %s', 'boldgrid-backup' ),
		Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] )
	);
?></p>
<?php
}

if ( ! empty( $archive_info['compressor'] ) ) {
?>
	<p><?php
	printf(
		esc_html__( 'Compressor: %s', 'boldgrid-backup' ),
		$archive_info['compressor']
	);
?></p>
<?php
}

if ( isset( $archive_info['duration'] ) ) {
?>
	<p><?php
	printf(
		esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
		$archive_info['duration']
	);
?></p>
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
	include dirname( __FILE__ ) . '/boldgrid-backup-admin-archives.php';
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
		// Error creating or restoring a backup archive file.
		?>
<div class="notice notice-error"><p><?php
if ( ! empty( $_POST['restore_now'] ) ) {
	esc_html_e( 'There was an error restoring the selected backup archive file', 'boldgrid-backup' );
} else {
	esc_html_e( 'There was an error creating a backup archive file', 'boldgrid-backup' );
}
?>.</p>
<?php
if ( ! empty( $archive_info['filepath'] ) ) {
?>
	<p><?php
	printf(
		esc_html__( 'File Path: %s', 'boldgrid-backup' ),
		$archive_info['filepath']
	);
?></p>
<?php
}
?>
<p><?php echo $archive_info['error']; ?></p>
<?php
if ( isset( $archive_info['error_message'] ) ) {
?><p><?php
	printf(
		esc_html__( 'Error Details: %s', 'boldgrid-backup' ),
		$archive_info['error_message']
	);

	if ( isset( $archive_info['error_code'] ) ) {
		echo ' (' . $archive_info['error_code'] . ')';
	}

?></p>
<?php
}
?>
</div>
<?php
	}
}

?>
<div id='end-backup'></div>
