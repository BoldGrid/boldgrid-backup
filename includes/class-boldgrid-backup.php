<?php
/**
 * File: class-boldgrid-backup.php
 *
 * A class definition that includes attributes and functions used across the admin area.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

/**
 * Class: Boldgrid_Backup
 *
 * The core plugin class.
 * This is used to define internationalization and admin-specific hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since 1.0
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
		$this->version     = ( defined( 'BOLDGRID_BACKUP_VERSION' ) ? BOLDGRID_BACKUP_VERSION : '' );

		add_filter( 'doing_it_wrong_trigger_error', array( $this, 'disable_jit_notices' ), 10, 3 );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Disable Just In Time notices.
	 *
	 * @since 1.17.1
	 *
	 * @param bool   $doing_it_wrong Whether to trigger the error for _doing_it_wrong.
	 * @param string $function_name The function that was called.
	 * @param string $message The message that was passed to _doing_it_wrong.
	 *
	 * @return bool $doing_it_wrong Whether to trigger the error for _doing_it_wrong.
	 */
	public function disable_jit_notices( $doing_it_wrong, $function_name, $message ) {
		// if the function is _load_textdomain_just_in_time, return false to prevent the error.
		if ( '_load_textdomain_just_in_time' === $function_name && false !== strpos( $message, 'boldgrid-backup' ) ) {
			return false;
		}
		return $doing_it_wrong;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Boldgrid_Backup_Loader. Orchestrates the hooks of the plugin.
	 * - Boldgrid_Backup_I18n. Defines internationalization functionality.
	 * - Boldgrid_Backup_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once BOLDGRID_BACKUP_PATH . '/vendor/autoload.php';

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

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-auto-updates.php';

		/**
		 * The class responsible for the cron functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cron.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cron-test.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cron-log.php';

		/**
		 * The class responsible for the core backup functionality in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-core.php';

		/**
		 * The class responsible for the backup  in the admin area.
		 */
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-settings.php';

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
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-filelist-analyzer.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-compressor.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-compressors.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/class-boldgrid-backup-admin-compressor-php-zip.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/class-boldgrid-backup-admin-compressor-pcl-zip.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip-test.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip-temp-folder.php';

		require_once BOLDGRID_BACKUP_PATH . '/vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-dump.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-get.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-import.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-db-omit.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-backup-dir.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-actions.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archives.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archives-all.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-browser.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-log.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-details.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive-fail.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archiver-cancel.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archiver-utility.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-wp-cron.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-scheduler.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-home-dir.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-auto-rollback.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-jobs.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-remote.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/storage/class-boldgrid-backup-admin-storage-local.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-email.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-folder-exclusion.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-core-files.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-in-progress.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-in-progress-data.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-in-progress-tmp.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/remote/class-boldgrid-backup-admin-ftp.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/remote/class-boldgrid-backup-admin-ftp-hooks.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/remote/class-boldgrid-backup-admin-ftp-page.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/remote/class-boldgrid-backup-admin-remote-settings.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-go-pro.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-tools.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-transfers.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-time.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-crypt.php';

		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-authentication.php';
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-download.php';
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-file.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-cli.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-dashboard-widget.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-dashboard.php';

		// Cron Module.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-cron.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/cron/class-crontab.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/cron/entry/class-entry.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/cron/entry/class-base.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/cron/entry/class-crontab.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/cron/entry/class-wpcron.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-plugins.php';

		// Premium Features Page.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-premium-features.php';

		// Cards.
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-amazon-s3.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-backups.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-dream-objects.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-google-drive.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-database-encryption.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-historical-versions.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-history.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-one-click-restoration.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-plugin-editor-tools.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-premium.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-updates.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-find-modified-files.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/card/class-timely-auto-updates.php';

		// Features.
		if ( class_exists( '\Boldgrid\Library\Library\Ui\Feature' ) ) {
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-scheduled-backups.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-remote-storage.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-versions.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-auto-rollback.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-auto-update-backup.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-timely-auto-updates.php';
			// Features - Sign up for BoldGrid Central.
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-cloud-wordpress.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-speed-coach.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-sign-up.php';
			// Features - Upgrade to Pro.
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-more-backup.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-more-boldgrid.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-more-central.php';
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-database-encryption.php';
			// Feature - Pro.
			require_once BOLDGRID_BACKUP_PATH . '/admin/card/feature/class-central.php';
		}

		// WP-CLI support.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-wpcli.php';
		}

		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-activator.php';

		// REST API support.
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-controller.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-job.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-setting.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-archive.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-test.php';
		require_once BOLDGRID_BACKUP_PATH . '/rest/class-boldgrid-backup-rest-siteurl.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-usage.php';

		// Logs system.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-log.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-log-page.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-nopriv.php';

		// Task system.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-task.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-task-helper.php';

		// Archiver and Restorer classes.
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-archiver.php';
		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-restorer.php';

		require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-archive-fetcher.php';

		// Archive namespace.
		require_once BOLDGRID_BACKUP_PATH . '/includes/archive/class-factory.php';
		require_once BOLDGRID_BACKUP_PATH . '/includes/archive/class-option.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-plugin-notices.php';

		// Orphaned files.
		require_once BOLDGRID_BACKUP_PATH . '/admin/orphan/class-finder.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/orphan/class-cleanup.php';

		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-environment.php';

		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';

		// New Migration system.
		require_once BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-migrate.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-rx-rest.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-tx-rest.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-restore.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-rx.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-tx.php';
		require_once BOLDGRID_BACKUP_PATH . '/admin/migrate/class-boldgrid-backup-admin-migrate-util.php';

		$this->loader = new Boldgrid_Backup_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Boldgrid_Backup_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new Boldgrid_Backup_I18n();

		$this->loader->add_action( 'after_setup_theme', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since 1.0
	 * @access private
	 */
	private function define_admin_hooks() {
		// Instantiate a Boldgrid_Backup_Admin class object.
		$plugin_admin = new Boldgrid_Backup_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		// Instantiate the admin core.
		$plugin_admin_core = new Boldgrid_Backup_Admin_Core();

		// WP-CLI support.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			Boldgrid_Backup_Admin_Wpcli::$core = $plugin_admin_core;
		}

		$activator = new Boldgrid_Backup_Activator();
		$this->loader->add_action( 'admin_notices', $activator, 'post_activate_notice' );
		$this->loader->add_action( 'shutdown', $activator, 'shutdown' );

		// Add nav menu items.
		$this->loader->add_action(
			'admin_menu', $plugin_admin_core,
			'add_menu_items'
		);

		// Add a custom action for admin notices.
		$this->loader->add_action(
			'boldgrid_backup_notice', $plugin_admin_core->notice,
			'boldgrid_backup_notice', 10, 2
		);

		$this->loader->add_action( 'admin_notices', $plugin_admin_core->notice, 'display_user_notice' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_get_countdown_notice', $plugin_admin_core->auto_rollback, 'wp_ajax_get_countdown_notice' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_get_protect_notice', $plugin_admin_core->auto_rollback, 'wp_ajax_get_protect_notice' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_get_progress_notice', $plugin_admin_core->in_progress, 'wp_ajax_get_progress_notice' );
		$this->loader->add_action( 'core_upgrade_preamble', $plugin_admin_core->notice, 'display_autoupdate_notice' );

		// Add a custom action to handle AJAX callback for creating a backup archive file.
		$this->loader->add_action(
			'wp_ajax_boldgrid_backup_now', $plugin_admin_core,
			'boldgrid_backup_now_callback'
		);

		// Add a custom action to handle AJAX callback for archive file download buttons.
		$this->loader->add_action(
			'wp_ajax_download_archive_file', $plugin_admin_core,
			'download_archive_file_callback'
		);

		// Add an action to perform an auto-backup before an auto-update occurs.
		$this->loader->add_action(
			'pre_auto_update', $plugin_admin_core,
			'boldgrid_backup_now_auto'
		);

		// Add an action to display an admin notice for a pending rollback.
		$this->loader->add_action(
			'admin_notices', $plugin_admin_core->auto_rollback,
			'notice_countdown_show'
		);

		// Add a custom action to handle AJAX callback for canceling a pending rollback.
		$this->loader->add_action(
			'wp_ajax_boldgrid_cancel_rollback', $plugin_admin_core->auto_rollback,
			'wp_ajax_cancel'
		);

		// Add a custom action to handle AJAX callback for canceling a pending rollback from the CLI restoration script.
		$this->loader->add_action(
			'wp_ajax_nopriv_boldgrid_cli_cancel_rollback', $plugin_admin_core->auto_rollback,
			'wp_ajax_cli_cancel'
		);

		if ( $plugin_admin_core->test->run_functionality_tests() ) {
			$this->loader->add_action( 'admin_notices', $plugin_admin_core->auto_rollback, 'notice_backup_show' );
		}

		// Add an action to add a cron job to restore after WordPress Updates, unless canceled.
		$this->loader->add_action(
			'upgrader_process_complete', $plugin_admin_core->auto_rollback,
			'notice_deadline_show', 10, 2
		);

		// Add a custom action to handle AJAX callback for getting the rollback deadline.
		$this->loader->add_action(
			'wp_ajax_boldgrid_backup_deadline', $plugin_admin_core->auto_rollback,
			'wp_ajax_get_deadline'
		);

		$this->loader->add_action( 'boldgrid_backup_pre_restore', $plugin_admin_core->restore_helper, 'pre_restore' );
		$this->loader->add_action( 'boldgrid_backup_post_restore', $plugin_admin_core->restore_helper, 'post_restore' );
		$this->loader->add_filter( 'boldgrid_backup_post_restore', $plugin_admin_core->archive_log, 'post_restore' );
		$this->loader->add_action( 'boldgrid_backup_post_restore_htaccess', $plugin_admin_core->restore_helper, 'post_restore_htaccess' );
		$this->loader->add_action( 'boldgrid_backup_post_restore_wpconfig', $plugin_admin_core->restore_helper, 'post_restore_wpconfig' );
		$this->loader->add_filter( 'boldgrid_backup_restore_fail', $plugin_admin_core->restore_helper, 'restore_fail' );

		$this->loader->add_filter( 'boldgrid_backup_cannnot_restore_git_objects', $plugin_admin_core->restore_git, 'chmod_objects' );

		$this->loader->add_filter( 'boldgrid_backup_file_in_dir', $plugin_admin_core->backup_dir, 'file_in_dir' );

		$this->loader->add_filter( 'unzip_file_use_ziparchive', $plugin_admin_core->compressors, 'unzip_file_use_ziparchive' );

		$this->loader->add_filter( 'cron_schedules', $plugin_admin_core->wp_cron, 'cron_schedules' );
		$this->loader->add_action( 'boldgrid_backup_wp_cron_backup', $plugin_admin_core->wp_cron, 'backup' );
		$this->loader->add_action( 'boldgrid_backup_wp_cron_restore', $plugin_admin_core->wp_cron, 'restore' );

		$this->loader->add_action( 'boldgrid_backup_archive_files_init', $plugin_admin_core->archive_fail, 'archive_files_init' );
		$this->loader->add_action( 'wp_mail_failed', $plugin_admin_core->email, 'wp_mail_failed' );

		$this->loader->add_action( 'boldgrid_backup_wp_cron_run_jobs', $plugin_admin_core->jobs, 'run' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core, 'admin_enqueue_scripts' );

		$this->loader->add_filter( 'plugins_loaded', $plugin_admin_core, 'init_premium' );

		$this->loader->add_action( 'boldgrid_backup_delete_local', $plugin_admin_core->local, 'delete_local' );

		$this->loader->add_action( 'boldgrid_backup_post_archive_files', $plugin_admin_core->local, 'post_archive_files', 100 );
		$this->loader->add_action( 'boldgrid_backup_post_archive_files', $plugin_admin_core->jobs, 'post_archive_files', 200 );
		$this->loader->add_action( 'boldgrid_backup_post_jobs_email', $plugin_admin_core->jobs, 'post_jobs_email' );

		$this->loader->add_action( 'boldgrid_backup_cron_fail_email', $plugin_admin_core->archive_fail, 'cron_fail_email' );

		$this->loader->add_action( 'wp_ajax_boldgrid_backup_browse_archive', $plugin_admin_core->archive_browser, 'wp_ajax_browse_archive' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_browse_archive_file_actions', $plugin_admin_core->archive_browser, 'wp_ajax_file_actions' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_browse_archive_restore_db', $plugin_admin_core->archive_browser, 'wp_ajax_restore_db' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_browse_archive_view_db', $plugin_admin_core->archive_browser, 'wp_ajax_view_db' );

		$this->loader->add_action( 'wp_ajax_boldgrid_backup_restore_archive', $plugin_admin_core, 'wp_ajax_restore' );

		$this->loader->add_action( 'wp_ajax_boldgrid_backup_exclude_folders_preview', $plugin_admin_core->folder_exclusion, 'wp_ajax_preview' );

		$this->loader->add_action( 'admin_init', $plugin_admin_core->config, 'admin_init' );

		$this->loader->add_action( 'admin_init', $plugin_admin_core->auto_rollback, 'enqueue_update_selectors' );

		$this->loader->add_action( 'admin_init', $plugin_admin_core->cron, 'upgrade_crontab_entries' );

		$this->loader->add_action( 'wp_ajax_boldgrid_backup_generate_download_link', $plugin_admin_core->archive_actions, 'wp_ajax_generate_download_link' );

		/* FTP */

		// Allow one click upload.
		$this->loader->add_action( 'boldgrid_backup_single_archive_remote_options', $plugin_admin_core->ftp->hooks, 'single_archive_remote_option' );
		// Process upload via ajax.
		$this->loader->add_filter( 'wp_ajax_boldgrid_backup_remote_storage_upload_ftp', $plugin_admin_core->ftp->hooks, 'wp_ajax_upload' );
		// Add to the settings page.
		$this->loader->add_filter( 'boldgrid_backup_register_storage_location', $plugin_admin_core->ftp->hooks, 'register_storage_location' );
		// Add our "configure ftp" page.
		$this->loader->add_action( 'admin_menu', $plugin_admin_core->ftp->hooks, 'add_menu_items' );
		// After updating settings on the settings page, check if we have valid credentials.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_is_setup_ftp', $plugin_admin_core->ftp->hooks, 'is_setup_ajax' );
		// After a backup file has been created, add remote provider to jobs queue.
		$this->loader->add_action( 'boldgrid_backup_post_archive_files', $plugin_admin_core->ftp->hooks, 'post_archive_files' );
		// This is the filter executed by the jobs queue.
		$this->loader->add_filter( 'boldgrid_backup_ftp_upload_post_archive', $plugin_admin_core->ftp->hooks, 'upload_post_archiving' );
		// Add ftp backups to the "Backups" tab.
		$this->loader->add_action( 'boldgrid_backup_get_all', $plugin_admin_core->ftp->hooks, 'filter_get_all' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_remote_storage_download_ftp', $plugin_admin_core->ftp->hooks, 'wp_ajax_download' );
		// Styles and Scripts for FTP settings page.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core->ftp->page, 'enqueue_scripts' );
		$this->loader->add_filter( 'shutdown', $plugin_admin_core->ftp->hooks, 'shutdown' );

		$this->loader->add_action( 'admin_notices', $plugin_admin_core->go_pro, 'admin_notice_setup' );

		$this->loader->add_action( 'boldgrid_backup_pre_dump', $plugin_admin_core->in_progress, 'pre_dump' );
		$this->loader->add_action( 'boldgrid_backup_post_dump', $plugin_admin_core->in_progress, 'post_dump' );
		$this->loader->add_filter( 'heartbeat_received', $plugin_admin_core->in_progress, 'heartbeat_received', 10, 2 );

		$this->loader->add_action( 'customize_controls_enqueue_scripts', $plugin_admin_core->auto_rollback, 'enqueue_customize_controls' );

		add_filter( 'pre_update_option_boldgrid_backup_settings', array( 'Boldgrid_Backup_Admin_Crypt', 'pre_update_settings' ), 10, 3 );
		add_filter( 'option_boldgrid_backup_settings', array( 'Boldgrid_Backup_Admin_Crypt', 'option_settings' ), 10, 2 );

		// Actions run from crontab calls; unauthenticated.
		$this->loader->add_action( 'wp_ajax_nopriv_boldgrid_backup_run_jobs', $plugin_admin_core->jobs, 'run' );
		$this->loader->add_action( 'wp_ajax_nopriv_boldgrid_backup_run_backup', $plugin_admin_core->cron, 'backup' );
		$this->loader->add_action( 'wp_ajax_nopriv_boldgrid_backup_run_restore', $plugin_admin_core->cron, 'restore' );

		// For public downloads.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_download', $plugin_admin_core->download, 'public_download' );
		$this->loader->add_action( 'wp_ajax_nopriv_boldgrid_backup_download', $plugin_admin_core->download, 'public_download' );

		// Admin notices from cron log.
		$this->loader->add_action( 'admin_notices', $plugin_admin_core->cron_log, 'admin_notice' );

		// For Ajax URL import.
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_url_upload', $plugin_admin_core->upload, 'ajax_url_import' );

		// Filter the boldgrid_backup_pending_rollback site option.
		$this->loader->add_filter( 'site_option_boldgrid_backup_pending_rollback', $plugin_admin_core->auto_rollback, 'validate_rollback_option', 10, 2 );

		// Enable updating feature in the BoldGrid Library.
		add_filter( 'Boldgrid\Library\Update\isEnalbed', '__return_true' );

		$this->loader->add_filter( 'wp_ajax_boldgrid_backup_update_archive_details', $plugin_admin_core->archive_details, 'wp_ajax_update' );

		$this->loader->add_action( 'admin_menu', $plugin_admin_core->local, 'add_submenus' );

		$this->loader->add_action( 'wp_ajax_boldgrid_backup_is_setup_local', $plugin_admin_core->local, 'is_setup_ajax' );

		$this->loader->add_filter( 'Boldgrid\Library\Notifications\DashboardWidget\getFeaturePlugin\boldgrid-backup', $plugin_admin_core->dashboard_widget, 'filter_feature', 10, 2 );

		// Auto Update Actions & Filters.
		$this->loader->add_action( 'admin_init', $plugin_admin_core->auto_updates, 'auto_update_core' );
		$this->loader->add_action( 'wp_maybe_auto_update', $plugin_admin_core->auto_updates, 'auto_update_core' );
		$this->loader->add_filter( 'auto_update_plugin', $plugin_admin_core->auto_updates, 'auto_update_plugins', 10, 2 );
		$this->loader->add_filter( 'auto_update_theme', $plugin_admin_core->auto_updates, 'auto_update_themes', 10, 2 );
		$this->loader->add_action( 'update_option_auto_update_plugins', $plugin_admin_core->auto_updates, 'wordpress_option_updated', 10, 3 );
		$this->loader->add_action( 'update_option_auto_update_themes', $plugin_admin_core->auto_updates, 'wordpress_option_updated', 10, 3 );

		// This plugin's Dashboard.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core->dashboard, 'admin_enqueue_scripts' );

		// Disply a notice regarding the plugin rename.
		$this->loader->add_action( 'admin_notices', $plugin_admin_core->notice, 'plugin_renamed_notice' );
		$this->loader->add_action( 'wp_ajax_dismissBoldgridNotice', 'Boldgrid\Library\Library\Notice', 'dismiss' );

		// Register REST endpoints.
		add_action( 'rest_api_init', function() use ( $plugin_admin_core ) {
			$rest_job = new Boldgrid_Backup_Rest_Job( $plugin_admin_core );
			$rest_job->register_routes();

			$rest_archive = new Boldgrid_Backup_Rest_Archive( $plugin_admin_core );
			$rest_archive->register_routes();

			$rest_setting = new Boldgrid_Backup_Rest_Setting( $plugin_admin_core );
			$rest_setting->register_routes();

			$rest_test = new Boldgrid_Backup_Rest_Test( $plugin_admin_core );
			$rest_test->register_routes();

			$rest_siteurl = new Boldgrid_Backup_Rest_Siteurl( $plugin_admin_core );
			$rest_siteurl->register_routes();
		} );

		$usage = new Boldgrid_Backup_Admin_Usage();

		$this->loader->add_action( 'admin_init', $usage, 'admin_init' );
		$this->loader->add_filter( 'Boldgrid\Library\Usage\Notice\admin_notices', $usage, 'filter_notice' );
		$this->loader->add_filter( 'Boldgrid\Library\Usage\Notice\maybeShow', $usage, 'maybe_show_notice' );
		$this->loader->add_filter( 'Boldgrid\Library\Usage\getPrefixes', $usage, 'filter_prefixes' );
		$this->loader->add_filter( 'is_boldgrid_backup_page', $usage, 'has_screen_prefix' );
		$this->loader->add_action( 'in_admin_header', $plugin_admin_core->upload, 'archive_upload_action' );

		// Log system.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core->log_page, 'admin_enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_boldgrid_backup_view_log', $plugin_admin_core->log_page, 'wp_ajax_boldgrid_backup_view_log' );
		$this->loader->add_action( 'shutdown', $plugin_admin_core->logger, 'shutdown' );

		// Tools page.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core->tools, 'admin_enqueue_scripts' );

		add_action( 'wp_ajax_boldgrid_backup_cancel', 'Boldgrid_Backup_Admin_Archiver_Cancel::wp_ajax_cancel' );

		/*
		 * Plugin notices.
		 *
		 * The library's plugin notices class is instantiated below in order to add necessary filters.
		 */
		if ( class_exists( '\Boldgrid\Library\Library\Plugin\Notices' ) ) {
			new \Boldgrid\Library\Library\Plugin\Notices();
		}

		$plugin_notices = new Boldgrid_Backup_Admin_Plugin_Notices();
		$this->loader->add_filter( 'Boldgrid\Library\Plugin\Notices\admin_enqueue_scripts', $plugin_notices, 'filter' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin_core, 'add_thickbox' );

		/*
		 * Things to do in a dev environment.
		 *
		 * @link https://make.wordpress.org/core/2020/07/24/new-wp_get_environment_type-function-in-wordpress-5-5/
		 */
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'development' === WP_ENVIRONMENT_TYPE ) {
			$this->loader->add_action( 'admin_footer', 'Boldgrid_Backup_Rest_Utility', 'insert_nonce' );
		}
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
