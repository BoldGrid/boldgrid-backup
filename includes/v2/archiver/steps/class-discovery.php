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

namespace Boldgrid\Backup\V2\Archiver\Steps;

/**
 * Class: Discovery
 *
 * @since SINCEVERSION
 */
class Discovery extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the discovery scripts.
	 *
	 * Used to determine what needs to be backed up.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$this->database();
		$this->files();

		$this->complete();

		return true;
	}

	/**
	 * Discover which tables and views in the database should be backed up.
	 *
	 * @since SINCEVERSION
	 */
	private function database() {
		$tables = $this->get_core()->db_dump->get_table_list();

		$this->get_data_type( 'step' )->set_key( 'tables', $tables );
	}

	/**
	 * Discover which files need to be backed up.
	 *
	 * @since SINCEVERSION
	 */
	private function files() {
		$filelist_creator = new \Boldgrid\Backup\V2\Filelist\Create();
		$files            = $filelist_creator->run();
		$total_size       = $filelist_creator->get_total_size();

		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived', $total_size );
		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived_size_format', size_format( $total_size, 2 ) );

		$this->info->set_key( 'total_size', $total_size );

		$filelists = array();

		foreach ( $files as $type => $data ) {
			$filename = 'filelist-' . $type . '.json';
			$this->write_contents( $filename, wp_json_encode( $data ) );

			$filelists[] = $filename;
		}

		$this->get_data_type( 'step' )->set_key( 'filelists', $filelists );
	}
}
