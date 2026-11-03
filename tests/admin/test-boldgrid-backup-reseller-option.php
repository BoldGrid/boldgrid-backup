<?php
/**
 * A missing reseller option is exposed as an array.
 *
 * @package Boldgrid_Backup
 */

/**
 * Class: Test_Boldgrid_Backup_Reseller_Option
 */
class Test_Boldgrid_Backup_Reseller_Option extends WP_UnitTestCase {

	/**
	 * False and other non-arrays become an empty array. Arrays pass through.
	 */
	public function test_reseller_option_is_always_an_array() {
		require_once BOLDGRID_BACKUP_PATH . '/includes/reseller-option.php';

		$this->assertSame( array(), boldgrid_backup_reseller_option( false ) );
		$this->assertSame( array(), boldgrid_backup_reseller_option( '' ) );
		$this->assertSame(
			array( 'reseller_identifier' => 'Acme' ),
			boldgrid_backup_reseller_option( array( 'reseller_identifier' => 'Acme' ) )
		);
	}
}
