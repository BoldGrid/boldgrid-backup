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
		$core = apply_filters( 'boldgrid_backup_get_core', null );
		$log  = new Boldgrid_Backup_Admin_Log( $core );
		$log->init( 'backup-download.log' );
		$log->add( 'Initializing send_file() method...' );

		if ( empty( $filepath ) || ! $core->wp_filesystem->exists( $filepath ) ) {
			$log->add( 'Invalid filepath.' );
			wp_redirect( get_site_url(), 404 );
		}

		$filename = basename( $filepath );

		if ( empty( $filesize ) ) {
			$filesize = $core->wp_filesystem->size( $filepath );
		}

		// Send header.
		$log->add( 'Sending headers...' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Type: binary/octet-stream' );
		header( 'Content-Length: ' . $filesize );

		// Clean up output buffering.
		while ( $ob_level = ob_get_level() ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$buffer_contents = ob_get_contents();
			$log->add( 'ob level ' . $ob_level . ' contents preview: ' . substr( $buffer_contents, 0, 100 ) );

			$log->add( 'Calling ob_end_clean()... ' . ( ob_end_clean() ? 'Success' : 'Fail' ) );
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
			$log->add( 'Invalid handle. fopen failed.' );
			wp_die();
		}

		// Loop through the file and send it 1MB at a time.
		$buffer_size = 1024 * 1024;
		$log->add( 'Beginnig to send file... Buffer size: ' . size_format( $buffer_size, 2 ) );
		while ( ! feof( $handle ) ) {
			$time_start_read = microtime( true );
			$buffer          = fread( $handle, $buffer_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			$duration_read   = microtime( true ) - $time_start_read;

			$time_start_send = microtime( true );
			echo $buffer; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			$duration_send = microtime( true ) - $time_start_send;

			$log->add( 'Buffer read in ' . round( $duration_read, 4 ) . ' seconds and sent in ' . round( $duration_send, 4 ) . ' seconds.' );
		}
		$log->add( 'Finished sending file.' );

		$log->add( 'Closing file...' );
		if ( fclose( $handle ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			$log->add( 'File closed successfully.' );
		} else {
			$log->add( 'Error closing file.' );
		}

		$log->add( 'send_file() method complete. Ending with wp_die().' );
		wp_die();
	}
}
