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
	 * Path to direct-transfer.php
	 * 
	 * @since 1.17.0
	 * @var string
	 */
	public $direct_transfer = 'cron/direct-transfer.php';

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
	 * Cached raw crontab from get_all(), or false when the last read failed.
	 *
	 * @since 1.17.5
	 * @var string|false|null
	 */
	private $crontab_cache = null;

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

		/*
		 * Prefer the saved setting, then the same fallback as scheduler->get() /
		 * auto-rollback. Using only settings['scheduler'] would no-op when the key
		 * was never saved even though system cron is the effective scheduler.
		 */
		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : $this->core->scheduler->get();
		$schedule  = ! empty( $settings['schedule'] ) ? $settings['schedule'] : null;

		if ( 'cron' === $scheduler && $this->core->scheduler->is_available( $scheduler ) ) {
			$this->core->scheduler->clear_all_schedules();

			$scheduled = false;
			if ( ! empty( $schedule ) ) {
				$scheduled = $this->add_cron_entry( $settings );
			}

			$jobs_scheduled = $this->schedule_jobs( $settings );
			$site_check     = $this->schedule_site_check( $settings );

			/*
			 * An empty schedule is success when jobs were written: there is no backup
			 * entry to add. Requiring $scheduled here left crontab_version unset, so
			 * upgrade_crontab_entries cleared restore/direct-transfer jobs on every
			 * admin_init for sites with no backup schedule.
			 */
			$success = ( empty( $schedule ) || $scheduled ) && $jobs_scheduled;

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
	 * Schedule Direct Transfer Cron.
	 * 
	 * This method will be run after starting a new
	 * direct transfer job.
	 * 
	 * @since 1.17.0
	 */
	public function schedule_direct_transfer() {
		$cron_interval = '*/1 * * * *';
		$entry         = sprintf(
			'%7$s %6$s "%1$s/%2$s" siteurl=%3$s id=%4$s secret=%5$s > /dev/null 2>&1',
			dirname( dirname( __FILE__ ) ),
			$this->direct_transfer,
			get_site_url(),
			$this->core->get_backup_identifier(),
			$this->get_cron_secret(),
			$this->cron_command,
			$cron_interval
		);

		return $this->update_cron( $entry );
	}

	/**
	 * Check if there is an active direct transfer in progress.
	 *
	 * Direct transfers can be active on either the receiving side (active_transfer)
	 * or the sending side (active_tx). This checks both.
	 *
	 * @since 1.17.4
	 *
	 * @return bool True if an active direct transfer exists.
	 */
	public function has_active_direct_transfer() {
		$config       = ! empty( $this->core->configs['direct_transfer'] ) ? $this->core->configs['direct_transfer'] : array();
		$option_names = ! empty( $config['option_names'] ) ? $config['option_names'] : array();

		$active_rx = ! empty( $option_names['active_transfer'] )
			? get_option( $option_names['active_transfer'], false )
			: false;

		$active_tx = ! empty( $option_names['active_tx'] )
			? get_option( $option_names['active_tx'], false )
			: false;

		if ( empty( $active_rx ) && empty( $active_tx ) ) {
			return false;
		}

		$transfers_option = ! empty( $option_names['transfers'] ) ? $option_names['transfers'] : '';
		$transfers        = '' !== $transfers_option ? get_option( $transfers_option, array() ) : array();

		/*
		 * Migrate writes "canceled"; some call sites historically checked "cancelled".
		 * Treat both (and other terminal statuses) as inactive. Missing status is treated
		 * as active so a corrupted option cannot drop an in-progress transfer cron.
		 */
		$inactive_statuses = array( 'completed', 'restore-completed', 'failed', 'canceled', 'cancelled' );

		if ( ! empty( $active_rx ) && isset( $transfers[ $active_rx ] ) && is_array( $transfers[ $active_rx ] ) ) {
			$status = isset( $transfers[ $active_rx ]['status'] ) ? $transfers[ $active_rx ]['status'] : '';
			if ( ! in_array( $status, $inactive_statuses, true ) ) {
				return true;
			}
		}

		if ( ! empty( $active_tx ) ) {
			return true;
		}

		return false;
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

		if ( false === $crontab ) {
			return false;
		}

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
		$all_entries = $this->get_all();

		// get_all() returns false when crontab cannot be read (PHP 8+ array_search TypeError).
		if ( ! is_array( $all_entries ) ) {
			return false;
		}

		$key = array_search( $entry, $all_entries, true );

		if ( false === $key ) {
			return true;
		}

		unset( $all_entries[ $key ] );

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
		if ( null !== $this->crontab_cache ) {
			if ( false === $this->crontab_cache ) {
				return false;
			}

			return $raw ? $this->crontab_cache : explode( "\n", $this->crontab_cache );
		}

		if ( $this->core->backup_dir->can_exec_write() ) {
			$crontab_file_path = $this->core->backup_dir->get_path_to( 'crontab' );

			$command = sprintf( 'crontab -l > %1$s', $crontab_file_path );
			$this->core->execute_command( $command, $success );

			if ( ! $this->core->wp_filesystem->exists( $crontab_file_path ) ) {
				$this->crontab_cache = false;
				return false;
			}

			$crontab = $this->core->wp_filesystem->get_contents( $crontab_file_path );
			$success = false !== $crontab;

			$this->core->wp_filesystem->delete( $crontab_file_path );
		} else {
			$command = 'crontab -l';
			$crontab = $this->core->execute_command( $command, $success );
		}

		if ( ! $success ) {
			$this->crontab_cache = false;
			return false;
		}

		$this->crontab_cache = $crontab;

		return $raw ? $crontab : explode( "\n", $crontab );
	}

	/**
	 * Drop the per-request crontab read cache after a write.
	 *
	 * @since 1.17.5
	 */
	public function clear_crontab_cache() {
		$this->crontab_cache = null;
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

		// Generate and store a one-time random secret for the CLI cancel endpoint.
		$cli_cancel_secret = wp_generate_password( 32, false );
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $cli_cancel_secret );

		$entry_parts = [
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Server-local time for crontab.
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
			'cli_cancel_secret=' . $cli_cancel_secret,
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

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Server-local time for crontab.
		$schedule['tod_h'] = intval( date( 'g', $unix_time ) );
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Server-local time for crontab.
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
				esc_html( $archive_info['mode'] )
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
				esc_html( $action_name )
			) . PHP_EOL;

			printf(
				// translators: 1: Error message.
				esc_html__( 'Error: %s', 'boldgrid-backup' ),
				esc_html( $archive_info['error'] )
			) . PHP_EOL;

			if ( isset( $archive_info['error_message'] ) ) {
				printf(
					// translators: 1: Error message.
					esc_html__( 'Error Message: %s', 'boldgrid-backup' ),
					esc_html( $archive_info['error_message'] )
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
					esc_html( $archive_info['filepath'] )
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['filesize'] ) ) {
				printf(
					// translators: 1: File size.
					esc_html__( 'File Size: %s', 'boldgrid-backup' ),
					esc_html( Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['filesize'] ) )
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['total_size'] ) ) {
				printf(
					// translators: 1: Total backup size.
					esc_html__( 'Total size: %s', 'boldgrid-backup' ),
					esc_html( Boldgrid_Backup_Admin_Utility::bytes_to_human( $archive_info['total_size'] ) )
				) . PHP_EOL;
			}

			if ( ! empty( $archive_info['compressor'] ) ) {
				printf(
					// translators: 1: Compressor name.
					esc_html__( 'Compressor: %s', 'boldgrid-backup' ),
					esc_html( $archive_info['compressor'] )
				) . PHP_EOL;
			}

			// Show how long the website was paused for.
			if ( isset( $archive_info['db_duration'] ) ) {
				printf(
					esc_html( $this->core->configs['lang']['est_pause'] ),
					esc_html( $archive_info['db_duration'] )
				) . PHP_EOL;
			}

			if ( isset( $archive_info['duration'] ) ) {
				printf(
					// translators: 1: Backup duration.
					esc_html__( 'Duration: %s seconds', 'boldgrid-backup' ),
					esc_html( $archive_info['duration'] )
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
				esc_html( $action_name )
			) . PHP_EOL;
		}
	}

	/**
	 * Site option key for the one-time secrets rotation gate.
	 *
	 * @since 1.17.4
	 * @var string
	 */
	const SECRETS_ROTATED_OPTION = 'boldgrid_backup_secrets_rotated';

	/**
	 * Plugin version that introduced one-time cron/CLI secret rotation.
	 *
	 * @since 1.17.4
	 * @var string
	 */
	const SECRETS_ROTATED_VERSION = '1.17.4';

	/**
	 * Site option key used to claim an in-progress secrets rotation.
	 *
	 * @since 1.17.4
	 * @var string
	 */
	const SECRETS_ROTATING_OPTION = 'boldgrid_backup_secrets_rotating';

	/**
	 * Seconds after which an abandoned rotation claim may be taken over.
	 *
	 * @since 1.17.4
	 * @var int
	 */
	const SECRETS_ROTATING_TIMEOUT = 300;

	/**
	 * Site option key for a one-shot crontab-format upgrade attempt.
	 *
	 * @since 1.17.5
	 * @var string
	 */
	const CRONTAB_UPGRADE_ATTEMPTED_OPTION = 'boldgrid_backup_crontab_upgrade_attempted';

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
	 * One-time rotation of previously exposable cron / CLI cancel secrets.
	 *
	 * Sites that ran 1.14.10–1.17.2 may have had cron_secret published at a predictable
	 * URL. 1.17.3 stopped the leak but left any already-stored secret valid. On upgrade
	 * to 1.17.4 this method clears those secrets once, mints a fresh cron_secret, and
	 * rewrites crontab / restore-info that still embedded the old value.
	 *
	 * The one-shot gate is written immediately after remint so a later crontab rewrite
	 * failure cannot cause another rotation on the next request. Crontab / restore-info
	 * updates are best-effort; a failed rewrite can be repaired by re-saving settings or
	 * reactivating the plugin (activation always calls add_all_crons). Concurrent
	 * requests are serialized by a claim, since two remints would leave crontab and
	 * settings holding different secrets. Abandoned-claim takeover remints even when
	 * settings already lost the old secret, and refreshes restore-info without requiring
	 * that prior value.
	 *
	 * @since 1.17.4
	 *
	 * @see Boldgrid_Backup_Admin_Cron::claim_secrets_rotation()
	 * @see Boldgrid_Backup_Admin_Cron::get_cron_secret()
	 * @see Boldgrid_Backup_Admin_Cron::add_all_crons()
	 * @see Boldgrid_Backup_Admin_Cron::add_restore_cron()
	 *
	 * @return bool True when rotation ran (or was a no-op that set the gate); false if already done.
	 */
	public function maybe_rotate_cron_secrets() {
		if ( self::SECRETS_ROTATED_VERSION === get_site_option( self::SECRETS_ROTATED_OPTION ) ) {
			return false;
		}

		$claim_result = $this->claim_secrets_rotation();
		if ( false === $claim_result ) {
			return false;
		}

		/*
		 * When taking over an abandoned claim, the failed request may have already
		 * deleted cron_secret and cli_cancel_secret before dying. In that case
		 * $old_secret would be empty and we would skip remint/rewrite, leaving
		 * crontab broken. Force a full rotation on takeover to ensure recovery.
		 */
		$is_takeover = 'takeover' === $claim_result;

		$settings   = $this->core->settings->get_settings( true );
		$old_secret = ! empty( $settings['cron_secret'] ) ? (string) $settings['cron_secret'] : '';
		$new_secret = '';
		$rotating   = '' !== $old_secret || $is_takeover;

		if ( '' !== $old_secret ) {
			unset( $settings['cron_secret'] );
			$this->cron_secret = null;
			update_site_option( 'boldgrid_backup_settings', $settings );
		}

		$had_cancel_secret = ! empty( get_site_option( 'boldgrid_backup_cli_cancel_secret' ) );
		delete_site_option( 'boldgrid_backup_cli_cancel_secret' );

		if ( $rotating ) {
			$new_secret = $this->get_cron_secret();
		}

		/*
		 * Persist the gate before crontab/restore-info rewrites. Remint already
		 * invalidated the harvested secret; failing to set the gate here would
		 * treat the fresh secret as "old" on the next init and rotate forever.
		 */
		update_site_option( self::SECRETS_ROTATED_OPTION, self::SECRETS_ROTATED_VERSION );

		$settings = $this->core->settings->get_settings( true );
		/*
		 * Use scheduler->get() so a missing settings['scheduler'] still resolves via the
		 * same fallback as auto-rollback. Reading the raw key left pending rollbacks
		 * without a cancel re-issue and skipped crontab rewrite after remint.
		 */
		$scheduler = $this->core->scheduler->get();

		if ( $rotating && 'cron' === $scheduler && $this->core->scheduler->is_available( 'cron' ) ) {
			$this->add_all_crons( $settings );
		}

		/*
		 * Direct transfer always uses system crontab with an embedded cron_secret, even when
		 * the configured backup scheduler is wp-cron. add_all_crons (system-cron path) clears
		 * that entry and never recreates it; the wp-cron path never touches it, so the old
		 * secret would keep working against settings that no longer accept it. Delete then
		 * reschedule so only the new secret remains.
		 */
		if ( $rotating && $this->has_active_direct_transfer() ) {
			$this->entry_delete_contains( 'direct-transfer.php' );
			$this->schedule_direct_transfer();
		}

		/*
		 * A pending rollback's restore cron embeds cli_cancel_secret, which was just
		 * deleted. Re-issue so the emailed cancel link keeps working; a site can hold a
		 * cancel secret without ever having stored a cron_secret, so this cannot be
		 * limited to the rotation path. On takeover, always re-issue since the
		 * failed request may have already deleted cli_cancel_secret.
		 */
		$archives = $this->core->get_archive_list();
		if ( ( $rotating || $had_cancel_secret || $is_takeover ) && get_site_option( 'boldgrid_backup_pending_rollback' ) && ! empty( $archives ) ) {
			switch ( $scheduler ) {
				case 'cron':
					$this->add_restore_cron();
					break;
				case 'wp-cron':
					$this->core->wp_cron->add_restore_cron();
					break;
			}
		}

		/*
		 * Normal rotation knows the harvested secret from settings. On takeover the prior
		 * request may already have deleted it, so refresh with an empty $old_secret to rewrite
		 * any restore-info still holding a different value.
		 */
		if ( '' !== $new_secret && ( '' !== $old_secret || $is_takeover ) ) {
			$this->refresh_restore_info_cron_secret( $old_secret, $new_secret );
		}

		delete_site_option( self::SECRETS_ROTATING_OPTION );

		return true;
	}

	/**
	 * Claim the one-time rotation so concurrent requests do not both remint.
	 *
	 * Two requests can pass the gate check before either persists it, which would remint
	 * twice and leave crontab and settings holding different secrets. add_site_option()
	 * only reports success for the request that inserts the row, so it serializes the
	 * common case. A claim older than SECRETS_ROTATING_TIMEOUT is assumed abandoned by a
	 * request that died mid-rotation and may be taken over, otherwise a fatal during
	 * rotation would block the upgrade permanently.
	 *
	 * @since 1.17.4
	 *
	 * @return string|false 'fresh' when this request is the first claimer, 'takeover' when
	 *                      claiming an abandoned rotation, false when another request owns it.
	 */
	private function claim_secrets_rotation() {
		if ( add_site_option( self::SECRETS_ROTATING_OPTION, time() ) ) {
			return 'fresh';
		}

		$claimed_at = (int) get_site_option( self::SECRETS_ROTATING_OPTION );

		if ( $claimed_at && ( time() - $claimed_at ) < self::SECRETS_ROTATING_TIMEOUT ) {
			return false;
		}

		/*
		 * Delete the stale claim and re-add atomically. add_site_option() only succeeds
		 * for the first inserter, so concurrent takeover attempts are serialized the
		 * same way fresh claims are. If another request wins the race, fall back to
		 * waiting for it to complete.
		 */
		delete_site_option( self::SECRETS_ROTATING_OPTION );

		if ( ! add_site_option( self::SECRETS_ROTATING_OPTION, time() ) ) {
			return false;
		}

		return 'takeover';
	}

	/**
	 * Rewrite cron_secret inside the secure restore-info JSON after rotation.
	 *
	 * Emergency CLI restore builds its admin-ajax call from this file. Leaving the
	 * old secret would either fail is_valid_call() or (worse) leave a harvested value
	 * as the only copy trusted by the CLI path. Secure storage is always scanned; the
	 * legacy plugin cron/ directory is rewritten only when it is still the live metadata
	 * (no secure storage), and otherwise has its retired copies deleted.
	 *
	 * When $old_secret is empty (abandoned-claim takeover), any file whose cron_secret
	 * is not already $new_secret is rewritten, since settings no longer hold the prior
	 * value to match against.
	 *
	 * @since 1.17.4
	 *
	 * @param string $old_secret Prior cron secret, or empty to rewrite any non-matching file.
	 * @param string $new_secret Fresh cron secret.
	 * @return bool True when a restore-info file was updated.
	 */
	public function refresh_restore_info_cron_secret( $old_secret, $new_secret ) {
		$old_secret = (string) $old_secret;
		$new_secret = (string) $new_secret;

		if ( '' === $new_secret || ( '' !== $old_secret && hash_equals( $old_secret, $new_secret ) ) ) {
			return false;
		}

		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';

		$paths      = array();
		$backup_dir = $this->core->backup_dir->get();

		if ( ! empty( $backup_dir ) ) {
			$backup_dir = \Boldgrid\Backup\Cli\Info::untrailingslashit_path( (string) $backup_dir );

			$cli_secret = \Boldgrid\Backup\Cli\Info::get_secret();
			if ( \Boldgrid\Backup\Cli\Info::is_valid_secret_format( $cli_secret ) ) {
				$paths[] = $backup_dir . '/restore-info-' . $cli_secret . '.json';
			}

			// Also scan for any restore-info-*.json in the backup directory (orphan names).
			$paths = array_merge( $paths, $this->get_restore_info_paths( $backup_dir ) );
		}

		/*
		 * The CLI only reads the legacy plugin-tree copy when secure storage is
		 * unavailable, and the plugin tree is not a guaranteed-private location. Rewrite
		 * it only while it is the live metadata; otherwise the CLI cannot reach it, so
		 * delete it instead of storing a fresh secret there.
		 */
		$legacy_paths = $this->get_restore_info_paths( BOLDGRID_BACKUP_PATH . '/cron' );

		if ( \Boldgrid\Backup\Cli\Info::get_secure_storage_dir() ) {
			$this->delete_stale_restore_info( $legacy_paths, $old_secret, $new_secret );
		} else {
			$paths = array_merge( $paths, $legacy_paths );
		}

		$paths   = array_unique( $paths );
		$updated = false;

		foreach ( $paths as $path ) {
			$data = $this->read_restore_info( $path );
			if ( null === $data || empty( $data['cron_secret'] ) ) {
				continue;
			}

			$file_secret = (string) $data['cron_secret'];

			if ( '' !== $old_secret ) {
				if ( ! hash_equals( $file_secret, $old_secret ) ) {
					continue;
				}
			} elseif ( hash_equals( $file_secret, $new_secret ) ) {
				continue;
			}

			$data['cron_secret'] = $new_secret;

			if ( ! empty( $data['restore_cmd'] ) && is_string( $data['restore_cmd'] ) ) {
				$data['restore_cmd'] = str_replace(
					'secret=' . $file_secret,
					'secret=' . $new_secret,
					$data['restore_cmd']
				);
			}

			$payload = wp_json_encode( $data );
			if ( false === $payload ) {
				continue;
			}

			$written = false;
			if ( ! empty( $this->core->wp_filesystem ) ) {
				$written = (bool) $this->core->wp_filesystem->put_contents( $path, $payload, 0600 );
			}

			if ( ! $written ) {
				$bytes = @file_put_contents( $path, $payload ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				if ( false !== $bytes ) {
					@chmod( $path, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_chmod
					$written = true;
				}
			}

			if ( $written ) {
				$updated = true;
			}
		}

		return $updated;
	}

	/**
	 * Read and decode a restore-info JSON file.
	 *
	 * @since 1.17.4
	 *
	 * @param  string $path Absolute path to a restore-info file.
	 * @return array|null Decoded data, or null when unreadable / not JSON.
	 */
	private function read_restore_info( $path ) {
		if ( ! is_readable( $path ) ) {
			return null;
		}

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return null;
		}

		$data = json_decode( $contents, true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Delete restore-info copies that still hold a rotated-out cron secret.
	 *
	 * Used for copies the CLI can no longer reach, so the retired secret does not linger
	 * on disk. Files holding any other secret are left alone, unless $old_secret is empty
	 * (takeover recovery) in which case any file whose cron_secret is not $new_secret is
	 * removed.
	 *
	 * @since 1.17.4
	 *
	 * @param  array  $paths      Restore-info paths to consider.
	 * @param  string $old_secret Rotated-out cron secret, or empty for takeover recovery.
	 * @param  string $new_secret Fresh cron secret (used when $old_secret is empty).
	 * @return int Number of files deleted.
	 */
	private function delete_stale_restore_info( array $paths, $old_secret, $new_secret = '' ) {
		$deleted    = 0;
		$old_secret = (string) $old_secret;
		$new_secret = (string) $new_secret;

		foreach ( $paths as $path ) {
			$data = $this->read_restore_info( $path );
			if ( null === $data || empty( $data['cron_secret'] ) ) {
				continue;
			}

			$file_secret = (string) $data['cron_secret'];

			if ( '' !== $old_secret ) {
				if ( ! hash_equals( $file_secret, $old_secret ) ) {
					continue;
				}
			} elseif ( '' === $new_secret || hash_equals( $file_secret, $new_secret ) ) {
				continue;
			}

			if ( @unlink( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * List restore-info-*.json files in a directory.
	 *
	 * @since 1.17.4
	 *
	 * @param  string $dir Directory to scan.
	 * @return array Absolute paths, empty when the directory is unreadable.
	 */
	private function get_restore_info_paths( $dir ) {
		$dir   = \Boldgrid\Backup\Cli\Info::untrailingslashit_path( (string) $dir );
		$paths = array();

		if ( '' === $dir || ! is_dir( $dir ) ) {
			return $paths;
		}

		$listing = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( empty( $listing ) ) {
			return $paths;
		}

		foreach ( preg_grep( '/^restore-info-.*\.json$/', $listing ) as $file ) {
			$paths[] = $dir . '/' . $file;
		}

		return $paths;
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
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Request is authenticated below by comparing a shared secret.
		$provided_id     = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
		$provided_secret = isset( $_GET['secret'] ) ?
			sanitize_text_field( wp_unslash( $_GET['secret'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$backup_id_match = '' !== $provided_id &&
			hash_equals( (string) $this->core->get_backup_identifier(), $provided_id );

		$cron_secret_match = is_string( $provided_secret ) && '' !== $provided_secret &&
			hash_equals( (string) $this->get_cron_secret(), $provided_secret );

		return current_user_can( 'update_plugins' ) || ( $backup_id_match && $cron_secret_match );
	}

	/**
	 * Upgrade crontab entries, if not already upgraded.
	 *
	 * Cheap no-op when crontab_version already matches. Availability probes and
	 * crontab-to-file reads run only when an upgrade is still needed, and a failed
	 * attempt is gated so admin_init cannot stall on every request.
	 *
	 * @since 1.6.1-rc.1
	 *
	 * @see BoldGrid_Backup_Admin_Settings::get_settings()
	 * @see BoldGrid_Backup_Admin_Cron::add_all_crons()
	 *
	 * @return bool Returns TRUE only if an upgrade was performed.
	 */
	public function upgrade_crontab_entries() {
		$settings = $this->core->settings->get_settings( true );

		if ( ! empty( $settings['crontab_version'] ) &&
			$this->crontab_version === $settings['crontab_version'] ) {
			return false;
		}

		$scheduler = ! empty( $settings['scheduler'] ) ? $settings['scheduler'] : $this->core->scheduler->get();

		if ( 'cron' !== $scheduler ) {
			return false;
		}

		if ( $this->crontab_version === get_site_option( self::CRONTAB_UPGRADE_ATTEMPTED_OPTION ) ) {
			return false;
		}

		update_site_option( self::CRONTAB_UPGRADE_ATTEMPTED_OPTION, $this->crontab_version );

		if ( ! $this->core->scheduler->is_available( $scheduler ) ) {
			return false;
		}

		$upgraded = $this->add_all_crons( $settings );

		/*
		 * add_all_crons clears all BoldGrid crontab entries and only re-adds
		 * backup/jobs/site-check. Pending rollback restore crons and active
		 * direct-transfer crons must be re-added afterward — matching activation
		 * and secret rotation. When the schedule is empty, add_all_crons may
		 * return false without persisting crontab_version; without this follow-up
		 * those jobs would be dropped again on every admin_init.
		 */
		$archives = $this->core->get_archive_list();
		if ( get_site_option( 'boldgrid_backup_pending_rollback' ) && ! empty( $archives ) ) {
			$this->add_restore_cron();
		}

		if ( $this->has_active_direct_transfer() ) {
			$this->entry_delete_contains( 'direct-transfer.php' );
			$this->schedule_direct_transfer();
		}

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
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Request is authenticated by is_valid_call() below.
		$task_id = ! empty( $_POST['task_id'] ) ? sanitize_key( wp_unslash( $_POST['task_id'] ) ) : null;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

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
