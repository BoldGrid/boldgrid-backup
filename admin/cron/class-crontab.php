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
	 * Boldgrid_Backup_Admin_Core object.
	 *
	 * @since 1.11.1
	 * @access private
	 *
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.11.1
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
	}

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
		$all_crons = $this->core->cron->get_all( false );
		$all_crons = false === $all_crons ? array() : $all_crons;

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

	/**
	 * Write to the system crontab.
	 *
	 * The crontab contents will be replaced with the string passed to this method.
	 *
	 * @since 1.11.1
	 *
	 * @param  string $crontab The crontab contents to be written.
	 * @return bool
	 */
	public function write_crontab( $crontab ) {
		$backup_directory = $this->core->backup_dir->get();

		if ( ! $this->core->wp_filesystem->is_writable( $backup_directory ) ) {
			return false;
		}

		// Strip extra line breaks.
		$crontab = str_replace( "\n\n", "\n", $crontab );

		// Trim the crontab.
		$crontab = trim( $crontab );

		// Add a line break at the end of the file.
		$crontab .= "\n";

		// Save the temp crontab to file.
		$temp_crontab_path = $backup_directory . '/crontab.' . microtime( true ) . '.tmp';

		// Save a temporary file for crontab.
		$this->core->wp_filesystem->put_contents( $temp_crontab_path, $crontab, 0600 );

		// Check if the defaults file was written.
		if ( ! $this->core->wp_filesystem->exists( $temp_crontab_path ) ) {
			return false;
		}

		// Write crontab.
		$command = 'crontab ' . $temp_crontab_path;

		$this->core->execute_command( $command, $success );

		// Remove temp crontab file.
		$this->core->wp_filesystem->delete( $temp_crontab_path, false, 'f' );

		return (bool) $success;
	}
}
