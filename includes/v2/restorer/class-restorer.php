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
			$step_id = 'step_restore_' . $data['name'];
			$step    = new \Boldgrid\Backup\V2\Restorer\Steps\Unzip( $step_id, $this->id, $this->get_dir() );

			// Tell the new Unzip step the full path to the zip file it will unzip.
			$zip_filepath = trailingslashit( $this->get_data_type( 'step' )->get_key( 'backup_folder_path' ) ) . $data['name'];
			$step->get_data_type( 'step' )->set_key( 'zip_filepath', $zip_filepath );

			$steps[] = $step;
		}

		foreach ( $steps as $step ) {
			$this->check_in();

			if ( $step->maybe_run() ) {
				$step->run();

				if ( $step->is_fail() ) {
					return false;
				}
			}
		}

		$is_post_restore = $this->get_data_type( 'step' )->get_key( 'is_post_restore' );
		if ( ! $is_post_restore ) {
			/**
			 * Action to take after restoring an archive.
			 *
			 * @since 1.5.1
			 *
			 * @param array $info
			 */
			do_action( 'boldgrid_backup_post_restore', $this->info->get() );
		}
		$this->get_data_type( 'step' )->set_key( 'is_post_restore', true );

		// After unzipping all the files, find the sql file and restore it.
		$db_step          = new \Boldgrid\Backup\V2\Restorer\Steps\Db( 'step_restore_db', $this->id, $this->get_dir());
		$zip_filepath     = trailingslashit( $this->get_data_type( 'step' )->get_key( 'backup_folder_path' ) ) . 'zip-sql-1.zip';
		$db_dump_filepath = $this->get_core()->get_dump_file( $zip_filepath );
		if ( ! empty( $db_dump_filepath ) && $db_step->maybe_run() ) {
			$db_step->run( $zip_filepath, $db_dump_filepath );

			if ( $db_step->is_fail() ) {
				return false;
			}
		}

		$this->complete();

		return true;
	}
}
