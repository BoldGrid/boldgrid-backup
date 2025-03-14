<?php
/**
 * File: class-boldgrid-backup-admin-core.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

/**
 * Class: Boldgrid_Backup_Admin_Core
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Core {
	/**
	 * Archiver Utility class.
	 *
	 * @since 1.9.0
	 * @var Boldgrid_Backup_Admin_Archiver_Utility
	 */
	public $archiver_utility;

	/**
	 * Auto Rollback class.
	 *
	 * @since  1.5.2
	 * @var    Boldgrid_Backup_Admin_Auto_Rollback
	 */
	public $auto_rollback;

	/**
	 * An instance of Boldgrid_Backup_Admin_Dashboard
	 *
	 * @since 1.11.0
	 * @var   Boldgrid_Backup_Admin_Dashboard
	 */
	public $dashboard;

	/**
	 * Dashboard widget.
	 *
	 * @since 1.10.0
	 * @var Boldgrid_Backup_Admin_Dashboard_Widget
	 */
	public $dashboard_widget;

	/**
	 * The settings class object.
	 *
	 * @since 1.0
	 * @var Boldgrid_Backup_Admin_Settings
	 */
	public $settings;

	/**
	 * The configuration class object.
	 *
	 * @since 1.0
	 * @var Boldgrid_Backup_Admin_Config
	 */
	public $config;

	/**
	 * Plugin configs.
	 *
	 * @since  1.3.4
	 * @var    array
	 */
	public $configs;

	/**
	 * Plugin class.
	 *
	 * @since  1.13.0
	 * @var    Boldgrid\Library\Library\Plugin\Plugin
	 */
	public $plugin;

	/**
	 * Core Files class.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Core_Files
	 */
	public $core_files;

	/**
	 * Whether or not we're in ajax.
	 *
	 * When we're on the archives page and click "Backup Site Now", when
	 * this->archive_files runs, it reports doing_ajax as true.
	 *
	 * @since  1.5.2
	 * @var    bool
	 */
	public $doing_ajax;

	/**
	 * Whether or not we're doing cron.
	 *
	 * Please see $this->set_doing_cron() for further clarification.
	 *
	 * @since  1.5.1
	 * @var    bool
	 */
	public $doing_cron;

	/**
	 * Whether or not we're doing wp_cron.
	 *
	 * In WordPress' wp-cron.php DOING_CRON is defined as true. In several of
	 * our files, we define DOING_CRON as well. When we want to tell the
	 * difference between (1)wp-cron.php and (2)cron / cli, it's difficult.
	 * This property is solely to know if we're in wp-cron.php.
	 *
	 * @todo Within our plugins, DOING_CRON should be cleaned up.
	 *
	 * @since 1.6.0
	 *
	 * @var bool
	 */
	public $doing_wp_cron;

	/**
	 * Email.
	 *
	 * @since  1.5.2
	 * @var    Boldgrid_Backup_Admin_Email
	 */
	public $email;

	/**
	 * Elements.
	 *
	 * Common elements used throughout admin pages. Usually a combination of
	 * language strings.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Email
	 */
	public $elements = [];

	/**
	 * The functionality test class object.
	 *
	 * @since 1.0
	 * @var Boldgrid_Backup_Admin_Test
	 */
	public $test;

	/**
	 * The Time class object.
	 *
	 * @since 1.6.0
	 * @var Boldgrid_Backup_Admin_Time
	 */
	public $time;

	/**
	 * An instance of Boldgrid_Backup_Admin_Tools.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Tools
	 */
	public $tools;

	/**
	 * An instance of Boldgrid_Backup_Admin_Transfers.
	 *
	 * @since 1.11.0
	 * @var    Boldgrid_Backup_Admin_Transfers
	 */
	public $transfers;

	/**
	 * An instance of Boldgrid_Backup_Admin_Support.
	 *
	 * @since 1.10.1
	 * @var    Boldgrid_Backup_Admin_Support
	 */
	public $support;

	/**
	 * An instance of Boldgrid_Backup_Admin_Premium.
	 *
	 * @since 1.12.4
	 * @var    Boldgrid_Backup_Admin_Premium_Features
	 */
	public $premium_page;

	/**
	 * An instance of Boldgrid_Backup_Admin_Utility.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Utility
	 */
	public $utility;

	/**
	 * The admin notice class object.
	 *
	 * @since 1.0
	 * @var Boldgrid_Backup_Admin_Notice
	 */
	public $notice;

	/**
	 * WordPress' global pagenow.
	 *
	 * @since  1.6.0
	 * @var    string
	 */
	public $pagenow;

	/**
	 * A bool indicating at the time of archivign files whether or not the
	 * current_filter() was pre_auto_update (the action ran immediately before
	 * WordPress does an auto upgrade).
	 *
	 * @since  1.6.0
	 * @var    bool
	 */
	public $pre_auto_update = false;

	/**
	 * The admin cron class object.
	 *
	 * @since 1.0
	 * @var Boldgrid_Backup_Admin_Cron
	 */
	public $cron;

	/**
	 * Cron log class.
	 *
	 * @since 1.6.5
	 * @var Boldgrid_Backup_Admin_Cron_Log
	 */
	public $cron_log;

	/**
	 * Cron test class.
	 *
	 * @since 1.6.5
	 * @var Boldgrid_Backup_Admin_Cron_Test
	 */
	public $cron_test;

	/**
	 * The admin xhprof class object.
	 *
	 * @since 1.2
	 * @var Boldgrid_Backup_Admin_Xhprof
	 */
	public $xhprof;

	/**
	 * WP Cron class.
	 *
	 * @since  1.5.1
	 * @var    Boldgrid_Backup_Admin_WP_Cron
	 */
	public $wp_cron;

	/**
	 * An instance of the filesystem.
	 *
	 * @since  1.5.1
	 * @var    WP_Filesystem
	 */
	public $wp_filesystem;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Archive class.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Archive
	 */
	public $archive;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Archives class.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Archives
	 */
	public $archives;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Archives_All class.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Archives_All
	 */
	public $archives_all;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Archive_Actions class.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Archive_Actions
	 */
	public $archive_actions;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Archive_Browser class.
	 *
	 * @since  1.5.2
	 * @var    Boldgrid_Backup_Admin_Archive_Browser
	 */
	public $archive_browser;

	/**
	 * An instance of the Archive Log class.
	 *
	 * @since  1.5.1
	 * @var    Boldgrid_Backup_Admin_Archive_Log
	 */
	public $archive_log;

	/**
	 * An instance of the Archive Details class.
	 *
	 * @since  1.5.1
	 * @var    Boldgrid_Backup_Admin_Archive_Details
	 */
	public $archive_details;

	/**
	 * An instance of the Archive Fail class.
	 *
	 * @since  1.5.2
	 * @var    Boldgrid_Backup_Admin_Archive_Fail
	 */
	public $archive_fail;

	/**
	 * Archive filepath.
	 *
	 * This is similar to $db_dump_filepath, but holds the path to the archive instead of the .sql file.
	 * This is being added in 1.14.13 to handle the scenario of a user canceling a backup. If the
	 * user does cancel the backup, we need to delete it (which is handled in archive-fail).
	 *
	 * @since 1.14.13
	 * @var   string
	 */
	public $archive_filepath;

	/**
	 * Whether or not we're in the middle of archiving files.
	 *
	 * This is set at the beginning and end of self::archive_files().
	 *
	 * @since 1.13.4
	 * @var bool
	 */
	public $archiving_files = false;

	/**
	 * Db Dump.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Db_Dump
	 */
	public $db_dump;

	/**
	 * Database backup file path.
	 *
	 * @since 1.0
	 * @var string
	 */
	public $db_dump_filepath = '';

	/**
	 * Db Get.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Db_Dump
	 */
	public $db_get;

	/**
	 * An instance of Boldgrid_Backup_Admin_Db_Omit.
	 *
	 * @since  1.5.3
	 * @var    Boldgrid_Backup_Admin_Db_Omit
	 */
	public $db_omit;

	/**
	 * Database Restoration errro
	 *
	 * @since 1.14.0
	 * @var string
	 */
	public $db_restore_error;

	/**
	 * An instance of Boldgrid_Backup_Admin_Filelist.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Filelist
	 */
	public $filelist;

	/**
	 * Base directory for the get_filelist method.
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $filelist_basedir = null;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Folder_Exclusion class.
	 *
	 * @since 1.6.0
	 * @var   Boldgrid_Backup_Admin_Folder_Exclusion
	 */
	public $folder_exclusion;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Ftp class.
	 *
	 * @since 1.6.0
	 * @var   Boldgrid_Backup_Admin_Ftp
	 */
	public $ftp;

	/**
	 * An instance of the Boldgrid_Backup_Admin_Go_Pro class.
	 *
	 * @since 1.6.0
	 * @var   Boldgrid_Backup_Admin_Go_Pro
	 */
	public $go_pro;

	/**
	 * An instance of Boldgrid_Backup_Admin_Backup_Dir.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Backup_Dir
	 */
	public $backup_dir;

	/**
	 * A unique identifier for backups of this WordPress installation.
	 *
	 * @since 1.0.1
	 * @access private
	 * @var string
	 */
	private $backup_identifier = null;

	/**
	 * Value indicating we are in the Backup Site Now callback and the user is
	 * choosing a full backup.
	 *
	 * @since 1.6.0
	 * @var    bool
	 */
	public $is_backup_full = false;

	/**
	 * Value indicating we are in the Backup Site Now callback.
	 *
	 * @since 1.6.0
	 * @var    bool
	 */
	public $is_backup_now = false;

	/**
	 * An instance of Boldgrid_Backup_Admin_Home_Dir.
	 *
	 * @since 1.5.1
	 * @var   Boldgrid_Backup_Admin_Home_Dir
	 */
	public $home_dir;

	/**
	 * An instance of the In Progress class.
	 *
	 * @since 1.6.0
	 * @var   Boldgrid_Backup_Admin_In_Progress object.
	 */
	public $in_progress;

	/**
	 * Value indicating whether or not we're creating a backup for update
	 * protection.
	 *
	 * @since 1.6.0
	 * @var   bool
	 */
	public $is_archiving_update_protection = false;

	/**
	 * Whether or not we are in a scheduled backup (IE a cron backup).
	 *
	 * @since 1.9.0
	 * @var bool
	 */
	public $is_scheduled_backup;

	/**
	 * Common elements.
	 *
	 * @since 1.5.3
	 * @var   array
	 */
	public $lang = [];

	/**
	 * Local storage.
	 *
	 * @since  1.5.2
	 * @var    Boldgrid_Backup_Admin_Storage_Local
	 */
	public $local;

	/**
	 * Log page.
	 *
	 * @since 1.12.5
	 * @var Boldgrid_Backup_Admin_Log_Page
	 */
	public $log_page;

	/**
	 * Logger.
	 *
	 * @since 1.12.5
	 * @var Boldgrid_Backup_Admin_Log
	 */
	public $logger;

	/**
	 * Path to our config.rating-prompt.php file.
	 *
	 * @since  1.7.2
	 * @var    string
	 */
	public $rating_prompt_config;

	/**
	 * The Restore Helper class.
	 *
	 * @since  1.6.1
	 * @var    Boldgrid_Backup_Admin_Restore_Helper
	 */
	public $restore_helper;

	/**
	 * Whether or not we are in the middle of restoring an archive.
	 *
	 * @since 1.13.5
	 * @var bool
	 *
	 * @see self::archiving_files
	 */
	public $restoring_archive_file = false;

	/**
	 * The scheduler class object.
	 *
	 * @since  1.5.1
	 * @var    Boldgrid_Backup_Admin_Scheduler
	 */
	public $scheduler;

	/**
	 * The public download class object.
	 *
	 * @since  1.7.0
	 * @var    Boldgrid_Backup_Download
	 */
	public $download;

	/**
	 * An instance of the Boldgrid\Library\Library\Activity class.
	 *
	 * @since  1.7.2
	 * @var    Boldgrid\Library\Library\Activity
	 */
	public $activity;

	/**
	 * An instance of the Auto Updates class
	 *
	 * @since 1.14.0
	 * @var Boldgrid_Backup_Admin_Auto_Updates
	 */
	public $auto_updates;

	/**
	 * An Instance of the Upload class.
	 *
	 * @since 1.2.2
	 * @var Boldgrid_Backup_Admin_Upload
	 */
	public $upload;

	/**
	 * An instance of the Restore Git class.
	 *
	 * @since 1.5.1
	 * @var Boldgrid_Backup_Admin_Restore_Git
	 */
	public $restore_git;

	/**
	 * An instance of the Compressors class.
	 *
	 * @since 1.5.1
	 * @var Boldgrid_Backup_Admin_Compressors
	 */
	public $compressors;

	/**
	 * An instance of the Remote class
	 *
	 * @since 1.5.2
	 * @var Boldgrid_Backup_Admin_Remote
	 */
	public $remote;

	/**
	 * An instance of the Jobs class
	 *
	 * @since 1.5.2
	 * @var Boldgrid_Backup_Admin_Jobs
	 */
	public $jobs;

	/**
	 * An instance of the Premium class
	 *
	 * @since 1.0.0
	 * @var Boldgrid_Backup_Premium
	 */
	public $premium;

	/**
	 * An instance of the Migrate Class
	 * 
	 * @since 1.17.0
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 */
	public $migrate;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @global $wp_filesystem
	 */
	public function __construct() {
		/*
		 * Moves the definition of 'boldgrid_backup_get_core' from Boldgrid_Backup here.
		 * This should help prevent issues where this filter doesn't work in some places.
		 */
		add_filter( 'boldgrid_backup_get_core', array( $this, 'get_core' ), 10, 1 );

		WP_Filesystem();
		global $wp_filesystem;
		global $pagenow;

		$this->set_doing_cron();

		$this->doing_ajax    = is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX;
		$this->doing_wp_cron = ! empty( $_SERVER['SCRIPT_FILENAME'] ) &&
			trailingslashit( ABSPATH ) . 'wp-cron.php' === $_SERVER['SCRIPT_FILENAME'];

		$this->wp_filesystem = $wp_filesystem;

		$this->pagenow = $pagenow;

		// Instantiate Configs Array
		$this->configs = Boldgrid_Backup_Admin::get_configs();

		// Instantiate Boldgrid_Backup_Admin_Settings.
		$this->settings = new Boldgrid_Backup_Admin_Settings( $this );

		// Instantiate Boldgrid_Backup_Admin_Config.
		$this->config = new Boldgrid_Backup_Admin_Config( $this );

		// Instantiate Boldgrid_Backup_Admin_Test.
		$this->test = new Boldgrid_Backup_Admin_Test( $this );

		// Instantiate Boldgrid_Backup_Admin_Notice.
		$this->notice = new Boldgrid_Backup_Admin_Notice( $this );

		// Instantiate Boldgrid_Backup_Admin_Cron.
		$this->cron = new Boldgrid_Backup_Admin_Cron( $this );

		// Instantiate Boldgrid_Backup_Admin_Upload.
		$this->upload = new Boldgrid_Backup_Admin_Upload( $this );

		// Instantiate Boldgrid_Backup_Admin_Xhprof.
		// Starts profiling and saves a report, if enabled in the "config.local.php" file.
		$this->xhprof = new Boldgrid_Backup_Admin_Xhprof();

		$this->restore_helper = new Boldgrid_Backup_Admin_Restore_Helper();

		$this->restore_git = new Boldgrid_Backup_Admin_Restore_Git();

		$this->filelist = new Boldgrid_Backup_Admin_Filelist( $this );

		$this->backup_dir = new Boldgrid_Backup_Admin_Backup_Dir( $this );

		$this->home_dir = new Boldgrid_Backup_Admin_Home_Dir( $this );

		$this->compressors = new Boldgrid_Backup_Admin_Compressors( $this );

		$this->archive_browser = new Boldgrid_Backup_Admin_Archive_Browser( $this );

		$this->archive = new Boldgrid_Backup_Admin_Archive( $this );

		$this->archive_actions = new Boldgrid_Backup_Admin_Archive_Actions( $this );

		$this->archives = new Boldgrid_Backup_Admin_Archives( $this );

		$this->archives_all = new Boldgrid_Backup_Admin_Archives_All( $this );

		$this->archive_log = new Boldgrid_Backup_Admin_Archive_Log( $this );

		$this->archive_details = new Boldgrid_Backup_Admin_Archive_Details( $this );

		$this->archive_fail = new Boldgrid_Backup_Admin_Archive_Fail( $this );

		$this->archiver_utility = new Boldgrid_Backup_Admin_Archiver_Utility( $this );

		$this->wp_cron = new Boldgrid_Backup_Admin_WP_Cron( $this );

		$this->scheduler = new Boldgrid_Backup_Admin_Scheduler( $this );

		$this->auto_rollback = new Boldgrid_Backup_Admin_Auto_Rollback( $this );

		$this->remote = new Boldgrid_Backup_Admin_Remote( $this );

		// This was moved up the list to ensure it was defined in the Boldgrid_Backup_Admin_Jobs
		$this->folder_exclusion = new Boldgrid_Backup_Admin_Folder_Exclusion( $this );

		$this->jobs = new Boldgrid_Backup_Admin_Jobs( $this );

		$this->local = new Boldgrid_Backup_Admin_Storage_Local( $this );

		$this->email = new Boldgrid_Backup_Admin_Email( $this );

		$this->db_omit = new Boldgrid_Backup_Admin_Db_Omit( $this );

		$this->db_dump = new Boldgrid_Backup_Admin_Db_Dump( $this );

		$this->db_get = new Boldgrid_Backup_Admin_Db_get( $this );

		$this->utility = new Boldgrid_Backup_Admin_Utility();

		$this->core_files = new Boldgrid_Backup_Admin_Core_Files( $this );

		$this->in_progress = new Boldgrid_Backup_Admin_In_Progress( $this );

		$this->ftp = new Boldgrid_Backup_Admin_Ftp( $this );

		$this->go_pro = new Boldgrid_Backup_Admin_Go_Pro( $this );

		$this->tools = new Boldgrid_Backup_Admin_Tools( $this );

		$this->transfers = new Boldgrid_Backup_Admin_Transfers( $this );

		$this->support = new Boldgrid_Backup_Admin_Support( $this );

		$this->time = new Boldgrid_Backup_Admin_Time( $this );

		$this->cron_test = new Boldgrid_Backup_Admin_Cron_Test( $this );

		$this->cron_log = new Boldgrid_Backup_Admin_Cron_Log( $this );

		$this->download = new Boldgrid_Backup_Download( $this );

		$this->dashboard_widget = new Boldgrid_Backup_Admin_Dashboard_Widget( $this );

		$this->dashboard = new Boldgrid_Backup_Admin_Dashboard( $this );

		/**
		 * For backwards compatibility with plugins using previous versions of the Library, this will
		 * allow the plugins to be instantiated without using the new Factory methods.
		 */
		$this->plugin = new \Boldgrid\Library\Library\Plugin\Plugin( 'boldgrid-backup', $this->configs );

		$this->premium_page = new Boldgrid_Backup_Admin_Premium_Features( $this );

		// Instantiate Boldgrid_Backup_Admin_Auto_Updates.

		if ( Boldgrid_Backup_Admin_Utility::is_active() ) {
			$this->auto_updates = new Boldgrid_Backup_Admin_Auto_Updates();
		}

		// Ensure there is a backup identifier.
		$this->get_backup_identifier();

		$this->set_lang();

		// Log system.
		$this->logger   = new Boldgrid_Backup_Admin_Log( $this );
		$this->log_page = new Boldgrid_Backup_Admin_Log_Page( $this );

		// Need to construct class so necessary filters are added.
		if ( class_exists( '\Boldgrid\Library\Library\Ui' ) ) {
			$ui = new \Boldgrid\Library\Library\Ui();
		}

		// Setup library's Activity and RatingPrompt classes; init RatingPrompt to add necessary filters.
		$this->rating_prompt_config = BOLDGRID_BACKUP_PATH . '/includes/config/config.rating-prompt.php';
		if ( class_exists( '\Boldgrid\Library\Library\RatingPrompt' ) ) {
			new \Boldgrid\Library\Library\RatingPrompt();
		}
		if ( class_exists( '\Boldgrid\Library\Library\Activity' ) ) {
			$this->activity = new \Boldgrid\Library\Library\Activity( BOLDGRID_BACKUP_KEY );
		}

		// Instantiate the new Boldgrid_Backup_Admin_Migrate class.
		$this->migrate = new Boldgrid_Backup_Admin_Migrate( $this );
	}

	/**
	 * Get the unique identifier for backups of this WordPress installation.
	 *
	 * @since 1.0.1
	 *
	 * @return string A unique identifier for backups.
	 */
	public function get_backup_identifier() {
		// If the id was already stored, then return it.
		if ( ! empty( $this->backup_identifier ) ) {
			return $this->backup_identifier;
		}

		// Check wp_options for the id.
		$backup_identifier = get_site_option( 'boldgrid_backup_id' );

		// If the id was already stored in WP options, then save and return it.
		if ( ! empty( $backup_identifier ) ) {
			$this->backup_identifier = $backup_identifier;

			return $backup_identifier;
		}

		// Generate a new backup id.
		$admin_email = $this->config->get_admin_email();

		$unique_string = site_url() . ' <' . $admin_email . '>';

		$backup_identifier = hash( 'crc32', hash( 'sha512', $unique_string ) );

		// If something went wrong with hashing, then just use a random string to make the id.
		if ( empty( $backup_identifier ) ) {
			$random_string = '';

			for ( $i = 0; $i <= 32; $i ++ ) {
				$random_string .= chr( mt_rand( 40, 126 ) );
			}

			$backup_identifier = hash( 'crc32', $random_string );
		}

		// Save and return the id.
		$this->backup_identifier = $backup_identifier;

		update_site_option( 'boldgrid_backup_id', $backup_identifier );

		return $backup_identifier;
	}

	/**
	 * Get core.
	 *
	 * Callable via the boldgrid_backup_get_core filter.
	 *
	 * @since 1.7.2
	 *
	 * @return Boldgrid_Backup_Admin_Core object.
	 */
	public function get_core() {
		return $this;
	}

	/**
	 * Initialize the premium version of the plugin.
	 *
	 * @since 1.5.2
	 *
	 * @global string $pagenow
	 */
	public function init_premium() {
		global $pagenow;

		$premium_class = 'Boldgrid_Backup_Premium';

		/*
		 * Only initialize premium if both the plugin exists, is activated, and
		 * we have a premium key.
		 */
		if ( ! class_exists( $premium_class ) || ! $this->config->get_is_premium() ) {
			return;
		}

		/*
		 * If this version of the backup plugin is not compatible with the premium version:
		 * 1. Don't init the premium extension.
		 * 2. Display an admin notice telling the user to upgrade.
		 */
		if ( ! $this->support->is_premium_compatible() ) {
			if ( 'update-core.php' !== $pagenow ) {
				add_action( 'admin_notices', function() {
					$message = wp_kses(
						sprintf(
							// Translators: 1 The minimum version required, 2 an opening strong tag, 3 its closing strong tag, 4 an opening anchor to the updates page, 5 its closing anchor, 6: Plugin title, 7: Premium plugin title.
							__(
								'The version of the %2$s%7$s%3$s plugin you have installed requires %2$s%6$s%3$s version %1$s or higher. Don\'t worry, the fix is simple. Please go to your %4$sUpdates page%5$s and update the %2$s%6$s%3$s plugin.',
								'boldgrid-backup'
							),
							BOLDGRID_BACKUP_MIN_VERSION_FOR_PREMIUM,
							'<strong>',
							'</strong>',
							'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
							'</a>',
							BOLDGRID_BACKUP_TITLE,
							BOLDGRID_BACKUP_TITLE . ' Premium'
						),
						[
							'strong' => [],
							'a'      => [
								'href' => [],
							],
						]
					);

					$notice_markup = $this->notice->get_notice_markup(
						'notice notice-error is-dismissible',
						$message,
						BOLDGRID_BACKUP_TITLE . ' - ' . esc_html__( 'Update required', 'boldgrid-backup' )
					);

					echo $notice_markup; // phpcs:ignore
				});
			}

			return;
		}

		$this->premium = new $premium_class( $this );
		$this->premium->run();
	}

	/**
	 * Execute a system command using an array of execution functions.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Cli::call_command()
	 *
	 * @param  string $command                  A command string to be executed.
	 * @param  bool   $success                  Success or failure of the operation, passed back.
	 * @param  int    $return_var               If present, the return_var, passed back.
	 * @param  string $filepath                 An optional file path to write the output.
	 * @return string|bool Returns the command output or FALSE on error.
	 */
	public function execute_command( $command, &$success = false, &$return_var = 0, $filepath = null ) {
		// If no command was passed, then fail.
		if ( empty( $command ) ) {
			return false;
		}

		// If an output filepath is supplied, and the directory is writable, then write to file.
		if ( $filepath && $this->wp_filesystem->is_writable( dirname( $filepath ) ) ) {
			$command .= ' > ' . wp_normalize_path( $filepath );
		}

		// Disable stderr.
		if ( ! $this->test->is_windows() && false === strpos( $command, '2>/dev/null' ) ) {
			$command .= ' 2>/dev/null';
		}

		$output = Boldgrid_Backup_Admin_Cli::call_command( $command, $success, $return_var );

		return $output;
	}

	/**
	 * Add menu items.
	 *
	 * @since 1.0
	 *
	 * @global array $submenu
	 */
	public function add_menu_items() {
		global $submenu;

		$lang = [
			'backup_archive'  => esc_html__( 'Backup Archives', 'boldgrid-backup' ),
			'boldgrid_backup' => BOLDGRID_BACKUP_TITLE,
			'get_premium'     => esc_html__( 'Get Premium', 'boldgrid-bacukp' ),
			'preflight_check' => esc_html__( 'Preflight Check', 'boldgrid-backup' ),
			'settings'        => esc_html__( 'Settings', 'boldgrid-backup' ),
			'tools'           => esc_html__( 'Tools', 'boldgrid-backup' ),
			'transfers'       => esc_html__( 'Transfers', 'boldgrid-backup' ),
			'support'         => esc_html__( 'Support', 'boldgrid-backup' ),
			'premium'         => esc_html__( 'Premium Features', 'boldgrid-backup' ),
		];

		// The main slug all sub menu items are children of.
		$main_slug = 'boldgrid-backup-dashboard';

		// The capability required for these menu items to be displayed to the user.
		$capability = 'administrator';

		// Add the main menu item.
		add_menu_page(
			$lang['boldgrid_backup'],
			// This value is escaped already by Library\Plugin\Page::getUnreadMarkup
			$lang['boldgrid_backup'],
			$capability,
			$main_slug,
			[
				$this->dashboard,
				'page',
			],
			'none'
		);

		// Add our "Dashboard" page.
		add_submenu_page(
			$main_slug,
			__( 'Dashboard', 'boldgrid-backup' ),
			__( 'Dashboard', 'boldgrid-backup' ),
			$capability,
			'boldgrid-backup-dashboard',
			[
				$this->dashboard,
				'page',
			]
		);

		// Add "Backup Archive page".
		add_submenu_page(
			$main_slug,
			'BoldGrid ' . $lang['backup_archive'],
			$lang['backup_archive'],
			$capability,
			'boldgrid-backup',
			[
				$this,
				'page_archives',
			]
		);

		// Add "Transfers" page.
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['transfers'],
			$lang['transfers'],
			$capability,
			'boldgrid-backup-transfers',
			[
				$this->transfers,
				'page',
			]
		);

		// Add "Tools" page.
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['tools'],
			$lang['tools'],
			$capability,
			'boldgrid-backup-tools',
			[
				$this->tools,
				'page',
			]
		);

		/*
		 * Add "Settings", formally known as "Backup Settings".
		 *
		 * @link http://wordpress.stackexchange.com/questions/66498/add-menu-page-with-different-name-for-first-submenu-item
		 */
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['settings'],
			$lang['settings'],
			$capability,
			'boldgrid-backup-settings',
			[
				$this->settings,
				'page_backup_settings',
			]
		);

		// Add "Preflight Check" page, formally know as "Functionality Test".
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['preflight_check'],
			$lang['preflight_check'],
			$capability,
			'boldgrid-backup-test',
			[
				$this,
				'page_backup_test',
			]
		);

		// Add "Backup Archive Details" page.
		add_submenu_page(
			'boldgrid-backup',
			'BoldGrid ' . $lang['backup_archive'],
			$lang['backup_archive'],
			$capability,
			'boldgrid-backup-archive-details',
			[
				$this->archive_details,
				'render_archive',
			]
		);

		// Add "Support" page.
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['support'],
			$lang['support'],
			$capability,
			'boldgrid-backup-support',
			[
				$this->support,
				'page',
			]
		);

		// Add "Premium" page.
		add_submenu_page(
			$main_slug,
			$lang['boldgrid_backup'] . ' ' . $lang['premium'],
			// Count value is escaped already by Library\Plugin\Page::getUnreadMarkup
			$lang['premium'],
			$capability,
			'boldgrid-backup-premium-features',
			[
				$this->premium_page,
				'page',
			]
		);

		/*
		 * Add our "Get Premium" link to the navigation.
		 *
		 * Leave this as the last menu item.
		 */
		if ( ! $this->config->get_is_premium() ) {
			$menu_slug = 'boldgrid-backup-get-premium';

			add_submenu_page(
				$main_slug,
				$lang['get_premium'],
				'<span class="dashicons dashicons-dashboard"></span> <span class="get-premium">' . $lang['get_premium'] . '</span>',
				$capability,
				$menu_slug
			);

			/*
			 * Change the url (2 is key of the menu item's slug / url).
			 *
			 * The ! empty check is to ensure the submenu exists. In some cases, such as when a user
			 * is logged in as an editor, it will not exist.
			 */
			if ( ! empty( $submenu[ $main_slug ] ) ) {
				foreach ( $submenu[ $main_slug ] as &$item ) {
					if ( $menu_slug === $item[2] ) {
						$item[2] = $this->go_pro->get_premium_url( 'bgbkup-nav' );
					}
				}
			}
		}
	}

	/**
	 * Register / enqueue scripts.
	 *
	 * This method is being called during the "admin_enqueue_scripts" hook.
	 *
	 * @since 1.5.2
	 */
	public function admin_enqueue_scripts() {
		wp_register_style(
			'boldgrid-backup-admin-new-thickbox-style',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-new-thickbox-style.css',
			[],
			BOLDGRID_BACKUP_VERSION
		);

		wp_register_style(
			'boldgrid-backup-admin-hide-all',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-hide-all.css',
			[],
			BOLDGRID_BACKUP_VERSION
		);
	}

	/**
	 * Backup the WordPress database.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @return bool Status of the operation.
	 */
	private function backup_database() {
		/*
		 * If we're omitting all the tables, we can skip trying to backup the
		 * database.
		 */
		if ( $this->db_omit->is_omit_all() ) {
			$this->logger->add( 'No database tables selected to backup. A database export will not be in this backup.' );
			return true;
		}

		/*
		 * Log generic info about database.
		 *
		 * Before we begin to backup the database, let's log how big it is. While troubleshooting, it
		 * will be helpful to know how many tables we're seeing that COULD be backed up, as well as
		 * how large they are.
		 */
		$this->logger->add( 'Database info: ' . print_r( $this->db_get->prefixed_count(), 1 ) ); // phpcs:ignore

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice.
			$this->notice->functionality_fail_notice();
			return [ 'error' => esc_html__( 'Unable to create backup, functionality test failed.', 'boldgrid_backup' ) ];
		}

		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
			return [
				'error' => sprintf(
					// translators: 1: Backup directory path.
					__( 'The backup directory is not writable: %1$s.', 'boldgrid-backup' ),
					$backup_directory
				),
			];
		}

		// Create a file path for the dump file.
		$db_dump_filepath = $backup_directory . DIRECTORY_SEPARATOR . DB_NAME . '.' . date( 'Ymd-His' ) . '.sql';

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		$this->set_time_limit();

		// Create a dump of our database.
		$status = $this->db_dump->dump( $db_dump_filepath );
		if ( ! empty( $status['error'] ) ) {
			return [ 'error' => $status['error'] ];
		}

		// Ensure file is written and is over 100 bytes.
		$exists = $this->test->exists( $db_dump_filepath );
		if ( ! $exists ) {
			return [
				'error' => sprintf(
					// translators: 1: MySQL dump file path.
					__( 'mysqldump file does not exist: %1$s', 'boldgrid-backup' ),
					$db_dump_filepath
				),
			];
		}
		$dump_file_size = $this->wp_filesystem->size( $db_dump_filepath );
		if ( 100 > $dump_file_size ) {
			return [
				'error' => sprintf(
					// translators: 1: MySQL dump file path.
					__( 'mysqldump file was not written to: %1$s', 'boldgrid-backup' ),
					$db_dump_filepath
				),
			];
		}

		// Limit file permissions to the dump file.
		$wp_filesystem->chmod( $db_dump_filepath, 0600 );

		// Return success.
		return true;
	}

	/**
	 * Restore the WordPress database from a dump file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Test::run_functionality_tests()
	 * @see Boldgrid_Backup_Admin_Backup_Dir::get()
	 * @see Boldgrid_Backup_Admin_Utility::update_siteurl()
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 * @global wpdb $wpdb The WordPress database class object.
	 * @global WP_Rewrite $wp_rewrite Core class used to implement a rewrite component API.
	 *
	 * @param  string $db_dump_filepath File path to the mysql dump file.
	 * @param  string $db_prefix        The database prefix to use, if restoring and it changed.
	 * @param  bool   $db_encrypted     Is the database dump file encrypted.
	 * @return bool Status of the operation.
	 */
	private function restore_database( $db_dump_filepath, $db_prefix = null, $db_encrypted = false ) {
		// Check input.
		if ( empty( $db_dump_filepath ) ) {
			// Display an error notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The database dump file was not found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice.
			$this->notice->functionality_fail_notice();

			return false;
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Save the file path.
		$this->db_dump_filepath = $db_dump_filepath;

		// Get the WP Options for "siteurl" and "home", to restore later.
		$wp_siteurl = get_option( 'siteurl' );
		$wp_home    = get_option( 'home' );

		$this->set_time_limit();

		if ( $db_encrypted ) {
			/**
			 * If BGBP is activated, then check for encryption and decrypt the file.
			 *
			 * @since 1.12.0
			 */
			do_Action( 'boldgrid_backup_crypt_file', $db_dump_filepath, 'd' );
		}

		// Import the dump file.
		$importer = new Boldgrid_Backup_Admin_Db_Import();
		$status   = $importer->import( $db_dump_filepath );

		if ( ! empty( $status['error'] ) ) {
			$this->db_restore_error = $status['error'];
			do_action( 'boldgrid_backup_notice', $status['error'], 'notice notice-error is-dismissible' );
			return false;
		}

		// Set the database prefix, if supplied/changed.
		if ( ! empty( $db_prefix ) ) {
			// Connect to the WordPress database via $wpdb.
			global $wpdb;

			// Set the database table prefix.
			$wpdb->set_prefix( $db_prefix );
		}

		// Clear the WordPress cache.
		wp_cache_flush();

		/**
		 * In addition to flushing the cache (above), some classes may have cached option values (or other
		 * data from the database) in some other way - e.g. in class properties. That cached data cannot
		 * be flushed with wp_cache_flush.
		 *
		 * For example, the global $wp_rewrite class caches the permalink_structure option's value during
		 * its init method. Running the cache flush above will not update this class' permalink_structure
		 * property.
		 *
		 * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-rewrite.php#L1894
		 *
		 * This is important because if trying to rebuild the .htaccess file, it won't rebuild correctly
		 * based on the permalink_structure option in the database just restored.
		*/
		global $wp_rewrite;
		$wp_rewrite->init();

		// Get the restored "siteurl" and "home".
		$restored_wp_siteurl = get_option( 'siteurl' );
		$restored_wp_home    = get_option( 'home' );

		// If changed, then update the siteurl in the database.
		if ( $restored_wp_siteurl !== $wp_siteurl ) {
			$update_siteurl_success =
				Boldgrid_Backup_Admin_Utility::update_siteurl( array(
					'old_siteurl' => $restored_wp_siteurl,
					'siteurl'     => $wp_siteurl,
				) );

			if ( ! $update_siteurl_success ) {
				// Display an error notice.
				do_action(
					'boldgrid_backup_notice',
					esc_html__(
						'The WordPress siteurl has changed.  There was an issue changing it back.  You will have to fix the siteurl manually in the database, or use an override in your wp-config.php file.',
						'boldgrid-backup'
					),
					'notice notice-error is-dismissible'
				);
			}
		}

		// If changed, then restore the WP Option for "home".
		if ( $restored_wp_home !== $wp_home ) {

			// There may be a filter, so remove it.
			remove_all_filters( 'pre_update_option_home' );

			update_option( 'home', untrailingslashit( $wp_home ) );
		}

		// Return success.
		return true;
	}

	/**
	 * Get a single-dimension filelist array from a directory path.
	 *
	 * @since 1.0
	 *
	 * @param string $dirpath A directory path.
	 * @return array A single-dimension filelist array for use in this class.
	 */
	public function get_filelist( $dirpath ) {

		// If this is a node_modules folder, do not iterate through it.
		if ( false !== strpos( $dirpath, '/node_modules' ) ) {
			return [];
		}

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Validate input.
		if ( empty( $dirpath ) || ! $wp_filesystem->is_readable( $dirpath ) ) {
			return [];
		}

		// Remove any training slash in dirpath.
		$dirpath = untrailingslashit( $dirpath );

		// Mark the base directory, if not set (the first run).
		if ( empty( $this->filelist_basedir ) ) {
			$this->filelist_basedir = $dirpath;
		}

		// Get the non-recursive directory listing for the specified path.
		$dirlist = $wp_filesystem->dirlist( $dirpath, true, false );

		// Initialize $filelist.
		$filelist = [];

		/*
		 * Add empty directory.
		 *
		 * If this is an empty directory, add the directory itself to the
		 * $filelist.
		 *
		 * Previously we used Boldgrid_Backup_Admin_Compressor_Php_Zip::add_dirs
		 * to add all empty directories, but that method is no longer needed.
		 */
		if ( empty( $dirlist ) ) {
			$filelist[] = [
				$dirpath,
				str_replace( ABSPATH, '', $dirpath ),
				0,
				// Since 1.6.0, this 4th key represetnts 'type', as in a file or a directory.
				'd',
			];
		}

		// Sort the dirlist array by filename.
		uasort(
			$dirlist,
			function ( $a, $b ) {
				if ( $a['name'] < $b['name'] ) {
					return - 1;
				}

				if ( $a['name'] > $b['name'] ) {
					return 1;
				}

				return 0;
			}
		);

		// Perform conversion.
		foreach ( $dirlist as $fileinfo ) {
			// If item is a directory, then recurse, merge, and continue.
			if ( 'd' === $fileinfo['type'] ) {
				$filelist_add = $this->get_filelist( $dirpath . '/' . $fileinfo['name'] );

				$filelist = array_merge( $filelist, $filelist_add );

				continue;
			}

			// Get the file path.
			$filepath = $dirpath . '/' . $fileinfo['name'];

			// The relative path inside the ZIP file.
			$relative_path = substr( $filepath, strlen( $this->filelist_basedir ) + 1 );

			// For files, add to the filelist array.
			$filelist[] = [
				$filepath,
				$relative_path,
				$fileinfo['size'],
			];
		}

		// Return the array.
		return $filelist;
	}

	/**
	 * Get a recursive file list of the WordPress installation root directory.
	 *
	 * This is a recursive function, which uses the class property filelist_basedir.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Core::get_filelist().
	 *
	 * @param string $dirpath A directory path, defaults to ABSPATH.
	 * @return array An array of absolute file paths, relative paths, and file sizes.
	 *               Example: https://pastebin.com/QiquHdcC
	 */
	public function get_filtered_filelist( $dirpath = ABSPATH ) {

		// Validate input.
		if ( empty( $dirpath ) || ! $this->wp_filesystem->is_readable( $dirpath ) ) {
			return [];
		}

		// Get the recursive directory listing for the specified path.
		$filelist = $this->get_filelist( $dirpath );

		// If no files were found, then return an empty array.
		if ( empty( $filelist ) ) {
			return [];
		}

		// Initialize $new_filelist.
		$new_filelist = [];

		// Filter the filelist array.
		foreach ( $filelist as $fileinfo ) {

			// @todo The user needs a way to specifiy what to skip in the backups.
			$is_node_modules     = false !== strpos( $fileinfo[1], '/node_modules/' );
			$is_backup_directory = $this->backup_dir->file_in_dir( $fileinfo[1] );

			if ( $is_node_modules || $is_backup_directory ) {
				continue;
			}

			if ( ! $this->folder_exclusion->allow_file( $fileinfo[1] ) ) {
				continue;
			}

			$new_filelist[] = $fileinfo;
		}

		// Replace filelist.
		$filelist = $new_filelist;

		// Clear filelist_basedir.
		$this->filelist_basedir = null;

		// Return the filelist array.
		return $filelist;
	}

	/**
	 * Generate an new archive file path.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $extension An optional file extension.
	 * @return string|bool An archive file path, or FALSE on error.
	 */
	public function generate_archive_path( $extension = null ) {
		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Check if the backup directory is writable.
		if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		// Create a site identifier.
		$site_id = Boldgrid_Backup_Admin_Utility::create_site_id();

		$filename = sprintf(
			'boldgrid-backup-%1$s-%2$s-%3$s',
			$site_id,
			$backup_identifier,
			date( 'Ymd-His' )
		);
		$filename = sanitize_file_name( $filename );

		// Create a file path with no extension (added later).
		$filepath = $backup_directory . DIRECTORY_SEPARATOR . $filename;

		// If specified, add an extension.
		if ( ! empty( $extension ) ) {
			// Trim the input extension.
			$extension = trim( $extension, ' .' );

			$filepath .= '.' . $extension;
		}

		return $filepath;
	}

	/**
	 * Create an archive file containing the WordPress files.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Core::backup_database()
	 * @see Boldgrid_Backup_Admin_Archive::write_results_file()
	 *
	 * @param bool $save A switch to save the archive file. Default is FALSE.
	 * @param bool $dryrun An optional switch to perform a dry run test.
	 * @return array An array of archive file information.
	 */
	public function archive_files( $save = false, $dryrun = false ) {
		$this->archiving_files = true;

		$pid = getmypid();

		$log_time     = time();
		$log_filename = 'archive-' . $log_time . '.log';
		$this->logger->init( $log_filename );
		$this->logger->add( 'Backup process initialized.' );
		$this->logger->add( 'Process id: ' . $pid );

		$this->utility->bump_memory_limit( '1G' );

		$this->pre_auto_update = 'pre_auto_update' === current_filter();

		/*
		 * A scheduled backup is a backup triggered by the user's Settings > Backup Schedule.  If the user clicked
		 * "Backup Site Now" or this is a backup before an auto update occurs, this is not a scheduled backup.
		 */
		$this->is_scheduled_backup = $this->doing_cron && ! $this->pre_auto_update;

		Boldgrid_Backup_Admin_In_Progress_Data::init( array(
			'status'       => esc_html__( 'Initializing backup', 'boldgrid-backup' ),
			'log_filename' => $log_filename,
			'pid'          => $pid,
			'start_time'   => $log_time,
		) );

		/**
		 * Actions to take before any archiving begins.
		 *
		 * @since 1.5.2
		 */
		do_action( 'boldgrid_backup_archive_files_init' );

		if ( $save && ! $dryrun ) {
			$this->in_progress->set();
		}

		/*
		 * If this is a scheduled backup and no location is selected to save the
		 * backup to, abort.
		 *
		 * While we could prevent he user from setting this up in the first place,
		 * at the moment the settings page saves all settings. So, if the user
		 * wanted to change their retention settings but did not want to schedule
		 * backups, validating storage locations would be problematic.
		 */
		if ( $this->is_scheduled_backup && ! $this->remote->any_enabled() ) {
			$error = esc_html__( 'No backup locations selected! While we could create a backup archive, you have not selected where the backup archive should be saved. Please choose a storage location in your settings for where to save this backup archive.', 'boldgrid-backup' );
			$this->archive_fail->schedule_fail_email( $error );
			$this->logger->add( $error );
			return [ 'error' => $error ];
		}

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			// Display an error notice, if not already on the test page.
			if ( ! isset( $_GET['page'] ) || 'boldgrid-backup-test' !== $_GET['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
				// Display an error notice.
				$this->notice->functionality_fail_notice();
			}
			$error = __( 'Functionality tests fail.', 'boldgrid-backup' );
			$this->logger->add( $error );
			return [ 'error' => $error ];
		}

		// Close any PHP session, so that another session can open during the backup operation.
		session_write_close();

		/*
		 * Initialize return array and add "compressor" and "save" keys.
		 * Since 1.6.0, the folder include and exclude settings below are
		 * for informational purposes only. This array cannot be filtered to
		 * adjust which folders are actually included / excluded.
		 */
		$info = [
			'mode'              => 'backup',
			'dryrun'            => $dryrun,
			'compressor'        => null,
			'filesize'          => 0,
			'save'              => $save,
			'total_size'        => 0,
			'folder_include'    => $this->folder_exclusion->from_settings( 'include' ),
			'folder_exclude'    => $this->folder_exclusion->from_settings( 'exclude' ),
			'table_exclude'     => $this->db_omit->get_excluded_tables(),
			'title'             => ! empty( $_POST['backup_title'] ) ? stripslashes( $_POST['backup_title'] ) : null, // phpcs:ignore WordPress.CSRF.NonceVerification,WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
			'description'       => ! empty( $_POST['backup_description'] ) ? stripslashes( $_POST['backup_description'] ) : null, // phpcs:ignore WordPress.CSRF.NonceVerification,WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
			// Information used for the emergency restoration process.
			'ABSPATH'           => ABSPATH,
			'backup_id'         => $this->get_backup_identifier(),
			'siteurl'           => site_url(),
			'timestamp'         => time(), // @todo Is this a duplicate value? $info['lastmodunix'] is added below.
			// Environment information.
			'gateway_interface' => getenv( 'GATEWAY_INTERFACE' ),
			'http_host'         => getenv( 'HTTP_HOST' ),
			'php_sapi_name'     => php_sapi_name(),
			'php_uname'         => php_uname(),
			'php_version'       => phpversion(),
			'server_addr'       => getenv( 'SERVER_ADDR' ) ? getenv( 'SERVER_ADDR' ) : getenv( 'LOCAL_ADDR' ),
			'server_name'       => getenv( 'SERVER_NAME' ),
			'server_protocol'   => getenv( 'SERVER_PROTOCOL' ),
			'server_software'   => getenv( 'SERVER_SOFTWARE' ),
			'uid'               => getmyuid(),
			'username'          => get_current_user(),
			'encrypt_db'        => false,
		];

		// Determine how this backup was triggered.
		if ( $this->pre_auto_update ) {
			$info['trigger'] = esc_html__( 'Auto update', 'boldgrid-bakcup' );
		} elseif ( $this->doing_ajax && is_user_logged_in() ) {
			$current_user    = wp_get_current_user();
			$info['trigger'] = $current_user->user_login . ' (' . $current_user->user_email . ')';
		} elseif ( $this->doing_wp_cron ) {
			$info['trigger'] = 'WP cron';
		} elseif ( $this->doing_cron ) {
			$info['trigger'] = 'Cron';
		} else {
			$info['trigger'] = esc_html__( 'Unknown', 'boldgrid-backup' );
		}
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'trigger', $info['trigger'] );

		$this->logger->add( 'Backup triggered by: ' . $info['trigger'] );

		$info['compressor'] = $this->compressors->get();

		// If there is no available compressor, then fail.
		if ( null === $info['compressor'] ) {
			return [ 'error' => 'No available compressor.' ];
		}

		// Cleanup. Enforce retention and delete orphaned files.
		$this->enforce_retention();
		$orphan_cleanup = new Boldgrid\Backup\Admin\Orphan\Cleanup();
		$orphan_cleanup->run();

		// Prevent this script from dying.
		ignore_user_abort( true );

		// Start timer.
		$time_start = microtime( true );

		// Backup the database, if saving an archive file and not a dry run.
		if ( $save && ! $dryrun ) {
			$this->logger->add_separator();
			$this->logger->add( 'Starting dump of database...' );
			$this->logger->add_memory();

			$status = $this->backup_database();

			$this->logger->add( 'Dump of database complete! $status = ' . print_r( $status, 1 ) ); // phpcs:ignore
			$this->logger->add_memory();
			$this->logger->add_separator();

			if ( false === $status || ! empty( $status['error'] ) ) {
				$error = ! empty( $status['error'] ) ? $status['error'] : __( 'An unknown error occurred when backing up the database.', 'boldgrid-backup' );
				$this->logger->add( $error );
				return array( 'error' => $error );
			}
		}

		// Keep track of how long the site was paused for / the time to backup the database.
		$db_time_stop = microtime( true );

		// Get the file list.
		$this->logger->add( 'Retrieving file list.' );
		$filelist = $this->get_filtered_filelist( ABSPATH );

		// Initialize total_size.
		$info['total_size'] = 0;

		// If not saving, then just return info.
		if ( ! $save ) {
			foreach ( $filelist as $fileinfo ) {
				// Add the file size to the total.
				$info['total_size'] += $fileinfo[2];
			}

			return $info;
		}

		// Get the backup directory path.
		$this->logger->add( 'Retrieving backup directory.' );
		$backup_directory = $this->backup_dir->get();

		// Check if the backup directory is writable.
		if ( ! $this->wp_filesystem->is_writable( $backup_directory ) ) {
			$this->logger->add( 'Backup directory is not writable.' );
			return false;
		}

		// Add the database dump file to the beginning of file list.
		$this->logger->add( 'Adding Database to file list.' );
		if ( ! empty( $this->db_dump_filepath ) ) {
			$db_dump_size = $this->wp_filesystem->size( $this->db_dump_filepath );

			$db_file_array = [
				$this->db_dump_filepath,
				substr( $this->db_dump_filepath, strlen( $backup_directory ) + 1 ),
				$db_dump_size,
			];

			array_unshift( $filelist, $db_file_array );

			$this->logger->add(
				sprintf(
					'Database dump file added to file list: %1$s / %2$s (%3$s)',
					$this->db_dump_filepath,
					$db_dump_size,
					size_format( $db_dump_size, 2 )
				)
			);
		}

		$this->set_time_limit();

		/**
		 * Allow the filtering of our $info before generating a backup.
		 *
		 * @since 1.5.1
		 *
		 * @see Boldgrid_Backup_Admin_Compressor_Php_Zip::archive_files
		 * @see \Boldgrid\Backup\Premium\Admin\Crypt::post_dump()
		 *
		 * @param array $info Archive information.
		 */
		$info = apply_filters( 'boldgrid_backup_pre_archive_info', $info );

		$this->logger->add( 'Starting archiving of files. Chosen compressor: ' . $info['compressor'] );
		$this->logger->add_memory();

		// Determine the path to our zip file.
		$info['filepath']       = $this->generate_archive_path( 'zip' );
		$this->archive_filepath = $info['filepath'];

		Boldgrid_Backup_Admin_In_Progress_Data::set_args( array(
			'total_files_todo' => count( $filelist ),
			'filepath'         => $info['filepath'],
			'compressor'       => $info['compressor'],
		) );

		if ( Boldgrid_Backup_Admin_Filelist_Analyzer::is_enabled() ) {
			$this->logger->add_separator();
			$this->logger->add( 'Starting to analyze filelist...' );
			$this->logger->add_memory();

			$filelist_analyzer = new Boldgrid_Backup_Admin_Filelist_Analyzer( $filelist, $log_time );
			$filelist_analyzer->run();

			$this->logger->add( 'Finished analyzing filelist!' );
			$this->logger->add_memory();
		}

		/*
		 * Use the chosen compressor to build an archive.
		 * If the is no available compressor, then return an error.
		 */
		switch ( $info['compressor'] ) {
			case 'php_zip':
				$compressor = new Boldgrid_Backup_Admin_Compressor_Php_Zip( $this );
				$status     = $compressor->archive_files( $filelist, $info );
				break;
			case 'pcl_zip':
				$compressor = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this );
				$status     = $compressor->archive_files( $filelist, $info );
				break;
			case 'php_bz2':
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'b2z' );
				break;
			case 'php_zlib':
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'zlib' );
				break;
			case 'php_lzf':
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'lzf' );
				break;
			case 'system_tar':
				// Generate a new archive file path.
				$info['filepath'] = $this->generate_archive_path( 'tar.gz' );
				break;
			case 'system_zip':
				$compressor = new Boldgrid_Backup_Admin_Compressor_System_Zip( $this );
				$status     = $compressor->archive_files( $filelist, $info );
				break;
			default:
				$status = [ 'error' => 'No available compressor' ];
				break;
		}

		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'status', 'Wrapping things up...' );

		$archive_exists = ! empty( $info['filepath'] ) && $this->wp_filesystem->exists( $info['filepath'] );
		$archive_size   = ! $archive_exists ? 0 : $this->wp_filesystem->size( $info['filepath'] );

		// Add additional info to the logs.
		$this->logger->add( 'Archiving of files complete!' );
		if ( true !== $status ) {
			$this->logger->add( 'Archiving of files did not complete successfully: ' . print_r( $status, 1 ) ); // phpcs:ignore
		}
		$this->logger->add(
			sprintf(
				'Archive filepath / size: %1$s / %2$s (%3$s)',
				( empty( $info['filepath'] ) ? 'MISSING FILEPATH' : $info['filepath'] ),
				$archive_size,
				size_format( $archive_size, 2 )
			)
		);
		$this->logger->add_memory();

		$info['total_size'] += $this->filelist->get_total_size( $filelist );

		if ( true === $status && ! $archive_exists ) {
			$status = [ 'error' => 'The archive file "' . $info['filepath'] . '" was not written.' ];
		}

		if ( ! empty( $status['error'] ) ) {
			$this->logger->add( $status['error'] );
			return $status;
		}

		$info['lastmodunix'] = $this->wp_filesystem->mtime( $info['filepath'] );

		if ( $save && ! $dryrun ) {
			// Modify the archive file permissions to help protect from public access.
			$this->wp_filesystem->chmod( $info['filepath'], 0600 );

			// Add some statistics to the return.
			$info['filesize'] = $this->wp_filesystem->size( $info['filepath'] );

			// Delete the temporary database dump file.
			$this->wp_filesystem->delete( $this->db_dump_filepath, false, 'f' );
		}

		// Stop timer.
		$time_stop = microtime( true );

		// Calculate duration.
		$info['duration']    = number_format( ( $time_stop - $time_start ), 2, '.', '' );
		$info['db_duration'] = number_format( ( $db_time_stop - $time_start ), 2, '.', '' );
		$info['db_filename'] = basename( $this->db_dump_filepath );

		/**
		 * Actions to take after a backup has been created.
		 *
		 * At priority 10, we add to the jobs queue the tasks of uploading this backup to our remote
		 * storage providers. Each remote storage provider individually hooks into this action and adds
		 * a job to the queue.
		 *
		 * At priority 100, we add a job to delete the local backup file if the user does
		 * not want to keep it.
		 *
		 * At priority 200, we send an email to the user with a summary of the
		 * backup and the jobs.
		 *
		 * @since 1.5.2
		 *
		 * @param array $info {
		 *     An array of info about the backup just created.
		 *
		 *     @type string $mode         backup
		 *     @type bool   $dryrun
		 *     @type string $compressor   pcl_zip
		 *     @type int    $filesize     30992482
		 *     @type bool   $save
		 *     @type int    $total_size
		 *     @type string $filepath     C:\file.zip
		 *     @type int    $lastmodunix  1506602959
		 *     @type int    $duration     57.08
		 *     @type int    $db_duration  0.35
		 *     @type bool   $mail_success
		 * }
		 */
		do_action( 'boldgrid_backup_post_archive_files', $info );

		/*
		 * Send an email to the user, RIGHT NOW.
		 *
		 * Only send an email to the user now IF they are manually creating a backup. If this backup
		 * was created during a scheduled backup, the user will get an email from the jobs queue.
		 * Scheduled backups receive email notifications from the jobs queue because that email will
		 * not only include the standard info about the backup (which we're sending now), it will
		 * also include info about other jobs that were run (such as uploading the backup remotely).
		 */
		if ( $this->email->user_wants_notification( 'backup' ) && ! $this->is_scheduled_backup ) {
			$this->logger->add( 'Starting sending of email...' );
			Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'status', 'Sending email...' );

			$email_parts          = $this->email->post_archive_parts( $info );
			$email_body           = $email_parts['body']['main'] . $email_parts['body']['signature'];
			$info['mail_success'] = $this->email->send( $email_parts['subject'], $email_body );

			$this->logger->add( 'Sending of email complete! Status: ' . $info['mail_success'] );
		}

		// If not a dry-run test, update the last backup option and enforce retention.
		if ( ! $dryrun ) {
			// Update WP option for "boldgrid_backup_last_backup".
			update_site_option( 'boldgrid_backup_last_backup', time() );

			$this->archive_log->write( $info );

			// Enforce retention setting.
			$this->enforce_retention();

			update_option( 'boldgrid_backup_latest_backup', $info );
		}

		// Actions to take if we're creating a full site backup.
		if ( ! $dryrun && $this->archiver_utility->is_full_backup() ) {
			$this->archive->write_results_file( $info );
		}

		if ( isset( $this->activity ) ) {
			$this->activity->add( 'any_backup_created', 1, $this->rating_prompt_config );
		}

		$this->logger->add( 'Backup complete!' );
		$this->logger->add_memory();

		$this->archiving_files = false;

		Boldgrid_Backup_Admin_In_Progress_Data::set_args( array(
			'status'  => esc_html__( 'Backup complete!', 'boldgrid-backup' ),
			'success' => true,
		) );

		// Return the array of archive information.
		return $info;
	}

	/**
	 * Get information for the list of archive file(s) (in descending order by date modified).
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $download_filename A filename to match to get info.
	 * @param string $backup_directory  Specify a directory to look within for backups.
	 * @return array {
	 *  A numbered array of arrays containing the following indexes.
	 *  @type string $filepath Archive file path.
	 *  @type string $filename Archive filename.
	 *  @type string $filedate Localized file modification date.
	 *  @type int $filesize The archive file size in bytes.
	 *  @type int $lastmodunix The archive file modification time in unix seconds.
	 * }
	 */
	public function get_archive_list( $download_filename = null, $backup_directory = null ) {
		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Initialize $archive_files array.
		$archive_files = [];

		// Get the backup directory.
		if ( is_null( $backup_directory ) ) {
			$backup_directory = $this->backup_dir->get();
		}

		// If the backup directory is not configured, then return an empty array.
		if ( ! $backup_directory ) {
			return [];
		}

		// Find all backups.
		$dirlist = $wp_filesystem->dirlist( $backup_directory, false, false );

		// If no files were found, then return an empty array.
		if ( empty( $dirlist ) ) {
			return [];
		}

		// Sort the dirlist array by "lastmodunix" descending.
		uasort(
			$dirlist,
			function ( $a, $b ) {
				if ( $a['lastmodunix'] < $b['lastmodunix'] ) {
					return 1;
				}

				if ( $a['lastmodunix'] > $b['lastmodunix'] ) {
					return - 1;
				}

				return 0;
			}
		);

		// Initialize $index.
		$index = -1;

		// Filter the array.
		foreach ( $dirlist as $fileinfo ) {
			if ( $this->archive->is_site_archive( $fileinfo['name'] ) ) {
				// Increment the index.
				$index++;

				// If looking for one match, skip an iteration if not the matching filename.
				if ( ! empty( $download_filename ) && $download_filename !== $fileinfo['name'] ) {
					continue;
				}

				// Create the return array.
				// @todo Should we use the data and time from the filename, or rely on lastmodunix?
				$archive_files[ $index ] = [
					'filepath'    => $backup_directory . '/' . $fileinfo['name'],
					'filename'    => $fileinfo['name'],
					'filedate'    => get_date_from_gmt(
						date( 'Y-m-d H:i:s', $fileinfo['lastmodunix'] ), 'n/j/Y g:i A'
					),
					'filesize'    => $fileinfo['size'],
					'lastmodunix' => $fileinfo['lastmodunix'],
				];

				// If looking for info on one file and we found the match, then break the loop.
				if ( ! empty( $download_filename ) ) {
					break;
				}
			}
		}

		// Return the array.
		return $archive_files;
	}

	/**
	 * Delete an archive file.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Whether or not the archive file was deleted.
	 */
	public function delete_archive_file() {

		// If a deletion was not requested, then abort.
		if ( empty( $_POST['delete_now'] ) ) {
			return false;
		}

		// Initialize $delete_ok.
		$delete_ok = true;

		// Verify nonce, or die.
		check_admin_referer( 'archive_auth', 'archive_auth' );

		// Validate archive_key.
		if ( isset( $_POST['archive_key'] ) && is_numeric( $_POST['archive_key'] ) ) {
			$archive_key = (int) $_POST['archive_key'];
		} else {
			$delete_ok = false;

			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			$archive_key = null;
		}

		// Validate archive_filename.
		if ( ! empty( $_POST['archive_filename'] ) ) {
			$archive_filename = sanitize_file_name( $_POST['archive_filename'] );
		} else {
			// Fail with a notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// If there are errors, then abort.
		if ( ! $delete_ok ) {
			return false;
		}

		// Get archive list.
		$archives = $this->get_archive_list( $archive_filename );

		// If no files were found, then show a notice.
		if ( empty( $archives ) ) {
			// Fail with a notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'No archive files were found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Locate the filename by key number.
		$filename = (
			! empty( $archives[ $archive_key ]['filename'] ) ?
			$archives[ $archive_key ]['filename'] : null
		);

		// Verify specified filename.
		if ( $archive_filename !== $filename ) {
			// Fail with a notice.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);

			return false;
		}

		// Get the file path to delete.
		$filepath = (
			! empty( $archives[ $archive_key ]['filepath'] ) ?
			$archives[ $archive_key ]['filepath'] : null
		);

		$delete_ok = $this->archive->delete( $filepath );

		// Display notice of deletion status.
		if ( ! $delete_ok ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Error deleting the selected archive file.', 'boldgrid-backup' ),
				'notice notice-error is-dismissible'
			);
		}

		/**
		 * Take action after a user deletes a backup.
		 *
		 * @since 1.5.3
		 *
		 * @param string $filepath
		 * @param bool   $delete_ok
		 */
		do_action( 'boldgrid_backup_user_deleted_backup', $filepath, $delete_ok );

		// Return deletion status.
		return $delete_ok;
	}

	/**
	 * Get the newest database dump file path from a restored archive.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param  string $filepath The full filepath to the file .zip archive.
	 * @return string File path to the database dump file.
	 */
	public function get_dump_file( $filepath ) {

		if ( empty( $filepath ) || ! $this->wp_filesystem->exists( $filepath ) ) {
			return '';
		}

		/*
		 * Get the sql file to restore.
		 *
		 * These few lines below are new to the get_dump_file method.
		 * Historically we searched the ABSPATH for a filename matching
		 * ########-######.sql and returned the first one we found. Well, what
		 * if there were more than one matching file, which .sql file do we
		 * restore?
		 *
		 * $pcl_zip->get_sqls Searches the zip file and finds a list of all
		 * .sql files that match the pattern we're looking for and returns them.
		 * We should only get 1 back, and that is the appropriate dump file.
		 *
		 * These few lines should do the trick. If not, the original code is
		 * still in place to take the initial approach and find a dump file to
		 * restore (the first applicable one it can find).
		 */
		$pcl_zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this );
		$sqls    = $pcl_zip->get_sqls( $filepath );
		if ( 1 === count( $sqls ) ) {
			return ABSPATH . $sqls[0];
		}

		// Initialize $db_dump_filepath.
		$db_dump_filepath = '';

		// Find all backups.
		$dirlist = $this->wp_filesystem->dirlist( ABSPATH, false, false );

		// If no files were found, then return an empty array.
		if ( empty( $dirlist ) ) {
			return '';
		}

		// Sort the dirlist array by "name" descending.
		uasort(
			$dirlist,
			function ( $a, $b ) {
				if ( $a['name'] < $b['name'] ) {
					return 1;
				}

				if ( $a['name'] > $b['name'] ) {
					return - 1;
				}

				return 0;
			}
		);

		/*
		 * Find the first occurrence of a MySQL dump file.
		 * Format: "*.########-######.sql".
		 * An example filename: joec_wrdp2.20160919-162431.sql".
		 */
		foreach ( $dirlist as $fileinfo ) {
			if ( 1 === preg_match( '/\.[\d]+-[\d]+\.sql$/', $fileinfo['name'] ) ) {
				$db_dump_filepath = ABSPATH . $fileinfo['name'];
				break;
			}
		}

		// Return the array.
		return $db_dump_filepath;
	}

	/**
	 * Restore from a specified archive file.
	 *
	 * @since 1.0
	 *
	 * @see https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 *
	 * @param  bool  $dryrun An optional switch to perform a dry run test.
	 * @param  array $args {
	 *     An optional array of args.
	 *
	 *     @type int    $archive_key      An archive key.
	 *     @type string $archive_filename An archive filename.
	 * }
	 * @return array An array of archive file information.
	 */
	public function restore_archive_file( $dryrun = false, array $args = [] ) {
		$this->restoring_archive_file = true;

		$this->logger->init( 'restore-' . time() . '.log' );
		$this->logger->add( 'Restoration process initialized.' );
		$this->logger->add_memory();

		// Using pcl_zip (ZipArchive unavailable), a 400MB+ zip used over 500MB+ of memory to restore.
		Boldgrid_Backup_Admin_Utility::bump_memory_limit( '1G' );

		$restore_ok = true;

		/*
		 * This is a generic method to restore an archive. Do not assume the request to restore is coming
		 * from a user directly via $_POST.
		 *
		 * Refer to check_ajax_referer usage below to help protect ajax requests.
		 */
		$is_post_restore = isset( $_POST['action'] ) && 'boldgrid_backup_restore_archive' === $_POST['action']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

		// If a restoration was not requested, then abort.
		if ( empty( $_POST['restore_now'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$error_message = esc_html__( 'Invalid restore_now value.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		if ( $is_post_restore && ! check_ajax_referer( 'boldgrid_backup_restore_archive', 'archive_auth', false ) ) {
			$error_message = esc_html__( 'Invalid nonce.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		// Check if functional.
		if ( ! $this->test->run_functionality_tests() ) {
			$error_message = esc_html__( 'Functionality tests fail.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		/*
		 * Get our archive key.
		 *
		 * It can be passed in via $args or $_POST.
		 */
		$archive_key = false;
		if ( isset( $args['archive_key'] ) ) {
			$archive_key = (int) $args['archive_key'];
		} elseif ( isset( $_POST['archive_key'] ) && is_numeric( $_POST['archive_key'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$archive_key = (int) $_POST['archive_key'];
		} else {
			$error_message = esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		/*
		 * Get our archive filename.
		 *
		 * It can be passed in via $args or $_POST.
		 */
		$archive_filename = false;
		if ( ! empty( $args['archive_filename'] ) ) {
			$archive_filename = sanitize_file_name( $args['archive_filename'] );
		} elseif ( ! empty( $_POST['archive_filename'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$archive_filename = sanitize_file_name( $_POST['archive_filename'] );
		} else {
			$error_message = esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		// Close any PHP session, so that another session can open during this restore operation.
		session_write_close();

		$archives = $this->get_archive_list( $archive_filename );
		if ( empty( $archives ) ) {
			$error_message = esc_html__( 'No archive files were found.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		$filename = ! empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null;

		if ( $archive_filename !== $filename ) {
			$error_message = esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		$filepath = ! empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null;

		if ( ! empty( $filepath ) && $this->wp_filesystem->exists( $filepath ) ) {
			$filesize = $this->wp_filesystem->size( $filepath );
		} else {
			$error_message = esc_html__( 'The selected archive file is empty.', 'boldgrid-backup' );
			$this->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		// Populate $info.
		$info = [
			'mode'        => 'restore',
			'dryrun'      => $dryrun,
			'filename'    => $archive_filename,
			'filepath'    => $filepath,
			'filesize'    => $filesize,
			'archive_key' => $archive_key,
			'restore_ok'  => $restore_ok,
		];
		$this->logger->add( 'Restore info: ' . print_r( $info, 1 ) ); // phpcs:ignore

		// Prevent this script from dying.
		ignore_user_abort( true );

		$this->set_time_limit();

		/**
		 * Action to take before restoring an archive.
		 *
		 * @since 1.5.1
		 *
		 * @param array $info
		 */
		do_action( 'boldgrid_backup_pre_restore', $info );

		/*
		 * Attempt to fix any permissions related issues before the restoration begins. If we're
		 * unable to, the restoration may not continue.
		 */
		if ( class_exists( 'ZipArchive' ) ) {
			if ( ! $this->restore_helper->set_writable_permissions( $info['filepath'] ) ) {
				$error_message = $this->restore_helper->get_last_error();
				$this->logger->add( $error_message );
				return [ 'error' => $error_message ];
			}
		} else {
			$this->logger->add( 'ZipArchive not available. Unable to set_writable_permissions. Trying restore anyways...' );
		}

		$this->logger->add( 'Unzipping archive... filepath / ABSPATH: ' . $info['filepath'] . ' / ' . ABSPATH );
		$this->logger->add_memory();
		$unzip_status = ! $dryrun ? unzip_file( $info['filepath'], ABSPATH ) : null;
		$this->logger->add( 'Unzip complete! Status: ' . print_r( $unzip_status, 1 ) ); // phpcs:ignore
		$this->logger->add_memory();

		if ( is_wp_error( $unzip_status ) ) {
			$error = false;

			/**
			 * Take action when a restoration fails.
			 *
			 * Those actions may return a custom error message, such as:
			 * "Your restoration failed, but we did XYZ. Please try again".
			 *
			 * @param WP_Error $unzip_status
			 */
			$error = apply_filters( 'boldgrid_backup_restore_fail', $unzip_status );

			if ( empty( $error ) ) {
				$message = $unzip_status->get_error_message();
				$data    = $unzip_status->get_error_data();
				$error   = sprintf( '%1$s%2$s', $message, is_string( $data ) && ! empty( $data ) ? ': ' . $data : '' );
			}

			return [ 'error' => $error ];
		}

		/**
		 * Action to take after restoring an archive.
		 *
		 * @since 1.5.1
		 *
		 * @param array $info
		 */
		do_action( 'boldgrid_backup_post_restore', $info );

		/*
		 * Restore database.
		 *
		 * As of 1.5.4, we are checking to see if the backup archive contains a
		 * database dump before running the below conditional. Not all archives
		 * will contain a database dump, so we may be able to skip this step.
		 */
		$db_dump_filepath = $this->get_dump_file( $filepath );
		$this->logger->add( 'Attempting database restoration... $db_dump_filepath = ' . $db_dump_filepath );
		$this->logger->add_memory();
		if ( ! $dryrun && ! empty( $db_dump_filepath ) ) {
			$db_prefix = null;

			// Get the database table prefix from the new "wp-config.php" file, if exists.
			if ( $this->wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
				$wpcfg_contents = $this->wp_filesystem->get_contents( ABSPATH . 'wp-config.php' );
			}

			if ( ! empty( $wpcfg_contents ) ) {
				preg_match( '#\$table_prefix.*?=.*?' . "'" . '(.*?)' . "'" . ';#', $wpcfg_contents, $matches );

				if ( ! empty( $matches[1] ) ) {
					$db_prefix = $matches[1];
				}
			}

			// Determine if the dump file is encrypted.
			$this->archive->init( $filepath );
			$db_encrypted = $this->archive->get_attribute( 'encrypt_db' );

			// Restore the database and then delete the dump.
			$restore_ok = $this->restore_database( $db_dump_filepath, $db_prefix, $db_encrypted );
			$this->wp_filesystem->delete( $db_dump_filepath, false, 'f' );

			// Display notice of deletion status.
			if ( ! $restore_ok ) {
				$error_message = $this->db_restore_error ? $this->db_restore_error : esc_html__( 'Could not restore database.', 'boldgrid-backup' );
				$this->logger->add( $error_message );
				return [ 'error' => $error_message ];
			}
		}
		$this->logger->add( 'Database restoration complete.' );
		$this->logger->add_memory();

		// Clear rollback information and restoration cron jobs that may be present.
		$this->auto_rollback->cancel();

		// Get settings.
		$settings = $this->settings->get_settings();

		// If enabled, send email notification for restoration completed.
		if ( ! empty( $settings['notifications']['restore'] ) ) {
			$this->logger->add( 'Sending "restoration complete" email notification...' );

			// Include the mail template.
			include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-mail-restore.php';

			// Send the notification.
			// Parameters come from the included mail template file.
			$info['mail_success'] = $this->email->send( $subject, $body );

			$this->logger->add( 'Email sent. Status: ' . ( empty( $info['mail_success'] ) ? 'Fail' : 'Success' ) );
		}

		// Update status.
		$info['restore_ok'] = $restore_ok;

		// Check backup directory.
		$info['backup_directory_set'] = $this->backup_dir->get();

		$this->logger->add( 'Restoration complete!' );

		$this->restoring_archive_file = false;

		// Return info array.
		return $info;
	}

	/**
	 * Menu callback to display the Backup home page.
	 *
	 * @since 1.0
	 *
	 * @see    Boldgrid_Backup_Admin_Upload::upload_archive_file().
	 * @global string $pagenow
	 *
	 * @return null
	 */
	public function page_archives() {
		global $pagenow;

		// Thickbox used for Backup Site Now modal.
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );

		wp_enqueue_style( 'bglib-ui-css' );

		// Run the functionality tests.
		$is_functional = $this->test->run_functionality_tests();

		// If tests fail, then show an admin notice and abort.
		if ( ! $is_functional ) {
			$this->notice->functionality_fail_notice();

			return;
		}

		$this->auto_rollback->enqueue_home_scripts();
		$this->auto_rollback->enqueue_backup_scripts();
		$this->archive_actions->enqueue_scripts();

		$this->folder_exclusion->enqueue_scripts();
		$this->db_omit->enqueue_scripts();

		// Get archive list.
		$archives = $this->get_archive_list();

		// Get the archives file count.
		$archives_count = count( $archives );

		// Get the total size for all archives.
		$archives_size = 0;

		foreach ( $archives as $archive ) {
			$archives_size += $archive['filesize'];
		}

		// Get backup identifier.
		$backup_identifier = $this->get_backup_identifier();

		$settings = $this->settings->get_settings();

		// If the directory path is not in the settings, then add it for the form.
		if ( empty( $settings['backup_directory'] ) ) {
			$settings['backup_directory'] = $this->backup_dir->get();
		}

		$table = $this->archives->get_table();

		// Include the home page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-home.php';
	}

	/**
	 * Callback function for creating a backup archive file now via AJAX.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Core::archive_files()
	 */
	public function boldgrid_backup_now_callback() {

		// Verify nonce.
		if ( ! isset( $_POST['backup_auth'] ) || 1 !== check_ajax_referer( 'boldgrid_backup_now', 'backup_auth', false ) ) {
			$this->notice->add_user_notice(
				'<p>' . esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) . '</p>',
				'error'
			);
			wp_die();
		}

		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$this->notice->add_user_notice(
				'<p>' . esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ) . '</p>',
				'error'
			);
			wp_die();
		}

		$this->is_backup_now = true;

		$is_all_files         = isset( $_POST['folder_exclusion_type'] ) && 'full' === $_POST['folder_exclusion_type'];
		$is_all_tables        = isset( $_POST['table_inclusion_type'] ) && 'full' === $_POST['table_inclusion_type'];
		$this->is_backup_full = $is_all_files && $is_all_tables;

		$this->is_archiving_update_protection = ! empty( $_POST['is_updating'] ) &&
			'true' === $_POST['is_updating'];

		$archiver = new Boldgrid_Backup_Archiver();
		$archiver->run();

		$archive_info = $archiver->get_info();

		/*
		 * If there were any errors encountered during the backup, save them to the In Progress data.
		 *
		 * A "process error" is when the archive_files() method successfully returns info, and it includes
		 * an error.
		 */
		if ( ! empty( $archive_info['error'] ) ) {
			Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'process_error', $archive_info['error'] );
			Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'success', false );
		}

		if ( $this->is_archiving_update_protection ) {
			update_site_option( 'boldgrid_backup_pending_rollback', $archive_info );
		}

		/*
		 * Finish.
		 *
		 * Normally we'd give the user a notice that the backup has been completed. However, since
		 * 1.11.2, we are no longer waiting for this ajax call to complete. The "in progress" bar
		 * will give the user any updates they need.
		 */
		wp_send_json_success();
	}

	/**
	 * Callback function for downloading an archive file via AJAX.
	 *
	 * This callback function should only be called if the WP_Filesystem method is "direct", or
	 * a message should be displayed with the path to download using an alternate method.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_File::send_file()
	 */
	public function download_archive_file_callback() {
		$log = new Boldgrid_Backup_Admin_Log( $this );
		$log->init( 'backup-download.log' );
		$log->add_separator();
		$log->add( 'Attempting ajax download...' );

		// Verify nonce, or die.
		check_ajax_referer( 'archive_auth', 'wpnonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$error = __( 'Security violation (not authorized).', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		// Validate download_key.
		if ( isset( $_POST['download_key'] ) && is_numeric( $_POST['download_key'] ) ) {
			$download_key = (int) $_POST['download_key'];
		} else {
			$error = __( 'INVALID DOWNLOAD KEY', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		// Validate download_filename.
		if ( ! empty( $_POST['download_filename'] ) ) {
			$download_filename = sanitize_file_name( $_POST['download_filename'] );
		} else {
			$error = __( 'INVALID DOWNLOAD FILENAME', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		// Get the current wp_filesystem access method.
		$access_type = get_filesystem_method();

		// Check WP_Filesystem method; ensure it is "direct".
		if ( 'direct' !== $access_type ) {
			$error = __( 'WP_Filesystem method is not "direct"', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		// Get archive list.
		$archives = $this->get_archive_list( $download_filename );

		// If no files were found, then abort.
		if ( empty( $archives ) ) {
			$error = __( 'NO BACKUP ARCHIVES FOUND', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		// Locate the filename by key number.
		$filename = (
			! empty( $archives[ $download_key ]['filename'] ) ?
			$archives[ $download_key ]['filename'] : null
		);

		// Verify filename.
		if ( $download_filename !== $filename ) {
			$error = __( 'FILE NOT FOUND', 'boldgrid-backup' );
			echo esc_html( $error );
			$log->add( $error );
			wp_die();
		}

		$filepath = $archives[ $download_key ]['filepath'];

		$filesize = $archives[ $download_key ]['filesize'];

		if ( isset( $this->activity ) ) {
			$this->activity->add( 'download_to_local_machine', 1, $this->rating_prompt_config );
		}

		// Send the file and die nicely.
		$log->add( 'Request validated successfully. Now on to sending the file...' );
		Boldgrid_Backup_File::send_file( $filepath, $filesize );
	}

	/**
	 * Menu callback to display the Backup functionality test page.
	 *
	 * @since 1.0
	 *
	 * @global string $wp_version The WordPress version string.
	 * @global wpdb $wpdb The WordPress database class object.
	 */
	public function page_backup_test() {
		// Perform functionality tests.
		$is_functional = $this->test->run_functionality_tests();

		// Get the user home directory.
		$home_dir = $this->config->get_home_directory();

		// Get the mode of the directory.
		$home_dir_mode = $this->config->get_home_mode();

		// Check if home directory is writable.
		$home_dir_writable = $this->test->is_homedir_writable();

		// Get the backup directory path.
		$backup_directory = $this->backup_dir->get();

		$possible_backup_dirs = $this->backup_dir->get_possible_dirs();

		// Get the WordPress version.
		global $wp_version;

		// Connect to the WordPress database via $wpdb.
		global $wpdb;

		// Get the database size.
		$db_size = $this->test->get_database_size();

		// Get the database character set.
		$db_charset = $wpdb->charset;

		// Get the database collation.
		$db_collate = $wpdb->collate;

		$disk_space = $this->test->get_disk_space();

		/*
		 * Cron time zone testing.
		 *
		 * This set of code may modify cron jobs. Be sure to run before we get the cron jobs below
		 * so that we give the user accurate info about which cron jobs are set.
		 */
		if ( ! empty( $_POST['cron_timezone_test'] ) && check_admin_referer( 'cron_timezone_test' ) ) { // Input var okay.
			$this->cron_test->setup();
		} elseif ( ! $this->cron_test->is_running() ) {
			$this->cron_test->clean_up();
		}

		// Get our crons and ready them for display.
		$our_crons = $this->cron->get_our_crons();
		foreach ( $our_crons as &$cron ) {
			$cron = esc_html( $cron );
		}
		$our_wp_crons = $this->wp_cron->get_our_crons();
		foreach ( $our_wp_crons as &$cron ) {
			$cron = esc_html( $cron );
		}

		$cli_support = $this->test->get_cli_support();

		// Enqueue CSS for the test page.
		wp_enqueue_style(
			'boldgrid-backup-admin-test',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-test.css',
			[],
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		$settings = $this->settings->get_settings();
		wp_enqueue_style( 'boldgrid-backup-admin-new-thickbox-style' );
		wp_enqueue_style( 'bglib-ui-css' );

		$this->auto_rollback->enqueue_home_scripts();
		$this->auto_rollback->enqueue_backup_scripts();
		$this->archive_actions->enqueue_scripts();

		$this->folder_exclusion->enqueue_scripts();
		$this->db_omit->enqueue_scripts();

		$in_modal = true;
		$modal    = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-backup-modal.php';
		$in_modal = false;

		echo '
		<div class="wrap">
			<div id="bglib-page-container" class="bgbkup-page-container">
				<div id="bglib-page-top">
					<div id="bglib-page-header" class="bglib-has-logo">
						<h1>' . esc_html__( 'Total Upkeep Preflight Check', 'boldgrid-backup' ) . '</h1>
						<div class="page-title-actions">
						<a href="#TB_inline?width=800&amp;height=600&amp;inlineId=backup_now_content" class="thickbox page-title-action page-title-action-primary">' .
							esc_html__( 'Backup Site Now', 'boldgrid-backup' ) . '
						</a>
						<a class="page-title-action add-new">' . esc_html__( 'Upload Backup', 'boldgrid-backup' ) . '</a>
					</div>
					</div>
				</div>
				<div id="bglib-page-content">
					<div class="wp-header-end"></div>';
		echo $modal; //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-test.php';
		echo '
				</div>
			</div>
		</div>';
	}

	/**
	 * Set doing_cron.
	 *
	 * $this->doing_cron specifies whether or not we're doing cron.
	 *
	 * When we're generating a backup, both via cron and wpcron, doing_cron is true.
	 *
	 * @uses $_GET['doing_wp_cron'] The timestamp the cron has been triggered. Please see:
	 * @link https://github.com/WordPress/WordPress/blob/5.1.1/wp-cron.php#L84-L96
	 * @link https://github.com/WordPress/WordPress/blob/5.1.1/wp-includes/cron.php#L635-L639
	 *
	 * @since  1.9.2
	 */
	public function set_doing_cron() {
		$this->doing_cron = ( defined( 'DOING_CRON' ) && DOING_CRON ) || isset( $_GET['doing_wp_cron'] ); // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
	}

	/**
	 * Set lang strings.
	 *
	 * @since 1.5.3
	 */
	public function set_lang() {
		/*
		 * The "want_to" string is a generic "why you should upgrade" string used for general
		 * purposes. For example, it's currently used at the bottom of the backups page.
		 */
		$this->lang = [
			// Mine count, total number of backups.
			'All'                       => esc_html__( 'All', 'boldgrid-backup' ),
			'backup_created'            => esc_html__( 'Backup created successfully!', 'boldgrid-backup' ),
			'Checking_credentials'      => esc_html__( 'Checking credentials', 'boldgrid-backup' ),
			'checkmark'                 => '&#10003;',
			'get_support'               => wp_kses(
				sprintf(
					// translators: 1 The opening anchor tag to the support tab, 2 its closing anchor tag.
					esc_html__( 'Please try again. If you continue to experience problems, please %1$scontact us for additional support%2$s.', 'boldgrid-backup' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=boldgrid-backup-support' ) ) . '">',
					'</a>'
				),
				[ 'a' => [ 'href' => [] ] ]
			),
			'icon_success'              => '<span class="dashicons dashicons-yes green"></span> ',
			'icon_warning'              => '<span class="dashicons dashicons-warning yellow"></span> ',
			'heading_update_protection' => BOLDGRID_BACKUP_TITLE . ' - ' . esc_html__( 'Update Protection', 'boldgrid-backup' ),
			// Mine count, number of backups on remote storage providers.
			'Remote'                    => esc_html__( 'Remote', 'boldgrid-backup' ),
			'spinner'                   => '<span class="spinner"></span>',
			'spinner_inline'            => '<span class="spinner inline"></span>',

			/*
			 * The "want_to" string is a generic "why you should upgrade" string used for general
			 * purposes. For example, it's currently used at the bottom of the backups page.
			 */
			'want_to'                   => sprintf(
				// translators: 1 Markup showing a "Google Drive" logo, 2 Markup showing an "Amazon S3" logo.
				__( 'Catastrophic data loss can happen at any time. Storing your archives in multiple secure locations will keep your website data safe and put your mind at ease. Upgrade to %3$s to enable automated remote backups to %1$s and %2$s', 'boldgrid-backup' ),
				'<span class="bgbkup-remote-logo bgbkup-gdrive-logo" title="Google Drive"></span>',
				'<span class="bgbkup-remote-logo amazon-s3-logo" title="Amazon S3"></span>',
				BOLDGRID_BACKUP_TITLE . ' Premium'
			),
			// Mine count, number of backups on local web server.
			'Web_Server'                => esc_html__( 'Web Server', 'boldgrid-backup' ),
		];

		$this->elements = [
			'update_protection_activated' => sprintf( '%1$s %2$s', $this->lang['icon_success'], esc_html__( 'Update protection activated!', 'boldgrid-backup' ) ),
			// Use on long loading pages. Javascript will remove this on page load.
			'long_checking_creds'         => sprintf( '<div class="bgbu-remove-load">%1$s %2$s</div>', $this->lang['Checking_credentials'], $this->lang['spinner_inline'] ),
		];
	}

	/**
	 * Set the PHP timeout limit to at least 15 minutes.
	 *
	 * Various places within this class use to set the timeout limit to 300 seconds. This timeout
	 * limit has been increased to 900 seconds and moved into its own method.
	 *
	 * @since 1.3.5
	 *
	 * @param int $time_limit Limit in seconds.
	 */
	public function set_time_limit( $time_limit = 900 ) {
		$max_execution_time = ini_get( 'max_execution_time' );

		set_time_limit( ( $max_execution_time > $time_limit ? $max_execution_time : $time_limit ) );
	}

	/**
	 * Handle ajax request to restore a file.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_restore() {
		$error        = esc_html__( 'Unable to restore backup: ', 'boldgrid-backup' );
		$redirect_url = esc_url( admin_url( 'admin.php?page=boldgrid-backup' ) );

		// Validate user role.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$this->notice->add_user_notice(
				'<p>' . $error . esc_html__( 'Security violation (not authorized).', 'boldgrid-backup' ) . '</p>',
				'error'
			);
			wp_send_json_error();
		}

		// Validate nonce.
		if ( ! check_ajax_referer( 'boldgrid_backup_restore_archive', 'archive_auth', false ) ) {
				$this->notice->add_user_notice(
					'<p>' . $error . esc_html__( 'Security violation (invalid nonce).', 'boldgrid-backup' ) . '</p>',
					'error'
				);
			wp_send_json_error();
		}

		// Do the actual restoration.
		$restorer = new Boldgrid_Backup_Restorer();
		$restorer->run();

		$archive_info = $restorer->get_info();

		/*
		 * Generate success message and add as a user notice.
		 *
		 * Historically our success / fail message was taken from this file:
		 * admin/partials/boldgrid-backup-admin-backup.php'
		 *
		 * However, we cannot guarantee:
		 * # What is in that file right now because we could have restored a
		 *   backup from version 1.5 or 1.6.
		 * # What the file is doing (either returning info or echoing it).
		 */
		$is_success = ! empty( $archive_info ) && empty( $archive_info['error'] );
		if ( $is_success ) {
			$message = [
				'message' => esc_html__( 'The selected archive file has been successfully restored.', 'boldgrid-backup' ),
				'class'   => 'notice notice-success is-dismissible',
				'header'  => BOLDGRID_BACKUP_TITLE . ' - ' . esc_html__( 'Restoration complete' ),
			];
		} else {
			$message = [
				'message' => ! empty( $archive_info['error'] ) ? $archive_info['error'] : esc_html__( 'Unknown error when attempting to restore archive.', 'bolcgrid-backup' ),
				'class'   => 'notice notice-error is-dismissible',
				'header'  => BOLDGRID_BACKUP_TITLE . ' - ' . esc_html__( 'Restoration failed' ),
			];
		}
		$this->notice->add_user_notice( $message['message'], $message['class'], $message['header'] );

		wp_send_json_success( [ 'redirect_url' => $redirect_url ] );
	}

	/**
	 * Creating a backup archive file now, before an auto-update occurs.
	 *
	 * This method is hooked into the pre_auto_update action, which fires
	 * immediately prior to an auto-update.
	 *
	 * @since 1.0
	 *
	 * @link https://developer.wordpress.org/reference/hooks/pre_auto_update/
	 * @see Boldgrid_Backup_Admin_Core::archive_files()
	 *
	 * @param string $type The type of update being checked: 'core', 'theme', 'plugin', or 'translation'.
	 * @return null
	 */
	public function boldgrid_backup_now_auto( $type ) {
		// Get backup settings.
		$settings = $this->settings->get_settings();
		// Abort if auto-backup is not enabled.
		if ( empty( $settings['auto_backup'] ) ) {
			return;
		}

		// Get the last backup time (unix seconds).
		$last_backup_time = get_option( 'boldgrid_backup_last_backup' );

		// If the last backup was done in the last hour, then abort.
		if ( $last_backup_time && ( time() - $last_backup_time ) <= HOUR_IN_SECONDS ) {
			return;
		}

		// Perform the backup operation.
		$archiver = new Boldgrid_Backup_Archiver();
		$archiver->run();
	}

	/**
	 * Enforce backup archive retention setting.
	 *
	 * @since 1.0
	 *
	 * @see Boldgrid_Backup_Admin_Settings::get_settings()
	 *
	 * @return null
	 */
	public function enforce_retention() {
		$logger = new Boldgrid_Backup_Admin_Log( $this );
		$logger->init( 'retention-local.log' );
		$logger->add( 'Beginning core::enforce_retention...' );

		$settings = $this->settings->get_settings();
		$logger->add( 'Retention count: ' . $settings['retention_count'] );

		$archives = $this->get_archive_list();
		$logger->add( 'Number of archives found: ' . count( $archives ) );

		// Remove from the list of archives any that have been flagged as being protected.
		$protected_count = 0;
		foreach ( $archives as $key => $archive ) {
			$this->archive->init( $archive['filepath'] );
			if ( '1' === $this->archive->get_attribute( 'protect' ) ) {
				unset( $archives[ $key ] );
				$protected_count++;
			}
		}
		$logger->add( 'Number of protected archives found: ' . $protected_count );
		$archives = array_values( $archives );

		// Get the archives file count.
		$archives_count = count( $archives );

		// If the archive count is not beyond the set retention count, then return.
		if ( empty( $settings['retention_count'] ) || $archives_count <= $settings['retention_count'] ) {
			$logger->add( 'No backups to delete at this time due to retention settings.' );
			return;
		}

		// Initialize $counter.
		$counter = $archives_count - 1;

		// Delete old backups.
		while ( $archives_count > $settings['retention_count'] ) {
			// Get the file path to delete.
			$filepath = (
				! empty( $archives[ $counter ]['filepath'] ) ?
				$archives[ $counter ]['filepath'] : null
			);

			// Delete the specified archive file.
			$deleted = $this->archive->delete( $filepath );

			// Decrease the archive count.
			$archives_count --;

			// Increment the counter.
			$counter --;

			if ( $deleted ) {
				$logger->add( 'Deleted ' . $filepath );

				/**
				 * Take action after a backup has been deleted due to retention.
				 *
				 * @since 1.5.3
				 *
				 * @param string $filepath
				 */
				do_action( 'boldgrid_backup_retention_deleted', $filepath );
			} else {
				$logger->add( 'Failed to delete ' . $filepath );
			}
		}

		$logger->add( 'Completed core::enforce_retention.' );
		$logger->add_separator();
	}

	/**
	 * Add thickbox to bolgrid_backup admin pages.
	 *
	 * @since 1.14.0
	 */
	public function add_thickbox( $hook_suffix ) {
		if ( false !== strpos( $hook_suffix, 'boldgrid-backup' ) ) {
			add_thickbox();
		}
	}
}
