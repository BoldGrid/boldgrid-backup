<?php
/**
 * File: remote-storage.php
 *
 * Render remote provider's table on archive details page.
 * This file is included by:
 *  admin/partials/boldgrid-backup-admin-archive-details.php
 *  admin/class-boldgrid-backup-admin-archive-details.php
 *
 * @link https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @param bool $archive_found Whether or not the archive was found.
 */

defined( 'WPINC' ) || die;

$data['postbox'] = '';

$action = 'boldgrid_backup_single_archive_remote_options';
if ( ! empty( $archive['filepath'] ) ) {
	do_action( $action, $archive['filepath'] );
} elseif ( ! empty( $archive['filename'] ) ) {
	do_action( $action, $archive['filename'] );
}

if ( empty( $this->remote_storage_li ) ) {
	$data['postbox'] = __( 'No remote storage options available.', 'boldgrid-backup' );
	return $data;
}

// This is the template used to render each remote storage provider.
$entry_template = '%5$s
	<div data-remote-provider="%3$s">
		<span style="float:left; width:calc(50%% - 10px);" %6$s><strong>%1$s</strong></span>
		<span style="float:right; width:50%%;">%2$s</span>

		<div style="clear:both;"></div>

		<p>%4$s</p>
	</div>';

$count = 0;
foreach ( $this->remote_storage_li as $provider ) {
	$count++;

	// Generate a link to "download to server" from remote provider.
	$download = '';
	if ( ! $archive_found && $provider['uploaded'] ) {
		$download = sprintf(
			'
			<a class="button download-to-server" data-provider-id="%3$s">%1$s</a>
			%2$s
			',
			__( 'Download to web server', 'boldgrid-backup' ),
			$this->core->lang['spinner'],
			$provider['id']
		);
	}

	if ( $provider['uploaded'] ) {
		$upload = '&#10003; ' . __( 'Uploaded', 'boldgrid-backup' );
	} elseif ( $provider['allow_upload'] && $archive_found ) {
		$upload = sprintf(
			'<a class="button button-primary upload" data-provider-id="%2$s">%1$s</a>',
			__( 'Upload', 'boldgrid-backup' ),
			$provider['id']
		);
	} elseif ( isset( $provider['is_setup'] ) && false === $provider['is_setup'] ) {
		$upload = sprintf(
			// translators: 1: HTML anchor open tag, 2: HTML anchor close tag, 3: Provider title.
			esc_html__(
				'Please go to your %1$ssettings page%2$s to configure %3$s.',
				'boldgrid-backup'
			),
			'<a target="_parent" href="admin.php?page=boldgrid-backup-settings&section=section_storage">',
			'</a>',
			$provider['title']
		);
	} else {
		$upload = '';
	}

	$data['postbox'] .= sprintf(
		$entry_template,
		/* 1 */ esc_html( $provider['title'] ),
		/* 2 */ $upload,
		/* 3 */ $provider['id'],
		/* 4 */ $download,
		/* 5 */ 1 !== $count ? '<hr class="separator-small" />' : '',
		/* 6 */ empty( $provider['title_attr'] ) ? '' : sprintf( 'title="%1$s"', esc_attr( $provider['title_attr'] ) )
	);
}

// If the user is not on pro, show the remote storage providers available in pro.
if ( ! $this->core->config->get_is_premium() ) {
	foreach ( $this->core->configs['premium_remote'] as $provider ) {
		$data['postbox'] .= sprintf(
			$entry_template,
			/* 1 */ esc_html( $provider['title'] ),
			/* 2 */ esc_html__( 'Premium Feature', 'boldgrid-backup' ),
			/* 3 */ '',
			/* 4 */ '',
			/* 5 */ '<hr class="separator-small" />',
			/* 6 */ ''
		);
	}
}

return $data;
