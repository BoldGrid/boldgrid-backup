<?php
/**
 * Pcl Zip Compressor.
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
 * BoldGrid Backup Admin Compressor Pclzip Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Compressor_Pcl_Zip extends Boldgrid_Backup_Admin_Compressor {

	/**
	 * An array of errors.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $errors = array();

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
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		if( ! class_exists( 'PclZip' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/class-pclzip.php' );
		}

		parent::__construct( $core );
	}

	/**
	 * Add empty directories to current directory we're browsing.
	 *
	 * This method is used by this->browse, and it's whole reason for existence
	 * is because ZipArchive gives you the ability to simply add a directory to
	 * the archive, but PclZip does not.
	 *
	 * If PclZip archive only includes one file, such as
	 * wp-content/plugins/boldgrid/index.php, we need to "artificially" create
	 * these directories for the zip browser:
	 * # wp-content/
	 * # wp-content/plugins/
	 * # wp-content/plugins/boldgrid/
	 *
	 * @since 1.5.4
	 *
	 * @param  array  $list
	 * @param  array  $contents
	 * @param  array  $filenames
	 * @param  string $in_dir
	 * @return array  An updated $contents.
	 */
	public function browse_add_dirs( $list, $contents, $filenames, $in_dir ) {
		foreach( $list as $key => $file ) {

			// These variables are very similar, both exist for readability.
			$top_dir = null;
			$next_dir = null;

			if( '.' === $in_dir ) {
				$top_dir = explode( '/', $file['filename'] );
				$top_dir = $top_dir[0];

				if( empty( $top_dir ) || in_array( $top_dir, $filenames ) ) {
					continue;
				}
			} else {

				/*
				 * Determine if file is in directory.
				 *
				 * For example, We want to know if file wp-admin/user/about is
				 * in wp-content/
				 */
				$file_in_dir = 0 === strpos( $file['filename'], $in_dir . '/' );
				if( ! $file_in_dir ) {
					continue;
				}

				/*
				 * Calcular our next directory.
				 *
				 * If we're looking for all folders within wp-content/plugins
				 * and we're given a filename of
				 * wp-content/plugins/boldgrid/index.php, then we can say that
				 * wp-content/plugins/boldgrid exists in wp-content/plugins.
				 */
				$next_dir = str_replace( $in_dir . '/', '', $file['filename'] );
				$next_dir = explode( '/', $next_dir );
				$next_dir = $in_dir . '/' . $next_dir[0];

				if( $next_dir === $file['filename'] || in_array( $next_dir, $filenames ) ) {
					continue;
				}
			}

			$dir = ! empty( $top_dir ) ? $top_dir : $next_dir;
			$sudo_file = array(
				'filename' => $dir,
				'folder' => true,
			);
			$contents[] = $sudo_file;
			$filenames[] = $dir;
		}

		return $contents;
	}

	/**
	 * Archive files.
	 *
	 * @since 1.5.1
	 *
	 * @param array $filelist See Boldgrid_Backup_Admin_Filelist::get_total_size
	 * @param array $info {
	 *     An array of data about the backup archive we are generating.
	 *
	 *     @type string mode       backup
	 *     @type bool   dryrun
	 *     @type string compressor php_zip
	 *     @type ing    filesize   0
	 *     @type bool   save       1
	 *     @type int    total_size 0
	 * }
	 */
	public function archive_files( $filelist, &$info ) {
		$info['filepath'] = $this->core->generate_archive_path( 'zip' );

		if( $info['dryrun'] ) {
			return true;
		}

		$cwd = $this->wp_filesystem->cwd();

		$archive = new PclZip( $info['filepath'] );
		if ( 0 === $archive ) {
			return array(
				'error' => sprintf( 'Cannot create ZIP archive file %1$s. %2$s.', $info['filepath'], $archive->errorInfo() ),
			);
		}

		/*
		 * Create our $new_filelist.
		 *
		 * We can pass $archive->add() an array of files to archive. $filelist
		 * is an array of arrays, so we need to convert to simply an array of
		 * strings (filenames to archive).
		 */
		$new_filelist = array();
		foreach( $filelist as $key => $file ) {

			// Don't add the database dump at this time, it will be added later.
			if( ! empty( $this->core->db_dump_filepath ) && $file[0] === $this->core->db_dump_filepath ) {
				continue;
			}

			$new_filelist[] =  $file[0];
		}

		$status = $archive->add( $new_filelist,
			PCLZIP_OPT_REMOVE_PATH, ABSPATH
		);

		if( 0 === $status ) {
			$error_info = $archive->errorInfo();

			$custom_error = $this->parse_error_info( $error_info );

			if( false === $custom_error ) {
				return array(
					'error' => sprintf( 'Cannot add files to ZIP archive file: %1$s', $archive->errorInfo() ),
				);
			} else {
				return array(
					'error' => $custom_error,
				);
			}
		}

		/*
		 * Add our database dump to the zip.
		 *
		 * The check for ! empty is here because the user may have opted not
		 * to backup their database.
		 */
		if( ! empty( $this->core->db_dump_filepath ) ) {
			$status = $archive->add( $this->core->db_dump_filepath, PCLZIP_OPT_REMOVE_ALL_PATH );
			if( 0 === $status ) {
				return array(
						'error' => sprintf( 'Cannot add database dump to ZIP archive file: %1$s', $archive->errorInfo() ),
				);
			}
		}

		$this->wp_filesystem->chdir( $cwd );

		return true;
	}

	/**
	 * Get the contents of a zip file.
	 *
	 * @param  string  $filepath
	 * @param  string  $in_dir
	 * @return array
	 */
	public function browse( $filepath, $in_dir = '.' ) {
		$in_dir = untrailingslashit( $in_dir );

		/*
		 * Keep track of the contents of the directory we're trying to browse.
		 *
		 * This variable is different than the below $filenames variable because
		 * $contents contains an array arrays (info ABOUT each filename) while
		 * $filenames just contains either the file or folder name.
		 */
		$contents = array();

		/*
		 * Keep track of just file and folder names added to the $contents. For
		 * example:
		 *
		 * [0] wp-content (folder)
		 * [1] wp-content/index.php (file)
		 */
		$filenames = array();

		$zip = new PclZip( $filepath );

		$list = $zip->listContent();
		if( empty( $list ) ) {
			return $contents;
		}

		/*
		 * Each $file is an array. Several example $file's can be seen here:
		 * https://pastebin.com/bjQZYcAt
		 */
		foreach( $list as $key => $file ) {

			/*
			 * Calculate the parent directory this file / folder belongs to.
			 *
			 * Examples:
			 * * readme.html                     = .
			 * * wp-admin/press-this.php         = wp-admin
			 * * wp-admin/js/user-profile.min.js = wp-admin/js
			 */
			$parent_dir = dirname( $file['filename'] );

			if( $parent_dir !== $in_dir ) {
				continue;
			}

			$contents[] = $file;
			$filenames[] = rtrim( $file['filename'], '/' );
		}

		$contents = $this->browse_add_dirs( $list, $contents, $filenames, $in_dir );

		return $contents;
	}

	/**
	 * Extract one file from an archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath /home/user/boldgrid_backup/archive.zip
	 * @param  string $file     wp-content/index.php
	 *
	 */
	public function extract_one( $filepath, $file ) {
		if( ! $this->core->archive->is_archive( $filepath ) ) {
			$this->errors[] = __( 'Not an archive.', 'boldgrid-backup' );
			return false;
		}

		if( empty( $file ) ) {
			$this->errors[] = __( 'Empty file.', 'boldgrid-backup' );
			return false;
		}

		$file_contents = $this->get_file( $filepath, $file );

		// Write the file and adjust the timestamp.
		$written = $this->core->wp_filesystem->put_contents( ABSPATH . $file, $file_contents[0]['content'] );
		if( ! $written ) {
			$this->errors[] = __( 'Not written.', 'boldgrid-backup' );
			return false;
		}
		return $this->core->wp_filesystem->touch( ABSPATH . $file, $file_contents[0]['mtime'] );
	}

	/**
	 * Extract 1 file from a zip archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath /home/user/boldgrid_backup/archive.zip
	 * @param  string $file     wp-content/index.php
	 * @return mixed False on failure, array on success {
	 *     Accessed via $file_contentws[0].
	 *
	 *     @type string $filename        wp-content/index.php
	 *     @type string $stored_filename wp-content/index.php
	 *     @type int    $size            28
	 *     @type int    $compressed_size 30
	 *     @type int    $mtime           1505997200
	 *     @type string $comment
	 *     @type bool   $folder
	 *     @type int    $index           25054
	 *     @type string $status          ok
	 *     @type int    $crc             4212199498
	 *     @type string $content
	 * }
	 */
	public function get_file( $filepath, $file ) {
		if( ! $this->core->archive->is_archive( $filepath ) ) {
			return false;
		}

		if( empty( $file ) ) {
			return false;
		}

		$zip = new PclZip( $filepath );

		$list = $zip->listContent();
		if( empty( $list ) ) {
			return false;
		}

		$file_index = false;

		foreach( $list as $index => $filedata ) {
			if( $file === $filedata['filename'] ) {
				$file_index = $index;
			}
		}

		/*
		 * We use to check if(! $file_index) however sometimes the file we want
		 * is at the 0 index.
		 */
		if( false === $file_index ) {
			return false;
		}

		$file_contents = $zip->extractByIndex( $file_index, PCLZIP_OPT_EXTRACT_AS_STRING );

		return $file_contents;
	}

	/**
	 * Get a list of all sql dumps in an archive's root.
	 *
	 * When restoring an archive, this method is helpful in determining which
	 * sql dump to restore. We're expecting only 1 to be found.
	 *
	 * @since 1.5.2
	 *
	 * @param  string $filepath Full path to zip file.
	 * @return array An array of sql dumps found in the root.
	 */
	public function get_sqls( $filepath ) {
		$sqls = array();

		$zip = new PclZip( $filepath );

		$list = $zip->listContent();

		if( empty( $list ) ) {
			return $sqls;
		}

		foreach( $list as $key => $file ) {
			$filename = $file['filename'];

			// If it's not in the root, skip it.
			if( false !== strpos( $filename, '/' ) || false !== strpos( $filename, '\\' ) ) {
				continue;
			}

			// If it's not in this format, skip it - Format: *.########-######.sql
			if ( 1 !== preg_match( '/\.[\d]+-[\d]+\.sql$/', $filename ) ) {
				continue;
			}

			$sqls[] = $filename;
		}

		return $sqls;
	}

	/**
	 * Parse the error message and take appropriate action.
	 *
	 * @since 1.5.2
	 *
	 * @param string $error_info
	 * @return mixed False when no messages should be displayed, String when
	 *               returning a message to the user.
	 */
	public function parse_error_info( $error_info ) {
		$parts = explode( '\'', $error_info );
		$force_php_zip = false;
		$messages = array();

		// Does not exist [code -4].
		if( ! empty( $parts[2] ) && false !== strpos( $parts[2], 'code -4' ) ) {
			$path = ABSPATH . $parts[1];

			// Check for broken symlink.
			if( is_link( $path ) && ! $this->core->wp_filesystem->exists( $path ) ) {
				$force_php_zip = true;
				$messages[] = sprintf( __( 'PclZip encountered the following broken symlink and is unable to create a backup:<br />%1$s', 'boldgrid-backup' ), $parts[1] );
			}
		}

		/*
		 * If we have flagged that ZipArchive should be used instead of PclZip,
		 * then update the settings.
		 */
		if( $force_php_zip ) {
			$php_zip_set = $this->core->compressors->set_php_zip();

			if( $php_zip_set ) {
				$messages[] = __( 'We have changed your compressor from PclZip to ZipArchive. Please try to create a backup again.' );
			}
		}

		return empty( $messages ) ? false : implode( '<br />', $messages );
	}

	/**
	 * Test the functionality of php_zip.
	 *
	 * @since 1.5
	 *
	 * @param  bool $display_errors
	 * @return bool
	 */
	public function test( $display_errors = true ) {
		if( null !== self::$test_result ) {
			return self::$test_result;
		}

		$backup_dir = $this->core->backup_dir->get();

		// Strings to help with creating test files.
		$test_file_contents = $str = __( 'This is a test file from BoldGrid Backup. You can delete this file.', 'boldgrid-backup' );
		$safe_to_delete = __( 'safe-to-delete', 'boldgrid-backup' );
		$test_zip_file = $this->core->test->test_prefix . '-zip';
		$test_filename = sprintf( '%1$s%5$s%2$s-%3$s-%4$s', $backup_dir, $test_zip_file, mt_rand(), $safe_to_delete, DIRECTORY_SEPARATOR );
		$zip_filepath = $test_filename . '.zip';
		$random_filename = $test_filename . '.txt';

		$cannot_touch_file = __( 'PclZip test failed. We were unable to create the following test file:<br />
			%1$s.<br />
			Please ensure your backup directory has read, write, and modify permissions.',
			'boldgrid-backup'
		);

		$cannot_put_contents = __( 'PclZip test failed. We were able to create the following test file, but we were unable to modify it. were unable to modify it:<br />
			%1$s<br />
			Please ensure your backup directory has read, write, and modify permissions.',
			'boldgrid-backup'
		);

		$touched = $this->core->wp_filesystem->touch( $random_filename );
		if( ! $touched ) {
			$this->test_errors[] = sprintf( $cannot_touch_file, $random_filename );
			self::$test_result = false;
			return false;
		}

		$contents_put = $this->core->wp_filesystem->put_contents( $random_filename, $test_file_contents );
		if( ! $contents_put ) {
			$this->test_errors[] = sprintf( $cannot_put_contents, $random_filename );
			self::$test_result = false;
			return false;
		}

		$archive = new PclZip( $zip_filepath );
		if ( 0 === $archive ) {
			$this->test_errors[] = sprintf( 'Cannot create ZIP archive file %1$s. %2$s.', $info['filepath'], $archive->errorInfo() );
		}

		$status = $archive->add( $random_filename );
		if( 0 === $status ) {
			$this->test_errors[] = sprintf( 'Cannot add files to PclZip archive file: %1$s', $archive->errorInfo() );
		}

		$this->core->test->delete_test_files( $backup_dir );

		self::$test_result = true;
		return true;
	}
}
