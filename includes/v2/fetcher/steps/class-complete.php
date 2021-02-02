<?php
/**
 * Complete class.
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
 * Class: Complete.
 *
 * @since SINCEVERSION
 */
class Complete extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the complete process (post fetch).
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		/*
		 * We have the folder filed with zips, now we need to create the "virtual" zip.
		 *
		 * # /home/user/boldgrid_backup/boldgrid-backup-1234-abcd/
		 * # /home/user/boldgrid_backup/boldgrid-backup-1234-abcd.zip
		 */
		if ( ! \Boldgrid\Backup\Utility\Virtual_Folder::zip_by_folder( $this->info->get_key( 'backup_folder' ) ) ) {
			$this->fail();
			return false;
		}

		$this->complete();

		return true;
	}
}
