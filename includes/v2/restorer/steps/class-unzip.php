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

		// $zip_filepath = $this->get_data_type( 'step' )->get_key( 'zip_filepath' );

		$this->complete();

		return true;
	}
}
