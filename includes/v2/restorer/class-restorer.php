<?php
/**
 * Restorer class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Restorer;

/**
 * Class: Restorer
 *
 * @since SINCEVERSION
 */
class Restorer extends \Boldgrid\Backup\V2\Step\Step {

	/**
	 * Run the restoration process.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$archive = \Boldgrid\Backup\Archive\Factory::get_by_dir( $this->get_data_type( 'step' )->get_key( 'backup_folder_path' ) );
		if ( ! $archive->is_virtual ) {
			$this->fail( 'Unable to get archive by directory.' );
			return array( 'error' => 'Unable to get archive by directory.' );
		}

		$zips = $archive->virtual->get_dirlist()->get_by_extension( 'zip' );

		$steps = array();

		foreach ( $zips as $data ) {
			// Create the step.
			$step_id = 'step_' . $data['name'] . '.json';
			$step    = new \Boldgrid\Backup\V2\Restorer\Steps\Unzip( $step_id, $this->id, $this->get_dir() );

			// Tell the new Unzip step the full path to the zip file it will unzip.
			$zip_filepath = trailingslashit( $this->get_data_type( 'step' )->get_key( 'backup_folder_path' ) ) . $data['name'];
			$step->get_data_type( 'step' )->set_key( 'zip_filepath', $zip_filepath );

			$steps[] = $step;
		}

		foreach ( $steps as $step ) {
			$this->check_in();

			if ( $step->maybe_run() ) {
				$step_success = $step->run();

				if ( ! $step_success ) {
					return false;
				}
			}
		}

		$this->complete();

		die();

		return true;
	}
}
