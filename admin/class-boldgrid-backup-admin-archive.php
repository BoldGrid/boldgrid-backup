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
	 * Delete an archive file.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath Absolute path to a backup file.
	 * @return bool
	 */
	public function delete( $filepath ) {
		$deleted = $this->core->wp_filesystem->delete( $filepath, false, 'f' );

		$this->core->archive_log->delete_by_zip( $filepath );

		return $deleted;
	}

	/**
	 * Get an archive by name.
	 *
	 * Please see @return for more information on what an archive actually is.
	 *
	 * @since 1.5.4
	 *
	 * @param  string $filename
	 * @return array {
	 *     Details about an archive.
	 *
	 *     @type string $filepath    /home/user/boldgrid_backup/file.zip
	 *     @type string $filename    file.zip
	 *     @type string $filedate    1/2/2018 1:21 PM
	 *     @type int    $filesize    99152247
	 *     @type int    $lastmodunix 1514917311
	 *     @type int    $key         0
	 * }
	 */
	public function get_by_name( $filename ) {
		$return_archive = false;

		$archives = $this->core->get_archive_list();

		foreach( $archives as $key => $archive ) {
			if( $archive['filename'] === $filename ) {
				$archive['key'] = $key;
				$return_archive = $archive;
				break;
			}
		}

		return $return_archive;
	}

	/**
	 * Get one file from an archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $file      The file to get.
	 * @param  bool   $meta_only Whether to include the content of the file.
	 * @return array
	 */
	public function get_file( $file, $meta_only = false ) {
		if( empty( $this->filepath ) || ! $this->is_archive() ) {
			return false;
		}

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$file_contents = $zip->get_file( $this->filepath, $file );

		// If we only want the meta data, unset the content of the file.
		if( $meta_only && ! empty( $file_contents[0]['content'] ) ) {
			unset( $file_contents[0]['content'] );
		}

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
	 * Determine if a backup belongs to this site.
	 *
	 * This method takes into account a site's $backup_identifier and compares
	 * it to a backup's filename.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename
	 * @return bool
	 */
	public function is_site_archive( $filename ) {
		$backup_identifier = $this->core->get_backup_identifier();

		// End in zip.
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		if( 'zip' !== $extension ) {
			return false;
		}

		// Include the backup identifier.
		if( false === strpos( $filename, $backup_identifier ) ) {
			return false;
		}

		// Begin with 'boldgrid-backup-'.
		if( 0 !== strpos( $filename, 'boldgrid-backup-' ) ) {
			return false;
		}

		return true;
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
