<?php
/**
 * Archive class.
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
class Archive {
	/**
	 * IE /home/user/boldgrid_backup/boldgrid-backup-2cc84b67-8476bbfc0a3f4c5c
	 */
	private $dir;

	/**
	 *
	 * @var Boldgrid\Backup\V2\Archive\Dirlist
	 */
	private $dirlist;

	private $filename;

	private $folder;

	/**
	 * The id of this backup.
	 *
	 * IE the "53cb12ab25591dc9" in "boldgrid-backup-2cc84b67-53cb12ab25591dc9".
	 *
	 * @var string
	 */
	private $id;

	/**
	 *
	 */
	public function __construct() {
		$this->dirlist = new \Boldgrid\Backup\V2\Archive\Dirlist( $this );
	}

	/**
	 * The full path to the directory.
	 */
	public function get_dir() {
		return $this->dir;
	}

	/**
	 *
	 */
	public function get_dirlist() {
		return $this->dirlist;
	}

	/**
	 *
	 */
	public function get_file( $filename ) {
		return new \Boldgrid\Backup\V2\Archive\File( $this, $filename );
	}

	/**
	 *
	 */
	public function get_folder() {
		return $this->folder;
	}

	/**
	 *
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 *
	 */
	public function set_dir( $dir ) {
		$this->dir = $dir;

		$this->folder = pathinfo( $dir, PATHINFO_FILENAME );
	}

	/**
	 *
	 */
	public function set_filename( $filename ) {
		// Grab our id from the filename.
		preg_match( '/boldgrid-backup-(.+)-(.{16})/', $filename, $matches );
		if ( empty( $matches[2] ) ) {
			return false;
		}
		$this->id = $matches[2];

		$this->filename = $filename;
	}
}
