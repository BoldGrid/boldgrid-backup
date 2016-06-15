<?php
/**
 * The admin-specific utilities methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin settings class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Settings {
	/**
	 * The core class object.
	 *
	 * @since 1.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Config $core Config class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;
	}

	/**
	 * Get settings using defaults.
	 *
	 * @since 1.0
	 *
	 * @return array An array of settings.
	 */
	public function get_settings() {
		// Get settings.
		if ( true === is_multisite() ) {
			$settings = get_site_option( 'boldgrid_backup_settings' );
		} else {
			$settings = get_option( 'boldgrid_backup_settings' );
		}

		// Parse settings.
		if ( false === empty( $settings['schedule'] ) ) {
			// Update schedule format.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = ( false ===
				empty( $settings['schedule']['dow_sunday'] ) ? 1 : 0 );
			$settings['schedule']['dow_monday'] = ( false ===
				empty( $settings['schedule']['dow_monday'] ) ? 1 : 0 );
			$settings['schedule']['dow_tuesday'] = ( false ===
				empty( $settings['schedule']['dow_tuesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_wednesday'] = ( false ===
				empty( $settings['schedule']['dow_wednesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_thursday'] = ( false ===
				empty( $settings['schedule']['dow_thursday'] ) ? 1 : 0 );
			$settings['schedule']['dow_friday'] = ( false ===
				empty( $settings['schedule']['dow_friday'] ) ? 1 : 0 );
			$settings['schedule']['dow_saturday'] = ( false ===
				empty( $settings['schedule']['dow_saturday'] ) ? 1 : 0 );

			// Time of day.
			$settings['schedule']['tod_h'] = ( false === empty( $settings['schedule']['tod_h'] ) ? $settings['schedule']['tod_h'] : mt_rand( 1, 5 ) );
			$settings['schedule']['tod_m'] = ( false === empty( $settings['schedule']['tod_m'] ) ? $settings['schedule']['tod_m'] : mt_rand( 1, 59 ) );
			$settings['schedule']['tod_a'] = ( false === empty( $settings['schedule']['tod_a'] ) ? $settings['schedule']['tod_a'] : 'AM' );

			// Other settings.
			$settings['notifications']['backup'] = ( false ===
				isset( $settings['notifications']['backup'] ) || false ===
				empty( $settings['notifications']['backup'] ) ? 1 : 0 );
			$settings['notifications']['restore'] = ( false ===
				isset( $settings['notifications']['restore'] ) || false ===
				empty( $settings['notifications']['restore'] ) ? 1 : 0 );
			$settings['auto_backup'] = ( false === isset( $settings['auto_backup'] ) ||
				false === empty( $settings['auto_backup'] ) ? 1 : 0 );
			$settings['auto_rollback'] = ( false === isset( $settings['auto_rollback'] ) ||
				false === empty( $settings['auto_rollback'] ) ? 1 : 0 );
		} else {
			// Define defaults.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = 0;
			$settings['schedule']['dow_monday'] = 0;
			$settings['schedule']['dow_tuesday'] = 0;
			$settings['schedule']['dow_wednesday'] = 0;
			$settings['schedule']['dow_thursday'] = 0;
			$settings['schedule']['dow_friday'] = 0;
			$settings['schedule']['dow_saturday'] = 0;

			// Time of day.
			$settings['schedule']['tod_h'] = mt_rand( 1, 5 );
			$settings['schedule']['tod_m'] = mt_rand( 1, 59 );
			$settings['schedule']['tod_a'] = 'AM';

			// Other settings.
			$settings['retention_count'] = 5;
			$settings['notifications']['backup'] = 1;
			$settings['notifications']['restore'] = 1;
			$settings['auto_backup'] = 1;
			$settings['auto_rollback'] = 1;
		}

		// Check cron for schedule.
		$cron_schedule = $this->read_cron_entry();

		// If a cron schedule was found, then merge the settings.
		if ( false === empty( $cron_schedule ) ) {
			$settings['schedule'] = array_merge( $settings['schedule'], $cron_schedule );
		}

		// Return the settings array.
		return $settings;
	}

	/**
	 * Read an entry from the system user crontab or wp-cron.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @param string $mode The mode of the cron job; either "backup" or "restore".
	 * @return array An array containing the backup schedule.
	 */
	private function read_cron_entry( $mode = 'backup' ) {
		// Validate mode.
		if ( 'backup' !== $mode && 'restore' !== $mode ) {
			return array();
		}

		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		// If crontab or wp-cron is not available, then return an empty array.
		if ( true !== $is_crontab_available && true !== $is_wpcron_available ) {
			return array();
		}

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

		// Set a search pattern to match for our cron jobs.
		$pattern = 'boldgrid-backup-cron.php" mode=' . $mode;

		// Use either crontab or wp-cron.
		if ( true === $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->core->execute_command( $command, null, $success );

			// If the command to retrieve crontab failed, then return an empty array.
			if ( true !== $success ) {
				return array();
			}

			// If no entries exist, then return an empty array.
			if ( false === strpos( $crontab, $pattern ) ) {
				return array();
			}

			// Explode the crontab into an array.
			$crontab_exploded = explode( "\n", $crontab );

			// Initialize $entry.
			$entry = '';

			foreach ( $crontab_exploded as $line ) {
				if ( false !== strpos( $line, $pattern ) ) {
					// Found a matching entry.
					$entry = trim( $line );

					break;
				}
			}

			// If a match was found, then get the schedule.
			if ( false === empty( $entry ) ) {
				// Parse cron schedule.
				preg_match_all( '/([0-9*]+)(,([0-9*])+)*? /', $entry, $matches );

				// Minute.
				if ( true === isset( $matches[1][0] ) && true === is_numeric( $matches[1][0] ) ) {
					$schedule['tod_m'] = intval( $matches[1][0] );
				} else {
					return array();
				}

				// Hour.
				if ( true === isset( $matches[1][1] ) && true === is_numeric( $matches[1][1] ) ) {
					$schedule['tod_h'] = intval( $matches[1][1] );
				} else {
					return array();
				}

				// Convert from 24H to 12H time format.
				$unix_time = strtotime( $schedule['tod_h'] . ':' . $schedule['tod_m'] );

				$schedule['tod_h'] = intval( date( 'g', $unix_time ) );
				$schedule['tod_a'] = date( 'A', $unix_time );

				// Days of the week.
				if ( true === isset( $matches[0][4] ) ) {
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
			}
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return $schedule;
	}

	/**
	 * Update or add an entry to the system user crontab or wp-cron.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $entry A cron entry.
	 * @return bool Success.
	 */
	private function update_cron( $entry ) {
		// If no entry was passed, then abort.
		if ( true === empty( $entry ) ) {
			return false;
		}

		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( true !== $is_crontab_available && true !== $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( false === $this->core->config->get_backup_directory() ) {
			return false;
		}

		// Use either crontab or wp-cron.
		if ( true === $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->core->execute_command( $command );

			// Check for failure.
			if ( false === $crontab ) {
				return false;
			}

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
			$backup_directory = $this->core->config->get_backup_directory();

			// Connect to the WordPress Filesystem API.
			global $wp_filesystem;

			// Check if the backup directory is writable.
			if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
				return false;
			}

			// Save the temp crontab to file.
			$temp_crontab_path = $backup_directory . '/crontab.' . microtime( true ) . '.tmp';

			$wp_filesystem->put_contents( $temp_crontab_path, $crontab, 0600 );

			// Check if the defaults file was written.
			if ( false === $wp_filesystem->exists( $temp_crontab_path ) ) {
				return false;
			}

			// Write crontab.
			$command = 'crontab ' . $temp_crontab_path;

			$crontab = $this->core->execute_command( $command, null, $success );

			// Remove temp crontab file.
			$wp_filesystem->delete( $temp_crontab_path, false, 'f' );

			// Check for failure.
			if ( false === $crontab || true !== $success ) {
				return false;
			}
		} else {
			// Use wp-cron.
			// @todo Write wp-cron code here.
		}

		return true;
	}

	/**
	 * Delete boldgrid-backup cron entries from the system user crontab or wp-cron.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $mode If "restore" is specified, then only the restore cron jobs are removed,
	 * else all backup and restore cron jobs will be removed.
	 * @return bool Success.
	 */
	public function delete_cron_entries( $mode = '' ) {
		// Check if crontab is available.
		$is_crontab_available = $this->core->test->is_crontab_available();

		// Check if wp-cron is available.
		$is_wpcron_available = $this->core->test->wp_cron_enabled();

		// If crontab or wp-cron is not available, then abort.
		if ( true !== $is_crontab_available && true !== $is_wpcron_available ) {
			return false;
		}

		// Check if the backup directory is configured.
		if ( false === $this->core->config->get_backup_directory() ) {
			return false;
		}

		// Set a search pattern to match for our cron jobs.
		$pattern = 'boldgrid-backup-cron.php';

		// If the mode "restore" is specified, then only target the restore cron job entries.
		if ( 'restore' === $mode ) {
			$pattern .= '" mode=restore';
		}

		// Use either crontab or wp-cron.
		if ( true === $is_crontab_available ) {
			// Use crontab.
			// Read crontab.
			$command = 'crontab -l';

			$crontab = $this->core->execute_command( $command, null, $success );

			// If the command to retrieve crontab failed, then abort.
			if ( true !== $success ) {
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
			$backup_directory = $this->core->config->get_backup_directory();

			// Connect to the WordPress Filesystem API.
			global $wp_filesystem;

			// Check if the backup directory is writable.
			if ( true !== $wp_filesystem->is_writable( $backup_directory ) ) {
				return false;
			}

			// Save the temp crontab to file.
			$temp_crontab_path = $backup_directory . '/crontab.' . microtime( true ) . '.tmp';

			// Save a temporary file for crontab.
			$wp_filesystem->put_contents( $temp_crontab_path, $crontab, 0600 );

			// Check if the defaults file was written.
			if ( false === $wp_filesystem->exists( $temp_crontab_path ) ) {
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
	 * Update settings.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @return bool Update success.
	 */
	private function update_settings() {
		// Verify nonce.
		check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

		// Check for settings update.
		if ( false === empty( $_POST['save_time'] ) ) {
			// Get settings.
			$settings = $this->get_settings();

			// Initialize $update_error.
			$update_error = false;

			// Initialize $days_scheduled.
			$days_scheduled = array();

			// Validate input for schedule.
			$indices = array(
				'dow_sunday',
				'dow_monday',
				'dow_tuesday',
				'dow_wednesday',
				'dow_thursday',
				'dow_friday',
				'dow_saturday',
				'tod_h',
				'tod_m',
				'tod_a',
			);

			foreach ( $indices as $index ) {
				// Determine input type.
				if ( 0 === strpos( $index, 'dow_' ) ) {
					$type = 'day';
				} elseif ( 'tod_h' === $index ) {
					$type = 'h';
				} elseif ( 'tod_m' === $index ) {
					$type = 'm';
				} elseif ( 'tod_a' === $index ) {
					$type = 'a';
				} else {
					// Unknown type.
					$type = '?';
				}

				if ( false === empty( $_POST[ $index ] ) ) {
					// Validate by type.
					switch ( $type ) {
						case 'day' :
							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// If day was scheduled, then track it.
							if ( 1 === $_POST[ $index ] ) {
								$days_scheduled[] = date( 'w', strtotime( str_replace( 'dow_', '', $index ) ) );
							}

							break;
						case 'h' :
							if ( $_POST[ $index ] < 1 || $_POST[ $index ] > 12 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							break;
						case 'm' :
							if ( $_POST[ $index ] < 0 || $_POST[ $index ] > 59 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// Pad left with 0.
							$_POST[ $index ] = str_pad( $_POST[ $index ], 2, '0', STR_PAD_LEFT );

							break;
						case 'a' :
							if ( 'AM' !== $_POST[ $index ] && 'PM' !== $_POST[ $index ] ) {
								// Error in input; unknown type.
								$update_error = true;
								break 2;
							}

							break;
						default :
							// Error in input; unknown type.
							$update_error = true;
							break 2;
					}

					// Update the setting value provided.
					$settings['schedule'][ $index ] = $_POST[ $index ];
				} elseif ( 'day' === $type ) {
					// Unassigned days.
					$settings['schedule'][ $index ] = 0;
				} else {
					// Error in input.
					$update_error = true;

					break;
				}
			}

			// Validate input for other settings.
			$settings['retention_count'] = ( true === isset( $_POST['retention_count'] ) ?
				intval( $_POST['retention_count'] ) : 5 );

			$settings['notifications']['backup'] = ( ( true === isset( $_POST['notify_backup'] ) &&
				'1' === $_POST['notify_backup'] ) ? 1 : 0 );

			$settings['notifications']['restore'] = ( ( true === isset( $_POST['notify_restore'] ) &&
				'1' === $_POST['notify_restore'] ) ? 1 : 0 );

			$settings['auto_backup'] = ( ( false === isset( $_POST['auto_backup'] ) ||
				'1' === $_POST['auto_backup'] ) ? 1 : 0 );

			$settings['auto_rollback'] = ( ( false === isset( $_POST['auto_rollback'] ) ||
				'1' === $_POST['auto_rollback'] ) ? 1 : 0 );

			// If no errors, then save the settings.
			if ( false === $update_error ) {
				// Record the update time.
				$settings['updated'] = time();

				// Attempt to update WP option.
				if ( true === is_multisite() ) {
					$update_status = update_site_option( 'boldgrid_backup_settings', $settings );
				} else {
					$update_status = update_option( 'boldgrid_backup_settings', $settings );
				}

				if ( true !== $update_status ) {
					// Failure.
					$update_error = true;

					do_action( 'boldgrid_backup_notice',
						'Invalid settings submitted.  Please try again.',
						'notice notice-error is-dismissible'
					);
				} else {
					// Delete existing backup cron jobs, and add the new cron entry.
					$cron_status = $this->add_cron_entry();
				}
			} else {
				// Interrupted by a previous error.
				do_action( 'boldgrid_backup_notice',
					'Invalid settings submitted.  Please try again.',
					'notice notice-error is-dismissible'
				);
			}
		}

		// If delete cron failed, then show a notice.
		if ( true !== $cron_status ) {
			$update_error = true;

			do_action( 'boldgrid_backup_notice',
				'An error occurred when modifying cron jobs.  Please try again.',
				'notice notice-error is-dismissible'
			);
		}

		// If there was no error, then show success notice.
		if ( false === $update_error ) {
			// Success.
			do_action( 'boldgrid_backup_notice',
				'Settings saved.',
				'updated settings-error notice is-dismissible'
			);
		}

		// Return success.
		return ! $update_error;
	}

	/**
	 * Add cron entry for backups from stored settings.
	 *
	 * @since 1.0
	 *
	 * @return bool Success.
	 */
	public function add_cron_entry() {
		// Get settings.
		$settings = $this->get_settings();

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
			if ( true === isset( $settings['schedule'][ $index ] ) &&
			1 === $settings['schedule'][ $index ] ) {
				$days_scheduled_list .= $int . ',';
			}
		}

		// If no days are scheduled, then abort.
		if ( true === empty( $days_scheduled_list ) ) {
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
		if ( false === $this->core->test->is_windows() ) {
			$entry .= ' > /dev/null 2>&1';
		}

		// Update cron.
		$status = $this->update_cron( $entry );

		return $status;
	}

	/**
	 * Add a cron job to restore (rollback) using the last backup.
	 *
	 * @since 1.0
	 *
	 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 * @see Boldgrid_Backup_Admin_Core::get_archive_list()
	 *
	 * @return null
	 */
	public function add_restore_cron() {
		// Get settings.
		$settings = $this->get_settings();

		// If auto-rollback is not enabled, then abort.
		if ( 1 !== $settings['auto_rollback'] ) {
			return;
		}

		// Determine if multisite.
		$is_multisite = is_multisite();

		// If a backup was not made prior to an update (from an update page), then abort.
		if ( true === $is_multisite ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );
		}

		if ( true === empty( $pending_rollback ) ) {
			return;
		}

		// Get the unix time for 5 minutes ago.
		$time_5_minutes_ago = strtotime( 'NOW - 5 MINUTES' );

		// If the boldgrid_backup_last_backup time is too old, then abort.
		if ( true === $is_multisite ) {
			$last_backup_time = get_site_option( 'boldgrid_backup_last_backup' );
		} else {
			$last_backup_time = get_option( 'boldgrid_backup_last_backup' );
		}

		if ( $last_backup_time < $time_5_minutes_ago ) {
			return;
		}

		// Get archive list.
		$archives = $this->core->get_archive_list();

		// Get the archive count.
		$archive_count = count( $archives );

		// If there are no archives, then abort.
		if ( $archive_count <= 0 ) {
			return;
		}

		// Use the last key to get info on the most recent archive.
		$archive_key = $archive_count - 1;

		$archive = $archives[ $archive_key ];

		$archive_filename = $archive['filename'];

		// If the backup file is too old, then abort.
		if ( $archive['lastmodunix'] < $time_5_minutes_ago ) {
			return;
		}

		// Remove existing restore cron jobs.
		$this->delete_cron_entries( 'restore' );

		// Get the unix time for 5 minutes from now.
		$time_5_minutes_later = strtotime( 'NOW + 5 MINUTES' );

		// Build cron job line in crontab format.
		$entry = date( 'i G', $time_5_minutes_later ) . ' * * ' . date( 'w' );

		$entry .= ' php -qf "' . dirname( dirname( __FILE__ ) ) .
		'/boldgrid-backup-cron.php" mode=restore HTTP_HOST=' . $_SERVER['HTTP_HOST'];

		$entry .= ' archive_key=' . $archive_key . ' archive_filename=' . $archive_filename;

		// If not Windows, then also silence the cron job.
		if ( false === $this->core->test->is_windows() ) {
			$entry .= ' > /dev/null 2>&1';
		}

		// Update cron.
		$status = $this->update_cron( $entry );

		// If cron job was added, then update the boldgrid_backup_pending_rollback option with time.
		if ( true === $status ) {
			if ( true === $is_multisite ) {
				$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

				$pending_rollback['deadline'] = $time_5_minutes_later;

				update_site_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
			} else {
				$pending_rollback = get_option( 'boldgrid_backup_pending_rollback' );

				$pending_rollback['deadline'] = $time_5_minutes_later;

				update_option( 'boldgrid_backup_pending_rollback', $pending_rollback );
			}
		}

		return;
	}

	/**
	 * Menu callback to display the Backup schedule page.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function page_backup_settings() {
		// Run the functionality tests.
		$is_functional = $this->core->test->get_is_functional();

		// If tests fail, then show an admin notice and abort.
		if ( false === $is_functional ) {
			do_action( 'boldgrid_backup_notice',
				'Functionality test has failed.  You can go to <a href="' .
				admin_url( 'admin.php?page=boldgrid-backup-test' ) .
				'">Functionality Test</a> to view a report.',
				'notice notice-error is-dismissible'
			);

			return;
		}

		// Display warning on resource usage and backups.
		do_action( 'boldgrid_backup_notice',
			'Warning: Making backups uses resources. When the system is backing up, it will slow down your site for visitors. Furthermore, when the database itself is being copied, your site must “pause” temporarily to preserve data integrity. For most sites, the pause is typically a few seconds and is not noticed by visitors. Large sites take longer though. Please keep the number of backups you have stored and how often you make those backups to a minimum.',
			'notice notice-warning is-dismissible'
		);

		// Get BoldGrid reseller settings.
		$boldgrid_reseller = get_option( 'boldgrid_reseller' );

		// If not part of a reseller, then show the unofficial host notice.
		if ( true === empty( $boldgrid_reseller ) ) {
			do_action( 'boldgrid_backup_notice',
				'Please note that your web hosting provider may have a policy against these types of backups. Please verify with your provider or choose a BoldGrid Official Host.',
				'notice notice-warning is-dismissible'
			);
		}

		// Check for settings update.
		if ( false === empty( $_POST['save_time'] ) ) {
			// Verify nonce.
			check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

			$this->update_settings();
		}

		// Enqueue CSS for the settings page.
		wp_enqueue_style( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-settings.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Register the JS for the settings page.
		wp_register_script( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-settings.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Enqueue JS for the settings page.
		wp_enqueue_script( 'boldgrid-backup-admin-settings' );

		// Get settings.
		$settings = $this->get_settings();

		// Include the page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-settings.php';

		return;
	}
}
