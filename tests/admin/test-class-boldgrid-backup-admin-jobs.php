<?php
/**
 * File: test-class-boldgrid-backup-admin-jobs.php
 *
 * @link  https://www.boldgrid.com
 * @since 1.15.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Jobs
 *
 * @since 1.15.5
 */
class Test_Boldgrid_Backup_Admin_Jobs extends WP_UnitTestCase {
	/**
	 * Our jobs object.
	 *
	 * @since 1.15.5
	 * @var Boldgrid_Backup_Admin_Jobs
	 */
	public $jobs;

	/**
	 * Test maybe_fix_tsalled method.
	 *
	 * @since 1.15.5
	 */
	public function test_maybe_fix_stalled() {
		$filepath     = '/some/file/boldgrid-backup-domain.com-0134567-' . date( 'Ymd' ) . '-123456.zip';
		$old_job_path = '/some/file/boldgrid-backup-domain.com-0134567-' . date( 'Ymd', strtotime('-2 weeks') ) . '-123456.zip';
		$test_jobs    = array(
			array(
				'filepath'   => $filepath,
				'action'     => 'some_action_1',
				'status'     => 'running',
				// This job is not stalled. If the start time is right now, it's been running 0 seconds.
				'start_time' => time(),
			),
			array(
				'filepath'   => $filepath,
				'action'     => 'some_action_2',
				'status'     => 'running',
				// This job was started 1 year ago. It should be flagged as being stalled.
				'start_time' => time() - YEAR_IN_SECONDS,
			),
			array(
				// This job was created over a week ago, so it should be removed.
				'filepath'   => $old_job_path,
				'action'     => 'some_action_3',
				'status'     => 'running',
				'start_time' => time(),
			),
		);

		update_option( $this->jobs->option, $test_jobs );

		// When you set the jobs, it will call maybe_fix_stalled().
		$this->jobs->set_jobs();

		$this->assertEquals( 'running', $this->jobs->jobs[0]['status'] );

		$this->assertEquals( 'fail', $this->jobs->jobs[1]['status'] );

		$this->assertEquals( 2, count( $this->jobs->jobs ) );
	}

	/**
	 * Setup.
	 *
	 * @since 1.15.5
	 */
	public function set_up() {
		$core = new Boldgrid_Backup_Admin_Core();

		$this->jobs = new Boldgrid_Backup_Admin_Jobs( $core );
	}
}
