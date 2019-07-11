<?php
/**
 * File: wp-test.php
 *
 * Tests if WordPress loads.
 *
 * @link       https://www.boldgrid.com
 * @link       https://github.com/BoldGrid/boldgrid-backup/wiki/Restorations-outside-of-WordPress
 * @since      1.10.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup\Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions,WordPress.PHP.DevelopmentFunctions
 */

use Boldgrid\Backup\Cli\Info;

// Track if there was an exception.  This is not the greatest idea, but works for now.
$had_exception = false;

/**
 * Clean (erase) the output buffer and turn off output buffering for all levels.
 *
 * @since 1.10.0
 */
function ob_clean_all() {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
}

/**
 * Handle fatal errors on PHP shutdown.
 *
 * @since 1.10.0
 *
 * @link https://www.php.net/manual/en/errorfunc.constants.php
 */
function handle_shutdown() {
	global $had_exception;

	$error      = error_get_last();
	$error_nums = [
		1, // E_ERROR.
		4, // E_PARSE.
		64, // E_COMPILE_ERROR.
		256, // E_USER_ERROR.
	];

	if ( ! empty( $error['type'] ) && in_array( $error['type'], $error_nums, true ) ) {
		ob_clean_all();
		echo json_encode( [
			'success' => false,
			'error'   => $error,
		] );
		exit( 255 );
	} elseif ( ! $had_exception && null === $error ) {
		// Success.
		ob_clean_all();
		echo json_encode( [ 'success' => true ] );
		exit( 0 );
	}
}

register_shutdown_function( 'handle_shutdown' );

require __DIR__ . '/class-info.php';

$get_results_filepath = Info::get_results_filepath();
$info                 = Info::read_json_file( $get_results_filepath );

if ( empty( $info['ABSPATH'] ) ) {
	echo json_encode( [
		'type'    => 256,
		'message' => 'Error: Could not retrieve ABSPATH from "' . $get_results_filepath . '".',
		'file'    => __FILE__,
		'line'    => __LINE__,
	] );
	exit( 1 );
}

// Start output buffering.
ob_start();

// Change to the WordPress ABSPATH (root/installation) directory.
chdir( $info['ABSPATH'] );

// Disable WP Cron for the tests.
defined( 'DISABLE_WP_CRON' ) || define( 'DISABLE_WP_CRON', true );

// Test loading WordPress front-end.
try {
	require 'wp-load.php';
} catch ( Exception $e ) {
	$had_exception = true;
	ob_clean_all();
	echo json_encode( [
		'success'         => false,
		'message'         => 'Could not load the WordPress site front-end. Exception message: "' .
			$e->getMessage() . '" (File: "' . $e->getFile() . '" Line: "' . $e->getLine() . '")',
		'debug_backtrace' => debug_backtrace( 0 ),
	] );
	exit( 1 );
}

// Test loading WordPress admin back-end.
$test_uri                   = 'wp-admin/admin-ajax.php';
$url_parts                  = parse_url( $info['siteurl'] );
$_SERVER['HTTP_HOST']       = $url_parts['host'];
$_SERVER['SERVER_PROTOCOL'] = $url_parts['scheme'];
$_SERVER['PHP_SELF']        = ( ! empty( $url_parts['path'] ) ? $url_parts['path'] : '' ) . '/' . $test_uri;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['SERVER_NAME']     = '';
$_SERVER['SERVER_PORT']     = 'https' === $url_parts['scheme'] ? '443' : '80';

try {
	require $test_uri;
} catch ( Exception $e ) {
	$had_exception = true;
	ob_clean_all();
	echo json_encode( [
		'success'         => false,
		'message'         => 'Could not load the WordPress admin back-end. Exception message: "' .
			$e->getMessage() . '" (File: "' . $e->getFile() . '" Line: "' . $e->getLine() . '")',
		'debug_backtrace' => debug_backtrace( 0 ),
	] );
	exit( 1 );
}
