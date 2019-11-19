<?php
/**
 * File: boldgrid-backup-cron.php
 *
 * Total Upkeep cron control script.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput.OutputNotEscaped

require dirname( __FILE__ ) . '/admin/class-boldgrid-backup-admin-cron-log.php';
require dirname( __FILE__ ) . '/cron/class-boldgrid-backup-cron-helper.php';
$cron_helper = new Boldgrid_Backup_Cron_Helper();

// Abort if not being ran from the command line.
if ( ! $cron_helper->is_cli() ) {
	$error = 'Error: No parameters were passed.  A "siteurl", "mode", and "id" are required.';
	Boldgrid_Backup_Admin_Cron_Log::add_log( $error );
	die( $error . PHP_EOL );
}

// Initialize $input and $error.
$input = null;
$error = '';

/**
 * Parse input variables into an array.
 * Expected parameter: "siteurl"
 */
parse_str( implode( '&', array_slice( $argv, 1 ) ), $input );

$required_arguments = array(
	'siteurl',
	'id',
	'secret',
	'mode',
);

// Abort if arguments are not passed.
foreach ( $required_arguments as $required_argument ) {
	if ( empty( $input[ $required_argument ] ) ) {
		$error .= 'Error: "' . $required_argument . '" was not specified.' . PHP_EOL;
	}
}

if ( $error ) {
	Boldgrid_Backup_Admin_Cron_Log::add_log( $error );
	die( $error );
}

// Abort if not a valid mode.
$valid_modes = array(
	'backup',
	'restore',
);

if ( ! in_array( $input['mode'], $valid_modes, true ) ) {
	$error = 'Error: Invalid mode "' . $input['mode'] . '".';
	Boldgrid_Backup_Admin_Cron_Log::add_log( $error );
	die( $error );
}

// Make an ajax call to run jobs, and report status.
$query_string = array(
	'action'        => 'boldgrid_backup_run_' . $input['mode'],
	'id'            => $input['id'],
	'secret'        => $input['secret'],
	'doing_wp_cron' => time(),
);

if ( ! empty( $input['archive_filename'] ) ) {
	$query_string['archive_filename'] = $input['archive_filename'];
}

if ( isset( $input['archive_key'] ) && is_numeric( $input['archive_key'] ) ) {
	$query_string['archive_key'] = (int) $input['archive_key'];
}

$url = $input['siteurl'] . '/wp-admin/admin-ajax.php?' . http_build_query( $query_string );

// The helper class method will sanitize the url.
require dirname( __FILE__ ) . '/cron/class-boldgrid-backup-url-helper.php';
$url_helper = new Boldgrid_Backup_Url_Helper();
$result     = $url_helper->call_url( $url );

if ( false !== $result ) {
	$message = $result;
} else {
	$error = 'Error: Could not reach admin-ajax.php address';
	Boldgrid_Backup_Admin_Cron_Log::add_log( $error );
	$message = $error . ': "' . $url . '".';
}

die( $message );
