<?php
/**
 * Fetch class.
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
 * Class: Fetch.
 *
 * @since SINCEVERSION
 */
class Fetch extends \Boldgrid\Backup\V2\Step\Step {
	private $url;

	private $backup_filename;

	private $backup_size;



	/**
	 * Run the fetching process..
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$backup_folder = $this->info->get_key( 'backup_folder' );
		$backup_dir    = $this->core->backup_dir->get_path_to( $backup_folder );
		$destination   = trailingslashit( $backup_dir ) . $this->backup_filename;

		$this->log( 'Downloading ' . $this->backup_filename );

		$time_start = microtime( true );

		$success = \Boldgrid\Backup\Utility\Remote::save_file( $this->url, $destination );

		$duration = microtime( true ) - $time_start;

		if ( $success ) {
			$size = $this->core->wp_filesystem->size( $destination );
			$rate = $size / $duration;
			$this->log( size_format( $size, 2 ) . ' downloaded at ' . size_format( $rate, 2 ) . '/s' );

			$this->complete();

			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public function set_backup_filename( $filename ) {
		$this->backup_filename = $filename;
	}

	/**
	 *
	 */
	public function set_backup_size( $size ) {
		$this->backup_size = $size;
	}

	/**
	 *
	 */
	public function set_url( $url ) {
		$this->url = $url;
	}
}
