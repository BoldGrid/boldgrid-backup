<?php
/**
 * File: class-boldgrid-backup-admin-wpcli.php
 *
 * @link https://www.boldgrid.com
 * @since 1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'bgbkup', 'Boldgrid_Backup_Admin_Wpcli' );

/**
 * Support WP-CLI actions.
 *
 * ## EXAMPLES
 *
 *     # Show the configured backup schedule
 *     $ wp bgbkup schedule show
 *     Backup schedule: Sunday, Wednesday at 3:11 AM (WordPress timezone: America/New_York / UTC -5)
 *     Success: Schedule listed.
 *
 *     # Clear the backup schedule
 *     $ wp bgbkup schedule clear
 *     Success: Schedule cleared.
 *
 *     # Set the backup schedule
 *     $ wp bgbkup schedule set --days=0,3 --time=0311
 *     Success: Schedule set.
 *
 * @since 1.8.0
 */
class Boldgrid_Backup_Admin_Wpcli {
	/**
	 * Boldgrid_Backup_Admin_Core object.
	 *
	 * @since 1.8.0
	 *
	 * @var Boldgrid_Backup_Admin_Core
	 * @staticvar
	 */
	public static $core;

	/**
	 * Print the backup schedule.
	 *
	 * ## OPTIONS
	 *
	 * clear: Clear the backup schedule.
	 * list: Print the backup schedule. (default command)
	 * set: Set the backup schedule.  Day numbers (0-6: Sunday-Saturday, comma-delimited) and time of day (uses the timezone set in WordPress).  PHP strtotime() is used when setting the time value.
	 * show: Alias for `list`.
	 *
	 * ## EXAMPLES
	 *
	 *     # Show the configured backup schedule
	 *     $ wp bgbkup schedule show
	 *     Backup schedule: Sunday, Wednesday at 3:11 AM (WordPress timezone: America/New_York / UTC -5)
	 *     Success: Schedule listed.
	 *
	 *     # Clear the backup schedule
	 *     $ wp bgbkup schedule clear
	 *     Success: Schedule cleared.
	 *
	 *     # Set the backup schedule
	 *     $ wp bgbkup schedule set --days=0,3 --time=0311
	 *     Success: Schedule set.
	 *
	 * @param array $args       Array of arguments.
	 * @param array $assoc_args Associative array of arguments.
	 */
	public function schedule( array $args = [], array $assoc_args = [] ) {
		$cmd = isset( $args[0] ) ? $args[0] : null;

		switch ( $cmd ) {
			case 'clear':
				$this->schedule_clear();
				WP_CLI::success( __( 'Schedule cleared.', 'boldgrid-backup' ) );
				break;
			case 'show':
				$this->schedule_list();
				WP_CLI::success( __( 'Schedule listed.', 'boldgrid-backup' ) );
				break;
			case 'list':
				$this->schedule_list();
				WP_CLI::success( __( 'Schedule listed.', 'boldgrid-backup' ) );
				break;
			case 'set':
				if ( $this->schedule_set( $assoc_args ) ) {
					WP_CLI::success( __( 'Schedule set.', 'boldgrid-backup' ) );
				} else {
					WP_CLI::error( __( 'Could not set schedule.  Check syntax.', 'boldgrid-backup' ) );
				}
				break;
			case null:
				WP_CLI::runcommand( 'help bgbkup schedule' );
				break;
			default:
				// Translators: 1: WP-CLI command.
				WP_CLI::error( sprintf( __( '"%s" is not a valid command.', 'boldgrid-backup' ), $cmd ) );
				break;
		}
	}

	/**
	 * Clear the backup schedule.
	 *
	 * @since 1.8.0
	 * @access protected
	 *
	 * @subcommand clear
	 */
	protected function schedule_clear() {
		$settings = self::$core->settings->get_settings();

		$settings['schedule'] = null;

		self::$core->scheduler->clear_all_schedules();
		self::$core->settings->save( $settings );
	}

	/**
	 * Print the backup schedule.
	 *
	 * @since 1.8.0
	 * @access protected
	 *
	 * @subcommand list
	 * @subcommand show
	 */
	protected function schedule_list() {
		$backup_days = [];
		$settings    = self::$core->settings->get_settings();

		if ( $settings['schedule']['dow_sunday'] ) {
			$backup_days[] = __( 'Sunday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_monday'] ) {
			$backup_days[] = __( 'Monday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_tuesday'] ) {
			$backup_days[] = __( 'Tuesday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_wednesday'] ) {
			$backup_days[] = __( 'Wednesday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_thursday'] ) {
			$backup_days[] = __( 'Thursday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_friday'] ) {
			$backup_days[] = __( 'Friday', 'boldgrid-backup' );
		}
		if ( $settings['schedule']['dow_saturday'] ) {
			$backup_days[] = __( 'Saturday', 'boldgrid-backup' );
		}

		$backup_days = implode( ', ', $backup_days );
		$backup_days = $backup_days ? $backup_days : 'None';

		echo 'Backup schedule: ' . $backup_days;

		if ( 'None' !== $backup_days ) {
			$has_tod = isset(
				$settings['schedule']['tod_h'],
				$settings['schedule']['tod_m'],
				$settings['schedule']['tod_a']
			);

			if ( $has_tod ) {
				echo __( ' at ', 'boldgrid-backup' ) . $settings['schedule']['tod_h'] . ':' .
					$settings['schedule']['tod_m'] . ' ' . $settings['schedule']['tod_a'] .
					__( ' (WordPress timezone: ', 'boldgrid-backup' ) .
					get_option( 'timezone_string' ) . ' / UTC ' . get_option( 'gmt_offset' ) . ')';
			} else {
				esc_html_e( ' at unknown time', 'boldgrid-backup' );
			}
		}

		echo PHP_EOL;
	}

	/**
	 * Set the backup schedule.
	 *
	 * @since 1.8.0
	 * @access protected
	 *
	 * @subsommand set
	 *
	 * @param  array $assoc_args Associative array of arguments.
	 * @return bool
	 */
	protected function schedule_set( array $assoc_args ) {
		if ( ! isset( $assoc_args['days'], $assoc_args['time'] ) ) {
			return false;
		}

		$days = explode( ',', $assoc_args['days'] );
		$time = strtotime( $assoc_args['time'] );

		if ( ! $time ) {
			return false;
		}

		// Ensure that all of the day numbers are valid.
		foreach ( $days as $day ) {
			if ( ! preg_match( '/^[0-6]$/', $day ) ) {
				return false;
			}
		}

		$settings = self::$core->settings->get_settings();

		$settings['schedule'] = [
			'dow_sunday'    => in_array( '0', $days, true ) ? 1 : 0,
			'dow_monday'    => in_array( '1', $days, true ) ? 1 : 0,
			'dow_tuesday'   => in_array( '2', $days, true ) ? 1 : 0,
			'dow_wednesday' => in_array( '3', $days, true ) ? 1 : 0,
			'dow_thursday'  => in_array( '4', $days, true ) ? 1 : 0,
			'dow_friday'    => in_array( '5', $days, true ) ? 1 : 0,
			'dow_saturday'  => in_array( '6', $days, true ) ? 1 : 0,
			'tod_h'         => (int) date( 'g', $time ),
			'tod_m'         => date( 'i', $time ),
			'tod_a'         => date( 'A', $time ),
		];

		$settings = self::$core->settings->update_cron( $settings );

		self::$core->settings->save( $settings );

		return $settings['crons_added'];
	}
}
