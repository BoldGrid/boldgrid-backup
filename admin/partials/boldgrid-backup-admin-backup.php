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

$is_restore = ! empty( $_POST['restore_now'] ) && '1' === $_POST['restore_now'];
$is_success = ! empty( $archive_info ) && empty( $archive_info['error'] );

/*
 * Avoid backwards compatibility issues when restoring.
 *
 * For example, let's say you're running "BoldGrid Backup 1.5" and you're
 * trying to restore a "BoldGrid Backup 1.6" archive. The restoration request
 * is being handled by 1.5, who's $core is a bit different than the 1.6 $core.
 * Elements in this file are calling upon $core, but we don't know if $core
 * exists or what characteristics it has.
 *
 * This problem occurs because both 1.5 and 1.6 will include this file after
 * a restoration completes. The version of this file will be loaded from the
 * archive that was just restored.
 *
 * @since 1.6.0
 */
if( $is_restore && $is_success ) {

	/*
	 * After restoration, redirect user to the backups page.
	 *
	 * In Backup 1.6, we introduced the "Archive Details" page. The user may
	 * very well be restoring from this page. However, if we restored a backup
	 * from an earlier version, that "Archive Details" page may not exists, and
	 * the user will get an error.
	 */
	$redirect_url = admin_url( 'admin.php?page=boldgrid-backup' );
	if( headers_sent() ) {
		printf( '<script type="text/javascript">window.location.href = "%1$s";</script>', $redirect_url );
	} else {
		wp_redirect( $redirect_url );
		exit;
	}

	return array(
		'message' => esc_html__( 'The selected archive file has been successfully restored.', 'boldgrid-backup' ),
		'class' => 'notice notice-success is-dismissible',
		'header' => __( 'BoldGrid Backup - Restoration complete' ),
	);
}

$core = isset( $this->core ) ? $this->core : $this;

$core->archive->init( $archive_info['filepath'] );

/**
 * If data exists in the $archive_info array, then print results, else show an error message.
 *
 * @param array $archive_info {
 *        @type string compressor The code-name for the compressor used to create the archive.
 *        @type string $filepath The absolute file path.
 *        @type int $filesize The archive file size.
 *        @type int $total_size The total size of the uncompressed files.
 *        @type string $error A friendly error message.
 *        @type int $error_code An integer from a compressor constant.
 *        @type string $error_message A human-readable interpretation or the error code.
 *        }
 */

// Create a link to the settings page.
$url = admin_url( 'admin.php?page=boldgrid-backup-settings' );
$settings_page_link = sprintf(
	wp_kses(
		__( 'See <a href="%s">Settings for BoldGrid Backup</a> for details.', 'boldgrid-backup' ),
		array(  'a' => array( 'href' => array() ) )
	),
	esc_url( $url )
);

if ( ! empty( $archive_info ) ) {

	if ( ! empty( $archive_info['dryrun'] ) ) {
		$message = array(
			'class' => 'notice notice-info is-dismissible',
			'message' => sprintf( '<p>%1$s</p>', esc_html__( 'This was a dry run test', 'boldgrid-backup' ) ),
		);
	}

	/*
	 * Get our success message.
	 *
	 * Initially, we had one sprintf intelligent enough to determine if we were
	 * making a backup or restoring, and make us a nice message. However, having
	 * such a fantastic beast in one sprintf made it a bit difficult to manage.
	 * We've since split up the sprintf's into two, one for when a backup has
	 * completed empty( $_POST['restore_now'] ), and one when a restoration has
	 * been completed.
	 *
	 * @todo Read the above. For the time, we did a quick clean up. However, we
	 *       may want to further organize this entire file.
	 */
	if ( $is_success ) {

		if( ! $is_restore ) {
			$message = array(
				'class' => 'notice notice-success is-dismissible boldgrid-backup-complete',
				'message' => sprintf( '
						<h2 class="header-notice">%1$s - %2$s</h2>
						<p>%3$s <a href="%4$s">%5$s</a></p>
					',
					/* 1 */ __( 'BoldGrid Backup', 'boldgrid-backup' ),
					/* 2 */ __( 'Backup complete', 'boldgrid-backup' ),
					/* 3 */ esc_html__( 'A backup archive file has been created successfully!', 'boldgrid-backup' ),
					/* 4 */ $core->archive->view_details_url,
					/* 5 */ __( 'View details', 'boldgrid-backup' )
				),
			);
		} else {
			$message = array(
				'class' => 'notice notice-success is-dismissible',
				'message' => sprintf( '
					<h2 class="header-notice">%9$s - %1$s</h2>
					<p>%2$s</p>
					%3$s
					%4$s
					%5$s
					%6$s
					%7$s
					<p>%8$s</p>',
					/* 1 */ __( 'Restoration complete', 'boldgrid-backup' ),
					/* 2 */ esc_html__( 'The selected archive file has been successfully restored', 'boldgrid-backup' ),
					/* 3 */ ! empty( $archive_info['filepath'] ) ? '<p>' . sprintf( esc_html__( 'File Path: %s', 'boldgrid-backup' ), $archive_info['filepath'] ) . '</p>' : '',
					/* 4 */ ! empty( $archive_info['filesize'] ) ? '<p>' . sprintf( esc_html__( 'File Size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ) ) . '</p>' : '',
					/* 5 */ ! empty( $archive_info['total_size'] ) ? '<p>' . sprintf( esc_html__( 'Total size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) ) . '</p>' : '',
					/* 6 */ isset( $archive_info['db_duration'] ) ? '<p>' . sprintf( $core->configs['lang']['est_pause'], $archive_info['db_duration'] ) . '</p>' : '',
					/* 7 */ isset( $archive_info['duration'] ) ? '<p>' . sprintf( esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ), $archive_info['duration'] ) . '</p>' : '',
					/* 8 */ $settings_page_link,
					/* 9 */ __( 'BoldGrid Backup', 'boldgrid-backup' )
				),
			);
		}
	} else {
		$message = array(
			'class' => 'notice notice-error is-dismissible',
			'message' => esc_html( $archive_info['error'] ),
			'header' => sprintf(
				'%1$s - %2$s',
				__( 'BoldGrid Backup', 'boldgrid-backup' ),
				empty( $_POST['restore_now'] ) ? __( 'Error creating archive', 'boldgrid-backup' ) : __( 'Error restoring archive', 'boldgrid-backup' )
			),
		);
	}
} else {
	$message = array(
		'class' => 'notice notice-error is-dismissible',
		'message' => sprintf('
			<p>%1$s</p>
			%2$s
			%3$s
			%4$s %5$s',
			/* 1 */ ! empty( $_POST['restore_now'] ) ? esc_html__( 'There was an error restoring the selected backup archive file', 'boldgrid-backup' ) : esc_html__( 'There was an error creating a backup archive file', 'boldgrid-backup' ),
			/* 2 */ empty( $archive_info['filepath'] )        ? '' : '<p>' . sprintf( esc_html__( 'File Path: %s', 'boldgrid-backup' ), $archive_info['filepath'] ) . '</p>',
			/* 3 */ empty( $archive_info['error'] )           ? '' : '<p>' . $archive_info['error'] . '</p>',
			/* 4 */ ! isset( $archive_info['error_message'] ) ? '' : '<p>' . sprintf( __( 'Error Details: %s', 'boldgrid-backup' ), $archive_info['error_message'] ),
			/* 5 */ isset( $archive_info['error_message'] ) && isset( $archive_info['error_code'] ) ? ' (' . $archive_info['error_code'] . ')' : ''
		),
	);
}

if( ! isset( $message ) ) {
	$message = array(
		'class' => 'notice notice-error is-dismissible',
		'message' => __( 'Unknown error.', 'boldgrid-backup' ),
		'header' => __( 'BoldGrid Backup', 'boldgrid-backup' ),
	);
}

$message['header'] = isset( $message['header'] ) ? $message['header'] : null;

return $message;
