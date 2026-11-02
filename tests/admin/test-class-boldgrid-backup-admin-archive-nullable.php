<?php
/**
 * Archive is constructed with no core during early setup.
 *
 * @package Boldgrid_Backup
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Archive_Nullable
 */
class Test_Boldgrid_Backup_Admin_Archive_Nullable extends WP_UnitTestCase {

	/**
	 * The constructor type is explicitly nullable.
	 */
	public function test_constructor_core_parameter_is_explicitly_nullable() {
		$source = file_get_contents( BOLDGRID_BACKUP_PATH . '/admin/class-boldgrid-backup-admin-archive.php' );

		$this->assertIsString( $source );
		$this->assertMatchesRegularExpression(
			'/function __construct\(\s*\?Boldgrid_Backup_Admin_Core \$core = null\s*\)/',
			$source
		);

		$archive = new Boldgrid_Backup_Admin_Archive( null );
		$this->assertInstanceOf( Boldgrid_Backup_Admin_Archive::class, $archive );
	}
}
