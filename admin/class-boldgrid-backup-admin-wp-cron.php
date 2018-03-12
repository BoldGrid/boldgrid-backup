<?php
/**
 * WP Cron.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * WP Cron.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_WP_Cron {

	/**
	 * $days of the weeky.
	 *
	 * @since  1.5.1
	 * @access publc
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
	 * @access public
	 * @var    array
	 */
	public $hooks = array(
		'backup' => 'boldgrid_backup_wp_cron_backup',
		'restore' => 'boldgrid_backup_wp_cron_restore',
		'run_jobs' => 'boldgrid_backup_wp_cron_run_jobs',
	);

	/**
	 * Schedules.
	 *
	 * @since  1.5.1
	 * @access public
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
				'display' => __( 'Every 5 minutes', 'boldgrid-backup' ),
			),
			'weekly' => array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display' => __( 'Weekly', 'boldgrid-backup' ),
			),
			/*
			 * It does not appear that crons can be added for a one time event.
			 * Add a "never" schedule. Let someone in 1,000 years have the fun
			 * of their site being restored out of nowhere wha ha ha!
			 */
			'never' => array(
				'interval' => 1000 * YEAR_IN_SECONDS,
				'display' => __( 'Never', 'boldgrid-backup' ),
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
	 * @param  array $settings
	 * @return bool
	 */
	public function add_all_crons( $settings = array() ) {
		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;
		$schedule = ! empty( $settings['schedule'] ) ? $settings['schedule'] : null;

		if( 'wp-cron' === $scheduler && $this->core->scheduler->is_available( $scheduler ) && ! empty( $schedule ) ) {
			$this->core->scheduler->clear_all_schedules();

			$scheduled = $this->schedule( $settings['schedule'], $this->hooks['backup'] );
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
		$archives = $this->core->get_archive_list();
		$archive_key = 0;
		$archive = $archives[ $archive_key ];
		$archive_filename = $archive['filename'];

		// Remove existing restore cron jobs.
		$this->clear_schedules( array( $this->hooks['restore'] ) );

		// Get the unix time for 5 minutes from now.
		$time_5_minutes_later = strtotime( '+5 MINUTES' );

		$event_added = wp_schedule_event( $time_5_minutes_later, 'never', $this->hooks['restore'] );

		// If cron job was added, then update the boldgrid_backup_pending_rollback option with time.
		if ( false !== $event_added ) {
			$pending_rollback['deadline'] = $time_5_minutes_later;
			update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
		}

		return;
	}

	/**
	 * Clear schedules.
	 *
	 * @since 1.5.1
	 *
	 * @param array An array of hooks to clear.
	 */
	public function clear_schedules( $hooks = array() ) {
		if( empty( $hooks ) ) {
			$hooks = $this->hooks;
		}

		foreach( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	/**
	 * Hook into "cron_schedules" filter and add weekly.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $schedules
	 * @return array
	 */
	public function cron_schedules( $schedules ) {
		foreach( $this->schedules as $key => $schedule ) {
			if( in_array( $key, $schedules, true ) ) {
				continue;
			}

			$schedules[$key] = $schedule;
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
	 * @param  $d string Day of the week.
	 * @param  $h int    Hour.
	 * @param  $m int    Minute.
	 * @param  $p string Period (am/pm).
	 * @return A timestamp on success.
	 */
	public function get_next_time( $d, $h, $m, $p ) {
		$schedule_time = strtotime( sprintf( 'this %1$s %2$s:%3$s %4$s', $d, $h, $m, $p ) );

		if( time() > $schedule_time ) {
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

		foreach( $crons as $time => $cron ) {
			$action = key( $cron );

			if( empty( $action ) || 0 !== strpos( $action, 'boldgrid_backup_' ) ) {
				continue;
			}

			$action_key = key( $cron[$action] );

			$ours[] = sprintf( '%1$s (%2$s %3$s %4$s)', $action, $cron[$action][$action_key]['schedule'], __( 'starting','boldgrid-backup' ), date( 'Y.m.d h:i:s a e', $time ) );
		}

		return $ours;
	}

	/**
	 * Restore via wp cron.
	 *
	 * @since 1.5.2
	 */
	public function restore() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		if ( empty( $pending_rollback ) ) {
			$this->clear_schedules( array( $this->hooks['restore'] ) );
			return false;
		}

		/*
		 * If the deadline has elapsed more than 2 minutes ago, then abort.
		 *
		 * The boldgrid-backup-cron.php file has this check. As wp cron is not
		 * as precise, we will not check.
		 */

		/*
		 * Set GET variables.
		 *
		 * The archive_key and the archive_filename must match.
		 */
		$_POST['restore_now'] = 1;
		$_POST['archive_key'] = 0;
		$_POST['archive_filename'] = basename( $pending_rollback['filepath'] );

		$archive_info = $this->core->restore_archive_file();

		// Remove existing restore cron jobs.
		$this->clear_schedules( array( $this->hooks['restore'] ) );
	}

	/**
	 * Schedule a wp cron.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $schedule BoldGrid Backup's $settings['schedule'].
	 * @param  string $hook
	 * @return bool
	 */
	public function schedule( $schedule, $hook ) {

		/*
		 * WP Cron works off of UTC. Get our "local" time from our $settings and
		 * convert it to UTC.
		 */
		$date = $this->core->time->get_settings_date();
		$new_timezone = new DateTimeZone( 'UTC' );
		$date->setTimezone( $new_timezone );

		// Get hour, minute, and period.
		$h = $date->format( 'g' );
		$m = $date->format( 'i' );
		$p = $date->format( 'A' );

		$success = true;

		foreach( $this->days as $day ) {
			if( 1 !== $schedule[ 'dow_' . $day ] ) {
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
			if( false === $scheduled ) {
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
	 * This method is usually ran after saving the BoldGrid Backup settings. If
	 * after save wp-cron is our scheduler, then we need to make sure we have
	 * the "run_jobs" wp-cron scheduled.
	 *
	 * @since 1.5.2
	 *
	 * @return bool
	 */
	public function schedule_jobs() {
		$success = true;

		if( ! wp_next_scheduled( $this->hooks['run_jobs'] ) ) {
			$scheduled = wp_schedule_event( time(), 'every-5-minutes', $this->hooks['run_jobs'] );
			$success = false !== $scheduled;
		}

		return $success;
	}

	/**
	 * Hook into "boldgrid_backup_wp_cron_backup" and generate backup.
	 *
	 * @since 1.5.1
	 */
	public function backup() {
		$archive_info = $this->core->archive_files( true );
	}
}
