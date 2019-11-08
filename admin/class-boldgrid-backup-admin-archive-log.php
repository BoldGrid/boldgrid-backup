<?php
/**
 * File: class-boldgrid-backup-admin-archive-log.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archive_Log
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
	 * @param  string $zip_filepath Archive file path.
	 * @return bool
	 */
	public function delete_by_zip( $zip_filepath ) {
		$log_filepath = $this->path_from_zip( $zip_filepath );

		$exists = $this->core->wp_filesystem->exists( $log_filepath );
		if ( ! $exists ) {
			return true;
		}

		return $this->core->wp_filesystem->delete( $log_filepath );
	}

	/**
	 * Get the contents of a log file.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $zip_filepath Archive file path.
	 * @return array
	 */
	public function get_by_zip( $zip_filepath ) {
		$log_filepath = $this->path_from_zip( $zip_filepath );

		$exists = $this->core->wp_filesystem->exists( $log_filepath );
		if ( ! $exists ) {
			return array();
		}

		$file_contents = $this->core->wp_filesystem->get_contents( $log_filepath );
		if ( ! $file_contents ) {
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
	 * @param  string $zip_filepath Archive file path.
	 * @return bool
	 */
	public function path_from_zip( $zip_filepath ) {
		return pathinfo( $zip_filepath, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . pathinfo( $zip_filepath, PATHINFO_FILENAME ) . '.log';
	}

	/**
	 * Take action after an archive has been restored.
	 *
	 * This method hooks into the boldgrid_backup_post_restore action.
	 *
	 * @since 1.6.0
	 *
	 * @param array $info Archive information.
	 */
	public function post_restore( $info ) {
		$path_backup_dir = $this->path_from_zip( $info['filepath'] );
		$path_abspath    = ABSPATH . basename( $path_backup_dir );

		// If this backup did not restore a log file to ABSPATH, then we can abort.
		if ( ! $this->core->wp_filesystem->exists( $path_abspath ) ) {
			return;
		}

		// Move the abspath log file to the backup dir.
		$this->core->wp_filesystem->move( $path_abspath, $path_backup_dir, true );

		// We don't need the log file in the ABSPATH, remove it.
		$this->core->wp_filesystem->delete( $path_abspath );
	}

	/**
	 * Restore a log file by a zip's filepath.
	 *
	 * For example, if we just downloaded backup.zip from FTP, this method will
	 * extract backup.log from backup.zip if it exists. This was, we have all
	 * of the meta data about the backup.
	 *
	 * @since 1.6.0
	 *
	 * @param string $filepath         Archive file path.
	 * @param string $alt_log_filename Optional log file path.  Default is to get from filepath.
	 */
	public function restore_by_zip( $filepath, $alt_log_filename = null ) {
		$log_filepath = $this->path_from_zip( $filepath );

		if ( $this->core->wp_filesystem->exists( $log_filepath ) ) {
			return true;
		}

		$log_filename = ! empty( $alt_log_filename ) ?
			$alt_log_filename : basename( $log_filepath );

		// Extract the log file to ABSPATH.
		$zip    = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
		$status = $zip->extract_one( $filepath, $log_filename );
		if ( ! $status ) {
			return false;
		}

		// Move the log file from the ABSPATH to the backup dir.
		$old_path = ABSPATH . $log_filename;
		$new_path = $this->core->backup_dir->get_path_to( basename( $log_filepath ) );

		return $this->core->wp_filesystem->move( $old_path, $new_path, true );
	}

	/**
	 * Write info file.
	 *
	 * @since 1.5.1
	 *
	 * @see Boldgrid_Backup_Admin_Archive::delete_from_archive()
	 *
	 * @param  array $info Archive information.
	 * @return bool
	 */
	public function write( $info ) {
		if ( empty( $info['filepath'] ) ) {
			return false;
		}

		$log_filepath = $this->path_from_zip( $info['filepath'] );

		$touched = $this->core->wp_filesystem->touch( $log_filepath );
		if ( ! $touched ) {
			return false;
		}

		$written = $this->core->wp_filesystem->put_contents( $log_filepath, wp_json_encode( $info ) );
		if ( ! $written ) {
			return false;
		}

		// Add the log file to the archive file, as of 1.5.4.
		if ( ! class_exists( 'PclZip' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		}

		// Open the archive.
		$archive = new \PclZip( $info['filepath'] );
		if ( 0 === $archive ) {
			return false;
		}

		// Delete the old log file(s).
		$this->core->archive->delete_from_archive( $archive, basename( $log_filepath ) );

		/*
		 * The log file is being added to the root of the archive. If the user
		 * restores the archive, the log will be restored to the ABSPATH. The
		 * $this->post_restore() method will move the log file to the backup
		 * directory and delete it from the ABSPATH.
		 */
		$status = $archive->add( $log_filepath, PCLZIP_OPT_REMOVE_ALL_PATH );
		if ( 0 === $status ) {
			return false;
		}

		// Ensure the act updating the log file does not change the backup file's timestamp.
		$this->core->wp_filesystem->touch( $info['filepath'], $info['lastmodunix'] );

		return true;
	}
}
