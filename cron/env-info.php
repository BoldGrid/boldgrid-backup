<?php
/**
 * File: env-info.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

var_dump( array(
	'gateway_interface'  => getenv( 'GATEWAY_INTERFACE' ),
	'php_sapi_name'      => php_sapi_name(),
	'php_uname'          => php_uname(),
	'php_version'        => phpversion(),
	'server_protocol'    => getenv( 'SERVER_PROTOCOL' ),
	'server_software'    => getenv( 'SERVER_SOFTWARE' ),
) );
