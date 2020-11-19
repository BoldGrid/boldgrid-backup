<?php
/**
 * File: class-boldgrid-backup-archive-fetcher.php
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Archive_Fetcher
 *
 * This class is used to download archives from a url.
 *
 * The contents of this class were originally in the Boldgrid_Backup_Admin_Upload class, and have been
 * moved here for reusability.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Archive_Fetcher {
	/**
	 * The url to download.
	 *
	 * @since SINCEVERSION
	 * @param string
	 */
	public $url;

	/**
	 * Allowed content types.
	 *
	 * @since SINCVERSION
	 * @access private
	 * @var array
	 */
	private $allowed_content_types = [
		'application/octet-stream',
		'binary/octet-stream',
		'application/zip',
	];

	/**
	 * The core class object.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An error message.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $error;

	/**
	 * Filepath to our archive.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $filepath;

	/**
	 * Fetcher info.
	 *
	 * After a successful fetch, this array will have info about our new backup.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array {
	 *     @type string $filepath        The filepath to the archive.
	 *     @type string $detailsUrl      The admin url to the details page for this archive.
	 *     @type string $archiveFilename The filename of the archive.
	 *     @type int    $archiveKey      The archive key.
	 * }
	 */
	private $info = [];

	/**
	 * The path to the archive's log.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $log_filepath;

	/**
	 * The response received when trying to download the file.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var mixed
	 */
	private $response;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $url The url we will be downloading.
	 */
	public function __construct( $url ) {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );

		$this->url = $url;
	}

	/**
	 * Download a backup file from a remote server.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	public function download() {
		if ( ! $this->is_valid_url() ) {
			$this->error = __( 'Invalid URL address.', 'boldgrid-backup' );
			return false;
		}

		if ( ! $this->is_valid_backupdir() ) {
			$this->error = implode( '<br />', $this->core->backup_dir->errors );
			return false;
		}

		$this->filepath = $this->core->upload->get_save_path( basename( $this->url ) );

		$this->response = wp_remote_get(
			$this->url,
			[
				'filename'  => $this->filepath,
				'headers'   => 'Accept: ' . implode( ', ', $this->allowed_content_types ),
				'sslverify' => false,
				'stream'    => true,
				'timeout'   => MINUTE_IN_SECONDS * 20,
			]
		);

		if ( $this->is_call_successful() ) {
			$this->post_successful_download();

			return true;
		} else {
			$this->core->wp_filesystem->delete( $this->filepath );

			$this->error = __(
				'Could not retrieve the remote file.  It may not be a ZIP file, or the link is no longer valid.',
				'boldgrid-backup'
			);

			return false;
		}
	}

	/**
	 * Get our error message.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_error() {
		return $this->error;
	}

	/**
	 * Get our array of info after a successful download.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_info() {
		return $this->info;
	}

	/**
	 * Whether or not we encountered an error during the download process.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function has_error() {
		return ! empty( $this->error );
	}

	/**
	 * Whether or not the call to download the file was successful.
	 *
	 * This does not represent the success of the download() method, but instead the state of the
	 * wp_remote_get call.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True on success.
	 */
	private function is_call_successful() {
		return is_array( $this->response ) &&
			! is_wp_error( $this->response ) &&
			in_array( $this->response['headers']['content-type'], $this->allowed_content_types, true );
	}

	/**
	 * Validate our backup directory.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True if valid.
	 */
	private function is_valid_backupdir() {
		$backup_directory = $this->core->backup_dir->get();

		return $this->core->backup_dir->is_valid( $backup_directory ) && empty( $this->core->backup_dir->errors );
	}

	/**
	 * Validate our download url.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool True if valid.
	 */
	private function is_valid_url() {
		$url_regex = '/' . $this->core->configs['url_regex'] . '/i';

		return preg_match( $url_regex, $this->url );
	}

	/**
	 * Steps to take if our raw wp_remote_get() call to download the backup was successful.
	 *
	 * @since SINCEVERSION
	 */
	private function post_successful_download() {
		$this->set_logfilepath();

		$filename = basename( $this->filepath );

		// Restore the log file from the archive.
		$this->core->archive_log->restore_by_zip( $this->filepath, basename( $this->log_filepath ) );

		// Update the archive file modification time, based on the log file contents.
		$this->core->remote->post_download( $this->filepath );

		// Get the archive details.
		$archive = $this->core->archive->get_by_name( $filename );

		$this->info = [
			'filepath'        => $this->filepath,
			'detailsUrl'      => admin_url( 'admin.php?page=boldgrid-backup-archive-details&filename=' . basename( $this->filepath ) ),
			'archiveFilename' => $filename,
			'archiveKey'      => $archive['key'],
		];
	}

	/**
	 * Set the archive log filepath.
	 *
	 * @since SINCEVERSION
	 */
	private function set_logfilepath() {
		$this->log_filepath = $this->filepath;

		if ( ! empty( $this->response['headers']['content-disposition'] ) ) {
			$this->log_filepath = trim(
				str_replace(
					'attachment; filename=', '', $this->response['headers']['content-disposition']
				), '"'
			);

			$this->log_filepath = $this->core->backup_dir->get_path_to( $this->log_filepath );
		}

		$this->log_filepath = $this->core->archive_log->path_from_zip( $this->log_filepath );
	}
}
