<?php
/**
 * Archiver class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver;

/**
 * Class: Archiver
 *
 * @since SINCEVERSION
 */
class Archiver extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * Run the archive process.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$file_types = array(
			'plugins',
			// 'themes',
			// 'uploads',
			// 'other',
		);

		$steps = array(
			new \Boldgrid\Backup\V2\Archiver\Steps\Discovery( 'discovery', $this->get_dir() ),
			new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Database( 'archive_database', $this->get_dir() ),
		);

		foreach ( $file_types as  $type ) {
			$configs = array(
				'id'   => 'archive_' . $type,
				'type' => $type,
			);

			$steps[] = new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files( 'archive_' . $type, $this->get_dir(), $configs );
		}

		foreach ( $steps as $step ) {
			if ( $step->maybe_run() ) {
				$step->run();
			}

			// DEBUG.
			$contents = $step->get_contents();
			echo '<pre>contents = ' . print_r( $contents, 1 ) . '</pre>'; // phpcs:ignore
		}

		// DEBUG.
		$files = array();
		exec( 'ls -al ' . $this->get_dir(), $files ); // phpcs:ignore
		echo '<pre>$files = ' . print_r( $files, 1 ) . '</pre>'; // phpcs:ignore
	}
}
