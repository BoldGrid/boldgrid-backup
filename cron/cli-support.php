<?php
/**
 * File: cli-support.php
 *
 * Test for support from the command line.
 *
 * @link       https://www.boldgrid.com
 * @since      1.6.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * @see Boldgrid_Backup_Admin_Test::get_cli_support().
 */

// phpcs:disable WordPress.WP.AlternativeFunctions

// Require Boldgrid_Backup_Url_Helper class.
require dirname( __FILE__ ) . '/class-boldgrid-backup-url-helper.php';

$url_helper = new Boldgrid_Backup_Url_Helper();

die(
	json_encode(
		array(
			'has_curl_ssl'  => $url_helper->has_curl_ssl(),
			'has_url_fopen' => $url_helper->has_url_fopen(),
		)
	)
);
