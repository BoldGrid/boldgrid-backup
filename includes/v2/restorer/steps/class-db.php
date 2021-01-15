<?php
/**
 * Db class.
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
 * Class: Db
 *
 * @since SINCEVERSION
 */
class Db extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the discovery scripts.
	 *
	 * Used to determine what needs to be backed up.
	 *
	 * @since SINCEVERSION
	 */
	public function run( $zip_filepath, $db_dump_filepath ) {
		$this->add_attempt();

		$db_restorer = new \Boldgrid\Backup\Restorer\Db( $zip_filepath, $db_dump_filepath );
		$db_restorer->run();

		$this->complete();

		return true;
	}
}
