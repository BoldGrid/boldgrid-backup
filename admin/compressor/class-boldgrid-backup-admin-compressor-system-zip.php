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
	 * An error message.
	 *
	 * If we encounter an error while zipping, the error may be placed here - key phrase "may be placed
	 * here". At the introduction of this class property, it is being used in only one place. The entire
	 * class was not checked and tested to ensure all methods add any errors they run into here.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $error;

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
	 * Total Size Archived.
	 *
	 * @since 1.14.0
	 * @var int
	 */
	public $total_size_archived;

	/**
	 * Default Compression Level.
	 *
	 * @since 1.14.0
	 * @var string
	 */
	public $default_compression_level = '6';

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
	 * @return mixed True on success, an array on failure. This approach has been taken to follow the
	 *               standards already set by the pcl-zip and php-zip classes.
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

		$zip_success = $this->zip();

		// @todo Simliar to the zip call above which returns a success status, so should zip_sql.
		$this->zip_sql();

		Boldgrid_Backup_Admin_In_Progress_Data::delete_arg( 'step' );

		// Actions to take when we're all done / cleanup.
		$this->core->wp_filesystem->delete( $this->filelist_path );

		return true === $zip_success ? true : array(
			'error' => $this->error,
		);
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

		$this->total_size_archived = 0;

		// Create the file list.
		$filelist_array = [];
		foreach ( $this->filelist as $file ) {
			$filelist_array[] = str_replace( ABSPATH, '', $file[0] );

			$this->total_size_archived += empty( $file[2] ) ? 0 : $file[2];
		}

		// Add some values for "In progress".
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_files_done', count( $this->filelist ) );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived', $this->total_size_archived );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived_size_format', size_format( $this->total_size_archived, 2 ) );

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

		$this->core->logger->add( 'Finished creating list of files to include in zip. ' . count( $filelist_array ) . ' files in zip.' );
		$this->core->logger->add_memory();
	}

	/**
	 * Run the command to actually zip the files.
	 *
	 * @since 1.13.0
	 *
	 * @return bool True on success. Do note that $this->close() calls $this->zip_proc(), which will
	 *                               store any error messages in $this->error.
	 */
	private function zip() {
		$this->core->logger->add( 'Starting to close the zip file.' );
		$this->core->logger->add_memory();

		$this->temp_folder->create();

		$success = $this->close();

		$this->temp_folder->delete();

		$this->core->logger->add( 'Finished closing the zip file.' );
		$this->core->logger->add_memory();

		return $success;
	}

	/**
	 * Get Filelist Chunks.
	 *
	 * @since 1.14.0
	 *
	 * @return array Example https://pastebin.com/JsSEzNwA
	 */
	public function get_filelist_chunks() {
		// Chunk size in bytes.
		$max_chunk_size  = 26214400;
		$size_of_chunk   = 0;
		$filelist_chunks = array( array() );
		$chunk_position  = 0;
		foreach ( $this->filelist as $file ) {
			// Adds file to this chunk.
			$filelist_chunks[ $chunk_position ][] = $file[1];

			// Adds the most recent file's size to chunk size.
			$size_of_chunk = $size_of_chunk + (int) $file[2];

			// If the chunk size is >= the max chunk size, then move to next chunk.
			if ( $size_of_chunk >= $max_chunk_size ) {
				$chunk_position++;
				$size_of_chunk = 0;
			}
		}

		return $filelist_chunks;
	}

	/**
	 * Close.
	 *
	 * @since 1.14.0
	 */
	private function close() {
		$chunks_closed   = 0;
		$filelist_chunks = $this->get_filelist_chunks();
		$total_chunks    = count( $filelist_chunks );

		foreach ( $filelist_chunks as $filelist_chunk ) {
			$chunk_start_time = microtime( true );

			$success = $this->zip_proc( $filelist_chunk );
			if ( ! $success ) {
				return false;
			}

			// Process some stats.
			$chunks_closed++;
			$percent_complete = round( $chunks_closed / $total_chunks, 2 );
			$chunk_end_time   = microtime( true );
			$close_duration   = $chunk_end_time - $chunk_start_time;

			Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'percent_closed', $percent_complete );

			// Add messages to the log.
			$this->core->logger->add(
				'Chunk closed in ' .
				$close_duration .
				' seconds. ' . $percent_complete * 100 .
				'% complete closing'
			);
			$this->core->logger->add_memory();
		}

		return true;
	}

	/**
	 * Get Compression Level.
	 *
	 * @since 1.14.0
	 *
	 * @return string
	 */
	private function get_compression_level() {
		$compression_level = $this->core->settings->get_setting( 'compression_level' );
		return isset( $compression_level ) ? $compression_level : $this->default_compression_level;
	}

	/**
	 * Close Zip using proc_open.
	 *
	 * @since 1.14.0
	 *
	 * @param array $filelist_chunk Array of Files to be added.
	 * @return bool True on success.
	 */
	private function zip_proc( $filelist_chunk ) {
		$has_error = false;

		$descriptorspec = array(
			0 => array( 'pipe', 'r' ),  // stdin is a pipe that the child will read from.
			1 => array( 'pipe', 'w' ),  // stdout is a pipe that the child will write to.
			/**
			 * Initially we sent errors to /tmp/error-output.txt. This caused warnings when the file
			 * was not writable. For any error messages, see $pipes[2] further down this method.
			 */
			2 => array( 'pipe', 'w' ),
		);

		$cwd = ABSPATH;

		$compression_level = $this->get_compression_level();

		$process = proc_open( //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open
			'zip -' . $compression_level . ' -g -q -@ ' . $this->filepath,
			$descriptorspec,
			$pipes,
			$cwd
		);

		if ( is_resource( $process ) ) {
			foreach ( $filelist_chunk as $file ) {
				fwrite( $pipes[0], $file . "\n" ); //phpcs:ignore WordPress.WP.AlternativeFunctions
			}

			fclose( $pipes[0] ); //phpcs:ignore WordPress.WP.AlternativeFunctions

			fclose( $pipes[1] ); //phpcs:ignore WordPress.WP.AlternativeFunctions

			// Check for any errors.
			$stderr = stream_get_contents( $pipes[2] );
			fclose( $pipes[2] ); //phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! empty( $stderr ) ) {
				$this->error = $stderr;
				$this->core->logger->add( 'Error zipping files with system zip: ' . $stderr );
				$has_error = true;
			}

			// It is important that you close any pipes before calling.
			// proc_close in order to avoid a deadlock.
			proc_close( $process );
		} else {
			$has_error = true;
		}

		return ! $has_error;
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

		$compression_level = $this->get_compression_level();

		$this->core->execute_command( 'cd ' . $dir . '; zip -' . $compression_level . ' -g -q ' . $this->filepath . ' ' . basename( $this->core->db_dump_filepath ) . ';' );

		$this->core->logger->add( 'Finished adding db dump to the zip file.' );
		$this->core->logger->add_memory();
	}
}
