<?php
/**
 * Crontab class.
 *
 * This is an object class, representing crontab.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Cron;

/**
 * Class: Crontab
 *
 * @since 1.11.0
 */
class Crontab {
	/**
	 * Search for cron entries.
	 *
	 * This method is similar to the Boldgrid_Backup_Admin_Cron::entry_search() method, except it
	 * accepts an array of patterns to search for, rather than just one.
	 *
	 * @since 1.11.0
	 *
	 * @param  array $patterns An array of patterns to search for. All patterns must be found in
	 *                         order to return a cron entry as a match.
	 * @return array           An array of crons.
	 */
	public function find_crons( array $patterns = [] ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$all_crons = $core->cron->get_all( false );

		$matched_crons = [];

		foreach ( $all_crons as $cron ) {
			$all_patterns_found = true;

			foreach ( $patterns as $pattern ) {
				if ( false === strpos( $cron, $pattern ) ) {
					$all_patterns_found = false;
				}
			}

			if ( $all_patterns_found ) {
				$matched_crons[] = $cron;
			}
		}

		return $matched_crons;
	}
}
