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
?>
	<tr>
		<td class='backup-archive-list-path'><?php echo $archive['filename']; ?></td>
		<td class='backup-archive-list-size'><?php echo Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive['filesize'] ); ?></td>
		<td class='backup-archive-list-date'><?php echo $archive['filedate']; ?></td>
		<td class='backup-archive-list-download'>
			<a id='backup-archive-download-<?php echo $key; ?>'
			class='button action-download' href='#'
			data-key='<?php echo $key ?>' data-filepath='<?php echo $archive['filepath']; ?>'
			data-filename='<?php echo $archive['filename']; ?>'>
			<?php esc_html_e( 'Download', 'boldgrid-backup' ); ?></a>
		</td>
		<td class='backup-archive-list-restore' nowrap>
			<form action='<?php echo get_admin_url( null, 'admin.php?page=boldgrid-backup' ); ?>'
			class='restore-now-form' method='POST'>
				<input type='hidden' name='restore_now' value='1' />
				<input type='hidden' name='archive_key' value='<?php echo $key; ?>' />
				<input type='hidden' name='archive_filename' value='<?php echo $archive['filename']; ?>' />
				<?php wp_nonce_field(	'archive_auth', 'archive_auth' ); ?>
				<input type='submit' class='button action-restore' data-key='<?php echo $key ?>'
				data-filename='<?php echo $archive['filename']; ?>'
				value='<?php esc_html_e( 'Restore', 'boldgrid-backup' ); ?>' />
				<span class='spinner'></span>
			</form>
		</td>
		<td class='backup-archive-list-delete' nowrap>
			<form action='<?php echo get_admin_url( null, 'admin.php?page=boldgrid-backup' ); ?>'
			class='delete-now-form' method='POST'>
				<input type='hidden' name='delete_now' value='1' />
				<input type='hidden' name='archive_key' value='<?php echo $key; ?>' />
				<input type='hidden' name='archive_filename' value='<?php echo $archive['filename']; ?>' />
				<?php wp_nonce_field(	'archive_auth', 'archive_auth' ); ?>
				<input type='submit' class='button action-delete' data-key='<?php echo $key ?>'
				data-filename='<?php echo $archive['filename']; ?>'
				value='<?php esc_html_e( 'Delete', 'boldgrid-backup' ); ?>' />
				<span class='spinner'></span>
			</form>
		</td>
	</tr>
