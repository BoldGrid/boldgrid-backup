<?php

printf( '<h2>%1$s</h2>', __( 'Remote Storage', 'boldgrid-backup' ) );

$remote_storage_li = array();
$remote_storage_li = apply_filters( 'boldgrid_backup_single_archive_remote_options', $remote_storage_li, $archive['filepath'] );

if( empty( $remote_storage_li ) ) {
	echo __( 'No remote storage options available.', 'boldgrid-backup' );
	return;
}

echo '<table class="wp-list-table widefat fixed striped remote-storage">';

foreach( $remote_storage_li as $provider ) {

	if( $provider['uploaded'] ) {
		$upload = '&#10003; ' . __( 'Uploaded', 'boldgrid-backup' );
	} elseif( $provider['allow_upload'] ) {
		$upload = sprintf( '<a class="button button-primary upload">%1$s</a>', __( 'Upload', 'boldgrid-backup' ) );
	} else {
		$upload = sprintf( __( 'Please go to your <a target="_parent" href="%1$s">%2$s</a> to configure %3$s.', 'boldgrid-backup' ), 'admin.php?page=boldgrid-backup-settings', __( 'settings page', 'boldgrid-backup' ), $provider['title'] );
	}

	printf( '
		<tr data-remote-provider="%3$s">
			<td>
				<strong>%1$s</strong>
			</td>
			<td>
				%2$s
			</td>
		</tr>
		',
		$provider['title'],
		$upload,
		$provider['id']
	);
}

echo '</table>';

?>