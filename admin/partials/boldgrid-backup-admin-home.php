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
 * @param string $table             Markup for a table of current backups. If no
 *                                  backups, it's a p tag with appropriate message.
 */

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

?>
<div class='wrap'>

<h1 class="wp-heading-inline"><?php esc_html_e( 'Backup Archives', 'boldgrid-backup' ); ?></h1>

<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary"><?php echo __( 'Backup Site Now', 'boldgrid-backup' ); ?></a>

<a class="page-title-action add-new"><?php echo __( 'Upload Backup', 'boldgrid-backup' ); ?></a>

<?php
	echo $nav;

	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

	echo $table;

	$in_modal = true;
	$modal = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
	$in_modal = false;
	echo $modal;

	include BOLDGRID_BACKUP_PATH . '/admin/partials/archives/note-pre-backup.php';
?>

</div>
