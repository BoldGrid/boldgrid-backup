<?php
/**
 * File: boldgrid-backup-admin-backup.php
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

defined( 'WPINC' ) || die;

$is_restore   = ! empty( $_POST['restore_now'] ) && '1' === $_POST['restore_now']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
$is_success   = ! empty( $archive_info ) && empty( $archive_info['error'] );
$redirect_url = admin_url( 'admin.php?page=boldgrid-backup' );

/*
 * Avoid backwards compatibility issues when restoring.
 *
 * For example, let's say you're running version 1.5 and you're
 * trying to restore a version 1.6 archive. The restoration request
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
if ( $is_restore && $is_success ) {
	/*
	 * After restoration, redirect user to the backups page.
	 *
	 * In Backup 1.6, we introduced the "Archive Details" page. The user may
	 * very well be restoring from this page. However, if we restored a backup
	 * from an earlier version, that "Archive Details" page may not exists, and
	 * the user will get an error.
	 *
	 * Prior to Backup 1.6, restorations were made by forms submitted via post.
	 * In Backup 1.6, restorations are made via ajax.
	 *
	 * If we're not doing ajax, then the request came from Backup 1.5, and the
	 * site is being restored within the new pageload. When the page finally loads,
	 * you'll have a newer version of Backup, but you'll have to refresh the page
	 * to see it. We'll take care of the refresh for the user.
	 */
	if ( ! wp_doing_ajax() ) {
		?>
		<script type="text/javascript">window.location.href = "<?php echo esc_url( $redirect_url ); ?>";</script>
		<?php
		return;
	}
}

$archive_filepath = ! empty( $archive_info['filepath'] ) ? $archive_info['filepath'] : null;

$core = isset( $this->core ) ? $this->core : $this;

$core->archive->init( $archive_filepath );

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
$url                = admin_url( 'admin.php?page=boldgrid-backup-settings' );
$settings_page_link = sprintf(
	wp_kses(
		// translators: 1: URL address, 2: Plugin title.
		__( 'See <a href="%1$s">Settings for %2$s</a> for details.', 'boldgrid-backup' ),
		[ 'a' => [ 'href' => [] ] ]
	),
	esc_url( $url ),
	BOLDGRID_BACKUP_TITLE
);

if ( ! empty( $archive_info ) ) {

	if ( ! empty( $archive_info['dryrun'] ) ) {
		$message = [
			'class'   => 'notice notice-info is-dismissible',
			'message' => sprintf( '<p>%1$s</p>', esc_html__( 'This was a dry run test', 'boldgrid-backup' ) ),
		];
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
		$message = [
			'class'   => 'notice notice-success is-dismissible boldgrid-backup-complete',
			'message' => sprintf(
				'
					<p>%1$s <a href="%2$s">%3$s</a></p>
				',
				/* 1 */ esc_html__( 'A backup archive file has been created successfully!', 'boldgrid-backup' ),
				/* 2 */ $core->archive->view_details_url,
				/* 3 */ esc_html__( 'View details', 'boldgrid-backup' )
			),
			'header'  => BOLDGRID_BACKUP_TITLE . ' - ' . esc_html__( 'Backup complete', 'boldgrid-backup' ),
		];
	} else {
		$message = [
			'class'   => 'notice notice-error is-dismissible',
			'message' => esc_html( $archive_info['error'] ),
			'header'  => sprintf(
				'%1$s - %2$s',
				BOLDGRID_BACKUP_TITLE,
				empty( $_POST['restore_now'] ) ? // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
				esc_html__( 'Error creating archive', 'boldgrid-backup' ) :
				esc_html__( 'Error restoring archive', 'boldgrid-backup' )
			),
		];
	}
} else {
	$message = [
		'class'   => 'notice notice-error is-dismissible',
		'message' => sprintf(
			'
			<p>%1$s</p>
			%2$s
			%3$s
			%4$s %5$s',
			$is_restore ?
				esc_html__( 'There was an error restoring the selected backup archive file', 'boldgrid-backup' ) :
				esc_html__( 'There was an error creating a backup archive file', 'boldgrid-backup' ),
			// translators: 1: File path.
			empty( $archive_info['filepath'] ) ? '' : '<p>' . sprintf( esc_html__( 'File Path: %1$s', 'boldgrid-backup' ), $archive_info['filepath'] ) . '</p>',
			empty( $archive_info['error'] ) ? '' : '<p>' . $archive_info['error'] . '</p>',
			// translators: 1: Error message.
			! isset( $archive_info['error_message'] ) ? '' : '<p>' . sprintf( esc_html__( 'Error Details: %1$s', 'boldgrid-backup' ), $archive_info['error_message'] ),
			isset( $archive_info['error_message'] ) && isset( $archive_info['error_code'] ) ?
				' (' . $archive_info['error_code'] . ')' : ''
		),
	];
}

if ( ! isset( $message ) ) {
	$message = [
		'class'   => 'notice notice-error is-dismissible',
		'message' => esc_html__( 'Unknown error.', 'boldgrid-backup' ),
		'header'  => BOLDGRID_BACKUP_TITLE,
	];
}

$message['header'] = isset( $message['header'] ) ? $message['header'] : null;

return $message;
