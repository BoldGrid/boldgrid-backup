<?php
/**
 * Fetcher class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Fetcher;

/**
 * Class: Fetcher
 *
 * @since SINCEVERSION
 */
class Fetcher extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Get info for this process to return via ajax.
	 *
	 * Version 1 of the fetcher returns some info via get_info(), but it conflicts with our pre-existing
	 * get_info() method. This method simply returns some info to help render the admin notice.
	 */
	public function get_ajax_info() {
		$filename = \Boldgrid\Backup\Utility\Virtual_Folder::filename_by_folder( $this->info->get_key( 'backup_folder' ) );

		$archive = \Boldgrid\Backup\Archive\Factory::get_by_filename( $filename );

		return array(
			'filepath'        => $archive->filepath,
			'detailsUrl'      => admin_url( 'admin.php?page=boldgrid-backup-archive-details&filename=' . $archive->filename ),
			'archiveFilename' => $archive->filename,
			'archiveKey'      => $archive->get_key(),
		);
	}

	/**
	 * Run the fetching process..
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->maybe_init_logger( 'transfer-archive-' . time() . '.log' );

		$this->add_attempt();

		$resumer = new \Boldgrid\Backup\V2\Fetcher\Resumer();
		$resumer->maybe_add_cron();

		$steps = array(
			new \Boldgrid\Backup\V2\Fetcher\Steps\Discovery( 'discovery', $this->id, $this->get_dir() ),
		);

		foreach ( $steps as $step ) {
			$this->check_in();

			if ( $step->maybe_run() ) {
				$step->run();

				if ( $step->is_fail() ) {
					return false;
				}
			}
		}

		$steps = array();

		$zips = $this->info->get_key( 'zips' );
		if ( empty( $zips ) ) {
			return false;
		}

		foreach ( $zips as $zip ) {
			$step_name = 'fetch_' . $zip['name'];
			$fetcher   = new \Boldgrid\Backup\V2\Fetcher\Steps\Fetch( $step_name, $this->id, $this->get_dir() );

			$url = $this->info->get_key( 'download_url' ) . '&filename=' . $zip['name'];
			$fetcher->set_url( $url );
			$fetcher->set_backup_filename( $zip['name'] );
			$fetcher->set_backup_size( $zip['size'] );

			$steps[] = $fetcher;
		}

		$steps[] = new \Boldgrid\Backup\V2\Fetcher\Steps\Complete( 'complete', $this->id, $this->get_dir() );

		foreach ( $steps as $step ) {
			$this->check_in();

			if ( $step->maybe_run() ) {
				$step->run();

				if ( $step->is_fail() ) {
					return false;
				}
			}
		}

		$this->complete();

		$resumer->remove_cron();

		return true;
	}
}
