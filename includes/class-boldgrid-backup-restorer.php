<?php
/**
 * File: class-boldgrid-backup-restorer.php
 *
 * @link https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Restorer
 *
 * Originally, all methods for restoring a file have lived in Boldgrid_Backup_Admin_Core. This class,
 * over time, will absorb those methods.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Restorer {
	/**
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Archive
	 */
	private $archive;

	/**
	 * Admin core.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of info about our archive.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $info;

	/**
	 * An instance of Boldgrid_Backup_Admin_Task.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Task
	 */
	private $task;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 */
	public function __construct() {
		$this->core = apply_filters( 'boldgrid_backup_get_core', null );
	}

	/**
	 * Steps to take when archiving is complete.
	 *
	 * @since SINCEVERSION
	 */
	public function complete() {
		// Update the log.
		$this->core->logger->add( 'Restoration complete!' );
		$this->core->logger->add_memory();

		if ( $this->has_error() ) {
			$this->core->logger->add( 'Error during restoration: ' . $this->get_error() );
		}

		// End the task.
		$this->task->end();
	}

	/**
	 * Get our error message.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	private function get_error() {
		return ! empty( $this->info['error'] ) ? $this->info['error'] : __( 'Unknown error', 'boldgrid-backup' );
	}

	/**
	 * Get our archive info.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_info() {
		return $this->info;
	}

	/**
	 * Whether or not we have an error.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	private function has_error() {
		return ! empty( $this->info['error'] );
	}

	/**
	 * Steps to take before an archive is started.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  array $args {
	 *     An optional array of args.
	 *
	 *     @type int    $archive_key      An archive key.
	 *     @type string $archive_filename An archive filename.
	 * }
	 */
	public function init( $args = array() ) {
		// Init our logger.
		$this->core->logger->init( 'restore-' . time() . '.log' );
		$this->core->logger->add( 'Restore process initialized.' );
		$this->core->logger->add_memory();

		/*
		 * Setup our task.
		 *
		 * We're either starting a new task, or continuing on an existing task.
		 */
		$this->task = new Boldgrid_Backup_Admin_Task();
		if ( ! empty( $_POST['task_id'] ) ) { // phpcs:ignore
			$this->task->init_by_id( $_POST['task_id'] ); // phpcs:ignore
		} else {
			$this->task->init( [ 'type' => 'restore' ] );
		}
		$this->task->start();

		$this->core->restoring_archive_file = true;

		// Using pcl_zip (ZipArchive unavailable), a 400MB+ zip used over 500MB+ of memory to restore.
		Boldgrid_Backup_Admin_Utility::bump_memory_limit( '1G' );

		// Close any PHP session, so that another session can open during this restore operation.
		session_write_close();

		// Prevent this script from dying.
		ignore_user_abort( true );

		$this->core->set_time_limit();

		/*
		 * This is a generic method to restore an archive. Do not assume the request to restore is coming
		 * from a user directly via $_POST.
		 *
		 * Refer to check_ajax_referer usage below to help protect ajax requests.
		 */
		$is_post_restore = isset( $_POST['action'] ) && 'boldgrid_backup_restore_archive' === $_POST['action']; // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

		// If a restoration was not requested, then abort.
		if ( empty( $_POST['restore_now'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$error_message = esc_html__( 'Invalid restore_now value.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		if ( $is_post_restore && ! check_ajax_referer( 'boldgrid_backup_restore_archive', 'archive_auth', false ) ) {
			$error_message = esc_html__( 'Invalid nonce.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		// Check if functional.
		if ( ! $this->core->test->run_functionality_tests() ) {
			$error_message = esc_html__( 'Functionality tests fail.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		/*
		 * Get our archive key.
		 *
		 * It can be passed in via $args or $_POST.
		 */
		$archive_key = false;
		if ( isset( $args['archive_key'] ) ) {
			$archive_key = (int) $args['archive_key'];
		} elseif ( isset( $_POST['archive_key'] ) && is_numeric( $_POST['archive_key'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$archive_key = (int) $_POST['archive_key'];
		} else {
			$error_message = esc_html__( 'Invalid key for the selected archive file.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		/*
		 * Get our archive filename.
		 *
		 * It can be passed in via $args or $_POST.
		 */
		$archive_filename = false;
		if ( ! empty( $args['archive_filename'] ) ) {
			$archive_filename = sanitize_file_name( $args['archive_filename'] );
		} elseif ( ! empty( $_POST['archive_filename'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			$archive_filename = sanitize_file_name( $_POST['archive_filename'] );
		} else {
			$error_message = esc_html__( 'Invalid filename for the selected archive file.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		$archives = $this->core->get_archive_list( $archive_filename );
		if ( empty( $archives ) ) {
			$error_message = esc_html__( 'No archive files were found.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		$filename = ! empty( $archives[ $archive_key ]['filename'] ) ? $archives[ $archive_key ]['filename'] : null;

		if ( $archive_filename !== $filename ) {
			$error_message = esc_html__( 'The selected archive file was not found.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		$filepath = ! empty( $archives[ $archive_key ]['filepath'] ) ? $archives[ $archive_key ]['filepath'] : null;

		if ( ! empty( $filepath ) && $this->core->wp_filesystem->exists( $filepath ) ) {
			$filesize = $this->core->wp_filesystem->size( $filepath );
		} else {
			$error_message = esc_html__( 'The selected archive file is empty.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

		// Populate $info.
		$info = [
			'mode'        => 'restore',
			'filename'    => $archive_filename,
			'filepath'    => $filepath,
			'filesize'    => $filesize,
			'archive_key' => $archive_key,
			'restore_ok'  => true,
		];
		$this->core->logger->add( 'Restore info: ' . print_r( $info, 1 ) ); // phpcs:ignore

		error_log( 'restore info = ' . print_r( $info,1));

		$this->archive = \Boldgrid\Backup\Archive\Factory::get_by_filename( $info['filename'] );
		if ( empty( $this->archive ) ) {
			$error_message = esc_html__( 'Unable to get archive by filename.', 'boldgrid-backup' );
			$this->core->logger->add( $error_message );
			return [ 'error' => $error_message ];
		}

// 		error_log( 'is virtual = ' . $archive->is_virtual );
// 		$zips = $archive->virtual->get_dirlist()->get_by_extension( 'zip' );
// 		error_log( 'zips = ' . print_r( $zips,1 ) );
// 		die();
	}

	/**
	 * Restore a backup by id.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $id The backup id.
	 */
	public function restore_by_id( $id ) {
		$archive = Boldgrid\Backup\Archive\Factory::get_by_id( $id );

		$this->restore_by_key( $archive->get_key(), $archive->filename );
	}

	/**
	 * Restore an archive by key (and filename).
	 *
	 * @since SINCEVERSION
	 *
	 * @param int    $key      The archive key to restore.
	 * @param string $filename The archive filename to restore.
	 */
	public function restore_by_key( $key, $filename ) {
		$restore_args = [
			'archive_key'      => $key,
			'archive_filename' => $filename,
		];

		$this->info = $this->core->restore_archive_file( false, $restore_args );
	}

	/**
	 * Restore a site by url.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $url A url to a zip file.
	 */
	public function restore_by_url( $url ) {
		// Download the backup file.
		$archive_fetcher = new Boldgrid_Backup_Archive_Fetcher( $url );
		$archive_fetcher->download();

		// If we have errors, abort.
		if ( $archive_fetcher->has_error() ) {
			$this->set_error( $archive_fetcher->get_error() );
			return;
		}

		// Restore the new archive just downloaded.
		$info = $archive_fetcher->get_info();
		$this->restore_by_key( $info['archiveKey'], $info['archiveFilename'] );
	}

	/**
	 * Run a basic restore.
	 *
	 * This mimics a standard call to core->restore_archive_file, except it adds additional things
	 * like tasks and logs.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->init();

		if ( $this->archive->is_virtual ) {
			$restorer = \Boldgrid\Backup\V2\Restorer\Factory::run( $this->archive->virtual->get_id(), null );
			$restorer->run();
		} else {
			$this->info = $this->core->restore_archive_file();
		}

		$this->complete();
	}

	/**
	 * Run a restoration by backup id.
	 *
	 * Method run_by_id:     This method, does more than just restoration. Handles logging, etc.
	 * Method restore_by_id: Handles just the restoration, nothing more.
	 *
	 * @since SINCEVERSION
	 *
	 * @param int $id The backup id.
	 */
	public function run_by_id( $id ) {
		$this->init();

		$this->restore_by_id( $id );

		$this->complete();
	}

	/**
	 * Run a restoration by a url.
	 *
	 * Method run_by_url:     This method, does more than just restoration. Handles logging, etc.
	 * Method restore_by_url: Handles just the restoration, nothing more.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $url A url to a zip file.
	 */
	public function run_by_url( $url ) {
		$this->init();

		$this->restore_by_url( $url );

		$this->complete();
	}

	/**
	 * Set an error message.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $message The error message to set.
	 */
	private function set_error( $message ) {
		$this->info['error'] = $message;
	}
}
