<?php
/**
 * BoldGrid Source Code
 *
 * @copyright BoldGrid.com
 * @version   $Id$
 * @since     1.6.0
 * @author    BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Test Boldgrid_Backup_Admin_Crypt.
 *
 * @since 1.6.0
 */
class Test_Boldgrid_Backup_Admin_Crypt extends WP_UnitTestCase {


	/**
	 * Setup.
	 *
	 * @since 1.6.0
	 */
	public function setUp() {
		// $this->core = new Boldgrid_Backup_Admin_Core();
		$this->settings = array(
			'remote' => array(
				'ftp' => array(
					'address' => '742 Evergreen Terrace',
					'city' => 'Springfield',
				),
				'amazon_s3' => array(
					'address' => '31 Spooner Street',
					'city' => 'Quahog',
				)
			),
		);
	}

	/**
	 * Test crypt.
	 *
	 * @since 1.6.0
	 */
	public function test_crypt() {
		$string = 'my string';

		// Encrypt
		$encrypted = Boldgrid_Backup_Admin_Crypt::crypt( $string );
		$this->assertTrue( is_string( $encrypted ) );
		$this->assertFalse( $string === $encrypted );

		// Decrypt
		$decrypted = Boldgrid_Backup_Admin_Crypt::crypt( $encrypted, 'd' );
		$this->assertTrue( $decrypted === $string );
	}
}