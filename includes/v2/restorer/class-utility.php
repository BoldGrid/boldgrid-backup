<?php
/**
 * Utility class.
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
 * Class: Utility
 *
 * @since SINCEVERSION
 */
class Utility {
	/**
	 *
	 */
	public static function get_option() {
		return new \Boldgrid\Backup\Option\Option( 'boldgrid_backup_restore_data' );
	}
}
