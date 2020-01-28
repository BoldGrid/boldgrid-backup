<?php
/**
 * File: class-boldgrid-backup-rest-utility.php
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Rest_Utility
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Rest_Utility {
	/**
	 * Get the current url.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public static function get_current_url() {
		$protocol = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';

		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Whether or not we're in a bgbkup REST call.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public static function is_rest() {
		$current_url = self::get_current_url();
		$rest_prefix = get_site_url( null, 'wp-json/bgbkup/' );

		// True when the current url begins with http://domain.com/wp-json/bgbkup/.
		return substr( $current_url, 0, strlen( $rest_prefix ) ) === $rest_prefix;
	}
}
