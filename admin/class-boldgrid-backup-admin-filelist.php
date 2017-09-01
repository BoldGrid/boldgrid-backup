<?php
/**
 * Boldgrid Backup Admin Filelist.
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
 * BoldGrid Backup Admin Filelist Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Filelist {

	/**
	 * Get the total size of a $filelist.
	 *
	 * @since 1.5.1
	 *
	 * @param array $filelist {
	 *     An array files and data about those files.
	 *
	 *     @type string 0 The path of a file.   /home/user/public_html/readme.html
	 *     @type string 1 The filename.         readme.html
	 *     @type int    2 The size of the file. 7413
	 * }
	 * @return int
	 */
	public function get_total_size( $filelist ) {
		$total_size = 0;

		foreach ( $filelist as $fileinfo ) {
			$total_size += $fileinfo[2];
		}

		return $total_size;
	}
}
