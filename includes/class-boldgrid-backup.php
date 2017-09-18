<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across the admin area.
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization and admin-specific hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since 1.0
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @author BoldGrid.com <wpb@boldgrid.com>
 */
class Boldgrid_Backup {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0
	 * @access protected
	 * @var Boldgrid_Backup_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0
	 * @access protected
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0
	 * @access protected
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area of the site.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->plugin_name = 'boldgrid-backup';
		$this->version = ( defined( 'BOLDGRID_BACKUP_VERSION' ) ? BOLDGRID_BACKUP_VERSION : '' );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Boldgrid_Backup_Loader. Orchestrates the hooks of the plugin.
	 * - Boldgrid_Backup_i18n. Defines internationalization functionality.
	 * - Boldgrid_Backup_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin.php';

		/**
		 * Include a utility class.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-utility.php';

		/**
		 * The class responsible for the configuration of the plugin.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-config.php';

		/**
		 * The class responsible for the functionality test for the plugin.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-test.php';

		/**
		 * The class responsible for the admin notices for the plugin.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-notice.php';

		/**
		 * The class responsible for the cron functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cron.php';

		/**
		 * The class responsible for the core backup functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-core.php';

		/**
		 * The class responsible for the backup settings in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-settings.php';

		/**
		 * The class responsible for the plugin update functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-update.php';

		/**
		 * The class responsible for the PHP profiling functionality using XHProf.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-xhprof.php';

		/**
		 * The class responsible for the plugin file upload functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-upload.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-restore-helper.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-restore-git.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-filelist.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-compressor.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-compressors.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/php_zip.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/pcl_zip.php';

		include BOLDGRID_BACKUP_PATH . '/vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-dump.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-import.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-backup-dir.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-log.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-details.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-wp-cron.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-scheduler.php';

		$this->loader = new Boldgrid_Backup_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Boldgrid_Backup_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new Boldgrid_Backup_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return null
	 */
	private function define_admin_hooks() {
		// Instantiate a Boldgrid_Backup_Admin class object.
		$plugin_admin = new Boldgrid_Backup_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		// Instantiate the admin core.
		$plugin_admin_core = new Boldgrid_Backup_Admin_Core();

		// Instantiate the admin update class, if not already done for cron.
		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			$plugin_update = new Boldgrid_Backup_Update( Boldgrid_Backup_Admin::get_configs() );
		}

		// Add nav menu items.
		$this->loader->add_action( 'admin_menu', $plugin_admin_core,
			'add_menu_items'
		);

		// Add a custom action for admin notices.
		$this->loader->add_action( 'boldgrid_backup_notice', $plugin_admin_core->notice,
			'boldgrid_backup_notice', 10, 2
		);

		// Add a custom action to handle AJAX callback for creating a backup archive file.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_now', $plugin_admin_core,
			'boldgrid_backup_now_callback'
		);

		// Add a custom action to handle AJAX callback for archive file download buttons.
		$this->loader->add_action( 'wp_ajax_download_archive_file', $plugin_admin_core,
			'download_archive_file_callback'
		);

		// Add an action to perform an auto-backup before an auto-update occurs.
		$this->loader->add_action( 'pre_auto_update', $plugin_admin_core,
			'boldgrid_backup_now_auto'
		);

		// Add an action to display an admin notice for a pending rollback.
		$this->loader->add_action( 'admin_notices', $plugin_admin_core,
			'rollback_notice'
		);

		// Add a custom action to handle AJAX callback for canceling a pending rollback.
		$this->loader->add_action( 'wp_ajax_boldgrid_cancel_rollback', $plugin_admin_core,
			'boldgrid_cancel_rollback_callback'
		);

		// Add an action to display an admin notice on the WordPress Updates page.
		$this->loader->add_action( 'core_upgrade_preamble', $plugin_admin_core,
			'backup_notice'
		);

		// Add an action to display an admin notice on the WordPress Plugins page.
		$this->loader->add_action( 'pre_current_active_plugins', $plugin_admin_core,
			'backup_notice'
		);

		// Add an action to add a cron job to restore after WordPress Updates, unless canceled.
		$this->loader->add_action( 'upgrader_process_complete', $plugin_admin_core,
			'upgrader_process_complete'
		);

		// Add a custom action to handle AJAX callback for getting the rollback deadline.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_deadline', $plugin_admin_core,
			'boldgrid_backup_deadline_callback'
		);

		// Add a custom action to handle AJAX callback for getting size data.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_sizes', $plugin_admin_core,
			'boldgrid_backup_sizes_callback'
		);

		$this->loader->add_action( 'boldgrid_backup_pre_restore', $plugin_admin_core->restore_helper, 'pre_restore' );
		$this->loader->add_action( 'boldgrid_backup_post_restore', $plugin_admin_core->restore_helper, 'post_restore' );
		$this->loader->add_action( 'boldgrid_backup_post_restore_htaccess', $plugin_admin_core->restore_helper, 'post_restore_htaccess' );
		$this->loader->add_action( 'boldgrid_backup_post_restore_wpconfig', $plugin_admin_core->restore_helper, 'post_restore_wpconfig' );
		$this->loader->add_filter( 'boldgrid_backup_restore_fail', $plugin_admin_core->restore_helper, 'restore_fail' );

		$this->loader->add_filter( 'boldgrid_backup_cannnot_restore_git_objects', $plugin_admin_core->restore_git, 'chmod_objects' );

		$this->loader->add_filter( 'boldgrid_backup_file_in_dir', $plugin_admin_core->backup_dir, 'file_in_dir' );

		$this->loader->add_filter( 'unzip_file_use_ziparchive', $plugin_admin_core->compressors, 'unzip_file_use_ziparchive' );

		$this->loader->add_filter( 'cron_schedules', $plugin_admin_core->wp_cron, 'cron_schedules' );
		$this->loader->add_action( 'boldgrid_backup_wp_cron_backup', $plugin_admin_core->wp_cron, 'backup' );

		return;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since 1.0
	 * @return Boldgrid_Backup_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
