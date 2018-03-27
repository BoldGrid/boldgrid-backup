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
	}

	/**
	 * Test crypt.
	 *
	 * @since 1.6.0
	 */
	public function test_crypt() {

		$valids = array(
			'',
			' ',
			'my string',
			0,
			1.1,
			12345,
		);

		foreach( $valids as $string ) {
			// Encrypt
			$encrypted = Boldgrid_Backup_Admin_Crypt::crypt( $string );
			$this->assertTrue( is_string( $encrypted ) );
			$this->assertFalse( $string === $encrypted );

			// Decrypt
			$decrypted = Boldgrid_Backup_Admin_Crypt::crypt( $encrypted, 'd' );
			// Encrypt a number and you'll get a string.
			$this->assertTrue( $decrypted == $string );
		}

		$invalids = array(
			true,
			false,
			null,
			array( 'catfish' ),
		);

		foreach( $invalids as $invalid ) {
			$encrypted = Boldgrid_Backup_Admin_Crypt::crypt( $invalid );
			$this->assertEquals( $encrypted, $invalid );

			$decrypted = Boldgrid_Backup_Admin_Crypt::crypt( $invalid, 'd' );
			$this->assertEquals( $decrypted, $invalid );
		}
	}
}