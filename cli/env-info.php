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
