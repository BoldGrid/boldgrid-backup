<?php
/**
 * File: test-class-boldgrid-backup-admin-core.php
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

/**
 * Class: Test_Boldgrid_Backup_Admin_Core
 *
 * @since xxx
 */
class Test_Boldgrid_Backup_Admin_Core extends WP_UnitTestCase {
	/**
	 * Assert that a given dir in an archive has files and folders.
	 *
	 * This is a very generic test, doesn't need to be exact, as the WordPress core files will change
	 * over time.
	 *
	 * For example, we may run this and say:
	 * Make sure wp-admin folder has over 10 files totalling over 10000 bytes, and there's at least
	 * 3 folders.
	 *
	 * @since xxx
	 *
	 * @param string $filepath Path to the zip file.
	 * @param string $dir      The dir within the zip to check.
	 * @param int    $min_file_count The minimum number of files that need to be in the directory.
	 * @param int    $min_file_size The minimum file size of all files in the directory.
	 * @param int    $min_dir_count  The minimum number of folders that need to be in the directory.
	 */
	public function assertDirNotEmpty( $filepath, $dir = '.', $min_file_count, $min_file_size, $min_dir_count ) {
		$abspath    = $this->zip->browse( $filepath, $dir );
		$file_count = 0;
		$file_size  = 0;
		$dir_count  = 0;

		foreach ( $abspath as $file ) {
			if ( $file['folder'] ) {
				$dir_count++;
			} else {
				$file_count++;
				$file_size += $file['size'];
			}
		}

		/*
		 * Debug. This is how you can see the actual counts / sizes in question.
		phpunit_error_log( array(
			'$dir'        => $dir,
			'$file_count' => $file_count,
			'$file_size'  => $file_size,
			'$dir_count'  => $dir_count,
		) );
		*/

		$this->assertTrue( $file_count >= $min_file_count && $file_size >= $min_file_size && $dir_count >= $min_dir_count );
	}

	/**
	 * An instance core.
	 *
	 * @since xxx
	 * @var Boldgrid_Backup_Admin_Core
	 */
	public $core;

	/**
	 * An array of info about a backup.
	 *
	 * This is the $info that is returned when a backup file is made.
	 *
	 * @since xxx
	 * @var array
	 */
	public $info;

	/**
	 * The name of a view that we will be testing.
	 *
	 * @since 1.12.4
	 * @var string
	 */
	public $view_name;

	/**
	 * An instance of pcl_zip.
	 *
	 * @since xxx
	 * @var Boldgrid_Backup_Admin_Compressor_Pcl_Zip
	 */
	public $zip;

	/**
	 * Drop a table.
	 *
	 * @since xxx
	 *
	 * @param string $table Name of the table to delete.
	 */
	public function dropTable( $table ) {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table ); // phpcs:ignore
	}

	/**
	 * Create a wp-config file.
	 *
	 * This file doesn't actually exist within the testing environment, and needs to exist for the
	 * restore via cli script.
	 *
	 * @since xxx
	 */
	public function createWpconfig() {
		$wpconfig = '
			define( \'DB_NAME\', \'' . DB_NAME . '\' );
			define( \'DB_USER\', \'' . DB_USER . '\' );
			define( \'DB_PASSWORD\', \'' . DB_PASSWORD . '\' );
			define( \'DB_HOST\', \'' . DB_HOST . '\' );
			define( \'DB_CHARSET\', \'' . DB_CHARSET . '\' );
			define( \'DB_COLLATE\', \'\' );
		';

		file_put_contents( trailingslashit( ABSPATH ) . 'wp-config.php', $wpconfig ); // phpcs:ignore
	}

	/**
	 * Delete a few files, drop a few tables, and make sure the delete / drop worked as expected.
	 *
	 * This method is intended to be ran before / after a restore method is executed. It is used to
	 * delete files / drop tables, and after a restore, it makes sure those files and tables are
	 * back.
	 *
	 * @since xxx.
	 *
	 * @param string $action Action.
	 */
	public function deleteBasic( $action = 'delete' ) {
		global $wpdb;

		$files_to_delete = [
			trailingslashit( ABSPATH ) . 'wp-load.php',
			trailingslashit( ABSPATH ) . 'wp-includes/theme.php',
			trailingslashit( ABSPATH ) . 'wp-includes/rest-api/class-wp-rest-request.php',
		];

		$tables_to_drop = [
			'wptests_commentmeta',
			'wptests_comments',
		];

		switch ( $action ) {
			case 'delete':
				foreach ( $files_to_delete as $file ) {
					$this->assertTrue( file_exists( $file ) );
					unlink( $file );
					$this->assertFalse( file_exists( $file ) );
				}

				foreach ( $tables_to_drop as $table ) {
					$tables = $this->getTables();
					$this->assertTrue( in_array( $table, $tables, true ) );

					$this->dropTable( $table );

					$tables = $this->getTables();
					$this->assertFalse( in_array( $table, $tables, true ) );
				}

				// Delete the view we created. We should now have 0 views.
				$sql = 'DROP VIEW ' . $this->view_name;
				$wpdb->query( $sql ); // phpcs:ignore
				$views = $this->core->db_get->get_by_type( 'VIEW' );
				$this->assertTrue( 0 === count( $views ) );

				break;
			case 'restore':
				foreach ( $files_to_delete as $file ) {
					$this->assertTrue( file_exists( $file ) );
				}

				$tables = $this->getTables();
				foreach ( $tables_to_drop as $table ) {
					$this->assertTrue( in_array( $table, $tables, true ) );
				}

				// Ensure our view was restored.
				$views = $this->core->db_get->get_by_type( 'VIEW' );
				$this->assertTrue( 1 === count( $views ) );

				break;
		}
	}

	/**
	 * Get an array of our db tables.
	 *
	 * @since xxx
	 *
	 * @return array
	 */
	public function getTables() {
		global $wpdb;

		$tables = [];

		$sql_tables = $wpdb->get_results( 'SHOW TABLES' );

		foreach ( $sql_tables as $table ) {
			// Get the name of the table.
			$vars       = get_object_vars( $table );
			$table_name = reset( $vars );

			$tables[] = $table_name;
		}

		return $tables;
	}

	/**
	 * Setup.
	 *
	 * @since xxx
	 */
	public function setUp() {
		global $wpdb;

		if ( ! defined( 'BOLDGRID_BACKUP_VERSION' ) ) {
			$file = dirname( dirname( dirname( __FILE__ ) ) ) . '/boldgrid-backup.php';
			define( 'BOLDGRID_BACKUP_VERSION', implode( get_file_data( $file, array( 'Version' ), 'plugin' ) ) );
		}

		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$this->view_name = $wpdb->prefix . 'view1';
	}

	/**
	 * Test archive_files.
	 *
	 * @since xxx
	 */
	public function test_archive_files() {
		global $wpdb;

		// Delete our latest_backup variable.
		delete_option( 'boldgrid_backup_latest_backup' );
		$this->assertFalse( get_option( 'boldgrid_backup_latest_backup' ) );

		/*
		 * Basic test.
		 *
		 * This is a generic backup test (IE backup all files and folders and tables).
		 */

		/*
		 * Before backing up, create a view to ensure it's backed up / restored properly.
		 *
		 * @link https://wordpress.org/support/topic/backup-fails-because-of-some-database-error/
		 */
		$sql = 'CREATE OR REPLACE VIEW ' . $this->view_name . ' AS SELECT * FROM ' . $wpdb->prefix . 'options WHERE `option_id` = 10';
		$wpdb->query( $sql ); //  phpcs:ignore
		$views = $this->core->db_get->get_by_type( 'VIEW' );
		$this->assertTrue( 1 === count( $views ) );

		$this->info = $this->core->archive_files( true );

		// Ensure a backup was made and we have a filepath.
		$this->assertTrue( ! empty( $this->info['filepath'] ) );

		// Ensure the $this->info returned matches the data stored in the boldgrid_backup_latest_backup option.
		$this->assertTrue( get_option( 'boldgrid_backup_latest_backup' ) === $this->info );

		// Ensure we have files and folders (IE they're not empty and the backup process recursed).
		$this->assertDirNotEmpty( $this->info['filepath'], '.', 10, 10000, 3 );
		$this->assertDirNotEmpty( $this->info['filepath'], 'wp-includes', 10, 10000, 3 );
		$this->assertDirNotEmpty( $this->info['filepath'], 'wp-includes/rest-api', 3, 10000, 3 );
		$this->assertDirNotEmpty( $this->info['filepath'], 'wp-admin', 10, 10000, 3 );

		// Ensure there is exactly 1 .sql in the backup.
		$sqls = $this->zip->get_sqls( $this->info['filepath'] );
		$this->assertTrue( 1 === count( $sqls ) );
	}

	/**
	 * Test restore_archive_file.
	 *
	 * @since xxx
	 */
	public function test_restore_archive_file() {
		// @todo this should not be needed.
		$this->core = new Boldgrid_Backup_Admin_Core();

		/*
		 * Test: Basic test.
		 *
		 * Create a basic backup. Delete some stuff. Restore the backup. Make sure everything worked.
		 */

		// Create a backup if don't already have one.
		if ( empty( $this->info ) ) {
			$this->info = $this->core->archive_files( true );
		}

		$this->deleteBasic( 'delete' );

		// Pad necessary $_POST vars.
		$_POST['restore_now']      = 1;
		$_POST['archive_key']      = 0;
		$_POST['archive_filename'] = basename( $this->info['filepath'] );

		$this->core->restore_archive_file();

		$this->deleteBasic( 'restore' );
	}

	/**
	 * Test restore_cli.
	 *
	 * There is not restore_cli method. This method tests the cron command generated to restore a
	 * backup file.
	 *
	 * @since xxx
	 */
	public function test_restore_cli() {
		$this->createWpconfig();

		if ( empty( $this->info ) ) {
			$this->info = $this->core->archive_files( true );
		}

		$this->deleteBasic( 'delete' );

		// Get our restore command.
		$cron = $this->core->cron->get_restore_command();
		$cron = strstr( $cron, 'php' );
		$cron = strstr( $cron, ' > /dev/null', true );

		// Restore.
		exec( $cron, $output ); // phpcs:ignore

		$this->deleteBasic( 'restore' );
	}
}
