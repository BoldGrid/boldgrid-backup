<?php
/**
 * File: test-class-boldgrid-backup-admin-zip.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.17.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Zip
 *
 * @since 1.17.3
 */
class Test_Boldgrid_Backup_Admin_Zip extends WP_UnitTestCase {
	/**
	 * Temporary zip path.
	 *
	 * @var string
	 */
	private $zip_path;

	/**
	 * Setup.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive is required for ZIP64 repair tests.' );
		}

		$this->zip_path = wp_tempnam( 'bgbak-zip64-' );
		$this->assertNotEmpty( $this->zip_path );
		unlink( $this->zip_path );
		$this->zip_path .= '.zip';
	}

	/**
	 * Teardown.
	 */
	public function tear_down() {
		if ( ! empty( $this->zip_path ) && file_exists( $this->zip_path ) ) {
			unlink( $this->zip_path );
		}

		parent::tear_down();
	}

	/**
	 * Broken EOCD (entry count 0, no ZIP64) should list via CD walk and repair.
	 */
	public function test_repair_zero_eocd_entry_count() {
		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $this->zip_path, ZipArchive::CREATE ) );

		for ( $i = 0; $i < 50; $i++ ) {
			$zip->addFromString( 'file-' . $i . '.txt', 'x' );
		}
		$zip->addFromString( 'site.20260101-120000.sql', 'SELECT 1;' );
		$this->assertTrue( $zip->close() );

		$this->corrupt_eocd_entry_counts( $this->zip_path );

		$broken = new ZipArchive();
		$this->assertSame( ZipArchive::ER_INCONS, $broken->open( $this->zip_path ) );

		$list = Boldgrid_Backup_Admin_Zip::list_central_directory( $this->zip_path );
		$this->assertCount( 51, $list );

		$this->assertTrue( Boldgrid_Backup_Admin_Zip::maybe_repair_zip64_eocd( $this->zip_path ) );

		$fixed = Boldgrid_Backup_Admin_Zip::open_zip_archive( $this->zip_path );
		$this->assertInstanceOf( ZipArchive::class, $fixed );
		$this->assertSame( 51, $fixed->numFiles ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$fixed->close();
	}

	/**
	 * get_sqls / browse should recover after ZIP64 EOCD repair.
	 */
	public function test_pcl_zip_list_after_repair() {
		$core = new Boldgrid_Backup_Admin_Core();
		$this->assertNotEmpty( $core );

		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $this->zip_path, ZipArchive::CREATE ) );
		$zip->addFromString( 'index.php', '<?php' );
		$zip->addFromString( 'wp-content/index.php', '<?php' );
		$zip->addFromString( 'demo.20260724-120000.sql', 'SELECT 1;' );
		$this->assertTrue( $zip->close() );

		$this->corrupt_eocd_entry_counts( $this->zip_path );

		$pcl  = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $core );
		$sqls = $pcl->get_sqls( $this->zip_path );
		$this->assertSame( array( 'demo.20260724-120000.sql' ), $sqls );

		$root  = $pcl->browse( $this->zip_path, '.' );
		$names = wp_list_pluck( $root, 'filename' );
		$this->assertContains( 'index.php', $names );
		$this->assertContains( 'demo.20260724-120000.sql', $names );
		$this->assertTrue(
			in_array( 'wp-content', $names, true ) || in_array( 'wp-content/', $names, true )
		);
	}

	/**
	 * Zero out classic EOCD entry-count fields to mimic the production failure.
	 *
	 * @param string $filepath Zip path.
	 */
	private function corrupt_eocd_entry_counts( $filepath ) {
		$size = filesize( $filepath );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $filepath, 'r+b' );
		$this->assertNotFalse( $handle );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		fseek( $handle, $size - 22 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$eocd = fread( $handle, 22 );
		$this->assertSame( "PK\x05\x06", substr( $eocd, 0, 4 ) );
		$eocd[8]  = "\x00";
		$eocd[9]  = "\x00";
		$eocd[10] = "\x00";
		$eocd[11] = "\x00";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		fseek( $handle, $size - 22 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $handle, $eocd );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	}
}
