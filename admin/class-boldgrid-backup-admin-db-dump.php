<?php
/**
 * Database Dump.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

use Ifsnop\Mysqldump as IMysqldump;

/**
 * BoldGrid Backup Admin Database Dump class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Db_Dump {

	/**
	 * Create a MySQL dump of the database.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file The filepath to our file.
	 * @return bool   True on success.
	 */
	public function dump( $file ) {
		$include_tables = $this->get_tables();
		if ( empty( $include_tables ) ) {
			return array( 'error' => esc_html__( 'No tables selected to backup.', 'boldgrid-backup' ) );
		}

		// Some hosts may configure the DB_HOST as localhost:3306. Strip out the port.
		$db_host = explode( ':', DB_HOST );

		try {
			$dump = new IMysqldump\Mysqldump(
				sprintf( 'mysql:host=%1$s;dbname=%2$s', $db_host[0], DB_NAME ),
				DB_USER,
				DB_PASSWORD,
				array(
					'include-tables' => $include_tables,
					'add-drop-table' => true,
					'no-autocommit' => false,
				)
			);
			$dump->start( $file );
		} catch (\Exception $e) {
			return array( 'error' => $e->getMessage() );
		}

		return true;
	}

	/**
	 * Get an array of tables that we should backup.
	 *
	 * @since 1.5.1
	 *
	 * @global $wpdb.
	 *
	 * @return array
	 */
	public function get_tables() {
		global $wpdb;

		$include_tables = array();

		$query = $wpdb->prepare(
			'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`=%s AND `TABLE_NAME` LIKE %s ORDER BY `TABLE_NAME`;',
			DB_NAME, $wpdb->get_blog_prefix( is_multisite() ) . '%'
		);

		// Check query.
		if ( empty( $query ) ) {
			do_action( 'boldgrid_backup_notice', esc_html__( 'Could not determine mysql tables names.', 'boldgrid-backup' ), 'notice notice-error is-dismissible' );
			return false;
		}

		$tables = $wpdb->get_results( $query, ARRAY_N );

		if ( empty( $tables ) ) {
			do_action( 'boldgrid_backup_notice', esc_html__( 'No results when getting mysql table names.', 'boldgrid-backup' ), 'notice notice-error is-dismissible' );
			return false;
		}

		foreach ( $tables as $table ) {
			$include_tables[] = $table[0];
		}

		/**
		 * Filter the tables that we will backup.
		 *
		 * @since 1.5.1
		 *
		 * @param array $include_tables
		 */
		$include_tables = apply_filters( 'boldgrid_backup_backup_tables', $include_tables );

		return $include_tables;
	}
}

