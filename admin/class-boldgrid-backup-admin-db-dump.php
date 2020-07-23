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
	 * @global wpdb $wpdb
	 *
	 * @param  string $file The filepath to our file.
	 * @return bool   True on success.
	 */
	public function dump( $file ) {
		global $wpdb;

		$include_tables = $this->core->db_omit->get_filtered_tables();
		if ( empty( $include_tables ) ) {
			return array( 'error' => esc_html__( 'No tables selected to backup.', 'boldgrid-backup' ) );
		}

		/*
		 * Create separate arrays for the "tables" and "views" that we want to dump.
		 *
		 * When dumping our database, we need to send a separate list of tables to dump, and a separate
		 * one for views to dump. $include_tables is an array possibly containing both tables and views,
		 * so we'll split it up now.
		 *
		 * In the list below, it is important that $include_tables is processed last.
		 */
		$include_views = $this->core->db_get->filter_by_type( $include_tables, 'VIEW' );

		$include_tables = $this->core->db_get->filter_by_type( $include_tables, 'BASE TABLE' );

		Boldgrid_Backup_Admin_In_Progress_Data::set_args(
			array(
				'status' => __( 'Backing up database...', 'boldgrid-backup' ),
				'tables' => $include_tables,
				'step'   => 1,
			)
		);

		/**
		 * Take action before a database is dumped.
		 *
		 * @since 1.6.0
		 */
		do_action( 'boldgrid_backup_pre_dump', $file );

		$settings = array(
			'include-tables' => $include_tables,
			'include-views'  => $include_views,
			'add-drop-table' => true,
			'no-autocommit'  => false,
		);

		/*
		 * Set default character set.
		 *
		 * By default, IMysqldump\Mysqldump uses utf8.
		 *
		 * By default, WordPress sets CHARSET to utf8 in wp-config but will default to utf8mb4
		 * if it's available.
		 *
		 * @see wpdb::determine_charset
		 */
		if ( ! empty( $wpdb->charset ) ) {
			$settings['default-character-set'] = $wpdb->charset;
		}

		if ( ! empty( $include_views ) ) {
			$db_import           = new Boldgrid_Backup_Admin_Db_Import();
			$user_has_privileges = $db_import->has_db_privileges( array( 'SHOW VIEW' ) );
			if ( false === $user_has_privileges ) {
				return array(
					'error' => esc_html__(
						'The database contains VIEWS, but the database user does not have the permissions needed to create a backup.',
						'boldgrid-backup'
					),
				);
			}
		}

		try {
			$dump = new IMysqldump\Mysqldump(
				$this->get_connection_string(),
				DB_USER,
				DB_PASSWORD,
				$settings
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
		do_action( 'boldgrid_backup_post_dump', $file );

		return true;
	}

	/**
	 * Get our PDO DSN connection string.
	 *
	 * @since 1.13.3
	 *
	 * @param  string $db_host DB hostname.
	 * @param  string $db_name DB name.
	 * @return string
	 */
	public function get_connection_string( $db_host = null, $db_name = null ) {
		$params = array();

		// Configure parameters passed in.
		$db_name = empty( $db_name ) ? DB_NAME : $db_name;
		$db_host = empty( $db_host ) ? DB_HOST : $db_host;
		$db_host = explode( ':', $db_host );

		// Parse info and get hostname, port, and socket. Not all required. See comments below.
		switch ( count( $db_host ) ) {
			/*
			 * Examples:
			 *
			 * # localhost
			 * # /var/lib/mysql/mysql.sock
			 */
			case 1:
				$has_socket = 'sock' === pathinfo( $db_host[0], PATHINFO_EXTENSION );

				if ( $has_socket ) {
					$params['unix_socket'] = $db_host[0];
				} else {
					$params['host'] = $db_host[0];
				}

				break;
			/*
			 * Examples:
			 *
			 * # localhost:/var/lib/mysql/mysql.sock
			 * # localhost:3306
			 */
			case 2:
				$has_socket = 'sock' === pathinfo( $db_host[1], PATHINFO_EXTENSION );
				$has_port   = is_numeric( $db_host[1] );

				$params['host'] = $db_host[0];

				if ( $has_socket ) {
					$params['unix_socket'] = $db_host[1];
				} elseif ( $has_port ) {
					$params['port'] = $db_host[1];
				}

				break;
		}

		$connection_string = 'mysql:';
		foreach ( $params as $key => $value ) {
			$connection_string .= $key . '=' . $value . ';';
		}
		$connection_string .= 'dbname=' . $db_name;

		return $connection_string;
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
		$return = [];
		$tables = [];

		$this->core->archive->init( $filepath );

		$file_contents = $this->core->archive->get_dump_file( $file );

		// Check for the dump file header.
		if ( false !== strpos( $file_contents[0]['content'], '-- mysqldump-php' ) ) {
			$tables = $this->get_insert_tables( $filepath, $file );
		} else {
			// The file header is missing; the file may have been encrypted with other settings.
			$return = [ 'encrypted_other' => true ];
		}

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
		$file_contents = $this->core->archive->get_dump_file( $file );

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
