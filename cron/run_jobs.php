<?php
/**
 * BoldGrid Backup Run Jobs.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package   Boldgrid_Backup
 * @copyright BoldGrid.com
 * @version   $Id$
 * @author    BoldGrid.com <wpb@boldgrid.com>
 */

// Abort if not being ran from the command line.
if( ! isset( $_SERVER['argv'], $_SERVER['argc'] ) || ! $_SERVER['argc'] ) {
    die( 'Error: No parameters were passed.  A "siteurl" and "id" are required.' . PHP_EOL );
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

// Make an ajax call to run jobs, and report status.
$url = $input['siteurl'] . '/wp-admin/admin-ajax.php?action=boldgrid_backup_run_jobs&id=' .
	$input['id'] . '&doing_wp_cron=' . time();

$result = file_get_contents( $url );

if ( false !== $result ) {
	$message = $result;
} else {
	$message = 'Error: Could not reach URL address "' . $url . '".';
}

die( $message );
