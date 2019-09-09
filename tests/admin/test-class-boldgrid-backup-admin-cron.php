<?php
/**
 * File: test-class-boldgrid-backup-admin-cron.php
 *
 * @link https://www.boldgrid.com
 * @since     1.11.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Cron
 *
 * @since 1.11.1
 */
class Test_Boldgrid_Backup_Admin_Cron extends WP_UnitTestCase {
	/**
	 * Boldgrid_Backup_Admin_Core object.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * Mock test plugin base path, including trailing slash.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var string
	 */
	private $base_path;

	/**
	 * Mock test crontab contents.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var string
	 */
	private $crontab;

	/**
	 * Setup.
	 *
	 * This setup method stages the information needed for the tests.
	 * The mock crontab contains old and new (cli) formats of the restore cron job entry.
	 *
	 * @since 1.11.1
	 */
	public function setUp() {
		$this->core      = new Boldgrid_Backup_Admin_Core();
		$this->base_path = BOLDGRID_BACKUP_PATH . '/';

		/*
		 * Example crontab.
		 *
		 * After the MAILTO, this crontab has 7 different entries:
		 * 1. A simple echo command; example of a cron job entry not used by this plugin.
		 * 2. The command to create a backup.
		 * 3. The command for site check.
		 * 4. The command for run jobs.
		 * 5. The old command for run jobs.
		 * 6. The command to restore a backup. Versions prior to 1.11.0
		 * 7. The command to restore a backup. Version 1.11.0 and later.
		 */
		$this->crontab = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1

03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1

';
	}

	/**
	 * Test filtering crontab contents with mode "" (backup).
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_backup() {
		$pattern_expected = $this->base_path . 'boldgrid-backup-cron\.php" mode=backup';

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'backup' );
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern();
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}

	/**
	 * Test filtering crontab contents with mode "restore".
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_restore() {
		$pattern_expected  = $this->base_path . '(boldgrid-backup-cron|cli/bgbkup-cli)\.php" mode=restore';
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'restore' );
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}

	/**
	 * Test filtering crontab contents with mode "jobs".
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_run_jobs() {
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'jobs' );
		$pattern_expected  = $this->base_path . '(cron/run_jobs\.php|cron/run-jobs\.php)';
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}

	/**
	 * Test filtering crontab contents with mode "site_check".
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_site_check() {
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'site_check' );
		$pattern_expected  = $this->base_path . 'cli/bgbkup-cli\.php" check';
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}

	/**
	 * Test filtering crontab contents for all plugin entries.
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_all() {
		$pattern_expected = $this->base_path;

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern( true );
		$this->assertEquals( $pattern_expected, $pattern_from_mode );

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'all' );
		$this->assertEquals( $pattern_expected, $pattern_from_mode );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}
}
