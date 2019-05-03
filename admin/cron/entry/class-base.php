<?php
/**
 * Entry class.
 *
 * This is our base class for an entry.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Cron\Entry;

use Boldgrid\Backup\Admin\Cron\Entry\Entry;

/**
 * Class: Entry
 *
 * @since xxx
 */
class Base implements Entry {
	/**
	 * Get our cron entry's next runtime.
	 *
	 * @since xxx
	 *
	 * @return bool
	 */
	public function get_next_runtime() {
		return false;
	}

	/**
	 * Initialize our cron entry.
	 *
	 * @since xxx
	 *
	 * @return bool
	 */
	public function init_via_search( array $patterns = [] ) {
		return false;
	}

	/**
	 * Get whether or not our cron entry exists in the cron engine.
	 *
	 * @since xxx
	 *
	 * @return boolean
	 */
	public function is_set() {
		return false;
	}
}
