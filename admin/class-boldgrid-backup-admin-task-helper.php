<?php
/**
 * File: class-boldgrid-backup-admin-task-helper.php
 *
 * @link  https://www.boldgrid.com
 * @since SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Task_Helper
 *
 * This class is a helper class for the Boldgrid_Backup_Admin_Task class.
 *
 * An example of tasks can be found here: admin/class-boldgrid-backup-admin-task.md#example-tasks
 *
 * @since SINCEVERSION
 */
class Boldgrid_Backup_Admin_Task_Helper {
	/**
	 * Option name storing our tasks.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $option = 'boldgrid_backup_tasks';

	/**
	 * Get a task by id.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $id A task id.
	 * @return array
	 */
	public function get_by_id( $id ) {
		$return_task = [];

		$tasks = $this->get_tasks();

		foreach ( $tasks as $task ) {
			if ( $task['id'] === $id ) {
				$return_task = $task;
				break;
			}
		}

		return $return_task;
	}

	/**
	 * Get all tasks.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_tasks() {
		return get_option( $this->option, [] );
	}

	/**
	 * Update a task.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  array $update_task The task that needs updating.
	 * @return bool               True on success.
	 */
	public function update( $update_task ) {
		// A task id is required.
		if ( empty( $update_task['id'] ) ) {
			return false;
		}

		$tasks = $this->get_tasks();

		$existing_task = $this->get_by_id( $update_task['id'] );

		/*
		 * Add our task to $tasks.
		 *
		 * If the task already exists, find it and update it. Otherwise, add it.
		 */
		if ( ! empty( $existing_task ) ) {
			foreach ( $tasks as $key => $task ) {
				// Keep looking for our task by id until it's found.
				if ( $task['id'] !== $update_task['id'] ) {
					continue;
				}

				// It's been found. Replace the task by the task passed into this method.
				$tasks[ $key ] = $update_task;
				break;
			}
		} else {
			// This is a new task. Simply add it to the list.
			$tasks[] = $update_task;
		}

		return $this->update_tasks( $tasks );
	}

	/**
	 * Update all tasks.
	 *
	 * @since SINCEVERSION
	 *
	 * @param array $tasks An array of all our tasks.
	 */
	public function update_tasks( $tasks ) {
		if ( ! is_array( $tasks ) ) {
			return false;
		}

		update_option( $this->option, $tasks );
	}
}
