<?php
/**
 * File: cron-test.php
 *
 * For a full description of what this test is doing and why, please see
 * Boldgrid_Backup_Admin_Cron_Test::setup()
 *
 * @link https://www.boldgrid.com
 * @since 1.6.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

$minute = date( 'i', time() );
$config = __DIR__ . '/cron-test.config';
$result = __DIR__ . '/cron-test.result';

if ( file_exists( $result ) ) {
	return;
}

$configs = json_decode( file_get_contents( $config ), true );
if ( empty( $configs ) ) {
	return;
}

/*
 * Try to find a match in the configs.
 *
 * Loop through all of the configs. If the current minute matches the minute of one of our configs,
 * log the results.
 */
foreach ( $configs as $config ) {
	$config_minute = date( 'i', $config['time'] );

	if ( $minute === $config_minute ) {
		$results = array(
			'offset' => $config['offset'],
		);

		file_put_contents( $result, json_encode( $results ) );

		die();
	}
}

