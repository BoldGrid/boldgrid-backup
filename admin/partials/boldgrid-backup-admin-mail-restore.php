<?php
/**
 * File: boldgrid-backup-admin-mail-restore.php
 *
 * Mail template for restoration notifications.
 *
 * @link https://www.boldgrid.com
 * @since 1.2.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

/**
 * This template uses inherited variables.
 *
 * @see Boldgrid_Backup_Admin_Utility::create_site_id()
 *
 * @param bool $dryrun Whether or not is a dry run.
 * @param bool $restore_ok Success of the restoration.
 * @param string $info['filepath'] The file path restored.
 */
$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

$subject = sprintf(
	// translators: 1: Site identifier.
	esc_html__( 'Restoration completed for %s', 'boldgrid-backup' ),
	$site_id
);

$body = esc_html__( 'Hello', 'boldgrid-backup' ) . ",\n\n";

if ( $dryrun ) {
	$body .= esc_html__( 'THIS OPERATION WAS A DRY-RUN TEST', 'boldgrid-backup' ) . ".\n\n";
}

if ( $restore_ok ) {
	$body .= esc_html__( 'A backup archive has been restored', 'boldgrid-backup' );
} else {
	$body .= esc_html__(
		'An error occurred when attempting to restore a backup archive',
		'boldgrid-backup'
	);
}

$body .= sprintf(
	// translators: 1: Site identifier.
	__( ' for %s', 'boldgrid-backup' ),
	$site_id
) . ".\n\n";

$body .= esc_html__( 'Restoration details', 'boldgrid-backup' ) . ":\n";

$body .= sprintf(
	// translators: 1: Archive file path.
	esc_html__( 'Archive file path: %s', 'boldgrid-backup' ),
	$info['filepath']
) . "\n";

$body .= sprintf(
	// translators: 1: Archive file size.
	esc_html__( 'Archive file size: %s', 'boldgrid-backup' ),
	Boldgrid_Backup_Admin_Utility::bytes_to_human( $info['filesize'] )
) . "\n";

if ( defined( 'DOING_CRON' ) ) {
	$body .= esc_html__(
		'The restoration request was made via CRON (task scheduler)',
		'boldgrid-backup'
	) . ".\n\n";
}

$body .= sprintf(
	// translators: 1: Plugin title.
	esc_html__(
		'You can manage notifications in your WordPress admin panel, under %1$s Settings',
		'boldgrid-backup'
	),
	BOLDGRID_BACKUP_TITLE
) . ".\n\n";

$body .= esc_html__( 'Best regards', 'boldgrid-backup' ) . ",\n\n";

$body .= sprintf(
	// translators: 1: Plugin title.
	esc_html__( 'The %1$s plugin', 'boldgrid-backup' ),
	BOLDGRID_BACKUP_TITLE
) . "\n\n";
