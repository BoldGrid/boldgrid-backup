<?php
/**
 * File: class-boldgrid-backup-download.php
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
 * Class: Boldgrid_Backup_Download
 *
 * @since 1.7.0
 */
class Boldgrid_Backup_Download {
	/**
	 * The core class object.
	 *
	 * @since  1.7.0
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Callback function for downloading a backup archive file using a public link.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Authentication::get_token_details()
	 * @see Boldgrid_Backup_Admin_Archive::get_by_name()
	 * @see Boldgrid_Backup_File::send_file()
	 *
	 * @uses $_GET['t'] Token.
	 */
	public function public_download() {
		$token         = ! empty( $_GET['t'] ) ? sanitize_key( $_GET['t'] ) : null; // phpcs:ignore WordPress.CSRF.NonceVerification
		$token_details = Boldgrid_Backup_Authentication::get_token_details( $token );

		if ( $token_details['is_valid'] ) {
			$archive = \Boldgrid\Backup\Archive\Factory::get_by_filename( $token_details['id'] );

			if ( $archive->is_virtual ) {
				$this->download_virtual( $archive );
			} else {
				// Send file and die nicely.
				Boldgrid_Backup_File::send_file( $archive->filepath, $archive->get_filesize() );
			}
		}

		wp_redirect( get_site_url(), 404 );
	}

	/**
	 *
	 */
	private function download_virtual( $archive ) {
		// Verification handled by calling method, self::public_download().
		$filename = ! empty( $_GET['filename'] ) ? $_GET['filename'] : null; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		$zips     = $archive->virtual->get_dirlist()->get_by_extension( 'zip' );

		/*
		 * If the user didn't specify a specific filename, we'll send them a list of all the zips they
		 * will need to download.
		 *
		 * Otherwise, they requested a specific file to download.
		 */
		if ( empty( $filename ) ) {
			// Only return the filename and size.
			foreach ( $zips as &$zip ) {
				$zip = array(
					'name' => $zip['name'],
					'size' => $zip['size'],
				);
			}

			$return = array(
				'folder' => $archive->virtual->get_folder(),
				'zips'   => $zips,
			);

			wp_send_json_success( $return );
		} else {
			$file = $archive->virtual->get_file( $filename );
			$file->send();
		}
	}
}
