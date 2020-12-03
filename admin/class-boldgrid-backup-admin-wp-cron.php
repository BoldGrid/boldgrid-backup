<?php
/**
 * FIle: class-boldgrid-backup-admin-wp-cron.php
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_WP_Cron
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_WP_Cron {
	/**
	 * Days of the week.
	 *
	 * @since  1.5.1
	 * @var array
	 */
	public $days = array(
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
	);

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Hooks.
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	public $hooks = array(
		'backup'   => 'boldgrid_backup_wp_cron_backup',
		'restore'  => 'boldgrid_backup_wp_cron_restore',
		'run_jobs' => 'boldgrid_backup_wp_cron_run_jobs',
	);

	/**
	 * Schedules.
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	public $schedules = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->schedules = array(
			'every-5-minutes' => array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes', 'boldgrid-backup' ),
			),
			'weekly'          => array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Weekly', 'boldgrid-backup' ),
			),

			/*
			 * It does not appear that crons can be added for a one time event.
			 * Add a "never" schedule. Let someone in 1,000 years have the fun
			 * of their site being restored out of nowhere wha ha ha!
			 */
			'never'           => array(
				'interval' => 1000 * YEAR_IN_SECONDS,
				'display'  => __( 'Never', 'boldgrid-backup' ),
			),
		);
	}

	/**
	 * Add all cron jobs.
	 *
	 * This method first clears all crons, then adds all necessary crons based
	 * upon our settings.
	 *
	 * This method is useful for when:
	 * # User saves settings on settings page and crons need to be updated.
	 * # User reactivates plugin and all crons need to be added again.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $settings Settings.
	 * @return bool
	 */
	public function add_all_crons( $settings = array() ) {
		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;
		$schedule  = ! empty( $settings['schedule'] ) ? $settings['schedule'] : null;

		if ( 'wp-cron' === $scheduler && $this->core->scheduler->is_available( $scheduler ) && ! empty( $schedule ) ) {
			$this->core->scheduler->clear_all_schedules();

			$scheduled      = $this->schedule( $settings, $this->hooks['backup'] );
			$jobs_scheduled = $this->schedule_jobs();

			return $scheduled && $jobs_scheduled;
		}
	}

	/**
	 * Add cron to perform auto rollback.
	 *
	 * @since 1.5.1
	 */
	public function add_restore_cron() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

		// Get the archive to restore.
		$archives         = $this->core->get_archive_list();
		$archive_key      = 0;
		$archive          = $archives[ $archive_key ];
		$archive_filename = $archive['filename'];

		// Remove existing restore cron jobs.
		$this->clear_schedules( array( $this->hooks['restore'] ) );

		// Cron has a 15 minute window for auto restoriations, and so will we (WP Cron).
		$auto_restore_time = strtotime( '+15 MINUTES' );

		$event_added = wp_schedule_event( $auto_restore_time, 'never', $this->hooks['restore'] );

		// If cron job was added, then update the boldgrid_backup_pending_rollback option with time.
		if ( false !== $event_added ) {
			$pending_rollback['deadline'] = $auto_restore_time;
			update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
		}
	}

	/**
	 * Clear schedules.
	 *
	 * @since 1.5.1
	 *
	 * @param array $hooks An array of hooks to clear.
	 */
	public function clear_schedules( $hooks = array() ) {
		if ( empty( $hooks ) ) {
			$hooks = $this->hooks;
		}

		foreach ( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	/**
	 * Hook into "cron_schedules" filter and add weekly.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $schedules An array of events.
	 * @return array
	 */
	public function cron_schedules( $schedules ) {
		foreach ( $this->schedules as $key => $schedule ) {
			if ( in_array( $key, $schedules, true ) ) {
				continue;
			}

			$schedules[ $key ] = $schedule;
		}

		return $schedules;
	}

	/**
	 * Get the next time for a wp cron.
	 *
	 * For example, if today is Monday 1:10pm and you pass in Monday 1:11pm, it
	 * should return 1 minute from now. If you pass in Monday 1:09pm, that time
	 * has already passed, so return next Monday at 1:09pm.
	 *
	 * @param string $d Day of the week.
	 * @param int    $h Hour.
	 * @param int    $m Minute.
	 * @param string $p Period (am/pm).
	 * @return A timestamp on success.
	 */
	public function get_next_time( $d, $h, $m, $p ) {
		$schedule_time = strtotime( sprintf( 'this %1$s %2$s:%3$s %4$s', $d, $h, $m, $p ) );

		if ( time() > $schedule_time ) {
			$schedule_time = strtotime( sprintf( 'next %1$s %2$s:%3$s %4$s', $d, $h, $m, $p ) );
		}

		return $schedule_time;
	}

	/**
	 * Get all of our wp crons.
	 *
	 * @since 1.5.2
	 *
	 * @return array
	 */
	public function get_our_crons() {
		$ours = array();

		$crons = _get_cron_array();
		$crons = is_array( $crons ) ? $crons : array();

		foreach ( $crons as $time => $cron ) {
			$action = key( $cron );

			if ( empty( $action ) || 0 !== strpos( $action, 'boldgrid_backup_' ) ) {
				continue;
			}

			$action_key = key( $cron[ $action ] );

			$ours[] = sprintf( '%1$s (%2$s %3$s %4$s)', $action, $cron[ $action ][ $action_key ]['schedule'], __( 'starting', 'boldgrid-backup' ), date( 'Y.m.d h:i:s a e', $time ) );
		}

		return $ours;
	}

	/**
	 * Restore via wp cron.
	 *
	 * @since 1.5.2
	 *
	 * @return mixed null|false
	 */
	public function restore() {
		$archive_info = array(
			'error' => __( 'Could not perform restoration from WP Cron task.', 'boldgrid-backup' ),
		);

		if ( $this->core->restore_helper->prepare_restore() ) {
			$archive_info = $this->core->restore_archive_file();
		}

		// Remove existing restore cron jobs.
		$this->clear_schedules( array( $this->hooks['restore'] ) );

		return $archive_info;
	}

	/**
	 * Schedule a wp cron.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $settings Settings.
	 * @param  string $hook     Hook name.
	 * @return bool
	 */
	public function schedule( $settings, $hook ) {
		/*
		 * WP Cron works off of UTC. Get our "local" time from our $settings and
		 * convert it to UTC.
		 *
		 * It's important that we pass in our $settings to get_settings_date().
		 * Let's say the original time was 4am and we changed it to 5am.
		 * # If we don't pass in $settings, which is the new time we're in the
		 *   middle of saving right now (5am)...
		 * # Then it will get the settings from options, which is still 4am (as
		 *   it hasn't been saved yet).
		 */
		$date         = $this->core->time->get_settings_date( $settings );
		$new_timezone = new DateTimeZone( 'UTC' );
		$date->setTimezone( $new_timezone );

		// Get hour, minute, and period.
		$h = $date->format( 'g' );
		$m = $date->format( 'i' );
		$p = $date->format( 'A' );

		$success = true;

		foreach ( $this->days as $day ) {
			if ( 1 !== $settings['schedule'][ 'dow_' . $day ] ) {
				continue;
			}

			$schedule_time = $this->get_next_time( $day, $h, $m, $p );

			/*
			 * Schedule our event and track our success.
			 *
			 * As we may be scheduling multiple events via this loop, the
			 * $success of this method is based on whether or not all items
			 * are successfully scheduled. If we have 5 days to schedule and
			 * only 4 are scheduled successfully, this method returns false.
			 */
			$scheduled = wp_schedule_event( $schedule_time, 'weekly', $hook );
			if ( false === $scheduled ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Schedule the "run_jobs" hook.
	 *
	 * This hook will run every 5 minutes and run one job at a time, such as
	 * upload to a remote storage provider.
	 *
	 * This method is usually ran after saving the settings. If after save wp-cron is our scheduler,
	 * then we need tomake sure we have the "run_jobs" wp-cron scheduled.
	 *
	 * @since 1.5.2
	 *
	 * @return bool
	 */
	public function schedule_jobs() {
		$success = true;

		if ( ! wp_next_scheduled( $this->hooks['run_jobs'] ) ) {
			$scheduled = wp_schedule_event( time(), 'every-5-minutes', $this->hooks['run_jobs'] );
			$success   = false !== $scheduled;
		}

		return $success;
	}

	/**
	 * Hook into "boldgrid_backup_wp_cron_backup" and generate backup.
	 *
	 * @since 1.5.1
	 */
	public function backup() {
		$archiver = new Boldgrid_Backup_Archiver();
		$archiver->run();
	}
}
