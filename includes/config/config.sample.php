<?php
/**
 * File: config.sample.php
 *
 * Plugin sample local override configuration file.
 *
 * @link https://www.boldgrid.com
 * @since 1.0.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Copy this sample file to config.local.php and update it with any variables that you would like
 * to override.
 */
return array(
	'public_link_lifetime' => '1 HOUR',
);
