<?php
/**
 * Archive Log class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Log Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Log {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Delete a log file.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $zip_filepath
	 * @return bool
	 */
	public function delete_by_zip( $zip_filepath ) {
		$log_filepath = $this->path_from_zip( $zip_filepath );

		$exists = $this->core->wp_filesystem->exists( $log_filepath );
		if( ! $exists ) {
			return true;
		}

		return $this->core->wp_filesystem->delete( $log_filepath );
	}

	/**
	 * Get the contents of a log file.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $zip_filepath
	 * @return array
	 */
	public function get_by_zip( $zip_filepath ) {
		$log_filepath = $this->path_from_zip( $zip_filepath );

		$exists = $this->core->wp_filesystem->exists( $log_filepath );
		if( ! $exists ) {
			return array();
		}

		$file_contents = $this->core->wp_filesystem->get_contents( $log_filepath );
		if( ! $file_contents ) {
			return array();
		}

		return json_decode( $file_contents, true );
	}

	/**
	 * Create the path to a log file based on a zip file.
	 *
	 * Pass in c:\abc.zip and get c:\abc.log
	 *
	 * @since 1.5.1
	 *
	 * @param  string $zip_filepath
	 * @return bool
	 */
	public function path_from_zip( $zip_filepath ) {
		return pathinfo( $zip_filepath, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . pathinfo( $zip_filepath, PATHINFO_FILENAME ) . '.log';
	}

	/**
	 * Write info file.
	 *
	 * @since 1.5.1
	 *
	 * @param  $info array
	 * @return bool
	 */
	public function write( $info ) {
		if( empty( $info['filepath'] ) ) {
			return false;
		}

		$log_filepath = $this->path_from_zip( $info['filepath'] );

		$touched = $this->core->wp_filesystem->touch( $log_filepath );
		if( ! $touched ) {
			return false;
		}

		$written = $this->core->wp_filesystem->put_contents( $log_filepath, json_encode( $info ) );
		if( ! $written ) {
			return false;
		}

		return true;
	}
}
