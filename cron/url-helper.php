<?php
/**
 * File: url-helper.php
 *
 * Standalone URL address helper for Cron tasks.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.3
 *
 * @package Boldgrid_Backup
 * @copyright BoldGrid
 * @version $Id$
 * @author BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Url_Helper.
 *
 * @since 1.6.3
 */
class Boldgrid_Backup_Url_Helper {
	/**
	 * Call a URL address.
	 *
	 * @since 1.6.3
	 *
	 * @param  string $url A URL address.
	 * @return mixed  Returns a string or FALSE on failure to make the call.
	 */
	public function call_url( $url ) {
		$result = false;

		if ( empty( $url ) ) {
			return false;
		}

		// Sanitize the url.
		$url = filter_var( $url, FILTER_SANITIZE_URL );

		switch ( true ) {
			case $this->has_curl_ssl():
				$ch = curl_init( $url );

				$curl_options = array(
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_USERAGENT      => 'BoldGrid Backup cron task',
				);

				curl_setopt_array( $ch, $curl_options );

				$result = curl_exec( $ch );

				if ( curl_errno( $ch ) ) {
					$result = false;
				}

				curl_close( $ch );

				break;

			case $this->has_url_fopen():
				$result = file_get_contents( $url );
				break;

			default:
				break;
		}

		return $result;
	}

	/**
	 * Detect if cURL SSL is available.
	 *
	 * @since 1.6.3
	 *
	 * @return bool
	 */
	public function has_curl_ssl() {
		$has_curl_ssl = false;

		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$has_curl_ssl = (bool) ( $curl_version['features'] & CURL_VERSION_SSL );
		}

		return $has_curl_ssl;
	}

	/**
	 * Detect if allow_url_fopen is on.
	 *
	 * @since 1.6.3
	 *
	 * @return bool
	 */
	public function has_url_fopen() {
		return (bool) ini_get( 'allow_url_fopen' );
	}
}
