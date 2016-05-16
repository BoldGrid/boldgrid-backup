<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 */

// If uninstall not called from WordPress, then exit.
if ( false === defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}
