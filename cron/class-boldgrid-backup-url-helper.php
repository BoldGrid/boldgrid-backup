<?php
/**
 * File: class-boldgrid-backup-url-helper.php
 *
 * Standalone URL address helper for Cron tasks.
 *
 * @link       https://www.boldgrid.com
 * @since      1.6.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP, WordPress.WP.AlternativeFunctions

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
	 * @param  string $url     A URL address.
	 * @param  int    $status  HTTP status code from the response.
	 * @param  int    $errorno Error number. (cURL only).
	 * @param  string $error   Error message.
	 * @return mixed  Returns a string or FALSE on failure to make the call.
	 */
	public function call_url( $url, &$status = null, &$errorno = null, &$error = null ) {
		$result = false;

		if ( empty( $url ) ) {
			return false;
		}

		// Sanitize the url.
		$url = filter_var( $url, FILTER_SANITIZE_URL );

		switch ( true ) {
			case $this->has_curl_ssl():
				$ch = curl_init( $url );

				curl_setopt_array(
					$ch,
					[
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_SSL_VERIFYHOST => 0,
						CURLOPT_TIMEOUT        => 0,
						CURLOPT_USERAGENT      => 'BoldGrid task',
					]
				);

				$result  = curl_exec( $ch );
				$status  = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
				$errorno = curl_errno( $ch );
				$error   = curl_error( $ch );

				curl_close( $ch );

				break;

			case $this->has_url_fopen():
				$result = file_get_contents( $url );

				// Get response details from the $http_response_header (available if not failed).
				if ( ! empty( $http_response_header[0] ) ) {
					preg_match( '~([2-5]\d\d) (.*$)~', $http_response_header[0], $matches );

					if ( ! empty( $matches[1] ) ) {
						$status = intval( $matches[1] );

						if ( 200 === $status ) {
							$errorno = 0;
							$error   = '';
						} elseif ( ! empty( $matches[2] ) ) {
							$error = $matches[2];
						}
					}
				}

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
