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

// Delete BoldGrid Backup WordPress options.
$delete_options = array(
	'boldgrid_backup_settings',
	'boldgrid_backup_last_backup',
	'boldgrid_backup_pending_rollback',
);

$is_mutlisite = is_multisite();

if ( true === $is_multisite ) {
	foreach ( $delete_options as $option ) {
		delete_site_option( $option );
	}
} else {
	foreach ( $delete_options as $option ) {
		delete_option( $option );
	}
}
