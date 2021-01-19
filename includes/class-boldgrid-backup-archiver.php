<?php
/**
 * File: class-boldgrid-backup-archiver.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Archiver
 *
 * Originally, all methods for archiving a file have lived in Boldgrid_Backup_Admin_Core. This class,
 * over time, will absorb those methods.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Archiver {
	/**
	 * Admin core.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 *
	 */
	private $format;

	/**
	 * \Boldgrid\Backup\Archiver\Info
	 */
	private $info;

	/**
	 *
	 * @var \Boldgrid\Backup\V2\Archiver\Resumer()
	 */
	private $resumer;

	/**
	 * Whether or not we are archiving.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var bool
	 */
	private static $is_archiving = false;

	/**
	 *
	 */
	private $is_resuming = false;

	/**
	 * An instance of Boldgrid_Backup_Admin_Task.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Task
	 */
	private $task;

	/**
	 * @var \BoldGrid\Backup\V2\Archiver\Archiver
	 */
	protected $backup_process;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 */
	public function __construct( $id = null ) {
		$this->is_resuming = ! is_null( $id );
		$this->core        = apply_filters( 'boldgrid_backup_get_core', null );
		$this->format      = $this->core->settings->get_setting( 'format' );

		if ( 'one' === $this->format ) {
			$this->info = new \Boldgrid\Backup\Archiver\Info( 'one', array() );
		} else {
			$this->backup_process = \BoldGrid\Backup\V2\Archiver\Factory::run( $id );
			$this->info           = new \Boldgrid\Backup\Archiver\Info( 'many', $this->backup_process );
			$this->resumer        = new \Boldgrid\Backup\V2\Archiver\Resumer();

			if ( $this->is_resuming ) {
				// Normally, task is initialized in init(). Initialize task now as init() won't be ran.
				$this->task = new Boldgrid_Backup_Admin_Task();
				$this->task->init_by_id( $this->info->get_key( 'task_id' ) );

				// Initialize the logger. Mainly for logging done within self::complete().
				$this->core->logger->init( $this->info->get_key( 'log_filename' ) );
			}
		}
	}

	/**
	 * Steps to take when archiving is complete.
	 *
	 * @since SINCEVERSION
	 */
	public function complete() {
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'status', esc_html__( 'Wrapping things up...', 'boldgrid-backup' ) );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'percentage', 100 );

		$filepath = $this->info->get_key( 'filepath' );

		// Add some statistics to the return.
		$this->info->set_key( 'lastmodunix', $this->core->wp_filesystem->mtime( $filepath ) );
		$this->info->set_key( 'filesize', $this->core->wp_filesystem->size( $filepath ) );

		// Modify the archive file permissions to help protect from public access.
		$this->core->wp_filesystem->chmod( $filepath, 0600 );

		// Delete the temporary database dump file.
		$this->core->wp_filesystem->delete( $this->info->get_key( 'db_dump_filepath' ), false, 'f' );

		// Calculate duration.
		$this->info->set_key( 'duration', number_format( ( microtime( true ) - $this->info->get_key( 'time_start' ) ), 2, '.', '' ) );
		$this->info->set_key( 'db_duration', number_format( ( $this->info->get_key( 'db_time_stop' ) - $this->info->get_key( 'time_start' ) ), 2, '.', '' ) );

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
		 * @param array $this->info {
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
		do_action( 'boldgrid_backup_post_archive_files', $this->info->get() );

		/*
		 * Send an email to the user, RIGHT NOW.
		 *
		 * Only send an email to the user now IF they are manually creating a backup. If this backup
		 * was created during a scheduled backup, the user will get an email from the jobs queue.
		 * Scheduled backups receive email notifications from the jobs queue because that email will
		 * not only include the standard info about the backup (which we're sending now), it will
		 * also include info about other jobs that were run (such as uploading the backup remotely).
		 */
		if ( $this->core->email->user_wants_notification( 'backup' ) && ! $this->core->is_scheduled_backup ) {
			$this->core->logger->add( 'Starting sending of email...' );

			$email_parts   = $this->core->email->post_archive_parts( $this->info->get() );
			$email_body    = $email_parts['body']['main'] . $email_parts['body']['signature'];
			$email_success = $this->core->email->send( $email_parts['subject'], $email_body );
			$this->info->set_key( 'mail_success', $email_success );

			$this->core->logger->add( 'Sending of email complete! Status: ' . $email_success );
		}

		// Update WP option for "boldgrid_backup_last_backup".
		update_site_option( 'boldgrid_backup_last_backup', time() );

		$this->core->archive_log->write( $this->info->get() );

		// Enforce retention setting.
		$this->core->enforce_retention();

		update_option( 'boldgrid_backup_latest_backup', $this->info->get() );

		// Actions to take if we're creating a full site backup.
		if ( $this->core->archiver_utility->is_full_backup() ) {
			$this->core->archive->write_results_file( $this->info->get() );
		}

		Boldgrid_Backup_Admin_In_Progress_Data::set_args( [ 'status' => esc_html__( 'Backup complete!', 'boldgrid-backup' ) ] );

		if ( isset( $this->core->activity ) ) {
			$this->core->activity->add( 'any_backup_created', 1, $this->core->rating_prompt_config );
		}

		if ( 'many' === $this->format ) {
			$this->resumer->remove_cron();
		}

		$this->core->logger->add( 'Backup complete!' );
		$this->core->logger->add_memory();

		$this->task->end();

		self::$is_archiving = false;
	}

	/**
	 * Get our archive info.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_info() {
		return $this->info->get();
	}

	/**
	 * Steps to take before an archive is started.
	 *
	 * This method includes actions for both v1 and v2 backups.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		// Init our logger.
		$log_filename = 'archive-' . time() . '.log';
		$this->core->logger->init( $log_filename );
		$this->info->set_key( 'log_filename', $log_filename );

		$this->core->logger->add( 'Backup process initialized.' );
		$this->info->set_key( 'time_start', microtime( true ) );

		// Init our task.
		$this->task = new Boldgrid_Backup_Admin_Task();
		if ( ! empty( $_POST['task_id'] ) ) { // phpcs:ignore
			$this->task->init_by_id( $_POST['task_id'] ); // phpcs:ignore
		} else {
			$this->task->init( [ 'type' => 'backup' ] );
		}
		$this->task->start();
		$this->info->set_key( 'task_id', $this->task->get_id() );

		self::$is_archiving = true;

		if ( 'many' === $this->format ) {
			$this->resumer->maybe_add_cron();
		}

		Boldgrid_Backup_Admin_Utility::bump_memory_limit( '1G' );
		// Close any PHP session, so that another session can open during the backup operation.
		session_write_close();
		// Prevent this script from dying.
		ignore_user_abort( true );

		/*
		 * A scheduled backup is a backup triggered by the user's Settings > Backup Schedule.  If the user clicked
		 * "Backup Site Now" or this is a backup before an auto update occurs, this is not a scheduled backup.
		 */
		$this->core->is_scheduled_backup = $this->core->doing_cron && ! $this->core->pre_auto_update;
		$this->core->pre_auto_update     = 'pre_auto_update' === current_filter();

		Boldgrid_Backup_Admin_In_Progress_Data::set_args(
			[ 'status' => esc_html__( 'Initializing backup', 'boldgrid-backup' ) ]
		);

		/**
		 * Actions to take before any archiving begins.
		 *
		 * @since 1.5.2
		 */
		do_action( 'boldgrid_backup_archive_files_init' );

		$this->core->in_progress->set();

		/*
		 * If this is a scheduled backup and no location is selected to save the
		 * backup to, abort.
		 *
		 * While we could prevent he user from setting this up in the first place,
		 * at the moment the settings page saves all settings. So, if the user
		 * wanted to change their retention settings but did not want to schedule
		 * backups, validating storage locations would be problematic.
		 */
		if ( $this->core->is_scheduled_backup && ! $this->core->remote->any_enabled() ) {
			$error = esc_html__( 'No backup locations selected! While we could create a backup archive, you have not selected where the backup archive should be saved. Please choose a storage location in your settings for where to save this backup archive.', 'boldgrid-backup' );
			$this->core->archive_fail->schedule_fail_email( $error );
			return [ 'error' => $error ];
		}

		// Check if functional.
		if ( ! $this->core->test->run_functionality_tests() ) {
			// Display an error notice, if not already on the test page.
			if ( ! isset( $_GET['page'] ) || 'boldgrid-backup-test' !== $_GET['page'] ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
				// Display an error notice.
				$this->core->notice->functionality_fail_notice();
			}

			return [ 'error' => 'Functionality tests fail.' ];
		}

		/*
		 * Initialize return array and add "compressor" and "save" keys. Since 1.6.0, the folder include
		 * and exclude settings below are for informational purposes only. This array cannot be filtered
		 * to adjust which folders are actually included / excluded.
		 */
		$this->info->set_keys( array(
			'mode'              => 'backup',
			'compressor'        => null,
			'format'            => $this->format,
			'filesize'          => 0,
			'total_size'        => 0,
			'folder_include'    => $this->core->folder_exclusion->from_settings( 'include' ),
			'folder_exclude'    => $this->core->folder_exclusion->from_settings( 'exclude' ),
			'table_exclude'     => $this->core->db_omit->get_excluded_tables(),
			'title'             => ! empty( $_POST['backup_title'] ) ? stripslashes( $_POST['backup_title'] ) : null, // phpcs:ignore WordPress.CSRF.NonceVerification,WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
			'description'       => ! empty( $_POST['backup_description'] ) ? stripslashes( $_POST['backup_description'] ) : null, // phpcs:ignore WordPress.CSRF.NonceVerification,WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
			// Information used for the emergency restoration process.
			'ABSPATH'           => ABSPATH,
			'backup_id'         => $this->core->get_backup_identifier(),
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
		) );

		// Determine how this backup was triggered.
		if ( $this->core->pre_auto_update ) {
			$this->info->set_key( 'trigger', esc_html__( 'Auto update', 'boldgrid-bakcup' ) );
		} elseif ( $this->core->doing_ajax && is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$this->info->set_key( 'trigger', $current_user->user_login . ' (' . $current_user->user_email . ')' );
		} elseif ( $this->core->doing_wp_cron ) {
			$this->info->set_key( 'trigger', 'WP cron' );
		} elseif ( $this->core->doing_cron ) {
			$this->info->set_key( 'trigger', 'Cron' );
		} else {
			$this->info->set_key( 'trigger', esc_html__( 'Unknown', 'boldgrid-backup' ) );
		}

		// Get and validate our compressor.
		$compressor = $this->core->compressors->get();
		$this->info->set_key( 'compressor', $compressor );
		if ( null === $compressor ) {
			return [ 'error' => 'No available compressor.' ];
		}

		// Cleanup. Enforce retention and delete orphaned files.
		$this->core->enforce_retention();
		$orphan_cleanup = new Boldgrid\Backup\Admin\Orphan\Cleanup();
		$orphan_cleanup->run();

		$this->info->set_key( 'init_time_end', microtime( true ) );
	}

	/**
	 * Return whether or not we are currently generating an archive.
	 *
	 * @since SINCEVERSION
	 * @return bool
	 */
	public static function is_archiving() {
		return self::$is_archiving;
	}

	/**
	 *
	 */
	public function is_init_complete() {
		$start = $this->info->get_key( 'time_start' );
		$end   = $this->info->get_key( 'init_time_end' );

		return ! empty( $start ) && ! empty( $end );
	}

	/**
	 * Create an archive.
	 *
	 * Do everything.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		if ( ! $this->is_resuming ) {
			$this->init();
		} elseif ( ! $this->is_init_complete() ) {
			$this->info->set_key( 'error', __( 'Unable to initialize backup. Previous init was incomplete.', 'boldgrid-backup' ) );
		}

		if ( empty( $this->info->get_key( 'error' ) ) ) {
			if ( 'one' === $this->format ) {
				$info = $this->get_info();
				$info = $this->core->archive_files( $info );
				$this->info->set( $info );
			} else {
				$this->backup_process->run();
			}
		}

		$this->complete();

		return $this->get_info();
	}
}
