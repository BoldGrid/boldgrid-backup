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
	 * Import a mysqldump.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file The filepath to our file.
	 * @return bool   True on success.
	 */
	public function import( $file ) {
		$db = new PDO( sprintf( 'mysql:host=%1$s;dbname=%2$s;', DB_HOST, DB_NAME ), DB_USER, DB_PASSWORD );

		$templine = '';

		$lines = file( $file );
		if( false === $lines ) {
			return array( 'error' => sprintf( __( 'Unable to open mysqldump, %1$s.', 'boldgrid-backup' ), $file ) );
		}

		foreach ($lines as $line)
		{
			// Skip comments and empty lines.
			if ( substr($line, 0, 2) === '--' || empty( $line ) ) {
				continue;
			}

			$templine .= $line;

			// Check if this is the end of the query.
			if( substr( trim( $line ), -1, 1 ) === ';' ) {
				$affected_rows = $db->exec( $templine );
				if( false === $affected_rows ) {
					return false;
				}

				$templine = '';
			}
		}

		return true;
	}
}

