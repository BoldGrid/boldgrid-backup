<?php
/**
 * Database Import.
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
/**
 * BoldGrid Backup Admin Database Import class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Db_Import {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Errors.
	 *
	 * @since 1.5.4
	 * @var   array
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
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
	 * @param  string $file The filepath to our file.
	 * @return bool   True on success.
	 */
	public function import( $file ) {
		$lines = file( $file );
		if ( false === $lines ) {
			return array( 'error' => sprintf( __( 'Unable to open mysqldump, %1$s.', 'boldgrid-backup' ), $file ) );
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
	 * @since 1.5.4
	 *
	 * @param  string $archive_filepath
	 * @param  string $file
	 * @return bool
	 */
	public function import_from_archive( $archive_filepath, $file ) {
		$this->core->archive->init( $archive_filepath );
		$file_from_archive = $this->core->archive->get_file( $file );

		$sql = ! empty( $file_from_archive[0]['content'] ) ? $file_from_archive[0]['content'] : null;
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
	 * @since 1.5.4
	 *
	 * @param  array $lines
	 * @return bool
	 */
	public function import_lines( $lines ) {
		if ( empty( $lines ) ) {
			return false;
		}

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
	 * @since 1.5.4
	 *
	 * @param  string $string
	 * @return bool
	 */
	public function import_string( $string ) {
		$lines = preg_split( "/\\r\\n|\\r|\\n/", $string );

		$success = $this->import_lines( $lines );

		return $success;
	}
}

