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

defined( 'WPINC' ) ? : die;

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

<?php
	include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

	echo $table;
?>

<?php if( ! empty( $archives ) ) { ?>
<p>
<?php esc_html_e( 'These backups are stored on your server. You should occasionally download them to your local computer.', 'boldgrid-backup' ); ?>
</p>
<?php } ?>

<hr />

<?php
	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/premium-message.php';

	echo( include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-size-data.php' );

	$in_modal = true;
	$modal = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
	$in_modal = false;
	echo $modal;

	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/note-pre-backup.php';
?>

</div>
