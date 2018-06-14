<?php
/**
 * File: test-class-boldgrid-backup-admin-crypt.php
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
 * Class: Test_Boldgrid_Backup_Admin_Crypt
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
		/*
		 * Define keys / salts for crypt.
		 *
		 * These can be defined within the bootstrap.php file, however it will cause hideous warnings
		 * if this test is included alongside wordpress-tests-lib.
		 */
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'C=e+6NW`:pHp|;qT;d$U^OzC.UB34F<ZT=MV@M+n#6CnQ&u8^+H2h+l,<UuNlsl<' );
			define( 'SECURE_AUTH_KEY', '=}z=0#1k@}N#bD l]f!.|Pj2 o<i_$S^^&[2:dujku7lc4*lkE/[H_ng@G&6+`oS' );
			define( 'LOGGED_IN_KEY', '+#72T .$~UKko}8^`+n:(+;s|^t 8+%q|Gczwt-1!%$)3lG3OQ YPu5c[kEiO-&C' );
			define( 'NONCE_KEY', 'd7jiN<_mBPxgH?wlNyw_*r>p% b2h?tS-lVyJb zzYq<.3u~sJ1F.h*]AV-nfL/K' );
			define( 'AUTH_SALT', 'L{UY:O+zr$h>~upnj0$<ZFG|g^m|WW3Pn~+:h6ykDLRi+?0?f`(,ZI.y|=ASVs-e' );
			define( 'SECURE_AUTH_SALT', '[T^dblNJ1+e-gX~!_>Ylada}vK|/ABT|TDKuyz3bteD7>w*Z(!orrJD2LZ{v0SFV' );
			define( 'LOGGED_IN_SALT', 'dxk6&%y`yaZRi9RnYqUVT0 h@Q2oU/n~n`HfL/$q<+X-,xq/g[fteW:e+?m@}uYc' );
			define( 'NONCE_SALT', 'V:#&Qvi>&-?)YUgClfdS^+7wsW21MV+e-UZ]=dCSOGh|x&9mOuXEqJO32N!H#d&m' );
		}
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

		foreach ( $valids as $string ) {
			// Encrypt.
			$encrypted = Boldgrid_Backup_Admin_Crypt::crypt( $string );
			$this->assertTrue( is_string( $encrypted ) );
			$this->assertFalse( $string === $encrypted );

			// Decrypt.
			$decrypted = Boldgrid_Backup_Admin_Crypt::crypt( $encrypted, 'd' );

			// Decrypt a non-string and you'll get a string.
			$this->assertTrue( $decrypted == $string ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		}

		$invalids = array(
			true,
			false,
			null,
			array( 'catfish' ),
		);

		foreach ( $invalids as $invalid ) {
			$encrypted = Boldgrid_Backup_Admin_Crypt::crypt( $invalid );
			$this->assertEquals( $encrypted, $invalid );

			$decrypted = Boldgrid_Backup_Admin_Crypt::crypt( $invalid, 'd' );
			$this->assertEquals( $decrypted, $invalid );
		}
	}
}
