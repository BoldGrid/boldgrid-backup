<?php
/**
 * File: env-info.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 */

require_once 'class-info.php';

// Protect access to this script (standalone; WordPress is not loaded).
$provided_secret = isset( $_REQUEST['secret'] ) ? (string) $_REQUEST['secret'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.Security.ValidatedSanitizedInput
if ( '' === $provided_secret ||
	! hash_equals( (string) \Boldgrid\Backup\Cli\Info::get_secret(), $provided_secret ) ) {
	header( 'HTTP/1.1 403 Unauthorized' );
	exit;
}

echo json_encode( array(
	'gateway_interface' => getenv( 'GATEWAY_INTERFACE' ),
	'http_host'         => getenv( 'HTTP_HOST' ),
	'php_sapi_name'     => php_sapi_name(),
	'php_uname'         => php_uname(),
	'php_version'       => phpversion(),
	'server_addr'       => getenv( 'SERVER_ADDR' ) ? getenv( 'SERVER_ADDR' ) : getenv( 'LOCAL_ADDR' ),
	'server_name'       => getenv( 'SERVER_NAME' ),
	'server_protocol'   => getenv( 'SERVER_PROTOCOL' ),
	'server_software'   => getenv( 'SERVER_SOFTWARE' ),
	'uid'               => getmyuid(),
	'username'          => get_current_user(),
) );
