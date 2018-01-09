<?php
/**
 * Render remote provider's table on archive details page.
 *
 * This file is included by admin/partials/boldgrid-backup-admin-archive-details.php
 * whis is included by      admin/class-boldgrid-backup-admin-archive-details.php
 *
 * @param bool $archive_found Whether or not the archive was found.
 */

defined( 'WPINC' ) ? : die;

$remote_storage = sprintf( '<h2>%1$s</h2>', __( 'Remote Storage', 'boldgrid-backup' ) );

$action = 'boldgrid_backup_single_archive_remote_options';
if( ! empty( $archive['filepath'] ) ) {
	do_action( $action, $archive['filepath'] );
} elseif( ! empty( $archive['filename'] ) ) {
	do_action( $action, $archive['filename'] );
}

if( empty( $this->remote_storage_li ) ) {
	$remote_storage .= __( 'No remote storage options available.', 'boldgrid-backup' );
	return;
}

$remote_storage .= '<table class="wp-list-table widefat fixed striped remote-storage">';

foreach( $this->remote_storage_li as $provider ) {

	// Generate a link to "download to server" from remote provider.
	$download = '';
	if( ! $archive_found && $provider['uploaded'] ) {
		$download = sprintf( '
			<a class="button download-to-server">%1$s</a>
			%2$s
			',
			__( 'Download to server', 'boldgrid-backup' ),
			$this->core->lang['spinner']
		);
	}


	if( $provider['uploaded'] ) {
		$upload = '&#10003; ' . __( 'Uploaded', 'boldgrid-backup' );
	} elseif( $provider['allow_upload'] && $archive_found ) {
		$upload = sprintf( '<a class="button button-primary upload">%1$s</a>', __( 'Upload', 'boldgrid-backup' ) );
	} elseif( isset( $provider['is_setup'] ) and false === $provider['is_setup'] ) {
		$upload = sprintf( __( 'Please go to your <a target="_parent" href="%1$s">%2$s</a> to configure %3$s.', 'boldgrid-backup' ), 'admin.php?page=boldgrid-backup-settings', __( 'settings page', 'boldgrid-backup' ), $provider['title'] );
	} else {
		$upload = '';
	}

	$remote_storage .= sprintf( '
		<tr data-remote-provider="%3$s">
			<td>
				<strong>%1$s</strong>
			</td>
			<td>
				%2$s
			</td>
			<td>
				%4$s
			</td>
		</tr>
		',
		/* 1 */ $provider['title'],
		/* 2 */ $upload,
		/* 3 */ $provider['id'],
		/* 4 */ $download
	);
}

$remote_storage .= '</table>';

return $remote_storage;

?>