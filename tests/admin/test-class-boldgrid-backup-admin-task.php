<?php
/**
 * File: test-class-boldgrid-backup-admin-task.php
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Archive
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Admin_Task extends WP_UnitTestCase {
	/**
	 * An instance of Boldgrid_Backup_Admin_Task.
	 *
	 * @since SINCEVERSION
	 * @var Boldgrid_Backup_Admin_Task
	 */
	public $task;

	/**
	 * Setup.
	 *
	 * @since SINCEVERSION
	 */
	public function setUp() {
		$this->task = new Boldgrid_Backup_Admin_Task();
		$this->task->init( [ 'type' => 'backup' ] );
		$this->task->update();
	}

	/**
	 * Test get_data.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_data() {
		$this->assertFalse( $this->task->get_data( 'somedata' ) );

		// While we're here, test update_data().
		$this->task->update_data( 'somedata', 'car' );
		$this->assertEquals( 'car', $this->task->get_data( 'somedata' ) );
	}

	/**
	 * Test get_data.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_id() {
		$this->assertEquals( 17, strlen( $this->task->get_id() ) );
	}

	/**
	 * Test get_status.
	 *
	 * @since SINCEVERSION
	 */
	public function test_get_status() {
		$this->assertEquals( 'pending', $this->task->get_status() );

		$this->task->start();
		$this->assertEquals( 'in_progress', $this->task->get_status() );

		$this->task->end();
		$this->assertEquals( 'done', $this->task->get_status() );
	}

	/**
	 * Test init.
	 *
	 * @since SINCEVERSION
	 */
	public function test_init() {
		$task = new Boldgrid_Backup_Admin_Task();

		// Test types.
		$this->assertFalse( $task->init( [] ) );
		$this->assertFalse( $task->init( [ 'type' => 'catfish' ] ) );
		$this->assertTrue( $task->init( [ 'type' => 'backup' ] ) );

		$id = $task->get_id();

		// While we're here, test init_by_id.
		$this->assertFalse( $task->init_by_id( 99 ) );
		$this->assertTrue( $task->init_by_id( $id ) );
	}
}
