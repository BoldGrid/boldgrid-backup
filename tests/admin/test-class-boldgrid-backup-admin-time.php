<?php
/**
 * BoldGrid Source Code
 *
 * @package   Test_Boldgrid_Editor
 * @copyright BoldGrid.com
 * @version   $Id$
 * @since     1.6.0
 * @author    BoldGrid.com <wpb@boldgrid.com>
 */

/**
 *
 */
class Test_Boldgrid_Backup_Admin_Time extends WP_UnitTestCase {


	/**
	 * Setup.
	 *
	 * @since 1.6.0
	 */
	public function setUp() {
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
	 * Test offset_to_timezone.
	 *
	 * @since 1.6.0
	 */
	public function test_offset_to_timezone() {
		$timezone = $this->core->time->offset_to_timezone( '+5' );
		$this->assertTrue( $timezone instanceof DateTimeZone );

		$timezone = $this->core->time->offset_to_timezone( '-5' );
		$this->assertTrue( $timezone instanceof DateTimeZone );

		$timezone = $this->core->time->offset_to_timezone( '+0' );
		$this->assertTrue( $timezone instanceof DateTimeZone );

		$timezone = $this->core->time->offset_to_timezone( '' );
		$this->assertSame( false, $timezone );

		$timezone = $this->core->time->offset_to_timezone( 'catfish' );
		$this->assertSame( false, $timezone );

		$timezone = $this->core->time->offset_to_timezone( '+25' );
		$this->assertSame( false, $timezone );

		$timezone = $this->core->time->offset_to_timezone( array() );
		$this->assertSame( false, $timezone );

		$timezone = $this->core->time->offset_to_timezone( true );
		$this->assertSame( false, $timezone );
	}

	/**
	 * Test get_settings_date.
	 *
	 * @since 1.6.0
	 */
	public function test_get_settings_date() {
		$tz_info = $this->core->time->get_timezone_info();

		$settings_date = $this->core->time->get_settings_date();
		$this->assertTrue( $settings_date instanceof DateTime );

		// Pass bad data.
		$settings_date = $this->core->time->get_settings_date( array(
			'schedule' => array(
				'tod_h' => 33,
				'tod_m' => 111,
				'tod_a' => 'catfish',
			)
		));
		$this->assertFalse( $settings_date );

		// Pass bad data.
		$settings_date = $this->core->time->get_settings_date( array(
			'schedule' => array(
				'tod_h' => 1,
				// Should fail because 5:1am is wrong and 5:10 is correct.
				'tod_m' => 1,
				'tod_a' => 'am',
			)
		));
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