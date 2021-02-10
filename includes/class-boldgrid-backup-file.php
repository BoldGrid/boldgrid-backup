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

		/*
		 * Implicit flushing will result in a flush operation after every output call, so that explicit
		 * calls to flush() will no longer be needed.
		 */
		ob_implicit_flush( true );

		// Close any PHP session, so another session can open during the download.
		session_write_close();

		/*
		 * Begin code to send the file, chunked.
		 *
		 * Unable to find a replacement in $wp_filesystem.
		 *
		 * Inspired by https://stackoverflow.com/questions/6914912/streaming-a-large-file-using-php
		 *
		 * This method was needed because some users couldn't download large files using readfile() alone.
		 * They were able to download small backup files, but not larger ones.
		 */
		$buffer = '';

		// If we can't open the file, abort.
		$handle = fopen( $filepath, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( false === $handle ) {
			wp_die();
		}

		// Loop through the file and send it 1MB at a time.
		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, 1024 * 1024 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			echo $buffer; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		wp_die();
	}
}
