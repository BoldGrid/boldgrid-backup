<?php
/**
 * File: bootstrap.php
 *
 * Bootstrap file for tests.
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

if ( ! defined( 'BOLDGRID_BACKUP_PATH' ) ) {
	define( 'BOLDGRID_BACKUP_PATH', dirname( dirname( __FILE__ ) ) );
}

if ( ! defined( 'BOLDGRID_BACKUP_TITLE' ) ) {
	define( 'BOLDGRID_BACKUP_TITLE', 'Total Upkeep' );
}

// Require necessary files.
$files = array(
	'/vendor/boldgrid/library/src/Library/Settings.php',
	'/vendor/boldgrid/library/src/Library/Configs.php',
	'/vendor/boldgrid/library/src/Util/Version.php',
	'/vendor/boldgrid/library/src/Library/Plugin/Factory.php',
	'/vendor/boldgrid/library/src/Library/Plugin/Notice.php',
	'/vendor/boldgrid/library/src/Library/Plugin/Page.php',
	'/vendor/boldgrid/library/src/Library/Plugin/Plugin.php',
	'/vendor/boldgrid/library/src/Library/Plugin/Plugins.php',
	'/vendor/boldgrid/library/src/Library/Plugin/UpdateData.php',
	'/vendor/boldgrid/library/src/Library/Theme/Theme.php',
	'/vendor/boldgrid/library/src/Library/Theme/Themes.php',
	'/vendor/boldgrid/library/src/Library/Theme/UpdateData.php',
	'/admin/class-boldgrid-backup-admin-premium-features.php',
	'/admin/class-boldgrid-backup-admin-core.php',
	'/admin/class-boldgrid-backup-admin.php',
	'/admin/class-boldgrid-backup-admin-settings.php',
	'/admin/class-boldgrid-backup-admin-config.php',
	'/admin/class-boldgrid-backup-admin-test.php',
	'/admin/class-boldgrid-backup-admin-notice.php',
	'/admin/class-boldgrid-backup-admin-cron.php',
	'/admin/class-boldgrid-backup-admin-cron-log.php',
	'/admin/class-boldgrid-backup-admin-cron-test.php',
	'/admin/class-boldgrid-backup-admin-upload.php',
	'/admin/class-boldgrid-backup-admin-xhprof.php',
	'/admin/class-boldgrid-backup-admin-restore-helper.php',
	'/admin/class-boldgrid-backup-admin-restore-git.php',
	'/admin/class-boldgrid-backup-admin-filelist.php',
	'/admin/class-boldgrid-backup-admin-filelist-analyzer.php',
	'/admin/class-boldgrid-backup-admin-backup-dir.php',
	'/admin/class-boldgrid-backup-admin-home-dir.php',
	'/admin/class-boldgrid-backup-admin-compressors.php',
	'/admin/class-boldgrid-backup-admin-auto-updates.php',
	'/admin/class-boldgrid-backup-admin-archive-browser.php',
	'/admin/class-boldgrid-backup-admin-archive.php',
	'/admin/class-boldgrid-backup-admin-archive-actions.php',
	'/admin/class-boldgrid-backup-admin-archives.php',
	'/admin/class-boldgrid-backup-admin-archives-all.php',
	'/admin/class-boldgrid-backup-admin-archive-log.php',
	'/admin/class-boldgrid-backup-admin-archive-details.php',
	'/admin/class-boldgrid-backup-admin-archive-fail.php',
	'/admin/class-boldgrid-backup-admin-archiver-utility.php',
	'/admin/class-boldgrid-backup-admin-dashboard.php',
	'/admin/class-boldgrid-backup-admin-dashboard-widget.php',
	'/admin/class-boldgrid-backup-admin-wp-cron.php',
	'/admin/class-boldgrid-backup-admin-scheduler.php',
	'/admin/class-boldgrid-backup-admin-auto-rollback.php',
	'/admin/class-boldgrid-backup-admin-remote.php',
	'/admin/class-boldgrid-backup-admin-jobs.php',
	'/admin/class-boldgrid-backup-admin-email.php',
	'/admin/class-boldgrid-backup-admin-db-omit.php',
	'/admin/class-boldgrid-backup-admin-db-dump.php',
	'/admin/class-boldgrid-backup-admin-db-import.php',
	'/admin/class-boldgrid-backup-admin-db-get.php',
	'/admin/class-boldgrid-backup-admin-utility.php',
	'/admin/class-boldgrid-backup-admin-folder-exclusion.php',
	'/admin/class-boldgrid-backup-admin-core-files.php',
	'/admin/class-boldgrid-backup-admin-in-progress.php',
	'/admin/class-boldgrid-backup-admin-in-progress-tmp.php',
	'/admin/class-boldgrid-backup-admin-go-pro.php',
	'/admin/class-boldgrid-backup-admin-support.php',
	'/admin/class-boldgrid-backup-admin-tools.php',
	'/admin/class-boldgrid-backup-admin-transfers.php',
	'/admin/class-boldgrid-backup-admin-time.php',
	'/admin/class-boldgrid-backup-admin-crypt.php',
	'/admin/class-boldgrid-backup-admin-cli.php',
	'/admin/class-boldgrid-backup-admin-in-progress-data.php',
	'/admin/class-boldgrid-backup-admin-compressor.php',
	'/admin/class-boldgrid-backup-admin-log.php',
	'/admin/class-boldgrid-backup-admin-log-page.php',
	'/admin/class-boldgrid-backup-admin-environment.php',
	// Cron
	'/admin/cron/entry/class-entry.php',
	'/admin/class-cron.php',
	'/admin/cron/entry/class-base.php',
	'/admin/cron/entry/class-crontab.php',
	'/admin/cron/entry/class-wpcron.php',
	// Tasks.
	'/admin/class-boldgrid-backup-admin-task.php',
	'/admin/class-boldgrid-backup-admin-task-helper.php',
	// Compressors.
	'/admin/compressor/class-boldgrid-backup-admin-compressor-php-zip.php',
	'/admin/compressor/class-boldgrid-backup-admin-compressor-pcl-zip.php',
	'/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip.php',
	'/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip-temp-folder.php',
	'/admin/compressor/class-boldgrid-backup-admin-compressor-system-zip-test.php',
	'/admin/cron/class-crontab.php',
	'/includes/class-boldgrid-backup-authentication.php',
	'/includes/class-boldgrid-backup-download.php',
	'/includes/class-boldgrid-backup-file.php',
	'/includes/class-boldgrid-backup-archiver.php',
	'/includes/archive/class-factory.php',
	'/includes/archive/class-option.php',
	'/includes/archiver/class-info.php',
	'/admin/storage/class-boldgrid-backup-admin-storage-local.php',
	// Remote storage providers.
	'/admin/remote/class-boldgrid-backup-admin-ftp.php',
	'/admin/remote/class-boldgrid-backup-admin-ftp-hooks.php',
	'/admin/remote/class-boldgrid-backup-admin-ftp-page.php',
	'/admin/remote/class-boldgrid-backup-admin-remote-settings.php',
	// Vendor.
	'/vendor/phpseclib/phpseclib/phpseclib/Net/SSH2.php',
	'/vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php',
	// Cli.
	'/cli/class-info.php',
	'/cli/class-log.php',
	'/cli/class-email.php',
	// Orphan.
	'/admin/orphan/class-cleanup.php',
	'/admin/orphan/class-finder.php',
	// V2 Filelist.
	'/includes/v2/filelist/class-create.php',
	// V2 Step.
	'/includes/v2/step/class-step.php',
	'/includes/v2/step/class-data.php',
	'/includes/v2/step/class-json-file.php',
	// V2 Archiver.
	'/includes/v2/archiver/class-factory.php',
	'/includes/v2/archiver/class-archiver.php',
	'/includes/v2/archiver/class-resumer.php',
	'/includes/v2/archiver/steps/class-discovery.php',
	'/includes/v2/archiver/steps/class-archive-database.php',
	'/includes/v2/archiver/steps/class-archive-files.php',
	'/includes/v2/archiver/steps/archive_files/class-part.php',
	'/includes/v2/archiver/steps/archive_files/class-parts.php',
);

foreach ( $files as $file ) {
	require_once BOLDGRID_BACKUP_PATH . $file;
}

/**
 * Debug to console.
 *
 * @since 1.6.0
 *
 * @param mixed $var Message to write to STDERR.
 */
function phpunit_error_log( $var ) {
	fwrite( // phpcs:ignore
		STDERR,
		"\n\n## --------------------\n" .
			print_r( $var, 1 ) . // phpcs:ignore
		"\n## ------------------\n\n"
	);
}

require $_tests_dir . '/includes/bootstrap.php';
