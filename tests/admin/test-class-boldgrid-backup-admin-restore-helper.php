<?php
/**
 * File: test-class-boldgrid-backup-admin-restore-helper.php
 *
 * @link  https://www.boldgrid.com
 * @since xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.VIP
 */

define( 'BOLDGRID_BACKUP_VERSION', '1.1.1' );
/**
 * Class: Test_Boldgrid_Backup_Admin_Restore_Helper
 *
 * @since xxx
 */
class Test_Boldgrid_Backup_Admin_Restore_Helper extends WP_UnitTestCase {
	/**
	 * An instance core.
	 *
	 * @since xxx
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * Setup.
	 *
	 * @since SINCEVERSION
	 */
	public function setUp() {
		global $wpdb;

		$this->core = new \Boldgrid_Backup_Admin_Core();
	}

	/**
	 * Change File Permissions
	 *
	 * @since SINCEVERSION
	 *
	 * @param array  $archive Array of Archive Info.
	 * @param string $archive_type Either PclZip or ZipArchive.
	 */
	public function change_file_permissions( $archive, $archive_type ) {
		$mock_object = $this->getMockBuilder( \Boldgrid_Backup_Admin_Restore_Helper::class )
			->setMethods( [ 'is_compressor_type', 'change_file_permissions' ] )
			->getMock();
		$mock_object->expects( $this->any() )
			->method( 'is_compressor_type' )
			->willReturn( $archive_type );

		// Test with a failed change_file_permissions.
		$result = $mock_object->set_writable_permissions( $archive['filepath'] );
		$this->assertFalse( $result );

		// Test with a successfull change_file_permissions.
		$mock_object->expects( $this->any() )
			->method( 'change_file_permissions' )
			->willReturn( true );
		$result = $mock_object->set_writable_permissions( $archive['filepath'] );
		$this->assertTrue( $result );
	}

	/**
	 * Test set_writable_permissions
	 *
	 * @since SINCEVERSION
	 */
	public function test_set_writable_permissions() {
		$archive = $this->core->archive_files( true );

		// Test with PclZip.
		$this->change_file_permissions( $archive, 'PclZip' );

		// Test with ZipArchive.
		$this->change_file_permissions( $archive, 'ZipArchive' );
	}

	/**
	 * Test change_file_permissions
	 *
	 * @since SINCEVERSION
	 */
	public function test_change_file_permissions() {
		global $wp_filesystem;

		$file_name      = 'test.txt';
		$path           = ABSPATH . $file_name;
		$restore_helper = new \Boldgrid_Backup_Admin_Restore_Helper();

		// Tests success if file does not exist.
		$result = $restore_helper->change_file_permissions( $file_name );
		$this->assertTrue( $result );

		// Tests success if file does exist.
		touch( $path );
		$result = $restore_helper->change_file_permissions( $file_name );
		$this->assertTrue( $result );

	}
}
