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

// If not called from command-line or system cron, abort.
if ( 'cli' !== php_sapi_name() ) {
	die( 'Error: This utility must be run from the command-line.' . PHP_EOL );
}

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

echo 'Done.' . PHP_EOL;

// Load BoldGrid Backup.
echo __( 'Loading BoldGrid Backup... ' );

$boldgrid_backup_core = new Boldgrid_Backup_Admin_Core();

echo __( 'Done.' ) . PHP_EOL;

// Perform operations.
switch ( $input['mode'] ) {
	case 'backup' :
		echo __( 'Starting backup operation' );

		if ( true === $dry_run ) {
			echo __( ' (dry-run)' );
		}

		echo '...';

		$archive_info = $boldgrid_backup_core->archive_files( true, $dry_run );

		echo __( 'Done.' ) . PHP_EOL;
		break;

	case 'restore' :
		echo __( 'Starting restoration operation' );

		if ( true === $dry_run ) {
			echo __( ' (dry-run)' );
		}

		echo '...';

		// Set GET variables.
		$_GET['restore_now'] = 1;
		$_GET['archive_key'] = ( false === empty( $input['archive_key'] ) ? $input['archive_key'] : null );
		$_GET['archive_filename'] = ( false === empty( $input['archive_filename'] ) ? $input['archive_filename'] : null );

		// Call the restore function.
		$archive_info = $boldgrid_backup_core->restore_archive_file( $dry_run );

		// Remove existing restore cron jobs.
		$boldgrid_backup_core->settings->delete_cron_entries( 'restore' );

		echo __( 'Done.' ) . PHP_EOL;
		break;

	default :
		die( __( 'Error: Invalid mode "' . $input['mode'] . '" was specified.' ) );
		break;
}

// Check return for mode.
if ( true === empty( $archive_info['mode'] ) ) {
	$archive_info['mode'] = $input['mode'];
}

// Print report.
$boldgrid_backup_core->print_cron_report( $archive_info );

echo __( 'Operation complete.' ) . PHP_EOL;
