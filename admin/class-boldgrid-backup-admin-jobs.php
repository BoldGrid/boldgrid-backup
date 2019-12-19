<?php
/**
 * File: class-boldgrid-backup-admin-jobs.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Jobs
 *
 * Option $boldgrid_backup_jobs array {
 *     An array of jobs that need to be ran.
 *
 *     array {
 *
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
	 * @param Boldgrid_Backup_Admin_Core $core Boldgrid_Backup_Admin_Core Object.
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
		if ( empty( $args['action'] ) ) {
			return false;
		}

		$args['status'] = 'pending';

		$this->set_jobs();
		$this->jobs[] = $args;
		$this->save_jobs();
	}

	/**
	 * Delete all jobs before and including $delete_key.
	 *
	 * We may have 100 jobs lined up. Job #5 may say "delete me and all those
	 * that came before me". Job #5 does that by calling this method.
	 *
	 * @since 1.5.2
	 *
	 * @param int $delete_key Archive list key index number.
	 */
	public function delete_all_prior( $delete_key ) {

		if ( ! is_numeric( $delete_key ) ) {
			return;
		}

		$this->set_jobs();

		foreach ( $this->jobs as $key => $job ) {
			if ( $key <= $delete_key ) {
				unset( $this->jobs[ $key ] );
			}
		}

		$this->jobs = array_values( $this->jobs );
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

		foreach ( $this->jobs as $job ) {
			if ( 'running' === $job['status'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add "send an email" to the jobs queue.
	 *
	 * This method is added at priorty 200 to the
	 * boldgrid_backup_post_archive_files action. It should be the last thing
	 * that the jobs queue does, send an email confirmation.
	 *
	 * @since 1.5.2
	 *
	 * @param array $info Archive information.
	 */
	public function post_archive_files( $info ) {
		/*
		 * We only want to add this to the jobs queue if we're in the middle of
		 * an automatic backup (one that has been scheduled). If the user simply clicked on
		 * "Backup site now", we don't want to email the user, we'll already be doing that.
		 */
		if ( ! $this->core->is_scheduled_backup ) {
			return;
		}

		if ( ! $this->core->email->user_wants_notification( 'backup' ) ) {
			return;
		}

		$args = array(
			'filepath'    => $info['filepath'],
			'action'      => 'boldgrid_backup_post_jobs_email',
			'action_data' => $info,
			'post_action' => 'delete_all_prior',
		);

		$this->add( $args );
	}

	/**
	 * Send an email after all jobs have been ran.
	 *
	 * @since 1.5.2
	 *
	 * @param array $info Archive information.
	 */
	public function post_jobs_email( $info ) {
		$post_jobs = 0;

		$job_summary = array();

		$job_summary[] = __( 'The following tasks were run after creating the backup:', 'boldgrid-backup' );

		$email_parts = $this->core->email->post_archive_parts( $info );

		$this->set_jobs();

		foreach ( $this->jobs as $key => $job ) {

			if ( 'boldgrid_backup_post_jobs_email' === $job['action'] ) {
				unset( $this->jobs[ $key ] );
				break;
			}

			$job_summary[] = sprintf(
				'%1$s: %2$s%3$s* %4$s:%10$s%5$s%3$s* %6$s:%10$s%7$s%3$s* %8$s:%10$s%10$s%9$s',
				__( 'Task', 'boldgrid-backup' ),
				$job['action_title'],
				"\n",
				__( 'status', 'boldgrid-backup' ),
				$job['status'],
				__( 'start', 'boldgrid-backup' ),
				date( 'Y.m.d h:i:s a', $job['start_time'] ),
				__( 'end', 'boldgrid-backup' ),
				date( 'Y.m.d h:i:s a', $job['end_time'] ),
				"\t"
			);
			$post_jobs++;

			unset( $this->jobs[ $key ] );
		}

		$this->save_jobs();

		if ( $post_jobs > 0 ) {
			$email_parts['body']['main'] .= implode( "\n\n", $job_summary ) . "\n\n";
		}

		$body = implode( '', $email_parts['body'] );

		return $this->core->email->send( $email_parts['subject'], $body );
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

		// If there are no jobs or already running, then abort.
		if ( empty( $this->jobs ) || $this->is_running() ) {
			wp_die();
		}

		foreach ( $this->jobs as $key => &$job ) {
			if ( 'pending' !== $job['status'] ) {
				continue;
			}

			$job['start_time'] = time();
			$this->save_jobs();

			$status = apply_filters( $job['action'], $job['action_data'] );

			$job['end_time'] = time();
			$job['status']   = $status ? 'success' : 'fail';
			$this->save_jobs();

			break;
		}

		if ( ! empty( $job['post_action'] ) && 'delete_all_prior' === $job['post_action'] ) {
			$this->delete_all_prior( $key );
		}

		wp_die();
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
		if ( ! is_null( $this->jobs ) ) {
			return;
		}

		$this->jobs = get_site_option( $this->option, array() );
	}
}
