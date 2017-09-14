<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 */

$md5_id = md5( $archive['filepath'] );

$delete_form = sprintf(
	'<form action="%1$s" method="POST" class="delete-now-form">
		<input type="hidden" name="delete_now" value="1" />
		<input type="hidden" name="archive_key" value="%2$s" />
		<input type="hidden" name="archive_filename" value="%3$s" />
		%4$s
		<span class="trash">
			<a class="action-delete" data-key="%2$s" data-filename="%3$s" >%5$s</a>
		</span>
		<span class="spinner"></span>
	</form>',
	get_admin_url( null, 'admin.php?page=boldgrid-backup' ),
	$key,
	$archive['filename'],
	wp_nonce_field( 'archive_auth', 'archive_auth', true, false ),
	__( 'Delete', 'boldgrid-backup' )
);

$download_button = sprintf(
	'<a	id="backup-archive-download-%1$s"
		class="button button-primary action-download"
		href="#"
		data-key="%1$s"
		data-filepath="%2$s"
		data-filename="%3$s" >
		%4$s
	</a>',
	$key,
	$archive['filepath'],
	$archive['filename'],
	__( 'Download', 'boldgrid-backup' )
);

$restore_form = sprintf(
	'<form action="%1$s" class="restore-now-form method="POST">
		<input type="hidden" name="restore_now" value="1" />
		<input type="hidden" name="archive_key" value="%2$s" />
		<input type="hidden" name="archive_filename" value="%3$s" />
		%4$s
		<input type="submit" class="button action-restore" value="%5$s" disabled="disabled" />
		<span class="spinner"></span>
	</form>',
	get_admin_url( null, 'admin.php?page=boldgrid-backup' ),
	$key,
	$archive['filename'],
	wp_nonce_field( 'archive_auth', 'archive_auth', true, false ),
	__( 'Restore', 'boldgrid-backup' )
);

?>

<tr>
	<td class='backup-archive-list-date'>
		<strong><?php echo __( 'Backup', 'boldgrid-backup' );?></strong>: <?php echo $archive['filedate']; ?>
		<div class="row-actions">
			<?php printf( '<a href="admin.php?page=boldgrid-backup-archive-details&md5=%1$s&TB_iframe=true&width=772&height=550" class="thickbox">%2$s</a>', $md5_id, __( 'View details', 'boldgrid-backup' ) ); ?> |
			<?php echo $delete_form; ?>
		</div>
	</td>
	<td class='auto-width'>
		<?php echo $download_button; ?>
	</td>
	<td class='auto-width'>
		<?php echo $restore_form; ?>
	</td>
</tr>
