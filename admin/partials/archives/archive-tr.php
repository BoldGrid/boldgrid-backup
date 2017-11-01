<?php
/**
 * Create a <tr> for a single local backup.
 *
 * When the Backups page is generated, we loop through each local archive and
 * include this file, which returns the <tr>.
 *
 * @since 1.5.4
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

$restore_form = sprintf('
	<a
		data-restore-now="1"
		data-archive-key="%2$s"
		data-archive-filename="%3$s"
		data-nonce="%4$s"
		class="button restore-now"
		href="">
		%5$s
	</a>
	',
	/* 1 */ get_admin_url( null, 'admin.php?page=boldgrid-backup' ),
	/* 2 */ $key,
	/* 3 */ $archive['filename'],
	/* 4 */ wp_create_nonce( 'boldgrid_backup_restore_archive'),
	/* 5 */ __( 'Restore', 'boldgrid-backup' )
);

$locations = array( __( 'Local backup', 'boldgrid-backup' ) );
/**
 * Allow other plugins to modify text showing where this backup is located.
 *
 * @since 1.5.4
 *
 * @param string $filename
 * @param array  $locations
 */
$locations = apply_filters( 'boldgrid_backup_backup_locations', $archive['filename'], $locations );

return sprintf( '
		<tr data-timestamp="%1$s">
			<td class="auto-width">
				%2$s
			</td>
			<td class="backup-archive-list-date">
				<strong>%3$s</strong>: %4$s
				<div class="row-actions">
					%5$s | %6$s
				</div>
			</td>
			<td class="auto-width">
				%7$s
			</td>
			<td class="auto-width">
				%8$s
			</td>
		</tr>',
		/* 1 */ $archive['lastmodunix'],
		/* 2 */ implode( '<br />', $locations ),
		/* 3 */ __( 'Backup', 'boldgrid-backup' ),
		/* 4 */ $archive['filedate'],
		/* 5 */ sprintf( '<a href="admin.php?page=boldgrid-backup-archive-details&md5=%1$s">%2$s</a>', $md5_id, __( 'View details', 'boldgrid-backup' ) ),
		/* 6 */ $delete_form,
		/* 7 */ $download_button,
		/* 8 */ $restore_form
	);

?>
