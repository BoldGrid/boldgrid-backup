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
		$url = '';

		if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		return $url;
	}

	/**
	 * Echo a wp_rest nonce to the screen.
	 *
	 * This method is only using for testing, and is only called in a dev environment.
	 *
	 * @since SINCEVERSION
	 *
	 * @see Boldgrid_Backup::define_admin_hooks() For more info on how this method is used within a
	 *                                            dev environment.
	 * @see rest/README.MD                        For a list of example rest calls that depend on this
	 *                                            method.
	 */
	public static function insert_nonce() {
		wp_nonce_field( 'wp_rest', 'wp_rest' );
		echo '<input type="hidden" id="bgbkup_site_url" value="' . esc_attr( get_site_url() ) . '" />';
	}

	/**
	 * Whether or not we're in a REST call.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public static function is_rest() {
		$current_url = self::get_current_url();

		// True when the current url begins with http://domain.com/wp-json/.
		$rest_prefix         = get_site_url( null, 'wp-json/' );
		$in_pretty_permalink = substr( $current_url, 0, strlen( $rest_prefix ) ) === $rest_prefix;

		// True when the current url begins with http://domain.com/index.php/wp-json/
		$rest_prefix  = get_site_url( null, 'index.php/wp-json/' );
		$in_index_url = substr( $current_url, 0, strlen( $rest_prefix ) ) === $rest_prefix;

		// True when url includes the rest_route parameter.
		$in_get = ! empty( $_GET['rest_route'] ); // phpcs:ignore

		return $in_pretty_permalink || $in_index_url || $in_get;
	}
}
