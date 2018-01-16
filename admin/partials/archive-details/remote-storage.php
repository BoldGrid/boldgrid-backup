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

$data['postbox'] = '';

$action = 'boldgrid_backup_single_archive_remote_options';
if( ! empty( $archive['filepath'] ) ) {
	do_action( $action, $archive['filepath'] );
} elseif( ! empty( $archive['filename'] ) ) {
	do_action( $action, $archive['filename'] );
}

if( empty( $this->remote_storage_li ) ) {
	$data['postbox'] = __( 'No remote storage options available.', 'boldgrid-backup' );
	return $data;
}

$count = 0;
foreach( $this->remote_storage_li as $provider ) {
	$count++;

	// Generate a link to "download to server" from remote provider.
	$download = '';
	if( ! $archive_found && $provider['uploaded'] ) {
		$download = sprintf( '
			<a class="button download-to-server" data-provider-id="%3$s">%1$s</a>
			%2$s
			',
			__( 'Download to server', 'boldgrid-backup' ),
			$this->core->lang['spinner'],
			$provider['id']
		);
	}

	if( $provider['uploaded'] ) {
		$upload = '&#10003; ' . __( 'Uploaded', 'boldgrid-backup' );
	} elseif( $provider['allow_upload'] && $archive_found ) {
		$upload = sprintf(
			'<a class="button button-primary upload" data-provider-id="%2$s">%1$s</a>',
			__( 'Upload', 'boldgrid-backup' ),
			$provider['id']
		);
	} elseif( isset( $provider['is_setup'] ) and false === $provider['is_setup'] ) {
		$upload = sprintf( __( 'Please go to your <a target="_parent" href="%1$s">%2$s</a> to configure %3$s.', 'boldgrid-backup' ), 'admin.php?page=boldgrid-backup-settings', __( 'settings page', 'boldgrid-backup' ), $provider['title'] );
	} else {
		$upload = '';
	}

	$data['postbox'] .= sprintf( '
		%5$s
		<div data-remote-provider="%3$s">
			<span style="float:left;"><strong>%1$s</strong></span>
			<span style="float:right;max-width:50%%;">%2$s</span>

			<div style="clear:both;"></div>

			<p>%4$s</p>
		</div>
		',
		/* 1 */ $provider['title'],
		/* 2 */ $upload,
		/* 3 */ $provider['id'],
		/* 4 */ $download,
		/* 5 */ 1 !== $count ? '<hr class="separator-small" />' : ''
	);
}

return $data;

?>