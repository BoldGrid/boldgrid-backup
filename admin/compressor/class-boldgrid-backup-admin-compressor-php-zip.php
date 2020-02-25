<?php
/**
 * File: class-boldgrid-backup-admin-compressor-php-zip.php
 *
 * PHP Zip Compressor.
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/compressor
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Compressor_Php_Zip
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressor_Php_Zip extends Boldgrid_Backup_Admin_Compressor {
	/**
	 * An array of directories we've added to the zip.
	 *
	 * @since 1.6.0
	 * @access public
	 * @var    array
	 */
	public $dirs = array();

	/**
	 * The status of our test result.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    mixed|bool|null
	 */
	public static $test_result = null;

	/**
	 * An array of any errors received while testing.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    array
	 */
	public $test_errors = array();

	/**
	 * An instance of ZipArchive.
	 *
	 * @since 1.5.1
	 * @var   ZipArchive
	 */
	public $zip;

	/**
	 * Key.
	 *
	 * @since 1.13.0
	 * @access protected
	 * @var string
	 */
	protected $key = 'php_zip';

	/**
	 * Add a file's directories to the zip.
	 *
	 * When you add a file, the parent directories are not always explicitly
	 * created. For example, if you add wp-content/themes/pavilion/index.php the
	 * wp-content directory (and so forth) is not explicity added to the zip.
	 *
	 * @since 1.6.0
	 *
	 * @param string $file A file path.
	 */
	public function add_dir( $file ) {
		$add_directory = '';
		$dirs          = explode( DIRECTORY_SEPARATOR, dirname( $file ) );

		foreach ( $dirs as $key => $dir ) {
			if ( 0 === $key ) {
				$add_directory = $dir;
			} else {
				$add_directory .= '/' . $dir;
			}

			if ( ! in_array( $add_directory, $this->dirs, true ) ) {
				$this->zip->addEmptyDir( $add_directory );
				$this->dirs[] = $add_directory;
			}
		}
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
		// Init vars used for our "in progress" bar.
		$number_files_archived = 0;
		$total_size_archived   = 0;
		$number_files_todo     = count( $filelist );
		$last_x_files          = array();

		if ( $info['dryrun'] ) {
			return true;
		}

		// Prevent this process from ending; allow the archive to be completed.
		ignore_user_abort( true );
		set_time_limit( 0 );

		$this->zip = new ZipArchive();

		$status = $this->zip->open( $info['filepath'], ZipArchive::CREATE );

		if ( ! $status ) {
			return array(
				'error'         => 'Cannot open ZIP archive file "' . $info['filepath'] . '".',
				'error_code'    => $status,
				'error_message' => Boldgrid_Backup_Admin_Utility::translate_zip_error( $status ),
			);
		}

		$has_setexternalattributesname = method_exists( $this->zip, 'setExternalAttributesName' );

		foreach ( $filelist as $fileinfo ) {
			$is_dir = ! empty( $fileinfo[3] ) && 'd' === $fileinfo[3];

			switch ( $is_dir ) {
				case true:
					$this->zip->addEmptyDir( $fileinfo[1] );
					break;
				case false:
					if ( ! is_readable( $fileinfo[0] ) ) {
						$info['backup_errors'][] = sprintf(
							// translators: 1 The path to a file that was unable to be added to the backup.
							__( 'Permission defined. Unable to add the following file to your backup: %1$s', 'boldgrid-backup' ),
							$fileinfo[0]
						);
						continue 2;
					} else {
						$this->zip->addFile( $fileinfo[0], $fileinfo[1] );
						$this->add_dir( $fileinfo[1] );
					}
					break;
			}

			/*
			 * ZipArchive::setExternalAttributesName() is avaiable in PHP >= 5.6.
			 * If available, use the method to set the permissions for items in the ZIP archive.
			 * By default, items in a ZIP file are extracted with loose (777/666) modes, which can
			 * cause issues on Linux systems using suEXEX/suPHP or other mechanisms requiring
			 * tighter (755/644; world non-writable permissions) modes on directories and files.
			 */
			if ( $has_setexternalattributesname ) {
				$this->zip->setExternalAttributesName(
					$is_dir ? $fileinfo[1] . '/' : $fileinfo[1],
					ZipArchive::OPSYS_UNIX,
					fileperms( $fileinfo[0] ) << 16
				);
			}

			$number_files_archived++;
			$total_size_archived += empty( $fileinfo[2] ) ? 0 : $fileinfo[2];

			/*
			 * If applicable, add this file to the list of files archived that we show the user.
			 *
			 * To give the user a more broad sense of the files being added, our list only contains
			 * every 20th file.
			 *
			 * Our list is only 5 long because we make hook into the heartbeat every 5 seconds to grab
			 * the last 5 files, and we display each file for 1 second.
			 */
			if ( 0 === $number_files_archived % 20 ) {
				$last_x_files[] = $fileinfo[1];
				if ( count( $last_x_files ) > 5 ) {
					array_shift( $last_x_files );
				}
			}

			/*
			 * Update our "in progress" data.
			 *
			 * To prevent excessive calls to update options, we only update our in progress data every
			 * 100 files.
			 */
			$all_files_archived = $number_files_archived >= $number_files_todo;
			if ( 0 === $number_files_archived % 100 || $all_files_archived ) {
				Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_files_done', $number_files_archived );
				Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'last_files', $last_x_files );
				Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived', $total_size_archived );
				Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'total_size_archived_size_format', size_format( $total_size_archived, 2 ) );
				Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 2 );
			}
		}

		$this->core->logger->add(
			sprintf(
				'Total files / size archived: %1$s / %2$s (%3$s)',
				$number_files_archived,
				$total_size_archived,
				size_format( $total_size_archived, 2 )
			)
		);

		/*
		 * We're done archiving all files.
		 *
		 * Empty out the "last files archived" data, and set an appropriate status.
		 */
		Boldgrid_Backup_Admin_In_Progress_Data::delete_arg( 'last_files' );
		Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 3 );

		/*
		 * Verify files before write/close.  Delete any invalid file indicies in the ZIP index.
		 *
		 * In some scenarios, a file will be added above, but then deleted before the zip->close()
		 * call below. For example, while a backup is in progress, users may be editing pages and
		 * on save, cache files may get deleted. If the cache file was added to the zip above, and then
		 * deleted before the zip->close() below, we're going to have a problem.
		 *
		 * There is one file path outside of ABSPATH; the database dump file.
		 * The full path of each file is determined by checking if it is the dump file or not.
		 *
		 * @todo The user is not notified if a file is removed below.
		 */
		for ( $i = 0; $i < $this->zip->numFiles; $i++ ) {
			$filename = $this->zip->getNameIndex( $i );
			$filepath = false !== strpos( $this->core->db_dump_filepath, $filename ) ?
				$this->core->db_dump_filepath : ABSPATH . $filename;

			if ( ! $this->core->wp_filesystem->is_readable( $filepath ) ) {
				$this->zip->deleteIndex( $i );
			}
		}

		$this->core->logger->add( 'Starting to close the zip file.' );
		$this->core->logger->add_memory();

		$close = $this->zip->close();

		$this->core->logger->add( 'Finished closing the zip file.' );
		$this->core->logger->add_memory();

		Boldgrid_Backup_Admin_In_Progress_Data::delete_arg( 'step' );

		if ( ! $close ) {
			return array(
				'error' => 'Cannot close ZIP archive file "' . $info['filepath'] . '".',
			);
		}

		return true;
	}

	/**
	 * Determine if ZipArchive is available.
	 *
	 * @since 1.5.2
	 */
	public static function is_extension_available() {
		return extension_loaded( 'zip' ) && class_exists( 'ZipArchive' );
	}

	/**
	 * Test the functionality of php_zip.
	 *
	 * @since 1.5
	 *
	 * @return bool
	 */
	public function test() {
		if ( null !== self::$test_result ) {
			return self::$test_result;
		}

		$backup_dir = $this->core->backup_dir->get();

		$test_file_contents = sprintf(
			// translators: 1: Plugin title.
			__( 'This is a test file from %1$s. You can delete this file.', 'boldgrid-backup' ),
			BOLDGRID_BACKUP_TITLE
		);

		// translators: 1: A filename.
		$cannot_open_zip = __( 'Unable to create zip file: %1$s', 'boldgrid-backup' );

		// translators: 1: Backup directory path.
		$cannot_close_zip = __( 'When testing ZipArchive functionality, we are able to create a zip file and add files to it, but we were unable to close the zip file.<br /><strong>Please be sure the following backup directory has modify permissions</strong>:<br />%1$s', 'boldgrid-backup' );
		$safe_to_delete   = __( 'safe-to-delete', 'boldgrid-backup' );
		$test_zip_file    = $this->core->test->test_prefix . '-zip';
		$test_filename    = sprintf( '%1$s%5$s%2$s-%3$s-%4$s', $backup_dir, $test_zip_file, mt_rand(), $safe_to_delete, DIRECTORY_SEPARATOR );
		$zip_filepath     = $test_filename . '.zip';
		$random_filename  = $test_filename . '.txt';

		$zip    = new ZipArchive();
		$status = $zip->open( $zip_filepath, ZipArchive::CREATE );

		if ( ! $status ) {
			$this->test_errors[] = sprintf( $cannot_open_zip, $zip_filepath );
			self::$test_result   = false;

			return false;
		}

		$this->core->wp_filesystem->touch( $random_filename );
		$this->core->wp_filesystem->put_contents( $random_filename, $test_file_contents );

		$zip->addFile( $random_filename, 'test.txt' );

		$zip_closed = @$zip->close(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

		$this->core->test->delete_test_files( $backup_dir );

		if ( ! $zip_closed ) {
			$this->test_errors[] = sprintf( $cannot_close_zip, $backup_dir );
			self::$test_result   = false;

			return false;
		}

		self::$test_result = true;

		return true;
	}
}
