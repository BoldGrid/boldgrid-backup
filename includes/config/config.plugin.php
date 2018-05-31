<?php
/**
 * File: config.plugin.php
 *
 * Plugin configuration file.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
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

return array(
	'ajax_calls'            => array(
		'get_plugin_version' => '/api/open/get-plugin-version',
		'get_asset'          => '/api/open/get-asset',
	),
	'asset_server'          => 'https://wp-assets.boldgrid.com',

	'urls'                  => array(
		'compatibility'       => 'https://www.boldgrid.com/support/advanced-tutorials/backup-compatibility-guide',
		'possible_issues'     => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#possible-issues',
		'reduce_size_warning' => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#reduce-size-warning',
		'resource_usage'      => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#resource-usage',
		'upgrade'             => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#upgrade',
		'user_guide'          => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide',
		'restore'             => 'https://www.boldgrid.com/support/advanced-tutorials/restoring-boldgrid-backup/',
		'setting_directory'   => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#setting-backup-directory',
	),
	'lang'                  => array(
		// translators: 1: Number of seconds.
		'est_pause' => esc_html__( 'Estimated Pause: %s seconds', 'boldgrid-backup' ),
	),
	'plugin_name'           => 'boldgrid-backup',
	'plugin_key_code'       => 'backup',
	'main_file_path'        => BOLDGRID_BACKUP_PATH . '/boldgrid-backup.php',
	'plugin_transient_name' => 'boldgrid_backup_version_data',
);
