<?php
/**
 * File: class-boldgrid-backup-admin-log.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.12.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Log
 *
 * @since 1.12.5
 */
class Boldgrid_Backup_Admin_Log {
	/**
	 * Whether or not this log file was just created within this instance.
	 *
	 * Not all log files are recording info for one process, like a backup. There could be a general
	 * log that has stuff added to it on a regular basis.
	 *
	 * Knowing if this log file was just created can be useful, for example, if you wanted to add a
	 * heading to the log file to describe what the file is for.
	 *
	 * @since 1.13.8
	 * @var bool
	 */
	public $is_new = false;

	/**
	 * The core class object.
	 *
	 * @since  1.10.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Log file filename.
	 *
	 * @since 1.12.5
	 * @var string
	 * @access private
	 */
	private $filename;

	/**
	 * Log file filepath.
	 *
	 * @since 1.12.5
	 * @var string
	 * @access private
	 */
	private $filepath;

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( Boldgrid_Backup_Admin_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Add a message to the log.
	 *
	 * @since 1.12.5
	 *
	 * @param string $message        The message to add to the log.
	 * @param bool   $log_last_error Whether or not to log the last error. Most useful for self::add_last_error
	 *                               to avoid infinite loop when calling this method.
	 */
	public function add( $message, $log_last_error = true ) {
		/*
		 * Before we do anything, log the last error. This is important to go first because when looking
		 * at the log, the error should come first because it was triggered before whatever it is we're
		 * adding a message about right now.
		 */
		if ( $log_last_error ) {
			$this->add_last_error();
		}

		// Add a timestamp to the message.
		$message = date( '[Y-m-d H:i:s e]' ) . ' ' . $message;

		/*
		 * Append the message to the log.
		 */
		$handle = fopen( $this->filepath, 'a+' );
		if ( $handle ) {
			// Append the new message with a line break at the beginning
			fwrite( $handle, PHP_EOL . $message );
			// Close the file handle
			fclose( $handle );
		}
	}

	/**
	 * Add generic info for all logs.
	 *
	 * @since 1.12.6
	 */
	public function add_generic() {
		$this->add( 'PHP Version: ' . phpversion() );

		$this->add( 'WordPress Version: ' . get_bloginfo( 'version' ) );

		$version = defined( 'BOLDGRID_BACKUP_VERSION' ) ? BOLDGRID_BACKUP_VERSION : 'Unknown';
		$this->add( 'Total Upkeep version: ' . $version );

		$pgid_support = Boldgrid_Backup_Admin_Test::is_getpgid_supported();
		$this->add( 'getpgid support: ' . ( $pgid_support ? 'Available' : 'Unavailable' ) );
	}

	/**
	 * Add the last  fatal error to the log.
	 *
	 * The error is only added to the log if it is a fatal error
	 *
	 * @since 1.13.5
	 */
	public function add_last_error() {
		$current_error = error_get_last();

		// Only new fatal are logged.
		if ( is_array( $current_error ) && 1 === $current_error['type'] ) {
			$this->add( 'Last error: ' . print_r( $current_error, 1 ), false ); // phpcs:ignore
		}
	}


	/**
	 * Add info to the log about memory usage.
	 *
	 * @since 1.12.5
	 */
	public function add_memory() {
		$limit       = ini_get( 'memory_limit' );
		$memory      = memory_get_usage();
		$memory_peak = memory_get_peak_usage();

		$message = sprintf(
			'Memory usage - limit / current / peak memory usage: %1$s / %2$s (%3$s) / %4$s (%5$s)',
			$limit,
			$memory,
			size_format( $memory, 2 ),
			$memory_peak,
			size_format( $memory_peak )
		);

		$this->add( $message );
	}

	/**
	 * Add a separator in the log.
	 *
	 * @since 1.13.7
	 */
	public function add_separator() {
		$this->add( '--------------------------------------------------------------------------------' );
	}

	/**
	 * Delete old log files.
	 *
	 * @since 1.12.5
	 */
	public function clean_up() {
		// Get a dirlist of our logs dir.
		$logs_dir = $this->core->backup_dir->get_logs_dir();
		$dirlist  = $this->core->wp_filesystem->dirlist( $logs_dir );

		foreach ( $dirlist as $item ) {
			// Skip if this is not a log file.
			if ( 'log' !== pathinfo( $item['name'], PATHINFO_EXTENSION ) ) {
				continue;
			}

			// Skip if this file is not old enough to delete.
			$is_too_old = time() - $item['lastmodunix'] > $this->core->configs['max_log_age'];
			if ( ! $is_too_old ) {
				continue;
			}

			$filepath = Boldgrid_Backup_Admin_Utility::trailingslashit( $logs_dir ) . $item['name'];

			$this->core->wp_filesystem->delete( $filepath );
		}
	}

	/**
	 * Return the contents of the log file.
	 *
	 * @since 1.14.13
	 *
	 * @return string
	 */
	public function get_contents() {
		return $this->core->wp_filesystem->get_contents( $this->filepath );
	}

	/**
	 * Init.
	 *
	 * @since 1.12.5
	 *
	 * @param  string $filename The filename of the log to create.
	 * @return bool             Whether or not the log file was created successfully.
	 */
	public function init( $filename ) {
		// Purging of old log files is done here, when we're creating a new one.
		$this->clean_up();

		$this->filename = sanitize_file_name( $filename );

		$this->filepath = $this->core->backup_dir->get_logs_dir() . DIRECTORY_SEPARATOR . $this->filename;

		$this->init_signal_handler();

		$log_exists = $this->core->wp_filesystem->exists( $this->filepath );

		if ( ! $log_exists ) {
			$log_created = $this->core->wp_filesystem->touch( $this->filepath );

			if ( $log_created ) {
				$this->is_new = true;
				$this->add_generic();
			}
		}

		return $log_exists || $log_created;
	}

	/**
	 * Add signal handlers.
	 *
	 * The one signal we can't handle is SIGKILL (kill -9).
	 *
	 * @since 1.12.6
	 */
	private function init_signal_handler() {
		/*
		 * Available only on php >= 7.1
		 *
		 * With PHP 7.1, pcntl_async_signals was added to enable asynchronous signal handling, and it
		 * works great.
		 *
		 * Using ticks in php 5.6 not working as expected.
		 */
		if ( ! version_compare( phpversion(), '7.1', '>=' ) ) {
			return;
		}

		/*
		 * Removed logging that 'pcntl_async_signals' is not available,
		 * because it was filling up the logs on sites that don't have it available.
		 */
		if ( ! function_exists( 'pcntl_async_signals' ) ) {
			return;
		}

		// Enable asynchronous signal handling.
		pcntl_async_signals( true );

		$signals = [
			// Ctrl+C.
			SIGINT,
			// Ctrl+\ (similiar to SIGINT, generates a core dump if necessary).
			SIGQUIT,
			// kill.
			SIGTERM,
			// Terminal log-out.
			SIGHUP,
			/*
			 * Apache graceful stop.
			 *
			 * @link https://stackoverflow.com/questions/780853/what-is-in-apache-2-a-caught-sigwinch-error
			 */
			SIGWINCH,
			// The user requested to cancel (kill) the backup.
			SIGUSR1,
		];

		foreach ( $signals as $signal ) {
			pcntl_signal( $signal, [ $this, 'signal_handler' ] );
		}
	}

	/**
	 * Hook into shutdown.
	 *
	 * @since 1.13.5
	 */
	public function shutdown() {
		/*
		 * This method is always added to the shutdown. Only log errors if we've initialized and are
		 * using this logging system (IE don't log errors unrelated to this plugin).
		 */
		if ( ! empty( $this->filename ) ) {
			$this->add_last_error();
		}
	}

	/**
	 * Signal handler.
	 *
	 * @since 1.12.6
	 *
	 * @param int   $signo The signal being handled.
	 * @param array $signinfo Additional information about signal.
	 */
	public function signal_handler( $signo, $signinfo ) {
		$this->add( 'Signal received: ' . $signo . ' ' . wp_json_encode( $signinfo ) );

		exit;
	}
}
