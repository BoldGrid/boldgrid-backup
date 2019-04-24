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
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.WP.AlternativeFunctions
 */

namespace Boldgrid\Backup\Cron;

/**
 * Class: Log
 *
 * @since 1.10.0
 */
class Log {
	/**
	 * Log filename.
	 *
	 * @since  1.10.0
	 * @access private
	 *
	 * @var string
	 * @staticvar
	 */
	private static $filename = 'bgbkup-cli.log';

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
	 * @param  string $message Message.
	 * @return bool
	 */
	public static function write( $message ) {
		// If fopen is not enabled, then we cannot write; abort.
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			return false;
		}

		$message = date( self::$date_format ) . ': ' . $message . PHP_EOL;

		return false !== file_put_contents(
			__DIR__ . '/' . self::$filename,
			$message,
			FILE_APPEND
		);
	}
}
