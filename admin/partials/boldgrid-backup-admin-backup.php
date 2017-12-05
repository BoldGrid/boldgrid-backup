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
			'class' => 'notice notice-info',
			'message' => sprintf( '<p>%1$s</p>', esc_html__( 'This was a dry run test', 'boldgrid-backup' ) ),
		);
	}

	if ( empty( $archive_info['error'] ) ) {
		$message = array(
			'class' => 'notice notice-success',
			'message' => sprintf( '
				<h2 class="header-notice">%9$s - %1$s</h2>
				<p>%2$s</p>
				%3$s
				%4$s
				%5$s
				%6$s
				%7$s
				<p>%8$s</p>',
				/* 1 */ ! empty( $_POST['restore_now'] ) ? __( 'Restoration complete', 'boldgrid-backup' ) : __( 'Backup complete', 'boldgrid-backup' ),
				/* 2 */ ! empty( $_POST['restore_now'] ) ? esc_html__( 'The selected archive file has been successfully restored', 'boldgrid-backup' ) : esc_html__( 'A backup archive file has been created successfully', 'boldgrid-backup' ),
				/* 3 */ ! empty( $archive_info['filepath'] ) ? '<p>' . sprintf( esc_html__( 'File Path: %s', 'boldgrid-backup' ), $archive_info['filepath'] ) . '</p>' : '',
				/* 4 */ ! empty( $archive_info['filesize'] ) ? '<p>' . sprintf( esc_html__( 'File Size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ) ) . '</p>' : '',
				/* 5 */ ! empty( $archive_info['total_size'] ) ? '<p>' . sprintf( esc_html__( 'Total size: %s', 'boldgrid-backup' ), Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) ) . '</p>' : '',
				/* 6 */ isset( $archive_info['db_duration'] ) ? '<p>' . sprintf( $this->configs['lang']['est_pause'], $archive_info['db_duration'] ) . '</p>' : '',
				/* 7 */ isset( $archive_info['duration'] ) ? '<p>' . sprintf( esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ), $archive_info['duration'] ) . '</p>' : '',
				/* 8 */ $settings_page_link,
				/* 9 */ __( 'BoldGrid Backup', 'boldgrid-backup' )
			),
		);
	}
} else {
	$message = array(
		'class' => 'notice notice-error',
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

return $message;
