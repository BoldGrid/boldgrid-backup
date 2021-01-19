<?php
/**
 * Archiver class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver;

/**
 * Class: Archiver
 *
 * @since SINCEVERSION
 */
class Archiver extends \Boldgrid\Backup\V2\Step\Step {

	/**
	 *
	 */
	protected $unresponsive_time = 60;

	/**
	 * Run the archive process.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'dir', $this->get_dir() );

		$this->add_attempt();

		$file_types = array(
			'plugins' => array(),
			'themes'  => array(),
			'uploads' => array(),
			'other'   => array(),
			'sql'     => array(
				'use_full_filepath' => true,
				'part_configs'      => array(
					'junk_paths' => true,
				),
			),
		);

		$steps = array(
			new \Boldgrid\Backup\V2\Archiver\Steps\Discovery( 'discovery', $this->id, $this->get_dir() ),
			new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Database( 'archive_database', $this->id, $this->get_dir() ),
		);

		foreach ( $file_types as $type => $type_configs ) {
			$default_type_configs = array(
				'id'   => 'archive_' . $type,
				'type' => $type,
			);

			$configs = wp_parse_args( $type_configs, $default_type_configs );

			$step = new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files( 'archive_' . $type, $this->id, $this->get_dir() );
			$step->set_configs( $configs );

			$steps[] = $step;
		}

		foreach ( $steps as $step ) {
			$this->check_in();

			if ( $step->maybe_run() ) {
				$step_success = $step->run();

				if ( ! $step_success ) {
					// Failed to create zip. todo: cleanup?
					$this->log( 'ERROR: Step failed.' );
					return false;
				}
			}
		}

		$this->info->set_key( 'lastmodunix', $this->get_core()->wp_filesystem->mtime( $this->info->get_key( 'filepath' ) ) );

		$this->complete();
	}
}
