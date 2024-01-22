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
 * @subpackage Boldgrid_Backup\Cli
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @see \Boldgrid\Backup\Cli\Info::has_errors()
 * @see \Boldgrid\Backup\Cli\Info::print_errors()
 * @see \Boldgrid\Backup\Cli\Site_Check::should_restore()
 * @see \Boldgrid\Backup\Cli\Site_Restore::run()
 * @see \Boldgrid\Backup\Cli\Log::write()
 *
 * phpcs:disable WordPress.VIP,WordPress.XSS.EscapeOutput
 */

namespace Boldgrid\Backup\Cli;

$php_min_version = '5.4';

if ( version_compare( PHP_VERSION, $php_min_version, '<' ) ) {
	echo 'Error: Incompatible PHP version "' . PHP_VERSION . '".  This utility requires "' .
		$php_min_version . '" or higher.' . PHP_EOL;
	exit( 1 );
}

/*
 * We need to ensure that this is only run from the command-line.
 * Some environments use different SAPI names for CLI, such as 'cli'
 * or 'cli-server'. Therefore we check for the first three characters.
 */
$sapi_type = php_sapi_name();

if ( is_string( $sapi_type ) && 'cli' !== substr( $sapi_type, 0, 3 ) ) {
	throw new \Exception( 'This script must be run from the command line.' );
}

require __DIR__ . '/class-info.php';
require __DIR__ . '/class-site-check.php';
require __DIR__ . '/class-log.php';
require __DIR__ . '/class-email.php';

if ( Info::has_errors() ) {
	Info::print_errors();
	exit( 1 );
}

if ( Site_Check::should_restore() ) {
	require __DIR__ . '/class-site-restore.php';
	( new Site_Restore() )->run();
} else {
	$message = 'Info: No action taken.';
	echo $message . PHP_EOL;
	Log::write( $message, LOG_INFO );
}
