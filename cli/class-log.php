<?php
/**
 * File: class-log.php
 *
 * Log functions
 *
 * @link       https://www.boldgrid.com
 * @since      1.10.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.WP.AlternativeFunctions
 */

namespace Boldgrid\Backup\Cli;

/**
 * Class: Log
 *
 * @since 1.10.0
 */
class Log {
	/**
	 * Log level label map.
	 *
	 * Uses log level constants in PHP core:
	 *     LOG_EMERG   0 System is unusable.
	 *     LOG_ALERT   1 Action must be taken immediately.
	 *     LOG_CRIT    2 Critical conditions.
	 *     LOG_ERR     3 Error conditions.
	 *     LOG_WARNING 4 Warning conditions.
	 *     LOG_NOTICE  5 Normal, but significant, condition.
	 *     LOG_INFO    6 Informational message.
	 *     LOG_DEBUG   7 Debug-level message.
	 *
	 * @since 1.10.0
	 * @access private
	 *
	 * @var array
	 * @staticvar
	 */
	private static $log_levels = [
		LOG_EMERG   => 'EMERG',
		LOG_ALERT   => 'ALERT',
		LOG_CRIT    => 'CRIT',
		LOG_ERR     => 'ERR',
		LOG_WARNING => 'WARNING',
		LOG_NOTICE  => 'NOTICE',
		LOG_INFO    => 'INFO',
		LOG_DEBUG   => 'DEBUG',
	];

	/**
	 * Log file path.
	 *
	 * @since  1.10.0
	 * @access private
	 *
	 * @var string
	 * @staticvar
	 */
	private static $log_filename = '/bgbkup-cli.log';

	/**
	 * Log timestamp format.
	 *
	 * @since  1.10.0
	 * @access private
	 *
	 * @var string
	 * @staticvar
	 */
	private static $date_format = 'Y-m-d H:i:s';

	/**
	 * Write a message to the log file.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @param  int $log_level Log level.
	 * @return string
	 */
	public static function get_level_label( $log_level ) {
		return self::$log_levels[ $log_level ];
	}

	/**
	 * Write a message to the log file.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cli\Info::get_log_flag()
	 * @see \Boldgrid\Backup\Cli\Info::get_log_level()
	 * @see self::get_level_label()
	 *
	 * @param  string $message Message.
	 * @param  int    $log_level Log level. Default: LOG_NOTICE.
	 * @return bool
	 */
	public static function write( $message, $log_level = LOG_NOTICE ) {
		$success = true;

		// If fopen is not enabled, then we cannot write; abort.
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			return false;
		}

		// If logging is enabled and the message log level is included, then write to the log file.
		if ( Info::get_log_flag() && Info::get_log_level() >= $log_level ) {
			$message = date( self::$date_format ) . ' [' . self::get_level_label( $log_level ) . '] ' .
				$message . PHP_EOL;
			$success = false !== file_put_contents( __DIR__ . self::$log_filename, $message, FILE_APPEND );
		}

		return $success;
	}
}
