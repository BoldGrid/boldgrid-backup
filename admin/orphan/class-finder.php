<?php
/**
 * Orphan Finder class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.8
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Orphan;

/**
 * Class: Finder
 *
 * @since 1.13.8
 */
class Finder {
	/**
	 * An instance of Boldgrid_Backup_Admin_Core.
	 *
	 * @since 1.13.8
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of all orphaned files.
	 *
	 * Example: https://pastebin.com/T36y5PXb
	 *
	 * @since 1.13.8
	 * @access private
	 * @var array
	 */
	private $filelist = array();

	/**
	 * Constructor.
	 *
	 * @since 1.13.8
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
	}

	/**
	 * Determine whether or not a file is considered old enough to be an orphan.
	 *
	 * For example, we may be in the middle of creating a backup with system zip. Just because a file
	 * exists in /home/user/boldgrid_backup/system_zip_temp, doesn't mean it's an orphan. It could be
	 * the actual temp file for the zip in progress.
	 *
	 * @since 1.13.8
	 *
	 * @param  array $file An array of file information as received from a WP_Filesystm::dirlist call.
	 * @return bool
	 */
	public function is_file_old( array $file ) {
		// Any possible orphan over 2 hours old will be considered an orphan.
		$threshold = 60 * 60 * 2;

		return 'f' === $file['type'] && ( time() - $file['lastmodunix'] > $threshold );
	}

	/**
	 * Get and return a list of orphaned files.
	 *
	 * These files are considered safe to delete.
	 *
	 * @since 1.13.8
	 *
	 * @return array
	 */
	public function run() {
		$this->set_filelist();

		return $this->filelist;
	}

	/**
	 * Set our filelist, our array or orphaned files.
	 *
	 * @since 1.13.8
	 */
	public function set_filelist() {
		// Get orphaned files in the root of the backup directory.
		$zip_with_extension = $this->core->backup_dir->dirlist_containing( '.zip.' );
		$sqls               = $this->core->backup_dir->dirlist_containing( '.sql', 'end' );
		$files              = array_merge( $zip_with_extension, $sqls );
		foreach ( $files as $file ) {
			if ( $this->is_file_old( $file ) ) {
				$full_path                    = $this->core->backup_dir->get_path_to( $file['name'] );
				$this->filelist[ $full_path ] = $file;
			}
		}

		// Get orphaned files in the system zip temp folder.
		$system_zip_temp = new \Boldgrid_Backup_Admin_Compressor_System_Zip_Temp_Folder();
		foreach ( $system_zip_temp->dirlist() as $filepath => $file ) {
			if ( $this->is_file_old( $file ) ) {
				$this->filelist[ $filepath ] = $file;
			}
		}
	}
}
