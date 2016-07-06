<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link http://www.boldgrid.com
 * @since 1.0.1
 * @package Boldgrid_Backup
 *
 *          @wordpress-plugin
 *          Plugin Name: BoldGrid Backup
 *          Plugin URI: http://www.boldgrid.com
 *          Description: The BoldGrid Backup plugin.
 *          Version: 1.0
 *          Author: BoldGrid.com
 *          Author URI: http://www.boldgrid.com
 *          License: GPL-2.0+
 *          License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *          Text Domain: boldgrid-backup
 *          Domain Path: /languages
 */

// If this file is called directly, abort.
if ( false === defined( 'WPINC' ) ) {
	die();
}

// Define version.
if ( false === defined( 'BOLDGRID_BACKUP_VERSION' ) ) {
	define( 'BOLDGRID_BACKUP_VERSION', '1.0.1' );
}

// Define boldgrid-backup path.
if ( false === defined( 'BOLDGRID_BACKUP_PATH' ) ) {
	define( 'BOLDGRID_BACKUP_PATH', dirname( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-boldgrid-backup-activator.php
 */
function activate_boldgrid_backup() {
	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-activator.php';
	Boldgrid_Backup_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-boldgrid-backup-deactivator.php
 */
function deactivate_boldgrid_backup() {
	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-deactivator.php';
	Boldgrid_Backup_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_boldgrid_backup' );
register_deactivation_hook( __FILE__, 'deactivate_boldgrid_backup' );

/**
 * The core plugin class that is used to define internationalization and admin-specific hooks.
 */
if ( true === is_admin() || ( true === defined( 'DOING_CRON' ) && DOING_CRON ) ) {
	require BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup.php';
}

// If DOING_CRON, then check if this plugin should be auto-updated.
if ( true === defined( 'DOING_CRON' ) && DOING_CRON ){
	// Ensure required definitions for pluggable.
	if ( false === defined( 'AUTH_COOKIE' ) ) {
		define( 'AUTH_COOKIE', null );
	}

	if ( false === defined( 'LOGGED_IN_COOKIE' ) ) {
		define( 'LOGGED_IN_COOKIE', null );
	}

	// Load the pluggable class, if needed.
	require_once ABSPATH . 'wp-includes/pluggable.php';

	// Include the update class.
	require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-update.php';

	// Instantiate the update class.
	$plugin_update = new Boldgrid_Backup_Admin_Update();

	// Check and update plugins.
	$plugin_update->wp_update_this_plugin();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0
 */
function run_boldgrid_backup() {
	$plugin = new Boldgrid_Backup();
	$plugin->run();
}

// Load the plugin only if on a wp-admin page or when DOING_CRON.
if ( true === is_admin() || ( true === defined( 'DOING_CRON' ) && DOING_CRON ) ) {
	run_boldgrid_backup();
}
