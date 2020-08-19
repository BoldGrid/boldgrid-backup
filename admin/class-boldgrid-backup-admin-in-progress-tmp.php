<?php
/**
 * File: class-boldgrid-backup-admin-in-progress-tmp.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.13.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_In_Progress_Tmp
 *
 * When an archive is being created, it is first created in a temporary file, then finally moved to
 * the ending .zip file.
 *
 * This is a utility class used for getting information about these temporary files. They differ based
 * on the compressor that is being used.
 *
 * Examples of the different compressors' tmp files:
 * pcl_zip:    /backup_dir/backup.zip               Does not create a temporary file.
 * php_zip:    /backup_dir/backup.zip.Evubai
 * system_zip: /backup_dir/system_zip_temp/zigWlkvV The "system_zip_temp" folder is optional and
 *                                                  created by us.
 *
 * @since 1.13.0
 */
class Boldgrid_Backup_Admin_In_Progress_Tmp {
	/**
	 * An instance of core.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core = null ) {
		$this->core = ! empty( $core ) ? $core : apply_filters( 'boldgrid_backup_get_core', null );
	}

	/**
	 * Get an array of data for the temporary .zip file.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get() {
		$data = [];

		$compressor = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'compressor' );

		switch ( $compressor ) {
			case 'system_zip':
				$data = $this->get_system_zip();
				break;
			case 'php_zip':
				$data = $this->get_php_zip();
				break;
			case 'pcl_zip':
				$data = $this->get_pcl_zip();
				break;
		}

		return $data;
	}

	/**
	 * Get temporary .zip file info for system_zip compressor.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	private function get_system_zip() {
		$data = [];

		$filepath = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'filepath' );
		$filename = basename( $filepath );

		$dirlist = $this->core->backup_dir->dirlist_containing( $filename );

		if ( ! empty( $dirlist[ $filename ] ) ) {
			$data = [
				'size'        => $dirlist[ $filename ]['size'],
				'lastmodunix' => $dirlist[ $filename ]['lastmodunix'],
				'size_format' => size_format( $dirlist[ $filename ]['size'], 2 ),
			];
		}

		return $data;
	}

	/**
	 * Get data for pcl_zip.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	private function get_pcl_zip() {
		$data = [];

		$filepath = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'filepath' );
		$filename = basename( $filepath );

		$dirlist = $this->core->backup_dir->dirlist_containing( $filename );

		if ( ! empty( $dirlist[ $filename ] ) ) {
			$data = [
				'size'        => $dirlist[ $filename ]['size'],
				'lastmodunix' => $dirlist[ $filename ]['lastmodunix'],
				'size_format' => size_format( $dirlist[ $filename ]['size'], 2 ),
			];
		}

		return $data;
	}

	/**
	 * Get temporary .zip file info for system_zip compressor.
	 *
	 * This method originally lived in the Boldgrid_Backup_Admin_In_Progress class and handled getting
	 * data for the php_zip compressor. It was moved to this new class in order to more effecitvely
	 * account for system_zip and other compressors (in the future).
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function get_php_zip() {
		$data = [];

		$filepath = Boldgrid_Backup_Admin_In_Progress_Data::get_arg( 'filepath' );
		$filename = basename( $filepath );

		$dirlist = $this->core->backup_dir->dirlist_containing( $filename . '.' );

		// We're looping, but there should only be one item in the array.
		foreach ( $dirlist as $info ) {
			$data = [
				'size'        => $info['size'],
				'lastmodunix' => $info['lastmodunix'],
				'size_format' => size_format( $info['size'], 2 ),
			];

			break;
		}

		return $data;
	}
}
