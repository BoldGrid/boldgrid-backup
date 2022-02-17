<?php
/**
 * File: class-boldgrid-backup-admin-task.php
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
 * Class: Boldgrid_Backup_Admin_Task
 *
 * Tasks are similar to the jobs queue, but not quite the same. Please see class-boldgrid-backup-admin-task.md
 *
 * @since SINCEVERSION
 *
 * @todo Look into merging tasks with the jobs queue.
 */
class Boldgrid_Backup_Admin_Task {
	/**
	 * A date format used in returning times.
	 *
	 * For example, 'c' in date( 'c', time() ).
	 *
	 * @since SINCEVERSION
	 * @var string
	 */
	public $date_format;

	/**
	 * The time this task was completed.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $completed_at;

	/**
	 * The time this task was created.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $created_at;

	/**
	 * Misc data associated with this task.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $data;

	/**
	 * Our helper class.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var Boldgrid_Backup_Admin_Task_Helper
	 */
	private $helper;

	/**
	 * The task id.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $id;

	/**
	 * The time this task was started.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $started_at;

	/**
	 * The task type.
	 *
	 * For example: backup
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $type;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 */
	public function __construct() {
		$this->helper = new Boldgrid_Backup_Admin_Task_Helper();
	}

	/**
	 * Mark this task as being complete.
	 *
	 * @since SINCEVERSION
	 */
	public function end() {
		$this->completed_at = time();

		$this->update();
	}

	/**
	 * Get the properties of this class.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get() {
		return [
			'id'           => $this->id,
			'type'         => $this->type,
			'created_at'   => empty( $this->date_format ) ? $this->created_at : date( $this->date_format, $this->created_at ),
			'started_at'   => empty( $this->started_at ) ?
				null :
				( empty( $this->date_format ) ? $this->started_at : date( $this->date_format, $this->started_at ) ),
			'completed_at' => empty( $this->completed_at ) ?
				null :
				( empty( $this->date_format ) ? $this->completed_at : date( $this->date_format, $this->completed_at ) ),
			'status'       => $this->get_status(),
			'data'         => $this->data,
		];
	}

	/**
	 * Get a specific value from the data.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $key     The key to get.
	 * @param  mixed  $default The default value to return.
	 * @return mixed
	 */
	public function get_data( $key, $default = false ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Get our task id.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the status of this task.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_status() {
		if ( empty( $this->started_at ) ) {
			$status = 'pending';
		} elseif ( empty( $this->completed_at ) ) {
			$status = 'in_progress';
		} else {
			$status = 'done';
		}

		return $status;
	}

	/**
	 * Initialize a new task.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  array $args An array of args.
	 * @return bool        True if this task was initialized.
	 */
	public function init( $args ) {
		$this->id           = ! empty( $args['id'] ) ? $args['id'] : $this->set_id();
		$this->created_at   = ! empty( $args['created_at'] ) ? $args['created_at'] : time();
		$this->started_at   = ! empty( $args['started_at'] ) ? $args['started_at'] : null;
		$this->completed_at = ! empty( $args['completed_at'] ) ? $args['completed_at'] : null;
		$this->data         = ! empty( $args['data'] ) ? $args['data'] : [];

		// You must supply a type.
		$type = ! empty( $args['type'] ) ? $args['type'] : null;
		if ( empty( $type ) ) {
			return false;
		} elseif ( ! $this->set_type( $type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Init this task by a task id.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $id A task id.
	 * @return bool       True if this task was initialized.
	 */
	public function init_by_id( $id ) {
		$task = $this->helper->get_by_id( $id );

		return empty( $task ) ? false : $this->init( $task );
	}


	/**
	 * Create a new task id.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	private function set_id() {
		// A task id is the current time + 6 random chars.
		$this->id = time() . '-' . substr( md5( time() ), -6 );

		return $this->id;
	}

	/**
	 * Set our task type.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $type Our task type.
	 * @return bool         True if the type was set.
	 */
	private function set_type( $type ) {
		$valid_types = [
			'backup',
			'restore',
		];

		if ( ! in_array( $type, $valid_types, true ) ) {
			return false;
		}

		$this->type = $type;

		return true;
	}

	/**
	 * Start this task.
	 *
	 * @since SINCEVERSION
	 */
	public function start() {
		$this->started_at = time();

		$this->update();
	}

	/**
	 * Update data for a task.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string key The key to update.
	 * @param mixed $value The value to assign to the key.
	 */
	public function update_data( $key, $value ) {
		$this->data[ $key ] = $value;

		$this->update();
	}

	/**
	 * Update / save this task.
	 *
	 * @since SINCEVERSION
	 */
	public function update() {
		return $this->helper->update( $this->get() );
	}
}
