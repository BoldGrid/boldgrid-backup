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

/*
 * Variables passed by scope.
 *
 * @param int $archives_count The archive file count.
 * @param int $archives_size The total size of all archive files.
 * @param array $archives {
 * 	A numbered array of arrays containing the following indexes.
 * 	@type string $filepath Archive file path.
 * 	@type string $filename Archive filename.
 * 	@type string $filedate Localized file modification date.
 * 	@type int $filesize The archive file size in bytes.
 * 	@type int $lastmodunix The archive file modification time in unix seconds.
 * }
 * @param string $backup_identifier The backup identifier for this installation.
 */

?>
<div class='wrap'>
<h1>BoldGrid Backup</h1>

<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/premium-message.php'; ?>

<div id='size-data'>
		<?php
		wp_nonce_field( 'boldgrid_backup_sizes', 'sizes_auth' );
		printf( '<p><span class="spinner" style="float:none; visibility:visible; margin-top: -10px; margin-left:0px;"></span>%s</p>',
			esc_html__( 'Calculating disk space...' )
		);
		?>
</div>

<hr />
<h2><?php esc_html_e( 'Backup Archive Summary', 'boldgrid-backup' ); ?></h2>
<table id='backup-archive-summary-table'>
	<tbody id='backup-archive-list-header'>
		<tr>
			<td class='backup-archive-summary-metric'>
				<?php esc_html_e( 'Archive Count', 'boldgrid-backup' ); ?>:
			</td>
			<td class='backup-archive-summary-value' id='archives-count'>
				<?php echo $archives_count; ?>
			</td>
		</tr>
		<tr>
			<td class='backup-archive-summary-metric'>
				<?php esc_html_e( 'Total Size', 'boldgrid-backup' ); ?>:
			</td>
			<td class='backup-archive-summary-value' id='archives-size'>
				<?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archives_size );?>
			</td>
		</tr>
	</tbody>
</table>
<h2><?php esc_html_e( 'Backup Archives', 'boldgrid-backup' ); ?></h2>
<table id='backup-archive-list-table'>
	<thead id='backup-archive-list-header'>
		<tr>
			<th class='backup-archive-list-path'>
				<?php esc_html_e( 'Filename', 'boldgrid-backup' ); ?>
			</th>
			<th class='backup-archive-list-size'>
				<?php esc_html_e( 'Size', 'boldgrid-backup' ); ?>
			</th>
			<th class='backup-archive-list-date'>
				<?php esc_html_e( 'Date', 'boldgrid-backup' ); ?>
			</th>
			<th class='backup-archive-list-download'></th>
			<th class='backup-archive-list-restore'></th>
		</tr>
		<tr>
			<th colspan="6"><hr /></th>
		</tr>
	</thead>
	<tbody id='backup-archive-list-body'>
<?php

// Print the list of archive files.
if ( ! empty( $archives ) ) {
	foreach ( $archives as $key => $archive ) {
		include dirname( __FILE__ ) . '/boldgrid-backup-admin-archives.php';
	}
} else {
?>
	<tr>
		<td colspan='3'>
			<?php
			esc_html_e(
				'There are no archives for this site in the backup directory.',
				'boldgrid-backup'
			);
			?>
		</td>
	</tr>
<?php
}
?>
	</tbody>
</table>
<div id='backup-site-now-section'>
	<form action='#' id='backup-site-now-form' method='POST'>
		<?php wp_nonce_field( 'boldgrid_backup_now', 'backup_auth' ); ?>
		<p>
			<a id='backup-site-now' class='button button-primary'<?php

			// If a restoration was just performed, then disable the backup button.
			if ( ! empty( $_POST['restore_now'] ) ) {
		?> disabled='disabled' style='pointer-events: none;'<?php
			}

?>><?php esc_html_e( 'Backup Site Now', 'boldgrid-backup' ); ?></a>
			<span class='spinner'></span>
		</p>
	</form>
</div>
<div id='backup-site-now-results'></div>
<h2>
	<?php esc_html_e( 'Upload a Backup Archive', 'boldgrid-backup' ); ?>
	<span class="dashicons dashicons-editor-help" data-id="upload-backup"></span>
</h2>
<p class="help" data-id="upload-backup">
	<?php
	esc_html_e(
		'You can upload a backup file that was created with BoldGrid Backup.
		If you choose to restore an uploaded file from a different web location (URL), then we will try to ensure that references to URL address are updated.
		There may be times when some items may need to be updated manually.',
		'boldgrid-backup'
	);
	?>
</p>
<div id="upload-archive-section">
	<form id="upload-archive-form" method="POST"
	enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
		<input type="hidden" name="uploading" value="1" />
		<?php wp_nonce_field( 'upload_archive_file' ); ?>
		<input name="file" type="file" />
		<p>
			<?php
			// Print the file upload limit.
			echo esc_html__( 'File size limit', 'boldgrid-backup' ) . ': ' .
			Boldgrid_Backup_Admin_Utility::bytes_to_human(
				Boldgrid_Backup_Admin_Utility::get_upload_limit()
			);
			?>
			<span class="dashicons dashicons-editor-help" data-id="size-limit"></span>
		</p>
		<p class="help" data-id="size-limit">
			<?php
			esc_html_e(
				'To change the limit, you may be able to modify your server\'s php.ini or .htaccess file.  Please ask your web hosting provider if you need assistance.',
				'boldgrid-backup'
			);
			?>
		</p>
		<p>
			<input class="button" type="submit" value="Upload" />
			<span class='spinner'></span>
		</p>
	</form>
</div>
<h2>
	<?php esc_html_e( 'Backup Id', 'boldgrid-backup' ); ?>
	<span class="dashicons dashicons-editor-help" data-id="backup-id"></span>
</h2>
<p class="help" data-id="backup-id">
	<?php
	printf(
		esc_html__(
			'The backup id is used to determine if a backup archive file is associated with this WordPress installation.
			You can upload an archive file that was created with BoldGrid Backup using this page or a manual method such as FTP.%1$s
			Manually uploaded archive files must have filenames starting with "%2$s", contain the BoldGrid Backup id "%3$s", and end with "%4$s", to be recognized.%1$s
			For example: %5$s',
			'boldgrid-backup'
		),
		'<br />',
		'boldgrid-backup-',
		$backup_identifier,
		'.zip',
		'boldgrid-backup-' . $backup_identifier . '-my_backup_file.zip'
	);
	?>
</p>
<?php
// Print the backup identifier.
echo esc_html__( 'BoldGrid Backup id', 'boldgrid-backup' ) . ': ' . $backup_identifier;
?>
</div>
