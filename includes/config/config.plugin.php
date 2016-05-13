<?php
/**
 * Plugin configuration file
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

return array(
	'ajax_calls' => array(
		'get_plugin_version' => '/api/open/get-plugin-version',
		'get_asset' => '/api/open/get-asset',
	),
	'asset_server' => 'https://wp-assets.boldgrid.com',
);
