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

WP_CLI::add_command( 'bgb', 'Boldgrid_Backup_Admin_Wpcli' );

/**
 * Class: Boldgrid_Backup_Admin_Wpcli
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
	 * set: Set the backup schedule.  Day numbers (0-6, comma-delimited) and any time (uses strtotime).
	 *
	 * ## EXAMPLES
	 *
	 * wp bgb schedule
	 * wp bgb schedule clear
	 * wp bgb schedule list
	 * wp bgb schedule set --days=0,1,2,3,4,5,6 --time=0311
	 * wp bgb schedule set --days=0,3 --time=0311
	 *
	 * @param array $args       Array of arguments.
	 * @param array $assoc_args Associative array of arguments.
	 */
	public function schedule( array $args = [], array $assoc_args = [] ) {
		$cmd = isset( $args[0] ) ? $args[0] : null;

		switch ( $cmd ) {
			case 'clear':
				$this->schedule_clear();
				WP_CLI::success( 'Schedule cleared.' );
				break;
			case null:
			case 'list':
				$this->schedule_list();
				WP_CLI::success( 'Schedule listed.' );
				break;
			case 'set':
				if ( $this->schedule_set( $assoc_args ) ) {
					WP_CLI::success( 'Schedule set.' );
				} else {
					WP_CLI::error( 'Could not set schedule.  Check syntax.' );
				}
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
	 */
	protected function schedule_list() {
		$backup_days = [];
		$settings    = self::$core->settings->get_settings();

		foreach ( $settings['schedule'] as $key => $value ) {
			if ( 0 === strpos( $key, 'dow_' ) && $value ) {
				$backup_days[] = ucfirst( str_replace( 'dow_', '', $key ) );
			}
		}

		$backup_days = implode( ', ', $backup_days );
		$backup_days = $backup_days ? $backup_days : 'None';

		echo 'Backup schedule: ' . $backup_days;

		if ( 'None' !== $backup_days ) {
			if ( isset( $settings['schedule']['tod_h'], $settings['schedule']['tod_m'], $settings['schedule']['tod_a'] ) ) {
				echo ' at ' . $settings['schedule']['tod_h'] . ':' . $settings['schedule']['tod_m'] . ' ' .
					$settings['schedule']['tod_a'] . ' (system/server time)';
			} else {
				echo ' at unknown time';
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
	 * @param  array $assoc_args Associative array of arguments.
	 * @return bool
	 */
	protected function schedule_set( array $assoc_args ) {
		if ( ! isset( $assoc_args['days'], $assoc_args['time'] ) ) {
			return false;
		}

		$days_arr = explode( ',', $assoc_args['days'] );
		$time     = strtotime( $assoc_args['time'] );

		foreach ( $days_arr as $day ) {
			if ( ! preg_match( '/^[0-6,]$/', $day ) ) {
				return false;
			}
		}

		if ( ! $time ) {
			return false;
		}

		$settings = self::$core->settings->get_settings();

		$settings['schedule'] = [
			'dow_sunday'    => in_array( '0', $days_arr, true ) ? 1 : 0,
			'dow_monday'    => in_array( '1', $days_arr, true ) ? 1 : 0,
			'dow_tuesday'   => in_array( '2', $days_arr, true ) ? 1 : 0,
			'dow_wednesday' => in_array( '3', $days_arr, true ) ? 1 : 0,
			'dow_thursday'  => in_array( '4', $days_arr, true ) ? 1 : 0,
			'dow_friday'    => in_array( '5', $days_arr, true ) ? 1 : 0,
			'dow_satday'    => in_array( '6', $days_arr, true ) ? 1 : 0,
			'tod_h'         => date( 'g', $time ),
			'tod_m'         => date( 'i', $time ),
			'tod_a'         => date( 'A', $time ),
		];

		self::$core->settings->update_cron( $settings );
		self::$core->settings->save( $settings );

		return true;
	}
}
