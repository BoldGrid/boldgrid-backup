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
 * @subpackage Boldgrid_Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 */

use Boldgrid\Backup\Cron\Info;

/**
 * Handle fatal errors on PHP shutdown.
 *
 * @since 1.10.0
 *
 * @link https://www.php.net/manual/en/errorfunc.constants.php
 */
function handle_fatal() {
	$error      = error_get_last();
	$error_nums = [
		1, // E_ERROR.
		4, // E_PARSE.
		64, // E_COMPILE_ERROR.
		256, // E_USER_ERROR.
	];

	if ( ! empty( $error['type'] ) && in_array( $error['type'], $error_nums, true ) ) {
		echo json_encode( $error );
		exit( 255 );
	}
}

register_shutdown_function( 'handle_fatal' );

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

// Run test.
chdir( $info['ABSPATH'] );

try {
	require 'wp-load.php';
	echo json_encode( [ 'success' => true ] );
	exit( 0 );
} catch ( Exception $e ) {
	echo json_encode( [ 'success' => false ] );
	exit( 1 );
}
