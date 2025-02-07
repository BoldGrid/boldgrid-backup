<?php
/**
 * File: boldgrid-backup.php
 *
 * The plugin bootstrap file.
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.0.0
 * @package Boldgrid_Backup
 *
 *          @wordpress-plugin
 *          Plugin Name: Total Upkeep
 *          Plugin URI: https://www.boldgrid.com/boldgrid-backup/
 *          Description: Automated backups, remote backup to Amazon S3 and Google Drive, stop website crashes before they happen and more. Total Upkeep is the backup solution you need.
 *          Version: 1.17.0
 *          Author: BoldGrid
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
	define( 'BOLDGRID_BACKUP_PATH', __DIR__ );
}

// Define boldgrid-backup key.
if ( ! defined( 'BOLDGRID_BACKUP_KEY' ) ) {
	define( 'BOLDGRID_BACKUP_KEY', 'boldgrid-backup' );
}

// Define The plugin title.
if ( ! defined( 'BOLDGRID_BACKUP_TITLE' ) ) {
	define( 'BOLDGRID_BACKUP_TITLE', 'Total Upkeep' );
}

require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-utility.php';

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

/**
 * Load Total Upkeep.
 *
 * Before loading, ensure system meets minimum requirements:
 * # vendor folder exists. This is not a system requirement, but we want to make
 *   sure the user is NOT running a dev version with a missing vendor folder.
 *
 * @since 1.6.0
 *
 * @see Boldgrid_Backup_Admin_Support::run_tests()
 *
 * @return bool
 */
function load_boldgrid_backup() {
	require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-support.php';
	$support      = new Boldgrid_Backup_Admin_Support();
	$tests_passed = $support->run_tests();

	if ( ! $tests_passed ) {
		return false;
	}

	// Include the autoloader to set plugin options and create instance.
	$loader = require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Load Library.
	$load = new Boldgrid\Library\Util\Load(
		array(
			'type'            => 'plugin',
			'file'            => plugin_basename( __FILE__ ),
			'loader'          => $loader,
			'keyValidate'     => true,
			'licenseActivate' => false,
		)
	);

	// Make sure we have necessary library files.
	if ( ! $support->run_library_tests() ) {
		return false;
	}

	register_activation_hook( __FILE__, 'activate_boldgrid_backup' );
	register_deactivation_hook( __FILE__, 'deactivate_boldgrid_backup' );

	return true;
}

/*
 * Load the plugin.
 *
 * Above is only:
 * # function declarations
 * # constant declarations
 *
 * The initial loading of this plugin is done below.
 *
 * Run the plugin only if on a wp-admin page or when DOING_CRON.
 */
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || defined( 'WP_CLI' ) && WP_CLI || Boldgrid_Backup_Rest_Utility::is_rest() ) {
	// If we could not load boldgrid_backup (missing system requirements), abort.
	if ( load_boldgrid_backup() ) {
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup.php';
		run_boldgrid_backup();
	}
}

/*
 * Fix added as of 1.14.10.
 *
 * @todo This fix can be removed in the future.
 */
$oldname = BOLDGRID_BACKUP_PATH . '/cron/restore-info.json';
if ( file_exists( $oldname ) ) {
	require_once 'cli/class-info.php';
	$newname = BOLDGRID_BACKUP_PATH . '/cron/' . basename( \Boldgrid\Backup\Cli\Info::get_results_filepath() );
	rename( $oldname, $newname );
}
