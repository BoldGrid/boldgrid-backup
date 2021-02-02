<?php
/**
 * Utility Remove class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Utility;

/**
 * Class: Remote
 *
 * @since SINCEVERSION
 */
class Remote {
	/**
	 *
	 * @return boolean
	 */
	public static function get_json( $url ) {
		$request = wp_remote_get( $url );

		// If we have an error, return that now. No further processing needed.
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$body = wp_remote_retrieve_body( $request );
		$data = json_decode( $body, true );

		return $data;
	}

	/**
	 *
	 */
	public static function save_file( $source, $destination, &$response = null ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$allowed_content_types = [
			'application/octet-stream',
			'binary/octet-stream',
			'application/zip',
		];

		$url_regex    = '/' . $core->configs['url_regex'] . '/i';
		$is_value_url = preg_match( $url_regex, $source );
		if ( ! $is_value_url ) {
			return __( 'Invalid URL address.', 'boldgrid-backup' );
		}

		$response = wp_remote_get(
			$source,
			[
				'filename'  => $destination,
				'headers'   => 'Accept: ' . implode( ', ', $allowed_content_types ),
				'sslverify' => false,
				'stream'    => true,
				'timeout'   => MINUTE_IN_SECONDS * 20,
			]
		);

		$success = is_array( $response ) &&
			! is_wp_error( $response ) &&
			in_array( $response['headers']['content-type'], $allowed_content_types, true );

		if ( ! $success ) {
			$core->wp_filesystem->delete( $destination );
		}

		return $success;
	}
}
