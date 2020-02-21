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
 * @author     BoldGrid <support@boldgrid.com>
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

return [
	'urls'                 => [
		'compatibility'       => 'https://www.boldgrid.com/support/advanced-tutorials/backup-compatibility-guide',
		'possible_issues'     => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#possible-issues',
		'reduce_size_warning' => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#reduce-size-warning',
		'resource_usage'      => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#resource-usage',
		'upgrade'             => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#upgrade',
		'user_guide'          => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide',
		'restore'             => 'https://www.boldgrid.com/support/advanced-tutorials/restoring-boldgrid-backup/',
		'setting_directory'   => 'https://www.boldgrid.com/support/advanced-tutorials/backup-userguide#setting-backup-directory',
		'plugin_renamed'      => 'https://www.boldgrid.com/support/total-upkeep-backup-plugin-product-guide/what-is-total-upkeep/',
	],
	'lang'                 => [
		// translators: 1: Number of seconds.
		'est_pause' => esc_html__( 'Estimated Pause: %s seconds', 'boldgrid-backup' ),
	],
	'public_link_lifetime' => '1 HOUR',
	'url_regex'            => '^https?:\/\/[a-z0-9\-\.]+(\.[a-z]{2,5})?(:[0-9]{1,5})?(\/.*)?$',

	/*
	 * When we login to a remote storage provider, we log the utc timestamp of that login. Sometimes
	 * we want to know if a remote storage provider is setup, and usually we check by trying to log
	 * in successfully. To skip having to log in, we can simply check the last time we logged in.
	 * For example, if we logged in 2 hours ago, usually we can say that the remote storage is setup
	 * correctly because we logged in successfully just 2 hours prior. last_login_lifetime specifies
	 * this time limit. If we logged in within 'last_login_lifetime' ago, assume the remote storage
	 * is still setup successfully. This is not across the board though, each storage provider must
	 * setup this last login cache and check against it.
	 */
	'last_login_lifetime'  => DAY_IN_SECONDS,

	/*
	 * An array of premium remote storage providers.
	 *
	 * This config is not needed for any premium features to work. Instead, it is holding the info
	 * we need to help inform users about the glory that awaits them after upgrading.
	 *
	 * @param array premium_remote {
	 * 		An array of premium remote storage providers.
	 *
	 * 		@type string title      The title of the provider.
	 * 		@type string logo_class The class used to display the logo, used in the following way:
	 *                              <span class="bgbkup-gdrive-logo" title="Google Drive"></span>
	 * }
	 */
	'premium_remote'       => [
		'google_drive' => [
			'title'      => __( 'Google Drive', 'boldgrid-backup' ),
			'logo_class' => 'bgbkup-gdrive-logo',
			'key'        => 'google_drive',
		],
		'amazon_s3'    => [
			'title'      => __( 'Amazon S3', 'boldgrid-backup' ),
			'logo_class' => 'amazon-s3-logo',
			'key'        => 'amazon_s3',
		],
		'dreamobjects' => [
			'title' => __( 'DreamObjects', 'boldgrid-backup' ),
			'key'   => 'dreamobjects',
		],
	],

	// The time, in seconds, that log files are kept for.
	'max_log_age'          => 30 * DAY_IN_SECONDS,

	/*
	 * Plugin_notices is used to add "unread" notice counts to various
	 * UI locations within boldgrid plugin. This config is used by
	 * Boldgrid\Library\Library\NoticeCounts
	 */
	'pages'                => [
		'boldgrid-backup-premium-features',
	],
	'page_notices'         => [
		[
			'id'      => 'bgbkup_database_encryption',
			'page'    => 'boldgrid-backup-premium-features',
			'version' => '1.13.0',
		],
		[
			'id'      => 'bgbkup_timely_auto_updates',
			'page'    => 'boldgrid-backup-premium-features',
			'version' => '1.14.0',
		],
	],
];
