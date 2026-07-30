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
	 * Site b base path.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var string
	 */
	private $site_b_base_path;

	/**
	 * Site c base path.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var string
	 */
	private $site_c_base_path;

	/**
	 * Setup.
	 *
	 * This setup method stages the information needed for the tests.
	 * The mock crontab contains old and new (cli) formats of the restore cron job entry.
	 *
	 * @since 1.11.1
	 */
	public function set_up() {
		$this->core      = new Boldgrid_Backup_Admin_Core();
		$this->base_path = BOLDGRID_BACKUP_PATH . '/';

		$site_b_abspath         = dirname( dirname( dirname( $this->base_path ) ) ) . '/site-b/';
		$this->site_b_base_path = $site_b_abspath . 'wp-content/plugins/boldgrid-backup/';

		$site_c_abspath         = dirname( dirname( dirname( dirname( $this->base_path ) ) ) ) . '/';
		$this->site_c_base_path = $site_c_abspath . 'wp-content/plugins/boldgrid-backup/';

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
		 *
		 * // Commands for a site within the original site's path.
		 * 8. A restore command for a different site.
		 * 9. A backup command for a different site.
		 * 10. A run-jobs command for a different site.
		 * 11. A run_jobs command for a different site.
		 *
		 * // Commands for a site outside of the original site's path.
		 * 12. A restore command for a different site.
		 * 13. A backup command for a different site.
		 * 14. A run-jobs command for a different site.
		 * 15. A run_jobs command for a different site.
		 */
		$this->crontab = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1

03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1

09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1

*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.

08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
	}

	/**
	 * Ensure at least one backup archive exists on disk so that get_restore_command()
	 * can successfully initialize an archive via init_by_key(0).
	 *
	 * @since 1.17.2
	 */
	private function maybe_create_backup() {
		$latest_backup = get_option( 'boldgrid_backup_latest_backup' );
		if ( empty( $latest_backup ) ) {
			$this->core->archive_files( true );
		}
	}

	/**
	 * Test that get_restore_command() generates a secret, stores it in the site option,
	 * and embeds the same value in the returned cron entry string.
	 *
	 * @since 1.17.2
	 */
	public function test_get_restore_command_stores_secret() {
		$this->maybe_create_backup();

		delete_site_option( 'boldgrid_backup_cli_cancel_secret' );

		$entry = $this->core->cron->get_restore_command();

		$this->assertNotEmpty( $entry );

		$stored_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', false );
		$this->assertNotFalse( $stored_secret );
		$this->assertNotEmpty( $stored_secret );

		$this->assertStringContainsString( 'cli_cancel_secret=' . $stored_secret, $entry );
	}

	/**
	 * Test that each call to get_restore_command() generates a fresh secret.
	 *
	 * The previous secret must not be reusable after a new restore is scheduled.
	 *
	 * @since 1.17.2
	 */
	public function test_get_restore_command_secret_rotates() {
		$this->maybe_create_backup();

		$this->core->cron->get_restore_command();
		$first_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', false );

		$this->core->cron->get_restore_command();
		$second_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', false );

		$this->assertNotFalse( $first_secret );
		$this->assertNotFalse( $second_secret );
		$this->assertNotEquals( $first_secret, $second_secret );
	}

	/**
	 * Test that get_restore_command() returns an empty string and does not store a
	 * secret when no backup archive is available.
	 *
	 * @since 1.17.2
	 */
	public function test_get_restore_command_no_archive() {
		// Redirect the backup dir to a non-existent path so get_archive_list() returns [].
		$original_dir                             = $this->core->backup_dir->backup_directory;
		$this->core->backup_dir->backup_directory = '/nonexistent/path/that/does/not/exist';

		delete_site_option( 'boldgrid_backup_cli_cancel_secret' );

		$entry = $this->core->cron->get_restore_command();

		// Restore the backup dir before any assertions that could fail.
		$this->core->backup_dir->backup_directory = $original_dir;

		$this->assertEmpty( $entry );
		$this->assertFalse( get_site_option( 'boldgrid_backup_cli_cancel_secret', false ) );
	}

	/**
	 * Test one-time rotation clears stored secrets and mints a new cron_secret.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_rotates_once() {
		$old_secret = 'leaked_cron_secret_from_pre_1_17_3';
		$settings   = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['cron_secret'] = $old_secret;
		update_site_option( 'boldgrid_backup_settings', $settings );
		update_site_option( 'boldgrid_backup_cli_cancel_secret', 'old_cli_cancel_secret' );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		// Ensure in-memory cache does not short-circuit rotation.
		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, $old_secret );

		$ran = $this->core->cron->maybe_rotate_cron_secrets();
		$this->assertTrue( $ran );

		$new_secret = $this->core->cron->get_cron_secret();
		$this->assertNotEmpty( $new_secret );
		$this->assertNotEquals( $old_secret, $new_secret );
		$this->assertFalse(
			hash_equals( $old_secret, $new_secret ),
			'Harvested pre-upgrade secret must no longer match the stored cron_secret.'
		);
		$this->assertFalse( get_site_option( 'boldgrid_backup_cli_cancel_secret', false ) );
		$this->assertSame(
			Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_VERSION,
			get_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION )
		);

		// Second call is a no-op and must not rotate again.
		$second_ran = $this->core->cron->maybe_rotate_cron_secrets();
		$this->assertFalse( $second_ran );
		$this->assertSame( $new_secret, $this->core->cron->get_cron_secret() );
	}

	/**
	 * Gate must be set even when crontab rewrite fails, so the next init does not re-mint.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_sets_gate_when_crontab_rewrite_fails() {
		$old_secret = 'secret_before_failed_crontab_rewrite';
		$settings   = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['cron_secret'] = $old_secret;
		$settings['scheduler']   = 'cron';
		$settings['schedule']    = array(
			'dow_monday' => 1,
			'tod_h'      => 3,
			'tod_m'      => '15',
			'tod_a'      => 'AM',
		);
		update_site_option( 'boldgrid_backup_settings', $settings );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, $old_secret );

		/*
		 * Force add_all_crons to fail after remint by pointing backup_dir at a missing path
		 * (update_cron bails when backup dir is empty/unavailable).
		 */
		$original_dir                             = $this->core->backup_dir->backup_directory;
		$this->core->backup_dir->backup_directory = '/nonexistent/path/that/does/not/exist';

		$ran = $this->core->cron->maybe_rotate_cron_secrets();

		$this->core->backup_dir->backup_directory = $original_dir;

		$this->assertTrue( $ran );
		$this->assertSame(
			Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_VERSION,
			get_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION ),
			'Gate must be set after remint even if crontab rewrite fails.'
		);

		$mid_secret = $this->core->cron->get_cron_secret();
		$this->assertNotEquals( $old_secret, $mid_secret );

		// A subsequent call must not rotate again.
		$this->assertFalse( $this->core->cron->maybe_rotate_cron_secrets() );
		$this->assertSame( $mid_secret, $this->core->cron->get_cron_secret() );
	}

	/**
	 * A pending rollback must keep a usable cancel secret even with no cron_secret to rotate.
	 *
	 * The CLI cancel secret is minted independently of cron_secret, so clearing it without
	 * re-issuing the restore cron would leave the rollback running and uncancellable.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_reissues_cancel_secret_without_old_cron_secret() {
		$this->maybe_create_backup();

		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		unset( $settings['cron_secret'] );
		$settings['scheduler'] = 'cron';
		update_site_option( 'boldgrid_backup_settings', $settings );

		$old_cancel_secret = 'cancel_secret_from_pre_upgrade';
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $old_cancel_secret );
		update_site_option( 'boldgrid_backup_pending_rollback', array( 'deadline' => time() + 300 ) );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, null );

		$this->assertTrue( $this->core->cron->maybe_rotate_cron_secrets() );

		$new_cancel_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', '' );

		delete_site_option( 'boldgrid_backup_pending_rollback' );

		$this->assertNotEmpty(
			$new_cancel_secret,
			'A pending rollback must be left with a usable cancel secret.'
		);
		$this->assertNotEquals( $old_cancel_secret, $new_cancel_secret );
	}

	/**
	 * Test greenfield installs with no prior secret only set the rotation gate.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_greenfield_sets_flag_only() {
		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		unset( $settings['cron_secret'] );
		update_site_option( 'boldgrid_backup_settings', $settings );
		delete_site_option( 'boldgrid_backup_cli_cancel_secret' );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, null );

		$ran = $this->core->cron->maybe_rotate_cron_secrets();
		$this->assertTrue( $ran );
		$this->assertSame(
			Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_VERSION,
			get_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION )
		);

		// No secret should have been minted solely by the greenfield gate.
		$settings_after = get_site_option( 'boldgrid_backup_settings', array() );
		$this->assertTrue( empty( $settings_after['cron_secret'] ) );
	}

	/**
	 * Test restore-info JSON is rewritten to the new cron_secret after rotation.
	 *
	 * @since 1.17.4
	 */
	public function test_refresh_restore_info_cron_secret() {
		$backup_dir = $this->core->backup_dir->get();
		$this->assertNotEmpty( $backup_dir );

		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';
		$storage = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $backup_dir );
		$this->assertNotEmpty( $storage );

		$cli_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$this->assertTrue( \Boldgrid\Backup\Cli\Info::is_valid_secret_format( $cli_secret ) );

		$old_secret = 'old_restore_info_cron_secret';
		$new_secret = 'new_restore_info_cron_secret';
		$path       = trailingslashit( $storage ) . 'restore-info-' . $cli_secret . '.json';

		$payload = wp_json_encode(
			array(
				'cron_secret' => $old_secret,
				'filepath'    => '/tmp/example.zip',
				'restore_cmd' => 'php -qf "boldgrid-backup-cron.php" mode=restore secret=' . $old_secret . ' archive_key=0',
			)
		);
		$this->assertNotFalse( file_put_contents( $path, $payload ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$updated = $this->core->cron->refresh_restore_info_cron_secret( $old_secret, $new_secret );
		$this->assertTrue( $updated );

		$decoded = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->assertSame( $new_secret, $decoded['cron_secret'] );
		$this->assertStringContainsString( 'secret=' . $new_secret, $decoded['restore_cmd'] );
		$this->assertStringNotContainsString( 'secret=' . $old_secret, $decoded['restore_cmd'] );

		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
	}

	/**
	 * Test filtering crontab contents with mode "" (backup).
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_backup() {
		$pattern_expected = $this->base_path . 'boldgrid-backup-cron\.php" mode=backup';

		// Make sure correct pattern is returned for 'backup'.
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'backup' );
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure our 'backup' pattern only filters out 'backup' commands.
		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern();
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure when passing nothing to get_mode_pattern, we get the same results for 'backup'.
		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}

	/**
	 * Test filtering crontab contents with mode "restore".
	 *
	 * @since 1.11.1
	 */
	public function test_filter_crontab_restore() {
		$pattern_expected = $this->base_path . '(boldgrid-backup-cron|cli/bgbkup-cli)\.php" mode=restore';

		// Make sure correct pattern is returned.
		$pattern_from_mode = $this->core->cron->get_mode_pattern( 'restore' );
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure old and new style restore commands are removed.
		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
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

		// Make sure correct pattern is returned.
		$pattern_expected = $this->base_path . '(cron/run_jobs\.php|cron/run-jobs\.php)';
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure both old and new style "run jobs" commands are removed.
		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/15 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" check auto_recovery=0 email=user@example.com log=0 notify=0 >/dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
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

		// Make sure correct pattern is returned.
		$pattern_expected = $this->base_path . 'cli/bgbkup-cli\.php" check';
		$this->assertEquals( $pattern_from_mode, $pattern_expected );

		// Make sure "site check" commands are removed.
		$crontab_filtered = $this->core->cron->filter_crontab( $pattern_from_mode, $this->crontab );
		$crontab_expected = 'MAILTO=""
58 23 * * * echo "2 minutes to midnight"
20 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/5 * * * * php -d register_argc_argv="1" -qf "' . $this->base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
03 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'boldgrid-backup-cron.php" mode=restore siteurl=https://example.com id=12345678 secret=notasecret archive_key=0 archive_filename=boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
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
09 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
21 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/3 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/4 * * * * php -d register_argc_argv="1" -qf "' . $this->site_b_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
# This is a comment.
08 12 * * 4 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cli/bgbkup-cli.php" mode=restore restore notify email=user@example.com backup_id=12345678 zip=/home/user/boldgrid_backup/boldgrid-backup-example.com-12345678-20190905-150000.zip > /dev/null 2>&1
09 4 * * 1 php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'boldgrid-backup-cron.php" mode=backup siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/7 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run-jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
*/8 * * * * php -d register_argc_argv="1" -qf "' . $this->site_c_base_path . 'cron/run_jobs.php" siteurl=https://example.com id=12345678 secret=notasecret > /dev/null 2>&1
';
		$this->assertEquals( $crontab_expected, $crontab_filtered );
	}
}
