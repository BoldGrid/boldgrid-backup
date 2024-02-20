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
	 * Path to run-jobs.php.
	 *
	 * @since 1.5.2
	 * @var   string
	 */
	public $run_jobs = 'cron/run-jobs.php';

	/**
	 * Path to the bgbkup-cli script.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public $site_check = 'cli/bgbkup-cli.php';

	/**
	 * Cron command.
	 *
	 * This is the base of most of our cron commands.
	 *
	 * The following was added as of 1.6.5 for those hosts that have register_argc_argv disabled:
	 * -d register_argc_argv="1"
	 *
	 * @since 1.6.5
	 * @access private
	 * @var string
	 */
	private $cron_command = 'php -d register_argc_argv="1" -qf';

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
	 * Get the cron command prefix.
	 *
	 * @return string
	 */
	public function get_cron_command() {
		return $this->cron_command;
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
	 * @param  array $settings Settings.
	 * @return bool  Success.
	 */
	public function add_cron_entry( array $settings = [] ) {
		if ( empty( $settings ) ) {
			$settings = $this->core->settings->get_settings();
		}

		// Delete existing backup cron jobs.
		$cron_status = $this->delete_cron_entries( 'backup' );

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

		$entry .= $days_scheduled_list . ' ' . $this->cron_command . ' "' . dirname( dirname( __FILE__ ) ) .
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
	 * @param  array $settings Settings.
	 * @return bool
	 */
	public function add_all_crons( array $settings ) {
		$success = false;

		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : null;
		$schedule  = ! empty( $settings['schedule'] ) ? $settings['schedule'] : null;

		if ( 'cron' === $scheduler && $this->core->scheduler->is_available( $scheduler ) ) {
			$this->core->scheduler->clear_all_schedules();

			$scheduled = false;
			if ( ! empty( $schedule ) ) {
				$scheduled = $this->add_cron_entry( $settings );
			}

			$jobs_scheduled = $this->schedule_jobs( $settings );
			$site_check     = $this->schedule_site_check( $settings );

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
		// Remove existing restore cron jobs.
		$this->delete_cron_entries( 'restore' );

		$entry  = $this->get_restore_command();
		$status = $this->update_cron( $entry );
		$time   = $this->core->auto_rollback->get_time_data();

		// If cron job was added, then update the boldgrid_backup_pending_rollback option with time.
		if ( $status ) {
			$pending_rollback = get_site_option( 'boldgrid_backup_pending_rollback' );

			$pending_rollback['deadline'] = $time['deadline'];

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
	 * This method is usually ran after saving the settings. If after save cron is our scheduler,
	 * then we need to make sure we have the "run_jobs" wp-cron scheduled.
	 *
	 * @since 1.5.2
	 *
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 *
	 * @param  array $settings Settings.
	 *
	 * @return bool Success.
	 */
	public function schedule_jobs( $settings ) {
		$cron_interval = isset( $settings['cron_interval'] ) ? $settings['cron_interval'] : '*/10 * * * *';
		$entry         = sprintf(
			'%7$s %6$s "%1$s/%2$s" siteurl=%3$s id=%4$s secret=%5$s > /dev/null 2>&1',
			dirname( dirname( __FILE__ ) ),
			$this->run_jobs,
			get_site_url(),
			$this->core->get_backup_identifier(),
			$this->get_cron_secret(),
			$this->cron_command,
			$cron_interval
		);

		return $this->update_cron( $entry );
	}

	/**
	 * Schedule Site Check.
	 *
	 * This method is usually ran after saving the settings. If (after save) cron is our scheduler,
	 * then we need to make sure we have the "site_check" wp-cron scheduled.
	 *
	 * @since 1.10.0
	 *
	 * @see BoldGrid_Backup_Admin_Core::get_backup_identifier()
	 * @see BoldGrid_Backup_Admin_Cron::get_cron_secret()
	 *
	 * @param  array $settings Settings.
	 * @return bool
	 */
	public function schedule_site_check( array $settings = [] ) {
		if ( empty( $settings ) ) {
			$settings = $this->core->settings->get_settings();
		}

		if ( empty( $settings['site_check']['enabled'] ) ) {
			return false;
		}

		$args = implode(
			' ',
			[
				'auto_recovery=' . ( ! empty( $settings['site_check']['auto_recovery'] ) ? 1 : 0 ),
				'email=' . $settings['notification_email'],
				'log=' . ( ! empty( $settings['site_check']['logger'] ) ? 1 : 0 ),
				'notify=' . ( ! empty( $settings['notifications']['site_check'] ) ? 1 : 0 ),
			]
		);

		$entry = sprintf(
			'*/%1$u * * * * %2$s "%3$s/%4$s" check %5$s >/dev/null 2>&1',
			$settings['site_check']['interval'],
			$this->cron_command,
			dirname( dirname( __FILE__ ) ),
			$this->site_check,
			$args
		);

		return $this->update_cron( $entry );
	}

	/**
	 * Update or add an entry to the system user crontab or wp-cron.
	 *
	 * @since 1.2
	 *
	 * @see \Boldgrid\Backup\Admin\Cron\Crontab::write_crontab()
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

		$crontab_written = ( new \Boldgrid\Backup\Admin\Cron\Crontab() )->write_crontab( $crontab );

		return $crontab_written && $this->entry_exists( $entry );
	}

	/**
	 * Get the pattern determined by mode passed.
	 *
	 * @since 1.11.1
	 *
	 * @param  string|bool $mode Please see in-method comments below when $pattern is configured.
	 * @return string
	 */
	public function get_mode_pattern( $mode = '' ) {
		/*
		 * Configure our regex pattern.
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

		switch ( true ) {
			case '' === $mode:
			case 'backup' === $mode:
				$pattern .= 'boldgrid-backup-cron.php" mode=backup';
				break;
			case 'restore' === $mode:
				// Match "boldgrid-backup-cron.php" (old) and "cli/bgbkup-cli.php" (new) in the pattern.
				$pattern .= '(boldgrid-backup-cron|cli/bgbkup-cli).php" mode=restore';
				break;
			case 'jobs' === $mode:
				// Match "run_jobs" (old) and "run-jobs" (new) filenames in the pattern.
				$pattern .= '(cron/run_jobs.php|' . $this->run_jobs . ')';
				break;
			case 'site_check' === $mode:
				$pattern .= $this->site_check . '" check';
				break;
			case 'all' === $mode:
			case true === $mode:
				break;
			default:
				$pattern .= $mode;
				break;
		}

		// Format the periods in the pattern for regex; ensure a backslash before periods.
		$pattern = str_replace( '\.', '.', $pattern );
		$pattern = str_replace( '.', '\.', $pattern );

		// Escape the regex delimited that we will use.
		$pattern = str_replace( '~', '\~', $pattern );

		return $pattern;
	}

	/**
	 * Remove lines matching the pattern.
	 *
	 * @since 1.11.1
	 *
	 * @param  string $pattern Regex pattern, without delimiter "~".
	 * @param  string $crontab The crontab contents.
	 * @return string
	 */
	public function filter_crontab( $pattern, $crontab ) {
		$crontab_exploded = explode( "\n", $crontab );
		$crontab          = '';

		foreach ( $crontab_exploded as $line ) {
			if ( ! empty( $line ) && ! preg_match( '~' . $pattern . '~', $line ) ) {
				$line     = trim( $line );
				$crontab .= $line . "\n";
			}
		}

		return $crontab;
	}

	/**
	 * Delete boldgrid-backup cron entries from the system user crontab.
	 *
	 * @since 1.2
	 *
	 * @see \Boldgrid_Backup_Admin_Cron::get_mode_pattern()
	 * @see \Boldgrid_Backup_Admin_Cron::filter_crontab()
	 * @see \Boldgrid\Backup\Admin\Cron\Crontab::write_crontab()
	 *
	 * @param  string|bool $mode Please see in-method comments below when $pattern is configured.
	 * @return bool
	 */
	public function delete_cron_entries( $mode = '' ) {
		if ( ! $this->core->test->is_crontab_available() ) {
			return false;
		}

		if ( ! $this->core->backup_dir->get() ) {
			return false;
		}

		$crontab_helper = new \Boldgrid\Backup\Admin\Cron\Crontab();

		$crontab = $this->get_all( true );

		if ( false === $crontab ) {
			return false;
		}

		$pattern = $this->get_mode_pattern( $mode );

		// If no entries exist, then return success.
		if ( ! preg_match( '~' . $pattern . '~', $crontab ) ) {
			return true;
		}

		$crontab = $this->filter_crontab( $pattern, $crontab );

		return $crontab_helper->write_crontab( $crontab );
	}

	/**
	 * Delete one entry from the crontab.
	 *
	 * @since 1.6.0
	 *
	 * @see \Boldgrid\Backup\Admin\Cron\Crontab::write_crontab()
	 * @see \Boldgrid_Backup_Admin_Cron::entry_exists()
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

		$all_entries     = implode( "\n", $all_entries );
		$crontab_written = ( new \Boldgrid\Backup\Admin\Cron\Crontab() )->write_crontab( $all_entries );

		return $crontab_written && ! $this->entry_exists( $entry );
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

		if ( empty( $all_entries ) ) {
			return false;
		}

		return false !== array_search( $entry, $all_entries, true );
	}

	/**
	 * Search for cron entries that contain a specfic string.
	 *
	 * @since 1.6.5
	 *
	 * @param  string $search String to search for.
	 * @return array          An array of matches.
	 */
	public function entry_search( $search ) {
		$matches = array();
		$entries = $this->get_all();

		if ( empty( $entries ) ) {
			return $matches;
		}

		foreach ( $entries as $entry ) {
			if ( false !== strpos( $entry, $search ) ) {
				$matches[] = $entry;
			}
		}

		return $matches;
	}

	/**
	 * Get all entries in cron.
	 *
	 * Prior to 1.13.9, the Boldgrid\Backup\Admin\Cron\Crontab class had a read_crontab() method that
	 * aimed to do the same thing as this method. However, it only executed "crontab -l" and returned
	 * the results. It did not take into account that not all lines of the crontab may be returned (this
	 * method does and has a conditional for it, so is better).
	 *
	 * @todo Migrate this method to the Boldgrid\Backup\Admin\Cron\Crontab. This method currently called
	 * 9+ times, and will need some good testing.
	 *
	 * @since 1.5.2
	 *
	 * @see Boldgrid_Backup_Admin_Core::execute_command()
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

		/*
		 * Attempt to read the crontab.
		 *
		 * Historically, we just read the output of "crontab -l". In certain scenarious, this does
		 * not return the full output of the command. Another solution would be to output that command
		 * to a file, and then read the file.
		 *
		 * As of 1.6.5, we'll first try the latter option.
		 */
		if ( $this->core->backup_dir->can_exec_write() ) {
			$crontab_file_path = $this->core->backup_dir->get_path_to( 'crontab' );

			// Write crontab to temp file.
			$command = sprintf( 'crontab -l > %1$s', $crontab_file_path );
			$this->core->execute_command( $command, $success );

			// Read the crontab from temp file.
			$crontab = $this->core->wp_filesystem->get_contents( $crontab_file_path );
			$success = false !== $crontab;

			$this->core->wp_filesystem->delete( $crontab_file_path );
		} else {
			$command = 'crontab -l';
			$crontab = $this->core->execute_command( $command, $success );
		}

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
	 * Get restore command.
	 *
	 * Create the cron markup (creates the markup, does not actually add to crontab) to restore a
	 * backup archive.
	 *
	 * Before @since xxx, this method was in add_cron_entry. It has since been moved to its own
	 * method here for reusability.
	 *
	 * @since xxx
	 *
	 * return string
	 */
	public function get_restore_command() {
		$entry = '';

		// Use the first key to get info on the most recent archive.
		if ( ! $this->core->archive->init_by_key( 0 ) ) {
			return $entry;
		}

		$time      = $this->core->auto_rollback->get_time_data();
		$settings  = $this->core->settings->get_settings();
		$backup_id = $this->core->get_backup_identifier();

		$entry_parts = [
			date( $time['minute'] . ' ' . $time['hour'], $time['deadline'] ) . ' * * ' . date( 'w' ),
			$this->cron_command,
			'"' . dirname( dirname( __FILE__ ) ) . '/cli/bgbkup-cli.php"',

			/*
			 * Info on mode=restore and restore:
			 *
			 * The "mode=restore" property is for the cron remove function (it's a pattern searched for),
			 * and "plain" is used by CLI. If you take out "mode=restore", it will still do the
			 * restoration but it won't be able to find and delete the cron.
			 *
			 * @todo simplify this.
			 */
			'mode=restore restore',
			'notify email=' . $settings['notification_email'],
			'backup_id=' . $backup_id,
			'zip=' . $this->core->archive->filepath,
		];

		// If not Windows, then also silence the cron job.
		if ( ! $this->core->test->is_windows() ) {
			$entry_parts[] = '> /dev/null 2>&1';
		}

		$entry = implode( ' ', $entry_parts );

		return $entry;
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
	 * A scheduled backup (via cron) will call a url which ultimately triggers this method to be ran
	 * to backup the site.
	 *
	 * @since 1.6.1
	 *
	 * @see Boldgrid_Backup_Admin_Cron::is_valid_call()
	 *
	 * @return array An array of archive file information.
	 */
	public function backup() {
		if ( ! $this->is_valid_call() ) {
			wp_die( esc_html__( 'Error: Invalid request.', 'boldgrid-backup' ) );
		}

		$archiver = new Boldgrid_Backup_Archiver();
		$archiver->run();

		return $archiver->get_info();
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
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		$task_id = ! empty( $_POST['task_id'] ) ? $_POST['task_id'] : null;
		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( ! $this->is_valid_call() ) {
			wp_die( esc_html__( 'Error: Invalid request.', 'boldgrid-backup' ) );
		}

		// A default error to return if restoration is not started in conditionals below.
		$archive_info = [
			'error' => __( 'Unknown error attempting restore.', 'boldgrid-backup' ),
		];

		/*
		 * Restore an archive.
		 *
		 * As of @SINCEVERSION, archives can be restored via REST. If we have a task, we're handling
		 * a REST restore. Otherwise, we're handling a standard restore request.
		 */
		if ( ! empty( $task_id ) ) {
			$task       = new Boldgrid_Backup_Admin_Task();
			$task_found = $task->init_by_id( $task_id );
			$restorer   = new Boldgrid_Backup_Restorer();

			if ( ! $task_found ) {
				$archive_info = [
					'error' => __( 'Resore error: Unable to instantiate task.', 'boldgrid-backup' ),
				];
			} elseif ( false !== $task->get_data( 'url' ) ) {
				$restorer->run_by_url( $task->get_data( 'url' ) );
				$archive_info = $restorer->get_info();
			} elseif ( false !== $task->get_data( 'backup_id' ) ) {
				$restorer->run_by_id( $task->get_data( 'backup_id' ) );
				$archive_info = $restorer->get_info();
			} else {
				$archive_info = [
					'error' => __( 'Restore error: Missing url / id.', 'boldgrid-backup' ),
				];
			}
		} else {
			if ( $this->core->restore_helper->prepare_restore() ) {
				$archive_info = $this->core->restore_archive_file();
			}
		}

		return $archive_info;
	}
}
