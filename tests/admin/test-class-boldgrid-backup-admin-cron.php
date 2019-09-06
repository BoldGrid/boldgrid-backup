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
	 * @since 1.11.1
	 */
	public function setUp() {
		$this->core      = new Boldgrid_Backup_Admin_Core();
		$this->base_path = BOLDGRID_BACKUP_PATH . '/';
		$this->crontab   = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup/cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1

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
		$pattern_expected = $this->base_path . 'boldgrid-backup-cron\.php" mode=';

		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'backup' );
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$pattern_from_mode = $this->core->cron->get_mode_pattern();
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
	';
		$this->assertEquals( $crontab_filtered, $crontab_expected );
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
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
		$this->assertEquals( $crontab_filtered, $crontab_expected );
	}

	/**
	 * Test filtering crontab contents with mode "cron/run-jobs.php".
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_run_jobs() {
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'cron/run-jobs.php' );
		$pattern_expected  = $this->base_path . 'cron/run-jobs\.php';
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
';
		$this->assertEquals( $crontab_filtered, $crontab_expected );
	}

	/**
	 * Test filtering crontab contents for all plugin entries.
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_all() {
		$pattern_from_mode = $this->core->cron->get_mode_pattern( true );
		$pattern_expected  = $this->base_path;
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
';
		$this->assertEquals( $crontab_filtered, $crontab_expected );
	}
}
