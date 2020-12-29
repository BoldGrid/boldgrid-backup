<?php
/**
 * Data class.
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
 * Class: Data
 *
 * Represents a type of data for a step. Such as "run" data or "step" data.
 *
 * @since SINCEVERSION
 */
class Data {
	/**
	 * The id of this data.
	 *
	 * IE "run" or "step".
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $id;

	/**
	 * Our parent step.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var \Boldgrid\Backup\V2\Step\Step
	 */
	private $step;

	/**
	 * Constructor.
	 *
	 * @since SINCEVERSION
	 *
	 * @param \Boldgrid\Backup\V2\Step\Step $step Our parent step.
	 * @param string                        $id   The id of our step.
	 */
	public function __construct( \Boldgrid\Backup\V2\Step\Step $step, $id ) {
		$this->step = $step;
		$this->id   = $id;
	}

	/**
	 * Get our data.
	 *
	 * @since SINCEVERSION
	 *
	 * @return array
	 */
	public function get_data() {
		$contents = $this->step->get_contents();

		return isset( $contents[ $this->id ] ) ? $contents[ $this->id ] : array();
	}

	/**
	 * Get a specific key from the data.
	 *
	 * @since SINCEVERSION
	 *
	 * @param  string $key The key to get.
	 * @param  mixed  $defalt The default value to return.
	 * @return mixed
	 */
	public function get_key( $key, $default = false ) {
		$data = $this->get_data();

		return isset( $data[ $key ] ) ? $data[ $key ] : $default;
	}

	/**
	 * Set a key.
	 *
	 * @since SINCEVERSION
	 *
	 * @param string $key   The key of the key/value pair.$this
	 * @param mixed  $value The value to save.
	 */
	public function set_key( $key, $value ) {
		$data         = $this->get_data();
		$data[ $key ] = $value;

		$this->step->write_key( $this->id, $data );
	}
}
