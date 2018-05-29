<?php
/**
 * Test for support from the command line.
 *
 * @since 1.6.2
 *
 * @see Boldgrid_Backup_Admin_Test::get_cli_support().
 */

// Require Boldgrid_Backup_Url_Helper class.
require dirname( __FILE__ ) . '/url-helper.php';
$url_helper = new Boldgrid_Backup_Url_Helper();

$support = array(
	'has_curl_ssl'  => $url_helper->has_curl_ssl(),
	'has_url_fopen' => $url_helper->has_url_fopen(),
);

echo json_encode( $support );
