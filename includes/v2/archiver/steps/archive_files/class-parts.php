<?php
/**
 * Parts class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver\Steps\Archive_Files;

/**
 * Class: Steps
 *
 * @since SINCEVERSION
 */
class Parts {
	/**
	 * Our parent archive_files class.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files
	 */
	private $archive_files;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files $archive_files Our parent archive_files class.
	 */
	public function __construct( $archive_files ) {
		$this->archive_files = $archive_files;
	}

	/**
	 * Determine our next part.
	 *
	 * @since SINCEVERSION
	 *
	 * @return \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Part
	 */
	public function get_next() {
		$parts = $this->archive_files->get_data_type( 'step' )->get_key( 'parts', array() );

		$part_number = 1;

		if ( ! empty( $parts ) ) {
			foreach ( $parts as $part ) {
				// If our part is complete, our next part will be one more.
				if ( ! empty( $part['complete_time'] ) ) {
					$part_number++;
				}
			}
		}

		return new \Boldgrid\Backup\V2\Archiver\Steps\Archive_Files\Part( $this->archive_files, $part_number );
	}
}
