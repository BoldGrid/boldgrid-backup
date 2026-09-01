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

		delete_site_option( Boldgrid_Backup_Admin_Cron::CRONTAB_UPGRADE_ATTEMPTED_OPTION );
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

		$backup_id      = $this->core->get_backup_identifier();
		$_GET['id']     = $backup_id;
		$_GET['secret'] = $old_secret;
		wp_set_current_user( 0 );

		$this->assertTrue(
			$this->core->cron->is_valid_call(),
			'Pre-rotation secret must still authorize before upgrade rotation runs.'
		);

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

		$_GET['secret'] = $old_secret;
		$this->assertFalse(
			$this->core->cron->is_valid_call(),
			'Harvested pre-upgrade secret must be rejected after rotation.'
		);
		$_GET['secret'] = $new_secret;
		$this->assertTrue(
			$this->core->cron->is_valid_call(),
			'Fresh post-rotation secret must authorize legitimate cron calls.'
		);
		unset( $_GET['id'], $_GET['secret'] );

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
		 * Crontab writes are blocked suite-wide by the bootstrap, so add_all_crons() cannot
		 * succeed here. Assert that precondition so this test cannot silently stop covering
		 * the failed-rewrite path.
		 */
		$this->assertFalse(
			( new \Boldgrid\Backup\Admin\Cron\Crontab() )->write_crontab( '# no-op' ),
			'Crontab writes must be blocked for this test to cover a failed rewrite.'
		);

		$ran = $this->core->cron->maybe_rotate_cron_secrets();

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
	 * Takeover recovery must refresh restore-info even when the prior cron_secret is gone.
	 *
	 * After a failed request clears settings['cron_secret'], refresh cannot match by the old
	 * value. An empty $old_secret must rewrite any file still holding a different secret.
	 *
	 * @since 1.17.4
	 */
	public function test_refresh_restore_info_cron_secret_rewrites_without_old_secret() {
		$backup_dir = $this->core->backup_dir->get();
		$this->assertNotEmpty( $backup_dir );

		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';
		$storage = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $backup_dir );
		$this->assertNotEmpty( $storage );

		$cli_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$this->assertTrue( \Boldgrid\Backup\Cli\Info::is_valid_secret_format( $cli_secret ) );

		$harvested  = 'harvested_secret_unknown_to_takeover';
		$new_secret = 'takeover_recovery_cron_secret';
		$path       = trailingslashit( $storage ) . 'restore-info-' . $cli_secret . '.json';

		$payload = wp_json_encode(
			array(
				'cron_secret' => $harvested,
				'filepath'    => '/tmp/example.zip',
				'restore_cmd' => 'php -qf "boldgrid-backup-cron.php" mode=restore secret=' . $harvested . ' archive_key=0',
			)
		);
		$this->assertNotFalse( file_put_contents( $path, $payload ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$updated = $this->core->cron->refresh_restore_info_cron_secret( '', $new_secret );
		$this->assertTrue( $updated );

		$decoded = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->assertSame( $new_secret, $decoded['cron_secret'] );
		$this->assertStringContainsString( 'secret=' . $new_secret, $decoded['restore_cmd'] );
		$this->assertStringNotContainsString( 'secret=' . $harvested, $decoded['restore_cmd'] );

		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
	}

	/**
	 * Abandoned-claim takeover must rewrite restore-info after settings lost the old secret.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_takeover_refreshes_restore_info() {
		$backup_dir = $this->core->backup_dir->get();
		$this->assertNotEmpty( $backup_dir );

		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';
		$storage = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $backup_dir );
		$this->assertNotEmpty( $storage );

		$cli_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$this->assertTrue( \Boldgrid\Backup\Cli\Info::is_valid_secret_format( $cli_secret ) );

		$harvested = 'harvested_secret_left_in_restore_info';
		$path      = trailingslashit( $storage ) . 'restore-info-' . $cli_secret . '.json';
		$payload   = wp_json_encode(
			array(
				'cron_secret' => $harvested,
				'filepath'    => '/tmp/example.zip',
				'restore_cmd' => 'php -qf "boldgrid-backup-cron.php" mode=restore secret=' . $harvested . ' archive_key=0',
			)
		);
		$this->assertNotFalse( file_put_contents( $path, $payload ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		/*
		 * Simulate a request that died after clearing secrets but before the gate / restore-info
		 * rewrite: settings no longer hold the harvested value, yet restore-info still does.
		 */
		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		unset( $settings['cron_secret'] );
		$settings['scheduler'] = 'cron';
		update_site_option( 'boldgrid_backup_settings', $settings );
		delete_site_option( 'boldgrid_backup_cli_cancel_secret' );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, null );

		update_site_option(
			Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_OPTION,
			time() - ( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_TIMEOUT + 1 )
		);

		$this->assertTrue( $this->core->cron->maybe_rotate_cron_secrets() );

		$new_secret = $this->core->cron->get_cron_secret();
		$decoded    = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions

		$this->assertNotEmpty( $new_secret );
		$this->assertIsArray( $decoded );
		$this->assertSame( $new_secret, $decoded['cron_secret'] );
		$this->assertStringContainsString( 'secret=' . $new_secret, $decoded['restore_cmd'] );
		$this->assertStringNotContainsString( 'secret=' . $harvested, $decoded['restore_cmd'] );
	}

	/**
	 * Write a legacy plugin-tree restore-info file holding the given cron secret.
	 *
	 * @since 1.17.4
	 *
	 * @param  string $cron_secret Cron secret to embed.
	 * @return string Absolute path to the file written.
	 */
	private function write_legacy_restore_info( $cron_secret ) {
		$path = BOLDGRID_BACKUP_PATH . '/cron/restore-info-' . str_repeat( 'a', 32 ) . '.json';

		$payload = wp_json_encode(
			array(
				'cron_secret' => $cron_secret,
				'filepath'    => '/tmp/example.zip',
				'restore_cmd' => 'php -qf "boldgrid-backup-cron.php" mode=restore secret=' . $cron_secret . ' archive_key=0',
			)
		);

		$this->assertNotFalse( file_put_contents( $path, $payload ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return $path;
	}

	/**
	 * A retired plugin-tree copy must be deleted rather than given the new secret.
	 *
	 * The CLI cannot reach it once secure storage exists, and the plugin tree is not a
	 * guaranteed-private location, so the fresh secret must not be written there.
	 *
	 * @since 1.17.4
	 */
	public function test_refresh_restore_info_cron_secret_deletes_retired_legacy_copy() {
		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';
		$this->assertNotEmpty( \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $this->core->backup_dir->get() ) );

		$old_secret = 'old_legacy_cron_restore_info_secret';
		$path       = $this->write_legacy_restore_info( $old_secret );

		$this->core->cron->refresh_restore_info_cron_secret( $old_secret, 'new_legacy_cron_restore_info_secret' );

		$exists    = file_exists( $path );
		$remaining = $exists ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions

		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions

		$this->assertFalse( $exists, 'A retired plugin-tree restore-info copy must be deleted.' );
		$this->assertStringNotContainsString( 'new_legacy_cron_restore_info_secret', $remaining );
	}

	/**
	 * A live plugin-tree copy must be rewritten so emergency restore keeps working.
	 *
	 * Sites where secure storage is unavailable keep their live restore metadata in the
	 * plugin's cron/ directory, so skipping it would leave emergency CLI restore calling
	 * admin-ajax with a secret that is no longer valid.
	 *
	 * @since 1.17.4
	 */
	public function test_refresh_restore_info_cron_secret_rewrites_live_legacy_copy() {
		require_once BOLDGRID_BACKUP_PATH . '/cli/class-info.php';

		$old_secret = 'old_live_legacy_cron_secret';
		$new_secret = 'new_live_legacy_cron_secret';
		$path       = $this->write_legacy_restore_info( $old_secret );

		/*
		 * Hide the restore locators so Info reports no secure storage, which is the state
		 * that leaves the plugin tree serving as the live metadata location.
		 */
		$hidden = array();
		foreach ( array( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath(), \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath() ) as $locator ) {
			if ( ! empty( $locator ) && file_exists( $locator ) && rename( $locator, $locator . '.test-bak' ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				$hidden[] = $locator;
			}
		}

		$updated = $this->core->cron->refresh_restore_info_cron_secret( $old_secret, $new_secret );

		foreach ( $hidden as $locator ) {
			rename( $locator . '.test-bak', $locator ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		}

		$decoded = file_exists( $path ) ? json_decode( file_get_contents( $path ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions

		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions

		$this->assertTrue( $updated );
		$this->assertIsArray( $decoded, 'The live plugin-tree copy must be kept, not deleted.' );
		$this->assertSame( $new_secret, $decoded['cron_secret'] );
		$this->assertStringContainsString( 'secret=' . $new_secret, $decoded['restore_cmd'] );
		$this->assertStringNotContainsString( 'secret=' . $old_secret, $decoded['restore_cmd'] );
	}

	/**
	 * Rotation must not run while another request holds a fresh claim.
	 *
	 * Without the claim, two concurrent requests can both pass the gate check and remint,
	 * leaving crontab and settings holding different secrets. An abandoned claim must
	 * still be taken over so a request that died mid-rotation cannot block the upgrade.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_respects_rotation_claim() {
		$old_secret = 'secret_guarded_by_rotation_claim';
		$settings   = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['cron_secret'] = $old_secret;
		update_site_option( 'boldgrid_backup_settings', $settings );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, $old_secret );

		// Another request just claimed the rotation.
		update_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_OPTION, time() );

		$this->assertFalse( $this->core->cron->maybe_rotate_cron_secrets() );
		$this->assertFalse( get_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION, false ) );

		$settings_after = get_site_option( 'boldgrid_backup_settings', array() );
		$this->assertSame(
			$old_secret,
			$settings_after['cron_secret'],
			'A claimed rotation must not remint in a second request.'
		);

		// An abandoned claim must not block the upgrade forever.
		update_site_option(
			Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_OPTION,
			time() - ( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_TIMEOUT + 1 )
		);

		$this->assertTrue( $this->core->cron->maybe_rotate_cron_secrets() );
		$this->assertNotEquals( $old_secret, $this->core->cron->get_cron_secret() );
		$this->assertFalse( get_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATING_OPTION, false ) );
	}

	/**
	 * has_active_direct_transfer must tolerate missing status and match migrate status values.
	 *
	 * @since 1.17.4
	 */
	public function test_has_active_direct_transfer() {
		$config       = $this->core->configs['direct_transfer'];
		$option_names = $config['option_names'];
		$transfer_id  = 'xfer_status_guard_test';

		delete_option( $option_names['active_transfer'] );
		delete_option( $option_names['active_tx'] );
		delete_option( $option_names['transfers'] );

		$this->assertFalse( $this->core->cron->has_active_direct_transfer() );

		update_option( $option_names['active_transfer'], $transfer_id, false );
		update_option(
			$option_names['transfers'],
			array(
				$transfer_id => array(
					'transfer_id' => $transfer_id,
					'status'      => 'transferring',
				),
			),
			false
		);
		$this->assertTrue( $this->core->cron->has_active_direct_transfer() );

		// Missing status must not warn/fatal; treat as active so upgrade cannot drop the cron.
		update_option(
			$option_names['transfers'],
			array(
				$transfer_id => array(
					'transfer_id' => $transfer_id,
				),
			),
			false
		);
		$this->assertTrue( $this->core->cron->has_active_direct_transfer() );

		// Migrate writes "canceled"; also accept the historical "cancelled" spelling.
		update_option(
			$option_names['transfers'],
			array(
				$transfer_id => array(
					'transfer_id' => $transfer_id,
					'status'      => 'canceled',
				),
			),
			false
		);
		$this->assertFalse( $this->core->cron->has_active_direct_transfer() );

		update_option(
			$option_names['transfers'],
			array(
				$transfer_id => array(
					'transfer_id' => $transfer_id,
					'status'      => 'cancelled',
				),
			),
			false
		);
		$this->assertFalse( $this->core->cron->has_active_direct_transfer() );

		update_option(
			$option_names['transfers'],
			array(
				$transfer_id => array(
					'transfer_id' => $transfer_id,
					'status'      => 'completed',
				),
			),
			false
		);
		$this->assertFalse( $this->core->cron->has_active_direct_transfer() );

		delete_option( $option_names['active_transfer'] );
		delete_option( $option_names['transfers'] );

		update_option(
			$option_names['active_tx'],
			array(
				'transfer_id' => $transfer_id,
				'status'      => 'pending',
			),
			false
		);
		$this->assertTrue( $this->core->cron->has_active_direct_transfer() );

		delete_option( $option_names['active_tx'] );
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

	/**
	 * upgrade_crontab_entries must re-add a pending rollback after add_all_crons clears.
	 *
	 * When crontab_version is missing and there is no backup schedule, add_all_crons
	 * clears plugin crontab lines. The follow-up must call add_restore_cron so the
	 * cancel secret is reminted (observable even when crontab writes are blocked).
	 *
	 * @since 1.17.4
	 */
	public function test_upgrade_crontab_entries_reissues_pending_rollback() {
		$this->maybe_create_backup();

		// Force the system-cron path so this assertion does not depend on host crontab probes.
		$this->core->scheduler->available = array(
			'cron' => array(
				'title' => 'Cron',
			),
		);

		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['scheduler'] = 'cron';
		unset( $settings['schedule'], $settings['crontab_version'] );
		update_site_option( 'boldgrid_backup_settings', $settings );

		$old_cancel_secret = 'cancel_secret_before_crontab_upgrade';
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $old_cancel_secret );
		update_site_option( 'boldgrid_backup_pending_rollback', array( 'deadline' => time() + 300 ) );

		$this->core->cron->upgrade_crontab_entries();

		$new_cancel_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', '' );
		delete_site_option( 'boldgrid_backup_pending_rollback' );

		$this->assertNotEmpty( $new_cancel_secret );
		$this->assertNotEquals(
			$old_cancel_secret,
			$new_cancel_secret,
			'Pending rollback restore cron must be re-issued after upgrade_crontab_entries.'
		);
	}

	/**
	 * upgrade_crontab_entries must not schedule a system restore cron for wp-cron sites.
	 *
	 * @since 1.17.4
	 */
	public function test_upgrade_crontab_entries_skips_system_restore_for_wp_cron() {
		$this->maybe_create_backup();

		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['scheduler'] = 'wp-cron';
		unset( $settings['schedule'], $settings['crontab_version'] );
		update_site_option( 'boldgrid_backup_settings', $settings );

		$old_cancel_secret = 'cancel_secret_wp_cron_must_keep';
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $old_cancel_secret );
		update_site_option( 'boldgrid_backup_pending_rollback', array( 'deadline' => time() + 300 ) );

		$this->core->cron->upgrade_crontab_entries();

		delete_site_option( 'boldgrid_backup_pending_rollback' );

		$this->assertSame(
			$old_cancel_secret,
			get_site_option( 'boldgrid_backup_cli_cancel_secret', '' ),
			'wp-cron sites must not get a system restore cron from upgrade_crontab_entries.'
		);
	}

	/**
	 * Rotation must use scheduler->get() fallback when settings never saved a scheduler.
	 *
	 * Auto-rollback schedules via the fallback. Reading only settings['scheduler'] would
	 * skip restore re-issue after clearing cli_cancel_secret on those sites.
	 *
	 * @since 1.17.4
	 */
	public function test_maybe_rotate_cron_secrets_uses_scheduler_fallback() {
		$this->maybe_create_backup();

		$this->core->scheduler->available = array(
			'cron' => array(
				'title' => 'Cron',
			),
		);

		$old_secret = 'secret_with_no_scheduler_setting';
		$settings   = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['cron_secret'] = $old_secret;
		unset( $settings['scheduler'] );
		update_site_option( 'boldgrid_backup_settings', $settings );

		$old_cancel_secret = 'cancel_secret_before_scheduler_fallback';
		update_site_option( 'boldgrid_backup_cli_cancel_secret', $old_cancel_secret );
		update_site_option( 'boldgrid_backup_pending_rollback', array( 'deadline' => time() + 300 ) );
		delete_site_option( Boldgrid_Backup_Admin_Cron::SECRETS_ROTATED_OPTION );

		$reflection = new ReflectionClass( $this->core->cron );
		$property   = $reflection->getProperty( 'cron_secret' );
		$property->setAccessible( true );
		$property->setValue( $this->core->cron, $old_secret );

		$this->assertSame( 'cron', $this->core->scheduler->get() );
		$this->assertTrue( $this->core->cron->maybe_rotate_cron_secrets() );

		$new_cancel_secret = get_site_option( 'boldgrid_backup_cli_cancel_secret', '' );
		delete_site_option( 'boldgrid_backup_pending_rollback' );

		$this->assertNotEmpty( $new_cancel_secret );
		$this->assertNotEquals(
			$old_cancel_secret,
			$new_cancel_secret,
			'Fallback system-cron sites must re-issue the pending rollback cancel secret.'
		);
		$this->assertNotEquals( $old_secret, $this->core->cron->get_cron_secret() );
	}

	/**
	 * entry_delete must not TypeError when get_all() returns false.
	 *
	 * @since 1.17.5
	 */
	public function test_entry_delete_handles_get_all_false() {
		$cron = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'get_all' ) )
			->getMock();

		$cron->method( 'get_all' )->willReturn( false );

		$this->assertFalse(
			$cron->entry_delete( '# Total Upkeep Test Entry (You can delete this line).' ),
			'Unread crontab must fail the delete without a TypeError.'
		);
	}

	/**
	 * entry_delete is a no-op success when the entry is already absent.
	 *
	 * @since 1.17.5
	 */
	public function test_entry_delete_missing_entry_returns_true() {
		$cron = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'get_all' ) )
			->getMock();

		$cron->method( 'get_all' )->willReturn(
			array(
				'# unrelated crontab line',
			)
		);

		$this->assertTrue(
			$cron->entry_delete( '# Total Upkeep Test Entry (You can delete this line).' )
		);
	}

	/**
	 * Upgrade crontab entries must not probe crontab when the format version already matches.
	 *
	 * @since 1.17.5
	 */
	public function test_upgrade_crontab_entries_skips_when_version_matches() {
		$cron = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'add_all_crons' ) )
			->getMock();

		$cron->expects( $this->never() )->method( 'add_all_crons' );

		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['scheduler']       = 'cron';
		$settings['crontab_version'] = $cron->crontab_version;
		update_site_option( 'boldgrid_backup_settings', $settings );

		$this->assertFalse( $cron->upgrade_crontab_entries() );
	}

	/**
	 * A failed crontab upgrade must not rerun add_all_crons on the next admin_init.
	 *
	 * @since 1.17.5
	 */
	public function test_upgrade_crontab_entries_gates_failed_attempt() {
		$this->core->scheduler->available = array(
			'cron' => array(
				'title' => 'Cron',
			),
		);

		$cron = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'add_all_crons' ) )
			->getMock();

		$cron->expects( $this->once() )->method( 'add_all_crons' )->willReturn( false );

		$settings = get_site_option( 'boldgrid_backup_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['scheduler'] = 'cron';
		unset( $settings['crontab_version'] );
		update_site_option( 'boldgrid_backup_settings', $settings );

		$this->assertFalse( $cron->upgrade_crontab_entries() );
		$this->assertSame(
			$cron->crontab_version,
			get_site_option( Boldgrid_Backup_Admin_Cron::CRONTAB_UPGRADE_ATTEMPTED_OPTION )
		);
		$this->assertFalse( $cron->upgrade_crontab_entries() );
	}

	/**
	 * Update cron must not rewrite crontab when get_all() cannot read it.
	 *
	 * @since 1.17.5
	 */
	public function test_update_cron_aborts_when_get_all_false() {
		$cron = $this->getMockBuilder( Boldgrid_Backup_Admin_Cron::class )
			->setConstructorArgs( array( $this->core ) )
			->setMethods( array( 'get_all', 'entry_exists' ) )
			->getMock();

		$cron->method( 'get_all' )->willReturn( false );
		$cron->method( 'entry_exists' )->willReturn( false );

		$this->assertFalse(
			$cron->update_cron( '# Total Upkeep Test Entry (You can delete this line).' )
		);
	}
}
