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

/*
 * Abort if not being ran from the command line.
 *
 * https://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
 */
$sapi_type = php_sapi_name();
if( substr( $sapi_type, 0, 3 ) !== 'cli' || ! empty($_SERVER['REMOTE_ADDR'] ) ) {
    return;
}

if ( ! defined( 'DOING_CRON' ) ) {
	define( 'DOING_CRON', true );
}

$abspath = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
if ( ! chdir( $abspath ) ) {
	die( 'Error: Could not change to directory "' . $abspath . '".' . PHP_EOL );
}
require_once $abspath . '/wp-load.php';

$core = new Boldgrid_Backup_Admin_Core();
$core->jobs->run();