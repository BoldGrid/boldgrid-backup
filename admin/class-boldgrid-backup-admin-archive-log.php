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
	 * Restore a log file by a zip's filepath.
	 *
	 * For example, if we just downloaded backup.zip from FTP, this method will
	 * extract backup.log from backup.zip if it exists. This was, we have all
	 * of the meta data about the backup.
	 *
	 * @since 1.5.4
	 *
	 * @param string $filepath
	 */
	public function restore_by_zip( $filepath ) {
		$log_filepath = $this->path_from_zip( $filepath );
		$log_filename = basename( $log_filepath );

		if( $this->core->wp_filesystem->exists( $log_filepath ) ) {
			return true;
		}

		// Extract the log file to ABSPATH.
		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
		$status = $zip->extract_one( $filepath, $log_filename );
		if( ! $status ) {
			return false;
		}

		// Move the log file from the ABSPATH to the backup dir.
		$old_path = ABSPATH . $log_filename;
		$new_path = $this->core->backup_dir->get_path_to( $log_filename );
		return $this->core->wp_filesystem->move( $old_path, $new_path );
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

		// Add the log file to the archive file, as of 1.5.4.
		$archive = new PclZip( $info['filepath'] );
		if ( 0 === $archive ) {
			return false;
		}
		$status = $archive->add( $log_filepath, PCLZIP_OPT_REMOVE_ALL_PATH );
		if( 0 === $status ) {
			return false;
		}

		return true;
	}
}
