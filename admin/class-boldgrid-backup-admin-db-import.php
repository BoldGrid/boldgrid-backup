<?php
/**
 * File: class-boldgrid-backup-admin-db-import.php
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

/**
 * Class: Boldgrid_Backup_Admin_Db_Import.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Db_Import {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @var    Boldgrid_Backup_Admin_Core
	 * @access private
	 */
	private $core;

	/**
	 * Errors.
	 *
	 * @since 1.6.0
	 * @var   array
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core object.
	 */
	public function __construct( $core = false ) {
		// We don't always require $core for this class.
		if ( $core ) {
			$this->core = $core;
		}
	}

	/**
	 * Get Lines from file.
	 *
	 * Gets an array of lines from a file.
	 *
	 * @since 1.14.0
	 *
	 * @param string $file String ocntaining the path of the file.
	 *
	 * @return array An array of lines.
	 */
	public function get_lines( $file ) {
		if ( false === file_exists( $file ) ) {
			return false;
		}
		return file( $file );
	}
	/**
	 * Import a mysqldump.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file File path.
	 * @return bool TRUE on success.
	 */
	public function import( $file ) {
		$lines = $this->get_lines( $file );

		if ( false === $lines ) {
			return array(
				'error' => sprintf(
					// translators: 1: File path.
					__( 'Unable to open mysqldump, %1$s.', 'boldgrid-backup' ),
					$file
				),
			);
		}

		$lines = $this->fix_view_statements( $lines );

		if ( true === empty( $lines ) ) {
			return array(
				'error' => sprintf(
					/* translators: 1: Database File Name */
					__( 'MySQL Database User does not have necessary priviliges to restore mysqldump, %1$s.', 'boldgrid-backup' ),
					$file
				),
			);
		}

		$success = $this->import_lines( $lines );

		return $success;
	}

	/**
	 * Import a database dump from an archive.
	 *
	 * Pass in "file.zip" and "backup.sql" and we'll find "backup.sql" in the file.zip file and restore it.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $archive_filepath Archive file path.
	 * @param  string $file             Filename.
	 * @return bool
	 */
	public function import_from_archive( $archive_filepath, $file ) {
		$this->core->archive->init( $archive_filepath );
		$file_contents = $this->core->archive->get_dump_file( $file );
		$sql           = ! empty( $file_contents[0]['content'] ) ? $file_contents[0]['content'] : null;
		if ( empty( $sql ) ) {
			$this->errors[] = __( 'Unable to get contents of file.', 'boldgrid-backup' );
			return false;
		}

		$success = $this->import_string( $sql );

		return $success;
	}

	/**
	 * Import lines (queries).
	 *
	 * This method accepts an array of $lines, and loops through each $line and imports it.
	 *
	 * These lines usually come from either a .sql file, or a string is parsed
	 * into separate lines.
	 *
	 * The functionality in this method use to be in the main import method,
	 * however it was broken away to make more reusable.
	 *
	 * @since 1.6.0
	 *
	 * @param  array $lines MySQL dump file lines.
	 * @return bool
	 */
	public function import_lines( $lines ) {
		if ( empty( $lines ) ) {
			return false;
		}

		/* phpcs:disable WordPress.DB.RestrictedClasses */
		$db = new PDO( $this->get_connection_string(), DB_USER, DB_PASSWORD );

		$templine = '';

		foreach ( $lines as $line ) {
			// Skip comments and empty lines.
			if ( substr( $line, 0, 2 ) === '--' || empty( $line ) ) {
				continue;
			}

			$templine .= $line;

			// Check if this is the end of the query.
			if ( substr( trim( $line ), -1, 1 ) === ';' ) {
				$affected_rows = $this->exec_import( $db, $templine );
				if ( false === $affected_rows ) {
					return false;
				}

				$templine = '';
			}
		}

		return true;
	}

	/**
	 * Import a string into a database.
	 *
	 * Generally this method is used when we grab a .sql file from within a .zip file and import it.
	 * Instead of saving the .sql file then importing, it comes straight from the .zip file as a string to here.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $string MySQL dump file as a string.
	 * @return bool
	 */
	public function import_string( $string ) {
		$lines = preg_split( "/\\r\\n|\\r|\\n/", $string );

		$lines = $this->fix_view_statements( $lines );

		if ( true === empty( $lines ) ) {
			return __( 'The Database User does not have the necessary priviliges to restore this database.', 'boldgrid-backup' );
		}

		$success = $this->import_lines( $lines );

		return $success;
	}

	/**
	 * Fix View Statements.
	 *
	 * Fixes view statements to ensure the definer matches the current db user.
	 *
	 * @since 1.14.0
	 *
	 * @param array $lines An array of lines from db file.
	 * @return array
	 */
	public function fix_view_statements( array $lines ) {

		$has_drop_view_if_exists = false;

		foreach ( $lines as $line ) {
			if ( strpos( $line, 'DROP VIEW IF EXISTS' ) ) {
				$has_drop_view_if_exists = true;
			}
		}

		if ( false === $has_drop_view_if_exists ) {
			return $lines;
		}

		$user_has_privileges = $this->has_db_privileges( array( 'SHOW VIEW', 'CREATE VIEW' ) );

		if ( false === $user_has_privileges ) {
			return array();
		}

		$fixed_lines = array();

		foreach ( $lines as $line ) {
			if ( strpos( $line, 'DEFINER=' ) === 9 ) {
				$fixed_lines[] = $this->fix_definer( $line );
			} else {
				$fixed_lines[] = $line;
			}
		}

		return $fixed_lines;
	}

	/**
	 * Fix Definer.
	 *
	 * Fixes the actual definer line.
	 *
	 * @since 1.14.0
	 *
	 * @param string $line The line from db dump file to fix.
	 * @return string The line with the DEFINER option removed.
	 */
	public function fix_definer( $line ) {
		$line_fixed_definer  = '';
		$sql_security_offset = strpos( $line, 'SQL SECURITY' );
		$line_fixed_definer  = substr( $line, 0, 9 );
		if ( strpos( $line, '@`%`' ) ) {
			$line_fixed_definer .= 'DEFINER=`' . DB_USER . '`@`%` ';
		} else {
			$line_fixed_definer .= 'DEFINER=`' . DB_USER . '`@`' . DB_HOST . '` ';
		}

		if ( strpos( $line, 'SQL SECURITY' ) ) {
			$line_fixed_definer .= subStr( $line, $sql_security_offset );
		} else {
			$line_fixed_definer .= '*/';
		}

		return $line_fixed_definer;
	}

	/**
	 * Tests if database user has specific privileges.
	 *
	 * @since 1.14.0
	 *
	 * @param array $privileges An array of permissions to check against.
	 * @return bool True if user has specified privileges.
	 */
	public function has_db_privileges( array $privileges ) {
		$user_grants = $this->get_db_privileges();
		if ( in_array( 'ALL', $user_grants, true ) ) {
			return true;
		}
		if ( count( $privileges ) === count( array_intersect( $privileges, $user_grants ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get database user privileges.
	 *
	 * @since 1.14.0
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return array An array of database user privileges.
	 */
	public function get_db_privileges() {
		$results = $this->show_grants_query();

		foreach ( $results as $result ) {
			$result[0]               = str_replace( '\\', '', $result[0] );
			$is_string_db_grant      = ( false !== strpos( $result[0], 'ON `' . DB_NAME . '`' ) );
			$is_string_all_grant     = ( false !== strpos( $result[0], 'ON *.*' ) );
			$is_grant_all_privileges = ( false !== strpos( $result[0], 'GRANT ALL PRIVILEGES' ) );

			if ( ( $is_string_db_grant || $is_string_all_grant ) && $is_grant_all_privileges ) {
				return array( 'ALL' );
			}
			if ( ( $is_string_db_grant ) && false === $is_grant_all_privileges ) {
				return $this->get_grants_array( $result[0] );
			}
		}
		return array();
	}

	/**
	 * Show Grants Query.
	 *
	 * Queries the database for 'SHOW GRANTS'.
	 *
	 * @since 1.14.0
	 *
	 * @return array an array of results from the database query
	 */
	public function show_grants_query() {
		$db           = new PDO( $this->get_connection_string(), DB_USER, DB_PASSWORD );
		$db_statement = $db->query( 'SHOW GRANTS' );
		return $db_statement->fetchAll();
	}

	/**
	 * Execute Import.
	 *
	 * Executes Import MySql Query.
	 *
	 * @since 1.14.0
	 *
	 * @param PDO    $db The PDO Object.
	 * @param string $sql_line The line of sql to execute.
	 *
	 * @return int Number of affected rows
	 */
	public function exec_import( PDO $db, $sql_line ) {
		return $db->exec( $sql_line );
	}

	/**
	 * Get a user's grants in the form of an array.
	 *
	 * @since 1.14.0
	 *
	 * @param string $grants_string A string containing the user's grants.
	 * @return array
	 */
	public function get_grants_array( $grants_string ) {
		$expected_grants_string_start = 6;

		if ( strpos( $grants_string, 'GRANT' ) === 0 ) {
			$grants_string = substr( $grants_string, $expected_grants_string_start );
		}

		$on_strpos = strpos( $grants_string, ' ON ' );
		if ( $on_strpos ) {
			$grants_string = substr( $grants_string, 0, $on_strpos );
		}

		return explode( ', ', $grants_string );
	}

	/**
	 * Get our PDO DSN connection string.
	 *
	 * This function is copied from class-boldgrid-backup-admin-db-dump.php. It hasn't been migrated to a utility function
	 * because these scripts are designed to be able to run without WordPress from the command line, including without the
	 * core wpdb::parse_db_host() function.
	 *
	 * @since 1.15.8
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
}
