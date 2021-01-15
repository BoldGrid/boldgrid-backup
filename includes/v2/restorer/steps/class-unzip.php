<?php
/**
 * Unzip class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Restorer\Steps;

/**
 * Class: Unzip
 *
 * @since SINCEVERSION
 */
class Unzip extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the discovery scripts.
	 *
	 * Used to determine what needs to be backed up.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		// Unzip the backup.
		$zip_filepath = $this->get_data_type( 'step' )->get_key( 'zip_filepath' );
		$unzip_status = unzip_file( $zip_filepath, ABSPATH );

		// Log our success.
		$zip_filesize = $this->get_core()->wp_filesystem->size( $zip_filepath );
		$this->log( 'Archive: ' . $zip_filepath . ' / ' . size_format( $zip_filesize ) );
		$this->log( 'Unzip status: ' . print_r( $unzip_status, 1 ) );

		if ( is_wp_error( $unzip_status ) ) {
			$error = false;

			/**
			 * Take action when a restoration fails.
			 *
			 * Those actions may return a custom error message, such as:
			 * "Your restoration failed, but we did XYZ. Please try again".
			 *
			 * @param WP_Error $unzip_status
			 */
			$error = apply_filters( 'boldgrid_backup_restore_fail', $unzip_status );

			if ( empty( $error ) ) {
				$message = $unzip_status->get_error_message();
				$data    = $unzip_status->get_error_data();
				$error   = sprintf( '%1$s%2$s', $message, is_string( $data ) && ! empty( $data ) ? ': ' . $data : '' );
			}

			// return [ 'error' => $error ];
			$this->fail( $error );
			return false;
		}

		$this->complete();

		return true;
	}
}
