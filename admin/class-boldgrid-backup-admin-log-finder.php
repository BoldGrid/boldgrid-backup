<?php
/**
 * File: class-boldgrid-backup-admin-log-finder.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.14.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Log_Finder
 *
 * @since 1.14.2
 */
class Boldgrid_Backup_Admin_Log_Finder {
	/**
	 * An instance of core.
	 *
	 * @since 1.4.2
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * A list of files found.
	 *
	 * This is a "log finder" class. $results is an array of log files found.
	 *
	 * @since 1.14.2
	 * @access private
	 * @var array
	 */
	private $results = array();

	/**
	 * Constructor.
	 *
	 * @since 1.14.2
	 */
	public function __construct() {
		$this->core     = apply_filters( 'boldgrid_backup_get_core', null );
		$this->logs_dir = $this->core->backup_dir->get_logs_dir();
		$this->init_results();
	}

	/**
	 * Initialize our results.
	 *
	 * By default, everything in the logs directory will be returned. We'll then use "filter" functions
	 * within this class to find what we need.
	 *
	 * @since 1.14.2
	 */
	public function init_results() {
		$logs_dir      = $this->core->backup_dir->get_logs_dir();
		$this->results = $this->core->wp_filesystem->dirlist( $logs_dir );
	}

	/**
	 * Find files based on age.
	 *
	 * Example:
	 * filter_age( 10, 'older' );
	 * Find files older than 10 seconds. Any file equal to or younger than 10 seconds will be removed
	 * from the results.
	 *
	 * @todo 'younger' has not yet been programmed.
	 *
	 * @since 1.14.2
	 *
	 * @param  int    $seconds Number of seconds to compare.
	 * @param  string $type    Whether we are looking for files older or younger than.
	 * @retrun array
	 */
	public function filter_age( $seconds, $type = 'older' ) {
		foreach ( $this->results as $item_key => $item ) {
			$age = time() - $item['lastmodunix'];

			switch ( $type ) {
				// This means find files "older" than $seconds.
				case 'older':
				default:
					if ( ! ( $age > $seconds ) ) {
						unset( $this->results[ $item_key ] );
					}
					break;
			}
		}

		return $this->results;
	}

	/**
	 * Filter files based on whether they contain something.
	 *
	 * For example, find all files containing the word ".log".
	 *
	 * Example:
	 * filter_containing( 'name', 'archive', 'start' );
	 * Find all the files that start with "archive" in the name. If the filename does not start with
	 * "archive", remove it from the results.
	 *
	 * @since 1.14.2
	 *
	 * @param  string $key The key to search in. For example, 'name' for "filename". See the results
	 *                of WP_Filesystem_Direct::dirlist()
	 * @param  string $search The string to search for.
	 * @param  string $position Search method. Valid includes "contain" and "start".
	 * @return array
	 */
	public function filter_containing( $key, $search, $position = 'contain' ) {
		foreach ( $this->results as $item_key => $item ) {
			// We're only interested in files, not folders.
			if ( 'f' !== $item['type'] ) {
				unset( $this->results[ $item_key ] );
				continue;
			}

			// If this item is missing our key (such as "filename"), remove from the results.
			if ( ! isset( $item[ $key ] ) ) {
				unset( $this->results[ $item_key ] );
				continue;
			}

			switch ( $position ) {
				case 'start':
					// If our search word is not at the beginning of the string, remove from results.
					if ( ! substr( $item[ $key ], 0, strlen( $search ) ) === $search ) {
						unset( $this->results[ $item_key ] );
					}
					break;
				// By default, do 'contain'.
				case 'contain':
				default:
					// If our search word is not found, remove from results.
					if ( strpos( $item[ $key ], $search ) === false ) {
						unset( $this->results[ $item_key ] );
					}
					break;
			}
		}

		return $this->results;
	}
}
