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
			'SHOW FULL TABLES;',
			ARRAY_N
		);

		/*
		 * Based upon our full list of tables, create our list of prefixed tables.
		 *
		 * Initially, in the get_results() call above, we used "SHOW TABLES LIKE 'prefix%'" to get a
		 * list of all prefixed tables. This returned both tables and views. The views triggered errors
		 * because they did not actually exist.
		 *
		 * @link https://stackoverflow.com/questions/2908680/how-to-get-only-tables-not-views-using-show-tables
		 * @link https://wordpress.org/support/topic/backup-fails-because-of-some-database-error/
		 */
		foreach ( $results as $v ) {
			// If this isn't a table (IE it's a view), skip it.
			if ( 'BASE TABLE' !== $v[1] ) {
				continue;
			}

			// If this table doesn't begin with our prefix, skip it.
			if ( substr( $v[0], 0, strlen( $wpdb->prefix ) ) !== $wpdb->prefix ) {
				continue;
			}

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
