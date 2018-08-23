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
			$archive = $this->core->archive->get_by_name( $token_details['id'] );

			if ( ! empty( $archive ) ) {
				// Send file and die nicely.
				Boldgrid_Backup_File::send_file( $archive['filepath'], $archive['filesize'] );
			}
		}

		wp_redirect( get_site_url(), 404 );
	}
}
