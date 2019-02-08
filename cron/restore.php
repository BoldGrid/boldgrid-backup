<?php
/**
 * File: restore.php
 *
 * Emergency restoration script.  This script is used when there is a severe issue with the site
 * which requires immediate restoration from the latest backup archive.
 *
 * @link https://www.boldgrid.com
 * @since 1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput
 */

// Ensure PHP compatibility.
$php_min_version = '5.4';

if ( version_compare( PHP_VERSION, $php_min_version, '<' ) ) {
	echo 'Error: Incompatible PHP version "' . PHP_VERSION . '".  This utility requires "' .
		$php_min_version . '" or higher.' . PHP_EOL;
	exit( 1 );
}

// Run the restoration.
require __DIR__ . '/class-boldgrid-backup-restore.php';
$restore = new BoldGrid_Backup_Restore();
$restore->run();
