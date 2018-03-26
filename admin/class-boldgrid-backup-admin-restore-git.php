<?php
/**
 * BoldGrid Backup Admin Restore Git.
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
 * BoldGrid Backup Admin Restore Git.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Restore_Git {

	/**
	 * Chmod .git/objects so that we can restore without file permission issues.
	 *
	 * @since 1.5.1
	 *
	 * @global $wp_filesystem
	 *
	 * @param  string $dir
	 * @return string
	 */
	public function chmod_objects( $dir ) {
		global $wp_filesystem;

		$message = sprintf( __( 'A file permissions error was encountered when attempting to restore files in "%1$s".', 'boldgrid-backup' ), ABSPATH . $dir ) ;

		$chmodded = $wp_filesystem->chmod( ABSPATH . $dir, FS_CHMOD_FILE, true );

		if( $chmodded ) {
			return $message . ' ' . __( 'We updated file permissions, and suggest that you attempt the restoration again.', 'boldgrid-backup' );
		} else {
			return $message . ' ' . __( 'We attempted to updated file permissions, but that does not appear to have worked. Before attempting to restore again, please manually review these file permissions.', 'boldgrid-backup' );
		}
	}
}
