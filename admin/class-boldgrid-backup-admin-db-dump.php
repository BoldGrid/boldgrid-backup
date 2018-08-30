<?php
/**
 * File: class-boldgrid-backup-admin-db-dump.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class: Boldgrid_Backup_Admin_Db_Dump
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Db_Dump {
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
	 * Create a MySQL dump of the database.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file The filepath to our file.
	 * @return bool   True on success.
	 */
	public function dump( $file ) {
		$include_tables = $this->core->db_omit->get_filtered_tables();
		if ( empty( $include_tables ) ) {
			return array( 'error' => esc_html__( 'No tables selected to backup.', 'boldgrid-backup' ) );
		}

		Boldgrid_Backup_Admin_In_Progress_Data::set_args( array(
			'status' => 'Backing up database...',
			'tables' => $include_tables,
		));

		/**
		 * Take action before a database is dumped.
		 *
		 * @since 1.6.0
		 */
		do_action( 'boldgrid_backup_pre_dump' );

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
					'no-autocommit'  => false,
				)
			);
			$dump->start( $file );
		} catch ( \Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}

		/**
		 * Take action after a database is dumped.
		 *
		 * @since 1.6.0
		 */
		do_action( 'boldgrid_backup_post_dump' );

		return true;
	}

	/**
	 * Get data on all tables and the number of records in the backup file.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath Path to zip file.
	 * @param  string $file     Path to file in zip.
	 * @return array
	 */
	public function get_insert_count( $filepath, $file ) {
		$return = array();

		$tables = $this->get_insert_tables( $filepath, $file );

		$file_contents = $this->core->archive->get_file( $file );

		foreach ( $tables as $table ) {
			/*
			 * Grab the exact insert statement.
			 *
			 * We have to use preg_match_all because some tables may have
			 * multiple INSERT INTO commands.
			 */
			$expression = sprintf( '/INSERT INTO \`%1$s\` VALUES(.*);/', $table );
			preg_match_all( $expression, $file_contents[0]['content'], $matches );

			if ( empty( $matches[1] ) ) {
				$return[ $table ] = 0;
				continue;
			}

			$count = 0;

			foreach ( $matches[1] as $line ) {
				/*
				 * Ultimately what we're trying to do below is get the number of
				 * records in the INSERT statement.
				 *
				 * We cannot simply count the number of items between ()'s, as there
				 * could be a text entry like this ('()'),(), that would trigger a
				 * false positive.
				 *
				 * What we're doing is converting:
				 * 'some text goes \' () here ' to
				 * 'text'
				 * ... so we can simply count the number of ('s to find the number
				 * of records.
				 */
				$insert_command = str_replace( '\\\'', '', $line );
				$exploded       = explode( '\'', $insert_command );
				foreach ( $exploded as $k => $v ) {
					// Odd numbers are what was between quotes.
					if ( 0 !== $k % 2 ) {
						$exploded[ $k ] = '';
					}
				}
				$insert_command = implode( '\'', $exploded );

				$count += substr_count( $insert_command, '(' );
			}

			$return[ $table ] = $count;
		}

		return $return;
	}

	/**
	 * Get a list of all tables a .sql file has insert commands for.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath Path to zip file.
	 * @param  string $file     Path to file in zip.
	 * @return array
	 */
	public function get_insert_tables( $filepath, $file ) {
		$this->core->archive->init( $filepath );
		$file_contents = $this->core->archive->get_file( $file );

		/*
		 * Get a list of all tables within the dump that we are inserting
		 * records into.
		 *
		 * We initially did this taking a preg_match_all approach, but it fails
		 * for really long tables / fails with really long strings.
		 * # preg_match_all('/INSERT INTO \`(.*)\` VALUES(.*)/', $file_contents[0]['content'], $matches );
		 * # $tables = ! empty( $matches[1] ) ? $matches[1] : array();
		 *
		 * @link https://stackoverflow.com/questions/3021316/preg-match-and-long-strings
		 *
		 * For now, we'll go with the handy explode technique.
		*/
		$tables   = array();
		$exploded = explode( 'INSERT INTO `', $file_contents[0]['content'] );
		unset( $exploded[0] );
		foreach ( $exploded as $table ) {
			$tables[] = strstr( $table, '`', true );
		}

		// Tables may have more than one INSERT INTO statement.
		$tables = array_unique( $tables );

		return $tables;
	}
}
