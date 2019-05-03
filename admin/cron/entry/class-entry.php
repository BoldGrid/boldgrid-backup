<?php
/**
 * Entry class.
 *
 * This is our entry interface.
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

/**
 * Class: Entry
 *
 * @since xxx
 */
interface Entry {
	public function get_next_runtime();

	public function init_via_search( array $patterns = [] );

	public function is_set();
}
