<?php
/**
 * File: test-class-boldgrid-backup-admin-db-get.php
 *
 * @link https://www.boldgrid.com
 * @since 1.12.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Db_Get
 *
 * @since 1.12.4
 */
class Test_Boldgrid_Backup_Admin_Db_Get extends WP_UnitTestCase {
	/**
	 * Test filter_by_type.
	 *
	 * @since 1.12.4
	 */
	public function test_filter_by_type() {
		global $wpdb;

		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$view_name = $wpdb->prefix . 'view1';

		/*
		 * Setup.
		 */

		// Create a view.
		$sql = 'CREATE OR REPLACE VIEW ' . $view_name . ' AS SELECT * FROM ' . $wpdb->prefix . 'options WHERE `option_id` = 10';
		$wpdb->query( $sql ); // phpcs:ignore

		// Get our prefixed tables. This will include both tables and views.
		$prefixed_tables = $core->db_get->prefixed();

		/*
		 * Testing.
		 */

		// Get our views and tables.
		$views  = $core->db_get->filter_by_type( $prefixed_tables, 'VIEW' );
		$tables = $core->db_get->filter_by_type( $prefixed_tables, 'BASE TABLE' );

		// Assert that we only have one view, the one we created.
		$this->assertEquals( $views, [ $view_name ] );

		// Assert that our view is not in our list of tables.
		$this->assertTrue( ! in_array( $view_name, $tables, true ) );

		// Assert that thte count of tables is 1 less (because there is 1 view) than all $prefixed_tables.
		$this->assertTrue( count( $tables ) === ( count( $prefixed_tables ) - 1 ) );

		/*
		 * Cleanup.
		 */

		// Delete the view we created and ensure it was deleted.
		$sql = 'DROP VIEW ' . $view_name;
		$wpdb->query( $sql ); // phpcs:ignore
		$views = $core->db_get->get_by_type( 'VIEW' );
		$this->assertTrue( 0 === count( $views ) );
	}

	/**
	 * Test get_by_type.
	 *
	 * @since 1.12.4
	 *
	 * @link https://wordpress.stackexchange.com/questions/220275/wordpress-unit-testing-cannot-create-tables
	 */
	public function test_get_by_type() {
		global $wpdb;

		$view_name = $wpdb->prefix . 'view1';

		$core = apply_filters( 'boldgrid_backup_get_core', null );

		/*
		 * Begin testing tables.
		 */

		// By default, there are 11 tables. Make sure we get at least 5.
		$tables                = $core->db_get->get_by_type( 'BASE TABLE' );
		$original_tables_count = count( $tables );
		$this->assertTrue( $original_tables_count > 5 );

		/*
		 * Begin testing views.
		 */

		// By default, there are 0 views. Make sure we get 0.
		$views = $core->db_get->get_by_type( 'VIEW' );
		$this->assertTrue( 0 === count( $views ) );

		// Create a view. We should now have 1 view.
		$sql = 'CREATE VIEW ' . $view_name . ' AS SELECT * FROM ' . $wpdb->prefix . 'options WHERE `option_id` = 10';
		$wpdb->query( $sql ); //  phpcs:ignore
		$views = $core->db_get->get_by_type( 'VIEW' );
		$this->assertTrue( 1 === count( $views ) );

		/*
		 * Now that we have a view, we need to confirm that when we get tables only, this new view
		 * does not show in that list.
		 */
		$tables = $core->db_get->get_by_type( 'BASE TABLE' );
		$this->assertTrue( count( $tables ) === $original_tables_count );

		// Delete the view. We should now have 0 views.
		$sql = 'DROP VIEW ' . $view_name;
		$wpdb->query( $sql ); // phpcs:ignore
		$views = $core->db_get->get_by_type( 'VIEW' );
		$this->assertTrue( 0 === count( $views ) );
	}
}
