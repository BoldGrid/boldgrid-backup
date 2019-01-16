<?php
/**
 * File: restore.php
 *
 * Emergency restoration script.  This script is used when there is a severe issue with the site
 * which requires immediate restoration from the latest backup archive.
 *
 * @link https://www.boldgrid.com
 * @since 1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput,WordPress.WP.AlternativeFunctions
 */

echo 'Starting emergency restoration process...' . PHP_EOL;

if ( ini_get( 'safe_mode' ) ) {
	echo 'Error: Cannot continue in PHP safe mode.' . PHP_EOL;
	exit( 1 );
}

// Get the backup results file.
$results_file_path = __DIR__ . '/restore-info.json';

if ( ! file_exists( $results_file_path ) ) {
	echo 'Error: No backup results file ("' . $results_file_path . '").' . PHP_EOL;
	exit( 1 );
}

$results = json_decode( file_get_contents( $results_file_path ), true );

// Validate results file content.
if ( empty( $results ) ) {
	echo 'Error: No backup results found.' . PHP_EOL;
	exit( 1 );
}

if ( empty( $results['siteurl'] ) ) {
	echo 'Error: Unknown siteurl.' . PHP_EOL;
	exit( 1 );
}

if ( empty( $results['cron_secret'] ) ) {
	echo 'Error: Unknown cron_secret.' . PHP_EOL;
	exit( 1 );
}

if ( empty( $results['archive_filepath'] ) ) {
	echo 'Error: Unknown backup archive file path.' . PHP_EOL;
	exit( 1 );
}

require __DIR__ . '/class-boldgrid-backup-cron-helper.php';
$cron_helper = new Boldgrid_Backup_Cron_Helper();

// Abort if not being ran from the command line.
if ( ! $cron_helper->is_cli() ) {
	echo 'Error: This process must run from the CLI.' . PHP_EOL;
	exit( 1 );
}

// Abort if execution functions are disabled.
$exec_functions = $cron_helper->get_execution_functions();

if ( empty( $exec_functions ) ) {
	echo 'Error: No available PHP executable functions.' . PHP_EOL;
	exit( 1 );
}

echo 'Attempting to restore "' . $results['siteurl'] . '" from backup archive file "' .
	$results['archive_filepath'] . '"...' . PHP_EOL;

// Check if the siteurl is reachable.
require __DIR__ . '/class-boldgrid-backup-url-helper.php';
$url_helper = new Boldgrid_Backup_Url_Helper();

$is_siteurl_reachable = false !== $url_helper->call_url( $results['siteurl'] );
$restore_cmd          = ! empty( $results['restore_cmd'] ) ? $results['restore_cmd'] : null;

if ( $is_siteurl_reachable && $restore_cmd ) {
	// Call the normal restore command.
	echo 'Using URL address restoration process.' . PHP_EOL;
	echo $cron_helper->execute_command( $restore_cmd, $success, $return_var ) . PHP_EOL;
} else {
	// Start the standalone restoration process.
	echo 'Cannot reach the site URL; using standalone restoration process.' . PHP_EOL;
	// @todo: Work on this section.
	echo $cron_helper->execute_command( 'echo "Still working on this."', $success, $return_var ) . PHP_EOL;
}

// Check for success.
if ( $success && 0 === $return_var ) {
	echo 'Success.' . PHP_EOL;
} else {
	echo 'Error: Could not perform restoration.' . PHP_EOL;
	exit( 1 );
}
