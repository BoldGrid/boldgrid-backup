<?php
/**
 * Boldgrid Backup Admin Jobs.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * Boldgrid Backup Admin Jobs class.
 *
 * Option $boldgrid_backup_jobs array {
 *     An array of jobs that need to be ran.
 *
 *     array {
 *        @type string $status      pending|running|complete|fail
 *        @type string $filepath    Full filepath to the backup file.
 *        @type int    $start_time
 *        @type int    $end_time
 *        @type string $action      The action to run.
 *        @type array  $action_data An array of data to send to the action.
 *     }
 * }
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Jobs {

	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of jobs.
	 *
	 * @since 1.5.2
	 * @var   mixed $jobs null|array Null by default to show it has not been
	 *                               initialized.
	 */
	public $jobs = null;

	/**
	 * The option name used to store jobs.
	 *
	 * @since 1.5.2
	 * @var   string $option
	 */
	public $option = 'boldgrid_backup_jobs';

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Add a job to the queue.
	 *
	 * @since 1.5.2
	 *
	 * @param array $args An array of args for our job. Please see doc block of
	 *                    this class for more information.
	 */
	public function add( $args ) {
		if( empty( $args['action'] ) ) {
			return false;
		}

		$args['status'] = 'pending';

		$this->set_jobs();
		$this->jobs[] = $args;
		$this->save_jobs();
	}

	/**
	 * Determine if we have any jobs currently running.
	 *
	 * @since 1.5.2
	 *
	 * @return bool True when we have a job currently running.
	 */
	public function is_running() {
		$this->set_jobs();

		foreach( $this->jobs as $job ) {
			if( 'running' === $job['status'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Run the next job in the queue.
	 *
	 * This is the main method of this class. When cron or wp-cron runs, it will
	 * call this method, which will handle the rest.
	 *
	 * @since 1.5.2
	 */
	public function run() {
		$this->set_jobs();

		if( empty( $this->jobs ) ) {
			return;
		}

		if( $this->is_running() ) {
			return;
		}

		foreach( $this->jobs as &$job ) {
			if( 'pending' !== $job['status'] ) {
				continue;
			}

			$job['start_time'] = time();
			$this->save_jobs();

			$status = apply_filters( $job['action'], $job['action_data'] );

			$job['end_time'] = time();
			$job['status'] = $status ? 'success' : 'fail';
			$this->save_jobs();

			break;
		}
	}

	/**
	 * Save jobs.
	 *
	 * @since 1.5.2
	 */
	public function save_jobs() {
		update_site_option( $this->option, $this->jobs );
	}

	/**
	 * Set jobs.
	 *
	 * @since 1.5.2
	 */
	public function set_jobs() {
		if( ! is_null( $this->jobs ) ) {
			return;
		}

		$this->jobs = get_site_option( $this->option, array() );
	}
}
