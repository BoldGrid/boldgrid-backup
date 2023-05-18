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

	/*
	 * An array of banned files.
	 *
	 * @see Boldgrid_Backup_Admin_Folder_Exclusion::is_banned()
	 */
	'banned'               => [
		/*
		 * The ea-php-cli cache symlink. This one has appeared several times, and therefore is now
		 * banned. The following description has been taken from the cPanel website:
		 *
		 * The first time you call one of the ea-php-cli binaries, the system creates the .ea-php-cli.cache
		 * symlink to the PHP version that the directory requires. This symlink provides a quick
		 * way for the system to determine the proper version of PHP and reads as broken by design.
		 * For example, if the PHP script requires PHP 7.0, then the symlink will point to ea-php70.
		 * cPanel creates broken symlinks by design and will recreate any removed symlinks the next
		 * time that you run the script. You can safely ignore them.
		 *
		 * @link https://wordpress.org/support/topic/total-upkeep-error-creating-backup/
		 */
		'.ea-php-cli.cache',
	],

	/*
	 * An array containing error codes and definitons for logging purposes
	 *
	 * @see Boldgrid_Backup_Admin_Log::format_error_info()
	 */
	'error_codes'          => [
		1     => [
			'type'            => 'E_ERROR',
			'description'     => 'Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted.',
			'additional_info' => 'This type of error may indicate a possible issue with the backup process',
		],
		2     => [
			'type'            => 'E_WARNING',
			'description'     => 'Run-time warnings (non-fatal errors). Execution of the script is not halted.',
			'additional_info' => 'Warnings can be ignored safely in most cases. These may indicate a problem if your backup is failing.',
		],
		4     => [
			'type'            => 'E_PARSE',
			'description'     => 'Compile-time parse errors. Parse errors should only be generated by the parser.',
			'additional_info' => 'This type of error may indicate a possible issue with the backup process',
		],
		8     => [
			'type'            => 'E_NOTICE',
			'description'     => 'Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script.',
			'additional_info' => 'Notices can be ignored safely in most cases. These may indicate a problem if your backup is failing.',
		],
		16    => [
			'type'        => 'E_CORE_ERROR',
			'description' => "Fatal errors that occur during PHP's initial startup. This is like an E_ERROR, except it is generated by the core of PHP." ,
		],
		32    => [
			'type'        => 'E_CORE_WARNING',
			'description' => "Warnings (non-fatal errors) that occur during PHP's initial startup. This is like an E_WARNING, except it is generated by the core of PHP.",
		],
		64    => [
			'type'        => 'E_COMPILE_ERROR',
			'description' => 'Fatal compile-time errors. This is like an E_ERROR, except it is generated by the Zend Scripting Engine.',
		],
		128   => [
			'type'        => 'E_COMPILE_WARNING',
			'description' => 'Compile-time warnings (non-fatal errors). This is like an E_WARNING, except it is generated by the Zend Scripting Engine.',
		],
		256   => [
			'type'        => 'E_USER_ERROR',
			'description' => 'User-generated error message. This is like an E_ERROR, except it is generated in PHP code by using the PHP function trigger_error().',
		],
		512   => [
			'type'        => 'E_USER_WARNING',
			'description' => 'User-generated warning message. This is like an E_WARNING, except it is generated in PHP code by using the PHP function trigger_error().',
		],
		1024  => [
			'type'        => 'E_USER_NOTICE',
			'description' => 'User-generated notice message. This is like an E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error().',
		],
		2048  => [
			'type'        => 'E_STRICT',
			'description' => 'Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code.',
		],
		4096  => [
			'type'        => 'E_RECOVERABLE_ERROR',
			'description' => 'Catchable fatal error. It indicates that a probably dangerous error occurred, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR.',
		],
		8192  => [
			'type'            => 'E_DEPRECATED',
			'description'     => 'Run-time notices. These warnings are to provide information about code that will not work in future versions.',
			'additional_info' => 'Deprecation warnings can be ignored safely in most cases. These may indicate a problem if your backup is failing.',
		],
		16384 => [
			'type'        => 'E_USER_DEPRECATED',
			'description' => 'User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error().',
		],

	],
];
