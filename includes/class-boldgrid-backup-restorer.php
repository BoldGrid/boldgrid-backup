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
	 */
	public function init() {
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

		$this->info = $this->core->restore_archive_file();

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
