<?php
/**
 * File: class-license.php
 *
 * @link https://www.boldgrid.com
 * @since     SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Library\Library;

/**
 * Class: License.
 *
 * This is a mock of \Boldgrid\Library\Library\License for testing.
 */
class License {
	/**
	 * Is Premium override.
	 *
	 * @param string $slug slug.
	 * @return bool
	 */
	public function isPremium( $slug ) { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		return true;
	}
}
