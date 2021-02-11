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
	 * Setup.
	 *
	 * @since 1.9.0
	 */
	public function setUp() {
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
