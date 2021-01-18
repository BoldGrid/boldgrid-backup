<?php
/**
 * Option class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Option;

/**
 * Class: Option
 *
 * @since SINCEVERSION
 */
class Option {
	private $name;

	/**
	 *
	 */
	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 *
	 */
	public function delete() {
		delete_option( $this->name );
	}

	/**
	 *
	 */
	public function get() {
		return get_option( $this->name, array() );
	}

	/**
	 *
	 */
	public function get_key( $key, $default = false ) {
		$option = $this->get();

		return isset( $option[ $key ] ) ? $option[ $key ] : $default;
	}

	/**
	 *
	 */
	public function set_key( $key, $value ) {
		$option = $this->get();

		$option[ $key ] = $value;

		update_option( $this->name, $option );
	}
}
