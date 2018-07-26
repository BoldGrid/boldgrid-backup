<?php
/**
 * File: class-boldgrid-backup-admin-cron.php
 *
 * @link https://www.boldgrid.com
 * @since 1.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Cron
 *
 * @since 1.2
 */
class Boldgrid_Backup_Admin_Cron {
	/**
	 * The core class object.
	 *
	 * @since 1.2
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Path to run-jobs.php
	 *
	 * @since 1.5.2
	 * @var   string
	 */
	public $run_jobs = 'cron/run-jobs.php';

	/**
	 * A cron secret used to validate unauthenticated crontab jobs.
	 *
	 * @since 1.6.1-rc.1
	 * @access private
	 * @var string
	 */
	private $cron_secret = null;

	/**
	 * Linux crontab entry version string.
	 *
	 * The version represents the plugin version string when the crontab entry format was changed.
	 *
	 * @var string
	 */
	public $crontab_version = '1.6.4';

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;
	}

	/**
	 * Add cron entry for backups from stored settings.
	 *
	 * @since 1.2
	 *
	 * @see Boldgrid_Backup_Admin_Cron::delete_cron_entries().
	 * @see Boldgrid_Backup_Admin_Cron::update_cron().
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 *
	 * @param  array $settings BoldGrid Backup settings.
	 * @return bool  Success.
	 */
	public function add_cron_entry( $settings = array() ) {
		if ( empty( $settings ) ) {
			$settings = $this->core->settings->get_settings();
		}

		// Delete existing backup cron jobs.
		$cron_status = $this->delete_cron_entries();

		// Initialize $days_scheduled_list.
		$days_scheduled_list = '';

		// Create an array of days index names.
		$days = array(
			'dow_sunday'    => 0,
			'dow_monday'    => 1,
			'dow_tuesday'   => 2,
			'dow_wednesday' => 3,
			'dow_thursday'  => 4,
			'dow_friday'    => 5,
			'dow_saturday'  => 6,
		);

		// Add scheduled days to the list.
		foreach ( $days as $index => $int ) {
			if ( isset( $settings['schedule'][ $index ] ) &&
			1 === $settings['schedule'][ $index ] ) {
					$days_scheduled_list .= $int . ',';
			}
		}

		// If no days are scheduled, then abort.
		if ( empty( $days_scheduled_list ) ) {
			return true;
		}

		// Strip trailing comma.
		$days_scheduled_list = rtrim( $days_scheduled_list, ',' );

		// Convert our WordPress time to Server time.
		$date = $this->core->time->get_settings_date( $settings );
		if ( false === $date ) {
			return false;
		}
		$server_timezone = $this->core->time->get_server_timezone();
		if ( false === $server_timezone ) {
			return false;
		}
		$date->setTimezone( $server_timezone );

		// Build cron job line in crontab format.
		$entry = $date->format( 'i G' ) . ' * * ';

		$entry .= $days_scheduled_list . ' php -qf "' . dirname( dirname( __FILE__ ) ) .
			'/boldgrid-backup-cron.php" mode=backup siteurl=' . get_site_url() . ' id=' .
			$this->core->get_backup_identifier() . ' secret=' . $this->get_cron_secret();

		// If not Windows, then also silence the cron job.
		if ( ! $this->core->test->is_windows() ) {
			$entry .= ' > /dev/null 2>&1';
		}

		// Update cron.
		$status = $this->update_cron( $entry );

		return $status;
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
	 * @param  array $settings BoldGrid Backup settings.
	 * @return bool
	 */
	public function add_all_crons( $settings ) {
		$success = false;

		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;
		$schedule  = ! empty( $settings['schedule'] ) ? $settings['schedule'] : null;

		if ( 'cron' === $scheduler && $this->core->scheduler->is_available( $scheduler ) && ! empty( $schedule ) ) {
			$this->core->scheduler->clear_all_schedules();

			$scheduled      = $this->add_cron_entry( $settings );
			$jobs_scheduled = $this->schedule_jobs();

			$success = $scheduled && $jobs_scheduled;

			if ( $success ) {
				$settings['crontab_version'] = $this->crontab_version;
				$settings['cron_secret']     = $this->get_cron_secret();
				update_site_option( 'boldgrid_backup_settings', $settings );
			}
		}

		return $success;
	}

	/**
	 * Add a cron job to restore (rollback) using the last backup.
	 *
	 * @since 1.2
	 *
	 * @see Boldgrid_Backup_Admin_Core::get_archive_list()
	 * @see Boldgrid_Backup_Admin_Core::execute_command()
	 * @see Boldgrid_Backup_Admin_Cron::delete_cron_entries().
	 * @see Boldgrid_Backup_Admin_Cron::update_cron().
	 * @see Boldgrid_Backup_Admin_Test::is_windows()
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 */
	public function add_restore_cron() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$archives         = $this->core->get_archive_list();

		// Use the first key to get info on the most recent archive.
		$archive_key = 0;

		$archive = $archives[ $archive_key ];

		$archive_filename = $archive['filename'];

		// Remove existing restore cron jobs.
		$this->delete_cron_entries( 'restore' );

		// Get the unix time for 5 minutes from now.
		$time_5_minutes_later = strtotime( $this->core->auto_rollback->testing_time );

		// Get the system's localized current time (HH:MM:SS), 5 minutes in the future.
		$system_time = $this->core->execute_command(
			'date "+%H|%M|%S|%a %d %b %Y %I:%M:00 %p %Z" -d "' . $this->core->auto_rollback->testing_time . '"'
		);

		// Split the time into hour, minute, and second.
		if ( ! empty( $system_time ) ) {
			list( $hour, $minute, $second, $system_time_iso ) = explode( '|', $system_time );
		}

		// Validate hour; use system hour, or the date code for hour ("G").
		if ( ! isset( $hour ) ) {
			$hour = 'G';
		}

		// Validate hour; use system hour, or the date code for minute ("i").
		if ( ! isset( $minute ) ) {
			$minute = 'i';
		}

		// Mark the deadline.
		if ( ! empty( $system_time_iso ) ) {
			$deadline = strtotime( $system_time_iso );
		} else {
			$deadline = $time_5_minutes_later;
		}

		// Build cron job line in crontab format.
		$entry = date( $minute . ' ' . $hour, $deadline ) . ' * * ' . date( 'w' ) . ' php -qf "' .
			dirname( dirname( __FILE__ ) ) . '/boldgrid-backup-cron.php" mode=restore siteurl=' .
			get_site_url() . ' id=' . $this->core->get_backup_identifier() . ' secret=' .
			$this->get_cron_secret() . ' archive_key=' . $archive_key .
			' archive_filename=' . $archive_filename;

		// If not Windows, then also silence the cron job.
		if ( ! $this->core->test->is_windows() ) {
			$entry .= ' > /dev/null 2>&1';
		}

		// Update cron.
		$status = $this->update_cron( $entry );

		// If cron job was added, then update the boldgrid_backup_pending_rollback option with time.
		if ( $status ) {
			$pending_rollback['deadline'] = $deadline;

			update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
		}
	}

	/**
	 * Read an entry from the system user crontab or wp-cron.
	 *
	 * @since 1.2
	 *
	 * @param string $mode The mode of the cron job; either "backup" or "restore".
	 * @return array An array containing the backup schedule.
	 */
	public function read_cron_entry( $mode = 'backup' ) {
		// Validate mode.
		if ( 'backup' !== $mode && 'restore' !== $mode ) {
			return array();
		}

		// Set a search pattern to match for our cron jobs.
		$pattern = dirname( dirname( __FILE__ ) ) . '/boldgrid-backup-cron.php" mode=' . $mode;

		// Get our cron jobs.
		$crontab_exploded = $this->get_all();
		if ( empty( $crontab_exploded ) ) {
			return array();
		}

		// If there's no cron jobs matching our pattern, abort.
		$crontab = implode( '', $crontab_exploded );
		if ( false === strpos( $crontab, $pattern ) ) {
			return array();
		}

		// Initialize $entry.
		$entry = '';

		foreach ( $crontab_exploded as $line ) {
			if ( false !== strpos( $line, $pattern ) ) {
				// Found a matching entry.
				$entry = trim( $line );

				break;
			}
		}

		$schedule = $this->get_schedule( $entry );

		return $schedule;
	}

	/**
	 * Schedule "run_jobs".
	 *
	 * This hook will run every 5 minutes and run one job at a time, such as
	 * upload to a remote storage provider.
	 *
	 * This method is usually ran after saving the BoldGrid Backup settings. If
	 * after save cron is our scheduler, then we need to make sure we have
	 * the "run_jobs" wp-cron scheduled.
	 *
	 * @since 1.5.2
	 *
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 */
	public function schedule_jobs() {
		$entry = sprintf(
			'*/5 * * * * php -qf "%1$s/%2$s" siteurl=%3$s id=%4$s secret=%5$s > /dev/null 2>&1',
			dirname( dirname( __FILE__ ) ),
			$this->run_jobs,
			get_site_url(),
			$this->core->get_backup_identifier(),
			$this->get_cron_secret()
		);

		return $this->update_cron( $entry );
	}

	/**
	 * Update or add an entry to the system user crontab or wp-cron.
	 *
	 * @since 1.2
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $entry A cron entry.
	 * @return bool Success.
	 */
	public function update_cron( $entry ) {

		// If no entry was passed, then abort.
		if ( empty( $entry ) ) {
			return false;
		}

		if ( $this->entry_exists( $entry ) ) {
			return true;
		}

		// Check if the backup directory is configured.
		if ( ! $this->core->backup_dir->get() ) {
			return false;
		}

		$crontab = $this->get_all( true );

		$crontab .= "\n" . $entry . "\n";

		$crontab_written = $this->write_crontab( $crontab );

		return $crontab_written && $this->entry_exists( $entry );
	}

	/**
	 * Write the crontab.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $crontab A string of crons, similar to raw output of crontab -l.
	 * @return bool
	 */
	public function write_crontab( $crontab ) {
		// Strip extra line breaks.
		$crontab = str_replace( "\n\n", "\n", $crontab );

		// Trim the crontab.
		$crontab = trim( $crontab );

		// Add a line break at the end of the file.
		$crontab .= "\n";

		// Get the backup directory path.
		$backup_directory = $this->core->backup_dir->get();

		// Check if the backup directory is writable.
		if ( ! $this->core->wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Save the temp crontab to file.
		$temp_crontab_path = $backup_directory . '/crontab.' . microtime( true ) . '.tmp';

		$this->core->wp_filesystem->put_contents( $temp_crontab_path, $crontab, 0600 );

		// Check if the defaults file was written.
		if ( ! $this->core->wp_filesystem->exists( $temp_crontab_path ) ) {
			return false;
		}

		// Write crontab.
		$command = 'crontab ' . $temp_crontab_path;

		$crontab = $this->core->execute_command( $command, null, $success );

		// Remove temp crontab file.
		$deleted = $this->core->wp_filesystem->delete( $temp_crontab_path, false, 'f' );

		return $success && $deleted;
	}

	/**
	 * Delete boldgrid-backup cron entries from the system user crontab.
	 *
	 * @since 1.2
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param  string $mode Please see in-method comments when the $pattern is
	 *                      configured.
	 * @return bool         Success.
	 */
	public function delete_cron_entries( $mode = '' ) {
		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();

		// If crontab is not available, then abort.
		if ( ! $is_crontab_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( ! $this->core->backup_dir->get() ) {
			return false;
		}

		/*
		 * Configure our pattern.
		 *
		 * When this method was initially written, $mode was either
		 * empty (defaulting to "backup") or "restore", hence the first two
		 * conditionals below.
		 *
		 * As of @1.5.2, you can pass any other string to this method, such as
		 * "cron/run-jobs.php", so that the pattern will become
		 * /home/user/public_html/wp-content/plugins/boldgrid-backup/cron/run-jobs.php
		 *
		 * As of @1.6.0 you can pass true as the $mode so that nothing else is
		 * added to the pattern and ALL crons for this site will be removed.
		 */
		$pattern = BOLDGRID_BACKUP_PATH . '/';
		if ( '' === $mode ) {
			$pattern .= 'boldgrid-backup-cron.php" mode=';
		} elseif ( 'restore' === $mode ) {
			$pattern .= 'boldgrid-backup-cron.php" mode=restore';
		} elseif ( true !== $mode ) {
			$pattern .= $mode;
		}

		// Use either crontab or wp-cron.
		if ( $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->core->execute_command( $command, null, $success );

			// If the command to retrieve crontab failed, then abort.
			if ( ! $success ) {
				return false;
			}

			// If no entries exist, then return success.
			if ( false === strpos( $crontab, $pattern ) ) {
				return true;
			}

			// Remove lines matching the pattern.
			$crontab_exploded = explode( "\n", $crontab );

			$crontab = '';

			foreach ( $crontab_exploded as $line ) {
				if ( false === strpos( $line, $pattern ) ) {
					$line     = trim( $line );
					$crontab .= $line . "\n";
				}
			}

			// Get the backup directory path.
			$backup_directory = $this->core->backup_dir->get();

			// Connect to the WordPress Filesystem API.
			global $wp_filesystem;

			// Check if the backup directory is writable.
			if ( ! $wp_filesystem->is_writable( $backup_directory ) ) {
				return false;
			}

			// Save the temp crontab to file.
			$temp_crontab_path = $backup_directory . '/crontab.' . microtime( true ) . '.tmp';

			// Save a temporary file for crontab.
			$wp_filesystem->put_contents( $temp_crontab_path, $crontab, 0600 );

			// Check if the defaults file was written.
			if ( ! $wp_filesystem->exists( $temp_crontab_path ) ) {
				return false;
			}

			// Write crontab.
			$command = 'crontab ' . $temp_crontab_path;

			$crontab = $this->core->execute_command( $command, null, $success );

			// Remove temp crontab file.
			$wp_filesystem->delete( $temp_crontab_path, false, 'f' );
		}

		return true;
	}

	/**
	 * Delete one entry from the crontab.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $entry Crontab entry.
	 * @return bool True if the entry does not exist or was deleted successfully.
	 */
	public function entry_delete( $entry ) {
		if ( ! $this->entry_exists( $entry ) ) {
			return true;
		}

		$all_entries = $this->get_all();

		$key = array_search( $entry, $all_entries, true );

		if ( false !== $key ) {
			unset( $all_entries[ $key ] );
		}

		$all_entries = implode( "\n", $all_entries );

		return $this->write_crontab( $all_entries ) && ! $this->entry_exists( $entry );
	}

	/**
	 * Delete all cron entries that contain a string.
	 *
	 * @since 1.6.5
	 *
	 * @param string $string The string to look for.
	 */
	public function entry_delete_contains( $string ) {
		$all_entries = $this->get_all();

		if ( ! is_array( $all_entries ) ) {
			return;
		}

		foreach ( $all_entries as $entry ) {
			if ( false !== strpos( $entry, $string ) ) {
				$this->entry_delete( $entry );
			}
		}
	}

	/**
	 * Determine if an entry exists in the crontab.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $entry Crontab entry.
	 * @return bool
	 */
	public function entry_exists( $entry ) {
		$all_entries = $this->get_all();

		return false !== array_search( $entry, $all_entries, true );
	}

	/**
	 * Get all entries in cron.
	 *
	 * @since 1.5.2
	 *
	 * @param  bool $raw Return a string of crons when true, an array when false.
	 * @return mixed
	 */
	public function get_all( $raw = false ) {
		/*
		 * Cron is not available on Windows.
		 *
		 * It would be clean to call is_crontab_available(), but that method
		 * uses this method, and would result in an infinite loop.
		 */
		if ( $this->core->test->is_windows() ) {
			return false;
		}

		$command = 'crontab -l';
		$crontab = $this->core->execute_command( $command, null, $success );

		if ( ! $success ) {
			return false;
		}

		return $raw ? $crontab : explode( "\n", $crontab );
	}

	/**
	 * Get all of our cron jobs.
	 *
	 * Similar to self::get_all, except only returns crons belonging to this
	 * installation.
	 *
	 * @since 1.5.2
	 *
	 * @return array
	 */
	public function get_our_crons() {
		$our = array();
		$all = $this->get_all();

		if ( empty( $all ) ) {
			return $our;
		}

		foreach ( $all as $cron ) {
			if ( false !== strpos( $cron, BOLDGRID_BACKUP_PATH ) ) {
				$our[] = $cron;
			}
		}

		return $our;
	}

	/**
	 * Read a line from the cron and return the schedule.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $cron_line An entry from cron.
	 * @return array Please see the $schedule initialized early in this method.
	 */
	public function get_schedule( $cron_line ) {
		// Initialize $schedule.
		$schedule = array(
			'dow_sunday'    => 0,
			'dow_monday'    => 0,
			'dow_tuesday'   => 0,
			'dow_wednesday' => 0,
			'dow_thursday'  => 0,
			'dow_friday'    => 0,
			'dow_saturday'  => 0,
			'tod_h'         => null,
			'tod_m'         => null,
			'tod_a'         => null,
		);

		if ( empty( $cron_line ) ) {
			return $schedule;
		}

		// Parse cron schedule.
		preg_match_all( '/([0-9*]+)(,([0-9*])+)*? /', $cron_line, $matches );

		// Minute.
		if ( isset( $matches[1][0] ) && is_numeric( $matches[1][0] ) ) {
			$schedule['tod_m'] = intval( $matches[1][0] );
		} else {
			return array();
		}

		// Hour.
		if ( isset( $matches[1][1] ) && is_numeric( $matches[1][1] ) ) {
			$schedule['tod_h'] = intval( $matches[1][1] );
		} else {
			return array();
		}

		// Convert from 24H to 12H time format.
		$unix_time = strtotime( $schedule['tod_h'] . ':' . $schedule['tod_m'] );

		$schedule['tod_h'] = intval( date( 'g', $unix_time ) );
		$schedule['tod_a'] = date( 'A', $unix_time );

		// Days of the week.
		if ( isset( $matches[0][4] ) ) {
			$days = explode( ',', $matches[0][4] );
			foreach ( $days as $day ) {
				switch ( $day ) {
					case 0:
						$schedule['dow_sunday'] = 1;
						break;
					case 1:
						$schedule['dow_monday'] = 1;
						break;
					case 2:
						$schedule['dow_tuesday'] = 1;
						break;
					case 3:
						$schedule['dow_wednesday'] = 1;
						break;
					case 4:
						$schedule['dow_thursday'] = 1;
						break;
					case 5:
						$schedule['dow_friday'] = 1;
						break;
					case 6:
						$schedule['dow_saturday'] = 1;
						break;
					default:
						break;
				}
			}
		}

		return $schedule;
	}

	/**
	 * Print cron report.
	 *
	 * @since 1.2
	 *
	 * @param array $archive_info An array of archive file information.
	 */
	public function print_cron_report( $archive_info ) {
		// Validate mode.
		if ( empty( $archive_info['mode'] ) ) {
			wp_die( esc_html__( 'Error: A mode was not specified.', 'boldgrid-backup' ) );
		}

		$valid_modes = array(
			'backup',
			'restore',
		);

		if ( ! in_array( $archive_info['mode'], $valid_modes, true ) ) {
			printf(
				// translators: 1: Archive mode ("backup" or "restore").
				esc_html__( 'Error: Invalid mode "%s".', 'boldgrid-backup' ),
				$archive_info['mode'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			);
			wp_die();
		}

		// Create action name.
		switch ( $archive_info['mode'] ) {
			case 'backup':
				$action_name = 'creating';
				break;

			case 'restore':
				$action_name = 'restoring';
				break;

			default:
				$action_name = 'handling';
				break;
		}

		// Print report.
		if ( ! empty( $archive_info['error'] ) ) {
			// Error.
			printf(
				esc_html__( 'There was an error $s backup archive file.', 'boldgrid-backup' ),
				$action_name // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			) . PHP_EOL;

			printf(
				// translators: 1: Error message.
				esc_html__( 'Error: %s', 'boldgrid-backup' ),
				$archive_info['error'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			) . PHP_EOL;

			if ( isset( $archive_info['error_message'] ) ) {
				printf(
					// translators: 1: Error message.
					esc_html__( 'Error Message: %s', 'boldgrid-backup' ),
					$archive_info['error_message'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				);
			}

			if ( isset( $archive_info['error_code'] ) ) {
				printf(
					' (%s)',
					esc_html( $archive_info['error_code'] )
				) . PHP_EOL;
			}
		} elseif ( ! empty( $archive_info['filesize'] ) || ! empty( $archive_info['dryrun'] ) ) {
			// Dry run.
			if ( ! empty( $archive_info['filepath'] ) ) {
				printf(
					// translators: 1: File path.
					esc_html__( 'File Path: %s', 'boldgrid-backup' ),
					$archive_info['filepath'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['filesize'] ) ) {
				printf(
					// translators: 1: File size.
					esc_html__( 'File Size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ) // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['total_size'] ) ) {
				printf(
					// translators: 1: Total backup size.
					esc_html__( 'Total size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['compressor'] ) ) {
				printf(
					// translators: 1: Compressor name.
					esc_html__( 'Compressor: %s', 'boldgrid-backup' ),
					$archive_info['compressor'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}

			// Show how long the website was paused for.
			if ( isset( $archive_info['db_duration'] ) ) {
				printf(
					esc_html( $this->core->configs['lang']['est_pause'] ),
					$archive_info['db_duration'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}

			if ( isset( $archive_info['duration'] ) ) {
				printf(
					// translators: 1: Backup duration.
					esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
					$archive_info['duration'] // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				) . PHP_EOL;
			}
		} else {
			// Unknown error.
			printf(
				// translators: 1: Backup action name.
				esc_html__(
					'There was an unknown error %s a backup archive file.',
					'boldgrid-backup'
				),
				$action_name // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			) . PHP_EOL;
		}
	}

	/**
	 * Get the cron secret used to validate unauthenticated crontab jobs.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @see BoldGrid_Backup_Admin_Settings::get_settings()
	 *
	 * @return string
	 */
	public function get_cron_secret() {
		if ( empty( $this->cron_secret ) ) {
			$settings = $this->core->settings->get_settings( true );

			if ( empty( $settings['cron_secret'] ) ) {
				$settings['cron_secret'] = hash( 'sha256', openssl_random_pseudo_bytes( 21 ) );

				update_site_option( 'boldgrid_backup_settings', $settings );
			}

			$this->cron_secret = $settings['cron_secret'];
		}

		return $this->cron_secret;
	}

	/**
	 * Validate an unauthenticated wp_ajax_nopriv_ call by backup id and cron secret.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @uses $_GET['id']
	 * @uses $_GET['secret']
	 *
	 * @see current_user_can()
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 *
	 * @return bool
	 */
	public function is_valid_call() {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		$backup_id_match = ! empty( $_GET['id'] ) &&
			$this->core->get_backup_identifier() === sanitize_key( $_GET['id'] );

		$cron_secret_match = ! empty( $_GET['secret'] ) &&
			$this->get_cron_secret() === $_GET['secret'];

		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		return current_user_can( 'update_plugins' ) || ( $backup_id_match && $cron_secret_match );
	}

	/**
	 * Upgrade crontab entries, if not already upgraded.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @see BoldGrid_Backup_Admin_Settings::get_settings()
	 * @see BoldGrid_Backup_Admin_Cron::add_all_crons()
	 *
	 * @return bool Returns TRUE only if an upgrade was performed.
	 */
	public function upgrade_crontab_entries() {
		$upgraded = false;
		$settings = $this->core->settings->get_settings( true );

		if ( empty( $settings['crontab_version'] ) ||
			$this->crontab_version !== $settings['crontab_version'] ) {
				// Delete and recreate the crontab entries.
				$upgraded = $this->add_all_crons( $settings );

			if ( $upgraded ) {
				/**
					 * Action when the crontab entry upgrade is successfully completed.
					 *
					 * @since 1.6.1-rc.1
					 *
					 * @param string The new crontab entry version.
					 */
				do_action(
					'boldgrid_backup_upgrade_crontab_entries_complete',
					$this->crontab_version
				);
			}
		}

		return $upgraded;
	}

	/**
	 * Hook into "wp_ajax_nopriv_boldgrid_backup_run_backup" and generate backup.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @see Boldgrid_Backup_Admin_Cron::is_valid_call()
	 *
	 * @return array An array of archive file information.
	 */
	public function backup() {
		if ( ! $this->is_valid_call() ) {
			wp_die( esc_html__( 'Error: Invalid request.', 'boldgrid-backup' ) );
		}

		$archive_info = $this->core->archive_files( true );

		return $archive_info;
	}

	/**
	 * Hook into "wp_ajax_nopriv_boldgrid_backup_run_restore" and restores from backup.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @see Boldgrid_Backup_Admin_Cron::is_valid_call()
	 *
	 * @return array An array of archive file information.
	 */
	public function restore() {
		if ( ! $this->is_valid_call() ) {
			wp_die( esc_html__( 'Error: Invalid request.', 'boldgrid-backup' ) );
		}

		$archive_info = array(
			'error' => __( 'Could not perform restoration from cron job.', 'boldgrid-backup' ),
		);

		if ( $this->core->restore_helper->prepare_restore() ) {
			$archive_info = $this->core->restore_archive_file();
		}

		return $archive_info;
	}
}
