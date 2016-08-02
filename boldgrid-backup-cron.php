<?php
/**
 * BoldGrid Backup cron control script
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

// Welcome.
echo '== BoldGrid Backup Cron Job ==' . PHP_EOL;

// Check for input variables.
if ( true === empty( $argv ) ) {
	die( 'Error: No parameters were passed.  A mode is required.' . PHP_EOL );
}

// Initialize $input.
$input = null;

// Parse input variables into an array.
// Expected parameters: "mode", "HTTP_HOST".
parse_str( implode( '&', array_slice( $argv, 1 ) ), $input );

// Validate mode.
if ( true === empty( $input['mode'] ) ) {
	die( 'Error: A mode was not specified.' . PHP_EOL );
}

$valid_modes = array(
	'backup',
	'restore',
);

if ( false === in_array( $input['mode'], $valid_modes, true ) ) {
	die( 'Error: Invalid mode "' . $input['mode'] . '".' . PHP_EOL );
}

// Validate HTTP_HOST.
if ( true === empty( $input['HTTP_HOST'] ) ) {
	die( 'Error: HTTP_HOST was not specified.' . PHP_EOL );
}

// Set the HTTP_HOST.
$_SERVER['HTTP_HOST'] = $input['HTTP_HOST'];

// Set DOING_CRON.
if ( false === defined( 'DOING_CRON' ) ) {
	define( 'DOING_CRON', true );
}

// Check of a dry run was specified.
$dry_run = ( false === empty( $input['dry_run'] ) );

// Set the current working directory to the WordPress installation root directory.
$abspath = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );

if ( false === chdir( $abspath ) ) {
	die( 'Error: Could not change to directory "' . $abspath . '".' . PHP_EOL );
}

// Load WordPress.
echo 'Loading WordPress... ';

require_once $abspath . '/wp-load.php';

esc_html_e( 'Done.', 'boldgrid-backup' );

echo PHP_EOL;

// Load BoldGrid Backup.
esc_html_e( 'Loading BoldGrid Backup... ', 'boldgrid-backup' );

$boldgrid_backup_core = new Boldgrid_Backup_Admin_Core();

esc_html_e( 'Done.', 'boldgrid-backup' );

echo PHP_EOL;

// If there is no rollback deadline, then
if ( true === empty( $pending_rollback['deadline'] ) ) {

}

// Perform operations.
switch ( $input['mode'] ) {
	case 'backup' :
		esc_html_e( 'Starting backup operation', 'boldgrid-backup' );

		if ( true === $dry_run ) {
			esc_html_e( ' (dry-run)', 'boldgrid-backup' );
		}

		echo '...';

		$archive_info = $boldgrid_backup_core->archive_files( true, $dry_run );

		esc_html_e( 'Done.', 'boldgrid-backup' );

		echo PHP_EOL;

		break;

	case 'restore' :
		// If there is no pending rollback in the options, then abort.
		if ( true === is_multisite() ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );
		}

		if ( true === empty( $pending_rollback ) ) {
			// Remove existing restore cron jobs.
			$boldgrid_backup_core->settings->delete_cron_entries( 'restore' );

			die(
				esc_html__(
					'Error: Pending rollback information is missing.',
					'boldgrid-backup'
				)
			);
		}

		// If the deadline has elapsed more than 2 minutes ago, then abort.
		if ( true === empty( $pending_rollback['deadline'] ) ||
		$pending_rollback['deadline'] < strtotime( 'NOW -2 MINUTES' ) ) {
			// Delete the pending rollback information.
			$boldgrid_backup_core->settings->delete_rollback_option();

			// Remove existing restore cron jobs.
			$boldgrid_backup_core->settings->delete_cron_entries( 'restore' );

			die(
				esc_html__(
					'Error: Pending rollback time has passed more than 2 minutes ago.',
					'boldgrid-backup'
				)
			);
		}

		esc_html_e( 'Starting restoration operation', 'boldgrid-backup' );

		if ( true === $dry_run ) {
			esc_html_e( ' (dry-run)', 'boldgrid-backup' );
		}

		echo '...';

		// Set GET variables.
		$_GET['restore_now'] = 1;
		$_GET['archive_key'] = ( true === isset( $input['archive_key'] ) ? $input['archive_key'] : null );
		$_GET['archive_filename'] = ( false === empty( $input['archive_filename'] ) ? $input['archive_filename'] : null );

		// Call the restore function.
		$archive_info = $boldgrid_backup_core->restore_archive_file( $dry_run );

		// Remove existing restore cron jobs.
		$boldgrid_backup_core->settings->delete_cron_entries( 'restore' );

		esc_html_e( 'Done.', 'boldgrid-backup' );

		echo PHP_EOL;

		break;

	default :
		die(
			sprintf(
				esc_html__(	'Error: Invalid mode "%s" was specified.', 'boldgrid-backup' ),
				$input['mode']
				)
		);
		break;
}

// Check return for mode.
if ( true === empty( $archive_info['mode'] ) ) {
	$archive_info['mode'] = $input['mode'];
}

// Print report.
$boldgrid_backup_core->print_cron_report( $archive_info );

esc_html_e( 'Operation complete.', 'boldgrid-backup' );

echo PHP_EOL;
