<?php
/**
 * File: class-boldgrid-backup-job.php
 *
 * Manage the state and data of long running jobs.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Job
 *
 * Manage the state and data of long running jobs.
 *
 * @since X.X.X
 */
class Boldgrid_Backup_Job {

	/**
	 * Name of the option to store jobs.
	 *
	 * @since X.X.X
	 * @var string
	 */
	protected static $option_name = 'bgbkup_jobs';

	/**
	 * Start a job.
	 *
	 * @since X.X.X
	 *
	 * @param string $type Type of job.
	 * @param array $data  Request data for job start.
	 * @return array       Job resource.
	 */
	public static function start( $type, $data = [] ) {
		$new_job = [
			'id'         => self::generate_id(),
			'status'     => 'running',
			'type'       => $type,
			'data_start' => $data,
		];

		self::insert_db( $new_job );

		return $new_job;
	}

	/**
	 * Update the job to complete.
	 *
	 * @since X.X.X
	 *
	 * @param  string $job_id Job ID.
	 * @param  string $status Status of job.
	 * @return array|bool     Job Data.
	 */
	public static function update( $job_id, $status ) {
		$job = self::update( $job_id, function ( $job ) use ( $status ) {
			$job['status'] = $status;
			return $job;
		} );

		return $job ? $job : false;
	}

	/**
	 * Update the job to complete.
	 *
	 * @since X.X.X
	 *
	 * @param  string $job_id Job ID.
	 * @param  string $status Status of job.
	 * @return array          Data for step.
	 * @return array|bool     Job Data.
	 */
	public static function complete( $job_id, $status, $data ) {
		$job = self::update_db( $job_id, function ( $job ) use ( $status, $data ) {
			$job['status']        = $status;
			$job['data_complete'] = $data;
			return $job;
		} );

		return $job ? $job : false;
	}

	/**
	 * Get a job from storage.
	 *
	 * @since X.X.X
	 *
	 * @param  string $job_id Job ID.
	 * @return array|null     Job Data.
	 */
	public static function get( $job_id ) {
		$jobs = self::fetch_all();

		$job = array_filter( $jobs, function ( $val ) use ( $job_id ) {
			return ! empty( $val['id'] ) && $val['id'] === $job_id;
		} );

		$job = reset( $job );

		return $job ? $job : null;
	}

	/**
	 * Get all jobs from storage.
	 *
	 * @since X.X.X
	 *
	 * @return array Collection of Jobs.
	 */
	protected static function fetch_all() {
		$jobs = get_option( self::$option_name, [] );
		return ! empty( $jobs ) ? $jobs : [];
	}

	/**
	 * Update the database with the job data.
	 *
	 * @since X.X.X
	 *
	 * @param string   $job_id   Job ID.
	 * @param function $callback Update operation.
	 * @return array|bool        Job Data.
	 */
	protected static function update_db( $job_id, $callback ) {
		$jobs = self::fetch_all();
		$job  = array_filter( $jobs, function ( $val ) use ( $job_id, $callback ) {
			if ( ! empty( $val['id'] ) && $val['id'] === $job_id ) {
				$val = $callback( $val );
			}
		} );

		return $job ? $job : false;
	}

	/**
	 * Insert a new job record.
	 *
	 * @since X.X.X
	 *
	 * @param array $job Single Job Record.
	 * @return void
	 */
	protected static function insert_db( $job ) {
		$jobs   = self::fetch_all();
		$jobs[] = $job;
		update_option( self::$option_name, $jobs );
	}

	/**
	 * Create a new job id.
	 *
	 * @since X.X.X
	 *
	 * @return string Job Id.
	 */
	protected static function generate_id() {
		return md5( microtime( true ) );
	}
}
