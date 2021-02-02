<?php
/**
 * File class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archive;

/**
 * Class: Archive
 *
 * @since SINCEVERSION
 */
class File {
	/**
	 *
	 * @var Boldgrid\Backup\V2\Archive\Archive
	 */
	private $archive;

	private $core;

	private $data;

	private $filename;

	/**
	 *
	 */
	public function __construct( $archive, $filename ) {
		$this->core     = apply_filters( 'boldgrid_backup_get_core', null );
		$this->archive  = $archive;
		$this->filename = $filename;

		$data       = $this->archive->get_dirlist()->get_by_key( 'name', $filename );
		$this->data = $data[0];
	}

	/**
	 *
	 */
	public function send() {
		\Boldgrid_Backup_File::send_file( $this->data['path'], $this->data['size'] );
	}
}
