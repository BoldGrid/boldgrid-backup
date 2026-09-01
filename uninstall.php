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
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete Total Upkeep WordPress options.
$boldgrid_backup_delete_options = array(
	'boldgrid_backup_settings',
	'boldgrid_backup_last_backup',
	'boldgrid_backup_pending_rollback',
);

foreach ( $boldgrid_backup_delete_options as $boldgrid_backup_option ) {
	delete_site_option( $boldgrid_backup_option );
}
