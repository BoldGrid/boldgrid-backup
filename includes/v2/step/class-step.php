<?php
/**
 * Step class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Step;

/**
 * Class: Step
 *
 * @since SINCEVERSION
 */
class Step {
	/**
	 * This step's id.
	 *
	 * @since SINCEVERSION
	 * @var string
	 */
	public $id;

	/**
	 * The core class object.
	 *
	 * @since  SINCEVERSION
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Data stored for this step.
	 *
	 * IE run data or step data.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var array
	 */
	private $data;

	/**
	 * The directory this step will live.
	 *
	 * IE backup-1234546-1234567812345678
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $dir;

	private $filename;

	/**
	 * The filepath to this step's json file.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $filepath;

	/**
	 * The max attempts to execute this step before giving up.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	private $max_attempts = 5;

	/**
	 *
	 */
	private $parent_id;

	/**
	 * Our persistent info class.
	 *
	 * @since SINCEVERSION
	 * @access protected
	 * @var \Boldgrid\Backup\V2\Step\Json_file
	 */
	protected $info;

	/**
	 * The number of seconds until a step is seen as unresonsive.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var int
	 */
	protected $unresponsive_time = 15;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $id  The id of this step.
	 * @param string $dir The directory where this step's data is saved.
	 */
	public function __construct( $id, $parent_id, $dir ) {
		$this->core = apply_filters( 'boldgrid_backup_get_core', false );

		$this->id        = $id;
		$this->parent_id = $parent_id;
		$this->dir       = trailingslashit( $dir );
		$this->filename  = 'step-' . $this->id . '.json';
		$this->filepath  = $this->dir . $this->filename;

		$this->data['run']  = new \Boldgrid\Backup\V2\Step\Data( $this, 'run_data' );
		$this->data['step'] = new \Boldgrid\Backup\V2\Step\Data( $this, 'step_data' );

		$this->info = new \Boldgrid\Backup\V2\Step\Json_file( $this->get_path_to( 'info.json' ) );

		add_filter( 'boldgrid_backup_get_step_' . $this->id, array( $this, 'get_this' ) );
	}

	/**
	 * Add an attempt for this step.
	 *
	 * @since SINCEVERSION
	 */
	public function add_attempt() {
		$attempts = $this->get_data_type( 'run' )->get_key( 'attempts', 0 );
		$attempts++;

		$this->get_data_type( 'run' )->set_key( 'attempts', $attempts );
		$this->get_data_type( 'run' )->set_key( 'start_time', time() );
		$this->get_data_type( 'run' )->set_key( 'memory_peak_start', memory_get_peak_usage() );
		$this->check_in();
	}

	/**
	 * Check in.
	 *
	 * Used to tell if the step is unresponsive.
	 *
	 * @since SINCEVERSION
	 */
	public function check_in() {
		$this->get_data_type( 'run' )->set_key( 'last_check_in', time() );

		// Whenever this step checks in, the parent should check in as well.
		$parent = $this->get_parent();
		if ( ! empty( $parent ) ) {
			$parent->check_in();
		}
	}

	/**
	 * Steps to take when this step is complete.
	 *
	 * @since SINCEVERSION
	 */
	public function complete() {
		$complete_time = time();
		$duration      = $complete_time - $this->get_data_type( 'run' )->get_key( 'start_time' );

		$this->get_data_type( 'run' )->set_key( 'complete_time', time() );
		$this->get_data_type( 'run' )->set_key( 'duration', $duration );

		$memory_peak_end    = memory_get_peak_usage();
		$memory_peak_change = $memory_peak_end - $this->get_data_type( 'run' )->get_key( 'memory_peak_start' );

		$this->get_data_type( 'run' )->set_key( 'memory_peak_end', $memory_peak_end );
		$this->get_data_type( 'run' )->set_key( 'memory_peak_change', $memory_peak_change );
	}

	/**
	 *
	 */
	public function fail( $message ) {
		$this->get_data_type( 'run' )->set_key( 'fail_time', time() );
		$this->get_data_type( 'run' )->set_key( 'fail_message', $message );

		$this->info->set_key( 'error', $message );

		$this->complete();
	}

	/**
	 * Get core.
	 *
	 * @since SINCEVERSION
	 *
	 * @return Boldgrid_Backup_Admin_Core
	 */
	public function get_core() {
		return $this->core;
	}

	/**
	 * Get the contents of our step's json file.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_contents() {
		$data = array();

		if ( $this->core->wp_filesystem->exists( $this->filepath ) ) {
			$json = $this->core->wp_filesystem->get_contents( $this->filepath );
			$data = json_decode( $json, true );
			$data = is_array( $data ) ? $data : array();
		}

		return $data;
	}

	/**
	 * Get our data type.
	 *
	 * Generally retrieved so we can add data to it.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $type The data type, such as "run" or "step".
	 * @return \Boldgrid\Backup\V2\Step\Data
	 */
	public function get_data_type( $type ) {
		return $this->data[ $type ];
	}

	/**
	 * Get the directory this step's data is saved to.
	 *
	 * @since SINCEVERSION
	 *
	 * @return string
	 */
	public function get_dir() {
		return $this->dir;
	}

	/**
	 *
	 */
	public function get_info() {
		return $this->info;
	}

	/**
	 *
	 */
	public function get_parent() {
		return $this->get_step( $this->parent_id );
	}

	/**
	 * Get the path to a file in our data directory.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $filename A filename.
	 * @return string
	 */
	public function get_path_to( $filename ) {
		return $this->get_dir() . $filename;
	}

	/**
	 *
	 */
	public function get_this() {
		return $this;
	}

	/**
	 *
	 */
	public function get_step( $id ) {
		return apply_filters( 'boldgrid_backup_get_step_' . $id, false );
	}

	/**
	 * Whether or not this step is complete.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_complete() {
		$data = $this->get_data_type( 'run' )->get_data();

		return ! empty( $data['complete_time'] );
	}

	/**
	 * Whether or not this step has failed.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_fail() {
		$data = $this->get_data_type( 'run' )->get_data();

		return ! empty( $data['fail_time'] );
	}

	/**
	 * Whether or not this step is in progress.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_in_progress() {
		$data = $this->get_data_type( 'run' )->get_data();

		// todo maybe add something about last check in and timeouts.
		return ! empty( $data['start_time'] ) && empty( $data['complete_time'] );
	}

	/**
	 * Whether or not we have retried too many times.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_max_attemps() {
		return $this->get_data_type( 'run' )->get_key( 'attempts', 0 ) >= $this->max_attempts;
	}

	/**
	 * Determine whether or not this step is unresponsive.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function is_unresponsive() {
		$time_since_checkin = time() - $this->get_data_type( 'run' )->get_key( 'last_check_in', 0 );

		return ! $this->is_complete() && $time_since_checkin >= $this->unresponsive_time;
	}

	/**
	 * Determine whether or not we should run this step.
	 *
	 * @since SINCEVERSION
	 *
	 * @return bool
	 */
	public function maybe_run() {
		// No matter what, if we've hit our max attempts, don't try again.
		if ( $this->is_max_attemps() ) {
			return false;
		}

		if ( $this->is_complete() ) {
			return false;
		}

		if ( $this->is_fail() ) {
			return false;
		}

		if ( $this->is_in_progress() && ! $this->is_unresponsive() ) {
			return false;
		}

		return true;
	}

	/**
	 * Write a file to the step's directory.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $filename The filename to write to.
	 * @param string $contents The contents to write to the file.
	 * @return bool True on success.
	 */
	public function write_contents( $filename, $contents ) {
		$written = $this->core->wp_filesystem->put_contents( $this->dir . $filename, $contents );

		return $written;
	}

	/**
	 * Write a key to this step's json file.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $key   The key of the key/value pair.
	 * @param mixed  $value The vale to the key/value pair.
	 */
	public function write_key( $key, $value ) {
		$contents         = $this->get_contents();
		$contents[ $key ] = $value;

		// old
		// return $this->core->wp_filesystem->put_contents( $this->filename, wp_json_encode( $contents ) );
		$this->write_contents( $this->filename, wp_json_encode( $contents ) );

		// $this->check_in();
	}
}
