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
 *          Version: 1.17.3
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

	/*
	 * Register BoldGrid Library version before Load (Composer 2 installed.json format).
	 *
	 * Library Version.php only understands Composer 1's flat installed.json. Composer 2 wraps
	 * packages under a "packages" key (and adds non-package keys like "dev"), so version
	 * detection returns null, Load never registers the PSR-4 path, and classes such as
	 * Boldgrid\Library\Library\Ui\Card are missing.
	 */
	$plugin_file = plugin_basename( __FILE__ );
	\Boldgrid\Library\Util\Option::init();
	$libraries = \Boldgrid\Library\Util\Option::get( 'library' );
	if ( empty( $libraries[ $plugin_file ] ) ) {
		$installed_file = plugin_dir_path( __FILE__ ) . 'vendor/composer/installed.json';
		if ( is_readable( $installed_file ) ) {
			$installed = json_decode( file_get_contents( $installed_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $installed ) ) {
				$packages = isset( $installed['packages'] ) ? $installed['packages'] : $installed;
				foreach ( $packages as $package ) {
					if (
						! empty( $package['name'] ) &&
						'boldgrid/library' === $package['name'] &&
						! empty( $package['version_normalized'] )
					) {
						\Boldgrid\Library\Util\Option::set( $plugin_file, $package['version_normalized'] );
						break;
					}
				}
			}
		}
	}

	// Load Library.
	$load = new Boldgrid\Library\Util\Load(
		array(
			'type'            => 'plugin',
			'file'            => $plugin_file,
			'loader'          => $loader,
			'keyValidate'     => true,
			'licenseActivate' => false,
		)
	);

	/*
	 * Drop Library's activation register callback.
	 *
	 * It re-reads Composer 1-only installed.json, stores a null version, and emits PHP warnings.
	 * Version is already registered above for Composer 2.
	 */
	$activate_hook = 'activate_' . $plugin_file;
	global $wp_filter;
	if ( isset( $wp_filter[ $activate_hook ] ) ) {
		foreach ( $wp_filter[ $activate_hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if (
					is_array( $callback['function'] ) &&
					isset( $callback['function'][0], $callback['function'][1] ) &&
					$callback['function'][0] instanceof \Boldgrid\Library\Util\Registration\Plugin &&
					'register' === $callback['function'][1]
				) {
					remove_action( $activate_hook, $callback['function'], $priority );
				}
			}
		}
	}

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
 *
 * Instantiation is deferred to init so constructors that call translation
 * functions do not trigger WP 6.7+ just-in-time textdomain notices.
 */
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || defined( 'WP_CLI' ) && WP_CLI || Boldgrid_Backup_Rest_Utility::is_rest() ) {
	// If we could not load boldgrid_backup (missing system requirements), abort.
	if ( load_boldgrid_backup() ) {
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup.php';
		if ( did_action( 'init' ) ) {
			run_boldgrid_backup();
		} else {
			add_action( 'init', 'run_boldgrid_backup', 1 );
		}
	}
}

/*
 * Legacy migration for pre-1.14.10 restore-info.json (kept for old installs).
 *
 * Only run when we have a stable secret source (locator or legacy verify file),
 * otherwise front-end requests can generate a one-off in-memory secret that drifts
 * on retry and orphans the renamed file. Admin/cron paths call ensure_secure_storage()
 * which handles migration properly.
 *
 * @todo This fix can be removed in the future.
 */
$oldname = BOLDGRID_BACKUP_PATH . '/cron/restore-info.json';
if ( file_exists( $oldname ) ) {
	require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';
	$has_stable_secret = \Boldgrid\Backup\Cli\Info::get_secure_storage_dir()
		|| \Boldgrid\Backup\Cli\Info::get_legacy_verify_secret();
	if ( $has_stable_secret ) {
		$results_path = \Boldgrid\Backup\Cli\Info::get_results_filepath();
		$new_basename = $results_path ? basename( $results_path ) : '';
		// Only rename onto a valid secret-named file; never restore-info-.json.
		if ( $new_basename && preg_match( '/^restore-info-[0-9a-f]{32}\.json$/', $new_basename ) ) {
			// Use the full resolved path (backup dir when a locator exists; else legacy cron/).
			$newname = $results_path;
			if ( ! file_exists( $newname ) ) {
				$moved = @rename( $oldname, $newname ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				/*
				 * rename() can fail across filesystems (plugin tree vs backup volume).
				 * Fall back to copy + unlink so the file still lands where CLI looks.
				 */
				if ( ! $moved && @copy( $oldname, $newname ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $oldname ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$moved = true;
				}
				if ( $moved ) {
					@chmod( $newname, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			} elseif ( file_exists( $oldname ) ) {
				unlink( $oldname );
			}
		}
	}
}
