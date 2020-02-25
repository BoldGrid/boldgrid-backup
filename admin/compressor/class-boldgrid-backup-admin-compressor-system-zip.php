<?php
/**
 * File: class-boldgrid-backup-admin-compressor-system-zip.php
 *
 * System Zip Compressor.
 *
 * @link  https://www.boldgrid.com
 * @since 1.13.0
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
 * @since 1.13.0
 */
class Boldgrid_Backup_Admin_Compressor_System_Zip extends Boldgrid_Backup_Admin_Compressor {
	/**
	 * An array of files that should be archived.
	 *
	 * @since 1.13.0
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
	 * @since 1.13.0
	 * @access private
	 * @var string
	 */
	private $filelist_path;

	/**
	 * The filepath to the zip file.
	 *
	 * @since 1.13.0
	 * @access PRIVATE
	 * @var string
	 */
	private $filepath;

	/**
	 * The temporary folder used when saving a zip file.
	 *
	 * @since 1.13.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Compressor_System_Zip_Temp_Folder
	 */
	private $temp_folder;

	/**
	 * Key.
	 *
	 * @since 1.13.0
	 * @access protected
	 * @var string
	 */
	protected $key = 'system_zip';

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 */
	public function __construct() {
		$core = apply_filters( 'boldgrid_backup_get_core', null );
		parent::__construct( $core );

		$this->temp_folder = new Boldgrid_Backup_Admin_Compressor_System_Zip_Temp_Folder();
	}

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

		$this->filepath = $info['filepath'];

		$this->filelist_create();

		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 3 );

		$this->zip();

		$this->zip_sql();

		Boldgrid_Backup_Admin_In_Progress_Data::delete_arg( 'step' );

		// Actions to take when we're all done / cleanup.
		$this->core->wp_filesystem->delete( $this->filelist_path );

		return true;
	}

	/**
	 * Create the file containing a list of files to backup.
	 *
	 * @since 1.13.0
	 */
	private function filelist_create() {
		$this->core->logger->add( 'Starting to create list of files to include in zip.' );
		$this->core->logger->add_memory();

		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 2 );

		$this->filelist_path = $this->core->backup_dir->get_path_to( 'system_zip_filelist-' . time() . '.txt' );

		$total_size_archived = 0;

		// Create the file list.
		$filelist_array = [];
		foreach ( $this->filelist as $file ) {
			$filelist_array[] = str_replace( ABSPATH, '', $file[0] );

			$total_size_archived += empty( $file[2] ) ? 0 : $file[2];
		}

		// Add some values for "In progress".
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_files_done', count( $this->filelist ) );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived', $total_size_archived );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived_size_format', size_format( $total_size_archived, 2 ) );

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
	 * @since 1.13.0
	 */
	private function zip() {
		$this->core->logger->add( 'Starting to close the zip file.' );
		$this->core->logger->add_memory();

		$this->temp_folder->create();

		$this->core->execute_command( 'cd ' . ABSPATH . '; zip -b ' . $this->temp_folder->get_path() . ' ' . $this->filepath . ' -@ < ' . $this->filelist_path );

		$this->temp_folder->delete();

		$this->core->logger->add( 'Finished closing the zip file.' );
		$this->core->logger->add_memory();
	}

	/**
	 * Add the .sql file individually.
	 *
	 * @since 1.13.0
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
