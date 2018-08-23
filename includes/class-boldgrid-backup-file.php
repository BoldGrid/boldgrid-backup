<?php
/**
 * File: class-boldgrid-backup-file.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.7.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_File
 *
 * @since 1.7.0
 */
class Boldgrid_Backup_File {
	/**
	 * Send a file for download and die.
	 *
	 * @since 1.7.0
	 *
	 * @static
	 * @global $wp_filesystem
	 *
	 * @param string $filepath File path.
	 * @param int    $filesize File size (optional).
	 */
	public static function send_file( $filepath, $filesize = null ) {
		WP_Filesystem();
		global $wp_filesystem;

		// phpcs:disable WordPress.VIP
		if ( empty( $filepath ) || ! $wp_filesystem->exists( $filepath ) ) {
			wp_redirect( get_site_url(), 404 );
		}

		$filename = basename( $filepath );

		if ( empty( $filesize ) ) {
			$filesize = $wp_filesystem->size( $filepath );
		}

		// Send header.
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Type: binary/octet-stream' );
		header( 'Content-Length: ' . $filesize );

		// Check and flush output buffer if needed.
		if ( 0 !== ob_get_level() ) {
			ob_end_flush();
		}

		// Close any PHP session, so another session can open during the download.
		session_write_close();

		// Send the file.  Not finding a replacement in $wp_filesystem.
		// phpcs:disable
		readfile( $filepath );
		// phpcs:enable

		wp_die();

		// phpcs:enable WordPress.VIP
	}
}
