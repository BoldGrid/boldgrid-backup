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


<h1 class="wp-heading-inline"><?php esc_html_e( 'Backup Archives', 'boldgrid-backup' ); ?></h1>
<a class="page-title-action add-new"><?php echo __( 'Upload Backup', 'boldgrid-backup' ); ?></a>

<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php'; ?>

<?php include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php'; ?>

<p>
	<?php esc_html_e( 'Archive Count', 'boldgrid-backup' ); ?> (<?php echo $archives_count; ?>) |
	<?php esc_html_e( 'Total Size', 'boldgrid-backup' ); ?> (<?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archives_size );?>)
</p>

<table class='wp-list-table widefat fixed striped pages'>
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
		<td>
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

<?php if( ! empty( $archives ) ) { ?>
<p>
<?php esc_html_e( 'These backups are stored on your server. You should occasionally download them to your local computer.', 'boldgrid-backup' ); ?>
</p>
<?php } ?>

<hr />

<?php
	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/premium-message.php';

	echo( include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php' );
	echo( include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-button.php' );

	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/note-pre-backup.php';
?>

</div>
