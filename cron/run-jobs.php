<?php
/**
 * File: run-jobs.php
 *
 * Run jobs.
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

// Abort if not being ran from the command line.
if ( ! isset( $_SERVER['argv'], $_SERVER['argc'] ) || ! $_SERVER['argc'] ) { // WPCS: input var ok; sanitization ok.
	die( 'Error: No parameters were passed.  A "siteurl" and "id" are required.' . PHP_EOL ); // WPCS: XSS ok.
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
);

// Abort if arguments are not passed.
foreach ( $required_arguments as $required_argument ) {
	if ( empty( $input[ $required_argument ] ) ) {
		$error .= 'Error: "' . $required_argument . '" was not specified.' . PHP_EOL;
	}
}

if ( $error ) {
	die( $error ); // WPCS: XSS ok.
}

// Make an ajax call to run jobs, and report status.
$url = $input['siteurl'] . '/wp-admin/admin-ajax.php?action=boldgrid_backup_run_jobs&id=' .
	$input['id'] . '&secret=' . $input['secret'] . '&doing_wp_cron=' . time();

// The helper class method will sanitize the url.
require dirname( __FILE__ ) . '/class-boldgrid-backup-url-helper.php';
$url_helper = new Boldgrid_Backup_Url_Helper();
$result     = $url_helper->call_url( $url );

if ( false !== $result ) {
	$message = $result;
} else {
	$message = 'Error: Could not reach URL address "' . $url . '".';
}

die( $message ); // WPCS: XSS ok.
