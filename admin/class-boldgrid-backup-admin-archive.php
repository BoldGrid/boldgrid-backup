<?php
/**
 * Archive class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Browser Class.
 *
 * @since 1.5.3
 */
class Boldgrid_Backup_Admin_Archive {

	/**
	 * The core class object.
	 *
	 * @since  1.5.3
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Full filepath to the archive.
	 *
	 * @since  1.5.3
	 * @access public
	 * @var    string
	 */
	public $filepath = null;

	/**
	 * Constructor.
	 *
	 * @since 1.5.3
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Get one file from an archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $file The file to get.
	 * @return array
	 */
	public function get_file( $file ) {
		if( empty( $this->filepath ) || ! $this->is_archive() ) {
			return false;
		}

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$file_contents = $zip->get_file( $this->filepath, $file );

		return $file_contents;
	}

	/**
	 * Determine if a zip file is in our archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath
	 * @return bool
	 */
	public function is_archive( $filepath = null ) {
		if( ! empty( $filepath ) ) {
			$this->set( $filepath );
		}

		if( is_null( $this->filepath ) ) {
			return false;
		}

		$archives = $this->core->get_archive_list();

		if( empty( $archives ) ) {
			return false;
		}

		foreach( $archives as $archive ) {
			if( $this->filepath === $archive['filepath'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set the filepath.
	 *
	 * @since 1.5.3
	 */
	public function set( $filepath ) {
		$this->filepath = $filepath;
	}
}
