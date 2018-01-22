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
 * http://www.binarytides.com/php-check-running-cli/
 */
$is_cli = empty( $_SERVER['REMOTE_ADDR'] ) && ! isset( $_SERVER['HTTP_USER_AGENT'] ) && count( $_SERVER['argv'] ) > 0;
if( ! $is_cli ) {
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