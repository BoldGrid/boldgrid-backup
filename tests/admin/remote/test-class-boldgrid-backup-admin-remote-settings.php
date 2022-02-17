<?php
/**
 * File: test-class-boldgrid-backup-admin-remote-settings.php
 *
 * @link https://www.boldgrid.com
 * @since     1.7.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin/remote
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Remote_Settings
 *
 * @since 1.7.2
 */
class Test_Boldgrid_Backup_Admin_Remote_Settings extends WP_UnitTestCase {

	/**
	 * Initial settings.
	 *
	 * @since 1.7.2
	 */
	public $initial_settings = array(
		'remote' => array(
			'ftp' => array(
				'user'       => 'myuser',
				'pass'       => 'mypass',
				'last_login' => '1234',
			),
		),
	);

	/**
	 * An instace of the Boldgrid_Backup_Admin_Remote_Settings class.
	 *
	 * @since 1.7.2
	 */
	public $remote_settings;

	/**
	 * Setup.
	 *
	 * @since 1.6.0
	 */
	public function set_up() {
		$this->core = new Boldgrid_Backup_Admin_Core();

		add_filter( 'boldgrid_backup_get_core', array( $this->core, 'get_core' ) );

		$this->remote_settings = new Boldgrid_Backup_Admin_Remote_Settings( 'ftp' );
	}

	/**
	 * Test get_last_login().
	 *
	 * @since 1.7.2
	 */
	public function test_get_last_login() {
		update_option( 'boldgrid_backup_settings', $this->initial_settings );

		$last_login = $this->initial_settings['remote']['ftp']['last_login'];

		$this->assertEquals( $last_login, $this->remote_settings->get_last_login() );
	}

	/**
	 * Test get_option().
	 *
	 * @since 1.7.2
	 */
	public function test_get_option() {
		update_option( 'boldgrid_backup_settings', $this->initial_settings );

		$this->assertEquals( $this->initial_settings, $this->remote_settings->get_option() );
	}

	/**
	 * Test get_setting().
	 *
	 * @since 1.7.2
	 */
	public function test_get_setting() {
		update_option( 'boldgrid_backup_settings', $this->initial_settings );

		$this->assertEquals( $this->initial_settings['remote']['ftp']['user'], $this->remote_settings->get_setting( 'user' ) );
	}

	/**
	 * Test get_settings().
	 *
	 * @since 1.7.2
	 */
	public function test_get_settings() {
		update_option( 'boldgrid_backup_settings', $this->initial_settings );

		$this->assertEquals( $this->initial_settings['remote']['ftp'], $this->remote_settings->get_settings() );
	}

	/**
	 * Test is_last_login_valid().
	 *
	 * @since 1.7.2
	 */
	public function test_is_last_login_valid() {
		$lifetime = $this->core->configs['last_login_lifetime'];

		// The default settings should not validate.
		update_option( 'boldgrid_backup_settings', $this->initial_settings );
		$this->assertFalse( $this->remote_settings->is_last_login_valid() );

		// Setting the last login to now should validate.
		$this->remote_settings->set_last_login();
		$this->assertTrue( $this->remote_settings->is_last_login_valid() );

		// Setting the last login to lifetime - an additional minute ago should fail.
		$this->remote_settings->save_setting( 'last_login', time() - $lifetime - 60 );
		$this->assertFalse( $this->remote_settings->is_last_login_valid() );

		// Setting the last login to lifetime + an additional minute ago should pass.
		$this->remote_settings->save_setting( 'last_login', time() - $lifetime + 60 );
		$this->assertTrue( $this->remote_settings->is_last_login_valid() );
	}

	/**
	 * Test save_setting().
	 *
	 * @since 1.7.2
	 */
	public function test_save_setting() {
		$key   = 'host';
		$value = 'boldgrid.com';

		$this->remote_settings->save_setting( $key, $value );

		$this->assertEquals( $value, $this->remote_settings->get_setting( $key ) );
	}

	/**
	 * Test save_settings().
	 *
	 * @since 1.7.2
	 */
	public function test_save_settings() {
		$settings = array(
			'brother' => 'clint',
			'sister'  => 'stephanie',
		);

		$this->remote_settings->save_settings( $settings );

		$this->assertEquals( $settings, $this->remote_settings->get_settings() );
	}

	/**
	 * Test set_last_login().
	 *
	 * @since 1.7.2
	 */
	public function test_set_last_login() {
		update_option( 'boldgrid_backup_settings', $this->initial_settings );

		$this->assertEquals( $this->remote_settings->get_last_login(), $this->initial_settings['remote']['ftp']['last_login'] );

		$this->remote_settings->set_last_login();

		$this->assertNotEquals( $this->remote_settings->get_last_login(), $this->initial_settings['remote']['ftp']['last_login'] );
	}
}
