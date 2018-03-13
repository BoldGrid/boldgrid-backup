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
 *          Plugin URI: https://www.boldgrid.com/boldgrid-backup/
 *          Description: The BoldGrid Backup plugin.
 *          Version: 1.6.0-rc.5
 *          Author: BoldGrid.com
 *          Author URI: https://www.boldgrid.com/
 *          License: GPL-2.0+
 *          License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *          Text Domain: boldgrid-backup
 *          Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// Define version.
if ( ! defined( 'BOLDGRID_BACKUP_VERSION' ) ) {
	define( 'BOLDGRID_BACKUP_VERSION', implode( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
}

// Define boldgrid-backup path.
if ( ! defined( 'BOLDGRID_BACKUP_PATH' ) ) {
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

// Include the autoloader to set plugin options and create instance.
$loader = require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Load Library.
$load = new Boldgrid\Library\Util\Load(
	array(
		'type' => 'plugin',
		'file' => plugin_basename( __FILE__ ),
		'loader' => $loader,
		'keyValidate' => true,
		'licenseActivate', false,
	)
);

// Load the plugin only if on a wp-admin page or when DOING_CRON.
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup.php';
	run_boldgrid_backup();
}
