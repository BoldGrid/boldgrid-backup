<?php
/**
 * File: class-boldgrid-backup-admin-compressor-system-zip.php
 *
 * System Zip Compressor.
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/compressor
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Compressor_System_Zip
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Compressor_System_Zip extends Boldgrid_Backup_Admin_Compressor {
	/**
	 * An array of files that should be archived.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array {
	 *     An array of files.
	 *
	 *     @type string 0 Path.  Example: ""/home/user/public_html/readme.html".
	 *     @type string 1 basename.  Example: "readme.html".
	 *     @type int    2 File size (in bytes). Example: "7413".
	 *     @type string 3 File type. Examples: "d", "f".
	 * }
	 */
	private $filelist = [];

	/**
	 * The filepath to our text file holding list of files to archive.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $filelist_path;

	/**
	 * The filepath to the zip file.
	 *
	 * @since SINCEVERSION
	 * @access PRIVATE
	 * @var string
	 */
	private $filepath;

	/**
	 * Archive files.
	 *
	 * @since 1.5.1
	 *
	 * @see Boldgrid_Backup_Admin_Filelist::get_total_size()
	 *
	 * @param array $filelist {
	 *     File details.
	 *
	 *     @type string 0 Path.  Example: ""/home/user/public_html/readme.html".
	 *     @type string 1 basename.  Example: "readme.html".
	 *     @type int    2 File size (in bytes). Example: "7413".
	 *     @type string 3 File type. Examples: "d", "f".
	 * }
	 * @param array $info {
	 *     Data about the backup archive we are generating.
	 *
	 *     @type string mode       backup
	 *     @type bool   dryrun
	 *     @type string compressor php_zip
	 *     @type int    filesize   0
	 *     @type bool   save       1
	 *     @type int    total_size 0
	 * }
	 */
	public function archive_files( $filelist, &$info ) {
		if ( $info['dryrun'] ) {
			return true;
		}

		// Prevent this process from ending; allow the archive to be completed.
		ignore_user_abort( true );
		set_time_limit( 0 );

		$this->filelist = $filelist;

		// Set the filepath to the .zip file.
		$this->filepath   = $this->core->generate_archive_path( 'zip' );
		$info['filepath'] = $this->filepath;

		$this->filelist_create();

		$this->zip();

		$this->zip_sql();

		// Actions to take when we're all done / cleanup.
		$this->core->wp_filesystem->delete( $this->filelist_path );

		return true;
	}

	/**
	 * Create the file containing a list of files to backup.
	 *
	 * @since SINCEVERSION
	 */
	private function filelist_create() {
		$this->core->logger->add( 'Starting to create list of files to include in zip.' );
		$this->core->logger->add_memory();

		$this->filelist_path = $this->core->backup_dir->get_path_to( 'system_zip_filelist-' . time() . '.txt' );

		// Create the file list.
		$filelist_array = [];
		foreach ( $this->filelist as $file ) {
			$filelist_array[] = str_replace( ABSPATH, '', $file[0] );
		}

		/*
		 * Remove our db_dump_filepath from the list.
		 *
		 * If we add it now, the zip file will include /home/user/boldgrid_backup/db.sql
		 *
		 * We'll add the sql separately in self::zip_sql().
		 */
		if ( ( $key = array_search( $this->core->db_dump_filepath, $filelist_array ) ) !== false ) { // phpcs:ignore
			unset( $filelist_array[ $key ] );
		}

		$this->core->wp_filesystem->put_contents(
			$this->filelist_path,
			implode( PHP_EOL, $filelist_array )
		);

		$this->core->logger->add( 'Finished creating list of files to include in zip.' );
		$this->core->logger->add_memory();
	}

	/**
	 * Run the command to actually zip the files.
	 *
	 * @since SINCEVERSION
	 */
	private function zip() {
		$this->core->logger->add( 'Starting to close the zip file.' );
		$this->core->logger->add_memory();

		$this->core->execute_command( 'cd ' . ABSPATH . '; zip ' . $this->filepath . ' -@ < ' . $this->filelist_path );

		$this->core->logger->add( 'Finished closing the zip file.' );
		$this->core->logger->add_memory();
	}

	/**
	 * Add the .sql file individually.
	 *
	 * @since SINCEVERSION
	 *
	 * @see self::filelist_create
	 */
	private function zip_sql() {
		$this->core->logger->add( 'Starting to add db dump to the zip file.' );
		$this->core->logger->add_memory();

		$dir = pathinfo( $this->core->db_dump_filepath, PATHINFO_DIRNAME );

		$this->core->execute_command( 'cd ' . $dir . '; zip ' . $this->filepath . ' ' . basename( $this->core->db_dump_filepath ) . ';' );

		$this->core->logger->add( 'Finished adding db dump to the zip file.' );
		$this->core->logger->add_memory();
	}
}
