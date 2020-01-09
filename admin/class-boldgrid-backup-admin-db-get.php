<?php
/**
 * File: class-boldgrid-backup-admin-db-get.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

/**
 * Class: Boldgrid_Backup_Admin_Db_Get
 *
 * @since 1.5.3
 */
class Boldgrid_Backup_Admin_Db_Get {
	/**
	 * The core class object.
	 *
	 * @since  1.5.3
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.3
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get an array of all tables by table type.
	 *
	 * Types must be either "BASE TABLE" or "VIEW".
	 *
	 * This method does not filter the list of tables based on prefix.
	 *
	 * @since 1.12.4
	 *
	 * @param  string $type The table type to get.
	 * @return array
	 */
	public function get_by_type( $type ) {
		global $wpdb;

		$tables = [];

		// Validate our table type.
		$types = [ 'BASE TABLE', 'VIEW' ];
		if ( ! in_array( $type, $types, true ) ) {
			return [];
		}

		/*
		 * Get our list of tables by type.
		 *
		 * $results will be an array of arrays, with the latter arrays containing [0] table name and
		 * [1] table type.
		 */
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW FULL TABLES WHERE TABLE_TYPE = %s;',
				$type
			),
			ARRAY_N
		);

		// Convert results to an array of table names (rather than an array of arrays).
		foreach ( $results as $result ) {
			$tables[] = $result[0];
		}

		return $tables;
	}

	/**
	 * Filter an array of table names by table type.
	 *
	 * For example, pass in an array of tables that include both "tables" and "views", and this method
	 * allows you get back only those that match $type.
	 *
	 * @since 1.12.4
	 *
	 * @param  array  $tables An array of table names.
	 * @param  string $type A table type, such as "BASE TABLE" or "VIEW".
	 * @return array
	 */
	public function filter_by_type( $tables, $type ) {
		// The filtered list of tables that we will return.
		$tables_by_type = [];

		$all_of_type = $this->get_by_type( $type );

		foreach ( $tables as $table ) {
			if ( in_array( $table, $all_of_type, true ) ) {
				$tables_by_type[] = $table;
			}
		}

		return $tables_by_type;
	}

	/**
	 * Get a list of all tables based on system prefix.
	 *
	 * @since 1.5.3
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return array
	 */
	public function prefixed() {
		global $wpdb;

		$prefix_tables = array();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s;',
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			),
			ARRAY_N
		);

		foreach ( $results as $v ) {
			$prefix_tables[] = $v[0];
		}

		return $prefix_tables;
	}

	/**
	 * Get a list of all prefixed tables and the number of rows in each.
	 *
	 * This is similar to self::prefixed, except this method returns the number
	 * of rows in each table.
	 *
	 * @since 1.5.3
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return array
	 */
	public function prefixed_count() {
		global $wpdb;

		$return = array();

		$tables = $this->prefixed();

		foreach ( $tables as $table ) {
			$num = $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $table . '`;' ); // phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared

			$return[ $table ] = $num;
		}

		return $return;
	}
}
