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
	 * The last error, as per error_get_last().
	 *
	 * @since 1.13.5
	 * @var array
	 * @access private
	 */
	private $last_error;

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
		 *
		 * WP_Filesystem does not have a way to append to a file, so we're rewriting the file each
		 * time. Best route would be to fopen the file and append. This may need to be revisited.
		 */
		$file_content = $this->get_contents() . PHP_EOL . $message;
		$this->core->wp_filesystem->put_contents( $this->filepath, $file_content );
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
	 * Add the last error to the log.
	 *
	 * The error is only added to the log if it hasn't been logged before.
	 *
	 * @since 1.13.5
	 */
	public function add_last_error() {
		$current_error = error_get_last();
		if ( ! empty( $current_error ) ) {
			$current_error = $this->format_error_info( $current_error );
		}

		// Only new errors are logged.
		if ( $current_error !== $this->last_error ) {
			$this->add( 'Last error: ' . print_r( $current_error, 1 ), false ); // phpcs:ignore
		}

		// This method will be called often, so keep track of errors to avoid logging duplicates.
		$this->last_error = $current_error;
	}

	/**
	 * Add user-friendly messaging to the log regarding the last error
	 *
	 * @param array $current_error The error array to format.
	 * @since 1.5.7
	 */
	public static function format_error_info( $current_error ) {
		/**
		 * Array containing error codes and definitions.
		 */
		$error_codes = array(
			1     => array( 'E_ERROR', 'Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted.' ),
			2     => array( 'E_WARNING', 'Run-time warnings (non-fatal errors). Execution of the script is not halted.' ),
			4     => array( 'E_PARSE', 'Compile-time parse errors. Parse errors should only be generated by the parser.' ),
			8     => array( 'E_NOTICE', ' Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script.' ),
			16    => array( 'E_CORE_ERROR', "Fatal errors that occur during PHP's initial startup. This is like an E_ERROR, except it is generated by the core of PHP." ),
			32    => array( 'E_CORE_WARNING', "Warnings (non-fatal errors) that occur during PHP's initial startup. This is like an E_WARNING, except it is generated by the core of PHP." ),
			64    => array( 'E_COMPILE_ERROR', 'Fatal compile-time errors. This is like an E_ERROR, except it is generated by the Zend Scripting Engine.' ),
			128   => array( 'E_COMPILE_WARNING', 'Compile-time warnings (non-fatal errors). This is like an E_WARNING, except it is generated by the Zend Scripting Engine.' ),
			256   => array( 'E_USER_ERROR', 'User-generated error message. This is like an E_ERROR, except it is generated in PHP code by using the PHP function trigger_error().' ),
			512   => array( 'E_USER_WARNING', 'User-generated warning message. This is like an E_WARNING, except it is generated in PHP code by using the PHP function trigger_error().' ),
			1024  => array( 'E_USER_NOTICE', 'User-generated notice message. This is like an E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error().' ),
			2048  => array( 'E_STRICT', 'Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code.' ),
			4096  => array( 'E_RECOVERABLE_ERROR', 'Catchable fatal error. It indicates that a probably dangerous error occurred, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR.' ),
			8192  => array( 'E_DEPRECATED', 'Run-time notices. These warnings are to provide information about code that will not work in future versions.' ),
			16384 => array( 'E_USER_DEPRECATED', 'User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error().' ),
		);

		/**
		 * Switch case to Add additional info for the corresponding errors from $error_codes
		 * all cases should fall through to default to complete the formatting.
		 */
		switch ( $current_error['type'] ) {
			case 1:
				$current_error['additional_info'] = 'This type of error may indicate a possible issue with the backup process';
				// Add additional info for  Fatal error and fall through to complete formatting

			case 2:
				$current_error['additional_info'] = 'Warnings can be ignored safely in most cases. These may indicate a problem if your backup is failing.';
				// Add additional info for wWrnings and fall through to complete formatting

			case 4:
				$current_error['additional_info'] = 'This type of error may indicate a possible issue with the backup process';
				// Add additional info for Parse errors and fall through to complete formatting

			case 8:
				$current_error['additional_info'] = 'Notices can be ignored safely in most cases. These may indicate a problem if your backup is failing.';
				// Add additional info for Notices and fall through to complete formatting

			case 8192:
				$current_error['additional_info'] = 'Deprecation warnings can be ignored safely in most cases. These may indicate a problem if your backup is failing.';
				// Add additional info for  Deprecated warnings and fall through to complete formatting

			default:
				$error_info                = array();
				$error_info['error_code']  = $current_error['type'];
				$error_info['description'] = $error_codes[ $current_error['type'] ][0] . ': ' . $error_codes[ $current_error['type'] ][1];
				$current_error['type']     = $error_info;
				if ( ! isset( $current_error['additional_info'] ) ) {
					$current_error['additional_info'] = 'No additional info available.';
				}
				break;
		}
		return $current_error;
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

		if ( ! function_exists( 'pcntl_async_signals' ) ) {
			$this->add( 'Cannot add signal handlers, pcntl_async_signals function does not exist.' );
			return;
		}

		// Enable asynchronous signal handling.
		pcntl_async_signals( true );

		$signals = array(
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
		);

		foreach ( $signals as $signal ) {
			pcntl_signal( $signal, array( $this, 'signal_handler' ) );
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
