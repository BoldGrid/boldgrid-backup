<?php
/**
 * File: class-boldgrid-backup-authentication.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.7.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Authentication
 *
 * @since 1.7.0
 */
class Boldgrid_Backup_Authentication {
	/**
	 * Generate a limited-lifetime token.
	 *
	 * @since 1.7.0
	 *
	 * @static
	 *
	 * @param  string $id Identifier.
	 * @param  int    $expires Expiration time (in UNIX seconds).
	 * @return string
	 */
	public static function create_token( $id, $expires ) {
		$token    = md5( openssl_random_pseudo_bytes( 64 ) );
		$tokens   = get_site_option( 'boldgrid_backup_tokens', array() );
		$tokens[] = array(
			'id'      => $id,
			'token'   => $token,
			'expires' => $expires,
		);

		// Purge expired tokens.
		foreach ( $tokens as $index => $row ) {
			if ( time() > $row['expires'] ) {
				unset( $tokens[ $index ] );
			}
		}

		update_site_option( 'boldgrid_backup_tokens', $tokens );

		return $token;
	}

	/**
	 * Validate token and return details.
	 *
	 * @since 1.7.0
	 *
	 * @static
	 *
	 * @param  string $token Token.
	 * @return array
	 */
	public static function get_token_details( $token ) {
		$result['is_valid'] = false;
		$tokens             = get_site_option( 'boldgrid_backup_tokens', array() );

		foreach ( $tokens as $row ) {
			if ( $token === $row['token'] && time() < $row['expires'] ) {
				$result = array(
					'is_valid' => true,
					'id'       => $row['id'],
					'expires'  => $row['expires'],
				);
				break;
			}
		}

		return $result;
	}
}
