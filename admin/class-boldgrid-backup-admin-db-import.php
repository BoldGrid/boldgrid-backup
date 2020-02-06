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
 * Class: Boldgrid_Backup_Admin_Db_Import
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Db_Import {
	/**
	 * The core class object.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
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
	 * Import a mysqldump.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file File path.
	 * @return bool TRUE on success.
	 */
	public function import( $file ) {

		$lines = file( $file );

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

		if ( false === $lines ) {
			return array(
				'error' => sprintf(
					__( 'MySQL Database User does not have necessary priviliges to restore this database.', 'boldgrid-backup' ),
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
	 * Pass in "file.zip" and "backup.sql" and we'll find "backup.sql" in the
	 * "file.zip" file and restore it.
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

		$sql = ! empty( $file_contents[0]['content'] ) ? $file_contents[0]['content'] : null;
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
	 * This method accepts an array of $lines, and loops through each $line and
	 * imports it.
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
		$db = new PDO( sprintf( 'mysql:host=%1$s;dbname=%2$s;', DB_HOST, DB_NAME ), DB_USER, DB_PASSWORD );

		$templine = '';

		foreach ( $lines as $line ) {
			// Skip comments and empty lines.
			if ( substr( $line, 0, 2 ) === '--' || empty( $line ) ) {
				continue;
			}

			$templine .= $line;

			// Check if this is the end of the query.
			if ( substr( trim( $line ), -1, 1 ) === ';' ) {
				$affected_rows = $db->exec( $templine );
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
	 * Generally this method is used when we grab a .sql file from within a .zip
	 * file and import it. Instead of saving the .sql file then importing, it
	 * comes straight from the .zip file as a string to here.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $string MySQL dump file as a string.
	 * @return bool
	 */
	public function import_string( $string ) {
		$lines = preg_split( "/\\r\\n|\\r|\\n/", $string );

		$lines = $this->fix_view_statements( $lines );

		$success = $this->import_lines( $lines );

		return $success;
	}

	/**
	 * Fix View Statements.
	 *
	 * Fixes view statements to ensure the definer matches the
	 * current db user.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $file db dump filename.
	 * @return array an array of lines from db file.
	 */
	public function fix_view_statements( $lines ) {

		$has_drop_view_if_exists = false;

		foreach ( $lines as $line ) {
			if ( strpos( $line, 'DROP VIEW IF EXISTS' ) ) {
				$has_drop_view_if_exists = true;
			}
		}

		if ( false === $has_drop_view_if_exists ) {
			return $lines;
		}

		$user_has_privileges = $this->has_db_privileges( [ 'SHOW VIEW', 'CREATE VIEW' ] );

		if ( false === $user_has_privileges ) {
			return false;
		}

		$fixed_lines = [];

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
	 * Fix Definer
	 *
	 * Fixes the actual definer line.
	 *
	 * @since SINCEVERSION
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
	 * Tests if database user has specific privileges
	 *
	 * @since SINCEVERSION
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
	 * Get database user privileges
	 *
	 * @since SINCEVERSION
	 *
	 * @global wpdb $wpdb The WordPress database class object.
	 *
	 * @return array An array of database user privileges.
	 */
	public function get_db_privileges() {
		$db_grant_string = 'ON `' . DB_NAME . '`';

		$db           = new PDO( sprintf( 'mysql:host=%1$s;dbname=%2$s;', DB_HOST, DB_NAME ), DB_USER, DB_PASSWORD );
		$db_statement = $db->query( 'SHOW GRANTS' );
		$results      = $db_statement->fetchAll();

		foreach ( $results as $result ) {
			if ( false !== strpos( $result[0], $db_grant_string ) && false !== strpos( $result[0], 'GRANT ALL PRIVILEGES' ) ) {
				return [ 'ALL' ];
			}
			if ( false !== strpos( $result[0], $db_grant_string ) && false === strpos( $result[0], 'GRANT ALL PRIVILEGES' ) ) {
				return $this->get_grants_array( $result[0] );
			} // } else {
			// 	print_r( serialize( ( $result[0] ) ) );
			// }
		}
		return [ $result ];
	}

	/**
	 * Get a user's grants in the form of an array
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $grants_string.
	 * @return array An array of grants.
	 */
	public function get_grants_array( $grants_string ) {
		if ( strpos( $grants_string, 'GRANT' ) === 0 ) {
			$grants_string = substr( $grants_string, 6 );
		}
		if ( strpos( $grants_string, 'ON' ) ) {
			$grants_string = substr( $grants_string, 0, strpos( $grants_string, ' ON ' ) );
		}

		return explode( ', ', $grants_string );
	}
}
