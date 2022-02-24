<?php
/**
 * File: class-boldgrid-backup-admin-in-progress.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_In_Progress
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_In_Progress {
	/**
	 * The core class object.
	 *
	 * @since  1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Our tmp class.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_In_Progress_Tmp
	 */
	private $tmp;

	/**
	 * A unix timestamp indicating when a backup was started.
	 *
	 * Currently this property is only used before and after a database is dumped,
	 * see $this->pre_dump() and $this->post_dump().
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    int
	 */
	protected $in_progress;

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->tmp = new Boldgrid_Backup_Admin_In_Progress_Tmp( $this->core );
	}

	/**
	 * Add a notice telling the user there's a backup in progress.
	 *
	 * Calls to this method should ensure user (based on role) should actually see this notice.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $notices Array of notices to display.
	 * @return array
	 */
	public function add_notice( $notices ) {
		global $pagenow;

		$in_progress = self::get();

		/*
		 * If we're on a page that shows "update protection" notices, return. The in progress bar will
		 * be included in said notice.
		 */
		if ( in_array( $pagenow, array( 'update-core.php', 'plugins.php' ), true ) ) {
			return $notices;
		}

		// If there's not a backup in progress, do not show the notice.
		if ( empty( $in_progress ) && ! self::is_quick_fail() ) {
			return $notices;
		}

		/*
		 * If we are in the middle of a backup, we'll want to increase the rate
		 * of the heartbeat so that we can more quickly update the user when
		 * the backup has completed.
		 */
		wp_enqueue_script( 'heartbeat' );

		wp_enqueue_script( 'jquery-ui-progressbar' );

		$notice = self::get_notice( true );
		if ( false === $notice ) {
			return $notices;
		}

		// If there's a backup in progress that started more than 15 minutes ago, something's awry.
		if ( $in_progress ) {
			$elapsed = time() - $in_progress;
			$limit   = 15 * MINUTE_IN_SECONDS;

			if ( $elapsed > $limit ) {
				$notice['message'] .= __(
					' Most backups usually finish before this amount of time, so we will stop displaying this notice.',
					'boldgrid-backup'
				);

				$this->end();
			}
		}

		$notices[] = $notice;

		return $notices;
	}

	/**
	 * Stop.
	 *
	 * Specify that we are no longer backing up a website.
	 *
	 * @since 1.6.0
	 */
	public function end() {
		$settings = $this->core->settings->get_settings( true );

		if ( ! empty( $settings['in_progress'] ) ) {
			unset( $settings['in_progress'] );
		}

		$this->core->settings->save( $settings );
	}

	/**
	 * Get the in progress value.
	 *
	 * The value is the time we started the backup.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public static function get() {
		$core = apply_filters( 'boldgrid_backup_get_core', false );

		$settings = $core->settings->get_settings( true );

		$in_progress = ! empty( $settings['in_progress'] ) ? $settings['in_progress'] : null;

		return $in_progress;
	}

	/**
	 * Get our backup's error message.
	 *
	 * @since 1.14.13
	 *
	 * @return mixed False if no error message, else error message string.
	 */
	public static function get_error_message() {
		$error = false;
		$data  = Boldgrid_Backup_Admin_In_Progress_Data::get_args();
		$keys  = array(
			'shutdown_fatal_error',
			'process_error',
		);

		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$error = $data[ $key ];
				break;
			}
		}

		return $error;
	}

	/**
	 * Get the log of the current backup in progress.
	 *
	 * @since 1.14.13
	 *
	 * @return Boldgrid_Backup_Admin_Log
	 */
	public static function get_log() {
		$log_filename = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'log_filename' );
		if ( empty( $log_filename ) ) {
			return false;
		}

		$core = apply_filters( 'boldgrid_backup_get_core', false );
		$log  = new Boldgrid_Backup_Admin_Log( $core );
		$log->init( $log_filename );

		return $log;
	}

	/**
	 * Get our in progress notice.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed Array on success, false when there's no backup in progress.
	 */
	public static function get_notice( $force = false ) {
		// Get the time the backup started. If there is no backup in progress, abort.
		$in_progress = self::get();
		if ( empty( $in_progress ) && ! $force ) {
			return false;
		}

		$core = apply_filters( 'boldgrid_backup_get_core', false );
		$core->time->init( $in_progress );

		$message = '<div id="boldgrid_backup_in_progress_container" class="hidden">';

		// Create the nav tabs.
		$message .= '
			<nav class="nav-tab-wrapper bgbkup-nav-tab-wrapper-small bgbkup-nav-tab-wrapper-in-progress">
				<a class="nav-tab nav-tab-active" data-container="bgbkup_progress_status">Status</a>
				<a class="nav-tab" data-container="bgbkup_progress_log">Log</a>
			</nav>';

		// Add our "Status" container.
		$message .= '
			<div id="bgbkup_progress_status">
				<table' . ( empty( $in_progress ) ? ' class="hidden"' : '' ) . '>
					<tr>
						<th>' . esc_html__( 'Started at:', 'boldgrid-backup' ) . '</th>
						<td>' . $core->time->get_span() . ' / ' . human_time_diff( $in_progress, time() ) . ' ' . esc_html__( 'ago', 'boldgrid-backup' ) . '</td>
					</tr>
					<tr>
						<th>' . esc_html__( 'Triggered by:', 'boldgrid-backup' ) . '</th>
						<td>' . esc_html( Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'trigger' ) ) . '</td>
					</tr>
					<tr>
						<th>' . esc_html__( 'Actions:', 'boldgrid-backup' ) . '</th>
						<td id="bgbkup_progress_actions">' . Boldgrid_Backup_Admin_Archiver_Cancel::get_button() . '</td>
					</tr>
				</table>

				<div id="boldgrid_backup_in_progress_steps">
					<div class="step" data-step="1">' . esc_html__( 'Backing up database...', 'boldgrid-backup' ) . '</div>
					<div class="step" data-step="2">' . esc_html__( 'Adding files to archive...', 'boldgrid-backup' ) . '</div>
					<div class="step" data-step="3">' . esc_html__( 'Saving archive to disk...', 'boldgrid-backup' ) . '</div>
				</div>

				<div id="boldgrid-backup-in-progress-bar">
					<div class="progress-label">' . esc_html__( 'Loading...', 'boldgrid-backup' ) . '</div>
					<div id="last_file_archived"></div>
				</div>
			</div>';

		// Add our "Log" container.
		$message .= '
			<div id="bgbkup_progress_log" class="hidden">
				<p class="bgbkup-log" style="white-space:pre;overflow:auto">' . __( 'Loading...', 'boldgrid-backup' ) . '</p>
			</div>';

		$message .= '</div>';

		$notice = [
			'class'   => 'notice notice-warning boldgrid-backup-in-progress',
			'message' => $message,
			'heading' => BOLDGRID_BACKUP_TITLE . ' - ' . __( 'Backup in progress', 'boldgrid-backup' ),
		];

		return $notice;
	}

	/**
	 * Get our notice markup.
	 *
	 * @since 1.6.0
	 *
	 * @return mixed Returns a string (html markup), a WordPress admin notice or
	 *               false if we don't have a notice.
	 */
	public function get_notice_markup() {
		$notice = self::get_notice();
		$markup = false;

		if ( $notice ) {
			$markup = $this->core->notice->get_notice_markup( $notice['class'], $notice['message'], $notice['heading'] );
		}

		return $markup;
	}

	/**
	 * Return how many seconds ago the backup started.
	 *
	 * @since 1.14.13
	 *
	 * @return mixed False if we cannot determine, otherwise an int to show how many seconds ago.
	 */
	public static function get_start_ago() {
		$time_start = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'start_time' );
		if ( false === $time_start ) {
			return false;
		}

		return time() - $time_start;
	}

	/**
	 * Get our backup process' pgid.
	 *
	 * @since 1.14.13
	 *
	 * @return null if not supported, otherwise value of posix_getpgid().
	 */
	public function getpgid() {
		if ( ! Boldgrid_Backup_Admin_Test::is_getpgid_supported() ) {
			return null;
		}

		$pid = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'pid' );
		if ( empty( $pid ) ) {
			return null;
		}

		return posix_getpgid( $pid );
	}

	/**
	 * Determine whether or not this backup process is done.
	 *
	 * This does not return success or failure, simply that the backup process is done.
	 *
	 * @since 1.14.13
	 *
	 * @return bool
	 */
	public static function is_done() {
		// If we can't get the timestamp of the current backup in progress, it's "done" and been cleared.
		$timestamp = self::get();
		if ( empty( $timestamp ) ) {
			return true;
		}

		// If the backup process finished and it flagged itself success, we're done.
		if ( is_bool( Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'success', null ) ) ) {
			return true;
		}

		// If the backup process is no longer running, we're done.
		if ( false === self::is_running() ) {
			return true;
		}

		return false;
	}

	/**
	 * A "quick fail" is when a backup fails within the first 5 - 10 seconds of starting.
	 *
	 * This causes a problem with the in progress toolbar because (1) the user clicks "backup now",
	 * (2) the backup pretty much fails right away, and (3) by the time the page refreshes there's no
	 * longer a backup in progress. This means the user clicks "backup now", the page refreshes, but
	 * they never see an in progress bar.
	 *
	 * @since 1.14.13
	 *
	 * @return bool
	 */
	public static function is_quick_fail() {
		// If there's a backup in progress, then it can't be a quick fail.
		$in_progress = self::get();
		if ( ! empty( $in_progress ) ) {
			return false;
		}

		/*
		 * If we can't determine when the backup started, or it started more than 20 seconds ago, it
		 * wasn't a "quick" fail.
		 */
		$start_ago = self::get_start_ago();
		if ( false === $start_ago || 20 <= $start_ago ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether or not the backup is running.
	 *
	 * This method relies on getpgid support. If this method returns null, then we don't know for sure
	 * if the backup is running or not. An alternative is to use the is_done() method.
	 *
	 * @since 1.14.13
	 *
	 * @return mixed null if support for this is not available. Otherwise, bool.
	 */
	public static function is_running() {
		if ( ! Boldgrid_Backup_Admin_Test::is_getpgid_supported() ) {
			return null;
		}

		$pid = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'pid' );
		if ( empty( $pid ) ) {
			return null;
		}

		return false !== posix_getpgid( $pid );
	}

	/**
	 * Take action when the heartbeat is received.
	 *
	 * Include data in the heartbeat to let the user know if their backup is
	 * still in progress, or it has finished.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $response Response.
	 * @param  array $data     Data in heartbeat.
	 * @return array
	 */
	public function heartbeat_received( $response, $data ) {
		// Only admins should see the status of a backup in progress.
		if ( ! Boldgrid_Backup_Admin_Utility::is_user_admin() ) {
			return $response;
		}

		$key = 'boldgrid_backup_in_progress';

		if ( empty( $data[ $key ] ) ) {
			return $response;
		}

		$log = self::get_log();

		/*
		 * An int specifiying when the current "in progress" backup started.
		 *
		 * The value is:
		 * 1. Stored in Total Upkeep's settings.
		 * 2. Originally set by Boldgrid_Backup_Admin_Core::archive_files() when archiving begins.
		 * 3. Unset by:
		 *   a. self::pre_dump().
		 *   b. Boldgrid_Backup_Admin_Archive_Fail::shutdown().
		 *
		 * When this value is missing, the in-progress.js script then determines that a backup is no
		 * longer in progress, and updates the UI for the user.
		 */
		$response[ $key ] = self::get();

		// Our "backup complete!" admin notice.
		$response['boldgrid_backup_complete'] = $this->core->notice->get_backup_complete();

		$response['in_progress_data'] = Boldgrid_Backup_Admin_In_Progress_Data::get_args();

		$response['is_success'] = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'success', null );

		$response['boldgrid_backup_error'] = array();

		// If we have an error message (either a shutdown fatal error, or a process error), return it.
		if ( false !== self::get_error_message() ) {
			$style = ! empty( $response['in_progress_data']['shutdown_fatal_error'] ) ?
				' style="white-space:pre;font-family:\'Courier New\';overflow:auto"' : '';

			$header = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'is_user_killed' ) ?
				__( 'Backup Canceled', 'boldgrid-backup' ) : __( 'Error creating backup', 'boldgrid-backup' );

			$response['boldgrid_backup_error'] = array(
				'class'   => 'notice notice-error boldgrid-backup-in-progress',
				'message' => '
					<p' . $style . '>' . wp_kses(
					self::get_error_message(),
					array( 'strong' => array() )
				) . '</p>',
				'header'  => BOLDGRID_BACKUP_TITLE . ' - ' . $header,
			);
		}

		// Steps to take if we're on the last step, step 3, closing the archive.
		if ( 3 === Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'step' ) ) {
			$tmp = $this->tmp->get();
			if ( ! empty( $tmp ) ) {
				$response['in_progress_data']['tmp'] = $tmp;
			}
		}

		/*
		 * Add the backup log file to the response.
		 *
		 * @todo To save on bandwidth, we don't have to return the entire log file, just the part the
		 * user hasn't seen.
		 */
		$response['log'] = esc_html( $log->get_contents() );

		// If support is available, add info about whether the backup process is running.
		if ( Boldgrid_Backup_Admin_Test::is_getpgid_supported() ) {
			$response['is_running'] = self::is_running();
			$log->add( 'Backup process running: ' . ( ! $response['is_running'] ? 'No' : 'Yes (pgid = ' . self::getpgid() . ')' ) );
		}

		$response['is_killed'] = false === $response['is_running'] && ! is_bool( $response['is_success'] ) && empty( $response['boldgrid_backup_error'] );
		if ( $response['is_killed'] ) {
			/*
			 * If the backup process was killed, we may have not been able to flag that the process
			 * has ended. Also note: do not set the success value as false because we are expecting
			 * a killed backup to not have been able to flag a backup with a status.
			 */
			$this->end();

			$response['boldgrid_backup_error'] = array(
				'class'   => 'notice notice-error',
				'header'  => BOLDGRID_BACKUP_TITLE . ' - ' . __( 'Backup failed', 'boldgrid-backup' ),
				'message' => '<p>' . wp_kses(
					sprintf(
						// Translators: 1 An opening anchor tag to our troubleshooting tutorial, 2 its closing tag.
						__( 'Your backup failed, and we were unable to detect any fatal errors. This usually happens when your hosting provider kills a backup process using the "SIGKILL" signal. To learn more about this as well as troubleshooting techniques, please %1$sclick here%2$s.', 'boldgrid-backup' ),
						'<a href="https://www.boldgrid.com/support/total-upkeep/backup-wordpress-website/#troubleshooting" target="_blank">',
						'</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				) . '</p>',
			);
		}

		// Tell the "in progress" system we're done, and it should run onSuccess / onFailure functions.
		$response['is_done'] = self::is_done();

		return $response;
	}

	/**
	 * Action to take before a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function post_dump() {
		/*
		 * After the database has been dumped, restore the flag stating a backup
		 * is still in progress.
		 *
		 * @see documentation in $this->pre_dump().
		 */
		if ( ! empty( $this->in_progress ) ) {
			$this->set( $this->in_progress );
		}
	}

	/**
	 * Action to take after a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function pre_dump() {
		/*
		 * Cancel any "Backup in progress" statuses.
		 *
		 * Avoid this issue:
		 * Before we begin creating a backup, we set a flag stating there is a
		 * "Backup in progress". So, when we create a backup of the database,
		 * that flag is in the backup. When we restore a backup, that flag will
		 * be restored, even if we're not in the middle of making a backup, thus
		 * giving us a false positive.
		 */
		$this->in_progress = self::get();
		$this->end();
	}

	/**
	 * Set that we are in progress of backing up a website.
	 *
	 * @since 1.6.0
	 *
	 * @param int $time A unix timestamp indicating the time a backup started.
	 */
	public function set( $time = null ) {
		$settings = $this->core->settings->get_settings( true );

		$settings['in_progress'] = ! empty( $time ) ? $time : time();

		$this->core->settings->save( $settings );
	}

	/**
	 * Via ajax, get our "in progress" admin notice.
	 *
	 * Example usage includes the customizer. When a user goes to the "change themes" section of the
	 * customizer, an ajax call is made (action:boldgrid_backup_get_progress_notice) and this method
	 * handles it. If the $in_progress_markup data we return is not false, then we add the notice and
	 * trigger boldgrid_backup_progress_notice_added. If there is not a backup in progress, then ultimately
	 * nothing happens.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_get_progress_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! check_ajax_referer( 'boldgrid_backup_customizer', 'nonce', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$in_progress_markup = $this->get_notice_markup();

		wp_send_json_success( $in_progress_markup );
	}
}
