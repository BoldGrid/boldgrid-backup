<?php
/**
 * File: class-boldgrid-backup-admin-scheduler.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Scheduler
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Scheduler {
	/**
	 * Available schedulers.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    array
	 */
	public $available = array();

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Clear all schedules.
	 *
	 * @since 1.5.1
	 */
	public function clear_all_schedules() {
		$this->core->wp_cron->clear_schedules();
		$this->core->cron->delete_cron_entries( 'all' );
	}

	/**
	 * Get our scheduler.
	 *
	 * @since  1.5.1
	 * @return mixed
	 */
	public function get() {
		$settings = $this->core->settings->get_settings();

		$available = $this->get_available();

		if ( ! empty( $settings['scheduler'] ) ) {
			return $settings['scheduler'];
		} elseif ( array_key_exists( 'cron', $available ) ) {
			return 'cron';
		} elseif ( array_key_exists( 'wp-cron', $available ) ) {
			return 'wp-cron';
		} else {
			return false;
		}
	}

	/**
	 * Get available schedulers.
	 *
	 * @since 1.5.1
	 *
	 * @return array {
	 *     An array of available schedulers.
	 *
	 *     cron array {
	 *         @type string $title Cron
	 *     }
	 *     wp-cron array {
	 *         @type string $title WP Cron
	 *     }
	 */
	public function get_available() {
		if ( ! empty( $this->available ) ) {
			return $this->available;
		}

		$is_crontab_available = $this->core->test->is_crontab_available();
		$cli_support          = $this->core->test->get_cli_support();

		// We schedule crontab jobs requiring fetching a url (curl or file_get_contents).
		if ( $is_crontab_available && $cli_support['can_remote_get'] ) {
			$this->available['cron'] = array(
				'title' => 'Cron',
			);
		}

		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		if ( $is_wpcron_available ) {
			$this->available['wp-cron'] = array(
				'title' => 'WP Cron',
			);
		}

		return $this->available;
	}

	/**
	 * Check if a scheduler is available.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $scheduler Scheduler type ("cron" or "wp-cron").
	 * @return bool
	 */
	public function is_available( $scheduler ) {
		$available = $this->get_available();

		return array_key_exists( $scheduler, $available );
	}
}
