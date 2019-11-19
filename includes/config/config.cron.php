<?php
/**
 * File: config.cron.php
 *
 * Plugin configuration file.
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$core = apply_filters( 'boldgrid_backup_get_core', null );

/*
 * The site_url is mainly needed for crontab, as one linux account may be running more than
 * once instance of this plugin.
 *
 * For example, we could find the backup cron entry by searching for, "mode=backup", but we
 * would get multiple results if more than one site was adding cron entries.
 */
$site_url = site_url();

// Only defined cron entries can be found.
$config = [
	'entries' => [
		'backup' => [
			'search' => [
				'cron'    => [
					'mode=backup',
					'siteurl=' . $site_url . ' ',
				],
				'wp-cron' => [
					$core->wp_cron->hooks['backup'],
				],
			],
		],
	],
];

unset( $core, $site_url );

return $config;
