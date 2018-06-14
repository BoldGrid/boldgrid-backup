<?php
/**
 * File: class-boldgrid-backup-admin-crypt.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Crypt
 *
 * @since 1.6.0
 */
class Boldgrid_Backup_Admin_Crypt {
	/**
	 * Encrypt and decrypt.
	 *
	 * @author Nazmul Ahsan <n.mukto@gmail.com>
	 * @link   http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
	 *
	 * @param  string $string String to be encrypted/decrypted.
	 * @param  string $action e for encrypt, d for decrypt.
	 * @return string
	 */
	public static function crypt( $string, $action = 'e' ) {
		/*
		 * We are only encrypting strings and numbers. User beware, encrypt a
		 * number, it will be a string when decrypted.
		 */
		if ( ! is_string( $string ) && ! is_numeric( $string ) ) {
			return $string;
		}

		$output         = false;
		$encrypt_method = 'AES-256-CBC';
		$key            = hash( 'sha256', AUTH_KEY );
		$iv             = substr( hash( 'sha256', SECURE_AUTH_KEY ), 0, 16 );

		if ( 'e' === $action ) {
			$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		} elseif ( 'd' === $action ) {
			$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		return $output;
	}

	/**
	 * When getting the settings option, decrypt it.
	 *
	 * @since 1.6.0
	 *
	 * @param  mixed  $value  Value of option.
	 * @param  string $option Name of option.
	 * @return mixed
	 */
	public static function option_settings( $value, $option ) {
		// Decrypt all remote (ftp / S3) credentials.
		if ( ! empty( $value['remote'] ) ) {
			foreach ( $value['remote'] as $remote_type => &$remote_settings ) {
				foreach ( $remote_settings as &$remote_setting ) {
					$remote_setting = self::crypt( $remote_setting, 'd' );
				}
			}
		}

		return $value;
	}

	/**
	 * When updating the settings option, encrypt it.
	 *
	 * @since 1.6.0
	 *
	 * @param  mixed  $value Value of option.
	 * @param  mixed  $old_value Old value of option.
	 * @param  string $option Option name.
	 * @return mixed
	 */
	public static function pre_update_settings( $value, $old_value, $option ) {
		// Encrypt all remote (ftp / S3) credentials.
		if ( ! empty( $value['remote'] ) ) {
			foreach ( $value['remote'] as $remote_type => &$remote_settings ) {
				foreach ( $remote_settings as &$remote_setting ) {
					$remote_setting = self::crypt( $remote_setting );
				}
			}
		}

		return $value;
	}
}
