<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * File: test-class-boldgrid-backup-admin-db-import.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.14.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    1.14.0
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Db_Import.
 *
 * @since xxx
 */
class Test_Boldgrid_Backup_Admin_Db_Import extends \WP_UnitTestCase {

	/**
	 * Setup.
	 *
	 * @since xxx
	 */
	public function setUp() {
		global $wpdb;

		$this->core = new \Boldgrid_Backup_Admin_Core();

		$this->test_lines = array(
			'1234',
			'abcd',
			'6789',
			'efgh',
		);

		$this->original_view_string = '/*!50001 DROP VIEW IF EXISTS `test_view`*/;\n/*!50001 CREATE ALGORITHM=UNDEFINED */\r/*!50013 DEFINER=`original_user`@`localhost` SQL SECURITY DEFINER */\n/*!50001 VIEW `test_view` AS select `wp_options`.`option_id` AS `option_id`,`wp_options`.`option_name` AS `option_name`,`wp_options`.`option_value` AS `option_value`,`wp_options`.`autoload` AS `autoload` from `wp_options` */;';
		$this->original_view_lines  = array(
			'/*!50001 DROP VIEW IF EXISTS `test_view`*/;',
			'/*!50001 CREATE ALGORITHM=UNDEFINED */',
			'/*!50013 DEFINER=`original_user`@`localhost` SQL SECURITY DEFINER */',
			'/*!50001 VIEW `test_view` AS select `wp_options`.`option_id` AS `option_id`,`wp_options`.`option_name` AS `option_name`,`wp_options`.`option_value` AS `option_value`,`wp_options`.`autoload` AS `autoload` from `wp_options` */;',
		);

		$this->expected_view_lines = array(
			'/*!50001 DROP VIEW IF EXISTS `test_view`*/;',
			'/*!50001 CREATE ALGORITHM=UNDEFINED */',
			'/*!50013 DEFINER=`' . DB_USER . '`@`localhost` SQL SECURITY DEFINER */',
			'/*!50001 VIEW `test_view` AS select `wp_options`.`option_id` AS `option_id`,`wp_options`.`option_name` AS `option_name`,`wp_options`.`option_value` AS `option_value`,`wp_options`.`autoload` AS `autoload` from `wp_options` */;',
		);
	}

	/**
	 * Test Import From Archive.
	 *
	 * @since 1.14.0
	 */
	public function test_import_from_archive() {
		$db_import = new \Boldgrid_Backup_Admin_Db_Import( $this->core );
		$db_import->import_from_archive( __FILE__, 'test' );
		$this->assertEquals( array( __( 'Unable to get contents of file.', 'boldgrid-backup' ) ), $db_import->errors );
	}

	/**
	 * Test Get Lines.
	 *
	 * @since 1.14.0
	 */
	public function test_get_lines() {
		$db_import = new \Boldgrid_Backup_Admin_Db_Import();
		$file      = __FILE__;
		$this->assertTrue( is_array( $db_import->get_lines( $file ) ) );
		$this->assertFalse( $db_import->get_lines( 'x' ) );
	}

	/**
	 * Test Import.
	 *
	 * @since 1.14.0
	 */
	public function test_import() {
		$file                   = __FILE__;
		$failed_get_lines_array = array( 'error' => sprintf( __( 'Unable to open mysqldump, %1$s.', 'boldgrid-backup' ), $file ) ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		$failed_fix_perm_array  = array( 'error' => sprintf( __( 'MySQL Database User does not have necessary priviliges to restore mysqldump, %1$s.', 'boldgrid-backup' ), $file ) ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		$mock_db_import         = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
			->setMethods( array( 'get_lines', 'fix_view_statements' ) )
			->getMock();
		$mock_db_import->method( 'get_lines' )
			->will( $this->onConsecutiveCalls( false, $this->original_view_lines ) );
		$mock_db_import->method( 'fix_view_statements' )
			->willReturn( false );

		$this->assertEquals( $failed_get_lines_array, $mock_db_import->import( __FILE__ ) );
		$this->assertEquals( $failed_fix_perm_array, $mock_db_import->import( __FILE__ ) );
	}
	/**
	 * Test Import From Lines.
	 *
	 * @since 1.14.0
	 */
	public function test_import_lines() {
		$db_import = new \Boldgrid_Backup_Admin_Db_Import();

		// If we try to import an empty array of lines, it should fail.
		$results = $db_import->import_lines( array() );
		$this->assertTrue( ! empty( $results['error'] ) );

		/*
		 * @todo Review and reimplement these tests.
		 *
		 * The import_lines() method was updated to were true on success, and an array with an error
		 * message on failure. Before, it would return false, but this was changed so it would return
		 * an error message to help with troubleshooting.
		 */
// 		$mock_db_import = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
// 			->setMethods( array( 'exec_import' ) )
// 			->getMock();
// 		$mock_db_import->method( 'exec_import' )
// 			->willReturn( false );
// 		$this->assertFalse( $db_import->import_lines( $this->original_view_lines ) );
	}

	/**
	 * Test Import String.
	 *
	 * @since 1.14.0
	 */
	public function test_import_string() {
		$mock_db_import = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
			->setMethods( array( 'exec_import' ) )
			->getMock();
		$mock_db_import->method( 'exec_import' )
		->willReturn( 4 );

		$this->assertEquals( 4, $mock_db_import->import_string( $this->original_view_string ) );
	}

	/**
	 * Test Fix View Statement.
	 *
	 * @since 1.14.0
	 */
	public function test_fix_view_statements() {
		$db_import = new \Boldgrid_Backup_Admin_Db_Import();

		$fixed_lines = $db_import->fix_view_statements( $this->original_view_lines );
		$this->assertEquals( $fixed_lines, $this->expected_view_lines );

		$unchanged_lines = $db_import->fix_view_statements( $this->test_lines );
		$this->assertEquals( $unchanged_lines, $this->test_lines );

		$mock_db_import = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
					->setMethods( array( 'has_db_privileges' ) )
					->getMock();
		$mock_db_import->method( 'has_db_privileges' )->willReturn( false );

		$fixed_lines = $mock_db_import->fix_view_statements( $this->original_view_lines );
		$this->assertEquals( array(), $fixed_lines );
	}

	/**
	 * Test Fix Definer.
	 *
	 * @since 1.14.0
	 */
	public function test_fix_definer() {
		$db_import = new \Boldgrid_Backup_Admin_Db_Import();

		$expected_line = '/*!50013 DEFINER=`' . DB_USER . '`@`%` SQL SECURITY DEFINER */';
		$original_line = '/*!50013 DEFINER=`original_user`@`%` SQL SECURITY DEFINER */';
		$this->assertEquals( $expected_line, $db_import->fix_definer( $original_line ) );

		$expected_line = '/*!50013 DEFINER=`' . DB_USER . '`@`' . DB_HOST . '` SQL SECURITY DEFINER */';
		$original_line = '/*!50013 DEFINER=`original_user`@`' . DB_HOST . '` SQL SECURITY DEFINER */';
		$this->assertEquals( $expected_line, $db_import->fix_definer( $original_line ) );

		$expected_line = '/*!50013 DEFINER=`' . DB_USER . '`@`' . DB_HOST . '` */';
		$original_line = '/*!50013 DEFINER=`original_user`@`' . DB_HOST . '` */';
		$this->assertEquals( $expected_line, $db_import->fix_definer( $original_line ) );
	}

	/**
	 * Test has DB Privileges.
	 *
	 * @since 1.14.0
	 */
	public function test_has_db_privileges() {
		$required_privileges = array( 'CREATE VIEW', 'SHOW VIEW' );

		$all_priv_array       = array( 'ALL' );
		$true_priv_array      = array( 'CREATE VIEW', 'SHOW VIEW', 'CREATE ROUTINE', 'EVENT', 'TRIGGER' );
		$no_create_priv_array = array( 'SHOW VIEW', 'CREATE ROUTINE', 'EVENT', 'TRIGGER' );
		$no_show_priv_array   = array( 'CREATE VIEW', 'CREATE ROUTINE', 'EVENT', 'TRIGGER' );
		$no_view_priv_array   = array( 'CREATE TEMPORARY TABLES', 'LOCK TABLES', 'EXECUTE' );
		$mock_db_import       = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
			->setMethods( array( 'get_db_privileges' ) )
			->getMock();

		$mock_db_import->method( 'get_db_privileges' )
			->will(
				$this->onConsecutiveCalls(
					$all_priv_array,
					$true_priv_array,
					$no_create_priv_array,
					$no_show_priv_array,
					$no_view_priv_array
				)
			);

		$this->assertTrue( $mock_db_import->has_db_privileges( $required_privileges ) );
		$this->assertTrue( $mock_db_import->has_db_privileges( $required_privileges ) );
		$this->assertFalse( $mock_db_import->has_db_privileges( $required_privileges ) );
		$this->assertFalse( $mock_db_import->has_db_privileges( $required_privileges ) );
		$this->assertFalse( $mock_db_import->has_db_privileges( $required_privileges ) );
	}

	/**
	 * Test Get Grants Array.
	 *
	 * @since 1.14.0
	 */
	public function test_get_grants_array() {
		$db_import      = new \Boldgrid_Backup_Admin_Db_Import();
		$grants_string  = 'GRANT SHOW, VIEW, CREATE, SHOW VIEW, CREATE VIEW ON `' . DB_NAME . '`.* TO \'' . DB_USER . '\'@\'' . DB_HOST . '\'localhost\'';
		$expected_array = array( 'SHOW', 'VIEW', 'CREATE', 'SHOW VIEW', 'CREATE VIEW' );
		$this->assertEquals( $expected_array, $db_import->get_grants_array( $grants_string ) );
	}

	/**
	 * Test Get DB Privileges.
	 *
	 * @since 1.14.0
	 */
	public function test_get_db_privileges() {
		$mock_db_import = $this->getMockBuilder( \Boldgrid_Backup_Admin_Db_Import::class )
			->setMethods( array( 'show_grants_query' ) )
			->getMock();

		$not_all_privileges = array(
			array( "GRANT USAGE ON *.* TO '" . DB_USER . "'@'" . DB_HOST . "' IDENTIFIED BY PASSWORD '*7276EE768CF087FAAB5448F508F79DA704CB5CE9' WITH GRANT OPTION" ),
			array( 'GRANT SHOW, VIEW, CREATE, SHOW VIEW, CREATE ON `' . DB_NAME . '`.* TO \'' . DB_USER . '\'@\'' . DB_HOST . '\'' ),
		);
		$no_privileges      = array(
			array( "GRANT USAGE ON *.* TO '" . DB_USER . "'@'" . DB_HOST . "' IDENTIFIED BY PASSWORD '*7276EE768CF087FAAB5448F508F79DA704CB5CE9' WITH GRANT OPTION" ),
		);

		$mock_db_import->method( 'show_grants_query' )
			->will(
				$this->onConsecutiveCalls(
					$not_all_privileges,
					$no_privileges
				)
			);

		$this->assertEquals( array( 'SHOW', 'VIEW', 'CREATE', 'SHOW VIEW', 'CREATE' ), $mock_db_import->get_db_privileges() );
		$this->assertEquals( array(), $mock_db_import->get_db_privileges() );

	}
}
