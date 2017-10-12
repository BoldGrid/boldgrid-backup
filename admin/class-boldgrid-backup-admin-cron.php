<?php
/**
 * The admin-specific cron functionality of the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.2
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin cron class.
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
	 * Path to run_jobs.php
	 *
	 * @since 1.5.2
	 * @var   string
	 */
	public $run_jobs = 'cron/run_jobs.php';

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
	 *
	 * @param  array $settings
	 * @return bool  Success.
	 */
	public function add_cron_entry( $settings = array() ) {
		if( empty( $settings ) ) {
			$settings = $this->core->settings->get_settings();
		}

		// Delete existing backup cron jobs.
		$cron_status = $this->delete_cron_entries();

		// Initialize $days_scheduled_list.
		$days_scheduled_list = '';

		// Create an array of days index names.
		$days = array(
			'dow_sunday' => 0,
			'dow_monday' => 1,
			'dow_tuesday' => 2,
			'dow_wednesday' => 3,
			'dow_thursday' => 4,
			'dow_friday' => 5,
			'dow_saturday' => 6,
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

		// Build cron job line in crontab format.
		$entry = date( 'i G',
			strtotime(
				$settings['schedule']['tod_h'] . ':' . $settings['schedule']['tod_m'] . ' ' .
				$settings['schedule']['tod_a']
			)
		) . ' * * ';

		$entry .= $days_scheduled_list . ' php -qf "' . dirname( dirname( __FILE__ ) ) .
		'/boldgrid-backup-cron.php" mode=backup HTTP_HOST=' . $_SERVER['HTTP_HOST'];

		// If not Windows, then also silence the cron job.
		if ( ! $this->core->test->is_windows() ) {
			$entry .= ' > /dev/null 2>&1';
		}

		// Update cron.
		$status = $this->update_cron( $entry );

		return $status;
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
	 * @see Boldgrid_Backup_Admin_Settings::delete_rollback_option().
	 * @see Boldgrid_Backup_Admin_Settings::get_settings().
	 * @see Boldgrid_Backup_Admin_Test::is_windows()
	 *
	 * @return null
	 */
	public function add_restore_cron() {
		$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		$archives = $this->core->get_archive_list();

		// Use the first key to get info on the most recent archive.
		$archive_key = 0;

		$archive = $archives[ $archive_key ];

		$archive_filename = $archive['filename'];

		// Remove existing restore cron jobs.
		$this->delete_cron_entries( 'restore' );

		// Get the unix time for 5 minutes from now.
		$time_5_minutes_later = strtotime( '+5 MINUTES' );

		// Get the system's localized current time (HH:MM:SS), 5 minutes in the future.
		$system_time = $this->core->execute_command(
			'date "+%H|%M|%S|%a %d %b %Y %I:%M:00 %p %Z" -d "+5 minutes"'
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
		$entry = date( $minute . ' ' . $hour, $deadline ) . ' * * ' . date( 'w' );

		$entry .= ' php -qf "' . dirname( dirname( __FILE__ ) ) .
		'/boldgrid-backup-cron.php" mode=restore HTTP_HOST=' . $_SERVER['HTTP_HOST'];

		$entry .= ' archive_key=' . $archive_key . ' archive_filename=' . $archive_filename;

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

		return;
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

		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();
		if ( ! $is_crontab_available ) {
			return array();
		}

		// Set a search pattern to match for our cron jobs.
		$pattern = dirname( dirname( __FILE__ ) ) . '/boldgrid-backup-cron.php" mode=' . $mode;

		$crontab_exploded = $this->get_all();

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
	 */
	public function schedule_jobs() {
		$entry = sprintf( '*/5 * * * * php -qf "%1$s/%2$s" > /dev/null 2>&1', dirname( dirname( __FILE__ ) ), $this->run_jobs );

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

		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( ! $is_crontab_available && ! $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( ! $this->core->backup_dir->get() ) {
			return false;
		}

		// Use either crontab or wp-cron.
		if ( $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->core->execute_command( $command );

			// Add entry to crontab to the end, if it does not already exist.
			if ( false === strpos( $crontab, $entry ) ) {
				$crontab .= "\n" . $entry . "\n";
			}

			// Strip extra line breaks.
			$crontab = str_replace( "\n\n", "\n", $crontab );

			// Trim the crontab.
			$crontab = trim( $crontab );

			// Add a line break at the end of the file.
			$crontab .= "\n";

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

			// Check for failure.
			if ( false === $crontab || ! $success ) {
				return false;
			}
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return true;
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

		// Check if wp-cron is available.
		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( ! $is_crontab_available && ! $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( ! $this->core->backup_dir->get() ) {
			return false;
		}

		/*
		 * Configure our pattern.
		 *
		 * When this method was initiall written, $mode was either
		 * empty (defaulting to "backup") or "restore", hence the first two
		 * conditionals below.
		 *
		 * As of @1.5.2, you can pass any other string to this method, such as
		 * "cron/run_jobs.php", so that the pattern will become
		 * /home/user/public_html/wp-content/plugins/boldgrid-backup/cron/run_jobs.php
		 */
		$pattern = dirname( dirname( __FILE__ ) ) . '/';
		if( '' === $mode ) {
			$pattern .= 'boldgrid-backup-cron.php" mode=';
		} elseif( 'restore' === $mode ) {
			$pattern .= 'boldgrid-backup-cron.php" mode=restore';
		} else {
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
					$line = trim( $line );
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
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return true;
	}

	/**
	 * Get all entries in cron.
	 *
	 * @since 1.5.2
	 *
	 * @return array
	 */
	public function get_all() {
		$is_crontab_available = $this->core->test->is_crontab_available();

		if( ! $is_crontab_available ) {
			return array();
		}

		$command = 'crontab -l';
		$crontab = $this->core->execute_command( $command, null, $success );

		// If the command to retrieve crontab failed, then return an empty array.
		if ( ! $success ) {
			return array();
		}

		// Explode the crontab into an array.
		$crontab_exploded = explode( "\n", $crontab );

		return $crontab_exploded;
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
			'dow_sunday' => 0,
			'dow_monday' => 0,
			'dow_tuesday' => 0,
			'dow_wednesday' => 0,
			'dow_thursday' => 0,
			'dow_friday' => 0,
			'dow_saturday' => 0,
			'tod_h' => null,
			'tod_m' => null,
			'tod_a' => null,
		);

		if( empty( $cron_line ) ) {
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
					case 0 :
						$schedule['dow_sunday'] = 1;
						break;
					case 1 :
						$schedule['dow_monday'] = 1;
						break;
					case 2 :
						$schedule['dow_tuesday'] = 1;
						break;
					case 3 :
						$schedule['dow_wednesday'] = 1;
						break;
					case 4 :
						$schedule['dow_thursday'] = 1;
						break;
					case 5 :
						$schedule['dow_friday'] = 1;
						break;
					case 6 :
						$schedule['dow_saturday'] = 1;
						break;
					default :
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
			esc_html_e( 'Error: A mode was not specified.', 'boldgrid-backup' );
			wp_die();
		}

		$valid_modes = array(
			'backup',
			'restore',
		);

		if ( ! in_array( $archive_info['mode'], $valid_modes, true ) ) {
			printf(
				esc_html__( 'Error: Invalid mode "%s".', 'boldgrid-backup' ),
				$archive_info['mode']
			);
			wp_die();
		}

		// Create action name.
		switch ( $archive_info['mode'] ) {
			case 'backup' :
				$action_name = 'creating';
				break;

			case 'restore' :
				$action_name = 'restoring';
				break;

			default :
				$action_name = 'handling';
				break;
		}

		// Print report.
		if ( ! empty( $archive_info['error'] ) ) {
			// Error.
			printf(
				esc_html__( 'There was an error $s backup archive file.', 'boldgrid-backup' ),
				$action_name
			);

			echo PHP_EOL;

			printf(
				esc_html__( 'Error: %s', 'boldgrid-backup' ),
				$archive_info['error']
			);

			echo PHP_EOL;

			if ( isset( $archive_info['error_message'] ) ) {
				printf(
					esc_html__( 'Error Message: %s', 'boldgrid-backup' ),
					$archive_info['error_message']
				);
			}

			if ( isset( $archive_info['error_code'] ) ) {
				printf(
					' (%s)',
					$archive_info['error_code']
				);
			}

			echo PHP_EOL;
		} elseif ( ! empty( $archive_info['filesize'] ) || ! empty( $archive_info['dryrun'] ) ) {
			// Dry run.
			if ( ! empty( $archive_info['filepath'] ) ) {
				printf(
					esc_html__( 'File Path: %s', 'boldgrid-backup' ),
					$archive_info['filepath']
				);

				echo PHP_EOL;
			}

			if ( ! empty( $archive_info['filesize'] ) ) {
				printf(
					esc_html__( 'File Size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] )
				);

				echo PHP_EOL;
			}

			if ( ! empty( $archive_info['total_size'] ) ) {
				printf(
					esc_html__( 'Total size: %s', 'boldgrid-backup' ),
					Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] )
				);

				echo PHP_EOL;
			}

			if ( ! empty( $archive_info['compressor'] ) ) {
				printf(
					esc_html__( 'Compressor: %s', 'boldgrid-backup' ),
					$archive_info['compressor']
				);

				echo PHP_EOL;
			}

			// Show how long the website was paused for.
			if ( isset( $archive_info['db_duration'] ) ) {
				printf( $this->core->configs['lang']['est_pause'], $archive_info['db_duration'] );
				echo PHP_EOL;
			}

			if ( isset( $archive_info['duration'] ) ) {
				printf(
					esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
					$archive_info['duration']
				);

				echo PHP_EOL;
			}
		} else {
			// Unknown error.
			printf(
				esc_html__(
					'There was an unknown error %s a backup archive file.',
					'boldgrid-backup'
				),
				$action_name
			);

			echo PHP_EOL;
		}
	}
}
