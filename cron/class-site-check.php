<?php
/**
 * File: class-site-check.php
 *
 * Check the integrity of the website.
 *
 * @link       https://www.boldgrid.com
 * @since      1.9.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Cron;

/**
 * Class: Site_Check.
 *
 * @since 1.9.0
 */
class Site_Check {
	/**
	 * Maximum restoration attempts.
	 *
	 * @since  1.9.0
	 * @access private
	 * @static
	 *
	 * @var int
	 */
	private static $max_restore_attempts = 2;

	/**
	 * Run the site check process, to determine if the site needs to be restored from backup.
	 *
	 * Decide if a restoration should be completed.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cron\Info::has_errors()
	 * @see self::does_wp_load()
	 * @see \Boldgrid\Backup\Cron\Info::has_arg_flag()
	 *
	 * @return bool
	 */
	public static function should_restore() {
		$is_failing     = false;
		$should_restore = false;

		// Abort if there are errors retrieving information.
		if ( Info::has_errors() ) {
			return false;
		}

		$mode = Info::get_mode();

		$attempts_exceeded = Info::get_info()['restore_attempts'] >= self::$max_restore_attempts;

		// If "check" flag was passed and there have not been too many restoration attempts.
		if ( 'check' === $mode && ! $attempts_exceeded ) {
			// If WordPress cannot be loaded via PHP.
			if ( ! self::does_wp_load() ) {
				$should_restore = true;
			}
		}

		// If "Restore" flag was possed, which forces a restoration.
		if ( 'restore' === $mode ) {
			$should_restore = true;
		}

		return $should_restore;
	}

	/**
	 * Is the site reachable.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see \Boldgrid_Backup_Url_Helper::call_url()
	 *
	 * @return bool;
	 */
	public static function is_siteurl_reachable() {
		$result = false;

		require_once __DIR__ . '/class-boldgrid-backup-url-helper.php';

		if ( ! empty( Info::get_info()['siteurl'] ) ) {
			$response = ( new \Boldgrid_Backup_Url_Helper() )->call_url(
				Info::get_info()['siteurl'],
				$status,
				$errorno,
				$error
			);

			if ( 200 === $status ) {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Does WordPress load via PHP.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 *
	 * @return bool
	 */
	public static function does_wp_load() {
		\Boldgrid_Backup_Admin_Cli::call_command(
			'cd ' . __DIR__ . '; php -qf wp-test.php',
			$success,
			$return_var
		);

		return $success || 0 === $return_var;
	}
}
