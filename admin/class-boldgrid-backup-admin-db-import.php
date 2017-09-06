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
		// Connect to MySQL server
		mysql_connect( DB_HOST, DB_USER, DB_PASS ) or die('Error connecting to MySQL server: ' . mysql_error());
		// Select database
		mysql_select_db( DB_NAME ) or die('Error selecting MySQL database: ' . mysql_error());

		// Temporary variable, used to store current query
		$templine = '';
		// Read in entire file
		$lines = file( $file );
		// Loop through each line
		foreach ($lines as $line)
		{
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';')
			{
				// Perform the query
				mysql_query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
				// Reset temp variable to empty
				$templine = '';
			}
		}
		echo "Tables imported successfully";

		return true;
	}
}

