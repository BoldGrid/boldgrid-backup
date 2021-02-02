<?php
/**
 * Discovery class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Fetcher\Steps;

/**
 * Class: Discovery
 *
 * @since SINCEVERSION
 */
class Discovery extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the discovery scripts.
	 *
	 * Used to determine which backup files need to be downloaded.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$download_url = $this->info->get_key( 'download_url' );

		// Get our download data from the remove WordPress site.
		$data = \Boldgrid\Backup\Utility\Remote::get_json( $download_url );
		if ( is_wp_error( $data ) ) {
			return false;
		} elseif ( empty( $data ) ) {
			return false;
		}

		// Move fetcher process from /fetcher_1234 to /boldgrid-backup-1234-12345678/fetcher_1234
		$backup_folder = \Boldgrid\Backup\Utility\Virtual_Folder::get_by_id( $data['data']['id'] );
		$moved         = $this->move_working_dir( $backup_folder );
		if ( ! $moved ) {
			return false;
		}

		$this->info->set_key( 'zips', $data['data']['zips'] );
		$this->info->set_key( 'backup_folder', $backup_folder );
		$this->info->set_key( 'backup_id', $data['data']['id'] );

		$this->complete();

		return true;
	}

	/**
	 * Move our working directory.
	 *
	 * Now that we have the directory, make the following change:
	 * # /home/user/boldgrid_backup/fetcher_1234
	 * # /home/user/boldgrid_backup/boldgrid-backup-1234-12345678/fetcher_1234
	 */
	private function move_working_dir( $new_folder ) {
		// Make sure the backup folder exist.
		$backup_dir = $this->get_core()->backup_dir->get_path_to( $new_folder );
		if ( ! $this->core->wp_filesystem->exists( $backup_dir ) ) {
			if ( ! $this->core->wp_filesystem->mkdir( $backup_dir ) ) {
				return false;
			}
		}

		$new_dir = trailingslashit( $backup_dir ) . $this->get_folder();

		return $this->move_dir( $new_dir );
	}
}
