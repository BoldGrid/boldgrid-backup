<?php
/**
 * File: test-class-boldgrid-backup-admin-time.php
 *
 * @link https://www.boldgrid.com
 * @since     1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Time
 *
 * @since 1.6.0
 */
class Test_Boldgrid_Backup_Admin_Time extends WP_UnitTestCase {
	/**
	 * Setup.
	 *
	 * @since 1.6.0
	 */
	public function set_up() {
		$this->core = new Boldgrid_Backup_Admin_Core();
	}

	/**
	 * Test get_server_timezone.
	 *
	 * @since 1.6.0
	 */
	public function test_get_server_timezone() {
		$server_time = $this->core->time->get_server_timezone();

		$this->assertTrue( $server_time instanceof DateTimeZone );
	}

	/**
	 * Test get_settings_date.
	 *
	 * @since 1.6.0
	 */
	public function test_get_settings_date() {

		$settings = array(
			'schedule' => array(
				'tod_h' => 6,
				'tod_m' => 20,
				'tod_a' => 'pm',
			),
		);

		/*
		 * UTC-4
		 *
		 * The returned time should be UTC. So, in our settings if we set:
		 * 6:20 pm UTC-4, then actual UTC time should be 10:20 pm UTC.
		 */
		$settings_date = $this->core->time->get_settings_date( $settings, array( 'abbr' => 'UTC-4' ) );
		$this->assertEquals( '2220', $settings_date->format( 'Hi' ) );

		// UTC-4:30.
		$settings_date = $this->core->time->get_settings_date(
			$settings, array(
				'abbr'       => 'UTC-4.5',
				'gmt_offset' => -4.5,
			)
		);
		$this->assertEquals( '2250', $settings_date->format( 'Hi' ) );

		// UTC+4.
		$settings_date = $this->core->time->get_settings_date( $settings, array( 'abbr' => 'UTC+4' ) );
		$this->assertEquals( '1420', $settings_date->format( 'Hi' ) );

		// UTC+0.
		$settings_date = $this->core->time->get_settings_date( $settings, array( 'abbr' => 'UTC+0' ) );
		$this->assertEquals( '1820', $settings_date->format( 'Hi' ) );

		// Get New York time (18:20) and convert to UTC (22:20 DST or 23:20 not DST).
		$settings_date = $this->core->time->get_settings_date( $settings, array( 'name' => 'America/New_York' ) );
		$expected      = $settings_date->format( 'I' ) ? '2220' : '2320';
		$settings_date->setTimezone( new DateTimeZone( 'UTC' ) );
		$this->assertEquals( $expected, $settings_date->format( 'Hi' ) );

		// Pass bad data.
		$settings_date = $this->core->time->get_settings_date(
			array(
				'schedule' => array(
					'tod_h' => 33,
					'tod_m' => 111,
					'tod_a' => 'catfish',
				),
			)
		);
		$this->assertFalse( $settings_date );

		// Pass bad data.
		$settings_date = $this->core->time->get_settings_date(
			array(
				'schedule' => array(
					'tod_h' => 1,
					// Should fail because 5:1am is wrong and 5:10 is correct.
					'tod_m' => 1,
					'tod_a' => 'am',
				),
			)
		);
		$this->assertFalse( $settings_date );

		// Pass bad data.
		$settings_date = $this->core->time->get_settings_date( 'car', 'boat' );
		$this->assertFalse( $settings_date );
	}

	/**
	 * Test get_timezone_info method.
	 *
	 * @since 1.6.0
	 */
	public function test_get_timezone_info() {
		$tz_info = $this->core->time->get_timezone_info();

		$this->assertTrue( is_array( $tz_info ) );

		$this->assertTrue( is_string( $tz_info['abbr'] ) );
		$this->assertTrue( is_string( $tz_info['description'] ) );
		$this->assertTrue( is_string( $tz_info['markup_timezone'] ) );
		$this->assertTrue( is_string( $tz_info['markup_change'] ) );

		$this->assertSame( 4, count( $tz_info ) );
	}
}
