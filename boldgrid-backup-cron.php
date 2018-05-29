<?php
/**
 * BoldGrid Backup cron control script
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @copyright BoldGrid
 * @version $Id$
 * @author BoldGrid <support@boldgrid.com>
 */

// Abort if not being ran from the command line.
if ( ! isset( $_SERVER['argv'], $_SERVER['argc'] ) || ! $_SERVER['argc'] ) {
	die( 'Error: No parameters were passed.  A "siteurl", "mode", and "id" are required.' . PHP_EOL );
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
	die( $error );
}

// Abort if not a valid mode.
$valid_modes = array(
	'backup',
	'restore',
);

if ( ! in_array( $input['mode'], $valid_modes, true ) ) {
	die( 'Error: Invalid mode "' . $input['mode'] . '".' . PHP_EOL );
}

// Make an ajax call to run jobs, and report status.
$url = $input['siteurl'] . '/wp-admin/admin-ajax.php?action=boldgrid_backup_run_' . $input['mode'] .
	'&id=' . $input['id'] . '&secret=' . $input['secret'] . '&doing_wp_cron=' . time();

// The helper class method will sanitize the url.
require dirname( __FILE__ ) . '/cron/url-helper.php';
$url_helper = new Boldgrid_Backup_Url_Helper();
$result     = $url_helper->call_url( $url );

if ( false !== $result ) {
	$message = $result;
} else {
	$message = 'Error: Could not reach URL address "' . $url . '".';
}

die( $message );
