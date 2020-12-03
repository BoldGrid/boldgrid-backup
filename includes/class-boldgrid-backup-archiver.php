<?php
/**
 * File: class-boldgrid-backup-archiver.php
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
 * Class: Boldgrid_Backup_Archiver
 *
 * Originally, all methods for archiving a file have lived in Boldgrid_Backup_Admin_Core. This class,
 * over time, will absorb those methods.
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Archiver {
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
		$this->core->logger->add( 'Backup complete!' );
		$this->core->logger->add_memory();

		$this->task->end();
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
	 * Steps to take before an archive is started.
	 *
	 * @since SINCEVERSION
	 */
	public function init() {
		// Init our logger.
		$this->core->logger->init( 'archive-' . time() . '.log' );
		$this->core->logger->add( 'Backup process initialized.' );

		// Init our task.
		$this->task = new Boldgrid_Backup_Admin_Task();
		if ( ! empty( $_POST['task_id'] ) ) { // phpcs:ignore
			$this->task->init_by_id( $_POST['task_id'] ); // phpcs:ignore
		} else {
			$this->task->init( [ 'type' => 'backup' ] );
		}
		$this->task->start();
	}

	/**
	 * Create an archive.
	 *
	 * Do everything.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->init();

		$this->info = $this->core->archive_files( true );

		$this->complete();
	}
}
