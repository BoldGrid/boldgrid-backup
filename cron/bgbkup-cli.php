<?php
/**
 * File: bgbkup-cli.php
 *
 * Performs site integrity checks and manual and emergency restoration.
 *
 * @link       https://www.boldgrid.com
 * @link       https://github.com/BoldGrid/boldgrid-backup/wiki/Restorations-outside-of-WordPress
 * @since      1.9.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput
 */

namespace Boldgrid\Backup\Cron;

$php_min_version = '5.4';

if ( version_compare( PHP_VERSION, $php_min_version, '<' ) ) {
	echo 'Error: Incompatible PHP version "' . PHP_VERSION . '".  This utility requires "' .
		$php_min_version . '" or higher.' . PHP_EOL;
	exit( 1 );
}

require __DIR__ . '/class-info.php';
require __DIR__ . '/class-site-check.php';

if ( Info::has_errors() ) {
	Info::print_errors();
	exit( 1 );
}

if ( Site_Check::should_restore() ) {
	require __DIR__ . '/class-site-restore.php';
	( new Site_Restore() )->run();
} else {
	echo 'Info: No action taken.' . PHP_EOL;
}
