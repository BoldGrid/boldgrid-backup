<?php
/**
 * File: test-class-info.php
 *
 * @link https://www.boldgrid.com
 * @since     1.9.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

/**
 * Class: Test_Boldgrid_Backup_Cli_Info
 *
 * @since 1.9.0
 */
class Test_Boldgrid_Backup_Cli_Info extends WP_UnitTestCase {

	/**
	 * Memory limit, dummy data.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	public $memory_limit = '256M';

	/**
	 * The original $_SERVER['argv'] value.
	 *
	 * @since 1.9.0
	 * @var array
	 */
	public $original_argv;

	/**
	 * CLI Info helper under test.
	 *
	 * @since 1.9.0
	 * @var \Boldgrid\Backup\Cli\Info
	 */
	public $info;

	/**
	 * Setup.
	 *
	 * @since 1.9.0
	 */
	public function set_up() {
		$this->original_argv = $_SERVER['argv'];
		$this->change_server_argv();
		$this->info = new \Boldgrid\Backup\Cli\Info();
	}

	/**
	 * Set the server's $_SERVER['argv'] to our dummy data.
	 *
	 * @since 1.9.0
	 */
	public function change_server_argv() {
		$dummy_argv = array(
			'/home/user/phpunit.phar',
			'--debug',
			'-d',
			'memory_limit=' . $this->memory_limit,
			'--colors=always',
			'zip=backup.zip',
			'help',
			'siteurl=http://localhost',
		);

		$_SERVER['argv'] = $dummy_argv;
	}

	/**
	 * Reset the serve's $_SERVER['argv'] to its original value.
	 *
	 * @since 1.9.0
	 */
	public function reset_server_argv() {
		$_SERVER['argv'] = $this->original_argv;
	}

	/**
	 * Test choose_method().
	 *
	 * @since 1.9.0
	 */
	public function test_choose_method() {
		// @todo More testing needed here.
		$method = $this->info->choose_method();

		$this->assertEquals( 'ziparchive', $method );
	}

	/**
	 * Test get_arg_value().
	 *
	 * @since 1.9.0
	 */
	public function test_get_arg_value() {
		$this->assertEquals( $this->memory_limit, $this->info->get_arg_value( 'memory_limit' ) );

		$this->assertTrue( is_null( $this->info->get_arg_value( 'catfish' ) ) );
	}

	/**
	 * Test get_cli_args.
	 *
	 * @since 1.9.0
	 */
	public function test_get_cli_args() {
		$this->change_server_argv();

		$args = $this->info->get_cli_args();
		$this->assertEquals( $args['memory_limit'], $this->memory_limit );

		// Reset the server's argv. The original info has been cached by the Info class.
		$this->reset_server_argv();
		$args = $this->info->get_cli_args();
		$this->assertEquals( $args['memory_limit'], $this->memory_limit );
	}

	/**
	 * Test get_info().
	 *
	 * @since 1.9.0
	 */
	public function test_get_info() {
		$this->change_server_argv();

		$info = $this->info->get_info();

		$this->assertEquals( $info['cli_args']['memory_limit'], $this->memory_limit );
	}

	/**
	 * Test get_mode().
	 *
	 * @since 1.9.0
	 */
	public function test_get_mode() {
		$this->assertEquals( 'help', $this->info->get_mode() );
	}

	/**
	 * Test has_arg_flag().
	 *
	 * @since 1.9.0
	 */
	public function test_has_arg_flag() {
		$this->change_server_argv();

		$this->assertTrue( $this->info->has_arg_flag( 'memory_limit' ) );

		$this->assertFalse( $this->info->has_arg_flag( 'catfish' ) );
	}

	/**
	 * Test have_execution_functions().
	 *
	 * @since 1.9.0
	 */
	public function test_have_execution_functions() {
		$this->assertTrue( $this->info->have_execution_functions() );
	}

	/**
	 * Test has_errors().
	 *
	 * @since 1.9.0
	 */
	public function test_has_errors() {
		// @todo More testing needed here.
		$this->assertTrue( $this->info->has_errors() );
	}

	/**
	 * Test is_cli().
	 *
	 * @since 1.9.0
	 */
	public function test_is_cli() {
		// @todo More testing needed here.
		$this->assertTrue( $this->info->is_cli() );
	}

	/**
	 * Test get_results_filepath().
	 *
	 * @since 1.9.0
	 */
	public function test_get_results_filepath() {
		$path = $this->info->get_results_filepath();

		$this->assertTrue( ! empty( $path ) );
		$this->assertStringContainsString( 'restore-info-', $path );
	}

	/**
	 * Test ensure_secure_storage migrates away from webroot verify files.
	 *
	 * @since 1.17.3
	 */
	public function test_ensure_secure_storage_rotates_legacy_verify_secret() {
		$storage_dir = sys_get_temp_dir() . '/bgbak-secure-' . wp_generate_password( 8, false );
		mkdir( $storage_dir, 0700 );

		$legacy_secret = md5( 'legacy-shipped-secret' );
		$legacy_verify = BOLDGRID_BACKUP_PATH . '/cli/verify-' . $legacy_secret . '.php';
		$legacy_info   = BOLDGRID_BACKUP_PATH . '/cron/restore-info-' . $legacy_secret . '.json';

		file_put_contents( $legacy_verify, "<?php // phpcs:disable\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $legacy_info, wp_json_encode( [ 'cron_secret' => 'abc123' ] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$dir = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $storage_dir );
		$this->assertSame( $storage_dir, $dir );

		$new_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$this->assertNotSame( $legacy_secret, $new_secret );
		$this->assertFileExists( $storage_dir . '/.boldgrid-cli-secret' );
		$this->assertFileExists( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() );
		$wp_locator = \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath();
		// Available when WP_CONTENT_DIR is defined or the plugin lives under wp-content/plugins/.
		if ( $wp_locator ) {
			$this->assertFileExists( $wp_locator );
		}
		$this->assertFileDoesNotExist( $legacy_verify );
		$this->assertFileDoesNotExist( $legacy_info );

		$results = \Boldgrid\Backup\Cli\Info::get_results_filepath();
		$this->assertStringStartsWith( $storage_dir, $results );
		$this->assertFileExists( $storage_dir . '/restore-info-' . $new_secret . '.json' );

		$locator = file_get_contents( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->assertStringContainsString( 'SCRIPT_FILENAME', $locator );
		$this->assertStringContainsString( '__FILE__', $locator );

		// Plugin-tree locator may be gone after a plugin update; wp-content fallback remains.
		if ( $wp_locator && file_exists( $wp_locator ) ) {
			@unlink( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$this->assertSame( $storage_dir, \Boldgrid\Backup\Cli\Info::get_dir_from_locator() );
		}

		// Cleanup.
		@unlink( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $wp_locator ) {
			@unlink( $wp_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@unlink( $storage_dir . '/.boldgrid-cli-secret' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		foreach ( glob( $storage_dir . '/restore-info-*.json' ) as $file ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@rmdir( $storage_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Test restore-info migrates even when the legacy verify file is already gone.
	 *
	 * @since 1.17.3
	 */
	public function test_ensure_secure_storage_migrates_orphan_restore_info() {
		$storage_dir = sys_get_temp_dir() . '/bgbak-orphan-' . wp_generate_password( 8, false );
		mkdir( $storage_dir, 0700 );

		$orphan_secret = md5( 'orphan-restore-info' );
		$legacy_info   = BOLDGRID_BACKUP_PATH . '/cron/restore-info-' . $orphan_secret . '.json';
		$payload       = wp_json_encode( [ 'cron_secret' => 'orphan-cron', 'filepath' => '/tmp/backup.zip' ] );

		file_put_contents( $legacy_info, $payload ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$dir = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $storage_dir );
		$this->assertSame( $storage_dir, $dir );
		$this->assertFileDoesNotExist( $legacy_info );

		$new_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$migrated   = $storage_dir . '/restore-info-' . $new_secret . '.json';
		$this->assertFileExists( $migrated );
		$this->assertSame( $payload, file_get_contents( $migrated ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// Cleanup.
		@unlink( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$wp_locator = \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath();
		if ( $wp_locator ) {
			@unlink( $wp_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@unlink( $storage_dir . '/.boldgrid-cli-secret' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		foreach ( glob( $storage_dir . '/restore-info-*.json' ) as $file ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@rmdir( $storage_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Test remigration of restore-info already in the backup directory after secret loss.
	 *
	 * @since 1.17.3
	 */
	public function test_ensure_secure_storage_remigrates_backup_dir_restore_info() {
		$storage_dir = sys_get_temp_dir() . '/bgbak-remigrate-' . wp_generate_password( 8, false );
		mkdir( $storage_dir, 0700 );

		$old_secret = md5( 'previous-install-secret' );
		$payload    = wp_json_encode( [ 'cron_secret' => 'kept-cron', 'filepath' => '/tmp/last.zip' ] );
		$orphan     = $storage_dir . '/restore-info-' . $old_secret . '.json';

		file_put_contents( $orphan, $payload ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		// No .boldgrid-cli-secret — forces a new secret and remigration of the orphan.

		$dir = \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $storage_dir );
		$this->assertSame( $storage_dir, $dir );

		$new_secret = \Boldgrid\Backup\Cli\Info::get_secret();
		$this->assertNotSame( $old_secret, $new_secret );
		$this->assertFileDoesNotExist( $orphan );
		$migrated = $storage_dir . '/restore-info-' . $new_secret . '.json';
		$this->assertFileExists( $migrated );
		$this->assertSame( $payload, file_get_contents( $migrated ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// Cleanup.
		@unlink( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$wp_locator = \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath();
		if ( $wp_locator ) {
			@unlink( $wp_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@unlink( $storage_dir . '/.boldgrid-cli-secret' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		foreach ( glob( $storage_dir . '/restore-info-*.json' ) as $file ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		@rmdir( $storage_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Test ensure_secure_storage carries secret + restore-info across backup dir changes.
	 *
	 * @since 1.17.3
	 */
	public function test_ensure_secure_storage_migrates_on_backup_dir_change() {
		$old_dir = sys_get_temp_dir() . '/bgbak-old-' . wp_generate_password( 8, false );
		$new_dir = sys_get_temp_dir() . '/bgbak-new-' . wp_generate_password( 8, false );
		mkdir( $old_dir, 0700 );
		mkdir( $new_dir, 0700 );

		$this->assertSame( $old_dir, \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $old_dir ) );
		$secret  = \Boldgrid\Backup\Cli\Info::get_secret();
		$payload = wp_json_encode( [ 'cron_secret' => 'dir-change', 'filepath' => '/tmp/old.zip' ] );
		file_put_contents( $old_dir . '/restore-info-' . $secret . '.json', $payload ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$this->assertSame( $new_dir, \Boldgrid\Backup\Cli\Info::ensure_secure_storage( $new_dir ) );
		$this->assertSame( $secret, \Boldgrid\Backup\Cli\Info::get_secret() );
		$this->assertFileExists( $new_dir . '/.boldgrid-cli-secret' );
		$this->assertSame( $secret, trim( (string) file_get_contents( $new_dir . '/.boldgrid-cli-secret' ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$migrated = $new_dir . '/restore-info-' . $secret . '.json';
		$this->assertFileExists( $migrated );
		$this->assertSame( $payload, file_get_contents( $migrated ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->assertSame( $new_dir, \Boldgrid\Backup\Cli\Info::get_dir_from_locator() );

		// Cleanup.
		@unlink( \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$wp_locator = \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath();
		if ( $wp_locator ) {
			@unlink( $wp_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		foreach ( [ $old_dir, $new_dir ] as $dir ) {
			@unlink( $dir . '/.boldgrid-cli-secret' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			foreach ( glob( $dir . '/restore-info-*.json' ) as $file ) {
				@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * When plugin-tree and wp-content locators disagree, prefer wp-content.
	 *
	 * @since 1.17.3
	 */
	public function test_get_dir_from_locator_prefers_wp_content_on_disagreement() {
		$wp_locator = \Boldgrid\Backup\Cli\Info::get_wp_content_locator_filepath();
		if ( ! $wp_locator ) {
			$this->markTestSkipped( 'wp-content locator path unavailable in this environment.' );
		}

		$old_dir = sys_get_temp_dir() . '/bgbak-stale-' . wp_generate_password( 8, false );
		$new_dir = sys_get_temp_dir() . '/bgbak-fresh-' . wp_generate_password( 8, false );
		mkdir( $old_dir, 0700 );
		mkdir( $new_dir, 0700 );

		$plugin_locator = \Boldgrid\Backup\Cli\Info::get_restore_locator_filepath();
		$make_locator   = static function ( $dir ) {
			return "<?php\nreturn " . var_export( $dir, true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		};

		file_put_contents( $plugin_locator, $make_locator( $old_dir ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $wp_locator, $make_locator( $new_dir ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$this->assertSame( $new_dir, \Boldgrid\Backup\Cli\Info::get_dir_from_locator() );

		@unlink( $plugin_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( $wp_locator ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@rmdir( $old_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@rmdir( $new_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Test generate_secret returns a 32-hex CSPRNG value.
	 *
	 * @since 1.17.3
	 */
	public function test_generate_secret() {
		$secret = \Boldgrid\Backup\Cli\Info::generate_secret();
		$this->assertTrue( \Boldgrid\Backup\Cli\Info::is_valid_secret_format( $secret ) );
		$this->assertNotSame( $secret, \Boldgrid\Backup\Cli\Info::generate_secret() );
	}

	/**
	 * Test get_zip_arg().
	 *
	 * @since 1.9.0
	 */
	public function test_get_zip_arg() {
		// @todo More testing needed here.
		$this->assertFalse( $this->info->get_zip_arg() );
	}

	/**
	 * Test read_json_file().
	 *
	 * @since 1.9.0
	 */
	public function test_read_json_file() {
		$dummy_data = array( 'a', 'b', 'c' );

		$file_path = 'file.json';

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}
		$data = $this->info->read_json_file( $file_path );
		$this->assertTrue( empty( $data ) );

		file_put_contents( $file_path, json_encode( $dummy_data ) ); // phpcs:ignore
		$data = $this->info->read_json_file( $file_path );
		$this->assertEquals( $dummy_data, $data );

		unlink( $file_path );
	}
}
