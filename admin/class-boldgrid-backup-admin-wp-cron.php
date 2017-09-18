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
			'weekly' => array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display' => __( 'Weekly', 'boldgrid-backup' ),
			),
		);
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
	 * Schedule a wp cron.
	 *
	 * @since 1.5.1
	 *
	 * @param $schedule array BoldGrid Backup's $settings['schedule'].
	 * @param $hook     string
	 */
	public function schedule( $schedule, $hook ) {
		// Get hour, minute, and period.
		$h = $schedule['tod_h'];
		$m = $schedule['tod_m'];
		$p = $schedule['tod_a'];

		foreach( $this->days as $day ) {
			if( 1 !== $schedule[ 'dow_' . $day ] ) {
				continue;
			}

			$schedule_time = $this->get_next_time( $day, $h, $m, $p );

			wp_schedule_event( $schedule_time, 'weekly', $hook );
		}
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
